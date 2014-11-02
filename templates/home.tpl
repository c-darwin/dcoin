<link rel="stylesheet" type="text/css" href="css/tooltipster.css" />
<link rel="stylesheet" type="text/css" href="css/tooltipster-shadow.css" />
<script type="text/javascript" src="js/jquery.tooltipster.min.js"></script>

<script>

	function my_notice() {
		$('#page-wrapper').spin();
		$.post('ajax/my_notice.php', function(data) {
			//poll_time = Date.now();
			//i++;

			$('#main_status').html(data.main_status);
			if (data.main_status_complete!=1)
				$("#main_status").css({ 'color': 'red'});
			else
				$("#main_status").css({ 'color': '#333'});

			$('#account_status').text(data.account_status);
			if (data.account_status=='Miner') {
				$('#account_status').css("color", "green");
				$('#account_status').css("font-weight", "bold");
			}
			$('#cur_block_id').text(data.cur_block_id);
			$('#connections').text(data.connections);
			$('#time_last_block').text(data.time_last_block);
			if (data.inbox!=0)
				$('#inbox').html('<a class="btn btn-warning" href="#" onclick="fc_navigate(\'cash_requests_in\')"><i class="fa fa-warning"></i></a>');
			else
				$('#inbox').text(data.inbox);
			if (data.alert == 1 && i%2 == 0)
				$('#bar_alert').css("display", "block");
			else if (data.alert == 1 && i%2 != 0)
				$('#bar_alert').css("display", "none");

			//setTimeout(doPoll,30000);
			$('#page-wrapper').spin(false);

		}, 'json' );
	}
	my_notice();

	//var i=0;
	$('#panel_refresh').bind('click', function () {
		my_notice();
		return false;
	});

	//if (Date.now() - poll_time > 10000)
	//	doPoll();

	$('#new_cf_project').bind('click', function () {

		$('#page-wrapper').spin();
		$( "#dc_content" ).load( "content.php", { tpl_name: "new_cf_project" }, function() {
			$.getScript("https://maps.googleapis.com/maps/api/js?sensor=false&callback=initialize", function(){
				$('#page-wrapper').spin(false);
			});
		});

	});

	<?php
	if (preg_match('/user|miner/iD', $tpl['my_notice']['account_status']) && !$user_id)
		echo '$("#main-login").html(\'<a href="#myModal" data-backdrop="static" data-toggle="modal" role="button" class="btn btn-danger  btn-block "><i class="fa fa-sign-in fa-lg"></i> Login</a><div style="margin: 2px 10px; font-size: 11px">'.$lng['login_alert'].'</div>\');';
	?>
</script>
<style>
	.alert-info a:link{text-decoration: underline};
</style>
<link href="css/cf.css" rel="stylesheet">
<h1 class="page-header">Home</h1>

<div id="message"></div>
<script>

	console.log('intervalIdArray='+intervalIdArray);
	if (typeof intervalIdArray != "undefined") {
		for (i=0; i<intervalIdArray.length; i++)
			clearInterval(intervalIdArray[i]);
	}
	var intervalIdArray = [];

	function dc_counter(amount, pct, currency_id)
	{
		var i=0;
		pct = pct / 3;

		var intervalID = setInterval( function() {
			 i++;
			 //console.log(i);
			 var new_amount =  Math.pow(1+pct, i) * amount;
			 $('#'+currency_id).text(new_amount.toFixed(5));
		} , 300);
		intervalIdArray.push(intervalID);
	}

</script>
<script>
	$(document).ready(function() {
		$('.tooltip').tooltipster({
			delay: 50,
			contentAsHTML: true,
			interactive: true,
			theme: 'tooltipster-shadow'
		});
	});
