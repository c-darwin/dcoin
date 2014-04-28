
<style>
	.table td {
		vertical-align: middle;
	}
	.table input, .table select  {
		margin-bottom: 0px;
	}
</style>

<!-- container -->
<div class="container">

<script>
$('#next').bind('click', function () {

	to_user_id = $("#to_user_id").val();

	if ( to_user_id ) {

		$.post( 'ajax/encrypt_comment.php', {

			'to_user_id' : $("#to_user_id").val(),
			'comment' :  $("#comment").val()

			}, function (data) {

				if ($("#comment").val()=='') {
					data = '30';
					$("#comment").val('0');
				}
				$("#comment_encrypted").val(data);

				$("#wallets").css("display", "none");
				$("#sign").css("display", "block");
				$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"?>,'+$('#to_user_id').val()+','+$('#amount').val()+','+$('#commission').val()+','+data+','+$('#currency_id').val());

			});
	}

} );

$('#send_to_net').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'to_user_id' : $('#to_user_id').val(),
			'currency_id' : $('#currency_id').val(),
			'amount' : $('#amount').val(),
			'commission' : $('#commission').val(),
			'comment' : $('#comment_encrypted').val(),
			'comment_text' : $('#comment').val(),
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
			}, function (data) {
		fc_navigate ('wallets_list', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
	} );
	
} );

String.prototype.hex2bin = function ()
{

	var i = 0, l = this.length - 1, bytes = []

	for (i; i < l; i += 2)
	{
		bytes.push(parseInt(this.substr(i, 2), 16))
	}

	return String.fromCharCode.apply(String, bytes)

}

function decrypt_comment_0 (id, type) {

	var key = $("#key").text();
	var pass = $("#password").text();
	var e_text = $("#encrypt_comment_"+id).val();
	if (pass) {
		text = atob(key.replace(/\n|\r/g,""));
		var decrypt_PEM = mcrypt.Decrypt(text, <?php print json_encode(utf8_encode(mcrypt_create_iv(mcrypt_get_iv_size('rijndael-128', MCRYPT_MODE_ECB), MCRYPT_RAND)))?>, hex_md5(pass), 'rijndael-128', 'ecb');
	}
	else {
		decrypt_PEM = key;
	}
	var rsa2 = new RSAKey();
	rsa2.readPrivateKeyFromPEMString(decrypt_PEM); // N,E,D,P,Q,DP,DQ,C

	decrypt_comment = rsa2.decrypt(e_text);

	// decrypt_comment может содержать зловред
	$.post( 'ajax/save_decrypt_comment.php', {
		'id' : id,
		'comment' : decrypt_comment,
		'type' : type
	}, function (data) {
		$("#comment_"+id).html(data);
	} );

}

$('#amount').keyup(function(e) {

	var amount = $("#amount").val();
	var amount_ = '';
	amount_ = parseFloat(amount.replace(",", "."));
	amount_ = amount_.toFixed(2);

	if (amount.indexOf(",")!=-1) {
		$("#amount").val(amount_);
	}
	amount = amount_;

	commission = amount * (0.1 / 100);
	commission = commission.toFixed(2);
	if (commission==0)
		commission = 0.01;
	amount = parseFloat(amount);
	commission = parseFloat(commission);
	//amount_and_commission = amount + commission;
///	amount_and_commission = amount_and_commission.toFixed(2);
//	total = amount_and_commission+'  <?php echo $lng['including_commission']?> '+commission;
//	$("#total").text(total);
	$("#commission").val(commission);
});



</script>
<script src="js/js.js"></script>

	<legend><h2><?php echo $lng['wallets_list_title']?></h2></legend>
	
	<div id="wallets">

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	   
	<p><?php echo $lng['your_account_number']?>: <strong><?php echo $tpl['user_id']?></strong></p>
	
	<p><?php echo $lng['send_dc']?>:</p>
	<table class="table" style="width: 300px">
	<tr><td><?php echo $lng['currency']?></td><td><select id="currency_id">
	<?php
	if (isset($tpl['wallets']))
		foreach ($tpl['wallets'] as $id => $data)
			print "<option value='{$data['currency_id']}'>{$tpl['currency_list'][$data['currency_id']]}({$data['amount']})</option>";
	?>
	</select></td></tr>
	<tr><td><?php echo $lng['to_account']?></td><td><input type="text" id="to_user_id"></td></tr>
	<tr><td><?php echo $lng['amount']?></td><td><input type="text" id="amount"></td></tr>
	<tr><td><?php echo $lng['commission']?></td><td><input type="text" id="commission"></td></tr>
	<tr><td><?php echo $lng['note']?></td><td><input type="text" id="comment"></td></tr>
	</table>
	<button id="next" class="btn btn-primary" type="button"><?php echo $lng['send']?></button>
	
	<br><br>
	<?php
	if (isset($tpl['wallets']))
	if ($tpl['wallets']) {
		echo '<h3>'.$lng['wallets'].'</h3><table class="table" style="width:500px">';
		foreach ($tpl['wallets'] as $id => $data) {
		print "<tr><td>{$tpl['currency_list'][$data['currency_id']]}</td><td>{$data['amount']}</td></tr>";
		}
		echo '</table>';
	}
	?>


	<?php

if (isset($tpl['my_dc_transactions']))
	if ($tpl['my_dc_transactions']) {
		echo '<h3>'.$lng['transactions'].'</h3><table class="table" style="width:500px">';
		echo '<tr><th></th><th>'.$lng['time'].'</th><th>'.$lng['currency'].'</th><th>'.$lng['type'].'</th><th>'.$lng['recipient'].'</th><th>'.$lng['amount'].'</th><th>'.$lng['commission'].'</th><th>'.$lng['note'].'</th><th>'.$lng['status'].'</th><th>Block_id</th></tr>';
		foreach ($tpl['my_dc_transactions'] as $key => $data) {
			print "<tr>";
			if ($data['to_user_id']==$tpl['user_id'])
				print "<td>+</td>";
			else
				print "<td>-</td>";
			if ($data['time'])
				print "<td>".date('d-m-Y H:i:s', $data['time'])."</td>";
			else
				print "<td></td>";
			print "<td>{$tpl['currency_list'][$data['currency_id']]}</td><td>{$names[$data['type']]} ({$data['type_id']})</td><td>{$data['to_user_id']}</td><td>{$data['amount']}</td><td>".(($data['commission']>0)?$data['commission']:"")."</td>";
			if ($data['comment_status']=='decrypted')
				print "<td>{$data['comment']}</td>";
			else
				print "<td><div id=\"comment_{$data['id']}\"><input type=\"hidden\" id=\"encrypt_comment_{$data['id']}\" value=\"{$data['comment']}\"><button class=\"btn\" onclick=\"decrypt_comment_0({$data['id']}, 'dc_transactions')\">{$lng['decrypt']}</button></div></td>";
			print "<td>{$data['status']}</td><td>{$data['block_id']}</td></tr>";
		}
		echo '</table>';
	}
	?>

	
	</div>

	<?php require_once( 'signatures.tpl' );?>
    
	<input type="hidden" id="comment_encrypted" value="">


</div>
<!-- /container -->