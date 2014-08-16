<?php
define( 'DC', true );
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'includes/class.phpmailer.php');
require_once( ABSPATH . 'includes/class.smtp.php');

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

// валюты
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `id`,
					 `name`
		FROM `'.DB_PREFIX.'currency`
		ORDER BY `name`
		');
while ($row = $db->fetchArray($res))
	$currency_list[$row['id']] = $row['name'];

$notifications_array = array();
$user_email_sms_data = array();

$my_users_ids = get_community_users($db);
if (!$my_users_ids) {
	$community = false; // сингл-мод
	$my_users_ids[0] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `user_id`
			FROM `".DB_PREFIX."my_table`
			",	'fetch_one');
}
else
	$community = true;

if ($my_users_ids) {

	for ($i=0; $i<sizeof($my_users_ids); $i++) {

		if ($community)
			$my_prefix = $my_users_ids[$i].'_';
		else
			$my_prefix = '';

		$my_data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT * FROM `".DB_PREFIX."{$my_prefix}my_table`
				",	'fetch_array');
		$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."{$my_prefix}my_notifications`
				");
		while ($row = $db->fetchArray($res)) {
			$notifications_array[$row['name']][$my_users_ids[$i]] = array('email'=>$row['email'], 'sms'=>$row['sms']);
			$user_email_sms_data[$my_users_ids[$i]] = $my_data;
		}
	}
}

$subj = "From DCoin server";

