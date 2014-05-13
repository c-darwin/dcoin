<!-- container -->
<div class="container">

	<legend><h2><?php echo $lng['upgrade_resend_title']?></h2></legend>

	<?php echo $lng['data']?>:<br>
	<textarea id="for-signature" style="width:500px; height:100px"><?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?></textarea><br>
	<?php
	for ($i=1; $i<=$count_sign; $i++) {
		echo "<label>{$lng['sign']} ".(($i>1)?$i:'')."</label><textarea id=\"signature{$i}\" style=\"width:500px;\" rows=\"4\"></textarea>";
	}
	?>
	<br>
	<script>
	$('#save_queue').bind('click', function () {

		$.post( 'ajax/save_queue.php', {
							'type' : '<?php echo $tpl['data']['type']?>',
							'user_id' : <?php echo $tpl['data']['user_id']?>,
							'time' : <?php echo $tpl['data']['time']?>,
										'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
							}, function (data) {
			fc_navigate ('upgrade', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
		});
	});
	</script>

	<button class="btn btn-success"  type="button" id="save_queue"><?php echo $lng['send_to_net']?></button>


</div>
<!-- /container -->