<!-- container -->
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
		sell_rate = sell_rate.toFixed(6);
		sell_amount = Number($("#buy_amount").val()) * buy_price;
		sell_amount = sell_amount.toFixed(2);
		buy_currency_id = $("#buy_currency_id").val();
		commission = $("#buy_commission").val();

		$("#confirm").html('sell_currency_id: '+sell_currency_id+'<br>'+'sell_rate: '+sell_rate+'<br>'+'sell_amount: '+sell_amount+'<br>'+'buy_currency_id: '+buy_currency_id+'<br>'+'commission: '+commission+'<br><br>');
		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+sell_currency_id+','+sell_rate+','+sell_amount+','+buy_currency_id+','+commission);
	});



	$('#sell_button').bind('click', function () {

		$("#main").css("display", "none");
		$("#confirm").css("display", "block");
		$("#sign").css("display", "block");

		var sell_price = Number($("#sell_price").val());
		sell_currency_id = $("#buy_currency_id").val();
		sell_rate = sell_price;
		sell_rate = sell_rate.toFixed(6);
		sell_amount =  Number($("#sell_amount").val()) ;
		sell_amount = sell_amount.toFixed(2);
		buy_currency_id = $("#sell_currency_id").val();
		commission = $("#sell_commission").val();

		$("#confirm").html('sell_currency_id: '+sell_currency_id+'<br>'+'sell_rate: '+sell_rate+'<br>'+'sell_amount: '+sell_amount+'<br>'+'buy_currency_id: '+buy_currency_id+'<br>'+'commission: '+commission+'<br><br>');
		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+sell_currency_id+','+sell_rate+','+sell_amount+','+buy_currency_id+','+commission);
	});


	$('#buy_amount,#buy_price,#buy_commission').keyup(function(e) {

		var buy_amount = Number($("#buy_amount").val());
		var buy_price = Number($("#buy_price").val());
		var buy_commission = Number($("#buy_commission").val());

		if ( buy_amount && buy_price && buy_commission>=0 ) {
			$("#buy_total").text( buy_amount * buy_price );
		}
	});

	$('#sell_amount,#sell_price,#sell_commission').keyup(function(e) {

		var sell_amount = Number($("#sell_amount").val());
		var sell_price = Number($("#sell_price").val());
		var sell_commission = Number($("#sell_commission").val());

		if ( sell_amount && sell_price && sell_commission>=0 ) {
			$("#sell_total").text( sell_amount * sell_price );
		}
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
</script>
<div class="container">

	<legend><h2><?php echo $lng['currency_exchange']?></h2></legend>

	<div style="width: 700px" id="main">

	<div style="text-align: center; width: 100%;">
	<div class="form-inline" style="padding-bottom: 10px">
		<select id="buy_currency_id" style="width: 100px">
		<?php
		foreach ($tpl['currency_list_name'] as $id => $name) {
			if ($id == @$tpl['buy_currency_id'])
			$selected = 'selected';
			else
			$selected = '';
			echo "<option value='{$id}' {$selected}>{$name}</option>";
			}
		?>
		</select> /
			<select id="sell_currency_id" style="width: 100px">
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
	</div>
	<br>
	<?php
	//echo $tpl['currency_list_name'][$tpl['currency_id']];
	?>

	<table>
		<tr><td>
			<table>
				<caption><strong><?php echo "{$lng['buy']} {$tpl['buy_currency_name']}"?></strong></caption>
				<tr><td><?php echo "{$lng['amount_currency']} {$tpl['buy_currency_name']}"?>: </td><td><input type="text" id="buy_amount" class="input-mini"></td></tr>
				<tr><td><?php echo "{$lng['price_per']} {$tpl['buy_currency_name']}"?>: </td><td><input type="text" id="buy_price" class="input-mini"> <?php echo $tpl['sell_currency_name']?></td></tr>
				<tr style="height: 40px"><td><?php echo $lng['total']?>: </td><td><span id="buy_total">0</span> <?php echo $tpl['sell_currency_name']?></td></tr>
				<tr><td><?php echo $lng['commission']?>: </td><td><input type="text" id="buy_commission" class="input-mini"> <?php echo $tpl['sell_currency_name']?></td></tr>
				<tr style="height: 40px"><td>Your balance: </td><td><?php echo @$tpl['wallets_amounts'][$tpl['sell_currency_id']].' '.$tpl['sell_currency_name']?></td></tr>
			</table>
			<button class="btn" id="buy_button"><?php echo "{$lng['buy']} {$tpl['buy_currency_name']}"?></button>
				<br><br>

			<div style="width: 330px; height: 500px; overflow: auto;">
			<table class="table" style="width: 330px"><caption><?php echo $lng['sell_orders']?></caption>
				<thead><tr><th><?php echo $lng['price']?></th><th><?php echo $tpl['buy_currency_name']?></th><th><?php echo $tpl['sell_currency_name']?></th></tr></thead>
				<tbody>
					<?php
					if ($tpl['sell_orders'])
					foreach ($tpl['sell_orders'] as $data) {
						echo "<tr><td>".($data['sell_rate'])."</td><td>{$data['amount']}</td><td>".($data['amount']*$data['sell_rate'])."</td></tr>";
					}
					?>
				</tbody>
			</table>
			</div>

		</td>
		<td style="vertical-align: top">
			<table>
				<caption><strong><?php echo "{$lng['sell']} {$tpl['buy_currency_name']}"?></strong></caption>
				<tr><td><?php echo "{$lng['amount_currency']} {$tpl['buy_currency_name']}"?>: </td><td><input type="text" id="sell_amount" class="input-mini"></td></tr>
				<tr><td><?php echo "{$lng['price_per']} {$tpl['buy_currency_name']}"?>: </td><td><input type="text" id="sell_price" class="input-mini"> <?php echo $tpl['sell_currency_name']?></td></tr>
				<tr style="height: 40px"><td><?php echo $lng['total']?>: </td><td><span id="sell_total">0</span> <?php echo $tpl['sell_currency_name']?></td></tr>
				<tr><td><?php echo $lng['commission']?>: </td><td><input type="text" id="sell_commission" class="input-mini"> <?php echo $tpl['buy_currency_name']?></td></tr>
				<tr style="height: 40px"><td>Your balance: </td><td><?php echo @$tpl['wallets_amounts'][$tpl['buy_currency_id']].' '.$tpl['buy_currency_name']?></td></tr>
			</table>
			<button class="btn" id="sell_button"><?php echo "{$lng['sell']} {$tpl['buy_currency_name']}"?></button>
			<br><br>

			<div style="width: 330px; height: 500px; overflow: auto;">
			<table class="table" style="width: 330px"><caption><?php echo $lng['buy_orders']?></caption>
				<thead><tr><th><?php echo $lng['price']?></th><th><?php echo $tpl['buy_currency_name']?></th><th><?php echo $tpl['sell_currency_name']?></th></tr></thead>
				<tbody>
				<?php
				if ($tpl['buy_orders'])
					foreach ($tpl['buy_orders'] as $data) {
						echo "<tr><td>".round(1/$data['sell_rate'], 6)."</td><td>".round($data['amount']*$data['sell_rate'], 2)."</td><td>{$data['amount']}</td></tr>";
				}
				?>
				</tbody>
			</table>
			</div>

		</td></tr>
	</table>
		<h2>My orders</h2>
		<table class="table">
			<thead><tr><th>Order id</th><th>Sell_currency_id</th><th>sell_rate</th><th>amount</th><th>buy_currency_id</th><th>commission</th><th>del</th></tr></thead>
			<tbody>
			<?php
			if ($tpl['my_orders'])
			foreach ($tpl['my_orders'] as $data) {
				echo "<tr><td>{$data['id']}</td><td>{$data['sell_currency_id']}</td><td>{$data['sell_rate']}</td><td>{$data['amount']}</td><td>{$data['buy_currency_id']}</td><td>{$data['commission']}</td><td><a href='#' onclick=\"fc_navigate('currency_exchange_delete', {'del_id':'".$data['id']."'})\">Del</a></td></tr>";
			}
			?>

			</tbody>
		</table>
	</div>

	<div id="confirm" style="display:none">

	</div>

	<?php require_once( 'signatures.tpl' );?>

</div>

<!-- /container -->