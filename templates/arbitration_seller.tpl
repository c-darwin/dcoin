<script>

	var tx_type = '';
	var get_key_and_sign = 'null';

	$('#next').bind('click', function () {

		<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#main_data").css("display", "none");	$("#sign").css("display", "block");' ?>
		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$('#arbitration_days_refund').val()+','+$('#hold_back_pct').val());

		get_key_and_sign = <?php echo !defined('SHOW_SIGN_DATA')?'"send_to_net"':'"sign"' ?>;
		check_key_and_show_modal2();
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
		fc_navigate('money_back', {'order_id': id, 'amount': $('#money_back_amount_'+id).val()})
	}

	$('#generate_token').bind('click', function (e) {
		$('#shop_secret_key').val(Math.random().toString(36).slice(-10));
		e.preventDefault();
	});

	$('#save_shop_data').bind('click', function () {
		$.post( 'ajax/save_shop_data.php', {
				'shop_callback_url' : $('#shop_callback_url').val(),
				'shop_secret_key' : $('#shop_secret_key').val()
			},
			function () {
				fc_navigate ('arbitration_seller', {'alert': '<?php echo $lng['saved']?>'} );
			});
	});

	function decrypt_comment_0 (id) {
		decrypt_comment_01 (id, 'seller', <?php echo $tpl['miner_id']?>, <?php print json_encode(utf8_encode(mcrypt_create_iv(mcrypt_get_iv_size('rijndael-128', MCRYPT_MODE_ECB), MCRYPT_RAND)))?>);
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
							echo "<td><div class=\"comment_{$data['id']}\"><input type=\"hidden\" id=\"encrypt_comment_{$data['id']}\" value=\"{$data['comment']}\"><button class=\"btn\" onclick=\"decrypt_comment_0({$data['id']})\">{$lng['decrypt']}</button></div>";
						}
						echo "<input type='text' class='form-control' style='width:100px; margin: 5px 0px' id='money_back_amount_{$data['id']}'><button type='button' class='btn btn-outline btn-primary' onclick='money_back({$data['id']})'>Money back</button></td>";
					}
					else {
						echo "<td></td>";
					}

					echo "</tr>";
				}
				?>
			</table>

		<div style="<?php echo $tpl['pending_tx']?'display:none':'display:block'?>">
			<strong><?php echo $lng['number_days_hold_back']?></strong><br>
			<div class="form-inline"><input type="text" class="form-control" id="arbitration_days_refund" style="width: 100px; margin-right: 10px" value="<?php echo $tpl['hold_back']['arbitration_days_refund']?>"></div><br>
			<strong><?php echo $lng['hold_back_pct']?></strong><br>
			<div class="form-inline"><input type="text" class="form-control" id="hold_back_pct" style="width: 100px; display: inline-block" value="<?php echo  $tpl['hold_back']['seller_hold_back_pct']>0?$tpl['hold_back']['seller_hold_back_pct']:'0.01'?>"></div>
			<button type="button" class="btn btn-outline btn-primary" id="next" style="margin-top: 15px"><?php echo $lng['send_to_net']?></button>
			<br><br>
		</div>
		<div id="pending" style="<?php echo !$tpl['pending_tx']?'display:none':'display:block'?>">
			<div class="alert alert-success">
				<?php echo $lng['being_processed']?>
			</div>
		</div>


		<?php
		if (empty($_SESSION['restricted'])) {
			?>
			<strong>shop_callback_url</strong><br>
			<div class="form-inline"><input type="text" class="form-control" id="shop_callback_url"
			                                style="width: 200px; margin-right: 10px"
			                                value="<?php echo $tpl['shop_data']['shop_callback_url'] ?>"></div><br>
			<strong>shop_secret_key [<a href="#" id="generate_token">generate</a>]</strong><br>
			<div class="form-inline"><input type="text" class="form-control" id="shop_secret_key"
			                                style="width: 200px; display: inline-block"
			                                value="<?php echo $tpl['shop_data']['shop_secret_key'] ?>"></div>
			<button type="button" class="btn btn-outline btn-primary" id="save_shop_data"
			        style="margin-top: 15px"><?php echo $lng['save'] ?></button>
			<br>
		<?php
		}
		?>

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