
<script>

	<?php
	if(!empty($tpl['avatar']) && $tpl['avatar']!='0') {
		echo '$("#img_avatar").css("background", "url(\''.$tpl['avatar'].'\') 50% 50%");';
		echo '$("#img_avatar").css("background-size", "100px Auto");';
	}
	else
		echo 'get_img(0);';
	?>

$('#save').bind('click', function () {

	<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#main_data").css("display", "none");	$("#sign").css("display", "block");' ?>

	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#name").val()+','+$("#avatar").val());
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
});

$('#send_to_net').bind('click', function () {

	if (/^[a-z0-9\-_\.\/\:]{1,47}(png|jpg)$/i.test($('#avatar').val())) {
		$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
				'name' : $('#name').val(),
				'avatar' : $('#avatar').val(),
				'signature1': $('#signature1').val(),
				'signature2': $('#signature2').val(),
				'signature3': $('#signature3').val()
			}, function (data) {
				fc_navigate ('change_avatar', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
		);
	}
	else {
		console.log('error');
		$("#errors").html('<div class="alert alert-danger alert-dismissable" id="errors">Incorrect image url</div>');
	}
});

check_key_and_show_modal();

</script>

<h1 class="page-header"><?php echo $lng['change_avatar_title']?></h1>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	
	<div id="main_data">

		<div id="errors"></div>

		<form class="form-horizontal">
			<fieldset>
				<div class="form-group">
					<label class="col-md-4 control-label" for="category_id"><?php echo $lng['name']?></label>
					<div class="col-md-4">
						<input maxlength="30"  class="form-control" type="text" id="name" value="<?php echo @$tpl['name']?>"
						<span class="help-block"><?php echo $lng['only_english_letters']?></span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-4 control-label" for="avatar"><?php echo $lng['avatar']?></label>
					<div class="col-md-4">
						<input maxlength="50" class="form-control" type="text" id="avatar" value="<?php echo $tpl['avatar']?>"
						<span class="help-block"><?php echo $lng['avatar_url_30']?></span>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-4 control-label" for="singlebutton"></label>
					<div class="col-md-4">
						<button type="button" class="btn btn-outline btn-primary" id="save"><?php echo $lng['send_to_net']?></button>
					</div>
				</div>
			</fieldset>
		</form>

		<p><span class="label label-important"><?php echo $lng['limits']?></span> <?php echo $tpl['limits_text']?></p>

		<?php echo $tpl['last_tx_formatted']?>

	</div>

	<?php require_once( 'signatures.tpl' );?>

<script src="js/unixtime.js"></script>