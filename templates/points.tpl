<h1 class="page-header"><?php echo $lng['points']?></h1>
<ol class="breadcrumb">
	<li><a href="#" onclick="fc_navigate('mining_menu')"><?php echo $lng['mining'] ?></a></li>
	<li class="active"><?php echo $lng['points'] ?></li>
</ol>

<?php echo ($tpl['alert'])?'<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>'.$tpl['alert'].'</div>':''?>

<?php echo $lng['points_min']?>:<br>
<strong><?php echo $tpl['mean']?></strong><br>
<?php echo $lng['points_yours']?>: <br>
<strong><?php echo $tpl['my_points']?></strong><br>
<?php echo $lng['points_votes']?>: <br><strong><?php echo $tpl['votes_ok']?></strong><br>

<?php
	if (isset($tpl['points_status'])) {
		echo '<table class="table table-bordered" style="width:500px"><caption><h3>'.$lng['points_status'].'</h3></caption>';
		echo '<thead><tr><th>'.$lng['start_time'].'</th><th>'.$lng['status'].'</th></tr></thead>';
		echo '<tbody>';
		foreach ($tpl['points_status'] as $data) {
			echo "
				<tr>
				<td>".date('d-m-Y H:i:s', $data['time_start'])."</td>
				<td>{$data['status']}</td>
				</tr>
				";
			}
			echo '</tbody>';
			echo '</table>';
echo $lng['points_votes_period'];
	}

?>
