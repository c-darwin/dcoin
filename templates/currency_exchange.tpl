
<script>
	$('#currency_ok').bind('click', function () {
		fc_navigate ('currency_exchange', {'buy_currency_id': $('#buy_currency_id').val(), 'sell_currency_id': $('#sell_currency_id').val()} );
	});

	var sell_currency_id = 0;
	var sell_rate = 0;
	var sell_amount = 0;
	var buy_currency_id = 0;
	var commission = 0;

	$('#buy_button').bind('click', function () {

		$("#main").css("display", "none");
		$("#confirm").css("display", "block");
		$("#sign").css("display", "block");

		var buy_price = Number($("#buy_price").val());
		sell_currency_id = $("#sell_currency_id").val();
		sell_rate = 1/buy_price;
		sell_rate = sell_rate.toFixed(10);
		sell_amount = Number($("#buy_amount").val()) * buy_price;
		sell_amount = sell_amount.toFixed(2);
		buy_currency_id = $("#buy_currency_id").val();
		commission = $("#buy_commission").val();

		$("#confirm").html('sell_currency_id: '+sell_currency_id+'<br>'+'sell_rate: '+sell_rate+'<br>'+'sell_amount: '+sell_amount+'<br>'+'buy_currency_id: '+buy_currency_id+'<br>'+'commission: '+commission+'<br><br>');
		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+sell_currency_id+','+sell_rate+','+sell_amount+','+buy_currency_id+','+commission);
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	});



	$('#sell_button').bind('click', function () {

		$("#main").css("display", "none");
		$("#confirm").css("display", "block");
		$("#sign").css("display", "block");

		var sell_price = Number($("#sell_price").val());
		sell_currency_id = $("#buy_currency_id").val();
		sell_rate = sell_price;
		sell_rate = sell_rate.toFixed(10);
		sell_amount =  Number($("#sell_amount").val()) ;
		sell_amount = sell_amount.toFixed(2);
		buy_currency_id = $("#sell_currency_id").val();
		commission = $("#sell_commission").val();

		$("#confirm").html('sell_currency_id: '+sell_currency_id+'<br>'+'sell_rate: '+sell_rate+'<br>'+'sell_amount: '+sell_amount+'<br>'+'buy_currency_id: '+buy_currency_id+'<br>'+'commission: '+commission+'<br><br>');
		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+sell_currency_id+','+sell_rate+','+sell_amount+','+buy_currency_id+','+commission);
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	});


	$('#buy_amount,#buy_price,#buy_commission').keyup(function(e) {
/*
		var buy_amount = Number($("#buy_amount").val());
		var buy_price = Number($("#buy_price").val());
		var buy_commission = Number($("#buy_commission").val());

		if ( buy_amount && buy_price && buy_commission>=0 ) {
			$("#buy_total").text( buy_amount * buy_price );
		}*/
		calc_commission ('buy_amount', 'buy_price', 'buy_commission', 'sell_currency_id', 'buy_total');
	});

	function calc_commission (amount_id, price_id, commission_id, currency_id, total_id) {

		var amount = $("#"+amount_id).val();
		var price = $("#"+price_id).val();
		var commission = $("#"+commission_id).val();

		if (amount > 0) {
			var currency_id = $("#"+currency_id).val();
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
			commission = commission.toFixed(2);
			if (commission < min_commission)
				commission = min_commission;
			commission = parseFloat(commission);
			amount = parseFloat(amount);
			commission = parseFloat(commission);
			$("#"+commission_id).val(commission);
		}
		if ( amount && price && commission>=0 ) {
			$("#"+total_id).text( amount * parseFloat(price) );
		}
	}

	$('#sell_amount,#sell_price,#sell_commission').keyup(function(e) {
/*
		var sell_amount = Number($("#sell_amount").val());
		var sell_price = Number($("#sell_price").val());
		var sell_commission = Number($("#sell_commission").val());
		if ( sell_amount && sell_price && sell_commission>=0 ) {
			$("#sell_total").text( sell_amount * sell_price );
		}*/

		calc_commission ('sell_amount', 'sell_price', 'sell_commission', 'buy_currency_id', 'sell_total');
	});

	var currency_commission = [];
	<?php
	foreach($tpl['config']['commission'] as $currency_id=>$data) {
		echo "currency_commission[{$currency_id}] = [];\n";
		echo "currency_commission[{$currency_id}][0] = '{$data[0]}';\n";
		echo "currency_commission[{$currency_id}][1] = '{$data[1]}';\n";
	}
	?>

	$('#amount, #cf_amount, #currency_id').bind("keyup change", function(e) {

		var amount = $("#"+add+"amount").val();

	});


	$('#send_to_net').bind('click', function () {

		$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'sell_currency_id' :  sell_currency_id,
			'sell_rate' : sell_rate,
			'amount' :  sell_amount,
			'buy_currency_id' :  buy_currency_id,
			'commission' :  commission,
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
		}, function(data) {
			fc_navigate ('currency_exchange', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
		});

	} );

	$("#main_div select").addClass( "form-control" );
	$("#main_div input").addClass( "form-control form-control-fix" );
	$("#main_div button").addClass( "btn-outline btn-primary" );
