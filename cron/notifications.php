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

$my_data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, 'SELECT * FROM `'.DB_PREFIX.'my_table`', 'fetch_array' );
$my_data['subj'] = "From DCoin server";

$res0 = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."my_notifications`
		");
while ($row0 = $db->fetchArray($res0)) {

	$sms = $row0['sms'];
	$email = $row0['email'];

	switch ($row0['name']) {

		case 'admin_messages':

			$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`,
								  `message`
					FROM `".DB_PREFIX."alert_messages`
					WHERE `notification` = 0
					", 'fetch_array');
			$my_data['text'] = "From Admin: {$data['message']}";
			if ($data && $email)
				send_mail($my_data);
			if ($data && $sms)
				send_sms($my_data['sms_http_get_request'], $my_data['text']);

			if ($data)
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."alert_messages`
						SET `notification` = 1
						WHERE `id` = {$data['id']}
						");

			break;

		case 'incoming_cash_requests':

			$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`,
								 `amount`,
								 `currency_id`
					FROM `".DB_PREFIX."my_cash_requests`
					WHERE `to_user_id` = {$my_data['user_id']} AND
								 `notification` = 0 AND
								 `status` = 'pending'
					", 'fetch_array');
			if ($data) {
				$my_data['text'] = "Cash request: {$data['amount']}{$currency_list[$data['currency_id']]}";
				if ($data && $email)
					send_mail($my_data);
				if ($data && $sms)
					send_sms($my_data['sms_http_get_request'], $my_data['text']);

				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."my_cash_requests`
						SET `notification` = 1
						WHERE `id` = {$data['id']}
						");
			}
			break;


		// смена статуса юзера
		case 'change_in_status':

			$status = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `status`
					FROM `".DB_PREFIX."my_table`
					WHERE `notification_status` = 0
					", 'fetch_one');
			$my_data['text'] = "New status: {$status}";
			if ($status && $email)
				send_mail($my_data);
			if ($status && $sms)
				send_sms($my_data['sms_http_get_request'], $my_data['text'] );

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."my_table`
					SET `notification_status` = 1
					");

			break;

		// Поступление средств
		case 'fc_came_from':

			$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`,
								  `amount`,
								 `currency_id`
					FROM `".DB_PREFIX."my_dc_transactions`
					WHERE `to_user_id` = {$my_data['user_id']} AND
								 `notification` = 0 AND
								 `status` = 'approved'
					");
			while ($row = $db->fetchArray($res)) {

				$my_data['text'] = "New DC: {$row['amount']} D{$currency_list[$row['currency_id']]}";
				if ($email)
					send_mail($my_data);
				if ($sms)
					send_sms($my_data['sms_http_get_request'], $my_data['text']);

				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."my_dc_transactions`
						SET `notification` = 1
						WHERE `id` = {$row['id']}
						");
			}

			break;

		// списание средств
		case 'fc_sent':
			$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`,
								  `amount`,
								 `currency_id`
					FROM `".DB_PREFIX."my_dc_transactions`
					WHERE `to_user_id` != {$my_data['user_id']} AND
								 `notification` = 0 AND
								 `status` = 'approved'
					");
			while ($row = $db->fetchArray($res)) {

				$my_data['text'] = "Sending DC: {$row['amount']} D{$currency_list[$row['currency_id']]}";
				if ($email)
					send_mail($my_data);
				if ($sms)
					send_sms($my_data['sms_http_get_request'], $my_data['text']);

				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."my_dc_transactions`
						SET `notification` = 1
						WHERE `id` = {$row['id']}
						");
			}
			break;

		case 'update_primary_key':

			$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`
					FROM `".DB_PREFIX."my_keys`
					WHERE `notification` = 0 AND
								 `status` = 'approved'
					", 'fetch_array');
			$my_data['text'] = "Update primary key";
			if ($data && $email)
				send_mail($my_data);
			if ($data && $sms)
				send_sms($my_data['sms_http_get_request'], $my_data['text']);

			if ($data)
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."my_keys`
						SET `notification` = 1
						WHERE `id` = {$data['id']}
						");

			break;


		case 'update_email':

			$my_new_email = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `status`
					FROM `".DB_PREFIX."my_table`
					WHERE `notification_email` = 0
					", 'fetch_one');
			$my_data['text'] = "New email: {$my_new_email}";
			if ($my_new_email && $email)
				send_mail($my_data);
			if ($my_new_email && $sms)
				send_sms($my_data['sms_http_get_request'], $my_data['text'] );

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."my_table`
					SET `notification_email` = 1
					");

			break;

		case 'update_sms_request':

			$sms_http_get_request = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `sms_http_get_request`
					FROM `".DB_PREFIX."my_table`
					WHERE `notification_sms_http_get_request` = 0
					", 'fetch_one');
			$my_data['text'] = "New email: {$email}";
			if ($sms_http_get_request && $email)
				send_mail($my_data);
			if ($sms_http_get_request && $sms)
				send_sms($my_data['sms_http_get_request'], $my_data['text'] );

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."my_table`
					SET `notification_sms_http_get_request` = 1
					");

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
			$my_data['text'] = '';
			while ($data = $db->fetchArray($res)) {
				$my_data['text'].= "New pct {$currency_list[$data['currency_id']]}! miners: ".((pow(1+$data['miner'], 3600*24*365)-1)*100)."%/year, users: ".((pow(1+$data['user'], 3600*24*365)-1)*100)."%/year";
			}

			if ($data && $email)
				send_mail($my_data);
			if ($data && $sms)
				send_sms($my_data['sms_http_get_request'], $my_data['text']);

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."pct`
					SET `notification` = 1
					WHERE `notification` = 0
					");

			break;

		// Прошло 2 недели с момента Вашего голосования за %
		case 'voting_time':

			$last_voting = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `last_voting`
					FROM `".DB_PREFIX."my_complex_votes`
					WHERE `notification` = 0
					", 'fetch_one');
			if ( $last_voting && (time()-$last_voting) > 3600*24*14 ) {

				$my_data['text'] = "Time for voting";
				if ($email)
					send_mail($my_data);
				if ($sms)
					send_sms($my_data['sms_http_get_request'], $my_data['text']);

				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."my_complex_votes`
						SET `notification` = 1
						");
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

			$my_data['text'] = "New version: {$new_version}";
			if ($new_version && $email)
				send_mail($my_data);
			if ($new_version && $sms)
				send_sms($my_data['sms_http_get_request'], $my_data['text']);

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."new_version`
					SET `notification` = 1
					WHERE `version` = '{$new_version}'
					");

			break;

		// Расхождение времени сервера более чем на 5 сек
		case 'node_time':

			// имеет значение только для нодов. если невозможно получить время с ntp по какой-то причине, то об этом тоже уведомим
			$my_miner_id = get_my_miner_id($db);
			if ($my_miner_id>0) {
				$t = ntp_time();
				if ($t>5 || !is_int($t)) {
					if (is_int($t))
						$my_data['text'] = "Divergence time {$t} sec";
					else
						$my_data['text'] = "Time error: {$t}";

					if ($email)
						send_mail($my_data);
					if ($sms)
						send_sms($my_data['sms_http_get_request'], $my_data['text']);
				}
			}

			break;

	}

}


?>

