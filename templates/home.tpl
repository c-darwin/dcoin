<script>

	function my_notice() {
		$.post('ajax/my_notice.php', function(data) {
			//poll_time = Date.now();
			//i++;

			$('#main_status').html(data.main_status);
			if (data.main_status_complete!=1)
				$('#main_status').css("class", "list-group-item list-group-item-danger" );
			else
				$('#main_status').css("class", "list-group-item" );

			$('#account_status').text(data.account_status);
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
</script>
<style>
	.alert-info a:link{text-decoration: underline};
</style>
<link href="css2/cf.css" rel="stylesheet">
<h1 class="page-header">Home</h1>

<div id="message"></div>
	
<div id="generate">
	<div class="row" style="padding:0 15px">
		<div class="alert alert-info">
			<?php echo $lng['dcoin_risks_alert']?>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-4">
			<h3><?php echo $lng['panel']?> <a href="#" id="panel_refresh"><i class="fa fa-refresh fa-fw"></i></a></h3>
			<ul class="list-group">
				<li class="list-group-item" id="main_status"><?php echo $tpl['my_notice']['main_status']?></li>
				<li class="list-group-item"><?php echo $lng['account_status']?>: <span id="account_status"><?php echo $tpl['my_notice']['account_status'];?></span> <?php echo !empty($_SESSION['restricted'])?'restricted':'' ?> <?php echo defined('POOL_ADMIN')?'(Pool admin)':'' ?></li>
				<li class="list-group-item">User ID: <span id="user_id"><?php echo $user_id?></span></li>
				<li class="list-group-item"><?php echo $lng['inbox']?>: <span id="inbox"><?php echo $tpl['my_notice']['inbox']?></span></li>
				<li class="list-group-item"><?php echo $lng['status_daemons']?>: <?php echo $tpl['demons_status']?></li>
				<li class="list-group-item"><?php echo $lng['number_of_blocks']?>: <span id="cur_block_id"><?php echo $tpl['my_notice']['number_of_blocks']?></span></li>
				<li class="list-group-item"><?php echo $lng['time_last_block']?>: <span id="time_last_block"><?php echo $tpl['my_notice']['time_last_block']?></span></li>
				<li class="list-group-item"><?php echo $lng['connections']?>: <span id="connections"><?php echo $tpl['my_notice']['connections']?></span></li>
			</ul>
		</div>
		<?php
		if (!$_SESSION['restricted']) {
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
					<th><?php echo $lng['confirms']?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				if ($tpl['my_dc_transactions'])
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
					echo "<td>".($tpl['block_id'] - $data['block_id'])."</td>";
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

			<h3><?php echo $lng['balances']?></h3>
			<div style="height: 328px; overflow: auto">
				<div class="table-responsive table-bordered">
					<?php
						echo '<table class="table" style="margin-bottom: 0px">';
						if ($tpl['wallets']) {
							echo '<thead><tr><th>'.$lng['currency'].'</th><th>'.$lng['amount'].'</th><th>'.$lng['pct_year'].'</th></tr></thead>';
							foreach ($tpl['wallets'] as $id => $data) {
								echo "<tr>";
								if ($data['currency_id']>=1000)
									echo "<td><a href=\"#\" onclick=\"fc_navigate('cf_page_preview', {'only_cf_currency_name':'{$tpl['currency_list'][$data['currency_id']]}'})\">{$tpl['currency_list'][$data['currency_id']]}</a></td>";
								else
									echo "<td>D{$tpl['currency_list'][$data['currency_id']]}</td>";

								echo "<td>{$data['amount']}</td>";
								echo "<td>{$data['pct']}</td></tr>";
							}
						}
						else {
							echo "<tr><td colspan='3'>{$lng['no_coins']} {$lng['where_get_dc_text']}</td></tr>";
						}

						echo '</table>';
					?>
				</div>
			</div>


		</div>
		<!-- /.col-lg-4 -->
	</div>
	<!-- /.row -->
	<div class="row">
		<div style="width:800px; overflow:auto; margin-left: 15px">
			<h3>CrowdFunding</h3>
			<?php
			if ($tpl['projects'])
			foreach ($tpl['projects'] as $project_id=>$data) {
			?>
			<div class="well project-card" style="float:left; margin-right:20px; background-color: #fff">
				<a href="#" onclick="fc_navigate('cf_page_preview', {'only_project_id':<?php echo $project_id?><?php echo $data['lang_id']?", 'lang_id':{$data['lang_id']}":""?>})"><img src="<?php echo $data['blurb_img']?>" style="width:200px; height:310px"></a>
				<div>
					<div class="card-location" style="margin-top:10px;font-size: 13px; color: #828587;"><i class="fa  fa-map-marker  fa-fw"></i> <?php echo "{$data['country']},{$data['city']}"?></div>
					<div class="progress" style="height:5px; margin-top:10px; margin-bottom:10px"><div class="progress-bar progress-bar-success" style="width: <?php echo $data['pct']?>%;"></div></div>
					<div class="card-bottom">
						<div style="float:left; overflow:auto; padding-right:10px"><h5><?php echo $data['pct']?>%</h5>funded</div>
						<div style="float:left; overflow:auto; padding-right:10px"><h5><?php echo $data['funding_amount']?> D<?php echo $tpl['currency_list'][$data['currency_id']]?> </h5>pledged</div>
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



		<?php if (@$_SESSION['ADMIN']==1) {?>
			<br><br><br><br><button type="button" class="btn" data-toggle="button"  onclick="$.post('admin/content.php', { tpl_name: 'index', parameters: '' },
	              function(data) {
	              $('#dc_content').html( data );
	              }, 'html');" style="margin-left:30px">admin</button>
		<?php } ?>
	</div>

	<?php require_once( 'signatures.tpl' );?>

