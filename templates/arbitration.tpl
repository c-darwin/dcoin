<script>
	var arbitration_trust_list = '';
	var get_key_and_sign = 'null';

	$('#next').bind('click', function () {

		$(".arbitrator_id", $("#my_trust_list")).each(function(){
			if ($(this).text()) {
				arbitration_trust_list = arbitration_trust_list+$(this).text()+',';
			}
		} );
		if (arbitration_trust_list)
			arbitration_trust_list = '['+arbitration_trust_list.substr(0, arbitration_trust_list.length-1)+']';
		else
			arbitration_trust_list = '[0]';

		<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#main_data").css("display", "none");	$("#sign").css("display", "block");' ?>
		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+arbitration_trust_list);

		get_key_and_sign = <?php echo !defined('SHOW_SIGN_DATA')?'"send_to_net"':'"sign"' ?>;
		check_key_and_show_modal2();

	});

	$('#send_to_net').bind('click', function () {

		$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['type']?>',
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


	$("table.trust_list").on("click", ".btn_del", function (event) {
		$(this).closest("tr").remove();
	});

	$("#add_arbitrator").on("click", function (event) {
		$('.trust_list').css('display', 'block');
		$('.trust_list > tbody:last').append('<tr><td class="arbitrator_id">'+$("#new_arbitrator").val()+'</td><td></td><td></td><td><button class="btn btn-default  btn_del">del</button></td></tr>');
	});

</script>
<style>
	#my_trust_list > input{margin-top:5px}
</style>
<div id="main_div">
	<h1 class="page-header"><?php echo $lng['arbitration']?></h1>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	<ul class="nav nav-tabs" id="myTab">
		<li><a href="#wallets_list"><?php echo $lng['send_to_wallet']?></a></li>
		<li><a href="#currency_exchange"><?php echo $lng['currency_exchange1']?></a></li>
		<li><a href="#credits"><?php echo $lng['credits']?></a></li>
		<li class="active"><a href="#arbitration"><?php echo $lng['arbitration']?></a></li>
	</ul>

	<div id="main_data">
		<div style="float:left; margin-right: 20px; max-width: 400px">
			<h3><?php echo $lng['my_trust_list']?></h3>
			<div id="my_trust_list" style="<?php echo $tpl['pending_tx']?'display:none':'display:block'?>">
				<?php echo $lng['id_of_the_new_arbitrator']?><input type="text" class="form-control" id="new_arbitrator" style="display: inline-block; max-width: 50px; margin: 5px;"><button class="btn btn-default" id="add_arbitrator"  style="display: inline-block"><?php echo $lng['add']?></button>

					<table class="trust_list table" <?php echo !$tpl['my_trust_list']?'style="display:none"':''?>>
						<tr>
							<th>ID</th>
							<th>Link</th>
							<th><?php echo $lng['trust_pers'] ?></th>
							<th><?php echo $lng['delete'] ?></th>
						</tr>
						<tbody>
						<?php
						foreach ($tpl['my_trust_list'] as $data) {
							echo "<tr>";
							echo "<td class='arbitrator_id'>{$data['arbitrator_user_id']}</td>";
							echo $data['url']?"<td><a href='{$data['url']}' target='_blank'><i class='fa fa-external-link'></i></a></td>":"<td></td>";
							echo "<td>{$data['count']}</td>";
							echo "<td><button class='btn btn-default btn_del'>del</button></td></tr>";
						}
						?>
						</tbody>
					</table>
				<br>
				<button type="button" class="btn btn-outline btn-primary" id="next"><?php echo $lng['send_to_net']?></button>
			</div>
			<div id="pending" style="<?php echo !$tpl['pending_tx']?'display:none':'display:block'?>">
				<div class="alert alert-success">
					<?php echo $lng['being_processed']?>
				</div>
			</div>
		</div>

		<div style="float:left; padding-top: 75px; padding-left: 70px; overflow: auto">

			<a type="button" class="btn btn-primary" href="#arbitration_buyer"><?php echo $lng['i_buyer']?></a><br><br>
			<a type="button" class="btn btn-primary" href="#arbitration_seller"><?php echo $lng['i_seller']?></a><br><br>
			<a type="button" class="btn btn-primary" href="#arbitration_arbitrator"><?php echo $lng['i_arbitrator']?></a><br><br>

		</div>
		<div class="clearfix"></div>
		<table class="table" style="max-width: 700px; margin-top: 20px">
			<caption><strong><?php echo $lng['top_10_arbitrators']?></strong></caption>
			<tr>
				<td colspan="1" rowspan="2" style="vertical-align: middle;">ID</td>
				<td colspan="1" rowspan="2" style="vertical-align: middle;">Link</td>
				<td colspan="1" rowspan="2" style="vertical-align: middle;"><?php echo $lng['trust_pers']?></td>
				<td colspan="3" rowspan="1" style="text-align: center"><?php echo $lng['for_last_month']?></td>
			</tr>
			<tr>
				<td><?php echo $lng['resolution_in_favor_of_the_buyer']?></td>
				<td><?php echo $lng['average_refund_amount']?></td>
				<td><?php echo $lng['resolution_in_favor_of_the_seller']?></td>
			</tr>
			<?php
			if (!empty($tpl['arbitrators'])) {
				foreach ($tpl['arbitrators'] as $data) {
					echo "<tr>
							<td>{$data['arbitrator_user_id']}</td>";
					echo $data['url'] ? "<td><a href='{$data['url']}' target='_blank'><i class='fa fa-external-link'></i></a></td>" : "<td></td>";
					echo "<td>" . intval($data['count']) . "</td>
							<td>" . intval($data['refund_data']['count']) . "</td>
							<td>" . round(@($data['refund_data']['sum'] / $data['refund_data']['count']), 2) . "</td>
							<td>" . intval($data['count_rejected_refunds']) . "</td>
						</tr>";
				}
			}
			?>
		</table>

		<?php
		if (isset($tpl['last_tx_formatted'])) {
			echo $tpl['last_tx_formatted'];
		}
		?>

	</div>

</div>

<?php require_once( 'signatures.tpl' );?>
<script src="js/unixtime.js"></script>