<script>

var encrypted_message = '';
$('#save').bind('click', function () {

	<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#main").css("display", "none");	$("#sign").css("display", "block");' ?>

	if ($("#change_key_status").val()=='1') {
		encrypted_message = 30;
		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+encrypted_message );
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	}
	else {
		$.post( 'ajax/encrypt_comment.php', {

			'to_user_id' : <?php echo $tpl['admin_user_id']?>,
			'type' : 'restoring_access',
			'comment' : $("#secret").val()

		}, function (data) {

			encrypted_message = data;
			$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+encrypted_message );
			doSign();
			<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
		});
	}
});

$('#send_to_net').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'secret' : encrypted_message,
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
		}, function (data) {
			fc_navigate ('restoring_access', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
		}
	);

} );

</script>

	<h1 class="page-header"><?php echo $lng['restoring_access']?></h1>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	
	<div id="main">

		<?php
		if ($tpl['change_key_status']==1) {
			echo '<button type="submit" class="btn" id="save">'.$lng['forbid_admin_to_change_my_key'].'</button>';
		}
		else {
			echo $lng['restoring_secret_text'].'<br><textarea id="secret" class="form-control"></textarea><br><button type="submit"  id="save" class="btn btn-outline btn-primary">'.$lng['next'].'</button>';
		}
		?>

		<br><br><br>
		<button onclick="fc_navigate('change_key_request')" class="btn btn-primary"><?php echo $lng['make_a_request_for_a_access_to_the_account']?></button><br><br>
		<?php
		if ($tpl['requests']) {
			echo "<p>Request: {$tpl['requests']}</p>";
			echo '<a href="#change_key_close" class="btn btn-primary">'.$lng['cancel_the_requests'].'</button>';
		}
		?>


	</div>

	<input type="hidden" id="change_key_status" value="<?php echo $tpl['change_key_status']?>">

	<?php require_once( 'signatures.tpl' );?>

