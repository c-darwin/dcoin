<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="">
<meta name="author" content="">

<title>Democratic Coin</title>

<!-- Bootstrap Core CSS -->
<link href="css/bootstrap.min.css" rel="stylesheet">

<!-- MetisMenu CSS -->
<link href="css/plugins/metisMenu/metisMenu.min.css" rel="stylesheet">

<!-- Custom CSS -->
<link href="css/sb-admin.css" rel="stylesheet">

<!-- Custom Fonts -->
<link href="css/font-awesome.css" rel="stylesheet">

<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->

	<script language="JavaScript" type="text/javascript" src="js/md5.js"></script>
	<script language="JavaScript" type="text/javascript" src="js/jsbn.js"></script>
	<script language="JavaScript" type="text/javascript" src="js/jsbn2.js"></script>
	<script language="JavaScript" type="text/javascript" src="js/rsa.js"></script>
	<script language="JavaScript" type="text/javascript" src="js/rsa2.js"></script>
	<script language="JavaScript" type="text/javascript" src="js/sha1.js"></script>
	<script language="JavaScript" type="text/javascript" src="js/sha256.js"></script>
	<script language="JavaScript" type="text/javascript" src="js/base64.js"></script>
	<script language="JavaScript" type="text/javascript" src="js/rsapem-1.1.js"></script>
	<script language="JavaScript" type="text/javascript" src="js/rsasign-1.2.min.js"></script>
	<script language="JavaScript" type="text/javascript" src="js/asn1hex-1.1.min.js"></script>

	<script src="js/sha256.js"></script>

	<script type="text/javascript" src="js/rijndael.js"></script>
	<script type="text/javascript" src="js/mcrypt.js"></script>


<script src="js/index.js?r=<?php echo rand()?>"></script>

<script language="JavaScript" type="text/javascript">
function doSign(type){
	doSign_(type, <?php print json_encode(utf8_encode(mcrypt_create_iv(mcrypt_get_iv_size('rijndael-128', MCRYPT_MODE_ECB), MCRYPT_RAND)))?>);
}
</script>

	<script src="js/jquery-1.11.0.js"></script>
	<script src="js/jquery.qtip.min.js"></script>

	<link type="text/css" rel="stylesheet" href="css/jquery.qtip.min.css" />

	<link rel="stylesheet" media="all" type="text/css" href="css/jquery-ui.css" />
	<link rel="stylesheet" media="all" type="text/css" href="css/jquery-ui-timepicker-addon.css" />

	<link rel="icon" href="http://dcoin.me/favicon.ico" type="image/x-icon">
	<link rel="shortcut icon" href="http://dcoin.me/favicon.ico" type="image/x-icon">