</script>
<div id="generate">
<?php
if (isset($tpl['wallets'])) {
	$i = 0;
	$js = '';
	foreach ($tpl['wallets'] as $id => $data) {
		if ($data['currency_id']==1)
			$style = 'primary';
		else
			$style = 'green';
		?>
			<div style="width: 340px; float: left; margin-right:20px">
				<div class="panel panel-<?php echo $style?>" style="height: 75px">
					<div class="panel-heading" style="height: 75px">
						<div class="row">
							<div class="col-xs-3 text-right" style="font-size: 32px;line-height: 0; padding-top:30px" id="currency_<?php echo $data['currency_id']?>">
								<?php echo $data['amount']?>
							</div>
							<div class="col-xs-9 text-right">
								<div class="huge" style="font-size: 40px;line-height: 0; padding-top:30px"><?php echo "d{$tpl['currency_list'][$data['currency_id']]}"?></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php
		if ($data['pct_sec']>0)
			$js.="dc_counter({$data['amount']}, {$data['pct_sec']}, 'currency_{$data['currency_id']}');\n";
		$i++;
	}
	echo "<script>{$js}</script>";
}
?>
<style>
	.dc{font: bold italic 20px serif; }
	.dc_div{position:absolute; top:50px; right:15px; color:#555}
	.credit{position:absolute; top:8px; right:15px;}
	.rate{position:absolute; top:65px; left:18px;font-size:16px; font-weight:bold; color:#5CB85C}
	.amount{font-size:40px; font-weight:bold; color:#5CB85C}
	.my_panel_body{padding-top:5px; position:relative;}
	.my_panel{width:215px; height:100px; margin-left:20px; background: linear-gradient(to bottom, #fff, #f0f0f0);}
</style>

	<div style="clear: both"></div>
<?php
if (isset($tpl['I_creditor'])) {
	foreach ($tpl['I_creditor'] as $data) {
	?>
			<div class="panel panel-default my_panel" style="width: 215px; float: left; margin-right:20px; margin-left:0px">
				<div class="panel-body my_panel_body">
					<div class="amount"><?php echo round($data['amount'], 1)?></div> <div class="dc_div"><span class="dc">d</span><span style="font-size:30px; font-weight:bold;"><?php echo $tpl['currency_list'][$data['currency_id']]?></span></div>
					<div class="credit"><?php echo $lng['credit_link']?></div>
					<?php echo ($data['currency_id']==1)?'<div class="rate tooltip" title=\''.$lng['rate_1_1_dwoc'].'\'><i class="fa fa-line-chart"></i></div>':'<div class="rate tooltip" title=\''.str_replace('[currency]', $tpl['currency_list'][$data['currency_id']], htmlspecialchars($lng['rate_1_1'])).'\'>1:1</div>' ?>
				</div>
			</div>
	<?php
	}
}
?>

	<div style="clear: both"></div>
	<!--<div class="row" style="padding:0 15px">
		<div class="alert alert-info">
			<?php echo $lng['dcoin_risks_alert']?>
		</div>
	</div>-->

	<div class="row">
		<div class="col-lg-4">
			<h3><?php echo $lng['panel']?> <a href="#" id="panel_refresh"><i class="fa fa-refresh fa-fw"></i></a></h3>
			<ul class="list-group">
				<li class="list-group-item" id="main_status"><?php echo $tpl['my_notice']['main_status']?></li>
				<li class="list-group-item"><?php echo $lng['account_status']?>: <span id="account_status"><?php echo $tpl['my_notice']['account_status'];?></span> <?php echo !empty($_SESSION['restricted'])?'restricted':'' ?> <?php echo defined('POOL_ADMIN')?'(Pool admin)':'' ?></li>
				<li class="list-group-item">User ID: <span id="user_id"><?php echo $user_id?></span></li>
				<li class="list-group-item"><?php echo $lng['inbox']?>: <span id="inbox">0</span></li>
				<li class="list-group-item"><?php echo $lng['number_of_blocks']?>: <span id="cur_block_id">0</span></li>
				<li class="list-group-item"><?php echo $lng['time_last_block']?>: <span id="time_last_block"><?php echo $tpl['my_notice']['time_last_block']?></span></li>
			</ul>
		</div>
		<?php
		if (!isset($_SESSION['restricted'])) {
		?>
		<!-- /.col-lg-4 -->
		<div class="col-lg-4">
			<h3><?php echo $lng['last_operation']?></h3>
			<div style="height: 328px; overflow: auto">
			<div class="table-responsive table-bordered">
			<table class="table" style="margin-bottom: 0px">
				<thead>
				<tr>
					<th></th>
					<th><?php echo $lng['amount']?></th>
					<th><?php echo $lng['note']?></th>
					<th><nobr><?php echo $lng['confirms']?> <span class="tooltip" title='<?php echo $lng['conf_text']?>'><a href="#"><i class="fa fa-question-circle" style="font-size: 18px"></i></a></span></nobr></th>
				</tr>
				</thead>
				<tbody>
				<?php
				if (isset($tpl['my_dc_transactions']))
				foreach ($tpl['my_dc_transactions'] as $data) {
					echo "<tr>";
					if ($data['to_user_id']==$user_id)
						echo '<td><i class="fa fa-plus"></i></td>';
					else
						echo '<td><i class="fa fa-minus"></i></td>';
					echo "<td>{$data['amount']} ".make_currency_name($data['currency_id'])."{$tpl['currency_list'][$data['currency_id']]}</td>";
					if ($data['comment_status']=='decrypted' && strlen($data['comment'])>150)
						echo "<td><div style=\"width: 200px; overflow: auto\">{$data['comment']}</div></td>";
					else if ($data['comment_status']=='decrypted')
						echo "<td>{$data['comment']}</td>";
					else
						echo "<td>Encrypted</td>";
					echo "<td>".($tpl['confirmed_block_id'] - $data['block_id'])."</td>";
				}
				?>
				</tbody>
			</table>
			</div>
			</div>
		</div>
		<!-- /.col-lg-4 -->
		<?php
		}
		?>
		<div class="col-lg-4">


			<h3><?php echo $lng['I_creditor']?></h3>
			<div style="height: 328px; overflow: auto">
				<div class="table-responsive table-bordered">
					<table class="table" style="margin-bottom: 0px">
						<?php
						echo '<tr><th>'.$lng['amount'].'</th><th>'.$lng['currency'].'</th><th>'.$lng['Debtor_User_ID'].'</th></tr>';
						if (isset($tpl['I_creditor']))
						foreach ($tpl['I_creditor'] as $data) {
							if ($data['from_user_id']==1)
								echo "<tr class='tooltip' title='{$lng['admin_debtor']}'>";
							else
								echo "<tr>";
							echo "<td>{$data['amount']}</td>";
							echo "<td>d{$tpl['currency_list'][$data['currency_id']]}</td>";
							if ($data['from_user_id']==1)
								echo '<td><strong>'.$data['from_user_id'].'</strong></a></td>';
							else
								echo "<td>{$data['from_user_id']}</td>";
						}
						?>
					</table>
				</div>
			</div>





		</div>
		<!-- /.col-lg-4 -->
	</div>
	<!-- /.row -->
	<!--
	<div class="row" style="margin-bottom: 50px">
		<div style="width:800px; overflow:auto; margin-left: 15px">
			<h3>CrowdFunding</h3>
			<?php
			if (isset($tpl['projects']))
			foreach ($tpl['projects'] as $project_id=>$data) {
			?>
			<div class="well project-card" style="float:left; margin-right:20px; background-color: #fff">
				<a href="#" onclick="fc_navigate('cf_page_preview', {'only_project_id':<?php echo $project_id?><?php echo $data['lang_id']?", 'lang_id':{$data['lang_id']}":""?>})"><img src="<?php echo $data['blurb_img']?>" style="width:200px; height:310px"></a>
				<div>
					<div class="card-location" style="margin-top:10px;font-size: 13px; color: #828587;"><i class="fa  fa-map-marker  fa-fw"></i> <?php echo "{$data['country']},{$data['city']}"?></div>
					<div class="progress" style="height:5px; margin-top:10px; margin-bottom:10px"><div class="progress-bar progress-bar-success" style="width: <?php echo $data['pct']?>%;"></div></div>
					<div class="card-bottom">
						<div style="float:left; overflow:auto; padding-right:10px"><h5><?php echo $data['pct']?>%</h5>funded</div>
						<div style="float:left; overflow:auto; padding-right:10px"><h5><?php echo $data['funding_amount']?> d<?php echo $tpl['currency_list'][$data['currency_id']]?> </h5>pledged</div>
						<div style="float:left; overflow:auto;"><h5><?php echo $data['days']?></h5>days to go</div>
					</div>
				</div>
			</div>
			<?php
			}
		?>
			<button type="button" class="btn btn-primary" data-toggle="button"  id="new_cf_project"><?php echo $lng['start_your_cf_project']?></button>
		</div>
	</div>
	-->



		<?php if (@$_SESSION['ADMIN']==1) {?>
			<br><br><br><br><button type="button" class="btn" data-toggle="button"  onclick="$.post('admin/content.php', { tpl_name: 'index', parameters: '' },
	              function(data) {
	              $('#dc_content').html( data );
	              }, 'html');" style="margin-left:30px">admin</button>
		<?php } ?>
	</div>

	<?php require_once( 'signatures.tpl' );?>

