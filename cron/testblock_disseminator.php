<?php
/**
 * Демон, который мониторит таблу testblock и если видит status=active,
 * то шлет блок строго тем, кто находятся на одном с нами уровне. Если пошлет
 * тем, кто не на одном уровне, то блок просто проигнорируется
 *
 */
define( 'DC', true );
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

do {

	debug_print("START", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$urls = array();

	// отметимся в БД, что мы живы.
	upd_deamon_time($db);

	// проверим, не нужно нам выйти, т.к. обновилась версия скрипта
	if (check_deamon_restart($db))
		exit;

	if (get_my_local_gate_ip($db)) {
		sleep(5);
		continue;
	}

	$testBlock = new testblock($db, true);
	// получим id майнеров, которые на нашем уровне
	$nodes_ids = $testBlock->getOurLevelNodes();
	unset($testBlock);

	if (!$nodes_ids) {
		debug_print("continue", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		sleep(1);
		continue;
	}

	//print "id майнеров, которые на нашем уровне\n";
	debug_print($nodes_ids, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$add_sql = '';
	for ($i=0; $i<sizeof($nodes_ids); $i++)
		$add_sql.="{$nodes_ids[$i]},";
	$add_sql = substr($add_sql, 0, strlen($add_sql)-1);

	if (!$add_sql) {
		debug_print("continue", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		sleep(1);
		continue;
	}

	// получим хосты майнеров, которые на нашем уровне
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `host`
					FROM `".DB_PREFIX."miners_data`
					WHERE `user_id` IN ({$add_sql})
					");
	while ( $row = $db->fetchArray( $res ) )
		$urls[]['url'] = $row['host'].'gate_testblock.php';

	// шлем block_id, user_id, mrkl_root, signature
	$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `block_id`,  `time`, `user_id`, `mrkl_root`, `signature`
			FROM `".DB_PREFIX."testblock`
			WHERE `status` = 'active'
			", 'fetch_array' );
	//print_r($data);
	//print_R($urls);
	if ($data) {
		$data_binary =  dec_binary ($data['block_id'], 4) .
								dec_binary ($data['time'], 4) .
								dec_binary ($data['user_id'], 5) .
								$data['mrkl_root'] .
								ParseData::encode_length_plus_data($data['signature']);

		m_curl ($urls, $data_binary, '', 'data', 30);
	}
	//else


	debug_print("END", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	sleep(1);

} while (true);


?>
