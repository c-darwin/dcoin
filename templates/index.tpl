<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Democratic Coin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

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

	
<script language="JavaScript" type="text/javascript">

	var poll_time=0;

	function fc_navigate (page, parameters) {

	$.post("content.php?page="+page, { tpl_name: page, parameters: parameters },
			function(data) {
				$('.fc_content').html( data );
			}, "html");
		
	}

	function load_menu() {
		$.get("ajax/menu.php", { },
				function(data) {
					$('.fc_menu').html( data );
				}, "html");
	}

	function doSign() {
	
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
		if (pass)
			text = atob(key.replace(/\n|\r/g,""));

		var forsignature = $("#for-signature").val();
		
		if (!forsignature || forsignature=='') {
			if (key) {
				// авторизация с ключем и паролем
				forsignature = $.getValues("ajax/sign_login.php");
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
		if (SIGN_LOGIN || PASS_LOGIN) {

			if (key) {
				// шлем подпись на сервер на проверку
				$.post( 'ajax/check_sign.php', {
							'sign': hSig,
							'n' : modulus,
							'e': exp
						}, function (data) {
							// залогинились
							//alert(data.result);
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

		}
		else {
			
			$("#signature1").val(hSig);
		
		}
	}

	function login_ok (result) {

		if (result=='1') {

			$.get("ajax/menu.php", {},
					function(data) {
						$('.fc_menu').html( data );
					}, "html");

			$.get("content.php", { tpl_name: "home" },
					function(data) {
						$('.fc_content').html( data );
					}, "html");
		}
	}

	function save_key () {
     
		var key = $("#modal_key").val();
		var password = $("#modal_password").val();
		
		$('#key').text( key );
		$('#password').text( password );
		
		$("#key-password").html('<a href="#myModal" role="button"  data-toggle="modal"><?php echo $lng['change_key_pass']?></a>');

     }

    function logout () {

        $.get("ajax/logout.php",
                function() {
                    fc_navigate ('login', '');
                });
    }

	var keyStr = "ABCDEFGHIJKLMNOP" +
						"QRSTUVWXYZabcdef" +
						"ghijklmnopqrstuv" +
						"wxyz0123456789+/" +
						"=";
						
	function decode64(input) {
	     var output = "";
	     var chr1, chr2, chr3 = "";
	     var enc1, enc2, enc3, enc4 = "";
	     var i = 0;
	 
	     // remove all characters that are not A-Z, a-z, 0-9, +, /, or =
	     var base64test = /[^A-Za-z0-9\+\/\=]/g;
	     if (base64test.exec(input)) {
	        alert("There were invalid base64 characters in the input text.\n" +
	              "Valid base64 characters are A-Z, a-z, 0-9, '+', '/',and '='\n" +
	              "Expect errors in decoding.");
	     }
	     input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
	 
	     do {
	        enc1 = keyStr.indexOf(input.charAt(i++));
	        enc2 = keyStr.indexOf(input.charAt(i++));
	        enc3 = keyStr.indexOf(input.charAt(i++));
	        enc4 = keyStr.indexOf(input.charAt(i++));
	 
	        chr1 = (enc1 << 2) | (enc2 >> 4);
	        chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
	        chr3 = ((enc3 & 3) << 6) | enc4;
	 
	        output = output + String.fromCharCode(chr1);
	 
	        if (enc3 != 64) {
	           output = output + String.fromCharCode(chr2);
	        }
	        if (enc4 != 64) {
	           output = output + String.fromCharCode(chr3);
	        }
	 
	        chr1 = chr2 = chr3 = "";
	        enc1 = enc2 = enc3 = enc4 = "";
	 
	     } while (i < input.length);
	 
	     return unescape(output);
	}
	  
	</script>
	
	
	
    <!-- Le styles -->
    <link href="css/bootstrap.css" rel="stylesheet">
    <style type="text/css">
      body {
        padding-top: 60px;
        padding-bottom: 40px;
      }
    </style>
    <link href="css/bootstrap-responsive.css" rel="stylesheet">

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="../assets/js/html5shiv.js"></script>
    <![endif]-->

    <!-- Fav and touch icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="http://twitter.github.com/bootstrap/assets/ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="http://twitter.github.com/bootstrap/assets/ico/apple-touch-icon-114-precomposed.png">
      <link rel="apple-touch-icon-precomposed" sizes="72x72" href="http://twitter.github.com/bootstrap/assets/ico/apple-touch-icon-72-precomposed.png">
                    <link rel="apple-touch-icon-precomposed" href="http://twitter.github.com/bootstrap/assets/ico/apple-touch-icon-57-precomposed.png">
                                   <link rel="shortcut icon" href="http://twitter.github.com/bootstrap/assets/ico/favicon.png">
  
  
  
<script src="js/jquery.min.js"></script>
<script src="js/jquery.Jcrop.js"></script>
<script type="text/javascript">
  jQuery(function($){

    // How easy is this??
    $('#target').Jcrop();

  });

</script>

	<link rel="stylesheet" href="css/jquery.Jcrop.css" type="text/css" />

<link rel="stylesheet" href="bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css"/>

	<link rel="stylesheet" media="all" type="text/css" href="css/jquery-ui.css" />
	<link rel="stylesheet" media="all" type="text/css" href="css/jquery-ui-timepicker-addon.css" />

  
  </head>

  <body>
  
	<div class="fc_menu">
    
    </div>
    
    <div class="fc_content">
    
    </div>
    
    
	<!-- bottom -->
    <div class="navbar navbar-fixed-bottom">
      <div class="navbar-inner">
        <div class="container">
          
          <!--<a class="brand" href="#">Dcoin</a>--><img src="img/logo.png" style="float: left; padding-top: 5px; margin-right: 20px;">
	        <div>

	          <div style="float: left; width: 300px; height: 30px">
			    <!-- Button to trigger modal -->
				<div style="float: left; padding-right:20px; " id="key-password">

					Key: <a href="#myModal" role="button"  data-toggle="modal">no</a><br>
					Password: <a href="#myModal" role="button"  data-toggle="modal">no</a>

				</div>

				<div style="display: none;">

					<div id="key">key</div>
					<div id="password">password</div>

				</div>

				  <div style="padding-top: 10px;padding-right: 15px; float: left"><a href="#" onclick="fc_navigate('home', 'lang=ru'); load_menu();">ru</a> | <a href="#" onclick="fc_navigate('home', 'lang=en'); load_menu();">en</a></div>

		          <div style="padding-top: 10px; float: left"><a href="mailto: dcoin@hotmail.com">dcoin@hotmail.com</a></div>

	          </div>
	          <div id="bar_alert" style="display: none"><a href="#" onclick="fc_navigate('cash_requests_in')"><img src="img/alert.png"></a></div>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>
    
	<!-- Modal -->
		<div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="myModalLabel">Login</h3>
			</div>
			<div class="modal-body">
				<form>
				<fieldset>
				
				<label>Key</label>
				<textarea rows="3" style="width:500px" id="modal_key"></textarea>
				
				<label>Password (if exists)</label>
				<input type="password"  style="width:500px" id="modal_password">
				</fieldset>
				</form>
			</div>
			<div class="modal-footer">
			<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
			<!--<button class="btn btn-primary" data-dismiss="modal" aria-hidden="true" onclick="save_key()">Save changes</button>-->
				<button type="button" class="btn btn-primary" data-toggle="button"  data-dismiss="modal"  onclick="save_key();doSign()">Log in</button>
			</div>
		</div>
    <!-- / Modal -->




     <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap-transition.js"></script>
    <script src="js/bootstrap-alert.js"></script>
    <script src="js/bootstrap-modal.js"></script>
    <script src="js/bootstrap-dropdown.js"></script>
    <script src="js/bootstrap-scrollspy.js"></script>
    <script src="js/bootstrap-tab.js"></script>
    <script src="js/bootstrap-tooltip.js"></script>
    <script src="js/bootstrap-popover.js"></script>
    <script src="js/bootstrap-button.js"></script>
    <script src="js/bootstrap-collapse.js"></script>
    <script src="js/bootstrap-carousel.js"></script>
    <script src="js/bootstrap-typeahead.js"></script>

  	<script>
	    load_menu();
		
		$.get("content.php", { },
		function(data) {
			$('.fc_content').html( data );
		}, "html");
	</script>

	<script src="https://maps.googleapis.com/maps/api/js?sensor=false" type="text/javascript"></script>
	<script src="js/markerclusterer.js"></script>

	<script type="text/javascript" src="js/jquery-ui.min.js"></script>

	<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
	<script type="text/javascript" src="js/jquery-ui-sliderAccess.js"></script>


  </body></html>