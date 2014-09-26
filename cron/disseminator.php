<?php
if (!$argv) die('browser');

define( 'DC', true );
/*
 * просто шлем всем, кто есть в nodes_connection хэши блока и тр-ий
 * если мы не майнер, то шлем всю тр-ию целиком, блоки слать не можем
 * если майнер - то шлем только хэши, т.к. у нас есть хост, откуда всё можно скачать
 * */

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );
require_once( ABSPATH . 'includes/errors.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

while (true) {

	debug_print("START", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$urls = array();
	$hosts = array();

	$my_config = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `local_gate_ip`,
						 `static_node_user_id`
			FROM `".DB_PREFIX."config`
			", 'fetch_array' );

	// отметимся в БД, что мы живы.
	upd_deamon_time($db);

	// проверим, не нужно нам выйти, т.к. обновилась версия скрипта
	if (check_deamon_restart($db))
		exit;

	if (!$my_config['local_gate_ip']) {
		// обычнй режим
		$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `".DB_PREFIX."miners_data`.`user_id`, `".DB_PREFIX."miners_data`.`host`, `node_public_key`
					FROM `".DB_PREFIX."nodes_connection`
					LEFT JOIN `".DB_PREFIX."miners_data` ON `".DB_PREFIX."nodes_connection`.`user_id` = `".DB_PREFIX."miners_data`.`user_id`
					");
		while ( $row = $db->fetchArray( $res ) ) {
			$hosts[] = array('user_id'=>$row['user_id'], 'host'=>$row['host'], 'node_public_key'=>$row['node_public_key']);
		}
		// хосты могут еще не успеть набраться
		if (!$hosts) {
			sleep(1);
			continue;
		}
	}
	else {
		// защищеннй режим
		$node_data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `node_public_key`,
							  `host`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$my_config['static_node_user_id']}
				", 'fetch_array');
		$hosts[] = array('host'=>$my_config['local_gate_ip'], 'node_public_key'=>$node_data['node_public_key'], 'user_id'=>$my_config['static_node_user_id'] );
	}

	debug_print($hosts, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$my_users_ids = get_my_users_ids($db);
	$my_miners_ids = get_my_miners_ids($db, $my_users_ids);

	// если среди тр-ий есть смена нодовского ключа, то слать через отправку хэшей с последющей отдачей данных может не получиться
	// т.к. при некорректном нодовском ключе придет зашифрованый запрос на отдачу данных, а мы его не сможем расшифровать т.к. ключ у нас неверный
	$change_node_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(*)
			FROM `".DB_PREFIX."transactions`
			WHERE `type` = ".ParseData::findType('change_node_key')." AND
						 `user_id` IN (".implode(',', $my_users_ids).")
			", 'fetch_one');

	// если я майнер и работаю в обычном режиме, то должен слать хэши
	if ($my_miners_ids && !$my_config['local_gate_ip'] && !$change_node_key) {

		// опредлим, от кого будем слать
		$r = array_rand($my_miners_ids);
		$my_miner_id = $my_miners_ids[$r];
		$my_user_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `user_id`
				FROM `".DB_PREFIX."miners_data`
				WHERE `miner_id` = {$my_miner_id}
				", 'fetch_one');

		for ($i=0; $i<sizeof($hosts); $i++)
			$urls[$i] = array('url'=>$hosts[$i]['host'].'gate_hashes.php', 'node_public_key'=>$hosts[$i]['node_public_key'], 'user_id'=>$hosts[$i]['user_id']);

		//main_lock();

		// //возьмем хэш текущего блока и номер блока
		// //для теста ролбеков отключим на время
		$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `block_id`,
							 `hash`,
							 `head_hash`
				FROM `".DB_PREFIX."info_block`
				WHERE `sent` = 0
				", 'fetch_array');
		debug_print($data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		//$data = ''; // //для тестов

		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."info_block`
				SET `sent` = 1
				");
		/*
		 * Составляем данные на отправку
		 * */
		// 5 байт = наш user_id. Но они будут не первые, т.к. m_curl допишет вперед user_id получателя (нужно для пулов)
		$to_be_sent = dec_binary($my_user_id, 5);
		if ($data) { // блок
			// если 5-й байт = 0, то на приемнике будем читать блок, если = 1 , то сразу хэши тр-ий
			$to_be_sent .= dec_binary(0, 1) . dec_binary($data['block_id'], 3) . $data['hash'] . $data['head_hash'];
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "UPDATE `".DB_PREFIX."info_block` SET `sent` = 1");
		} else // тр-ии без блока
			$to_be_sent .= dec_binary(1, 1);

		// возьмем хэши тр-ий
		$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `hash`, `high_rate`
				FROM `".DB_PREFIX."transactions`
				WHERE `sent` = 0 AND
							`for_self_use` = 0
				");
		while ( $row = $db->fetchArray( $res ) ) {
			list(, $hex_hash) = unpack( "H*", $row['hash'] );
			$to_be_sent .= dec_binary($row['high_rate'], 1) . $row['hash'];
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."transactions`
					SET `sent` = 1
					WHERE `hash` = 0x{$hex_hash}
					");
		}

		//main_unlock();

		debug_print('$to_be_sent='.$to_be_sent, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		// отправляем блок и хэши тр-ий, если есть что отправлять
		if ( strlen($to_be_sent) > 10 ) {
			debug_print($urls, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$rez = m_curl ($urls, $to_be_sent, $db, 'data', 20, true);
			debug_print($rez, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		}

	}
	else { // если просто юзер или работаю в защищенном режиме, то шлю тр-ии целиком. слать блоки не имею права.
		if ($my_config['local_gate_ip']) {
			$gate = 'protected_gate_tx.php';
			// Чтобы protected_gate_tx.php мог понять, какому ноду слать эту тр-ию, пишем в первые 100 байт host
			$remote_node_host = $node_data['host'];
		}
		else {
			$gate = 'gate_tx.php';
			$remote_node_host = '';
		}
		for ($i=0; $i<sizeof($hosts); $i++)
			$urls[$i] = array('url'=>$hosts[$i]['host'].$gate, 'node_public_key'=>$hosts[$i]['node_public_key'], 'user_id'=>$hosts[$i]['user_id']);
		debug_print($urls, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		// возьмем хэши и сами тр-ии
		$tx_data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `hash`, `data`
				FROM `".DB_PREFIX."transactions`
				WHERE `sent` = 0
				", 'fetch_array');
		debug_print('$tx_data: '.print_r_hex($tx_data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if ($tx_data['hash']) {
			$hex_hash = bin2hex($tx_data['hash']);
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."transactions`
					SET `sent` = 1
					WHERE `hash` = 0x{$hex_hash}
					");
			// в первые 5 байт tx_data['data'] m_curl допишет user_id получателя, если вдруг там пул
			$rez = m_curl ($urls, $tx_data['data'], $db, 'data', 20, true, true, $remote_node_host);
			debug_print($rez, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		}
	}

	sleep(1);

}

?>
