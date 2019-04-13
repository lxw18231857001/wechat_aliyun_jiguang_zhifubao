
<!DOCTYPE html>
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width">
<title>收货地址共享</title>
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
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
		//获取address
		getaddr();	

		$('.btn').click(function(){
			getaddr();
		})		

	})

	function editAddress()
	{
		WeixinJSBridge.invoke(
			'editAddress',
			{$editAddress},
			function(res){
				provice = res.proviceFirstStageName;
				city = res.addressCitySecondStageName;
				counties = res.addressCountiesThirdStageName;
				detail = res.addressDetailInfo;
				$('.atten').html(res.userName);
				$('.tel').html(res.telNumber);
				$('.addr').html(provice + city + counties + detail );
			}
			);
	}

	function getaddr(){
		if (typeof WeixinJSBridge == "undefined"){
			if( document.addEventListener ){
				document.addEventListener('WeixinJSBridgeReady', editAddress, false);
			}else if (document.attachEvent){
				document.attachEvent('WeixinJSBridgeReady', editAddress); 
				document.attachEvent('onWeixinJSBridgeReady', editAddress);
			}
		}else{
			editAddress();
		}
	};
</script>

</head>
<body>


	<div class="box">
		<div>
			<div class="atten"></div>
			<div class="tel"></div>
			<div class="addr"></div>
		</div>
		<div class="btn">
			重选
		</div>


	</div>




</body>
</html>