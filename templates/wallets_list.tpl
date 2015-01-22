
<style>
	.table td {
		vertical-align: middle;
	}
	.table input, .table select  {
		margin-bottom: 0px;
	}
</style>

<link href="css/cf.css" rel="stylesheet">

<script>

var type = '';
var to_id = '';
var global_arbitrator_id = '0';
var global_arbitrator_commission = '0';
var global_arbitrator_commission_pct = '0';
var global_arbitrators_array = [];
var global_arbitrators_commissions_array = [];
var global_arbitrators_commissions_array2 = [];

var currency_list = [];
<?php
foreach ($tpl['wallets'] as $id => $data)
	echo "currency_list[{$data['currency_id']}] = '{$tpl['currency_list'][$data['currency_id']]}';\n";

?>

var arbitration_trust_list = [];
<?php
foreach ($tpl['arbitration_trust_list'] as $arbitrator_user_id=>$all_conditions) {
	echo "arbitration_trust_list[{$arbitrator_user_id}]=[];\n";
	$all_conditions = json_decode($all_conditions);
	foreach ($all_conditions as $a_currency_id=>$conditions) {
		echo "arbitration_trust_list[{$arbitrator_user_id}][{$a_currency_id}]=[];\n";
		echo "arbitration_trust_list[{$arbitrator_user_id}][{$a_currency_id}]['min_amount']='{$conditions[0]}';\n";
		echo "arbitration_trust_list[{$arbitrator_user_id}][{$a_currency_id}]['max_amount']='{$conditions[1]}';\n";
		echo "arbitration_trust_list[{$arbitrator_user_id}][{$a_currency_id}]['min_commission']='{$conditions[2]}';\n";
		echo "arbitration_trust_list[{$arbitrator_user_id}][{$a_currency_id}]['max_commission']='{$conditions[3]}';\n";
		echo "arbitration_trust_list[{$arbitrator_user_id}][{$a_currency_id}]['commission_pct']='{$conditions[4]}';\n";
	}
}
?>

