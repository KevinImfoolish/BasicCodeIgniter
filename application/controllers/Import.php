<?php
defined('BASEPATH') OR exit('此文件不可被直接访问');
/**
 * Created by PhpStorm.
 * User: imfoolish
 * Date: 2018/5/29
 * Time: 17:21
 */


class Import extends MY_Controller
{

	private $withPrimarKey = true;

	public function __construct(){
		parent::__construct();
		$this->setmenu(__CLASS__);
		$this->load->model('Basic_model');
		
	}

	//网页入口
	public function index(){
		$r = [null, null];
		var_dump(implode('', $r));
		$this->output->delete_cache();
		$this->load->view('excel/header.html', $this->menuClass);
		$this->load->view('excel/import-index.html');

		//$r = $this->Basic_model->count();
		// var_dump($r);
	}

	public function upload()
	{
        set_time_limit(40);
		$json = ['status'=>1, 'msg'=>'上传失败,请检查文件和网络'];
		$classname = $this->input->post('classname');
		$tablename = $this->input->post('tablename');
		$tableMap  = ['item', 'user'];
		if (empty($classname) || !in_array($classname, $tableMap)){
			$json['msg'] = '类名不能为空';
			echo json_encode($json);
			exit;
		}
        //上传配置，限制大小1k
		$config['upload_path']    = 'upload/';
        $config['allowed_types']  = 'xlsx';
        $config['max_size']       = 1024;
        $config['file_name']      = date('Ymd_his') . '-' . $classname . '-' . rand(1000, 9999);
        $this->load->library('upload', $config);
        $this->upload->initialize($config);
        
        if (!$this->upload->do_upload('excelfile'))
        {
            $json['msg'] = $this->upload->display_errors('','');
        	echo json_encode($json);
        	exit;
        } else {
            //上传的文件路径地址
        	$file = $this->upload->data('full_path');
        	//模型属性
        	$this->Basic_model->table_name = $classname;
        	$this->Basic_model->id_name    = $classname . '_id';

        	//获取表字段
        	$tablerows = $this->Basic_model->tablefields();
        	if ($tablerows === false) {
        		$json['msg'] = '该表不存在';
            	echo json_encode($json);
            	exit;
        	}

        	$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
			$spreadsheet = $reader->load($file);
			$filerow = $this->getfilehead($spreadsheet);

			//读取第一行，默认为字段。确认字段存在
			if (!$this->validrow($tablerows, $filerow)) {
				$json['msg'] = '字段不合法';
            	echo json_encode($json);
            	exit;
			}
			$data = [];
            $index = 1;
			while ($row = $this->getaline($spreadsheet, count($filerow), $index)) {
				if(empty(implode('', $row))){
					break;
				} else {
					$data[] = $row;
				}
				$index++;
			}
			if (empty($data)) {
				$json['msg'] = '没有数据';
            	echo json_encode($json);
            	exit;
			}
			$res = [];
			$curllink  = $this->env[ENVIRONMENT] . $classname . '/create';
			$http = new Curl();

			foreach ($data as $key => $value) {
				$record = array_combine($filerow, $value);
				if(array_key_exists('creator_id', $record) && (is_null($record['creator_id']) || empty($record['creator_id']))) {
					$record['creator_id'] = 1;
				}
				if($classname == 'item') {
					$record['app_type'] = 'biz';
					$this->withsignature($record);
					$ret = $http->go($curllink, $record);
					//接口返回数据正常
					if(is_array($data) && $ret['status'] == 200 && isset($ret['content']['id'])){
						$res[] = $ret['content']['id'];
					} else {
						if(is_array($ret) && $ret['status'] != 200){
							$json['msg'] = $ret['content']['error']['message'];
						} else {
							$json['msg'] = '接口异常';
						}
						$json['res'] = implode(',', $res);
						echo json_encode($json);
						exit;
					}
				} else {
					$res[] = $this->Basic_model->create($record, TRUE);
				}
				
			}
			$json['status'] = 0;
			$json['msg'] = '全部数据处理完毕，';
			$json['res'] = implode(',', $res);
			echo json_encode($json);
        }
        exit;
	}

    private function getfilehead(&$sheet){
		$loop   = true;
		$values = [];
		$temp   = '';
		$next   = 1;
		while ($loop) {
			$temp = $sheet->getActiveSheet()->getCellByColumnAndRow($next++, 1)->getValue();
			if (is_null($temp)) {
				$loop = false;
			} else {
				$values[] = $temp;	
			}
		}
		return $values;
	}
	private function getaline(&$sheet, $length, $index){
		$values = [];
		for($s = 1; $s <= $length + 1; $s++) {
            $values[] = $sheet->getActiveSheet()->getCellByColumnAndRow($s, $index)->getValue();
		}
		return $values;
	}
	private function validrow($table, $file){
		foreach ($file as $key => $value) {
			if (!in_array($value, $table)) {
				return false;
			}
		}
		return true;
	}
}