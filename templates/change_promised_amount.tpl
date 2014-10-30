
<script>

$('#send_to_net').bind('click', function () {
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
  <h1 class="page-header"><?php echo $lng['change_promised_amount']?></h1>

    <div id="main">

	    <div class="form-group">
		<label><?php echo $lng['data']?></label>
		<textarea id="for-signature"  class="form-control" style="" rows="4"><?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']},{$_REQUEST['parameters']['promised_amount_id']},{$_REQUEST['parameters']['amount']}"?></textarea>
	    </div>
	    <?php
		for ($i=1; $i<=$count_sign; $i++) {
			echo "<div class='form-group'><label>{$lng['sign']} ".(($i>1)?$i:'')."</label><textarea id=\"signature{$i}\" class='form-control' rows='4'></textarea></div>";
	    }
	    ?>
		<br>
	    <button class="btn btn-outline btn-primary" id="send_to_net"><?php echo $lng['send_to_net']?></button>

    </div>

	<input type="hidden" id="amount" value="<?php echo $_REQUEST['parameters']['amount']?>">
	<input type="hidden" id="promised_amount_id" value="<?php echo $_REQUEST['parameters']['promised_amount_id']?>">
	<script>
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	</script>

