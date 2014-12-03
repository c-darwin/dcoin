<h1 class="page-header">Progress</h1>

<?php
	if (isset($tpl['progress_bar_pct'])) {
		echo '<table class="table table-bordered" style="max-width:400px">';
		echo "<thead><tr><th style='width:15px'>Status</th><th>Name</th><th>%</th></tr></thead>";
		echo '<tbody>';
		foreach ($tpl['progress_bar_pct'] as $name => $pct) {
			if ($name!='referral')
				echo ($tpl['progress_bar'][$name])?'<tr style=\'background-color:#DFF0D8;\'><td style="text-align:center"><i class="fa fa-check-square-o"></i></td>':'<tr><td></td>';
			else {
				$pct = $pct * 30;
				echo '<td></td>';
			}
			echo "
					<td>{$lng['progress_bar_pct'][$name]}</td>
					<td>+{$pct}%</td>
					";
			echo "</tr>";
		}
		echo '</tbody>';
		echo '</table>';
	}
?>