</script>
<style>
	.form-control-fix{display: inline; width: 100px}
	td{padding: 3px}
</style>

<div id="main_div">
<h1 class="page-header"><?php echo $lng['currency_exchange']?></h1>
	<ul class="nav nav-tabs" id="myTab">
		<li><a href="#wallets_list"><?php echo $lng['send_to_wallet']?></a></li>
		<li class="active"><a href="#currency_exchange"><?php echo $lng['currency_exchange1']?></a></li>
		<li><a href="#credits"><?php echo $lng['credits']?></a></li>
	</ul>


	<div id="main" style="padding-top: 10px">
	<p><?php echo $lng['forex_alert'] ?></p>
	<div style="text-align: center; max-width: 700px">
	<div style="padding-bottom: 10px; display: inline-block"><?php echo $lng['order_buy']?>
		<select id="buy_currency_id" style="width: 100px; display: inline-block" class="form-control" >
		<?php
		foreach ($tpl['currency_list_name'] as $id => $name) {
			if ($id == @$tpl['buy_currency_id'])
			$selected = 'selected';
			else
			$selected = '';
			echo "<option value='{$id}' {$selected}>{$name}</option>";
			}
		?>
		</select> <a href="#currency_exchange/buy_currency_id=<?php echo $tpl['sell_currency_id']?>/sell_currency_id=<?php echo $tpl['buy_currency_id']?>"><i class="fa  fa-exchange  fa-fw"></i></a> <?php echo $lng['order_sell']?>
			<select id="sell_currency_id" style="width: 100px; display: inline-block"" class="form-control" >
				<?php
		foreach ($tpl['currency_list_name'] as $id => $name) {
				if ($id == @$tpl['sell_currency_id'])
				$selected = 'selected';
				else
				$selected = '';
				echo "<option value='{$id}' {$selected}>{$name}</option>";
				}
				?>
			</select>
		<button class="btn" id="currency_ok">OK</button>
	</div>
		<div class="clearfix"></div>
		<a href="#currency_exchange/all_currencies=1"><?php echo $lng['show_all']?></a>
	</div>
	<br>
	<?php
	//echo $tpl['currency_list_name'][$tpl['currency_id']];
	?>

	<div style="float: left">
			<table>
				<caption><strong><?php echo "{$lng['buy']} {$tpl['buy_currency_name']}"?></strong></caption>
				<tr><td><?php echo "{$lng['amount_currency']} {$tpl['buy_currency_name']}"?>: </td><td><input type="text" id="buy_amount" class="input-mini form-control"></td></tr>
				<tr><td><?php echo "{$lng['price_per']} {$tpl['buy_currency_name']}"?>: </td><td><input type="text" id="buy_price" class="input-mini form-control"> <?php echo $tpl['sell_currency_name']?></td></tr>
				<tr style="height: 40px"><td><?php echo $lng['total']?>: </td><td><span id="buy_total">0</span> <?php echo $tpl['sell_currency_name']?></td></tr>
				<tr><td><?php echo $lng['commission']?>: </td><td><input type="text" id="buy_commission" class="input-mini form-control"> <?php echo $tpl['sell_currency_name']?></td></tr>
				<tr style="height: 40px"><td>Your balance: </td><td><?php echo @$tpl['wallets_amounts'][$tpl['sell_currency_id']].' '.$tpl['sell_currency_name']?></td></tr>
			</table>
			<button class="btn" id="buy_button"><?php echo "{$lng['buy']} {$tpl['buy_currency_name']}"?></button>
				<br><br>

			<div style="width: 330px; max-height: 500px; overflow: auto;">
			<table class="table" style="width: 330px"><caption><?php echo $lng['sell_orders']?></caption>
				<thead><tr><th><?php echo $lng['price']?></th><th><?php echo $tpl['buy_currency_name']?></th><th><?php echo $tpl['sell_currency_name']?></th></tr></thead>
				<tbody>
					<?php
					if ($tpl['sell_orders'])
					foreach ($tpl['sell_orders'] as $data) {
						echo "<tr><td>".clear_zero($data['sell_rate'])."</td><td>".clear_zero($data['amount'])."</td><td>".clear_zero($data['amount']*$data['sell_rate'])."</td></tr>";
					}
					?>
				</tbody>
			</table>
			</div>

		</div>
		<div style="float: left">
			<table>
				<caption><strong><?php echo "{$lng['sell']} {$tpl['buy_currency_name']}"?></strong></caption>
				<tr><td><?php echo "{$lng['amount_currency']} {$tpl['buy_currency_name']}"?>: </td><td><input type="text" id="sell_amount" class="input-mini form-control"></td></tr>
				<tr><td><?php echo "{$lng['price_per']} {$tpl['buy_currency_name']}"?>: </td><td><input type="text" id="sell_price" class="input-mini form-control"> <?php echo $tpl['sell_currency_name']?></td></tr>
				<tr style="height: 40px"><td><?php echo $lng['total']?>: </td><td><span id="sell_total">0</span> <?php echo $tpl['sell_currency_name']?></td></tr>
				<tr><td><?php echo $lng['commission']?>: </td><td><input type="text" id="sell_commission" class="input-mini form-control"> <?php echo $tpl['buy_currency_name']?></td></tr>
				<tr style="height: 40px"><td>Your balance: </td><td><?php echo @$tpl['wallets_amounts'][$tpl['buy_currency_id']].' '.$tpl['buy_currency_name']?></td></tr>
			</table>
			<button class="btn" id="sell_button"><?php echo "{$lng['sell']} {$tpl['buy_currency_name']}"?></button>
			<br><br>

			<div style="width: 330px; max-height: 500px; overflow: auto;">
			<table class="table" style="width: 330px"><caption><?php echo $lng['buy_orders']?></caption>
				<thead><tr><th><?php echo $lng['price']?></th><th><?php echo $tpl['buy_currency_name']?></th><th><?php echo $tpl['sell_currency_name']?></th></tr></thead>
				<tbody>
				<?php
				if ($tpl['buy_orders'])
					foreach ($tpl['buy_orders'] as $data) {
						echo "<tr><td>".clear_zero(round(1/$data['sell_rate'], 6))."</td><td>".clear_zero(round($data['amount']*$data['sell_rate'], 2))."</td><td>".clear_zero($data['amount'])."</td></tr>";
				}
				?>
				</tbody>
			</table>
			</div>

		</div>
		<div class="clearfix"></div>

		<h2>My orders</h2>
		<table class="table" id="my_orders">
			<thead><tr><th>Order id</th><th>Sell_currency_id</th><th>sell_rate</th><th>amount</th><th>buy_currency_id</th><th>commission</th><th>del</th></tr></thead>
			<tbody>
			<?php
			if ($tpl['my_orders'])
			foreach ($tpl['my_orders'] as $data) {
				echo "<tr><td>{$data['id']}</td><td>{$data['sell_currency_id']}</td><td>".clear_zero($data['sell_rate'])."</td><td>".clear_zero($data['amount'])."</td><td>{$data['buy_currency_id']}</td><td>".clear_zero($data['commission'])."</td><td><a href='#' onclick=\"fc_navigate('currency_exchange_delete', {'del_id':'".$data['id']."'})\">Del</a></td></tr>";
			}
			?>

			</tbody>
		</table>
	</div>

	<div id="confirm" style="display:none">

	</div>

	<?php require_once( 'signatures.tpl' );?>
</div>

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
	$( document ).ready(function() {
		$('#my_orders').stacktable();
	});
</script>