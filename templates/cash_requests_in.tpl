<!-- container -->
<div class="container">
<script>

$('#next').bind('click', function () {

	var error_message = '';
	code =  $('#code').val();
	hash_code =  $('#hash_code').val();

	sha256 = hex_sha256(hex_sha256(code));
	if ( sha256 != hash_code && hash_code!='' ) {
		error_message = '<?php echo $lng['invalid_code']?>';
	}
	if (error_message!='') {
		$("#message").html( '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>'+error_message+'</div>' );
	}
	else {
		$("#wallets").css("display", "none");
		$("#sign").css("display", "block");
		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"?>,'+$('#cash_request_id').val()+','+$('#code').val());
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	}

} );

$('#send_to_net').bind('click', function () {

	code =  $('#code').val();

	$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
				'cash_request_id' : $('#cash_request_id').val(),
				'code' : code,
				'signature1': $('#signature1').val(),
				'signature2': $('#signature2').val(),
				'signature3': $('#signature3').val()
				}, function () {
					fc_navigate ('cash_requests_in', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
				});
} );

</script>
<script src="js/js.js"></script>

	<legend><h2><?php echo $lng['cash_request_in_title']?></h2></legend>
	<div id="message"></div>

	<?php
	if (isset($tpl['data']['id'])) {
	?>
	<div id="wallets">
	
		<table class="table" style="width:500px">
		<?php
		print "<tr><td><strong>{$lng['currency']}</strong></td><td>{$tpl['currency_list'][$tpl['data']['currency_id']]}</td></tr>";
		print "<tr><td><strong>{$lng['amount']}</strong></td><td>{$tpl['data']['amount']}</td></tr>";
		print "<tr><td><strong>{$lng['contact']}</strong></td>";
		if ($tpl['data']['comment_status']=='decrypted')
			print "<td>{$tpl['data']['comment']}</td>";
		else
			print "<td><div id=\"comment_{$tpl['data']['id']}\"><input type=\"hidden\" id=\"encrypt_comment_{$tpl['data']['id']}\" value=\"{$tpl['data']['comment']}\"><button class=\"btn\" onclick=\"decrypt_comment({$tpl['data']['id']}, 'cash_requests')\">{$lng['decrypt']}</button></div></td>";
		print "</tr>";
		?>
		</table>


	<?php echo $lng['enter_code']?><br>
		<input type="text" id="code"><br>
		<button id="next" class="btn btn-primary" type="button"><?php echo $lng['next']?></button>

	</div>
	<?php
	}
	?>

<?php require_once( 'signatures.tpl' );?>

<div id="list">
	<?php
	if (isset($tpl['my_cash_requests'])) {
		echo '<br><br><h3>'.$lng['list_of_requests'].'</h3>';
	echo '<table class="table" style="width:500px">';
		echo '<tr><th>'.$lng['time'].'</th><th>'.$lng['currency'].'</th><th>'.$lng['recipient'].'</th><th>'.$lng['amount'].'</th><th>'. $lng['comment'].'</th><th>'.$lng['status'].'</th></tr>';
		foreach ($tpl['my_cash_requests'] as $key => $data) {
		print "<tr>";
			if ($data['time'])
			print "<td>".date('d-m-Y H:i:s', $data['time'])."</td>";
			else
			print "<td></td>";
			print "<td>{$tpl['currency_list'][$data['currency_id']]}</td><td>{$data['to_user_id']}</td><td>{$data['amount']}</td>";
			if ($data['comment_status']=='decrypted')
				print "<td>{$data['comment']}</td>";
			else
				print "<td><div id=\"comment_{$data['id']}\"><input type=\"hidden\" id=\"encrypt_comment_{$data['id']}\" value=\"{$data['comment']}\"><button class=\"btn\" onclick=\"decrypt_comment({$data['id']}, 'cash_requests')\">{$lng['decrypt']}</button></div></td>";
			print "<td>".$cash_requests_status[$data['status']]."</td></tr>";
		}
		echo '</table>';
	}
	?>
</div>

	<input type="hidden" id="hash_code" value="<?php echo @$tpl['data']['hash_code']?>">
	<input type="hidden" id="cash_request_id" value="<?php echo @$tpl['data']['cash_request_id']?>">



</div>
<!-- /container -->