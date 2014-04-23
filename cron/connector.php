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

	$my_table = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `user_id`,
						 `miner_id`,
						 `out_connections`,
						 `local_gate_ip`
			FROM `".DB_PREFIX."my_table`
			", 'fetch_array' );
	if ($my_table['local_gate_ip']) {
		sleep(5);
		continue;
	}
	// ровно стольким нодам мы будем слать хэши блоков и тр-ий
	$max_hosts = ($my_table['out_connections']?$my_table['out_connections']:10);
	$my_miner_id = $my_table['miner_id'];
	$my_user_id = $my_table['user_id'];
	$hosts = array();
	$urls = array();
	$del_miners = array();
	$nodes_inc = '';
	$nodes_count = 0;
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `".DB_PREFIX."nodes_connection`.`host`,
						 `".DB_PREFIX."nodes_connection`.`user_id`,
						 `ban_start`,
						 `miner_id`
			FROM `".DB_PREFIX."nodes_connection`
			LEFT JOIN `".DB_PREFIX."nodes_ban` ON `".DB_PREFIX."nodes_ban`.`user_id` = `".DB_PREFIX."nodes_connection`.`user_id`
			LEFT JOIN `".DB_PREFIX."miners_data` ON `".DB_PREFIX."miners_data`.`user_id` = `".DB_PREFIX."nodes_connection`.`user_id`
			");
	while ( $row = $db->fetchArray( $res ) ) {

		// если нода забанена недавно
		if ( $row['ban_start'] > time() - NODE_BAN_TIME ) {
			$del_miners[] = $row['miner_id'];
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."nodes_connection`
					WHERE `host` = '{$row['host']}'
					");
			continue;
		}
		$hosts[] = $row['host'];
		$nodes_inc.="{$row['host']};{$row['user_id']}\n";
		$nodes_count++;
	}

	for ($i=0; $i<sizeof($hosts); $i++)
		$urls[$i]['url'] = $hosts[$i].'ok.php';

	$result = m_curl ($urls, '', '', '', 5, true, false);

	// если нода не отвечает, то удалем её из таблы nodes_connection
	foreach ($result as $host=>$answer) {
		if ($answer!='ok'){
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."nodes_connection`
					WHERE `host` = '{$host}'
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
		do {
			if ($max>1)
				$rand = (rand(1, $max));
			else
				$rand=1;
			$id_array[$rand] = 1;
			//print $rand."\n";
			//ob_flush();
		} while ( sizeof($id_array) < $need && sizeof($id_array) < $max );
		debug_print("id_array=".print_r_hex($id_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// удалим себя
		unset($id_array[$my_miner_id]);

		// Удалим забаннные хосты
		for ($i=0; $i<sizeof($del_miners); $i++) {
			unset($id_array[$del_miners[$i]]);
		}

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
				$hosts[] = $row['host'];
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT IGNORE INTO `".DB_PREFIX."nodes_connection` (
							`host`,
							`user_id`
						) VALUES (
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
			if ( $user_id == $my_user_id )
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
