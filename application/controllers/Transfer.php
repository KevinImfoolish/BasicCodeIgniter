<?php
defined('BASEPATH') OR exit('此文件不可被直接访问');
/**
 * Created by PhpStorm.
 * User: imfoolish
 * Date: 2018/5/29
 * Time: 17:21
 */
class Transfer extends CI_Controller
{
    /* 添加数据的log路径  */
    private $logfile;

    /* 接口报错的log路径（windows的cmd不支持utf8，所以输出到了文件）  */
    private $errorfile;

    /* 可能需要用到的链接  */
    private $links;

    /* 开始操作的页码  */
    private $page;

    /* log文件格式化  */
    private $record;

    /* error文件句柄  */
    private $filefd;

    /* 读接口的大小  */
    private $step;

    public function __construct()
    {
      
        parent::__construct();
        //设置脚本执行时间,每页条数等基本信息
        ini_set("max_execution_time", 0);
        $env = ['development' => 'https://api.517ybang.com/', 'production' => 'https://www.ybslux.com/'];
        $this->step = 50;
        $this->page = 1;
        $this->logfile   = 'application\logs\transfer.logs';
        $this->errorfile = 'application\logs\transfererror.logs';
        $this->links = [];
        $this->links['get']    = 'https://www.ybslux.com/interface/api/products.ashx';
        $this->links['send']   = $env[ENVIRONMENT] . 'item/create';
        $this->links['verify'] = $env[ENVIRONMENT] . 'item/count';
        $this->links['update'] = $env[ENVIRONMENT] . 'item/edit';
        $t_name = 'item';
        $this->load->model('Basic_model');
        $this->Basic_model->table_name = $t_name;
        $this->Basic_model->id_name    = $t_name . '_id';
    }

    /**
     * 程序入口
     * php index.php Transfer begin 'add|update'
     */
    public function begin($argv = 'update'){
        //数据处理方式，添加或者更新
        $handleway = $argv == 'add' ? 'add' : 'update';
        if($handleway == 'add'){
            //读取日志文件
            $this->getrecord();
        }
        //选择执行不同的方法
        $operationMap = ['add'=>'additem', 'update'=>'updateitem'];
        $appRun = true;
        while($appRun){
            $data = $this->curlget();
            array_walk($data, [$this, $operationMap[$handleway]]);
            $this->page++;
        }
    }

    /**
     * 打开日志文件
     * 读取已经处理的数据记录
     * 并移动到处理过的页码
     * 文本格式：
     * {signleid}
     * {newinsertid}-{datetime}-{page}-d
     * record格式：array
     * singleid => insertid-time-page-d
     */

    private function getrecord(){
        $this->filefd = fopen($this->logfile, 'a+');
        $record = file($this->logfile);
        $size   = count($record);
        //每次读两行
        for ($i = 0; $i < $size; $i += 2) {
            $this->record[trim($record[$i])] = trim($record[$i + 1]);
        }
        unset($record);
        if($this->record){
            preg_match('/-(\d+)-d/', end($this->record), $page);
            $this->page = intval($page[1]);
        }
    }
    //检测数据是否添加过
    private function itemexist($id){
        return isset($this->record[$id]);
    }
    //api创建项目
    private function additem($single){
        if ($this->itemexist(intval($single['id']))) {
            echo 'data has been added ' . $single['id'] . PHP_EOL;
            return true;
        }
        $data = $this->alterdata($single);
        $data['app_type'] = 'biz';
        //接口提交数据
        $json = $this->curlpost($data, $this->links['send']);
        if($json['status'] == 200){
            $key  = $single['id'];
            $line = $json['content']['id'] . '-' . date('Y-m-d H:i:s') . '-' . $this->page . '-d';
            $this->record[$key] = $line;
            fwrite($this->filefd, $key . PHP_EOL . $line . PHP_EOL);
            echo 'success handled '. $json['content']['id'] . PHP_EOL;
            return true;
        } else {
            file_put_contents($this->errorfile, $json['content']['error']['message']);
            exit('save-api goes wrong');
        }
    }

    //根据pcode更新数据
    private function updateitem($single){
        //pcode不为空
        if(empty($single['pcode'])){
            echo 'skip empty pcode item ' . PHP_EOL;
            return true;
        }
        // 检测code_biz的商品是否存在
        $validitem = $this->Basic_model->find('code_biz', $single['pcode']);
        if($validitem){
            $data = $this->alterdata($single);
            $r = $this->Basic_model->edit($validitem[$this->Basic_model->id_name], $data);
            echo 'update itemid:' . $validitem[$this->Basic_model->id_name] . '-' . $single['pcode'] . PHP_EOL;
            return true;
        }
    }

    //转换数据格式
    private function alterdata($single){
        $item = [];
        $item['code_biz']      = $single['pcode'];
        $item['name']          = mb_substr($single['pname'], 0, 40);
        $item['price']         = $single['price_new'];
        $item['unit_name']     = $single['unit'];
        $item['weight_net']    = $single['weight'];
        $item['weight_gross']  = $single['weight'];
        $item['weight_volume'] = $single['vol'];
        $item['stocks']        = intval($single['stock']) == 0 ? 1 : intval($single['stock']);
        $item['category_id']   = 1;
        $item['biz_id']        = 1;
        $item['brand_id']      = null;
        $item['sold_overall']  = $item['sold_monthly'] = intval($single['salcount']);
        $item['description']   = empty($single['detail']) ? '' : '<base href="http://www.ybslux.com">' . urldecode($single['detail']);
        $item['category_biz_id'] = null;
        $images = explode('#', $single['picture']);
        $item['url_image_main'] = isset($images[0]) ? array_shift($images) : '';
        $item['figure_image_urls'] = '';
        $item['tag_price']     = 0;
        if (!empty($single['price_old']) && round(floatval($single['price_new']),2) >= round(floatval($single['price_old']),2)) {
            $item['tag_price'] = 0;
        }
        if (!empty($single['updatetime'])) {
            $item['time_publish'] = strtotime($single['updatetime']);
            $item['time_edit']    = date('Y-m-d H:i:s', strtotime($single['updatetime']));
        }

        $item['slogan']        = mb_substr($single['shortdes'], 0, 30);
        if (trim($single['pname']) == trim($single['shortdes'])) {
            $item['slogan'] = null;
        }
        while (strlen($item['figure_image_urls'] . current($images)) < 255) {
            $item['figure_image_urls'] .= array_shift($images) . ',';
        }
        trim($item['figure_image_urls'], ',');
        return $item;
    }

    /**
     * 正式环境下验证签名
     * 
     */
    private function withsignature(&$params){
        if (ENVIRONMENT == 'production') {
            $params = [
                'app_type' => 'biz',
                'app_version' => '1.0.0',
                'device_platform' => 'server',
                'device_number' => '-----',
                'timestamp' => time(),
                'random' => rand(1000, 9999),
            ];
            $item['sign'] = $this->sign_generate($params);
            $item  += $params;
        }
        return true;
    }

    private function curlget()
    {
        echo 'operating the ' . $this->page . 'th page data' . PHP_EOL;
        $params = ['offset'=>($this->page - 1) * $this->step, 'limit'=>$this->step];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->links['get'] . '?' . http_build_query($params));
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        $tmpInfo = curl_exec($curl);
        curl_close($curl);
        if($tmpInfo){
            $data = json_decode($tmpInfo, true);
            if(is_array($data) && intval($data['state']) == 1 && !empty($data['data'])){
                return $data['data'];
            }
            exit('all data have done...');
        }
        exit('api of single-version may have problems');
    }

    private function curlpost($param, $url) 
    {   
        //在正式环境下需要加入验证、签名数据
        $this->withsignature($param);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
        $data = curl_exec($curl);
        curl_close($curl);
        if($data){
            $json = json_decode($data, true);
            if(is_array($json)){
                return $json;    
            }
        }
        exit('website may have problems');
    }


    /**
     * 生成签名
     */
    public function sign_generate($params)
    {   

        // 对参与签名的参数进行排序
        ksort($params);

        // 对随机字符串进行SHA1计算
        $params['random'] = SHA1( $params['random'] );

        // 拼接字符串
        $param_string = '';
        foreach ($params as $key => $value)
            $param_string .= '&'. $key.'='.$value;

        // 拼接密钥
        $param_string .= '&key='. API_TOKEN;

        // 计算字符串SHA1值并转为大写
        $sign = strtoupper( SHA1($param_string) );

        return $sign;
    }
   
}



