<script language="JavaScript" type="text/javascript">

check_key_and_show_modal();

$('#send').bind('click', function () {

	var key = $("#key").text();
	var pass = $("#password").text();

	var e_n_sign = get_e_n_sign(key, pass, <?php print json_encode(utf8_encode(mcrypt_create_iv(mcrypt_get_iv_size('rijndael-128', MCRYPT_MODE_ECB), MCRYPT_RAND)))?>, '', 'modal_alert');

	// шлем подпись на сервер на проверку
	$.post( 'ajax/sign_up_in_pool.php', {
			'email': $('#email').val(),
			'n' : e_n_sign['modulus'],
			'e': e_n_sign['exp']
		}, function (data) {
			console.log(data);
			if (data.success)
				fc_navigate('mining_menu');
			else
				$('#alerts').html('<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>'+data.error+'</div>');
		}, 'JSON');
});

</script>

<h1 class="page-header"><?php echo $lng['mining']?></h1>

	<div class="panel panel-primary">
		<div class="panel-heading">
			<?php echo $lng['insufficient_privileges']?>
		</div>
		<div class="panel-body">
			<div class="form-horizontal">
				<fieldset>

					<!-- Form Name -->
					<legend><?php echo $lng['register_key_at_the_pool']?></legend>
					<div id="alerts"></div>
					<!-- Text input-->
					<div class="form-group">
						<label class="col-md-4 control-label" for="textinput">E-mail</label>
						<div class="col-md-4">
							<input id="email" name="email" placeholder="" class="form-control input-md" type="text">
							<span class="help-block"><?php echo $lng['enter_your_email']?></span>
						</div>
					</div>

					<!-- Button -->
					<div class="form-group">
						<label class="col-md-4 control-label" for="singlebutton"></label>
						<div class="col-md-4">
							<button id="send" name="send" class="btn btn-primary"><?php echo $lng['send']?></button>
						</div>
					</div>

				</fieldset>
			</div>

		</div>
	</div>


