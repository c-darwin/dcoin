<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="">
<meta name="author" content="">

<title>Democratic Coin</title></title>

<!-- Bootstrap Core CSS -->
<link href="css2/bootstrap.min.css" rel="stylesheet">

<!-- MetisMenu CSS -->
<link href="css2/plugins/metisMenu/metisMenu.min.css" rel="stylesheet">

<!-- Custom CSS -->
<link href="css2/sb-admin.css" rel="stylesheet">

<!-- Custom Fonts -->
<link href="css2/font-awesome.css" rel="stylesheet">

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


<script src="js/index.js"></script>

<script language="JavaScript" type="text/javascript">

	var poll_time=0;

	function doSign(type) {

		if(typeof(type)==='undefined') type='sign';

		console.log('type='+type);

		var SIGN_LOGIN = false;
		var PASS_LOGIN = false;

		jQuery.extend({
			getValues: function(url) {
				var result = null;
				$.ajax({
					url: url,
					type: 'get',
					dataType: 'json',
					async: false,
					success: function(data) {
						result = data;
					}
				});
			return result;
			}
			});

		var key = $("#key").text();
		var pass = $("#password").text();

		if (key.indexOf('RSA PRIVATE KEY')!=-1)
			pass = '';

		if (pass)
			text = atob(key.replace(/\n|\r/g,""));

		if (type=='sign') {
			var forsignature = $("#for-signature").val();
		}
		else {
			if (key) {
				// авторизация с ключем и паролем
				var forsignature = $.getValues("ajax/sign_login.php");
				SIGN_LOGIN = true;
			}
			else {
				PASS_LOGIN = true;
			}
		}

		console.log('forsignature='+forsignature);

		if (forsignature) {

			console.log('pass='+pass);

			if (pass)
				var decrypt_PEM = mcrypt.Decrypt(text, <?php print json_encode(utf8_encode(mcrypt_create_iv(mcrypt_get_iv_size('rijndael-128', MCRYPT_MODE_ECB), MCRYPT_RAND)))?>, hex_md5(pass), 'rijndael-128', 'ecb');
			else
				var decrypt_PEM = key;
			console.log('decrypt_PEM='+decrypt_PEM);
			if (decrypt_PEM.indexOf('RSA PRIVATE KEY')==-1) {
				$("#page-wrapper").spin(false);
				$("#modal_alert").html('<div id="alertModalPull" class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button><p>'+$('#incorrect_key_or_password').val()+'</p></div>');
			}
			else {
				var rsa = new RSAKey();
				rsa.readPrivateKeyFromPEMString(decrypt_PEM);
				var a = rsa.readPrivateKeyFromPEMString(decrypt_PEM);
				var modulus = a[1];
				var exp = a[2];

				//var hash = CryptoJS.SHA256(forsignature);
				//var hSig = rsa.signString(forsignature, 'sha256');

				console.log('forsignature='+forsignature);
				console.log('hex_md5(pass)='+hex_md5(pass));

				var hSig = rsa.signString(forsignature, 'sha1');

				console.log('hSig='+hSig);

				delete rsa;
			}

		}
		if (SIGN_LOGIN || PASS_LOGIN) {

			console.log('SIGN_LOGIN || PASS_LOGIN');

			$("#page-wrapper").spin();
			if (key) {
				// шлем подпись на сервер на проверку
				$.post( 'ajax/check_sign.php', {
							'sign': hSig,
							'n' : modulus,
							'e': exp
						}, function (data) {
							// залогинились
							console.log(data.result);
							login_ok( data.result );

						}, 'JSON'
				);
			}
			else {

				hash_pass = hex_sha256(hex_sha256(pass));
				// шлем хэш пароля на проверку и получаем приватный ключ
				$.post( 'ajax/check_pass.php', {
							'hash_pass': hash_pass
						}, function (data) {
							// залогинились
							login_ok( data.result );

							$("#modal_key").val(data.key);
							$("#key").text(data.key);
							//alert(data.key);

						}, 'JSON'
				);

			}

			$("#page-wrapper").spin(false);

		}
		else {

			$("#signature1").val(hSig);

		}
	}


	</script>

<!-- jQuery Version 1.11.0 -->
<script src="js2/jquery-1.11.0.js"></script>

<script src="js/jquery.Jcrop.js"></script>
<script type="text/javascript">
  jQuery(function($){

    // How easy is this??
    $('#target').Jcrop();

  });

</script>

	<link rel="stylesheet" href="css/jquery.Jcrop.css" type="text/css" />

	<!--<link rel="stylesheet" href="bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css"/>-->

	<link rel="stylesheet" media="all" type="text/css" href="css/jquery-ui.css" />
	<link rel="stylesheet" media="all" type="text/css" href="css/jquery-ui-timepicker-addon.css" />


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
	</div>



     <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->

	<!-- Bootstrap Core JavaScript -->
	<script src="js2/bootstrap.min.js"></script>

	<!-- Custom Theme JavaScript -->

  	<script>
	  load_menu();
	  $( "#dc_content" ).load( "content.php");

	</script>

	<!--<script src="https://maps.googleapis.com/maps/api/js?sensor=false" type="text/javascript"></script>-->
	<script src="js/markerclusterer.js"></script>

	<script type="text/javascript" src="js/jquery-ui.min.js"></script>

	<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
	<script type="text/javascript" src="js/jquery-ui-sliderAccess.js"></script>

	<script language="JavaScript" type="text/javascript" src="js2/spin.js"></script>


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

	//$('#page-wrapper').spin();


</script>

  </body></html>