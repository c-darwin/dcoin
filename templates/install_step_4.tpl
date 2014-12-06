<style>
	#page-wrapper{
		margin: 0px 10% 0px 10%;
		border: 1px solid #E7E7E7;
		min-height: 550px;
	}
	#wrapper{height: 100%;}
	#dc_content{
		vertical-align: middle;
	}
</style>
<div style="max-width: 600px; margin: auto; margin-top: 50px">


<script src="js/sha256.js"></script>

<script>

	$('#save_exists_key').bind('click', function () {

		var key = $("#install_private_key").val();
		var pass = $("#install_password").val();
		if (pass) {
			var md5_pass = hex_md5(pass);
			var clear_key = atob(key.replace(/\n|\r/g,""));
		}
		else {
			var md5_pass = '';
			clear_key = key;
		}

		console.log('pass='+pass);
		console.log('md5_pass='+md5_pass);
		console.log('key='+key);
		console.log('clear_key='+clear_key);
		if (pass)
			var decrypt_PEM = mcrypt.Decrypt(clear_key, <?php print json_encode(utf8_encode(mcrypt_create_iv(mcrypt_get_iv_size('rijndael-128', MCRYPT_MODE_ECB), MCRYPT_RAND)))?>, md5_pass, 'rijndael-128', 'ecb');
		else
			var decrypt_PEM = key;
		console.log('decrypt_PEM='+decrypt_PEM);

		var rsa = new RSAKey();
		var a = rsa.readPrivateKeyFromPEMString(decrypt_PEM);
		var modulus = a[1];
		var exp = a[2];
		var save_private_key = 1;

		//if (!$("#save_exists_private_key").prop("checked")) {
			key = '';
			save_private_key = 0;
		//}

		$.post( 'content.php', {
					'tpl_name' : 'install_step_5',
					'n' : modulus,
					'e': exp,
					'private_key' : key,
					'hash_pass' : md5_pass,
					'save_private_key' : save_private_key
			}, function (data) {
				$('#dc_content').html( data );
			}, 'html' );

	});

</script>
	<ul class="nav nav-tabs" style="margin-bottom: 20px">
		<li><a href="#install_step_0">Step 0</a></li>
		<li><a href="#install_step_1">Step 1</a></li>
		<li><a href="#install_step_2">Step 2</a></li>
		<li><a href="#install_step_2_1">Step 3</a></li>
		<li><a href="#install_step_3">Step 4</a></li>
		<li class="active"><a href="#install_step_4">Step 5</a></li>
	</ul>
	<div id="exists_key">
		<?php
		if (isset($tpl['error']))
			for ($i=0; $i<sizeof($tpl['error']); $i++)
				echo "<p style=\"color:#ff0000\">{$tpl['error'][$i]}</p>";
		?>

		<label><?php echo $lng['primary_key']?></label>
		<textarea class="form-control" id="install_private_key" style="width: 600px; height: 300px"></textarea><br>
		<label><?php echo $lng['key_password']?></label><br>
		<input type="password" id="install_password" class="form-control" style="width: 600px"><br>

		<!--<label class="checkbox">
			<input id="save_exists_private_key" type="checkbox"> <?php echo $lng['save_key']?>
		</label>-->

		<button id="save_exists_key" class="btn btn-outline btn-primary"><?php echo $lng['next']?></button>
	</div>

<div style="clear: both"></div>
</div>