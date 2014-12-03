<h1 class="page-header"><?php echo $lng['upgrade_title']?></h1>
<ol class="breadcrumb">
	<li><a href="#mining_menu"><?php echo $lng['mining'] ?></a></li>
	<li class="active"><?php echo $lng['upgrade_title'] ?></li>
</ol>

<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

<?php
	//print $tpl['result'];
	switch ($tpl['result']) {
	case 'ok':

		print '<div class="alert alert-success">'.$lng['upgrade_status_ok'].'</div>';
		break;

	case 'bad':

		print '<div class="alert alert-danger">'.$lng['upgrade_status_bad'].'</div>';
		break;

	case 'nodes_pending':
	case 'users_pending':
	case 'pending':

		print '<div class="alert alert-info">'.$lng['upgrade_status_pending'].'</div>';
		break;

	case 'bad_photos_hash':

		print '<div class="alert alert-danger">'."{$lng['bad_photo']}{$tpl['host']}/user_face.jpg and {$tpl['host']}/user_profile.jpg<br><a type='button' class='btn btn-default' href='#upgrade_resend'>{$lng['send_repeated_request']}</a></div>";
		break;

	case 'resend':

		print '<div class="alert alert-warning">'.$lng['error_send_repeated_request'].'</div>';
		break;

	}
	?>
	<br>

	<?php
	if (!in_array($tpl['result'], array('ok', 'pending', 'users_pending', 'nodes_pending'))) {
		?>
		<button class="btn btn-success" onclick="fc_navigate('upgrade_0')"><?php echo $lng['begin_upgrade']?></button>
		<p><?php echo $lng['attempts_remaining']?> <?php echo $tpl['miner_votes_attempt']?></p>
	<p><?php echo $lng['purpose_of_inspections']?><br>

	<?php
	}
	?>


	<?php
	if ($tpl['my_comments']) {
		echo '<h3>'.$lng['comment_from_miners'].'</h3>';
		echo '<table class="table table-bordered" style="width:600px">';
		echo '<tbody>';
		foreach ( $tpl['my_comments'] as $data ) {
			print "<tr><td>{$data['comment']}</td></tr>";
		}
		echo '</tbody>';
		echo '</table>';
	}
	?>