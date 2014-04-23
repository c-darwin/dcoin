<!-- container -->
<div class="container">

<script>

$('#save').bind('click', function () {

	$("#change_host").css("display", "none");
	$("#sign").css("display", "block");
	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#host").val());

});

$('#send_to_net').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'host' : $('#host').val(),
						'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
		}, function (data) {
				fc_navigate ('change_host', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
	);

} );

</script>

	<legend><h2><?php echo $lng['change_host_title']?></h2></legend>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	
	<div id="change_host">

		<form>
			<fieldset>
				<input type="text" placeholder="" id="host" value="<?php echo $tpl['host']?>"><br>
				(<?php echo $tpl['host_status']?>)<br>
				<button type="submit" class="btn" id="save"><?php echo $lng['next']?></button>
			</fieldset>
		</form>

		<p><span class="label label-important"><?php echo $lng['limits']?></span> <?php echo $tpl['limits_text']?></p>

	</div>

	<?php require_once( 'signatures.tpl' );?>


</div>
<!-- /container -->