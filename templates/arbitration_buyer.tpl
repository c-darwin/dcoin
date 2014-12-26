<script>

</script>
<div id="main_div">
	<h1 class="page-header"><?php echo $lng['arbitration']?></h1>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	<ol class="breadcrumb">
		<li><a href="#wallets_list"><?php echo $lng['wallets']?></a></li>
		<li><a href="#arbitration"><?php echo $lng['arbitration']?></a></li>
		<li class="active"><?php echo $lng['i_buyer']?></li>
	</ol>

	<div id="main_data">

			<h3><?php echo $lng['my_purchases']?></h3>
			<table class="table" style="max-width: 600px">
				<tr><td>ID</td><td><?php echo $lng['time']?></td><td><?php echo $lng['amount']?></td><td><?php echo $lng['seller']?></td><td><?php echo $lng['money_back']?></td></tr>
				<?php
				foreach ($tpl['my_orders'] as $data) {
					echo "<tr>
							<td>{$data['id']}</td>
							<td class='unixtime'>{$data['time']}</td>
							<td>{$data['amount']}</td>
							<td>{$data['seller']}</td>
							<td><a class=\"btn btn-danger\" href=\"#\" onclick=\"fc_navigate('money_back_request', {'order_id':'{$data['id']}'}); return false;\">Moneyback</a></td>
						</tr>";
				}
				?>
			</table>
	</div>

</div>

<?php require_once( 'signatures.tpl' );?>
<script src="js/unixtime.js"></script>