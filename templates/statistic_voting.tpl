<style>
	.demo-container-plot {
		box-sizing: border-box;
		width: 450px;
		height: 200px;
		padding: 20px 15px 15px 15px;
		margin: 15px auto 30px auto;
		border: 1px solid #ddd;
		background: #fff;
		background: linear-gradient(#f6f6f6 0, #fff 50px);
		background: -o-linear-gradient(#f6f6f6 0, #fff 50px);
		background: -ms-linear-gradient(#f6f6f6 0, #fff 50px);
		background: -moz-linear-gradient(#f6f6f6 0, #fff 50px);
		background: -webkit-linear-gradient(#f6f6f6 0, #fff 50px);
		box-shadow: 0 3px 10px rgba(0,0,0,0.15);
		-o-box-shadow: 0 3px 10px rgba(0,0,0,0.1);
		-ms-box-shadow: 0 3px 10px rgba(0,0,0,0.1);
		-moz-box-shadow: 0 3px 10px rgba(0,0,0,0.1);
		-webkit-box-shadow: 0 3px 10px rgba(0,0,0,0.1);
	}

	.demo-placeholder {
		width: 100%;
		height: 100%;
		font-size: 14px;
		line-height: 1.2em;
	}
</style>
<script src="js/jquery_002.js"></script>

<script src="js/js.js"></script>

<h1 class="page-header"><?php echo $lng['voting']?></h1>

<?php

$js = '';
$divs = array();

echo '<h3>max_promised_amounts</h3>';
echo '<table class="table table-bordered" style="width:600px">';
echo "<thead><tr><th>currency</th><th>votes</th><th>result</th></tr></thead>";
if (isset($tpl['max_promised_amount_votes'])) {
	foreach ($tpl['max_promised_amount_votes'] as $currency_id => $data) {

		$js.="var max_promised_amounts_{$currency_id} = [";
		foreach ($data as $k=>$v){
			$js.="[{$k}, {$v}],";
		}
		$js = substr($js, 0, -1)."];\n";

		$divs[] = "max_promised_amounts_{$currency_id}";

		echo "<tr>";
		echo "<td>d{$tpl['currency_list'][$currency_id]}</td>";
		echo "<td><div class='demo-container-plot'><div id='max_promised_amounts_{$currency_id}' class='demo-placeholder'></div></div></td>";
		echo "<td>{$tpl['new_max_promised_amounts'][$currency_id]}</td>";
		echo "</tr>";
	}
}
echo '</tbody>';
echo '</table>';
?>


<?php
echo '<h3>max_other_currencies</h3>';
echo '<table class="table table-bordered" style="width:600px">';
echo "<thead><tr><th>currency</th><th>votes</th><th>result</th></tr></thead>";
if (isset($tpl['max_other_currencies_votes'])) {
	foreach ($tpl['max_other_currencies_votes'] as $currency_id => $data) {

		$divs[] = "max_other_currencies_votes_{$currency_id}";

		$js.="var max_other_currencies_votes_{$currency_id} = [";
		foreach ($data as $k=>$v){
			$js.="[{$k}, {$v}],";
		}
		$js = substr($js, 0, -1)."];\n";

		echo "<tr>";
		echo "<td>d{$tpl['currency_list'][$currency_id]}</td>";
		echo "<td><div class='demo-container-plot'><div id='max_other_currencies_votes_{$currency_id}' class='demo-placeholder'></div></div></td>";
		echo "<td>{$tpl['new_max_other_currencies'][$currency_id]}</td>";
		echo "</tr>";
	}
}
echo '</tbody>';
echo '</table>';
?>


<?php
echo '<h3>votes_reduction</h3>';
echo '<table class="table table-bordered" style="width:600px">';
echo "<thead><tr><th>currency</th><th>votes</th><th>need</th></tr></thead>";
if (isset($tpl['votes_reduction'])) {
	foreach ($tpl['votes_reduction'] as $currency_id => $data) {

		//$divs[] = "votes_reduction_{$currency_id}";

		echo "<tr>";
		echo "<td>d{$tpl['currency_list'][$currency_id]}</td>";
		echo "<td><pre style='height:100px'>" . print_r($data, true) . "</pre></td>";
		echo "<td>".($tpl['promised_amount'][$currency_id]/2)."</td>";
		echo "</tr>";
	}
}
echo '</tbody>';
echo '</table>';
?>


<?php
echo '<h3>votes_referral</h3>';
echo '<table class="table table-bordered" style="width:600px">';
echo "<thead><tr><th>currency</th><th>votes</th><th>result</th></tr></thead>";
if (isset($tpl['votes_referral'])) {
	foreach ($tpl['votes_referral'] as $level => $data) {

		$divs[] = "votes_referral_{$level}";

		$js.="var votes_referral_{$level} = [";
		foreach ($data as $k=>$v){
			$js.="[{$k}, {$v}],";
		}
		$js = substr($js, 0, -1)."];\n";

		echo "</script>";
		echo "<tr>";
		echo "<td>{$level}</td>";
		echo "<td><div class='demo-container-plot'><div id='votes_referral_{$level}' class='demo-placeholder'></div></div></td>";
		echo "<td>{$tpl['new_referral_pct'][$level]}</td>";
		echo "</tr>";
	}
}
echo '</tbody>';
echo '</table>';
?>



<?php
echo '<h3>votes_pct</h3>';
echo '<table class="table table-bordered" style="width:600px">';
echo "<thead><tr><th>currency</th><th>miner pct votes</th><th>result</th><th>user pct votes</th><th>result</th></tr></thead>";
if (isset($tpl['pct_votes'])) {
	foreach ($tpl['pct_votes'] as $currency_id => $data) {

		$divs[] = "miner_pct_{$currency_id}";
		$divs[] = "user_pct_{$currency_id}";

		$js.="var miner_pct_{$currency_id} = [";
		foreach ($data['miner_pct'] as $k=>$v){
			$js.="[{$k}, {$v}],";
		}
		$js = substr($js, 0, -1)."];\n";

		$js.="var user_pct_{$currency_id} = [";
		foreach ($data['user_pct'] as $k=>$v){
			$js.="[{$k}, {$v}],";
		}
		$js = substr($js, 0, -1)."];\n";

		echo "<tr>";
		echo "<td>d{$tpl['currency_list'][$currency_id]}</td>";
		echo "<td><div class='demo-container-plot'><div id='miner_pct_{$currency_id}' class='demo-placeholder'></div></div></td>";
		echo "<td>{$tpl['new_pct'][$currency_id]['miner_pct']}</td>";
		echo "<td><div class='demo-container-plot'><div id='user_pct_{$currency_id}' class='demo-placeholder'></div></div></td>";
		echo "<td>{$tpl['new_pct'][$currency_id]['user_pct']}</td>";
		echo "</tr>";
	}
}
echo '</tbody>';
echo '</table>';
?>

<script type="text/javascript">

	$(function() {

		<?php
		echo $js;
		for ($i=0; $i<sizeof($divs); $i++) {
			echo "$.plot(\"#{$divs[$i]}\", [{\n data: {$divs[$i]}, \n	bars: { show: true }\n }]);\n\n";
		}
		?>

	});

</script>
