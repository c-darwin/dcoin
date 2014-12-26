<script>

	var tx_type = '';

	$('#next').bind('click', function () {

		<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#main_data").css("display", "none");	$("#sign").css("display", "block");' ?>

		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$('#arbitration_days_refund').val()+','+$('#hold_back_pct').val());
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	});


	$('#send_to_net').bind('click', function () {

		$.post( 'ajax/save_queue.php', {

				'type' : '<?php echo $tpl['data']['type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
				'arbitration_days_refund' : $('#arbitration_days_refund').val(),
				'hold_back_pct' : $('#hold_back_pct').val(),
				'signature1': $('#signature1').val(),
				'signature2': $('#signature2').val(),
				'signature3': $('#signature3').val()
			}, function (data) {
				fc_navigate ('arbitration_seller', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
		);
	});

	function money_back(id) {
		fc_navigate('money_back', {'id': id, 'amount': $('#money_back_amount_'+id).val()})
	}

</script>
<div id="main_div">
	<h1 class="page-header"><?php echo $lng['arbitration']?></h1>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	<ol class="breadcrumb">
		<li><a href="#wallets_list"><?php echo $lng['wallets']?></a></li>
		<li><a href="#arbitration"><?php echo $lng['arbitration']?></a></li>
		<li class="active"><?php echo $lng['i_seller']?></li>
	</ol>

	<div id="main_data">

			<h3><?php echo $lng['my_deals']?></h3>
			<table class="table" style="max-width: 600px">
				<tr><td>ID</td><td><?php echo $lng['time']?></td><td><?php echo $lng['amount']?></td><td><?php echo $lng['seller']?></td><td><?php echo $lng['money_back']?></td></tr>
				<?php
				foreach ($tpl['my_orders'] as $data) {
					echo "<tr>
							<td>{$data['id']}</td>
							<td class='unixtime'>{$data['time']}</td>
							<td>{$data['amount']}</td>
							<td>{$data['seller']}</td>";
					// возможно покупатель запросил манибек
					if ($data['status']=='refund') {
						if ($data['comment_status'] == 'decrypted') {
							echo "<td><div style=\"width: 100px; overflow: auto\">{$data['comment']}</div>";
						}
						else {
							echo "<td><div id=\"comment_{$data['id']}\"><input type=\"hidden\" id=\"encrypt_comment_{$data['id']}\" value=\"{$data['comment']}\"><button class=\"btn\" onclick=\"decrypt_comment({$data['id']}, 'comments')\">{$lng['decrypt']}</button></div>";
						}
						echo "<input type='text' class='form-control' style='width:100px; margin: 5px 0px' id='money_back_amount_{$data['id']}'><button type='button' class='btn btn-outline btn-primary' onclick='money_back({$data['id']})'>Money back</button></td>";
					}

					echo "</tr>";
				}
				?>
			</table>

		<strong><?php echo $lng['number_days_hold_back']?></strong><br>
		<div class="form-inline"><input type="text" class="form-control" id="arbitration_days_refund" style="width: 100px; margin-right: 10px" value="<?php echo $tpl['hold_back']['arbitration_days_refund']?>"></div><br>
		<strong><?php echo $lng['hold_back_pct']?></strong><br>
		<div class="form-inline"><input type="text" class="form-control" id="hold_back_pct" style="width: 100px; display: inline-block" value="<?php echo  $tpl['hold_back']['seller_hold_back_pct']>0?$tpl['hold_back']['seller_hold_back_pct']:'0.01'?>"></div>
		<button type="button" class="btn btn-outline btn-primary" id="next" style="margin-top: 15px"><?php echo $lng['send_to_net']?></button>
		<br>

		<div style=" max-width: 600px;" id="tx_history">

			<?php
			if (isset($tpl['last_tx_formatted'])) {
				echo $tpl['last_tx_formatted'];
			}
			?>

		</div>
	</div>
</div>

<?php require_once( 'signatures.tpl' );?>
<script src="js/unixtime.js"></script>