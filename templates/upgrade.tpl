
<!-- container -->
<div class="container">

	<legend><h2><?php echo $lng['upgrade_title']?></h2></legend>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

	<p><?php echo $lng['status']?>:
	<?php
	//print $tpl['result'];
	switch ($tpl['result']) {
	case 'ok':

		print $lng['upgrade_status_ok'];
		break;

	case 'bad':

		print $lng['upgrade_status_bad'];
		break;

	case 'nodes_pending':
	case 'users_pending':
	case 'pending':

		print $lng['upgrade_status_pending'];
		break;

	case 'bad_photos_hash':

		print "{$lng['bad_photo']}{$tpl['host']}/user_face.jpg and {$tpl['host']}/user_profile.jpg<br><button class=\"btn\" onclick=\"fc_navigate('upgrade_resend')\">{$lng['send_repeated_request']}</button><br><br>";
		break;

	case 'resend':

		print $lng['error_send_repeated_request'];
		break;

	case 'null':

		print $lng['request_is_not_sent'];
		break;

	}
	?>
	</p>
	<br>

	<?php
	if ($tpl['result']!='ok') {
		?>
		<button class="btn btn-success" onclick="fc_navigate('upgrade_0')"><?php echo $lng['begin_upgrade']?></button>
		<p><?php echo $lng['attempts_remaining']?><?php echo $tpl['miner_votes_attempt']?></p>
	<p><?php echo $lng['purpose_of_inspections']?><br>

	<?php
	}
	?>

	<!--<button class="btn btn-success" onclick="fc_navigate('upgrade_0')"><?php echo $lng['begin_upgrade']?></button>-->

</div>
<!-- /container -->