foreach($notifications_array as $name => $notification_info) {

	switch ($name) {

		case 'admin_messages':

			$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`,
								  `message`
					FROM `".DB_PREFIX."alert_messages`
					WHERE `notification` = 0
					", 'fetch_array');

			if ($data) {

				foreach($notification_info as $user_id => $email_sms) {

					$my_data = $user_email_sms_data[$user_id];
					$my_data['text'] = "From Admin: {$data['message']}";
					$my_data['subj'] = $subj;

					if ($email_sms['email'])
						send_mail($my_data);
					if ($email_sms['sms'])
						send_sms($my_data['sms_http_get_request'], $my_data['text']);
				}

				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."alert_messages`
						SET `notification` = 1
						WHERE `id` = {$data['id']}
						");
			}

			break;

		case 'incoming_cash_requests':

			for ($i=0; $i<sizeof($my_users_ids); $i++) {

				if ($community)
					$my_prefix = $my_users_ids[$i].'_';
				else
					$my_prefix = '';

				$user_id = $my_users_ids[$i];

				$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							SELECT `id`,
										 `amount`,
										 `currency_id`
							FROM `".DB_PREFIX."{$my_prefix}my_cash_requests`
							WHERE `to_user_id` = {$user_id} AND
										 `notification` = 0 AND
										 `status` = 'pending'
							", 'fetch_array');
				if ($data) {

					$my_data = $user_email_sms_data[$user_id];
					$my_data['text'] = "Cash request: {$data['amount']}{$currency_list[$data['currency_id']]}";
					$my_data['subj'] = $subj;
					if ($notifications_array[$name][$user_id]['email'])
						send_mail($my_data);
					if ($notifications_array[$name][$user_id]['sms'])
						send_sms($my_data['sms_http_get_request'], $my_data['text']);

					$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."{$my_prefix}my_cash_requests`
							SET `notification` = 1
							WHERE `id` = {$data['id']}
							");
				}
			}

			break;

		// смена статуса юзера
		case 'change_in_status':

			for ($i=0; $i<sizeof($my_users_ids); $i++) {

				if ($community)
					$my_prefix = $my_users_ids[$i].'_';
				else
					$my_prefix = '';

				$user_id = $my_users_ids[$i];

				$status = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `status`
						FROM `".DB_PREFIX."{$my_prefix}my_table`
						WHERE `notification_status` = 0
						", 'fetch_one');
				if ($status) {

					$my_data = $user_email_sms_data[$user_id];
					$my_data['text'] = "New status: {$status}";
					$my_data['subj'] = $subj;
					if ($notifications_array[$name][$user_id]['email'])
						send_mail($my_data);
					if ($notifications_array[$name][$user_id]['sms'])
						send_sms($my_data['sms_http_get_request'], $my_data['text']);

					$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."{$my_prefix}my_table`
						SET `notification_status` = 1
					");
				}
			}

			break;

		// Поступление средств
		case 'fc_came_from':

			for ($i=0; $i<sizeof($my_users_ids); $i++) {

				if ($community)
					$my_prefix = $my_users_ids[$i].'_';
				else
					$my_prefix = '';

				$user_id = $my_users_ids[$i];

				$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `id`,
									  `amount`,
									 `currency_id`
						FROM `".DB_PREFIX."{$my_prefix}my_dc_transactions`
						WHERE `to_user_id` = {$user_id} AND
									 `notification` = 0 AND
									 `status` = 'approved'
						");
				while ($row = $db->fetchArray($res)) {

					$my_data = $user_email_sms_data[$user_id];
					$my_data['text'] = "New DC: {$row['amount']} D{$currency_list[$row['currency_id']]}";
					$my_data['subj'] = $subj;
					if ($notifications_array[$name][$user_id]['email'])
						send_mail($my_data);
					if ($notifications_array[$name][$user_id]['sms'])
						send_sms($my_data['sms_http_get_request'], $my_data['text']);

					$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."{$my_prefix}my_dc_transactions`
							SET `notification` = 1
							WHERE `id` = {$row['id']}
							");
				}
			}

			break;

		// списание средств
		case 'fc_sent':

			for ($i=0; $i<sizeof($my_users_ids); $i++) {

				if ($community)
					$my_prefix = $my_users_ids[$i].'_';
				else
					$my_prefix = '';

				$user_id = $my_users_ids[$i];

				$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `id`,
									  `amount`,
									 `currency_id`
						FROM `".DB_PREFIX."{$my_prefix}my_dc_transactions`
						WHERE `to_user_id` !=  {$user_id} AND
									 `notification` = 0 AND
									 `status` = 'approved'
						");
				while ($row = $db->fetchArray($res)) {

					$my_data = $user_email_sms_data[$user_id];
					$my_data['text'] = "Sending DC: {$row['amount']} D{$currency_list[$row['currency_id']]}";
					$my_data['subj'] = $subj;
					if ($notifications_array[$name][$user_id]['email'])
						send_mail($my_data);
					if ($notifications_array[$name][$user_id]['sms'])
						send_sms($my_data['sms_http_get_request'], $my_data['text']);

					$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."{$my_prefix}my_dc_transactions`
							SET `notification` = 1
							WHERE `id` = {$row['id']}
							");
				}
			}

			break;

		case 'update_primary_key':

			for ($i=0; $i<sizeof($my_users_ids); $i++) {

				if ($community)
					$my_prefix = $my_users_ids[$i].'_';
				else
					$my_prefix = '';

				$user_id = $my_users_ids[$i];

				$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `id`
						FROM `".DB_PREFIX."{$my_prefix}my_keys`
						WHERE `notification` = 0 AND
									 `status` = 'approved'
						", 'fetch_array');
				if ($data) {

					$my_data = $user_email_sms_data[$user_id];
					$my_data['text'] = "Update primary key";
					$my_data['subj'] = $subj;
					if ($notifications_array[$name][$user_id]['email'])
						send_mail($my_data);
					if ($notifications_array[$name][$user_id]['sms'])
						send_sms($my_data['sms_http_get_request'], $my_data['text']);

					$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."{$my_prefix}my_keys`
							SET `notification` = 1
							WHERE `id` = {$data['id']}
							");
				}
			}

			break;

		case 'update_email':

			for ($i=0; $i<sizeof($my_users_ids); $i++) {

				if ($community)
					$my_prefix = $my_users_ids[$i].'_';
				else
					$my_prefix = '';

				$user_id = $my_users_ids[$i];

				$my_new_email = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `status`
						FROM `".DB_PREFIX."{$my_prefix}my_table`
						WHERE `notification_email` = 0
						", 'fetch_one');
				if ($my_new_email) {

					$my_data = $user_email_sms_data[$user_id];
					$my_data['text'] = "New email: {$my_new_email}";
					$my_data['subj'] = $subj;
					if ($notifications_array[$name][$user_id]['email'])
						send_mail($my_data);
					if ($notifications_array[$name][$user_id]['sms'])
						send_sms($my_data['sms_http_get_request'], $my_data['text']);

					$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."{$my_prefix}my_table`
							SET `notification_email` = 1
					");
				}
			}

			break;

		case 'update_sms_request':

			for ($i=0; $i<sizeof($my_users_ids); $i++) {

				if ($community)
					$my_prefix = $my_users_ids[$i].'_';
				else
					$my_prefix = '';

				$user_id = $my_users_ids[$i];

				$sms_http_get_request = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `sms_http_get_request`
						FROM `".DB_PREFIX."{$my_prefix}my_table`
						WHERE `notification_sms_http_get_request` = 0
						", 'fetch_one');
				if ($sms_http_get_request) {

					$my_data = $user_email_sms_data[$user_id];
					$my_data['text'] = "New sms_http_get_request: {$sms_http_get_request}";
					$my_data['subj'] = $subj;
					if ($notifications_array[$name][$user_id]['email'])
						send_mail($my_data);
					if ($notifications_array[$name][$user_id]['sms'])
						send_sms($my_data['sms_http_get_request'], $my_data['text']);

					$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."{$my_prefix}my_table`
							SET `notification_sms_http_get_request` = 1
					");
				}
			}

			break;

		// новые %
		case 'voting_results':

			$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`,
								 `currency_id`,
								 `miner`,
								 `user`
					FROM `".DB_PREFIX."pct`
					WHERE `notification` = 0
					");
			$text = '';
			while ($data = $db->fetchArray($res)) {
				$text .= "New pct {$currency_list[$data['currency_id']]}! miners: ".((pow(1+$data['miner'], 3600*24*365)-1)*100)."%/year, users: ".((pow(1+$data['user'], 3600*24*365)-1)*100)."%/year";
			}

			if ($text) {

				foreach($notification_info as $user_id => $email_sms) {

					$my_data = $user_email_sms_data[$user_id];
					$my_data['text'] = $text;
					$my_data['subj'] = $subj;

					if ($email_sms['email'])
						send_mail($my_data);
					if ($email_sms['sms'])
						send_sms($my_data['sms_http_get_request'], $my_data['text']);
				}

				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."pct`
						SET `notification` = 1
						WHERE `notification` = 0
						");
			}

			break;

		// Прошло 2 недели с момента Вашего голосования за %
		case 'voting_time':

			for ($i=0; $i<sizeof($my_users_ids); $i++) {

				if ($community)
					$my_prefix = $my_users_ids[$i].'_';
				else
					$my_prefix = '';

				$user_id = $my_users_ids[$i];

				$last_voting = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `last_voting`
						FROM `".DB_PREFIX."{$my_prefix}my_complex_votes`
						WHERE `notification` = 0
						", 'fetch_one');
				if ( $last_voting && (time()-$last_voting) > 3600*24*14 ) {

					$my_data = $user_email_sms_data[$user_id];
					$my_data['text'] = "Time for voting";
					$my_data['subj'] = $subj;
					if ($notifications_array[$name][$user_id]['email'])
						send_mail($my_data);
					if ($notifications_array[$name][$user_id]['sms'])
						send_sms($my_data['sms_http_get_request'], $my_data['text']);

					$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."{$my_prefix}my_complex_votes`
							SET `notification` = 1
							");
				}
			}

			break;

		// Необходимость обновления FC-движка
		case 'new_version':

			$new_version = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `version`
					FROM `".DB_PREFIX."new_version`
					WHERE `notification` = 0 AND
								 `alert` = 1
					LIMIT 1
					", 'fetch_one');

			if ($new_version) {

				foreach($notification_info as $user_id => $email_sms) {

					$my_data = $user_email_sms_data[$user_id];
					$my_data['text'] = "New version: {$new_version}";
					$my_data['subj'] = $subj;

					if ($email_sms['email'])
						send_mail($my_data);
					if ($email_sms['sms'])
						send_sms($my_data['sms_http_get_request'], $my_data['text']);
				}

				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."new_version`
						SET `notification` = 1
						WHERE `version` = '{$new_version}'
						");
			}

			break;

		// Расхождение времени сервера более чем на 5 сек
		case 'node_time':

			// если работаем в режиме пула, то нужно слать инфу админу пула
			if ($community) {
				$pool_admin_user_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `pool_admin_user_id`
						FROM `".DB_PREFIX."config`
						", 'fetch_one' );
				$admin_user_id = $pool_admin_user_id;
			}
			else {
				// проверим, нода ли мы
				$my_table = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT *
						FROM `".DB_PREFIX."my_table`
						", 'fetch_array' );
				if (!$my_table['miner_id'])
					break;
				$admin_user_id = $my_table['user_id'];
			}

			$email_sms = $notification_info[$admin_user_id];
			$my_data = $user_email_sms_data[$admin_user_id];
			$my_data['subj'] = $subj;

			if ($my_data) {

				$my_data['subj'] = $subj;

				$t = ntp_time();
				if ($t>5 || !is_int($t)) {
					if (is_int($t))
						$my_data['text'] = "Divergence time {$t} sec";
					else
						$my_data['text'] = "Time error: {$t}";

					if ($email_sms['email'])
						send_mail($my_data);
					if ($email_sms['sms'])
						send_sms($my_data['sms_http_get_request'], $my_data['text']);
				}
			}

			break;
	}
}


?>

