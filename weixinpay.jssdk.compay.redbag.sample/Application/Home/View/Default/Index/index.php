<!DOCTYPE html>
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width">
<title>微信支付，收货地共享，jssdk，微信红包，企业支付</title>
<meta name="Keywords" content="">
<meta name="Description" content="微信支付，jssdk，微信红包，企业支付">
<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
<meta name="format-detection" content="telephone=no"/>
<script type="text/javascript" src="/Public/Jq/jquery.js"></script>
<!-- jssdk -->
<script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script type="text/javascript">
	wx.config({
		debug: false,
		appId: '{$signPack.appId}',
		timestamp: {$signPack.timestamp},
		nonceStr: '{$signPack.nonceStr}',
		signature: '{$signPack.signature}',
		jsApiList: [
		'onMenuShareTimeline',
		'onMenuShareAppMessage',
		'getLocation',
		]
	});	


	wx.ready(function(){			
		ShareAppMessage();
		ShareTimeline();
		getLocation();

	})

		//分享给朋友
		function ShareAppMessage(){
			wx.onMenuShareAppMessage({
				title: '分享的标题',
				desc: '分享的描述部分', 
				link: 'http://{$_SERVER['HTTP_HOST']}', 
				imgUrl: 'http://{$_SERVER['HTTP_HOST']}/Public/Img/share.jpg',
				type: '', 
				dataUrl: ''
			});

		}

		//分享到朋友圈
		function ShareTimeline(){
			wx.onMenuShareTimeline({
				title: '分享的标题',
				link: 'http://{$_SERVER['HTTP_HOST']}', 
				imgUrl: 'http://{$_SERVER['HTTP_HOST']}/Public/Img/share.jpg',
			});

		}

		//获取地址
		function getLocation(){
			wx.getLocation({
				type: 'wgs84',
				success: function (res) {
					latitude = res.latitude;
					longitude = res.longitude;
					pos = latitude + ','+longitude;
					alert(pos);
				}
			});
		}	

	</script>

	<!-- jssdk end -->

	<style type="text/css">
		body{
			margin: 0;
			padding: 0;
		}
		.box{
			max-width: 640px;
			padding: 10px;
		}
	</style>

</head>
<body>


	<div class="box">
		<div>
			支付授权目录: http://{$_SERVER['HTTP_HOST']}/index/pay/id/
		</div>
		
		<div>
			<a href="{:U('index/pay')}">体验支付</a>|
			<a href="{:U('index/addr')}">收货地址共享</a>
		</div>
		<div>
			<a href="{:U('index/index','opt=redbag')}">公众号内红包</a>|
			<a href="{:U('index/index','opt=compay')}">企业支付</a>
		</div>

		<div>
			<volist name="paydata" id="v">
				<eq name="v.status" value="1">
					<p>{$v.out_trade_no}</p>
					<else />
					<p><a href="{:U('index/pay','id='.$v['out_trade_no'])}">{$v.out_trade_no}</a></p>
				</eq>
				<p>{$v.openid}</p>
				<p>{$v.total_fee}分</p>
				<p>
					<eq name="v.status" value="0">未支付</eq>
					<eq name="v.status" value="1">已支付</eq>
				</p>
				<p>{$v.addtime|date='Y-m-d H:i:s',###}</p>
				<hr>
			</volist>
		</div>

	</div>




</body>
</html>