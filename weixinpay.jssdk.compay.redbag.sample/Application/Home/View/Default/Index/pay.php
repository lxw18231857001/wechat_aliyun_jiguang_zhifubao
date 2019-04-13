
<!DOCTYPE html>
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width">
<title>微信支付</title>
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
<meta name="format-detection" content="telephone=no"/>
<script type="text/javascript" src="/Public/Jq/jquery.js"></script>

<style type="text/css">
	body{
		margin: 0;
		padding: 0;
	}
	.box{
		max-width: 640px;
		padding: 10px;
	}
	.btn{
		background: #0072E3;
		line-height: 3rem;
		font-size: 1rem;
		color: #fff;
		text-align: center;
	}
</style>

<script type="text/javascript">
	$(function(){
		$('.btn').click(function(){
			callpay();
		})

	})
	//JS api 支付
	function jsApiCall()
	{
		WeixinJSBridge.invoke(
			'getBrandWCPayRequest',
			{$jsApiParameters},
			function(res){
				WeixinJSBridge.log(res.err_msg);
				if(res.err_msg == 'get_brand_wcpay_request:cancel') {
					alert("您已取消了此次支付");
					return;
				} else if(res.err_msg == 'get_brand_wcpay_request:fail') {
					alert("支付失败");
					return;
				} else if(res.err_msg == 'get_brand_wcpay_request:ok') {
					//alert("支付成功！");
					location.href="{:U('index/index')}";

				} else {
					alert("未知错误"+res.error_msg);
					return;
				}
			}
			);
	}
	function callpay()
	{
		if (typeof WeixinJSBridge == "undefined"){
			if( document.addEventListener ){
				document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
			}else if (document.attachEvent){
				document.attachEvent('WeixinJSBridgeReady', jsApiCall); 
				document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
			}
		}else{
			jsApiCall();
		}
	}
</script>
</head>
<body>


	<div class="box">
		<div>
			<p>{$paydata.out_trade_no}</p>
			<p>{$paydata.openid}</p>
			<p>{$paydata.total_fee}分</p>
			<p>
				<eq name="paydata.status" value="0">未支付</eq>
				<eq name="paydata.status" value="1">已支付</eq>
			</p>
			<p>{$paydata.addtime|date='Y-m-d H:i:s',###}</p>
		</div>

		<div class="btn">
			支付
		</div>

	</div>




</body>
</html>