<?php 

	class Shorturl extends CI_Controller {



		static $base32 = array (  
		    'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',  
		    'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p',  
		    'q', 'r', 's', 't', 'u', 'v', 'w', 'x',  
		    'y', 'z', '0', '1', '2', '3', '4', '5'  
		);

		

		public function __construct()
		{
			parent::__construct();
			$this->load->helper('url');
			$this->load->database();
			$this->base32Flip = array_flip(self::$base32);
			
		}


		//载入输入表单
		public function index()
		{
			// $str = "http://www.163.com/";
			// $lurl = explode("http://", string)
			//$this->load->model('surlmodel');
			//$this->surlmodel->loop();
			//$this->output->enable_profiler(TRUE);
			$this->load->view('shorturl.html');

		} 
		//保存网址方法3
		public function safeurl3()
		{

			$lurl = $this->input->post('lurl');
			$right_url = true;//checklurl($lurl);

			if ($right_url) {
			//长地址格式正确，插入数据库	
			//$surlhex = $this->shorturl($lurl);
			// print_r($lurl);
			// print_r($surlhex);
			$this->load->model('surlmodel');
			
			//先插入mysql数据库
			$id = $this->surlmodel->insert_entry2($lurl);
			$surlhex = $this->short_URL($id);

			//再载入redis缓存中
			$this->surlmodel->insert_entry3($surlhex,$lurl);
			// var_dump($var);
			$url['surl'] = site_url("/shorturl/jump3?hex=$surlhex");//返回给用户的短地址
			$this->load->view('shorturl.html',$url);

			}else{

				//调用js弹出提示框，‘地址格式不正确’；
			}
			
				

		}

		//跳转页面方法2
		public function jump3()
		{
			
		
		    $this->load->model('surlmodel');
			$hex = $this->input->get('hex');

		 
			$lurl = $this->surlmodel->getRow_redis($hex);


			//判定如果redis没有缓存数据，则从MySQL获取数据
			if (!empty($lurl)) {

				header('location:http://'.$lurl);
			}
			else{

				$hex = $this->input->get('hex');
				$id = $this->short_URL_flip($hex);
				$surl=$this->surlmodel->getRow('id',$id);
				header('location:http://'.$surl['lurl']);
			}
			
		}
		//保存网址方法2
		public function safeurl2()
		{
			//获取输入的长网址
			//$this->output->enable_profiler(TRUE);

			$this->base32Flip = array_flip(self::$base32);


			$lurl = $this->input->post('lurl');
			
			$this->load->model('surlmodel');
			$id = $this->surlmodel->insert_entry2($lurl);
			
			//将目标网址按算法缩短，得到算网址
			$surlhex = $this->short_URL($id);


			$url['surl'] = site_url("/shorturl/jump?hex=$surlhex");//返回给用户的短地址
			$this->load->view('shorturl.html',$url);


		}




		


		//跳转页面方法2
		 public function jump()
		 {


		 	//$this->output->enable_profiler(TRUE);
		 	//$this->base32Flip = array_flip(self::$base32);


		    
			$hex = $this->input->get('hex');
		    $id = $this->short_URL_flip($hex);

		    $this->load->model('surlmodel');
			$surl = $this->surlmodel->getRow('id',$id);
			// print_r($surl);
			header('location:http://'.$surl['lurl']);

		 } 




		//短网址算法2
		public function short_URL($id)
		{
			
			//$base = $this->base32;
			$base = self::$base32;

		    //位运算，获得10位数字字符串；
		    $str= sprintf('%08s', $id);
		    //按字符串长度循环，获取对应数值的$base32字符
		    $hex = '';
		    for ($i=0; $i < strlen($str); $i++) { 
		    	$var = $str[$i]; 
		    	$hex .= $base[$var]; 
		    }
		    //print_r($hex);
		    return $hex;

		} 

		public function short_URL_flip($hex)
		{
			
			$this->base32Flip = array_flip(self::$base32);
			$base32Flip = $this->base32Flip;

		 	for ($i=0; $i < strlen($hex); $i++) { 
		    	$var = $hex[$i]; 
		    	$id .= $base32Flip[$var]; 
		    }
		    return $id;
		}

		//保存网址方法1
		public function safeurl1()
		{
			//
			//$lurl = $this->input->post('lurl');
			
			$this->load->model('surlmodel');
			$lurl_is_empty = $this->surlmodel->checklurl($lurl);

			if ($lurl_is_empty) {
				//将目标网址按算法缩短，得到算网址
				$surlhex = $this->shorturl($lurl);
				$this->surlmodel->insert_entry($lurl,$surlhex);
				//print_r($id);
				$url['surl'] = site_url("/shorturl/jumpurl?hex=$surlhex");//返回给用户的短地址
				$this->load->view('shorturl.html',$url);
			
			}
			else
			{	
				$url = $this->surlmodel->getRow('lurl',$lurl);
				$surlhex = $url['surlhex'];
				$url['surl'] = site_url("/shorturl/jumpurl?hex=$surlhex");//返回给用户的短地址
				$this->load->view('shorturl.html',$url);					

			}

		}







		//跳转页面方法1
		public function jumpurl()
		{

			$this->load->model('surlmodel');
			$hex = $this->input->get('hex');
			$surl = $this->surlmodel->getRow('surlhex',$hex);
			// print_r($surl);
			header('location:http://'.$surl['lurl']);

		}


		//短网址算法1
		public function shorturl($input)
		{
			
			$base32 = array (  
			    'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',  
			    'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p',  
			    'q', 'r', 's', 't', 'u', 'v', 'w', 'x',  
			    'y', 'z', '0', '1', '2', '3', '4', '5'  
			    );  
			   
			$hex = md5($input);  
			$hexLen = strlen($hex);  
			$subHexLen = $hexLen / 8;  
			$output = array();  
			   
			for ($i = 0; $i < $subHexLen; $i++) {  
			  $subHex = substr ($hex, $i * 8, 8);  
			  $int = 0x3FFFFFFF & (1 * ('0x'.$subHex));  
			  $out = '';  
			   
			  for ($j = 0; $j < 6; $j++) {  
			    $val = 0x0000001F & $int;  
			    $out .= $base32[$val];  
			    $int = $int >> 5;  
			  }  
			   
			  $output[] = $out;  
			}  
			
			$key = array_rand($output);
			$outputhex = $output[$key];

			return $outputhex; 
			
		}






	}


 ?>