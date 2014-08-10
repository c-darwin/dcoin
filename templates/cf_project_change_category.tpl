<!-- container -->
<div class="container">

<script>

$('#confirm_delete').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'project_id' : <?php echo $tpl['del_id']?>,
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
			}, function (data) {
				fc_navigate ('my_cf_projects', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			} );
} );

</script>
  <legend><h2><?php echo $lng['del_cf_project_title']?></h2></legend>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

	<button type="button" class="btn btn-danger" id="confirm_delete">Delete</button>

    <div id="sign" style="display: none">
	
		<label><?php echo $lng['data']?></label>
		<textarea id="for-signature" style="width:500px;" rows="4"><?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']},{$tpl['del_id']}"; ?></textarea>
	    <?php
	for ($i=1; $i<=$count_sign; $i++) {
		echo "<label>{$lng['sign']} ".(($i>1)?$i:'')."</label><textarea id=\"signature{$i}\" style=\"width:500px;\" rows=\"4\"></textarea>";
	    }
	    ?>
	    <br>
		<button class="btn" id="send_to_net"><?php echo $lng['send_to_net']?></button>

    </div>

	<input type="hidden" id="user_id" value="<?php echo $_SESSION['user_id']?>">
	<input type="hidden" id="time" value="<?php echo time()?>">
	<script>
		doSign();
	</script>
</div>
<!-- /container -->