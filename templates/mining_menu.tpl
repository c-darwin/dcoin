<script>
	$(document).ready(function() {
		$( "#progress_bar" ).load( "ajax/progress_bar.php");
	});
</script>
<h1 class="page-header"><?php echo $lng['mining']?></h1>

<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
<div>

	<!-- Nav tabs -->
	<ul class="nav nav-tabs" style="margin-bottom: 20px">
		<li class="active"><a aria-expanded="true" href="#tab1" data-toggle="tab"><?php echo $lng['general']?></a>
		</li>
		<li class=""><a aria-expanded="false" href="#tab2" data-toggle="tab"><?php echo $lng['assignments']?></a>
		</li>
		<li class=""><a aria-expanded="false" href="#tab3" data-toggle="tab"><?php echo $lng['additionally']?></a>
		</li>
	</ul>

	<!-- Tab panes -->
	<div class="tab-content">
		<div class="tab-pane fade active in" id="tab1">
			<div class="col-lg-3">
				<div class="panel panel-info">
					<div class="panel-heading">
						<?php echo $lng['promised_amounts'] ?>
					</div>
					<div class="panel-body">
						<p><?php echo $lng['mining_menu']['promised_amounts_text']?></p>
					</div>
					<div class="panel-footer">
						<a href="#promised_amount_list"><?php echo $lng['goto']?></a>
					</div>
				</div>
			</div>

			<div class="col-lg-3">
				<div class="panel panel-info">
					<div class="panel-heading">
						<?php echo $lng['outgoing']?>
					</div>
					<div class="panel-body">
						<p><?php echo $lng['mining_menu']['outgoing_text']?></p>
					</div>
					<div class="panel-footer">
						<a href="#" onclick="map_navigate('cash_requests_out')"><?php echo $lng['goto']?></a>
					</div>
				</div>
			</div>

			<div class="col-lg-3">
				<div class="panel panel-info">
					<div class="panel-heading">
						<?php echo $lng['vote_for_me'] ?>
					</div>
					<div class="panel-body">
						<p><?php echo $lng['mining_menu']['vote_for_me_text']?></p>
					</div>
					<div class="panel-footer">
						<a href="#vote_for_me"><?php echo $lng['goto']?></a>
					</div>
				</div>
			</div>


		</div>
		<div class="tab-pane fade" id="tab2">

			<div class="col-lg-3">
				<div class="panel panel-success">
					<div class="panel-heading">
						<?php echo $lng['points']?>
					</div>
					<div class="panel-body">
						<p><?php echo $lng['mining_menu']['points_text']?></p>
					</div>
					<div class="panel-footer">
						<a href="#points"><?php echo $lng['goto']?></a>
					</div>
				</div>
			</div>


			<div class="col-lg-3">
				<div class="panel panel-success">
					<div class="panel-heading">
						<?php echo $lng['inbox']?>
					</div>
					<div class="panel-body">
						<p><?php echo $lng['mining_menu']['inbox_text']?></p>
					</div>
					<div class="panel-footer">
						<a href="#cash_requests_in"><?php echo $lng['goto']?></a>
					</div>
				</div>
			</div>



			<div class="col-lg-3">
				<div class="panel panel-success">
					<div class="panel-heading">
						<?php echo $lng['tasks']?>
					</div>
					<div class="panel-body">
						<p><?php echo $lng['mining_menu']['tasks_text']?></p>
					</div>
					<div class="panel-footer">
						<a href="#" onclick="map_navigate('tasks')"><?php echo $lng['goto']?></a>
					</div>
				</div>
			</div>


			<div class="col-lg-3">
				<div class="panel panel-success">
					<div class="panel-heading">
						<?php echo $lng['voting']?>
					</div>
					<div class="panel-body">
						<p><?php echo $lng['mining_menu']['voting_text']?></p>
					</div>
					<div class="panel-footer">
						<a href="#voting"><?php echo $lng['goto']?></a>
					</div>
				</div>
			</div>





		</div>
		<div class="tab-pane fade" id="tab3">


			<div class="col-lg-3">
				<div class="panel panel-default">
					<div class="panel-heading">
						<?php echo $lng['reg_users']?>
					</div>
					<div class="panel-body">
						<p><?php echo $lng['mining_menu']['reg_users_text']?></p>
					</div>
					<div class="panel-footer">
						<a href="#new_user"><?php echo $lng['goto']?></a>
					</div>
				</div>
			</div>

			<div class="col-lg-3">
				<div class="panel panel-default">
					<div class="panel-heading">
						<?php echo $lng['commission']?>
					</div>
					<div class="panel-body">
						<p><?php echo $lng['mining_menu']['commission_text']?></p>
					</div>
					<div class="panel-footer">
						<a href="#change_commission"><?php echo $lng['goto']?></a>
					</div>
				</div>
			</div>

			<div class="col-lg-3">
				<div class="panel panel-default">
					<div class="panel-heading">
						<?php echo $lng['holidays']?>
					</div>
					<div class="panel-body">
						<p><?php echo $lng['mining_menu']['holidays_text']?></p>
					</div>
					<div class="panel-footer">
						<a href="#holidays_list"><?php echo $lng['goto']?></a>
					</div>
				</div>
			</div>
			<div class="col-lg-3">
				<div class="panel panel-default">
					<div class="panel-heading">
						<?php echo $lng['geolocation'] ?>
					</div>
					<div class="panel-body">
						<p><?php echo $lng['mining_menu']['geolocation_text']?></p>
					</div>
					<div class="panel-footer">
						<a href="#" onclick="map_navigate ('geolocation')"><?php echo $lng['goto']?></a>
					</div>
				</div>
			</div>
			<div class="col-lg-3">
				<div class="panel panel-default">
					<div class="panel-heading">
						<?php echo $lng['sms_and_email_notifications']?>
					</div>
					<div class="panel-body">
						<p><?php echo $lng['sms_and_email_notifications']?></p>
					</div>
					<div class="panel-footer">
						<a href="#" onclick="map_navigate ('notifications')"><?php echo $lng['goto']?></a>
					</div>
				</div>
			</div>

		</div>
	</div>


</div>

<div class="clearfix"></div>
<?php echo $tpl['last_tx_formatted']?>
<script src="js/unixtime.js"></script>

