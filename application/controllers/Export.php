<?php
defined('BASEPATH') OR exit('此文件不可被直接访问');
/**
 * Created by PhpStorm.
 * User: imfoolish
 * Date: 2018/5/29
 * Time: 17:21
 */


class Export extends MY_Controller
{	

	public function __construct(){
		parent::__construct();
		$this->setmenu(__CLASS__);
	}

	//网页入口
	public function index(){
		$this->output->delete_cache();
		$this->load->view('excel/header.html', $this->menuClass);
		$this->load->view('excel/export-index.html');
	}


	//php cli : php index.php Export getresource $class
	public function getresource($argv = ''){
		$classname = empty($argv) ? $this->input->get('classname') : $argv;
		if(empty($classname)){
			echo json_encode(['status'=>1, 'msg'=>'输入错误']);
			exit;
		}
		$funcname  = 'index';//$this->input->get('funcname');
		$curllink  = $this->env[ENVIRONMENT] . $classname . '/' . $funcname;

		$http = new Curl();
		$request = ['offset'=>0, 'limit'=>2000];//以后数据过大时要分页读数据避免内存泄露。。。
		$this->withsignature($request);
		$data = $http->go($curllink, $request);

		//接口返回数据正常
		if(is_array($data) && $data['status'] == 200 && is_array($data['content'])){
			//尝试创建excel文件
			$res = $this->generatexlsx($data['content'], [$classname, $funcname]);
			if ($res) {
				echo json_encode(['status'=>0, 'msg'=>'', 'path'=> site_url($res)]);
			} else {
				echo json_encode(['status'=>1, 'msg'=>'生成文件失败，检查可能原因']);	
			}
		} else {
			$msg = '';
			if(is_array($data) && isset($data['status'])){
				$msg = $data['content']['error']['message'];
			} else {
				$msg = '检查接口是否存在';
			}
			echo json_encode(['status'=>1, 'msg'=>$msg]);
		}
		exit;
	}

	//写入一行excel文件
	private function writedata($sheet, $data, $line = 1)
	{	
		$alpha  = 65; //A
        $prefix = '';
		foreach ($data as $key => $value) {
        	if(is_array($value)){
        		continue;
        	}
			$column = $this->getcolumn($alpha, $prefix);
        	$sheet->getActiveSheet()->setCellValue($column . $line, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
	}

	//创建excel文件
	private function generatexlsx($data, $link){
		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$spreadsheet->getProperties()
				    ->setCreator("Yibang Huangxin programer")
				    ->setLastModifiedBy("")
				    ->setTitle(implode('/', $link))
				    ->setSubject("接口导出数据示例")
				    ->setDescription("填写类命，导出接口返回的数据");
        //所有字段规定为字符串形式
      	
      	$indexRow = 1;
        //第一行写入字段名
        $this->writedata($spreadsheet, array_keys(current($data)), $indexRow);

        //写入多行数据
        foreach ($data as $index => $item) {
        	$indexRow++;
        	foreach ($item as $key => $value) {
        		$this->writedata($spreadsheet, $item, $indexRow);
        	}
        }
        //生成的文件名
        $filePath = 'public/' . $link[0] . date('Ymd_his') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filePath);
        return file_exists($filePath) ? $filePath : false;
	}
		



}