<?php 
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Hello extends CI_Controller {

	function sayhello(){ 
		echo 'Hello World!';

	}

	function showview(){ 


		$name = "feng";
		@$count = file_get_contents('./num.txt');

		$count = $count?$count:0;

		$count++;

		$data = array('v_name' => $name,'v_count'=>$count );
		$re= fopen('./num.txt','w');
		fwrite($re, $count);
		fclose($re);
		$this->load->view('test_view',$data);
		$this->load->view('test_view_1');
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */