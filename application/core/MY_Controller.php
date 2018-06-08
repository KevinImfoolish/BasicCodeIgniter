<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');
	
	/**
	 * 根据导出、导入文件的需要设置的弗雷
	 *
	 * author imfoolish
	 */
	class MY_Controller extends CI_Controller
	{
		public $menuClass;
		public $env;
		public function __construct()
	    {
	        parent::__construct();
	        $this->env = ['development' => 'https://api.517ybang.com/', 'production' => 'https://www.ybslux.com/'];
	        $this->menuClass = ['menu_import'=>'', 'menu_export'=>''];
	    }

	    public function setmenu($where){
	    	$where = 'menu_' . strtolower($where);
	    	if (isset($this->menuClass[$where])) {
	    		$this->menuClass[$where] = 'active';
	    	}
	    	return true;
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

		public function getcolumn(&$alpha, &$prefix){
			if($alpha == 91){ //超出字母Z时
				$alpha = 65;
				$prefix = 'A';
			}
			return $prefix . chr($alpha++);

		}

		/**
	     * 正式环境下验证签名
	     * 
	     */
	    public function withsignature(&$params){
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
	}