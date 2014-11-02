
<style>
	.table td {
		vertical-align: middle;
	}
	.table input, .table select  {
		margin-bottom: 0px;
	}
</style>

<link href="css/cf.css?2" rel="stylesheet">
<script>

var type = '';
var to_id = '';

var currency_list = new Array()
<?php
foreach ($tpl['wallets'] as $id => $data)
	echo "currency_list[{$data['currency_id']}] = '{$tpl['currency_list'][$data['currency_id']]}';\n";

?>

$('#goto_confirm').bind('click', function () {

	check_key_and_show_modal();

	$("#confirm_currency").text(currency_list[$("#currency_id").val()]);
	$("#confirm_to_user_id").text($("#to_user_id").val());
	$("#confirm_amount").text($("#amount").val());
	$("#confirm_commission").text($("#commission").val());
	$("#confirm_comment").text($("#comment").val());
	$("#wallets_confirm").css("display", "block");
	$("#wallets").css("display", "none");

});

$('#next, #cf_next').bind('click', function () {

	var to_user_id = $("#to_user_id").val();
	var project_id = $("#project_id").val();
	if (to_user_id) {
		type = 'user';
		to_id = to_user_id;
		var tx_type_id = <?php echo $tpl['data']['user_type_id']?>;
		var cf = '';
		var currency_id = ','+$("#currency_id").val();
	}
	else if (project_id) {
		type = 'project';
		to_id = project_id;
		var tx_type_id = <?php echo $tpl['data']['project_type_id']?>;
		var cf = 'cf_';
		var currency_id = '';
	}
	console.log(cf);
	console.log(to_user_id);
	console.log(project_id);

	if (to_id) {

		$.post( 'ajax/encrypt_comment.php', {

			'to_id' : to_id,
			'type' : type,
			'comment' :  $("#"+cf+"comment").val()

			}, function (data) {

				if ($("#"+cf+"comment").val()=='') {
					data = '30';
					$("#"+cf+"comment").val('0');
				}
				$("#comment_encrypted").val(data);

				<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#sign").css("display", "block"); $("#wallets").css("display", "none");' ?>

				$("#for-signature").val( tx_type_id+',<?php echo "{$tpl['data']['time']},{$tpl['data']['user_id']}"?>,'+to_id+','+$('#'+cf+'amount').val()+','+$('#'+cf+'commission').val()+','+data+currency_id);
				doSign();
				<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>

			});
	}

} );

	function fill_cf_card(data)
	{
		console.log(data);
		$('#blurb_img').attr('src', data.blurb_img);
		$('#location').text(data.country+', '+data.city);
		$('#project_id_info').text(data.id);
		$('#available_dc').text(data.wallet_amount+' d'+data.currency);
		$('#cf_pledged').text(data.funding_amount+' d'+data.currency);
		$('#cf_days').text(data.days);
		$('#cf_progress').width(data.pct+'%');
		$('#cf_pct').text(data.pct+'%');
	}

	$('#cf_next_0').bind('click', function () {

		$("#cf_prject_id").css("display", "none");
		$("#cf_prject_card").css("display", "block");

		$.post( 'ajax/wallets_list_cf_project.php', {
			'project_id' : $('#project_id').val()
		}, function (data) {
			fill_cf_card(data);
		}, 'JSON');

	});

