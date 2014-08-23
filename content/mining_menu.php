<?php
if (!defined('DC')) die("!defined('DC')");

$status = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `status`
		FROM `".DB_PREFIX."miners_data`
		WHERE `user_id` = {$user_id}
		LIMIT 1
		", 'fetch_one');
if ($status && empty($_SESSION['restricted']))
	$tpl['account_status'] = $status;
else
	$tpl['account_status'] = 'user';

if ($tpl['account_status']=='user') {
	// Вывдаем только 1-й пункт
	$tpl['mode'] = 0;
}
else {
	// указан ли email?
	$email = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `email`
			FROM `".DB_PREFIX.MY_PREFIX."my_table`
			", 'fetch_one');
	if (!$email) {
		$tpl['mode'] = 1;
	}
	else {
		// добавлена ли обещанная сумма
		$promised_amount = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `user_id` = {$user_id}
				", 'fetch_one');
		if (!$promised_amount) {
			$tpl['mode'] = 2;
		}
		else {
			// установелна ли комиссия
			$commission = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `commission`
					FROM `".DB_PREFIX."commission`
					WHERE `user_id` = {$user_id}
					", 'fetch_one');
			if (!$commission) {
				$tpl['mode'] = 3;
			}
			else {
				$tpl['mode'] = 4; // итоговый режим, где выводим "Выполняйте задания по проверке других майнеров/Голосуйте за параметры валют/Не пропускайте входящие запросы/Переводите монеты с обещанных сумм на свой счет"
			}
		}
	}
}

require_once( ABSPATH . 'templates/mining_menu.tpl' );

?>