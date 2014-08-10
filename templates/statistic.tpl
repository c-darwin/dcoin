
	<h1 class="page-header"><?php echo $lng['statistic']?></h1>

<?php
	echo '<table class="table table-bordered" style="width:600px"><caption>Main</caption>';
	echo "<thead><tr><th>{$lng['currency']}</th><th>DC</th><th>{$lng['promised_amounts']}</th><th>miners</th><th>users</th></tr></thead>";
	echo '<tbody>';
	foreach ($sum_wallets as $currency_id => $sum_amount) {
		if ($currency_id == 1)
			continue;
		print "<tr><td>{$tpl['currency_list'][$currency_id]}</td><td>{$sum_amount}</td><td>".intval($sum_promised_amount[$currency_id])."</td><td>".intval($promised_amount_miners[$currency_id])."</td><td>".intval($wallets_users[$currency_id])."</td></tr>";
	}
	echo '</tbody>';
	echo '</table>';
	?>

