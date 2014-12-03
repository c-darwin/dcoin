<?php
if (!defined('DC')) die("!defined('DC')");

// чтобы при добавлений общенных сумм, смены комиссий редиректило сюда
$tpl['navigate'] = 'mining_menu';

if (!empty($_SESSION['restricted'])) {
	$tpl['result'] = 'need_email';
	print '<!--'.$_SESSION['restricted'].'-->';
}
else {
	$my_miner_id = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
					SELECT `miner_id`
					FROM `" . DB_PREFIX . "miners_data`
					WHERE `user_id` = {$user_id}
					", 'fetch_one');
	if (!$my_miner_id) {
		// проверим, послали ли мы запрос в FC-сеть
		$data = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
			SELECT `node_voting_send_request`,
						 `host`
			FROM `" . DB_PREFIX . MY_PREFIX . "my_table`
			LIMIT 1
			", 'fetch_array');
		$node_voting_send_request = $data['node_voting_send_request'];
		$host = $data['host'];

		if ($node_voting_send_request > 0) {

			// голосование нодов
			$node_votes_end = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
				SELECT `votes_end`
				FROM `" . DB_PREFIX . "votes_miners`
				WHERE `user_id` = {$user_id} AND
				             `type` = 'node_voting'
				 ORDER BY `id` DESC
				 LIMIT 1
				", 'fetch_one');

			if ($node_votes_end == '1') { // голосование нодов завершено

				$user_votes_end = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
				SELECT `votes_end`
				FROM `" . DB_PREFIX . "votes_miners`
				WHERE `user_id` = {$user_id} AND
				             `type` = 'user_voting'
				", 'fetch_one');

				if ($user_votes_end == '1') { // юзерское голосование закончено

					$tpl['result'] = 'bad';

				}
				else if ($user_votes_end == '0') { // идет юзерское голосование

					$tpl['result'] = 'users_pending';

				}
				else { // ноды приняли решение, что фото плохое

					$tpl['result'] = 'bad_photos_hash';
					$tpl['host'] = $host;

				}

			} else if ($node_votes_end == '0' && time() - $node_voting_send_request < 86400) { // голосование нодов началось, ждем.

				$tpl['result'] = 'nodes_pending';

			} else if ($node_votes_end == '0' && time() - $node_voting_send_request > 86400) { // голосование нодов удет более суток и еще не завершилось

				$tpl['result'] = 'resend';

			} else { // запрос в FC-сеть еще не дошел и голосования не начались

				// если прошло менее 1 часа
				if (time() - $node_voting_send_request < 3600) {

					$tpl['result'] = 'pending';
				} else { // где-то проблема и запрос не ушел.

					$tpl['result'] = 'resend';
				}
			}
		} else { // запрос на получение статуса "майнер" мы еще не слали
			$tpl['result'] = 'null';
		}
	} else {
		// добавлена ли обещанная сумма
		$promised_amount = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
					SELECT `id`
					FROM `" . DB_PREFIX . "promised_amount`
					WHERE `user_id` = {$user_id}
					", 'fetch_one');
		if (!$promised_amount) {

			// возможно юзер уже отправил запрос на добавление обещенной суммы
			$tpl['last_tx'] = get_last_tx($user_id, types_to_ids(array('new_promised_amount')));
			if (!empty($tpl['last_tx'][0]['queue_tx']) || !empty($tpl['last_tx'][0]['tx'])) {

				// установлена ли комиссия
				check_commission();
			}
			else {
				$tpl['result'] = 'need_promised_amount';
			}
		}
		else {

			// установлена ли комиссия
			check_commission();
		}
	}
}

function check_commission () {
	global $db, $tpl, $user_id, $lng;
	// установлена ли комиссия
	$commission = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
						SELECT `commission`
						FROM `" . DB_PREFIX . "commission`
						WHERE `user_id` = {$user_id}
						", 'fetch_one');
	if (!$commission) {

		// возможно юзер уже отправил запрос на добавление комиссии
		$tpl['last_tx'] = get_last_tx($user_id, types_to_ids(array('change_commission')));
		if (!empty($tpl['last_tx'][0]['queue_tx']) || !empty($tpl['last_tx'][0]['tx'])) {

			// авансом выдаем полное майнерское меню
			$tpl['result'] = 'full_mining_menu';
		}
		else {
			$tpl['result'] = 'need_commission';
		}
	}
	else {

		$tpl['result'] = 'full_mining_menu';
	}
}

if ($tpl['result']=='null') {

	require_once( ABSPATH . 'content/upgrade_0.php' );
}
else if ($tpl['result'] == 'need_email') {

	require_once(ABSPATH . 'templates/sign_up_in_the_pool.tpl');
}
else if ($tpl['result'] == 'need_promised_amount') {

	require_once(ABSPATH . 'content/promised_amount_add.php');
}
else if ($tpl['result'] == 'need_commission') {

	require_once(ABSPATH . 'content/change_commission.php');
}
else if ($tpl['result'] == 'full_mining_menu') {

	$tpl['last_tx'] = get_last_tx($user_id, types_to_ids(array('new_user', 'new_miner', 'new_promised_amount', 'change_promised_amount', 'votes_miner', 'change_geolocation', 'votes_promised_amount', 'del_promised_amount', 'cash_request_out', 'cash_request_in', 'votes_complex', 'for_repaid_fix', 'new_holidays', 'actualization_promised_amounts', 'mining', 'new_miner_update', 'change_host', 'change_commission')), 3);
	if (!empty($tpl['last_tx']))
		$tpl['last_tx_formatted'] = make_last_txs($tpl['last_tx']);

	require_once(ABSPATH . 'templates/mining_menu.tpl');
}
else {

// сколько у нас осталось попыток стать майнером.
	$count_attempt = ParseData::count_miner_attempt($db, $user_id, 'user_voting');
	$variables = ParseData::get_variables($db,  array('miner_votes_attempt') );

	$tpl['miner_votes_attempt'] = $variables['miner_votes_attempt'] - $count_attempt;

// комментарии проголосовавших
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT *
		FROM `'.DB_PREFIX.MY_PREFIX.'my_comments`
		WHERE `comment` != "null"
		');
	while ($row = $db->fetchArray($res)) {
		$tpl['my_comments'][] = $row;
	}
	require_once( ABSPATH . 'templates/upgrade.tpl' );
}
/*
	if ($tpl['account_status'] == 'user') {
		// Выдаем только 1-й пункт
		$tpl['mode'] = 0;
	} else {
		// указан ли email?
		$email = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
				SELECT `email`
				FROM `" . DB_PREFIX . MY_PREFIX . "my_table`
				", 'fetch_one');
		if (!$email) {
			$tpl['mode'] = 1;
		} else {
			// добавлена ли обещанная сумма
			$promised_amount = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
					SELECT `id`
					FROM `" . DB_PREFIX . "promised_amount`
					WHERE `user_id` = {$user_id}
					", 'fetch_one');
			if (!$promised_amount) {
				$tpl['mode'] = 2;
			} else {
				// установлена ли комиссия
				$commission = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
						SELECT `commission`
						FROM `" . DB_PREFIX . "commission`
						WHERE `user_id` = {$user_id}
						", 'fetch_one');
				if (!$commission) {
					$tpl['mode'] = 3;
				} else {
					$tpl['mode'] = 4; // итоговый режим, где выводим "Выполняйте задания по проверке других майнеров/Голосуйте за параметры валют/Не пропускайте входящие запросы/Переводите монеты с обещанных сумм на свой счет"
				}
			}
		}
	}
*/
/*
*/
?>