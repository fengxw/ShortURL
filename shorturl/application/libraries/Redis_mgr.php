<?php
class redis_mgr {
	protected $redis;
	protected $ip, $port, $is_open;
	static $servers = array ();
	
	/**
	 * 方法zUnion的参数aggregate的可选项
	 * SUM表示，同名的键将取所有score之和
	 */
	const PARAM_aggregate_SUM = 'SUM';
	/**
	 * 方法zUnion的参数aggregate的可选项
	 * MIN表示，同名的键将取所有score中最小值
	 */
	const PARAM_aggregate_MIN = 'MIN';
	/**
	 * 方法zUnion的参数aggregate的可选项
	 * MAX表示，同名的键将取所有score中最大值
	 */
	const PARAM_aggregate_MAX = 'MAX';
	
	/**
	 *
	 * @return redis_mgr
	 */
	static function getInstance($server = 'default',$redisConfig = array()) {
		if (isset ( self::$servers [$server] ))
			return self::$servers [$server];
		$ci = get_instance ();
		$var = __CLASS__;
		if ($server == 'default') {
			$redis = $ci->$var;
		} else {
			$redis = new $var ();
		}

		if (isset ( $ci->config->config ['redis_isopen'] ) && $ci->config->config ['redis_isopen']) {
			if (!empty($redisConfig)) {
				$ip = tool::getItem($redisConfig,'ip','127.0.0.1');
				$port = tool::getItem($redisConfig,'port',6379);
				$auth = tool::getItem($redisConfig,'auth','');
				$db = tool::getItem($redisConfig,'db','');
			}else{
				$ip = isset ( $ci->config->config ['redis'] [$server] ['ip'] ) ? $ci->config->config ['redis'] [$server] ['ip'] : '127.0.0.1';
				$port = isset ( $ci->config->config ['redis'] [$server] ['port'] ) ? $ci->config->config ['redis'] [$server] ['port'] : 6379;
				$auth = isset ( $ci->config->config ['redis'] [$server] ['auth'] ) ? $ci->config->config ['redis'] [$server] ['auth'] : '';
				$db = isset ( $ci->config->config ['redis'] [$server] ['db'] ) ? $ci->config->config ['redis'] [$server] ['db'] : 0;
			}
			$redis->connect ( $ip, $port, $auth, $db );
		}
		
		self::$servers [$server] = $redis;
		return $redis;
	}
	
	function close() {
		if ($this->is_connected ())
			$this->redis->close ();
	}
	
	/**
	 * 判断是否连接了缓存服务
	 *
	 * @return boolean
	 */
	public function is_connected() {
		return $this->redis ? true : false;
	}
	
	/**
	 * 连到指定服务
	 *
	 * @param string $ip        	
	 * @param string $port        	
	 * @param string $auth        	
	 * @param string $db        	
	 */
	public function connect($ip, $port, $auth, $db) {
		try {
			if (! $this->is_connected ())
				$this->redis = new Redis ();
			$this->ip = $ip;
			$this->port = $port;
			$ci = get_instance ();
			if (isset ( $ci->config->config ['redis_pconnect'] ) && $ci->config->config ['redis_pconnect']) {
				$r = $this->redis->pconnect ( $this->ip, $this->port, 1 );
			} else {
				$r = $this->redis->connect ( $this->ip, $this->port, 1 );
			}
			if ($r) {
				if ($auth)
					$this->redis->auth ( $auth );
				if ($db)
					$this->select ( $db );
			} else {
				unset ( $this->redis );
				$this->redis = null;
			}
		} catch ( Exception $e ) {
			unset ( $this->redis );
			$this->redis = null;
		}
	}
	
	/**
	 * 前端代码读写缓存。兼容memcache旧代码
	 *
	 * @param string $key
	 *        	键值，可为数组array(group值,键值)，group值可以用做批量删除
	 * @param callback $function        	
	 * @param array $params        	
	 * @param int $expire        	
	 * @return mixed
	 */
	public function getCache($key, $function, $params = array(), $expire = 0) {
		// 如果缓存没有开
		if (! $this->is_connected ()) {
			return call_user_func_array ( $function, $params );
		}
		if (is_array ( $key )) {
			$groupkey = $key [0];
			$key = $key [1];
		} else
			$groupkey = '';
		$group_expire = 0;
		if ($groupkey)
			$group_expire = intval ( $this->get ( "group_expire_" . $groupkey ) );
		if ($rs = $this->get ( $key )) {
			// 判断是否批量删除
			if ($groupkey && $group_expire > $rs ['ctime']) {
				// unset ( $rs );
				$rs = null;
			}
		}
		if (! $rs) {
			$rs = call_user_func_array ( $function, $params );
			if (! $rs)
				$expire = 5 * 60;
			$this->set ( $key, serialize ( array (
					'ctime' => $group_expire,
					'data' => $rs 
			) ), $expire );
		} else {
			$rs = $rs ['data'];
		}
		
		return $rs;
	}
	