<style>
	.DCava{
		display:inline-block;
		position:relative;
		overflow:hidden;
	}
	.DCava>img{
		vertical-align:top;
	}
	.DCava, .DCava:before{
		-moz-border-radius:100em;
		border-radius:100em;

	}
	.DCava>img{
		-webkit-border-radius:100em;
	}
	.DCava:before {
		content: '';
		display: block;
		position: absolute;
		left: 0;
		right: 0;
		width: 100%;
		height: 100%;
		margin: -10em;
		border: 10em solid #333;
		-moz-box-sizing: padding-box;
	}

	@media screen and (max-width: 768px) {
		body{background-color: #fff}
	}
</style>
</head>

<body>


<div id="wrapper">

<div id="dc_menu"></div>

<div id="page-wrapper">
	<div class="row">
		<div class="col-lg-12">
			<div id="dc_content"></div>


		</div>
		<!-- /.col-lg-12 -->
	</div>
	<!-- /.row -->
</div>
<!-- /#page-wrapper -->

</div>
<!-- /#wrapper -->

	<div style="display: none;">
		<div id="key">key</div>
		<div id="password">password</div>
		<img  id="image_key">
		<canvas  id="canvas_key"></canvas>
	</div>



     <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->

	<!-- Bootstrap Core JavaScript -->
	<script src="js/bootstrap.min.js"></script>


	<!--<script src="https://maps.googleapis.com/maps/api/js?sensor=false" type="text/javascript"></script>-->
	<script src="js/markerclusterer.js"></script>

	<script type="text/javascript" src="js/jquery-ui.min.js"></script>

	<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
	<script type="text/javascript" src="js/jquery-ui-sliderAccess.js"></script>

	<script language="JavaScript" type="text/javascript" src="js/spin.js"></script>


<script>
	(function ($) {
		$.fn.spin = function (opts, color) {
			var presets = {
				"tiny": {
					lines: 8,
					length: 2,
					width: 2,
					radius: 3
				},
				"small": {
					lines: 8,
					length: 4,
					width: 3,
					radius: 5
				},
				"large": {
					lines: 10,
					length: 8,
					width: 4,
					radius: 8
				}
			};
			if (Spinner) {
				return this.each(function () {
					var $this = $(this),
							data = $this.data();

					if (data.spinner) {
						data.spinner.stop();
						delete data.spinner;
					}
					if (opts !== false) {
						if (typeof opts === "string") {
							if (opts in presets) {
								opts = presets[opts];
							} else {
								opts = {};
							}
							if (color) {
								opts.color = color;
							}
						}
						data.spinner = new Spinner($.extend({
							color: $this.css('color')
						}, opts)).spin(this);
					}
				});
			} else {
				throw "Spinner class not available.";
			}
		};
	})(jQuery);

	//$('#wrapper').spin();


</script>

<style>
	.db_loader {
		font-size: 90px;
		text-indent: -9999em;
		overflow: hidden;
		width: 1em;
		height: 1em;
		border-radius: 50%;
		margin: 0.8em auto;
		position: relative;
		-webkit-animation: load6 1.7s infinite ease;
		animation: load6 1.7s infinite ease;
	}
	@-webkit-keyframes load6 {
		0% {
			-webkit-transform: rotate(0deg);
			transform: rotate(0deg);
			box-shadow: -0.11em -0.83em 0 -0.4em #cccccc, -0.11em -0.83em 0 -0.42em #cccccc, -0.11em -0.83em 0 -0.44em #cccccc, -0.11em -0.83em 0 -0.46em #cccccc, -0.11em -0.83em 0 -0.477em #cccccc;
		}
		5%,
		95% {
			box-shadow: -0.11em -0.83em 0 -0.4em #cccccc, -0.11em -0.83em 0 -0.42em #cccccc, -0.11em -0.83em 0 -0.44em #cccccc, -0.11em -0.83em 0 -0.46em #cccccc, -0.11em -0.83em 0 -0.477em #cccccc;
		}
		30% {
			box-shadow: -0.11em -0.83em 0 -0.4em #cccccc, -0.51em -0.66em 0 -0.42em #cccccc, -0.75em -0.36em 0 -0.44em #cccccc, -0.83em -0.03em 0 -0.46em #cccccc, -0.81em 0.21em 0 -0.477em #cccccc;
		}
		55% {
			box-shadow: -0.11em -0.83em 0 -0.4em #cccccc, -0.29em -0.78em 0 -0.42em #cccccc, -0.43em -0.72em 0 -0.44em #cccccc, -0.52em -0.65em 0 -0.46em #cccccc, -0.57em -0.61em 0 -0.477em #cccccc;
		}
		100% {
			-webkit-transform: rotate(360deg);
			transform: rotate(360deg);
			box-shadow: -0.11em -0.83em 0 -0.4em #cccccc, -0.11em -0.83em 0 -0.42em #cccccc, -0.11em -0.83em 0 -0.44em #cccccc, -0.11em -0.83em 0 -0.46em #cccccc, -0.11em -0.83em 0 -0.477em #cccccc;
		}
	}
	@keyframes load6 {
		0% {
			-webkit-transform: rotate(0deg);
			transform: rotate(0deg);
			box-shadow: -0.11em -0.83em 0 -0.4em #cccccc, -0.11em -0.83em 0 -0.42em #cccccc, -0.11em -0.83em 0 -0.44em #cccccc, -0.11em -0.83em 0 -0.46em #cccccc, -0.11em -0.83em 0 -0.477em #cccccc;
		}
		5%,
		95% {
			box-shadow: -0.11em -0.83em 0 -0.4em #cccccc, -0.11em -0.83em 0 -0.42em #cccccc, -0.11em -0.83em 0 -0.44em #cccccc, -0.11em -0.83em 0 -0.46em #cccccc, -0.11em -0.83em 0 -0.477em #cccccc;
		}
		30% {
			box-shadow: -0.11em -0.83em 0 -0.4em #cccccc, -0.51em -0.66em 0 -0.42em #cccccc, -0.75em -0.36em 0 -0.44em #cccccc, -0.83em -0.03em 0 -0.46em #cccccc, -0.81em 0.21em 0 -0.477em #cccccc;
		}
		55% {
			box-shadow: -0.11em -0.83em 0 -0.4em #cccccc, -0.29em -0.78em 0 -0.42em #cccccc, -0.43em -0.72em 0 -0.44em #cccccc, -0.52em -0.65em 0 -0.46em #cccccc, -0.57em -0.61em 0 -0.477em #cccccc;
		}
		100% {
			-webkit-transform: rotate(360deg);
			transform: rotate(360deg);
			box-shadow: -0.11em -0.83em 0 -0.4em #cccccc, -0.11em -0.83em 0 -0.42em #cccccc, -0.11em -0.83em 0 -0.44em #cccccc, -0.11em -0.83em 0 -0.46em #cccccc, -0.11em -0.83em 0 -0.477em #cccccc;
		}
	}

	.blockchain_loader:before,
	.blockchain_loader:after,
	.blockchain_loader {
		border-radius: 50%;
		width: 2.5em;
		height: 2.5em;
		-webkit-animation-fill-mode: both;
		animation-fill-mode: both;
		-webkit-animation: load7 1.8s infinite ease-in-out;
		animation: load7 1.8s infinite ease-in-out;
	}
	.blockchain_loader {
		margin: 8em auto;
		font-size: 10px;
		position: relative;
		text-indent: -9999em;
		-webkit-animation-delay: 0.16s;
		animation-delay: 0.16s;
	}
	.blockchain_loader:before {
		left: -3.5em;
	}
	.blockchain_loader:after {
		left: 3.5em;
		-webkit-animation-delay: 0.32s;
		animation-delay: 0.32s;
	}
	.blockchain_loader:before,
	.blockchain_loader:after {
		content: '';
		position: absolute;
		top: 0;
	}
	@-webkit-keyframes load7 {
		0%,
		80%,
		100% {
			box-shadow: 0 2.5em 0 -1.3em #ccc;
		}
		40% {
			box-shadow: 0 2.5em 0 0 #ccc;
		}
	}
	@keyframes load7 {
		0%,
		80%,
		100% {
			box-shadow: 0 2.5em 0 -1.3em #ccc;
		}
		40% {
			box-shadow: 0 2.5em 0 0 #ccc;
		}
	}

	.get_blocks_loader,
	.get_blocks_loader:before,
	.get_blocks_loader:after {
		border-radius: 50%;
	}
	.get_blocks_loader:before,
	.get_blocks_loader:after {
		position: absolute;
		content: '';
	}
	.get_blocks_loader:before {
		width: 5.2em;
		height: 10.2em;
		background: #fff;
		border-radius: 10.2em 0 0 10.2em;
		top: -0.1em;
		left: -0.1em;
		-webkit-transform-origin: 5.2em 5.1em;
		transform-origin: 5.2em 5.1em;
		-webkit-animation: load2 2s infinite ease 1.5s;
		animation: load2 2s infinite ease 1.5s;
	}
	.get_blocks_loader {
		font-size: 11px;
		text-indent: -99999em;
		margin: 5em auto;
		position: relative;
		width: 10em;
		height: 10em;
		box-shadow: inset 0 0 0 1em #ccc;
	}
	.get_blocks_loader:after {
		width: 5.2em;
		height: 10.2em;
		background: #fff;
		border-radius: 0 10.2em 10.2em 0;
		top: -0.1em;
		left: 5.1em;
		-webkit-transform-origin: 0px 5.1em;
		transform-origin: 0px 5.1em;
		-webkit-animation: load2 2s infinite ease;
		animation: load2 2s infinite ease;
	}
	@-webkit-keyframes load2 {
		0% {
			-webkit-transform: rotate(0deg);
			transform: rotate(0deg);
		}
		100% {
			-webkit-transform: rotate(360deg);
			transform: rotate(360deg);
		}
	}
	@keyframes load2 {
		0% {
			-webkit-transform: rotate(0deg);
			transform: rotate(0deg);
		}
		100% {
			-webkit-transform: rotate(360deg);
			transform: rotate(360deg);
		}
	}


	#loading_db{display: block}
</style>

<?php
if ( substr(PHP_OS, 0, 3) == "WIN" && !file_exists(ABSPATH . 'db_config.php') ) {
	echo '<style>
				#page-wrapper{
					margin: 0px 10% 0px 10%;
					border: 1px solid #E7E7E7;
					min-height: 550px;
				}
				#wrapper{height: 100%;}
				#dc_content{
					height: 550px;
					vertical-align: middle;
				}
			</style>';
	echo '  <div style="position:absolute; top:100px; left:50%; width:300px; margin-left:-150px; text-align:center" id="loading_db">
						<div class="db_loader" >Loading...</div>
						'.$lng['db_setup_wait'].'
						</div>';
}
?>
<script>

	$( document ).ready(function() {
		// Если нет dc_config и это винда, то выдается install_step_0, который может выдаваться пару минут, т.к. ждет пока запустится mysql
		<?php
		if ( substr(PHP_OS, 0, 3) == "WIN" && !file_exists(ABSPATH . 'db_config.php') ) {
			echo '$( "#dc_content" ).load( "content.php", function(response, status) {
					if ( status == "error" ) { window.location.href = "index.php"; console.log("load dc_content error/"+response+"/"+status) ;}
					$("#loading_db").css("display", "none");
					console.log("loading_db none /"+response+"/"+status) ;
					});';
		}
		else if (!empty($_REQUEST['key']) || !empty($_SESSION['private_key']) ) {
			$key = !empty($_REQUEST['key'])?$_REQUEST['key']:$_SESSION['private_key'];
			// пишем в сессию, что бы ctrl+F5 не сбрасывал ключ (для авто-входа с dcoin.me)
			$_SESSION['private_key'] = $key;
			$key = str_replace("\r", "\n", $key);
			$key = str_replace("\n\n", "\n", $key);
			$key = str_replace("\n", "\\\n", $key);
			$lang = $_REQUEST['lang']?'"parameters": {"lang":'.$_REQUEST['lang'].'}':'';
			echo "$('#wrapper').spin();\n";
			echo '$( "#dc_content" ).load( "content.php", { '.$lang.' }, function() {'."\n";
			echo '$("#modal_key").val("'.$key.'");'."\n";
			echo "save_key(); doSign('login');$('#wrapper').spin(false);\n";
			echo '});'."\n";
			echo 'load_menu();';
		}
		else {
			echo "$('#wrapper').spin();\n";
			echo '$( "#dc_content" ).load( "content.php", function() {$("#wrapper").spin(false);});';
			echo 'load_menu();';
		}
		?>
	});

