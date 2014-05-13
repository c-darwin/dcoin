<!-- container -->
<div class="container">

<script>

$('#send_data').bind('click', function () {
	if ( $('#amount').val() > 0 ) {
		$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
				'promised_amount_id' :  $('#promised_amount_id').val(),
				'amount' :  $('#amount').val(),
				'signature1': $('#signature1').val(),
				'signature2': $('#signature2').val(),
				'signature3': $('#signature3').val()
			}, function(data){
			fc_navigate ('promised_amount_list', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
		});
	}
	else	{
		alert('null amount');
	}
} );

</script>
  <legend><h2><?php echo $lng['mining']?></h2></legend>

    <div id="sign_banknote">
	
		<label><?php echo $lng['data']?></label>
		<textarea id="for-signature" style="width:500px;" rows="4"><?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']},{$_REQUEST['parameters']['promised_amount_id']},{$_REQUEST['parameters']['amount']}"?></textarea>
	    <?php
	for ($i=1; $i<=$count_sign; $i++) {
		echo "<label>{$lng['sign']} ".(($i>1)?$i:'')."</label><textarea id=\"signature{$i}\" style=\"width:500px;\" rows=\"4\"></textarea>";
	    }
	    ?>
		<br>
	    <button class="btn" id="send_data"><?php echo $lng['send_to_net']?></button>

    </div>

	<input type="hidden" id="amount" value="<?php echo $_REQUEST['parameters']['amount']?>">
	<input type="hidden" id="promised_amount_id" value="<?php echo $_REQUEST['parameters']['promised_amount_id']?>">

</div>
<!-- /container -->