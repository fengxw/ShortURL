<?php 
		
	/**
	* 
	*/
	class Saddrmodel extends CI_Model
	{
		
		function __construct()
		{
			parent::__construct();
		}




		function insert_Entries()
		{

			$this->laddr = $this->input->post('laddr'); //从表单获取长地址
			$id = $this->db->insert('saddr',$this);		//将长地址存入数据库,并获取ID号
			return $id;									//返回ID

		}

		function getAddr($id)
		{

			$result = $this->db->where('id',$id)->get('saddr')->row_array();
			return $result;
		}



	}


 ?>