	public function expireAt($key, $time) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->expireAt ( $key, $time );
	}
	
	/**
	 * 选择一个数据库
	 *
	 * @param int $db
	 *        	数据库id，范围0~15
	 */
	function select($db) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->select ( $db );
	}
	
	/**
	 * 设置一个值，仅当此值不存在时可以成功
	 *
	 * @param string $key        	
	 * @param string $value        	
	 */
	function add($key, $value, $expire = 0) {
		if (! $this->is_connected ()) {
			return false;
		}
		if (is_array ( $value ) || is_object ( $value ))
			$value = serialize ( $value );
		$r = $this->redis->setnx ( $key, $value );
		if ($r && $expire) {
			$this->redis->expire ( $key, $expire );
		}
		return $r;
	}
	
	/**
	 * 设置一个值
	 *
	 * @param string $key        	
	 * @param string $value        	
	 */
	function set($key, $value, $expire = 0) {
		if (! $this->is_connected ()) {
			return false;
		}

		if (is_array ( $value ) || is_object ( $value ))
			$value = serialize ( $value );
		if ($expire)
			return $this->redis->setex ( $key, $expire, $value );
		return $this->redis->set ( $key, $value );
	}
	
	/**
	 * 设置一个值，并返回他的原值
	 *
	 * @param string $key        	
	 * @param string $value        	
	 * @param string $expire        	
	 * @return string 这个键被set之前的原值
	 */
	function getSet($key, $value, $expire = 0) {
		if (! $this->is_connected ()) {
			return false;
		}
		$r = $this->redis->getSet ( $key, $value );
		if ($expire) {
			$this->redis->expire ( $key, $expire );
		}
		return $r;
	}
	
	/**
	 * 在一个值后面连接一个字符串
	 *
	 * @param string $key        	
	 * @param string $value        	
	 */
	function append($key, $value) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->append ( $key, $value );
	}
	
	/**
	 * 替换从pos位置开始的一段内容为value
	 *
	 * @param
	 *        	$key
	 * @param int $pos        	
	 * @param
	 *        	$value
	 * @return 返回替换完后，这个键的长度
	 */
	function setRange($key, $pos, $value) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->setRange ( $key, $pos, $value );
	}
	
	/**
	 * 键值是否存在
	 *
	 * @param string $key        	
	 */
	function exists($key) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->exists ( $key );
	}
	
	/**
	 * 取一个值
	 *
	 * @param string $key        	
	 */
	function get($key) {
		if (! $this->is_connected ()) {
			return false;
		}
		$r = $this->redis->get ( $key );
		$r2 = @unserialize ( $r );
		return $r2 !== false ? $r2 : $r;
	}
	
	/**
	 * 取一个值，不经过unserialize
	 *
	 * @param string $key        	
	 * @return boolean
	 */
	function getStr($key) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->get ( $key );
	}
	
	/**
	 * 删除一个或多个值
	 *
	 * @param string|array $key        	
	 */
	function clear($key) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->del ( $key );
	}
	
	/**
	 * 将一个值加上$value
	 *
	 * @param string $key        	
	 * @param int $value        	
	 * @return boolean
	 */
	function increment($key, $value) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->incrBy ( $key, $value );
	}
	
	/**
	 * 将一个值减去$value
	 *
	 * @param string $key        	
	 * @param int $value        	
	 * @return boolean
	 */
	function decrement($key, $value) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->decrBy ( $key, $value );
	}
	
	/**
	 * 批量清空使用getCache设置的缓存
	 *
	 * @param string $groupkey        	
	 * @return boolean
	 */
	public function group_clear($groupkey) {
		if (! $this->is_connected ()) {
			return false;
		}
		$groupkey = "group_expire_" . $groupkey;
		if (! $this->increment ( $groupkey, 1 ))
			$this->set ( $groupkey, 1 );
	}
	
	/**
	 * 设置有序集合
	 *
	 * @param string $key
	 *        	集合的key值
	 * @param string $zKey
	 *        	集合中的键名，不会重复
	 * @param int $score
	 *        	排序值，可重复
	 */
	function zSet($key, $zKey, $score) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->zAdd ( $key, $score, $zKey );
	}
	
	/**
	 * 得到有序集中value的排名（下标基数为0），正向为小到大
	 *
	 * @param string $key        	
	 * @param string $zKey        	
	 * @param bool $desc
	 *        	是否逆序从大到小
	 */
	function zRank($key, $zKey, $desc = false) {
		if (! $this->is_connected ()) {
			return false;
		}
		if ($desc)
			return $this->redis->zRevRank ( $key, $zKey );
		return $this->redis->zRank ( $key, $zKey );
	}
	
	/**
	 * 取一个范围的数据，start与end可以为负数，意为从尾部向前数
	 *
	 * @param string $key        	
	 * @param int $start
	 *        	分页开始坐标
	 * @param int $end
	 *        	分页结束坐标
	 * @param
	 *        	bool desc 是否逆序
	 * @param bool $withScore
	 *        	是否在结果中返回score
	 */
	function zGetPage($key, $start, $end, $desc = false, $withScore = false) {
		if (! $this->is_connected ()) {
			return false;
		}
		if ($desc)
			return $this->redis->zRevRange ( $key, $start, $end, $withScore );
		return $this->redis->zRange ( $key, $start, $end, $withScore );
	}
	
	/**
	 * 取集合中的一个值的score
	 *
	 * @param string $key        	 
	 * @param string $zKey        	
	 * @return boolean
	 */
	function zGet($key, $zKey) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->zScore ( $key, $zKey );
	}
	
	/**
	 * 取score范围在$min和$max之间的结果
	 *
	 * @param string $key        	
	 * @param string $min
	 *        	score的最小值，如果想要不限制最小值，这里输入字符串"-inf"
	 * @param string $max
	 *        	score的最大值，如果想要不限制最大值，这里输入字符串"+inf"
	 * @param int $start
	 *        	分页开始坐标
	 * @param int $limit
	 *        	分页记录条数（注意不是end）
	 * @param bool $desc
	 *        	是否逆序
	 * @param bool $withScore
	 *        	是否在结果中返回score
	 * @return array
	 */
	function zGetPageByScore($key, $min, $max, $start, $limit, $desc = false, $withScore = false) {
		if (! $this->is_connected ()) {
			return false;
		}
		$param = array (
				'withscores' => $withScore ? true : false 
		);
		if ($start !== null && $limit !== null) {
			$param ['limit'] = array (
					$start,
					$limit 
			);
		}
		if ($desc)
			return $this->redis->zRevRangeByScore ( $key, $max, $min, $param );
		return $this->redis->zRangeByScore ( $key, $min, $max, $param );
	}
	
	/**
	 * 删除集合中的value
	 *
	 * @param string $key        	
	 * @param string $zKey        	
	 */
	function zClear($key, $zKey) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->zRem ( $key, $zKey );
	}
	
	/**
	 * 删除集合内，socre大小从$start到$end的值
	 *
	 * @param int $start        	
	 * @param int $end        	
	 */
	function zClearByScore($key, $start, $end) {
		return $this->redis->zRemRangeByScore ( $key, $start, $end );
	}
	
	/**
	 * 删除集合内从$start到$end位置的值
	 *
	 * @param double $start        	
	 * @param double $end        	
	 */
	function zClearByRank($key, $start, $end) {
		return $this->redis->zRemRangeByRank ( $key, $start, $end );
	}
	
	/**
	 * 返回在$min和$max之前的记录数
	 *
	 * @param string $key        	
	 * @param int $min        	
	 * @param int $max        	
	 * @return boolean
	 */
	function zCount($key, $min, $max) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->zCount ( $key, $min, $max );
	}
	
	/**
	 * 返回一个集合的总记录数
	 *
	 * @param string $key        	
	 * @return boolean
	 */
	function zCountAll($key) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->zCard ( $key );
	}
	
	/**
	 * 将集合中的一个值加上$incr
	 *
	 * @param string $key        	
	 * @param string $zKey        	
	 * @param double $incr        	
	 * @return boolean
	 */
	function zIncrement($key, $zKey, $incr) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->zIncrBy ( $key, $incr, $zKey );
	}
	
	/**
	 * 取多个集合的并集 (慎用，注意性能问题，复杂度O(m+n)）
	 *
	 * @param string $toKey
	 *        	结果写入到的key
	 * @param array $fromKeys
	 *        	要合并的key的数组
	 * @param array $weights
	 *        	权重数组，合并前$fromKey值对应的score会乘以对应的$weights中的权重
	 * @param string $aggregate
	 *        	合并方式，见redis_mgr::PARAM_aggregate_
	 */
	function zUnion($toKey, $fromKeys, $weights = array(), $aggregate = "SUM") {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->zUnion ( $toKey, $fromKeys, $weights, $aggregate );
	}
	
	/**
	 * 取多个集合的交集(慎用，注意性能问题，复杂度O(m+n)）
	 *
	 * @param string $toKey
	 *        	结果写入到的key
	 * @param array $fromKeys
	 *        	要合并的key的数组
	 * @param array $weights
	 *        	权重数组，合并前$fromKey值对应的score会乘以对应的$weights中的权重
	 * @param string $aggregate
	 *        	合并方式，见redis_mgr::PARAM_aggregate_
	 */
	function zInter($toKey, $fromKeys, $weights = array(), $aggregate = "SUM") {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->zInter ( $toKey, $fromKeys, $weights, $aggregate );
	}
	
	/**
	 * 删除hash表中的一个值
	 *
	 * @param string $key        	
	 * @param string $hashKey        	
	 */
	function hClear($key, $hashKey) {
		if (! $this->is_connected ()) {
			return false;
		}
		$this->redis->hDel ( $key, $hashKey );
	}
	
	/**
	 * 判断hash表中一个值是否存在
	 *
	 * @param string $key        	
	 * @param string $hashKey        	
	 */
	function hExists($key, $hashKey) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->hExists ( $key, $hashKey );
	}
	
	/**
	 * 取一个hash表中一个值
	 *
	 * @param string $key        	
	 * @param string $hashKey        	
	 */
	function hGet($key, $hashKey) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->hGet ( $key, $hashKey );
	}
	
	/**
	 * 返回一个hash表中所有数据（慎用）
	 *
	 * @param string $key        	
	 */
	function hGetAll($key) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->hGetAll ( $key );
	}
	
	/**
	 * 取得hash表中的所有hashKey（慎用）
	 *
	 * @param string $key        	
	 */
	function hKeys($key) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->hKeys ( $key );
	}
	
	/**
	 * 返回hash中所有的value（慎用）
	 *
	 * @param string $key        	
	 */
	function hVals($key) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->hVals ( $key );
	}
	
	/**
	 * hash表中的一个值加上value
	 *
	 * @param string $key        	
	 * @param string $hashKey        	
	 * @param int $value        	
	 */
	function hIncrBy($key, $hashKey, $value) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->hIncrBy ( $key, $hashKey, $value );
	}
	/**
	 * hash表中的一个值加上value（浮点）
	 *
	 * @param string $key        	
	 * @param string $hashKey        	
	 * @param float $value        	
	 */
	function hIncrByFloat($key, $hashKey, $value) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->hIncrByFloat ( $key, $hashKey, $value );
	}
	
	/**
	 * 取hash表内成员数量
	 *
	 * @param string $key        	
	 */
	function hLen($key) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->hLen ( $key );
	}
	
	/**
	 * 批量取hash中的值
	 *
	 * @param string $key        	
	 * @param array $hashKeys        	
	 */
	function hMGet($key, $hashKeys) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->hMGet ( $key, $hashKeys );
	}
	/**
	 * 批量设置hash中的值
	 *
	 * @param string $key        	
	 * @param array $values
	 *        	hashKey=>value
	 */
	function hMSet($key, $values) {
		if (! $this->is_connected ()) {
			return false;
		}

		return $this->redis->hMSet ( $key, $values );
	}
	
	/**
	 * 设置hash表中一个值
	 *
	 * @param string $key        	
	 * @param string $hashKey        	
	 * @param string $value        	
	 */
	function hSet($key, $hashKey, $value) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->hSet ( $key, $hashKey, $value );
	}
	/**
	 * 设置hash表中一个值，只有这个值不存在时才能成功
	 *
	 * @param string $key        	
	 * @param string $hashKey        	
	 * @param string $value        	
	 */
	function hAdd($key, $hashKey, $value) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->hSetNx ( $key, $hashKey, $value );
	}
	
	/**
	 * 获取list队列里，位置从start到end的数据（数据不弹出）
	 *
	 * @param type $key        	
	 * @param type $start        	
	 * @param type $end        	
	 */
	function lGetRange($key, $start, $end) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->lRange ( $key, $start, $end );
	}
	
	/**
	 * 弹出list中的元素
	 *
	 * @param string $key        	
	 * @return string
	 */
	function lPop($key) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->lPop ( $key );
	}
	
	/**
	 * 入队列
	 *
	 * @param type $key        	
	 * @param type $value        	
	 * @return boolean
	 */
	function lSet($key, $value) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->lPush ( $key, $value );
	}
	
	/**
	 * 只保留一个队列中start~stop下标的项
	 *
	 * @param string $key        	
	 * @param int $start        	
	 * @param int $stop        	
	 */
	function lTrim($key, $start, $stop) {
		return $this->redis->lTrim ( $key, $start, $stop );
	}
	/**
	 * 计算key的长度
	 *
	 * @param type $key        	
	 */
	function lCount($key) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->LLEN ( $key );
	}
	
    function lIndex( $key, $val ){ 
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->LINDEX ( $key, $val );        
    }
    /**
	 * 写集合
	 *
	 * @param type $key        	
	 * @param type $value        	
	 */
	function sSet($key, $value) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->sAdd ( $key, $value );
	}
	
	/**
	 * 移除
	 *
	 * @param type $key        	
	 * @param type $value        	
	 */
	function sClear($key, $value) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->sRem ( $key, $value );
	}
	
	/**
	 * 取集合数量
	 *
	 * @param type $key        	
	 */
	function sCount($key) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->sSize ( $key );
	}
	
	/**
	 * 取集合里面的所有数据
	 *
	 * @param type $key        	
	 */
	function sMembers($key) {
		if (! $this->is_connected ()) {
			return false;
		}
		return $this->redis->sMembers ( $key );
	}
	#hScan() {
	

	function bin() {
		$this->redis->setOption ( Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE );
	}
	
	/**
	 * 修改名称
	 *
	 * @param type $key
	 *        	源key
	 * @param type $tarkey
	 *        	目标key
	 */
	function rename($key, $tarkey) {
		$this->redis->rename ( $key, $tarkey );
	}
}

?>
