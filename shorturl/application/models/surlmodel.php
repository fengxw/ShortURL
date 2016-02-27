<?php 
	class Surlmodel extends CI_Model{


		function __construct()
		{
			parent::__construct();
			$this->load->library('redis_mgr');

		}


		//插入方法3
		public function insert_entry3($surl,$lurl)
		{
			$redis = redis_mgr::getInstance();
			$redis->set($surl,$lurl);
			// if ($redis->set($surl,$lurl)) {
			// 	return true;
			// }else{
			// 	return false;
			// 	}
		}

		//用获取redis缓存数据
		public function getRow_redis($surl)
		{
			
			$redis = redis_mgr::getInstance();
			$lurl = $redis->get($surl);

			return $lurl;
		}

		//插入方法2
		public function insert_entry2($lurl)
		{

			$arrlurl = array('lurl' => $lurl);
			//print_r($arrlurl);
			$this->db->insert('surl',$arrlurl);

			$id = $this->db->insert_id();
			return $id;

		}

		//插入方法1
		public function insert_entry1($lurl,$surlhex)
		{

			$arrlurl = array('lurl' => $lurl,'surlhex'=> $surlhex);
			//print_r($arrlurl);
			$this->db->insert('surl',$arrlurl);

			//$this->db->insert_id();


		}


		public function getRow($field = '',$surlhex)
		{
			$arr = array($field => $surlhex );

			$result = $this->db->where($arr)->get('surl')->row_array();
			
			return $result;
		}

		public function checklurl($lurl)
		{
			$arr = array('lurl' => $lurl );
			$num_rows = $this->db->where($arr)->get('surl')->num_rows();
			
			if ($num_rows == 0) {
				return true;
			}
			else
			{
				return false;
			}

		}


		public function loop()
				{
					set_time_limit(0);
					for ($i=0; $i < 50; $i++) { 
						$this->loop_insert();
					}
				}

						
		public function loop_insert()
		{
			$str = array();
			$str1 = array();

			for ($i=0; $i < 10000; $i++) { 
				
				$str = $this->generate_string(7);
				$str1 = $this->generate_string(7);
				$lurl = 'www.'.$str1[0].'.com';


				$arrlurl = array('lurl' => $lurl,'surlhex'=> $str[0],'surlord'=>$str[1]);
				


				$data[] = $arrlurl;

				//print_r($data);
				// $data = array(
				//    array(
				//       'title' => 'My title' ,
				//       'name' => 'My Name' ,
				//       'date' => 'My date'
				//    ),
				//    array(
				//       'title' => 'Another title' ,
				//       'name' => 'Another Name' ,
				//       'date' => 'Another date'
				//    )
				// );

			}

			$this->db->insert_batch('surl1',$data);

		}








		public function generate_string( $length = 6 ) { 

			// 密码字符集，可任意添加你需要的字符  
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';  
			$str = '';  
			$ord = '';
			for ( $i = 0; $i < $length; $i++ )  
			{  
		 
				$str .= $chars[ mt_rand(0, strlen($chars) - 1) ];  
				$ord .= ord($str[$i]);
			}  
			return array($str,$ord);  
		}




	}

?>