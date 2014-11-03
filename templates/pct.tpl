
	<h1 class="page-header"><?php echo $lng['pct']?></h1>

<?php
	if (isset($tpl['currency_pct'])) {
		echo '<table class="table table-bordered" style="width:500px">';
		echo "<thead><tr><th>{$lng['currency']}</th><th>{$lng['pct_year']} miner</th><th>{$lng['pct_year']} user</th></tr></thead>";
		echo '<tbody>';
		foreach ($tpl['currency_pct'] as $currency_id=>$data) {
			if (!$data['miner'] && !$data['user'])
				continue;
			echo "
				<tr>
					<td>d{$data['name']}</td>
					<td>{$data['miner']}</td>
					<td>{$data['user']}</td>
				</tr>
				";
			}
			echo '</tbody>';
			echo '</table>';
	}
?>