$('#send_to_net').bind('click', function () {

	if (type=='user') {
		var tx_type = '<?php echo $tpl['data']['user_type']?>';
		var amount = $('#amount').val();
		var commission = $('#commission').val();
		var comment = $('#comment').val();
	}
	else if (type=='project') {
		var tx_type = '<?php echo $tpl['data']['project_type']?>';
		var amount = $('#cf_amount').val();
		var commission = $('#cf_commission').val();
		var comment = $('#cf_comment').val();
	}


	$.post( 'ajax/save_queue.php', {
			'type' : tx_type,
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'to_id' : to_id,
			'currency_id' : $('#currency_id').val(),
			'amount' : amount,
			'commission' : commission,
			'comment' : $('#comment_encrypted').val(),
			'comment_text' : comment,
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
	if (key.indexOf('RSA PRIVATE KEY')!=-1)
		pass = '';
	var e_text = $("#encrypt_comment_"+id).val();
	<?php
	if ($tpl['miner_id'] > 0) // если майнер, то коммент зашифрован нодовским ключем и тут его не расшифровать
		echo "var comment = e_text;\n";
	else {
	?>
	if (pass) {
		text = atob(key.replace(/\n|\r/g,""));
		var decrypt_PEM = mcrypt.Decrypt(text, <?php print json_encode(utf8_encode(mcrypt_create_iv(mcrypt_get_iv_size('rijndael-128', MCRYPT_MODE_ECB), MCRYPT_RAND)))?>, hex_md5(pass), 'rijndael-128', 'ecb');
	}
	else {
		decrypt_PEM = key;
	}
	var rsa2 = new RSAKey();
	rsa2.readPrivateKeyFromPEMString(decrypt_PEM); // N,E,D,P,Q,DP,DQ,C

	var comment = rsa2.decrypt(e_text);
	<?php
	}
	?>
	// decrypt_comment может содержать зловред
	$.post( 'ajax/save_decrypt_comment.php', {
		'id' : id,
		'comment' : comment,
		'type' : type
	}, function (data) {
		$("#comment_"+id).html(data);
	} );

}

$('#amount, #cf_amount').keyup(function(e) {

	if (this.id=='cf_amount')
		var add='cf_';
	else
		var add='';

	var amount = $("#"+add+"amount").val();
	var amount_ = '';
	amount_ = parseFloat(amount.replace(",", "."));
	amount_ = amount_.toFixed(2);

	if (amount.indexOf(",")!=-1) {
		$("#"+add+"amount").val(amount_);
	}
	amount = amount_;

	var commission = amount * (0.1 / 100);
	commission = commission.toFixed(2);
	if (commission==0)
		commission = 0.01;
	commission = parseFloat(commission);
	//commission = commission + 0.01; // чтобы точно прошло
	amount = parseFloat(amount);
	commission = parseFloat(commission);
	//amount_and_commission = amount + commission;
///	amount_and_commission = amount_and_commission.toFixed(2);
//	total = amount_and_commission+'  <?php echo $lng['including_commission']?> '+commission;
//	$("#total").text(total);
	$("#"+add+"commission").val(commission);
});


</script>
<script src="js/js.js"></script>

<h1 class="page-header"><?php echo $lng['wallets_list_title']?></h1>

	<div id="wallets">

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

		<div class="panel-body">
			<!-- Nav tabs -->
			<ul class="nav nav-tabs" id="myTab">
				<li class="active"><a href="#send_to_wallet" data-toggle="tab"><?php echo $lng['send_to_wallet']?></a>
				</li>
				<!--<li class=""><a href="#send_to_cf" data-toggle="tab"><?php echo $lng['send_to_cf_project']?></a>
				</li>-->
				<li class=""><a href="#" onclick="fc_navigate('currency_exchange')"><?php echo $lng['currency_exchange1']?></a>
				</li>
				<li class=""><a href="#" onclick="fc_navigate('credits')"><?php echo $lng['credits']?></a>
				</li>
			</ul>

			<!-- Tab panes -->
			<div class="tab-content">
				<div class="tab-pane fade active in" id="send_to_wallet">
					<br>
					<div style="float: left">
					<table class="table" style="width: 400px">
						<tr><td><?php echo $lng['currency']?></td><td>
						<select id="currency_id" class="form-control">
						<?php
							if (isset($tpl['wallets'])) {
								foreach ($tpl['wallets'] as $id => $data)
							echo "<option value='{$data['currency_id']}'>".make_currency_name($data['currency_id'])."{$tpl['currency_list'][$data['currency_id']]}({$data['amount']})</option>";
							}
							else
								echo "<option>{$lng['you_do_not_have_the_coins']}</option>";
						?>
						</select></td></tr>
						<tr><td><?php echo $lng['to_account']?></td><td><input class="form-control" type="text" id="to_user_id"></td></tr>
						<tr><td><?php echo $lng['amount']?></td><td><input class="form-control" type="text" id="amount"></td></tr>
						<tr><td><?php echo $lng['commission']?></td><td><input class="form-control" type="text" id="commission"></td></tr>
						<tr><td><?php echo $lng['note']?></td><td><input class="form-control" type="text" id="comment"></td></tr>
					</table>
					<button id="goto_confirm" class="btn btn-outline btn-primary" type="button" style="margin-left: 7px"><?php echo $lng['send']?></button>

					<br><br>
					<?php
					if (isset($tpl['wallets'])) {
						echo '<h3>'.$lng['balances'].'</h3><table class="table" style="width:400px">';
						//echo '<tr><th>'.$lng['currency'].'</th><th>'.$lng['amount'].'</th><th>'.$lng['pct_year'].'</th></tr>';
						echo '<tr><th>'.$lng['currency'].'</th><th>'.$lng['amount'].'</th></tr>';
						foreach ($tpl['wallets'] as $id => $data) {
							echo "<tr>";

							if ($data['currency_id']>=1000)
								echo "<td><a href=\"#\" onclick=\"fc_navigate('cf_page_preview', {'only_cf_currency_name':'{$tpl['currency_list'][$data['currency_id']]}'})\">{$tpl['currency_list'][$data['currency_id']]}</a></td>";
							else
								echo "<td>d{$tpl['currency_list'][$data['currency_id']]}</td>";

							echo "<td>{$data['amount']}</td>";
							//echo "<td>{$data['pct']}</td>";
							echo "</tr>";
						}
						echo '</table>';
					}
					?>

					<br><br>
					</div>
					<div style="overflow: auto;">
						<div class="panel panel-primary" style="margin-left: 40px; max-width: 400px">
							<div class="panel-heading">
								<?php echo $lng['your_account_number']?>
							</div>
							<div class="panel-body">
								<p style="font-size: 35px; font-weight: bold; margin-bottom: 0px; margin-top: 0px;line-height: 1.1;"><?php echo $tpl['user_id']?></p>
							</div>
						</div>
						<div class="panel panel-success" style="margin-left: 40px; max-width: 400px">
							<div class="panel-heading">
								<?php echo $lng['where_get_dc']?>
							</div>
							<div class="panel-body">
								<p><?php echo $lng['where_get_dc_text']?></p>
							</div>
						</div>
					</div>
					<div class="clearfix"></div>

					<?php

					if (isset($tpl['my_dc_transactions']))
					if ($tpl['my_dc_transactions']) {
						echo '<h3>'.$lng['transactions'].'</h3><table class="table" style="width:500px;">';
						echo '<tr><th></th><th>'.$lng['time'].'</th><th>'.$lng['currency'].'</th><th>'.$lng['type'].'</th><th>'.$lng['recipient'].'</th><th>'.$lng['amount'].'</th><th>'.$lng['commission'].'</th><th>'.$lng['note'].'</th><th>'.$lng['status'].'</th><th>Block_id</th><th>Confirmations</th></tr>';
						foreach ($tpl['my_dc_transactions'] as $key => $data) {
						print "<tr>";
							if ($data['to_user_id']==$tpl['user_id'])
								echo "<td>+</td>";
							else
								echo "<td>-</td>";
							if ($data['time'])
								echo "<td>".date('d-m-Y H:i:s', $data['time'])."</td>";
							else
								echo "<td></td>";
								echo "<td>".make_currency_name($data['currency_id'])."{$tpl['currency_list'][$data['currency_id']]}</td>";

							echo "<td>{$names[$data['type']]} ({$data['type_id']})";
							if ($data['type']=='cf_project')
								echo "<button class=\"btn\" onclick=\"fc_navigate('del_cf_funding', {'del_id':'{$data['to_user_id']}'})\">Cancel</button>";
								echo "</td><td>{$data['to_user_id']}</td><td>{$data['amount']}</td><td>".(($data['commission']>0)?$data['commission']:"")."</td>";
							if ($data['comment_status']=='decrypted')
								echo "<td><div style=\"width: 100px; overflow: auto\">{$data['comment']}</div></td>";
							else
								echo "<td><div id=\"comment_{$data['id']}\"><input type=\"hidden\" id=\"encrypt_comment_{$data['id']}\" value=\"{$data['comment']}\"><button class=\"btn\" onclick=\"decrypt_comment_0({$data['id']}, 'dc_transactions')\">{$lng['decrypt']}</button></div></td>";
					echo "<td>{$data['status']}</td><td><a href=\"#\" onclick=\"fc_navigate('block_explorer', {'block_id':{$data['block_id']}})\">{$data['block_id']}</a></td><td>".($tpl['data']['confirmed_block_id'] - $data['block_id'])."</td></tr>";
				}
				echo '</table>';
				echo "<p>{$lng['error_in_tx']}</p>";
				}
				?>





				</div>
				<div class="tab-pane fade" id="send_to_cf">
						<br>
						<div id="cf_prject_id">
							<div class="form-group">
								<label><?php echo $lng['project_id']?></label>
								<input class="form-control" style="width: 300px" id="project_id">
							</div>
							<div class="form-group">
								<button id="cf_next_0" class="btn btn-outline btn-primary" type="button"><?php echo $lng['next']?></button>
							</div>
						</div>

						<div style="display: none; overflow: auto" id="cf_prject_card">

							<div class="well project-card" style="float:left; margin-right:20px">
								<img id="blurb_img" style="width:200px; height:310px">
								<div>
									<div class="card-location" style="margin-top:10px;font-size: 13px;"><a href="#"><i class="fa  fa-map-marker  fa-fw"></i> <span id="location"><?php echo "{$data['country']},{$data['city']}"?></span></a></div>
									<div class="progress" style="height:5px; margin-top:10px; margin-bottom:10px"><div class="progress-bar progress-bar-success" style="width: 0%;" id="cf_progress"></div></div>
									<div class="card-bottom">
										<div style="float:left; overflow:auto; padding-right:10px"><h5 id="cf_pct">0%</h5>funded</div>
										<div style="float:left; overflow:auto; padding-right:10px"><h5 id="cf_pledged">0 </h5>pledged</div>
										<div style="float:left; overflow:auto;"><h5 id="cf_days">0</h5>days to go</div>
									</div>
								</div>
							</div>
							<div style="overflow: auto">
								<table class="table" style="width: 400px">
									<tr><td><?php echo $lng['project_id']?></td><td><span id="project_id_info"></span></td></tr>
									<tr><td><?php echo $lng['available']?></td><td><span id="available_dc"></span></td></tr>
									<tr><td><?php echo $lng['amount']?></td><td><input class="form-control" type="text" id="cf_amount"></td></tr>
									<tr><td><?php echo $lng['commission']?></td><td><input class="form-control" type="text" id="cf_commission"></td></tr>
									<tr><td><?php echo $lng['note']?></td><td><input class="form-control" type="text" id="cf_comment"></td></tr>
								</table>
								<button id="cf_next" class="btn btn-outline btn-primary" type="button"><?php echo $lng['send']?></button>
								<div class="panel panel-success" style="margin-top: 20px; max-width: 400px">
									<div class="panel-heading">
										<?php echo $lng['where_get_dc']?>
									</div>
									<div class="panel-body">
										<p><?php echo $lng['where_get_dc_text']?></p>
									</div>
								</div>
							</div>

						</div>

				</div>

			<div class="tab-pane fade" id="forex">

			</div>

			</div>
		</div>

	</div>

	<div id="wallets_confirm" style="margin: auto; width: 400px; display: none">
		<h3><?php echo $lng['check_data']?></h3>
		<table class="table" style="width: 300px; margin-top: 20px">
			<tbody>
			<tr><td><?php echo $lng['currency']?></td><td id="confirm_currency"></td></tr>
			<tr><td><?php echo $lng['to_account']?></td><td id="confirm_to_user_id"></td></tr>
			<tr><td><?php echo $lng['amount']?></td><td id="confirm_amount"></td></tr>
			<tr><td><?php echo $lng['commission']?></td><td id="confirm_commission"></td></tr>
			<tr><td><?php echo $lng['note']?></td><td id="confirm_comment"></td></tr>
			</tbody>
		</table>
		<button type="button" class="btn btn-link" onclick="fc_navigate('wallets_list')"><?php echo $lng['back']?></button> <button id="next" class="btn btn-outline btn-primary" type="button" style="margin-left: 7px"><?php echo $lng['send_to_net']?></button>

	</div>

	<?php require_once( 'signatures.tpl' );?>
    
	<input type="hidden" id="comment_encrypted" value="">

<script>

		<?php
		if ($tpl['cf_project_id']) {
		?>

			$('#myTab a[href="#send_to_cf"]').tab('show');

			$("#cf_prject_id").css("display", "none");
			$("#cf_prject_card").css("display", "block");
			$("#project_id").val(<?php echo $tpl['cf_project_id']?>);

			$.post( 'ajax/wallets_list_cf_project.php', {
				'project_id' : <?php echo $tpl['cf_project_id']?>
			}, function (data) {
				fill_cf_card(data);
			}, 'JSON');

		<?php
		}
		?>

</script>