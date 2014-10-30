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

$('#get_access').bind('click', function () {

	<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#main").css("display", "none");	$("#sign").css("display", "block");' ?>
	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id_get_access']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#to_user_id").val() );
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net_get_access").trigger("click");':'' ?>

});

$('#send_to_net_get_access').bind('click', function () {
	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type_get_access']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'to_user_id' : $('#to_user_id').val(),
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

		Придумайте контрольный вопрос и ответ на него, который можете знать только Вы. Также, рекомендуется указать номер Вашего телефона.<br>
		<?php
		if ($tpl['change_key_status']==1) {
			echo '<button type="submit" class="btn" id="save">Запретить админу менять мой ключ</button>';
		}
		else {
			echo '<textarea id="secret"></textarea><br><button type="submit" class="btn" id="save">'.$lng['next'].'</button>';
		}
		?>

		<br><br><br>
		<button>Запросить доступ к аккаунту</button><br>
		<button>Отменить запросы</button>


	</div>

	<input type="hidden" id="change_key_status" value="<?php echo $tpl['change_key_status']?>">

	<?php require_once( 'signatures.tpl' );?>