var arbitration_trust_list_agreed = '';
$('#goto_confirm').bind('click', function () {

	console.log('goto_confirm');

	//check_key_and_show_modal();

	var to_user_id = $("#to_user_id").val();

	console.log('to_user_id='+to_user_id);
	// возможно юзер поставил галочку возле арбитража
	if ( $("#arbitration").is(':checked') && to_user_id ) {

		console.log('arbitration');
		$("#arbitrator_link_add").css("display", "");

		// шлем запрос, чтобы получить список арбитров, с кем работает магазин
		$.post( 'ajax/get_seller_data.php', {
			'user_id' : to_user_id,
			'currency_id' : $("#currency_id").val()
		}, function (data) {

			if (data.arbitration_days_refund>0)
				$("#seller_info_div").css('display', '');
			else
				$("#wallets_confirm").css('maxWidth', '300px');


			// статистика по продавцу
			$("#seller_hold_back_pct").text(data.seller_hold_back_pct);
			$("#arbitration_days_refund").text(data.arbitration_days_refund);
			$("#buyers_miners_count_m").text(data.buyers_miners_count_m);
			$("#buyers_miners_count").text(data.buyers_miners_count);
			$("#buyers_count").text(data.buyers_count);
			$("#buyers_count_m").text(data.buyers_count_m);
			$("#seller_turnover_m").text(data.seller_turnover_m);
			$("#seller_turnover").text(data.seller_turnover);
			$("#hold_amount").text(data.hold_amount);
			$(".currency_name").text('d'+currency_list[$("#currency_id").val()]);


			// ищем общих арбитров и у юзера и у магазина
			console.log(data.trust_list.length);
			if (data.trust_list.length==0) {
				$("#arbitrator_link_add").css("display", "none");
				$("#arbitration_imposible").css("display", "");
			}
			else {
				var arbitrator = '';
				var amount = $("#amount").val();
				for (var arbitrator_id in arbitration_trust_list) {
					console.log(arbitration_trust_list[arbitrator_id]);
					for (var i=0; i<data.trust_list.length; i++) {
						if (arbitrator_id == data.trust_list[i]) {
							var min_amount = arbitration_trust_list[arbitrator_id][$("#currency_id").val()]['min_amount'];
							var max_amount = arbitration_trust_list[arbitrator_id][$("#currency_id").val()]['max_amount'];
							if ((amount<min_amount && min_amount>0) || (amount>max_amount && max_amount>0))
								continue;
							if (!arbitration_trust_list_agreed)
								arbitration_trust_list_agreed = '<select  id="arbitrator_id" class="form-control arbitrator_id" style="max-width: 80px; display: inline-block">';
							if (i==0) {
								var selected = 'selected';
								arbitrator = arbitrator_id;
							}
							else
								var selected = '';
							arbitration_trust_list_agreed = arbitration_trust_list_agreed+'<option id="'+arbitrator_id+'" '+selected+'>'+arbitrator_id+'</option>';
						}
					}
				}

				if (arbitration_trust_list_agreed) {
					console.log('arbitration_trust_list_agreed='+arbitration_trust_list_agreed);
					arbitration_trust_list_agreed = arbitration_trust_list_agreed+'</select>';
					$("#arbitration_trust_list_html").html(arbitration_trust_list_agreed);
					var min_commission = arbitration_trust_list[arbitrator][$("#currency_id").val()]['min_commission'];
					var max_commission = arbitration_trust_list[arbitrator][$("#currency_id").val()]['max_commission'];
					var commission_pct = arbitration_trust_list[arbitrator][$("#currency_id").val()]['commission_pct'];
					var commission = amount*(commission_pct/100);
					if (commission < min_commission && min_commission>0)
						commission = min_commission;
					if (commission > max_commission && max_commission>0 )
						commission = max_commission;

					commission = foattoupper(commission);

					global_arbitrator_id = arbitrator;
					global_arbitrator_commission = commission;
					global_arbitrator_commission_pct = commission_pct;
					global_arbitrators_commissions_array[arbitrator] = commission;

					$("#arbitrator_commission_html").html(commission+' ('+commission_pct+'%)');
					$(".arbitration_tr").css("display", "");
					$("#arbitrator_tr_commission").css("display", "");
					$("#arbitrator_link_add").css("display", "");
				}
				else {
					console.log('arbitration_imposible');
					$("#arbitration_imposible").css("display", "");
					$("#arbitrator_link_add").css("display", "none");
				}
			}
		}, 'JSON');
	}

	$("#confirm_currency").text('d'+currency_list[$("#currency_id").val()]);
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

				if (cf) {
					$("#for-signature").val(tx_type_id + ',<?php echo "{$tpl['data']['time']},{$tpl['data']['user_id']}"?>,' + to_id + ',' + $('#' + cf + 'amount').val() + ',' + $('#' + cf + 'commission').val() + ',' + data + currency_id);
				}
				else {
					var arbitrators = '';
					var arbitrators_array = [];
					var arbitrators_commissions = '';
					$(".arbitrator_id option:selected").each(function() {
						arbitrators_array[$(this).val()] = 1;
					});
					var count_arbitrators = arbitrators_array.filter(function(value) { return value !== undefined }).length;
					var null_arbitrators = '';
					if (count_arbitrators < 5) {
						for (var i=count_arbitrators; i<5; i++)
							null_arbitrators=null_arbitrators+'0,';
						null_arbitrators = null_arbitrators.substr(0, null_arbitrators.length-1);
					}
					console.log('null_arbitrators='+null_arbitrators);
					console.log('arbitrators_array:');
					console.log(arbitrators_array);
					for (var id in arbitrators_array) {
						if (arbitrators!='')
							arbitrators = arbitrators + ',' + id;
						else
							arbitrators = id;
						if (arbitrators_commissions!='')
							arbitrators_commissions = arbitrators_commissions+','+global_arbitrators_commissions_array[id];
						else
							arbitrators_commissions = global_arbitrators_commissions_array[id];
						global_arbitrators_commissions_array2.push(global_arbitrators_commissions_array[id]);
						global_arbitrators_array.push(id);
					}
					console.log('global_arbitrators_array:');
					console.log(global_arbitrators_array);
					if (null_arbitrators) {
						if (arbitrators)
							arbitrators = arbitrators+','+null_arbitrators;
						else
							arbitrators = null_arbitrators;
						if (arbitrators_commissions)
							arbitrators_commissions = arbitrators_commissions+','+null_arbitrators;
						else
							arbitrators_commissions = null_arbitrators;
					}
					console.log('arbitrators='+arbitrators);
					console.log('arbitrators_commissions='+arbitrators_commissions);
					console.log(global_arbitrators_commissions_array);
					$("#for-signature").val(tx_type_id + ',<?php echo "{$tpl['data']['time']},{$tpl['data']['user_id']}"?>,' + to_id + ',' + $('#' + cf + 'amount').val() + ',' + $('#' + cf + 'commission').val() + ',' + arbitrators + ',' + arbitrators_commissions + ',' + data + currency_id);
				}
				doSign();
				<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>

			});
	}

} );

	function fill_cf_card(data)
	{
		$('#cf_tab').css('display', '');
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
			'arbitrators' : global_arbitrators_array,
			'arbitrators_commissions' : global_arbitrators_commissions_array2,
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

function decrypt_comment_0 (id) {
	decrypt_comment_01 (id, 'dc_transactions', <?php echo $tpl['miner_id']?>, <?php print json_encode(utf8_encode(mcrypt_create_iv(mcrypt_get_iv_size('rijndael-128', MCRYPT_MODE_ECB), MCRYPT_RAND)))?>);
}

var currency_commission = [];
<?php
if (!empty($tpl['config']['commission']))
foreach($tpl['config']['commission'] as $currency_id=>$data) {
	echo "currency_commission[{$currency_id}] = [];\n";
	echo "currency_commission[{$currency_id}][0] = '{$data[0]}';\n";
	echo "currency_commission[{$currency_id}][1] = '{$data[1]}';\n";
}
?>

$('#amount, #cf_amount, #currency_id').bind("keyup change", function(e) {

	if (this.id=='cf_amount')
		var add='cf_';
	else
		var add='';

	var amount = $("#"+add+"amount").val();
	if (amount > 0) {
		var currency_id = $("#currency_id").val();
		if (currency_id>=1000)
			currency_id=1000;
		var commission_pct = Number(currency_commission[currency_id][0]);
		var min_commission = Number(currency_commission[currency_id][1]);
		console.log(commission_pct + '/' + min_commission);
		var amount_ = '';
		amount_ = parseFloat(amount.replace(",", "."));
		amount_ = amount_.toFixed(2);

		if (amount.indexOf(",") != -1) {
			$("#" + add + "amount").val(amount_);
		}
		amount = amount_;

		var commission = amount * (commission_pct / 100);
		commission = foattoupper(commission);
		if (commission < min_commission)
			commission = min_commission;
		commission = parseFloat(commission);
		amount = parseFloat(amount);
		commission = parseFloat(commission);
		$("#" + add + "commission").val(commission);
	}
});

$("table.confirm").on("click", ".btn_del", function (event) {
	$(this).closest("tr").next().remove();
	$(this).closest(".arbitration_tr").remove();
	event.preventDefault();
	count_arbitrators = count_arbitrators-1;
	if (count_arbitrators<5) {
		$("#arbitrator_link_add").css("display", "");
	}
});

var count_arbitrators = 1;
$('table.confirm').on('change', '.arbitrator_id', function(e) {
	console.log($(this).val());
	console.log($(this).closest("tr").next().children('.arbitrator_commission').html());
	//console.log($(this).closest("#arbitrator_commission").html());
	var amount = $("#amount").val();
	var arbitrator = $(this).val();
	var min_commission = arbitration_trust_list[arbitrator][$("#currency_id").val()]['min_commission'];
	var max_commission = arbitration_trust_list[arbitrator][$("#currency_id").val()]['max_commission'];
	var commission_pct = arbitration_trust_list[arbitrator][$("#currency_id").val()]['commission_pct'];
	console.log(min_commission);
	console.log(commission_pct);
	var commission = amount*(commission_pct/100);
	if (commission < min_commission && min_commission>0)
		commission = min_commission;
	if (commission > max_commission && max_commission>0 )
		commission = max_commission;


	commission = foattoupper(commission);
	global_arbitrators_commissions_array[arbitrator] = commission;
	$(this).closest("tr").next().children('.arbitrator_commission').html(commission+' ('+commission_pct+'%)');
	//$("#arbitrator_commission_html").html(commission+' ('+commission_pct+'%)');
	$(".arbitration_tr").css("display", "");
	$("#arbitrator_tr_commission").css("display", "");
});

$("#add_arbitrator").on("click", function (event) {
	$('.confirm > tbody:last').append('<tr class="arbitration_tr"><td><?php echo $lng['arbitrator']?></td><td>'+$("#arbitration_trust_list_html").html()+'<button class="btn btn-default  btn_del">del</button></td></tr><tr><td><?php echo $lng['arbitrator_commission']?></td><td class="arbitrator_commission">'+global_arbitrator_commission+' ('+global_arbitrator_commission_pct+'%)</td></tr>');
	event.preventDefault();
	count_arbitrators = count_arbitrators+1;
	if (count_arbitrators==5) {
		$("#arbitrator_link_add").css("display", "none");
	}

});

function foattoupper(x) {
	x = parseFloat(x);
	return (Math.ceil(x*100)/100);
}

</script>
<script src="js/js.js"></script>

<h1 class="page-header"><?php echo $lng['wallets_list_title']?></h1>

	<div id="wallets">

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

		<div class="panel-body" style="padding: 0">
			<!-- Nav tabs -->
			<ul class="nav nav-tabs" id="myTab">
				<li class="active"><a href="#wallets_list" data-toggle="tab"><?php echo $lng['send_to_wallet']?></a>
				</li>
				<li class="" style="display:none" id="cf_tab"><a href="#send_to_cf" data-toggle="tab"><?php echo $lng['send_to_cf_project']?></a>
				</li>
				<li class=""><a href="#currency_exchange"><?php echo $lng['currency_exchange1']?></a>
				</li>
				<li class=""><a href="#credits"><?php echo $lng['credits']?></a>
				</li>
				<li class=""><a href="#arbitration"><?php echo $lng['arbitration']?></a>
				</li>
			</ul>

			<!-- Tab panes -->
			<div class="tab-content">
				<div class="tab-pane fade active in" id="send_to_wallet">
					<br>
					<div>
					<table class="table" style="max-width: 400px">
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
						<tr><td><?php echo $lng['commission']?></td><td><input class="form-control" type="text" id="commission" <?php echo defined('COMMUNITY')?'readonly="1" ':''?>></td></tr>
						<tr><td><?php echo $lng['note']?></td><td><input class="form-control" type="text" id="comment"></td></tr>
						<tr><td><?php echo $lng['arbitration']?></td><td><?php echo $tpl['arbitration_trust_list']?'<input type="checkbox" id="arbitration">':'<a href="#arbitration">'.$lng['add_arbitrators'].'</a>' ?></td></tr>
					</table>
					<button id="goto_confirm" class="btn btn-outline btn-primary" type="button" style="margin-left: 7px"><?php echo $lng['next']?></button>

					<br><br>
					<?php
					if (isset($tpl['wallets'])) {
						echo '<h3>'.$lng['balances'].'</h3><table class="table" style="max-width:400px">';
						//echo '<tr><th>'.$lng['currency'].'</th><th>'.$lng['amount'].'</th><th>'.$lng['pct_year'].'</th></tr>';
						echo '<tr><th>'.$lng['currency'].'</th><th>'.$lng['amount'].'</th></tr>';
						foreach ($tpl['wallets'] as $id => $data) {
							echo "<tr>";

							if ($data['currency_id']>=1000)
								echo "<td><a href=\"#cf_page_preview/only_cf_currency_name={$tpl['currency_list'][$data['currency_id']]}\">{$tpl['currency_list'][$data['currency_id']]}</a></td>";
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
					<div style="overflow: auto;display: none" >
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
					if (isset($tpl['last_tx_formatted'])) {
						echo $tpl['last_tx_formatted'];
					}
					?>

					<?php
					if (isset($tpl['my_dc_transactions'])) {
						echo '<h3>' . $lng['last_operation'] . '</h3><table class="table" style="width:500px;" id="my_dc_transactions">';
						echo '<tr><th></th><th>' . $lng['time'] . '</th><th>' . $lng['currency'] . '</th><th>' . $lng['type'] . '</th><th>' . $lng['recipient'] . '</th><th>' . $lng['amount'] . '</th><th>' . $lng['commission'] . '</th><th>' . $lng['note'] . '</th><th>' . $lng['status'] . '</th><th>Block_id</th><th>Confirmations</th></tr>';
						foreach ($tpl['my_dc_transactions'] as $key => $data) {
							print "<tr>";
							if ($data['to_user_id'] == $tpl['user_id'])
								echo "<td>+</td>";
							else
								echo "<td>-</td>";
							if ($data['time'])
								echo "<td>" . date('d-m-Y H:i:s', $data['time']) . "</td>";
							else
								echo "<td></td>";
							echo "<td>" . make_currency_name($data['currency_id']) . "{$tpl['currency_list'][$data['currency_id']]}</td>";

							echo "<td>{$names[$data['type']]} ({$data['type_id']})";
							if ($data['type'] == 'cf_project')
								echo "<button class=\"btn\" onclick=\"fc_navigate('del_cf_funding', {'del_id':'{$data['to_user_id']}'})\">Cancel</button>";
							echo "</td><td>{$data['to_user_id']}</td><td>{$data['amount']}</td><td>" . (($data['commission'] > 0) ? $data['commission'] : "") . "</td>";
							if ($data['comment_status'] == 'decrypted')
								echo "<td><div style=\"width: 100px; overflow: auto\">{$data['comment']}</div></td>";
							else
								echo "<td><div class=\"comment_{$data['id']}\"><input type=\"hidden\" id=\"encrypt_comment_{$data['id']}\" value=\"{$data['comment']}\"><button class=\"btn\" onclick=\"decrypt_comment_0({$data['id']})\">{$lng['decrypt']}</button></div></td>";
							$num_blocks = $data['block_id']?($tpl['data']['confirmed_block_id'] - $data['block_id']):0;
							$num_blocks = ($num_blocks>0)?$num_blocks:0;
							echo "<td>{$data['status']}</td><td><a href=\"#block_explorer/block_id={$data['block_id']}\">{$data['block_id']}</a></td><td>" . $num_blocks . "</td></tr>";
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
								<table class="table"  style="max-width: 400px">
									<tr><td><?php echo $lng['project_id']?></td><td><span id="project_id_info"></span></td></tr>
									<tr><td><?php echo $lng['available']?></td><td><span id="available_dc"></span></td></tr>
									<tr><td><?php echo $lng['amount']?></td><td><input class="form-control" type="text" id="cf_amount"></td></tr>
									<tr><td><?php echo $lng['commission']?></td><td><input class="form-control" type="text" id="cf_commission"  ></td></tr>
									<tr><td><?php echo $lng['note']?></td><td><input class="form-control" type="text" id="cf_comment"></td></tr>
								</table>
								<button id="cf_next" class="btn btn-outline btn-primary" type="button"><?php echo $lng['send']?></button>
								<!--
								<div class="panel panel-success" style="margin-top: 20px; max-width: 400px">
									<div class="panel-heading">
										<?php echo $lng['where_get_dc']?>
									</div>
									<div class="panel-body">
										<p><?php echo $lng['where_get_dc_text']?></p>
									</div>
								</div>
								-->
							</div>

						</div>

				</div>

			<div class="tab-pane fade" id="forex">

			</div>

			</div>
		</div>

	</div>

	<div id="wallets_confirm" style="margin: auto; max-width: 600px; display: none">
		<h3><?php echo $lng['check_data']?></h3>
		<div style="float: left">
			<table class="table confirm" style="width: 300px; margin-top: 20px">
				<tbody>
				<tr><td><?php echo $lng['currency']?></td><td id="confirm_currency"></td></tr>
				<tr><td><?php echo $lng['to_account']?></td><td id="confirm_to_user_id"></td></tr>
				<tr><td><?php echo $lng['amount']?></td><td id="confirm_amount"></td></tr>
				<tr><td><?php echo $lng['commission']?></td><td id="confirm_commission"></td></tr>
				<tr><td><?php echo $lng['note']?></td><td id="confirm_comment"></td></tr>
				<tr class="arbitration_tr" style="display: none"><td style="vert-align: middle"><?php echo $lng['arbitrator']?></td><td id="arbitration_trust_list_html"></td></tr>
				<tr id="arbitrator_tr_commission" style="display: none"><td><?php echo $lng['arbitrator_commission']?></td><td id="arbitrator_commission_html" class="arbitrator_commission"></td></tr>
				<tr id="arbitration_imposible" style="display: none"><td colspan="2"><?php echo $lng['arbitration_imposible']?></td></tr>
				</tbody>
			</table>

			<p id="arbitrator_link_add" style="display: none"><a href="#" id="add_arbitrator" style="margin-left: 8px"><?php echo $lng['add_arbitrator']?></a></p>

		</div>
		<div style="float: left; margin-left: 10px; display: none" id="seller_info_div">
		<style>
			.seller_info td {text-align: center; padding-left:5px; padding-right:5px}
			.seller_info {margin:auto}
			#seller_turnover, #seller_turnover_m, #buyers_count_m, #buyers_miners_count_m, #buyers_count, #buyers_miners_count{font-size: 30px}
			.table_line_height{line-height: 20px}
			.table_margin{display: block; margin-top:15px}
		</style>
			<h4 style="text-align: center"><?php echo $lng['seller_info']?></h4>
			<table class="seller_info">

				<tr><td colspan="2" style="font-weight: bold"><?php echo $lng['turnover']?></td></tr>
				<tr><td><?php echo $lng['month']?><br><span id="seller_turnover_m"></span></td><td><?php echo $lng['entire']?><br><span id="seller_turnover"></span></td></tr>

				<tr><td colspan="2" style="font-weight: bold; padding-top:10px"><?php echo $lng['number_of_customers']?></td></tr>
				<tr>
					<td>
						<?php echo $lng['month']?><br>
						<span class="table_line_height"><span id="buyers_count_m" class="table_margin"></span><?php echo $lng['anonim']?></span><br>
						<span class="table_line_height"><span id="buyers_miners_count_m" class="table_margin"></span><?php echo $lng['pers']?></span>
					</td>
					<td>
						<?php echo $lng['entire']?><br>
						<span class="table_line_height"><span id="buyers_count" class="table_margin"></span><?php echo $lng['anonim']?></span><br>
						<span class="table_line_height"><span id="buyers_miners_count" class="table_margin"></span><?php echo $lng['pers']?></span>
					</td>
				</tr>

			</table>
			<p style="margin-top: 10px">
			<?php echo $lng['hold_for_money_back']?>: <span id="hold_amount"></span> <span class="currency_name"></span><br>
				<?php echo $lng['hold_back']?>: <span id="seller_hold_back_pct"></span>% <?php echo $lng['for']?> <span id="arbitration_days_refund"></span> <?php echo $lng['days']?><br>
			</p>
		</div>
		<div class="clearfix"></div>
		<div style="text-align: center; max-width: 550px; margin-top: 20px"><button type="button" class="btn btn-link" onclick="fc_navigate('wallets_list')"><?php echo $lng['back']?></button> <button id="next" class="btn btn-outline btn-primary" type="button" style="margin-left: 7px"><?php echo $lng['send_to_net']?></button></div>
		<div class="clearfix"></div>
	</div>

	<?php require_once( 'signatures.tpl' );?>
    
	<input type="hidden" id="comment_encrypted" value="">

<style>
	.stacktable { width: 100%; }
	.st-head-row { padding-top: 1em;font-size: 2em; text-align: center }
	.st-head-row.st-head-row-main { font-size: 1.5em; padding-top: 0; }
	.st-key { width: 49%; text-align: right; padding-right: 1%; }
	.st-val { width: 49%; padding-left: 1%; }

	.stacktable.large-only { display: table; }
	.stacktable.small-only { display: none; }

	@media (max-width: 1000px) {
		.stacktable.large-only { display: none; }
		.stacktable.small-only { display: table; }
	}

</style>
<script src="js/stacktable.js"></script>
<script>

	$('#my_dc_transactions').stacktable();

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
<script src="js/unixtime.js"></script>
