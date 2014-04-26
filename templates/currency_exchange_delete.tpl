<!-- container -->
<div class="container">

<script>

$('#send_data').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'order_id' : <?php echo $tpl['del_id']?>,
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
			}, function (data) {
				//alert(data);
				fc_navigate ('currency_exchange', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			} );
} );

</script>
  <legend><h2><?php echo $lng['del_order']?></h2></legend>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

    <div id="sign">
	
		<label><?php echo $lng['data']?></label>
		<textarea id="for-signature" style="width:500px;" rows="4"><?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']},{$tpl['del_id']}"; ?></textarea>
		<label><?php echo $lng['sign']?></label>
		<textarea id="signature1" style="width:500px;" rows="4"></textarea>
	    <label><?php echo $lng['sign']?> 2</label>
	    <textarea id="signature2" style="width:500px;" rows="4"></textarea>
	    <label><?php echo $lng['sign']?> 3</label>
	    <textarea id="signature3" style="width:500px;" rows="4"></textarea>
	    <br>
		<button class="btn" id="send_data"><?php echo $lng['send_to_net']?></button>

    </div>

</div>
<!-- /container -->