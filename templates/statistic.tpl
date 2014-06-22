<!-- container -->
<div class="container">

	<legend><h2><?php echo $lng['statistic']?></h2></legend>

<?php
	echo '<table class="table table-bordered" style="width:600px"><caption>Main</caption>';
	echo '<thead><tr><th>currency</th><th>DC</th><th>promised_amount</th></tr></thead>';
	echo '<tbody>';
	foreach ($sum_wallets as $currency_id => $sum_amount) {
		if ($currency_id == 1)
			continue;
		print "<tr><td>{$tpl['currency_list'][$currency_id]}</td><td>{$sum_amount}</td><td>{$sum_promised_amount[$currency_id]}</td></tr>";
	}
	echo '</tbody>';
	echo '</table>';
	?>


</div>

<!-- /container -->