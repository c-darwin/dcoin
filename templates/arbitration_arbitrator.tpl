<script>
	var arbitration_trust_list = '';

	$('#next').bind('click', function () {

		$("input[type=text]", $("#my_trust_list")).each(function(){
			if ($(this).val()) {
				arbitration_trust_list = arbitration_trust_list+$(this).val()+',';
			}
		} );
		if (arbitration_trust_list)
			arbitration_trust_list = '['+arbitration_trust_list.substr(0, arbitration_trust_list.length-1)+']';
		else
			arbitration_trust_list = '[0]';

		<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#main_data").css("display", "none");	$("#sign").css("display", "block");' ?>

		$("#for-signature").val( '<?php echo "{$tpl['data']['credit_part_type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+arbitration_trust_list);
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	});

	$('#send_to_net').bind('click', function () {

		$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['credit_part_type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
				'arbitration_trust_list' : arbitration_trust_list,
				'signature1': $('#signature1').val(),
				'signature2': $('#signature2').val(),
				'signature3': $('#signature3').val()
			}, function (data) {
				fc_navigate ('arbitration', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
		);
	});

	function money_back(id) {
		fc_navigate('money_back', {'order_id': id, 'arbitrator': 1, 'amount': $('#money_back_amount_'+id).val()})
	}

	function change_money_back_time(id) {
		fc_navigate('change_money_back_time', {'order_id': id, 'days': $('#change_money_back_time_'+id).val()})
	}

	function decrypt_comment_0 (id) {
		decrypt_comment_01 (id, 'arbitrator', <?php echo $tpl['miner_id']?>, <?php print json_encode(utf8_encode(mcrypt_create_iv(mcrypt_get_iv_size('rijndael-128', MCRYPT_MODE_ECB), MCRYPT_RAND)))?>);
	}

</script>
<style>
	th{text-align: center;}
</style>
<div id="main_div">
	<h1 class="page-header"><?php echo $lng['arbitration']?></h1>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	<ol class="breadcrumb">
		<li><a href="#wallets_list"><?php echo $lng['wallets']?></a></li>
		<li><a href="#arbitration"><?php echo $lng['arbitration']?></a></li>
		<li class="active"><?php echo $lng['i_arbitrator']?></li>
	</ol>

	<div id="main_data">

			<h3><?php echo $lng['requests']?></h3>
			<table class="table" style="max-width: 600px">
				<tr><th>ID</th><th><?php echo $lng['time']?></th><th><?php echo $lng['amount']?></th><th><?php echo $lng['buyer']?></th><th><?php echo $lng['contacts']?></th><th><?php echo $lng['extend_days']?></th><th><?php echo $lng['money_back']?></th></tr>
				<?php
				foreach ($tpl['my_orders'] as $data) {
					echo "<tr>
							<td>{$data['id']}</td>
							<td class='unixtime'>{$data['time']}</td>
							<td>{$data['amount']}</td>
							<td>{$data['seller']}</td>";
					if ($data['comment_status'] == 'decrypted') {
						echo "<td><div style=\"width: 100px; overflow: auto\">{$data['comment']}</div></td>";
					}
					else {
						echo "<td><div class=\"comment_{$data['id']}\"><input type=\"hidden\" id=\"encrypt_comment_{$data['id']}\" value=\"{$data['comment']}\"><button class=\"btn\" onclick=\"decrypt_comment_0({$data['id']})\">{$lng['decrypt']}</button></div></td>";
					}
					echo "<td><input type='text' class='form-control' style='width:100px; margin: 5px 0px' id='change_money_back_time_{$data['id']}'><button type='button' class='btn btn-outline btn-primary' onclick='change_money_back_time({$data['id']})'>OK</button></td>";
					echo "<td><input type='text' class='form-control' style='width:100px; margin: 5px 0px' id='money_back_amount_{$data['id']}'><button type='button' class='btn btn-outline btn-primary' onclick='money_back({$data['id']})'>Money back</button></td>";

					echo "</tr>";
				}
				?>
			</table>

		<a type="button" class="btn btn-primary" href="#change_arbitrator_conditions"><?php echo $lng['my_conditions']?></a>

			<?php
			if (isset($tpl['last_tx_formatted'])) {
				echo $tpl['last_tx_formatted'];
			}
			?>

	</div>

</div>

<?php require_once( 'signatures.tpl' );?>
<script src="js/unixtime.js"></script>