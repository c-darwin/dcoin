<script>
	$('#profileclose').bind('click', function () {
		$("#profile_window").css("display", "none");
	});
</script>
<script src="js/js.js"></script>
<div id="profile_window" style="display: none; width: 500px;	padding:10px 10px; background-color: #fff; border:  1px solid black; ">
	<button type="button" class="close" id="profileclose">×</button>
	<div style="float: left; margin-right: 10px"><img id="profile_photo" width="200"></div>
	<?php echo $lng['abuses']?>: <span id="profile_abuses"></span><br>
	<?php echo $lng['reg_time']?>: <span id="profile_reg_time"></span>
	<div id="reloadbtn"></div>
</div>

<h1 class="page-header"><?php echo $lng['statistic']?></h1>

<?php
	echo '<table class="table table-bordered" style="width:600px"><caption>Main</caption>';
	echo "<thead><tr><th>{$lng['currency']}</th><th>Coins</th><th>{$lng['promised_amounts']}</th><th>miners</th><th>users</th></tr></thead>";
	echo '<tbody>';
	foreach ($sum_wallets as $currency_id => $sum_amount) {
		print "<tr>";
		if ($currency_id>=1000)
			echo "<td><a href=\"#\" onclick=\"fc_navigate('cf_page_preview', {'only_cf_currency_name':'{$tpl['currency_list'][$currency_id]}'})\">{$tpl['currency_list'][$currency_id]}</a></td>";
		else
			echo "<td>D{$tpl['currency_list'][$currency_id]}</td>";

		print "<td>{$sum_amount}</td>";

		if ($currency_id>=1000 || $currency_id==1)
			echo "<td>{$lng['impossible']}</td>";
		else
			echo "<td>".intval($sum_promised_amount[$currency_id])."</td>";

		print "<td>".intval($promised_amount_miners[$currency_id])."</td><td>".intval($wallets_users[$currency_id])."</td></tr>";
	}
	echo '</tbody>';
	echo '</table>';
?>


<?php
	echo '<table class="table table-bordered" style="width:600px"><caption>Cash_requests</caption>';
	echo "<thead><tr><th>ID</th><th>{$lng['time']}</th><th>from_user_id</th><th>to_user_id</th><th>{$lng['currency']}</th><th>{$lng['amount']}</th><th>{$lng['status']}</th></tr></thead>";
	echo '<tbody>';
	foreach ($tpl['cash_requests'] as $data) {
		echo "<tr>";
		echo "<td>{$data['id']}</td>";
		echo "<td>{$data['time']}</td>";
		echo "<td><a href='#' onclick='show_profile({$data['from_user_id']});return false'>{$data['from_user_id']}</a></td>";
		echo "<td><a href='#' onclick='show_profile({$data['to_user_id']});return false'>{$data['to_user_id']}</a></td>";
		echo "<td>{$tpl['currency_list'][$data['currency_id']]}</td>";
		echo "<td>{$data['amount']}</td>";
		echo "<td>{$data['status']}</td>";
		echo "</tr>";
	}
	echo '</tbody>';
	echo '</table>';
?>