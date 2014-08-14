
<h1 class="page-header"><?php echo $lng['mining']?></h1>

<div class="panel panel-primary">
	<div class="panel-heading">
		<?php echo $lng['how_to_mining_coins']?>
	</div>
	<div class="panel-body">
		<ul class="list-group" style="margin-bottom: 0px">

		<?php
		$inactive_arr = str_ireplace (array('[0]', '[1]', '[2]', 'upgrade', 'notifications', 'promised_amount_list', 'change_commission', 'tasks', 'voting', 'cash_requests_in', 'wallets_list'), '', $lng['mining_menu']['start']);
		$active_arr = str_ireplace (array('[0]', '[1]', '[2]'), array('<a href="#" onclick="fc_navigate(\'', '\')">', '</a>'), $lng['mining_menu']['start']);

			for ($i=0; $i<sizeof($active_arr); $i++) {
				echo '<li class="list-group-item">';
				if ($i < $tpl['mode'])
					echo '<i class="fa  fa-check-square-o  fa-lg"></i> '.$active_arr[$i];
				else if ($i>=4 && $tpl['mode']==4)
					echo $active_arr[$i];
				else if ($i == $tpl['mode'])
					echo '<strong>'.$active_arr[$i];
				else
					echo '<span style="color:#ccc">'.$inactive_arr[$i];
				if ($i < $tpl['mode'])
					echo '';
				else if ($i>=4 && $tpl['mode']==4)
					echo '';
				else if ($i == $tpl['mode'])
					echo '</strong>';
				else
					echo '</span>';
				echo '</li>';
			}
		?>

		</ul>
	</div>
</div>

<div  <?php echo $tpl['mode']==0?'style="display:none"':''?>>
<div class="row">
	<div class="col-lg-4">
		<div class="panel panel-success">
			<div class="panel-heading">
				<?php echo $lng['inbox']?>
			</div>
			<div class="panel-body">
				<p><?php echo $lng['mining_menu']['inbox_text']?></p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('cash_requests_in')"><?php echo $lng['goto']?></a>
			</div>
		</div>
	</div>
	<!-- /.col-lg-4 -->
	<div class="col-lg-4">
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
	<!-- /.col-lg-4 -->
	<div class="col-lg-4">
		<div class="panel panel-success">
			<div class="panel-heading">
				<?php echo $lng['voting']?>
			</div>
			<div class="panel-body">
				<p><?php echo $lng['mining_menu']['voting_text']?></p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('voting')"><?php echo $lng['goto']?></a>
			</div>
		</div>
	</div>
	<!-- /.col-lg-4 -->
</div>
<!-- /.row -->
<div class="row">
	<div class="col-lg-4">
		<div class="panel panel-info">
			<div class="panel-heading">
				<?php echo $lng['reg_users']?>
			</div>
			<div class="panel-body">
				<p><?php echo $lng['mining_menu']['reg_users_text']?></p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('new_user')"><?php echo $lng['goto']?></a>
			</div>
		</div>
	</div>
	<!-- /.col-lg-4 -->
	<div class="col-lg-4">
		<div class="panel panel-info">
			<div class="panel-heading">
				<?php echo $lng['promised_amounts'] ?>
			</div>
			<div class="panel-body">
				<p><?php echo $lng['mining_menu']['promised_amounts_text']?></p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('promised_amount_list')"><?php echo $lng['goto']?></a>
			</div>
		</div>
	</div>
	<!-- /.col-lg-4 -->
	<div class="col-lg-4">
		<div class="panel panel-info">
			<div class="panel-heading">
				<?php echo $lng['outgoing']?>
			</div>
			<div class="panel-body">
				<p><?php echo $lng['mining_menu']['outgoing_text']?></p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('cash_requests_out')"><?php echo $lng['goto']?></a>
			</div>
		</div>
	</div>
	<!-- /.col-lg-4 -->
</div>
<!-- /.row -->
<div class="row">
	<div class="col-lg-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<?php echo $lng['points']?>
			</div>
			<div class="panel-body">
				<p><?php echo $lng['mining_menu']['points_text']?></p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('points')"><?php echo $lng['goto']?></a>
			</div>
		</div>
	</div>
	<div class="col-lg-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<?php echo $lng['commission']?>
			</div>
			<div class="panel-body">
				<p><?php echo $lng['mining_menu']['commission_text']?></p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('change_commission')"><?php echo $lng['goto']?></a>
			</div>
		</div>
	</div>
	<div class="col-lg-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<?php echo $lng['holidays']?>
			</div>
			<div class="panel-body">
				<p><?php echo $lng['mining_menu']['holidays_text']?></p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('holidays_list')"><?php echo $lng['goto']?></a>
			</div>
		</div>
	</div>
	<div class="col-lg-4">
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
</div>
<!-- /.row -->
</div>