<?php
// +----------------------------------------------------------------------
// | Author: 等不到那一天 （群：366504956  交流thinkphp下微信开发）
// +----------------------------------------------------------------------

namespace Home\Controller;
use Think\Controller;
use Common\Libs\Weixin\ComPay;//红包与企业支付
use \Common\Libs\Weixin\WechatAuth;//JSSDK 需要用到accessToken
use \Common\Libs\Weixin\JSSDK;//JSSDK

class IndexController extends Controller {

	protected function _initialize(){
		//基础配置(必须)
		define('APPID','');
		define('MCHID', '');
		define('KEY', '');
		define('APPSECRET', '');

		//证书配置
		//默认为Application/Common/Common/Libs/Weixin/cert文件夹下
		//其他位置需要设置证书物理路径
		// define('SSLCERT_PATH', '');
		// define('SSLKEY_PATH',  '' );


	}
	public function index(){
		$openid = $this->getOpenid();

		//红包部份
		$opt = I('get.opt');
		if($opt == 'redbag')$this->RedBag($openid,1);
		if($opt == 'compay')$this->ComPay($openid,1);

		//JSSDK部份
		$accessToken = $this->getToken();
		$jssdk = new JSSDK(APPID, $accessToken );
		$this->assign('signPack',$jssdk->getSignPackage());

		$this->assign('paydata',S('paydata'));
		$this->display();
	}

	//jssdk用到accessToken
	private function getToken(){
		$token = S('token');
		if(!$token){
			$WechatAuth = new WechatAuth(APPID,APPSECRET);
			$AccessToken = $WechatAuth->getAccessToken();
			$token = $AccessToken['access_token'];
			S('token',$token,7000);
		}
		return $token;
	}


	//公众号内红包
	private function RedBag($openid,$value){
		$rb = new ComPay();
		$fee = $value*100;//金额
		$totalnum = 1;//数量
		$sendname = '发送者名字';
		$wishing = '祝福语';
		$actname = '活动名称';
		$body = '有钱，任性';

		$rb->setOpenid($openid);
		$rb->setAmount($fee);
		$rb->setMchid(MCHID);
		$rb->setApiKey(KEY);
		$rb->setMchAppid(APPID);
		$rb->setSendName($sendname);
		$rb->setTotalNum($totalnum);
		$rb->setWishing($wishing);
		$rb->setActName($actname);
		$rb->setDesc($body);
		
		$res = $rb->RedBag();
		$res ? $this->success('发送成功') : $this->error($rb->error());
		die();

	}

	//企业支付
	private function ComPay($openid,$value){
		$rb = new ComPay();
		$fee = $value*100;
		$body = '企业支付';
		$rb->setOpenid($openid);
		$rb->setAmount($fee);
		$rb->setMchid(MCHID);
		$rb->setApiKey(KEY);
		$rb->setMchAppid(APPID);
		$rb->setDesc($body);		
		$res =  $rb->ComPay();
		$res ? $this->success('发送成功') : $this->error($rb->error());
		die();

	}


	//获取地址
	public function addr(){		
		import('Common.Libs.Weixin.JSAPI');
		$tools = new \JsApiPay();
		$openid = $tools->GetOpenid();
		$this->editAddress = $tools->GetEditAddressParameters();		
		$this->assign('title','收货信息');
		$this->display();
	}

	//支付
	public function pay(){
		$id = I('get.id');
		//生成订单及查询订单
		if(!$id){
			$data = $this->dataMgr('new');
			$this->redirect('index/pay','id=' . $data['out_trade_no']);
			die();
		}else{
			$data = $this->dataMgr('get',$id);
			if($this->orderquery($data['out_trade_no']) ){
				$this->error('订单已支付');
				die();
			}

		}
		
		//获取openid
		$openId = $this->getOpenid();

		//支付相关
		import('Common.Libs.Weixin.JSAPI');
		$tools = new \JsApiPay();

		$Out_trade_no 	= $data['out_trade_no'];
		$Body			= '订单号：'.$Out_trade_no;
		$Total_fee 		= $data['total_fee'];;
		
		//设置支付
		$input = new \WxPayUnifiedOrder();
		$input->SetBody($Body);
		$input->SetOut_trade_no($Out_trade_no);
		$input->SetTotal_fee($Total_fee);
		$input->SetNotify_url('http://' . $_SERVER['HTTP_HOST'] . U('index/notify') );
		$input->SetTrade_type("JSAPI");
		$input->SetOpenid($openId);
		$order = \WxPayApi::unifiedOrder($input);
		$this->jsApiParameters = $tools->GetJsApiParameters($order);

		$this->assign('paydata',$data);
		$this->display();
	}


	//退款
	public function refund(){
		$id = I('get.id');
		$data = $this->dataMgr('get',$id);

		$out_trade_no = $data['out_trade_no'];
		$total_fee = $data['total_fee'];
		if(!$out_trade_no || !$total_fee){
			$this->error('订单不存在');
			die();
		}

		if(!$this->orderquery($out_trade_no) ){
			$this->error('订单未付款');
			die();
		}

		import('Common.Libs.Weixin.JSAPI');		
		$refund_fee = $total_fee;
		$input = new \WxPayRefund();
		$input->SetOut_trade_no($out_trade_no);
		$input->SetTotal_fee($total_fee);
		$input->SetRefund_fee($refund_fee);
		$input->SetOut_refund_no(\WxPayConfig::MCHID.date("YmdHis"));
		$input->SetOp_user_id(\WxPayConfig::MCHID);
		print_r(\WxPayApi::refund($input));
	}

	//回调
	public function notify(){
		$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
		$xmlObj = simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOCDATA);
		$xmlArr = json_decode(json_encode($xmlObj),true);		
		$result_code = $xmlArr['result_code'];
		// $dir = dirname(__FILE__).'/xml.txt';
		// file_put_contents($dir, var_export($xmlObj,true) );
		$out_trade_no =  $xmlArr['out_trade_no'];
		if($result_code == 'SUCCESS'){
			//查询订单支付情况，并标注支付
			$this->orderquery($out_trade_no);
		}
	}


	//用缓存暂代数据库
	private function dataMgr($opt,$out_trade_no){
		if($opt == 'new'){
			$data = array(
				'openid'		=> $this->getOpenid(),
				'out_trade_no'	=> substr((string)time().rand(100,999),3),
				'total_fee'		=> 1,
				'status'		=> 0,
				'addtime'		=> time()
				);
			$paydata = S('paydata');			
			$paydata[$data['out_trade_no']] = $data;
			S('paydata',$paydata);
			return $data;
		}
		if($opt == 'get'){
			$paydata = S('paydata');
			return $paydata[$out_trade_no];
		}

	}

	

     //订单查询
	private function orderquery($out_trade_no){
		import('Common.Libs.Weixin.JSAPI');
		$input = new \WxPayOrderQuery();
		$input->SetOut_trade_no($out_trade_no);
		$data = \WxPayApi::orderQuery($input);
		if($data['trade_state'] == 'SUCCESS' ){
			$paydata = S('paydata');
			$data = $paydata[$out_trade_no];
			$data['status'] = 1;
			$paydata[$out_trade_no] = $data;
			S('paydata',$paydata);
			return $data;
		}else{
			return false;
		}

	}


	//获取openid
	private function getOpenid(){
		$openid = session('openid');
		if(!$openid){
			import('Common.Libs.Weixin.JSAPI');
			$tools = new \JsApiPay();
			$openid = $tools->GetOpenid();
			session('openid',$openid); 
		}
		return $openid;
	}
}