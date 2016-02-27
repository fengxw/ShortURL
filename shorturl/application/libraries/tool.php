<?php
	class tool {
		const INT = 'int';
		const STRING = 'string';

		/**
		 * 创建数据库主库连接
		 */
		public static function createMaster($return = FALSE)
		{			
			static $db;
			if (empty($db)) {
				$ci = get_instance();
				$db = $ci->load->database ( '', $return );
			}	
			return $db;
		}

		public static function createSlave($return = true)
		{
			static $db;
			if (empty($db)) {
				$ci = get_instance();
				$db = $ci->load->database ( 'backup', $return );
			}

			return $db;
		}

		/**
		 * 获取字符串哈希值
		 */
		public static function getHash($keys)
		{
			if (!is_array($keys)) return crc32($keys);
			foreach ($keys as &$key) {
				$key = crc32($key);
			}
			return $keys;
		}

		/**
		 * 时间标记
		 */
		public static  function markTime($status=''){
			static $i=1;static $tempTime=0,$tempTime1=0;
			$tempTime = $tempTime?$tempTime:self::microtime_float();
			$diffTime = self::microtime_float()-$tempTime;
			$diffTime1 = self::microtime_float()-$tempTime1;
			$tempTime1 = self::microtime_float();
			if($diffTime1>0.02 || in_array($status, array('5.0.2.1','10.1','5.0.0.2')) || 1==1){	//|| 1==1
				echo "time_$i    ".self::microtime_float()."___".$diffTime."----".$diffTime1.'--'.$status."<br>";
				echo number_format(memory_get_usage())."<br>";
			}
			$i++;
		}
		public static function microtime_float()
		{
			list($usec, $sec) = explode(" ", microtime());
			return ((float)$usec + (float)$sec);
		}

		/**
		 * 获取数组元素
		 */
		public static function getItem($arr, $key, $default = '')
		{
			return isset($arr[$key])?$arr[$key]:$default;
		}



		/**
		 * 指定数组中某个字段为索引
		 * @param array $arr
		 * @param string $key 索引字段
		 * e.g:  array(0=>array('id'=>22),1=>array('id'=>23),) 转换为 array(22=>array('id'=>22),23=>array('id'=>23),)
		 */
	    public static function  setArrayKey($arr = array(), $key = "")
	    {
	        $new_arr = array();
	        foreach ($arr as $item) {
	            if (isset($item[$key])) {
	                $new_arr[$item[$key]] = $item;
	            } else {
	                $new_arr[] = $item;
	            }
	        }

	        return $new_arr;
	    }

	    /**
	     * 设置数组索引与值
	     * e.g:  array(0=>array('id'=>22,'value'=>2),1=>array('id'=>23,,'value'=>3),) 转换为 array(22=>2,23=>3)
	     */
	    public static function  setArrayKeyValue($arr = array(), $indexKey = "",$valueKey="")
	    {
	        $new_arr = array();
	        foreach ($arr as $item) {
	            if (isset($item[$indexKey])) {
	                $new_arr[$item[$indexKey]] = self::getItem($item,$valueKey);
	            } else {
	                $new_arr[] = self::getItem($item,$valueKey);
	            }
	        }

	        return $new_arr;
	    }

	    /**
	     * 以数组中某个索引来分组
	     */
	    public static function groupArrayByKey($array = array(), $key="")
	    {
	    	if (empty($array)) {
	    		return $array;
	    	}
	    	$new_arr = array();
	        foreach ($array as $item) {
	            if (isset($item[$key])) {
	            	// var_dump($item[$key]);
	            	// exit;
	                $new_arr[$item[$key]][] = $item;
	            } else {
	                $new_arr[] = $item;
	            }
	        }

	        return $new_arr;
	    }
	    /**
	     * 以数组中某个索引来分组
	     */
	    public static function groupArrayBySubKey($array = array(), $key="",$subKey = '')
	    {
	    	$new_arr = array();
	        foreach ($array as $item) {
	            if (isset($item[$key])) {
	            	if (isset($item[$subKey])) {
	            		$new_arr[$item[$key]][$item[$subKey]] = $item;
	            	}else{
	            		$new_arr[$item[$key]][] = $item;
	            	}
	                
	            } else {
	                $new_arr[] = $item;
	            }
	        }

	        return $new_arr;
	    }


	    /**
	     * 数组键名映射
		 * @param array $arr
		 * @param array $maps 保存旧键名到新键名映射关系的数组
	     */
	    public static function mapArrayKeyName($arr, $maps)
	    {
	    	foreach ($arr as &$value) {

	    		foreach ($maps as $key => $newKey) {
	    			if (isset($value[$key])) {
	    				$value[$newKey] = $value[$key];
	    				unset($value[$key]);
	    			}
	    		}
	    	}
	    	return $arr;
	    }

	    /**
	     * 过滤值不在 指定集合中的行
	     */
	    public function getArrayByValues($arr,$key,$vals)
	    {
	    	foreach ($arr as $index => $value) {
	    		if (!isset($value[$key]) || !in_array($value[$key], $vals)) {
	    			unset($arr[$index]);
	    		}

	    	}
	    	return $arr;
	    }


	    /**
	     * 获取二维数组中的部分字段
	     * @param $ifFill 是否填充
	     */
	    public static function getArrayColumns($arr,$keys,$ifFill=false)
	    {
	    	$list = array();
	    	foreach ($arr as $value) {
	    		$row = array();
	    		foreach ( $keys as $key ) {
	    			if (isset($value[$key])) {
	    				$row[$key] = $value[$key];
	    			}elseif ($ifFill) {
	    				$row[$key] = '';
	    			}
	    		}
	    		$list[] = $row;
	    	}
	    	return $list;
	    }

	    /**
	     * 获取二维数组中的某个字段
	     * @param $ifFill 是否填充
	     */
	    public static function getArrayColumn($arr,$key,$ifFill=false)
	    {
	    	$list = array();
	    	foreach ($arr as $value) {
	    		if (isset($value[$key])) {
    				$item = $value[$key];
    			}elseif ($ifFill) {
    				$item = '';
    			}
	    		$list[] = $item;
	    	}
	    	return $list;
	    }


	    /**
	     * 对数组进行条件过滤
	     */
	    public static function filterArray($arr,$filter)
	    {
	    	foreach ($arr as $index => $row) {
	    		$ifUnset = false;
	    		//等于
	    		if (isset($filter['eq'])) {
	    			foreach ($filter['eq'] as $key => $value) {
	    				if (!isset($row[$key]) || $value != $row[$key]) {		//不存在对应的键，或者值与过滤条件匹配
	    					unset($arr[$index]);
	    					$ifUnset = true;
	    				}
	    			}
	    		}
		    	//小于
		    	if (isset($filter['lt']) && !$ifUnset) {
		    		foreach ($filter['lt'] as $key => $value) {
	    				if (!isset($row[$key]) ||  $row[$key]>=$value ) {		//不存在对应的键，或者值与过滤条件匹配
	    					unset($arr[$index]);
	    					$ifUnset = true;
	    				}
	    			}
		    	}

		    	//大于
		    	if (isset($filter['gt'])  && !$ifUnset) {
		    		foreach ($filter['gt'] as $key => $value) {

	    				if (!isset($row[$key]) ||  $row[$key]<= $value ) {		//不存在对应的键，或者值与过滤条件匹配
	    					unset($arr[$index]);
	    					$ifUnset = true;
	    				}
	    			}
		    	}
    		}
	    	$arr = array_values($arr);	//重建数字索引
	    	return $arr;
	    		
	    }

	    /**
	     * 过滤为空的元素
	     */
	    public static function filterEmpty($arr,$keys=array(),$ifMulti=false)
	    {
	    	$arr = $ifMulti?$arr:array($arr);
	    	foreach ($arr as &$row) {
	    		foreach ($row as $key=>$value) {
		    		if (empty($value) && (empty($keys) || in_array($key, $keys)) ) {
		    			unset($row[$key]);
		    		}
		    	}
	    	}
		    	
	    	return $ifMulti?$arr:current($arr);
	    }

	    /**
	     * 过滤为空的一维数组
	     */
	    public static function filterOneLevelEmpty($array)
	    {
	    	if (!is_array($array)) {
	    		return array();
	    	}
	    	foreach ($array as $key => $value) {
	    		if (empty($value)) {
	    			unset($array[$key]);
	    		}
	    	}
	    	return $array;
	    }

	    /**
	     * 过滤不在范围内的数字
	     */
	    public static function filterRange($array,$min,$max)
	    {
	    	foreach ($array as $key=>$value) {
	    		$value = (int)$value;
	    		$array[$key] = $value;
	    		if ($value<$min || $value>$max) {
	    			unset($array[$key]);
	    		}
	    	}
	    	return $array;
	    }


	    /**
	     * 将特定格式的字符串转为数组[用于 group/poi_to_group]
	     * 用于数据库查询：包括条件值及其查询范围
	     * @param string $poi_and_index 待转换的字符串 e.g: poi_and_index=1:0:10,39:0:9,395:0:1
	     * @param array $poiRange 转换后的数组 e.g: array ( 1 => array (  0, 10 ), 39 => array (  0, 9 ), 395 => array (  0,  1 ) )
	     */
	    public static function getKeyAndRange($poi_and_index)
	    {
	    	$poiRange = array();
	    	$poiList = explode(',', $poi_and_index);

	    	foreach ($poiList as  $value) {
	    		$row = explode(':', $value);
	    		if (count($row)==3) {
	    			$key = $row[0];			//条件值
	    			unset($row[0]);
	    			$row = array_values($row);
	    			$poiRange[$key] = $row;
	    		}
	    		
	    	}

	    	return $poiRange;

	    }

	    /**
	     * 从范围获取列表
	     */
	    public static function getListFromRange($range)
	    {
	    	list($low, $up) = $range;
	    	$list = array();
	    	for ($i=$low; $i < $up; $i++) { 
	    		$list[] = $i;
	    	}

	    	return $list;
	    }


	    /**
	     * 检查方法在当前版本是否允许执行
	     */
	    public static function checkMethodActiveByApv($classMethod = array(), $appVersion)
	    {
	    	$appVersion = rd_user_appversion_model::get_apv($appVersion);
	    	//要限制版本执行的方法
	    	$classMethodActiveInfo = array(
	    		'cw_poi_model' => array(
	    				'upSetPoi' => array('2.8.0',0),		//新增/编辑群组时，保存poi信息

	    		),
	    		// 'cw_poi_model' => array(
	    		// 		'updateGroupNum' => array('2.8.0',0),		//新增/编辑/批量生成 群组poi时，保存poi信息
	    		// ),
	    		
	    	);


	    	list($class,$method) = $classMethod;


	    	if (isset($classMethodActiveInfo[$class]) && isset($classMethodActiveInfo[$class][$method])) {
	    		list($beginApv,$endApv) = $classMethodActiveInfo[$class][$method];
	    		$beginApv =rd_user_appversion_model::get_apv($beginApv);
	    		$endApv =rd_user_appversion_model::get_apv($endApv);

	    		if ($appVersion>=$beginApv && (!$endApv || $appVersion<=$endApv)) {		//输入的app版本在限制的版本范围内
	    			return true;
	    		}
	    		return false;
	    	}

	    	return true;
	    }

	    /**
	     * 对数组的特定字段值进行类型转换
	     */
	    public static function convertArrayType($arr,$convertMap)
	    {
	    	// $keys = array_keys($convertMap);

	    	foreach ($arr as  &$row) {
	    		foreach ($convertMap as $key => $type) {
	    			if (isset($row[$key])) {
	    				$row[$key] = self::convertType($row[$key],$type);
	    			}
	    		}
	    	}

	    	return $arr;
	    }

	    /**
	     * 对一维数组进行类型转换
	     */
	    public static function convertOneLevelArrayType($arr,$type)
	    {
	    	foreach ($arr as &$item) {
	    		$item = self::convertType($item,$type);
	    	}
	    	return $arr;
	    }

	    /**
	     * 类型转换
	     */
	    public static function convertType($value,$type)
	    {
	    	switch ($type) {
	    		case self::INT:
	    			return intval($value);
	    			break;
	    		case self::STRING:
	    			return is_array($value)?'':(string)$value;
	    			break;
	    		
	    		default:
	    			return $value;
	    			break;
	    	}
	    	
	    }


	    /**
	     * 检查数组中的指定元素是否为空
	     */
	    public static function checkEmptyItem($arr, $requireKeys)
	    {
	    	foreach ($requireKeys as $key) {
	    		if (!isset($arr[$key]) || empty($arr[$key])) {
	    			return false;
	    		}
	    	}
	    	return true;
	    }




		/**
		 * 获取动态时间[用于活动动态与群组动态]
		 */
		public static function getStreamTime($msgKey)
		{
			$streamInfo = self::getStreamInfo($msgKey);
			$lastTime = tool::getItem($streamInfo,2,0);
			return $lastTime;
		}


		/**
		 * 获取动态id[用于活动动态与群组动态]
		 */
		public static function getStreamId($msgKey)
		{
			$streamInfo = self::getStreamInfo($msgKey);
			$streamId = tool::getItem($streamInfo,0,0);
			return $streamId;
		}

		/**
		 * 获取动态id[用于活动动态与群组动态]
		 */
		public static function getStreamType($msgKey)
		{
			$streamInfo = self::getStreamInfo($msgKey);
			$type = tool::getItem($streamInfo,1,0);
			return $type;
		}

		/**
		 * 解出动态信息
		 */
		public static function getStreamInfo($msgKey)
		{
			if (is_array($msgKey)) {
				$msgKey = array_keys($msgKey);
				$msgKey = current($msgKey);
			}
			
			$row =  explode('_', $msgKey);

			return $row;
		}





		/**
		 * 给文本中的url添加A标签
		 */
		public static function matchMarkUrl($content)
		{

			//匹配，替换email为其md5值
	        $email_pattern = '/[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/';

	        // $content = preg_replace($email_pattern, self::storeEmail("\\0"), $content);
	        $content = preg_replace_callback($email_pattern, "self::storeEmail", $content);

	        //匹配链接
			$regex = '#[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~\#?&//=]*)?#si';

			$content = preg_replace($regex, "<a href='\\0' class='url'><img src='http://picture.eclicks.cn/carwheel/style26/url.png'/></a>", $content);

			
			//将email替换回来
			$emailMap = self::getStroeEmail();
			foreach ($emailMap as $key => $email) {
				$content = str_replace($key, $email, $content);
			}

			return $content;
		}


		//防止email被识别为url,先将email替换为其MD5值，url识别后再替换回去
		public static function storeEmail($email='',$ifReturnMap = false)
		{
			static $emailMap = array();

			$email = is_array($email)?current($email):$email;

			if (!empty($email)) {
				$key = md5($email);
				$emailMap[$key] = $email;
			}
			
			return $ifReturnMap?$emailMap:$key;
		}

		public static function getStroeEmail()
		{
			$ifReturnMap = true;
			return self::storeEmail('',$ifReturnMap);
		}



		/**
		 * 获取当天0点时间戳
		 */
		public static function getDayTime($time=0,$diffDays = 0)
	    {
	    	$time = $time?$time:time();

	    	$diffDays && $time += $diffDays*86400;	//
	        return strtotime(date("y-m-d",$time));
	    }



	    /**
	     * 彩蛋
	     */
	    public static function getLuck()
	    {
	    	$lucky = array(
	    		'share_type' => 1,
	    		'share_content' => 'xxx',
	    		'share_thumb' => 'http://www.e.com/1.jpg',
	    		'share_title' => '我中大奖了',
	    		'share_url' => 'http://www.baidu.com',
	    		'type' => 1,
	    		'content' => 'ssfsdfsfsfd',
	    		'jump_url' => 'http://www.baidu.com',

			);
			return $lucky;
	    }

	    /**
	     * 写入文件
	     */
	    public static function writeFile($fileName,$content)
	    {
	    	$bytesNum = file_put_contents($fileName,$content,FILE_APPEND);

	    	return $bytesNum;
	    }


	    /**
		 * 判断运行环境是否为64位系统
		 *
		 * @return bool
		 */
		public static function is64Bit(){
		    // 左移32位之后会超过32位系统的限制，如果64位系统则不会
		    return (1<<32)==1 ? false : true;
		}

		/**
		 * 获取最大的整形
		 */
		public static function getMaxInt()
		{
			return PHP_INT_MAX;
		}


		/**
		 * 判断是否开发环境
		 */
		public static function isDev()
		{
			if ('development'==ENVIRONMENT) {
				return true;
			}
			return false;
		}


		/**
		 * 计算反向时间[即时间越大其值越小]
		 */
		public static function getRtime($time)
		{
			$beginTime = mktime(0,0,0,1,1,2012);

			return $beginTime-$time;
			
		}



		/**
		 * 获取随机数
		 */
		public static function getRand($min,$max,$exclude=array(),$try=100)
		{
			$val = rand($min,$max);

			if (!in_array($val, $exclude)) {
				return $val;
			}else{
				return self::getRand($min,$max,$exclude,--$try);
			}

		}


		/**
		 * 获取帖子标题[标题为空时截取内容]
		 */
		public static function getTitle($title,$content)
		{
			$emoji = emoji::getInstance ();
			if ($title) {
                $title = $emoji->emoji_unified_to_html( htmlentities( $title, null, "UTF-8" ) );
            }else{
                $title = $emoji->emoji_unified_to_html( htmlentities( mb4_substr($content,0,30), null, "UTF-8" ) );
            }

            return $title;
		}



		/**
		 * 将图片/音频 地址补充完整
		 */
		public static function formatImg($url)
		{
			$ci = get_instance ();
			$imgurl = $ci->config->config ['picture_server'];
        	$mediaurl = $ci->config->config['media_server'];

			$info = array();

			if( preg_match('/^.+_(\d+)(\.eav)$/is', $url, $var) ){
                $url = $mediaurl . $url;
                $info['sound_time'] = $var[1];
                $info['url'] = $url;
            }elseif (preg_match ( '#^.+_(\d+)_(\d+)\.(jpg|jpeg|png|gif)$#is', $url, $ar )) {
                $url = $imgurl . $url;
				$info ['width'] = $ar [1];
				$info ['height'] = $ar [2];
				$info['url'] = $url;
			} else {
                $url = $imgurl . $url;
				$info ['width'] = 600;
				$info ['height'] = 800;
				$info['url'] = $url;
			}
			return $info;
		}

	}