</script>

<script type="text/javascript" >
	//window.location.href += "#mypara";
	//location.reload();

	var lastLinkEvent;
	function dcNavHash(e) {
		//console.log('dcNavHash start');
		if (lastLinkEvent!=location.hash) {
			console.log(lastLinkEvent);
			dcNav({'target': {'hash': location.hash}});
		}
		//console.log('dcNavHash end');
	}

	function dcNav(e){

		//console.log('dcNav start');

		//console.log(e.target);
		//var str = location.hash;
		if (typeof e.target.hash=='undefined') {
			window.addEventListener("hashchange", dcNavHash);
			return false;
		}

		lastLinkEvent = e.target.hash;

		var str = e.target.hash;
		var page_match = str.match(/#(\w+)/i);
		if (page_match && typeof page_match[1]!='undefined' && page_match[1]!='tab1' && page_match[1]!='tab2' && page_match[1]!='tab3'  ) {
			var page = page_match[1];
			var param_match = str.match(/\/\w+=\w+/gi);
			var param_obj = {};
			if (param_match) {
				for (var i = 0; i < param_match.length; i++) {
					var param = param_match[i].match(/(\w+)=(\w+)/i);
					param_obj[param[1]] = param[2];
				}
				//console.log(param_obj);
			}
			//console.log(page);


			if (page=='upgrade_1'|| page=='upgrade_2')
				user_photo_navigate(page);
			else if (page=='upgrade_6')
				map_navigate(page);
			else if (page=='upgrade_4')
				user_webcam_navigate(page);
			else
				fc_navigate(page, param_obj);

			if (param_obj && typeof param_obj['lang']!='undefined') {
				load_menu();
			}
		}

		//console.log('dcNav end');
	}

	window.addEventListener("click", dcNav);

	window.addEventListener("hashchange", dcNavHash);

	//window.onhashchange = function(e) {
	//}
</script>
</body></html>