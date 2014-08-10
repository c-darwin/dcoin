<!-- container -->
<div class="container">

<script>

$('#save').bind('click', function () {

	$("#change_host").css("display", "none");
	$("#sign").css("display", "block");

	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']},{$tpl['project_id']}"; ?>,'+$("#comment").val());
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
});

$('#send_to_net').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'project_id' : <?php echo $tpl['project_id']?>,
			'comment' : $('#comment').val(),
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
		}, function (data) {
				fc_navigate ('my_cf_projects', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
		}
	);
});

</script>

	<legend><h2><?php echo $lng['add_cf_comment_title']?></h2></legend>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	
	<div id="change_host">

		<form>
			<fieldset>
				<input type="text" placeholder="comment" id="comment" value=""><br>
				<button type="submit" class="btn" id="save"><?php echo $lng['next']?></button>
			</fieldset>
		</form>

		<p><span class="label label-important"><?php echo $lng['limits']?></span> <?php echo $tpl['limits_text']?></p>

	</div>

	<?php require_once( 'signatures.tpl' );?>


</div>
<!-- /container -->