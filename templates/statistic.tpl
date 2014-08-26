<script>

	$('#profileclose').bind('click', function () {
		$("#profile_window").css("display", "none");
	});

	$('#find_user_info').bind('click', function () {
		fc_navigate ('statistic', {'user_info_id': $("#user_info_id").val()} );
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
if ($tpl['user_info_id']) {
	echo '<h3>User ID: '.$tpl['user_info_id'].'</h3>';
	echo '<h3>'.$lng['balances'].'</h3>';
	echo '<table class="table table-bordered" style="width:600px">';
	echo '<thead><tr><th>'.$lng['currency'].'</th><th>'.$lng['amount'].'</th></tr></thead>';
	if ($tp['user_info']['wallets']) {
		foreach ($tp['user_info']['wallets'] as $data) {
			echo "<tr>";
			if ($data['currency_id']>=1000)
				echo "<td><a href=\"#\" onclick=\"fc_navigate('cf_page_preview', {'only_cf_currency_name':'{$tpl['currency_list'][$data['currency_id']]}'})\">{$tpl['currency_list'][$data['currency_id']]}</a></td>";
			else
				echo "<td>D{$tpl['currency_list'][$data['currency_id']]}</td>";

			echo "<td>{$data['amount']}</td>";
			echo "</tr>";
		}
	}
	echo '</table>';

	if ($tpl['promised_amount_list']['accepted']) {
		echo '<h3>'.$lng['promised_amounts'].'</h3>';
		echo '<table class="table table-bordered" style="width:600px">';
		echo "<thead><tr><th>ID</th><th>{$lng['status']}</th><th style='text-align: center'>{$lng['currency']}</th><th style='text-align: center'>{$lng['amount']}</th><th style='text-align: center'>{$lng['pct_year']}</th><th>Coins</th></tr></thead>";
		echo '<tbody>';
		foreach($tpl['promised_amount_list']['accepted'] as $data) {
			$to_wallet = 0;
			if ($data['tdc'] > 0.01)
				$to_wallet = $data['tdc']-0.01;
			echo "<tr>";
			echo "<td>{$data['id']}</td>";
			echo "<td>{$data['status_text']}</td>";
			echo "<td style='text-align: center'>{$tpl['currency_list'][$data['currency_id']]}</td>";
			echo "<td style='text-align: center'>{$data['amount']}</td>";
			echo "<td style='text-align: center'>{$data['pct']}</td>";
			echo "<td>{$data['tdc']}</td>";
			echo "</tr>";
		}
		echo '</tbody>';
		echo '</table>';
	}
}
?>

<div class="form-horizontal" style="max-width: 800px">
	<fieldset>
		<div class="form-group">
			<label class="col-md-4 control-label" for="user_info_id"><?php echo $lng['user_info']?></label>
			<div class="col-md-4">
				<div class="input-group">
					<input id="user_info_id" name="user_info_id" class="form-control" type="text">
					<div class="input-group-btn">
						<button id="find_user_info" name="find_user_info" class="btn btn-primary"><?php echo $lng['find']?></button>
					</div>
				</div>
				<span class="help-block"><?php echo $lng['user_info_text']?></span>
			</div>
		</div>

	</fieldset>
</div>



<?php
	echo '<h3>'.$lng['general'].'</h3>';
	echo '<table class="table table-bordered" style="width:600px">';
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
	echo '<h3>'.$lng['statistic_cash_requests'].'</h3>';
	echo '<table class="table table-bordered" style="width:600px">';
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
	echo $lng['uid_2_7'];
?>