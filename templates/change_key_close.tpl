<script>

$('#save').bind('click', function () {

	<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#main").css("display", "none");	$("#sign").css("display", "block");' ?>
	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>');
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>

});

$('#send_to_net').bind('click', function () {
	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
		}, function (data) {
			fc_navigate ('restoring_access', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
		}
	);
} );

</script>

	<h1 class="page-header"><?php echo $lng['change_key_close']?></h1>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	
	<div id="main">
		<button id="save" class="btn btn-outline btn-primary"><?php echo $lng['cancel_the_requests']?></button>
	</div>

	<?php require_once( 'signatures.tpl' );?>

