<?php
define( 'DC', true );
/*
 * Поддержииваем таблицу nodes_connection в актуальном состоянии
 * Дожно быть кол-во нодов = my_table.out_connections
 * main_lock не используется
 * */

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

while (true) {

	// отметимся в БД, что мы живы.
	upd_deamon_time($db);

	// проверим, не нужно нам выйти, т.к. обновилась версия скрипта
	if (check_deamon_restart($db))
		exit;

	$my_config = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `out_connections`,
						 `local_gate_ip`
			FROM `".DB_PREFIX."config`
			", 'fetch_array' );
	if ($my_config['local_gate_ip']) {
		sleep(5);
		continue;
	}
	// ровно стольким нодам мы будем слать хэши блоков и тр-ий
	$max_hosts = ($my_config['out_connections']?$my_config['out_connections']:10);

	$collective = get_community_users($db);
	if (!$collective) // сингл-мод
		$collective[0] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				SELECT `user_id`
				FROM `".DB_PREFIX."my_table`
				", 'fetch_one' );

	// в сингл-моде будет только $my_miners_ids[0]
	$my_miners_ids = get_my_miners_ids($db, $collective);

	$nodes_ban = array();
	$hosts = array();
	$urls = array();
	$del_miners = array();
	$nodes_inc = '';
	$nodes_count = 0;

	// забаненные хосты
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `host`, `ban_start`
				FROM `".DB_PREFIX."nodes_ban`
				LEFT JOIN `".DB_PREFIX."miners_data` ON `".DB_PREFIX."miners_data`.`user_id` = `".DB_PREFIX."nodes_ban`.`user_id`
				");
	while ( $row = $db->fetchArray( $res ) ) {
		$nodes_ban[$row['host']] = $row['ban_start'];
	}

	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `".DB_PREFIX."nodes_connection`.`host`,
						 `".DB_PREFIX."nodes_connection`.`user_id`,
						 `ban_start`,
						 `miner_id`
			FROM `".DB_PREFIX."nodes_connection`
			LEFT JOIN `".DB_PREFIX."nodes_ban` ON `".DB_PREFIX."nodes_ban`.`user_id` = `".DB_PREFIX."nodes_connection`.`user_id`
			LEFT JOIN `".DB_PREFIX."miners_data` ON `".DB_PREFIX."miners_data`.`user_id` = `".DB_PREFIX."nodes_connection`.`user_id`
			");
	$i=0;
	while ( $row = $db->fetchArray( $res ) ) {

		// отметимся в БД, что мы живы.
		upd_deamon_time($db);

		// проверим, не нужно нам выйти, т.к. обновилась версия скрипта
		if (check_deamon_restart($db))
			exit;

		debug_print($row, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// проверим соотвествие хоста и user_id
		$ok = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `user_id`
			FROM `".DB_PREFIX."miners_data`
			WHERE `user_id` = {$row['user_id']} AND
						 `host` = '{$row['host']}'
			", 'fetch_one');
		if (!$ok) {
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."nodes_connection`
					WHERE `host` = '{$row['host']}' OR
								 `user_id` = {$row['user_id']}
					");
		}

		// если нода забанена недавно
		if ( $row['ban_start'] > time() - NODE_BAN_TIME ) {
			$del_miners[] = $row['miner_id'];
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."nodes_connection`
					WHERE `host` = '{$row['host']}' OR `user_id` = {$row['user_id']}
					");
			continue;
		}
		$hosts[$i]['host'] = $row['host'];
		$hosts[$i]['user_id'] = $row['user_id'];
		$nodes_inc.="{$row['host']};{$row['user_id']}\n";
		$nodes_count++;
		$i++;
	}

	for ($i=0; $i<sizeof($hosts); $i++) {
		$urls[$i]['url'] = $hosts[$i]['host'].'ok.php?user_id='.$hosts[$i]['user_id'];
		$urls[$i]['user_id'] = $hosts[$i]['user_id'];
	}

	$result = m_curl ($urls, '', '', '', 5, true, false);

	debug_print("result=".print_r_hex($result), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// если нода не отвечает, то удалем её из таблы nodes_connection
	foreach ($result as $user_id=>$answer) {
		if ($answer!='ok'){
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."nodes_connection`
					WHERE `user_id` = {$user_id}
					");
		}
	}
	// добьем недостающие хосты до $max_hosts
	if (sizeof($hosts) < $max_hosts) {

		$need = $max_hosts - sizeof($hosts);
		debug_print("need={$need}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$max = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT max(`miner_id`)
				FROM `".DB_PREFIX."miners`
				", 'fetch_one');
		debug_print("max={$max}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$i1=0;
		do {
			if ($max>1)
				$rand = (rand(1, $max));
			else
				$rand=1;
			$id_array[$rand] = 1;
			//print $rand."\n";
			//ob_flush();
			$i1++;
		} while ( sizeof($id_array) < $need && sizeof($id_array) < $max && $i1<30 );
		debug_print("id_array=".print_r_hex($id_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print("my_miners_ids=".print_r_hex($my_miners_ids), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print("del_miners=".print_r_hex($del_miners), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		for ($i=0; $i<sizeof($my_miners_ids); $i++)
			unset($id_array[$my_miners_ids[$i]]); // удалим себя

		// Удалим забаннные хосты
		for ($i=0; $i<sizeof($del_miners); $i++) {
			unset($id_array[$del_miners[$i]]);
		}

		debug_print("id_array=".print_r_hex($id_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$ids='';
		if ($id_array) {
			foreach ($id_array as $id=>$one) {
				$ids.=$id.',';
			}
			$ids = substr($ids, 0, -1);
			$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `host`, `user_id`
					FROM `".DB_PREFIX."miners_data`
					WHERE `miner_id` IN ({$ids})
					");
			while ( $row = $db->fetchArray( $res ) ) {

				if (array_key_exists($row['host'], $nodes_ban))
					if ($nodes_ban[$row['host']] > time() - NODE_BAN_TIME)
						continue;

				$hosts[] = $row['host'];
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT IGNORE INTO `".DB_PREFIX."nodes_connection` (
							`host`,
							`user_id`
						)
						VALUES (
							'{$row['host']}',
							{$row['user_id']}
						)");
			}
		}
	}

	debug_print($hosts, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// если хосты не набрались из miner_data, то берем из файла
	if (!$hosts) {
		$hosts = file(ABSPATH.'nodes.inc');
		debug_print(ABSPATH.'nodes.inc '.print_r_hex($hosts), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$r = array_rand($hosts, sizeof($hosts)>($max_hosts-1)?$max_hosts:sizeof($hosts));
		$r = is_array($r)?$r:array($r);

		for ($i=0; $i<sizeof($r); $i++) {
			list($host, $user_id) = explode(';', $hosts[$r[$i]]);
			if ( in_array($user_id, $collective) )
				continue;

			if (array_key_exists($host, $nodes_ban))
				if ($nodes_ban[$host] > time() - NODE_BAN_TIME)
					continue;

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT IGNORE INTO `".DB_PREFIX."nodes_connection` (
						`host`,
						`user_id`
					)
					VALUES (
						'{$host}',
						{$user_id}
					)");
		}
	}

	if ($nodes_count > 5) {
		$nodes_inc = substr($nodes_inc, 0, -1);
		file_put_contents(ABSPATH.'nodes.inc', $nodes_inc);
	}

	for ($i=0; $i<5; $i++) {
		// отметимся в БД, что мы живы.
		upd_deamon_time($db);
		// проверим, не нужно нам выйти, т.к. обновилась версия скрипта
		if (check_deamon_restart($db))
			exit;
		sleep(10);
	}
}


?>
