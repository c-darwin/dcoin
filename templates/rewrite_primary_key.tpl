<!-- container -->
<div class="container">

<script>

$('#save').bind('click', function () {

	var key = $("#new_private_key").val();
	var pass = $("#new_password").val();
	if (pass) {
		var decrypt_PEM = mcrypt.Decrypt(atob(key.replace(/\n|\r/g,"")), <?php print json_encode(utf8_encode(mcrypt_create_iv(mcrypt_get_iv_size('rijndael-128', MCRYPT_MODE_ECB), MCRYPT_RAND)))?>, hex_md5(pass), 'rijndael-128', 'ecb');
	}
	else
		var decrypt_PEM = key;
	console.log('decrypt_PEM='+decrypt_PEM);
	if (decrypt_PEM.indexOf('RSA PRIVATE KEY')==-1) {
		alert('Incorrect key or password');
	}
	else {
		var rsa = new RSAKey();
		rsa.readPrivateKeyFromPEMString(decrypt_PEM);
		var a = rsa.readPrivateKeyFromPEMString(decrypt_PEM);
		var modulus = a[1];
		var exp = a[2];
		$.post( 'ajax/rewrite_primary_key.php', {
				'n' : modulus,
				'e': exp
		}, function (data) {
			if (data.error)
				$('#alert-result').html('<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>'+data.error+'</div>');
			else
				$('#alert-result').html('<div class="alert alert-success alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>'+data.success+'</div>');
		}, 'json' );
	}
} );

</script>

	<legend><h2><?php echo $lng['change_primary_key_title']?></h2></legend>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	
	<div id="show_key">
		<div id="alert-result"></div>
		<label><?php echo $lng['your_new_key']?></label>
		<textarea rows="5" id="new_private_key" style="width:600px;text-align: justify"></textarea><br>
		<label>Password (if exists)</label>
		<input type="password" id="new_password"><br>

		<button class="btn" type="button" id="save"><?php echo $lng['save']?></button>

	</div>

</div>
<!-- /container -->