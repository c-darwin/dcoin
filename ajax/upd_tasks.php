<?php

if ( empty($_SESSION['user_id']) )
	die('!user_id');

if (!defined('DC'))
	die('!DC');

if (!node_admin_access($db))
	die ('Permission denied');

if (version_compare($cur_ver, '0.1.2b3') == -1) {
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."admin` (
			  `user_id` int(11) NOT NULL,
			  `time` int(11) NOT NULL,
			  `log_id` int(11) NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;
			");
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			INSERT INTO `".DB_PREFIX."admin` (`user_id`) VALUES (1)
			");
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."log_admin` (
			  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
			  `user_id` int(11) NOT NULL,
			  `time` int(11) NOT NULL,
			  `block_id` int(11) NOT NULL,
			  `prev_log_id` int(11) NOT NULL,
			  PRIMARY KEY (`log_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;
			");
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."votes_admin` (
			  `user_id` int(11) NOT NULL,
			  `time` int(11) NOT NULL,
			  `admin_user_id` int(11) NOT NULL,
			  `log_id` int(11) NOT NULL,
			  PRIMARY KEY (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;
			");
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."log_votes_admin` (
			  `log_id` int(11) NOT NULL AUTO_INCREMENT,
			  `time` int(11) NOT NULL,
			  `admin_user_id` int(11) NOT NULL,
			  `block_id` int(11) NOT NULL,
			  `prev_log_id` int(11) NOT NULL,
			  PRIMARY KEY (`log_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;
			");
}

/*
if (version_compare($cur_ver, '0.0.1b10') == -1) {

	// Пример отката с конца до опредленного блока
	// !!!! Этот способ нельзя использовать, если были внсены изменения, которые при роллбееках приведут к ошибкам. Если такое случится, единстенный вариант - собирать все блоки с начала

	main_lock();
	rollback_to_block_id(24470, $db);
	main_unlock();
}
*/

/*
if (version_compare($cur_ver, '0.0.2b7') == -1) {

	 // Пример отката с нуля до опредленного блока

	$last_block_id = 172;

	// на всякий случай пометим, что работаем
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			INSERT IGNORE INTO `".DB_PREFIX."main_lock` (
				`lock_time`,
				`script_name`
			)
			VALUES (
				'cleaning_db',
				".time()."
			) ON DUPLICATE KEY UPDATE `lock_time` = ".time().", `script_name` = 'cleaning_db'
			");

	$bad_blocks = json_encode(array(173=>'6b583061dc17ed26122fe22b66bc1b4c04ba38e76f4d8b527500e21fec832659d684c7b1ecce0e14bdd247d901ed397863229d12b3945f315f1799f99666b32d2d4f088ca8d61418f7cc0378e40012e8330e2ca066b5a97658f16d7f2d4c18b1f4013fe440fceb9da7438978ab48f886d4693bd7fe0bb27f1d21a8d25a05158d'));
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."config`
			SET `bad_blocks` = '{$bad_blocks}'
			");

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			DELETE FROM `".DB_PREFIX.MY_PREFIX."my_node_keys`
			WHERE `block_id` > {$last_block_id}
			");
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			DELETE FROM `".DB_PREFIX.MY_PREFIX."my_keys`
			WHERE `block_id` > {$last_block_id}
			");

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX.MY_PREFIX."my_table`
			SET `my_block_id` = {$last_block_id}
			");

	// если в my_node_keys пусто, значит тр-ия, мы еще не майнер
	$nodes_keys = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(*)
			FROM `".DB_PREFIX.MY_PREFIX."my_node_keys`
			", 'fetch_one');
	if ($nodes_keys == 0) {
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX.MY_PREFIX."my_table`
			SET `miner_id` = 0,
				   `status` = 'user'
			");
	}

	// если в my_keys пусто, значит тр-ия, мы еще не юзер
	$nodes_keys = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(*)
			FROM `".DB_PREFIX.MY_PREFIX."my_keys`
			", 'fetch_one');
	if ($nodes_keys == 0) {
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX.MY_PREFIX."my_table`
			SET `user_id` = 0,
				   `status` = 'my_pending'
			");
	}

	$tables_array = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SHOW TABLES
			", 'array');

	foreach($tables_array as $table) {
		//if (substr($table, 0, 3) != 'my_' && substr($table, 0, 3) != '_my' && $table!='install' && $table!='daemons') {
		if (!preg_match('/(my_|install|config|daemons)/i', $table)) {
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
					TRUNCATE TABLE `".DB_PREFIX."{$table}`
					");
		}
	}

}
*/

?>