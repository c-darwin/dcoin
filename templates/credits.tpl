<script>

	function change_creditor(credit_id) {
		fc_navigate('change_creditor', {'credit_id':credit_id});
	}

	$('#new_credit').bind('click', function () {
		fc_navigate('new_credit');
	});

	$('#credit_part_save').bind('click', function () {

		<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#main_data").css("display", "none");	$("#sign").css("display", "block");' ?>

		$("#for-signature").val( '<?php echo "{$tpl['data']['credit_part_type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#credit_part").val());
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	});

	$('#send_to_net').bind('click', function () {

		$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['credit_part_type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
				'pct' : $('#credit_part').val(),
				'signature1': $('#signature1').val(),
				'signature2': $('#signature2').val(),
				'signature3': $('#signature3').val()
			}, function (data) {
				fc_navigate ('credits', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
		);
	});

</script>
<div id="main_div">
	<h1 class="page-header"><?php echo $lng['credits']?></h1>
	<ul class="nav nav-tabs" id="myTab">
		<li><a href="#wallets_list"><?php echo $lng['send_to_wallet']?></a></li>
		<li><a href="#currency_exchange"><?php echo $lng['currency_exchange1']?></a></li>
		<li class="active"><a href="#credits""><?php echo $lng['credits']?></a></li>
		<li><a href="#arbitration"><?php echo $lng['arbitration']?></a></li>
	</ul>

	<div id="main_data">
		<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

		<h3><?php echo $lng['I_creditor']?></h3>
		<table class="table" style="width:600px">
		<?php
			echo "<tr><th>{$lng['time']}</th><th>{$lng['amount']}</th><th>{$lng['currency']}</th><th>User_ID</th><th style='text-align: center'>{$lng['action']}</th></tr>";
			foreach ($tpl['I_creditor'] as $data) {
				echo "<tr>";
				echo "<td>{$data['time']}</td>";
				echo "<td>{$data['amount']}</td>";
				echo "<td>D{$tpl['currency_list'][$data['currency_id']]}</td>";
				echo "<td>{$data['from_user_id']}</td>";
				echo "<td><button type='button' class='btn btn-default' onclick=\"change_creditor({$data['id']})\">{$lng['transfer']}</button> <a class=\"btn btn-danger\" href=\"#\" onclick=\"fc_navigate('del_credit', {'credit_id':'{$data['id']}'}); return false;\"><i class=\"fa fa-trash-o fa-lg\"></i></a></td>";
			}
		?>
		</table>

		<br>
		<h3><?php echo $lng['I_debtor']?></h3>
		<table class="table" style="width:500px">
			<?php
			echo "<tr><th>{$lng['time']}</th><th>{$lng['amount']}</th><th>{$lng['currency']}</th><th>User_ID</th><th>%</th><th style='text-align: center'>{$lng['pay']}</th></tr>";
			foreach ($tpl['I_debtor'] as $data) {
				echo "<tr>";
				echo "<td>{$data['time']}</td>";
				echo "<td>{$data['amount']}</td>";
				echo "<td>D{$tpl['currency_list'][$data['currency_id']]}</td>";
				echo "<td>{$data['to_user_id']}</td>";
				echo "<td>{$data['pct']}</td>";
				echo "<td style='text-align: center'><button type='button' class='btn btn-default' onclick=\"fc_navigate('repayment_credit', {'credit_id':'{$data['id']}'}); return false;\">{$lng['pay']}</button></td>";
			}
			?>
		</table>

		<button type="button" class="btn btn-primary" id="new_credit"><?php echo $lng['create_credit']?></button>

		<div class="form-inline" style="margin-top: 50px">
			<?php echo $lng['will_not_be_used']?><br>
			<input id="credit_part" type="text" class="form-control" style="width: 100px" value="<?php echo $tpl['credit_part']?>"/>
			<button type="button" class="btn btn-default" id="credit_part_save"><?php echo $lng['save']?></button>
		</div>
	</div>

</div>

<?php require_once( 'signatures.tpl' );?>