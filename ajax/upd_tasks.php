<?php

if (!defined('DC'))
	die('!DC');

/*
if (version_compare($cur_ver, '0.0.1b10') == -1) {

	// Пример отката с конца до опредленного блока
	// !!!! Этот способ нельзя использовать, если были внсены изменения, которые при роллбееках приведут к ошибкам. Если такое случится, единстенный вариант - собирать все блоки с начала

	main_lock();
	rollback_to_block_id(24470, $db);
	main_unlock();
}
*/


if (version_compare($cur_ver, '0.0.1b11') == -1) {

	 // Пример отката с нуля до опредленного блока

	$last_block_id = 3000;

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

	$bad_blocks = json_encode(array(3002=>'79a3c86939d364497aebe0f97f2e902702ed9bbed897129fdb17de499768c42860541e08b2365ac77e25b96525663427ce665f6150c9c6492d6147c609942ad4f3e63b72f6b823bebdff14da19b43bc336f5286138470964e3249001561330ce79e1e0a0a700adffc93d08510958046c0205c6e225a9e55be2eab6489fbfb447'));
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."my_table`
			SET `bad_blocks` = '{$bad_blocks}'
			");

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			DELETE FROM `".DB_PREFIX."my_node_keys`
			WHERE `block_id` > {$last_block_id}
			");
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			DELETE FROM `".DB_PREFIX."my_keys`
			WHERE `block_id` > {$last_block_id}
			");

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."my_table`
			SET `my_block_id` = {$last_block_id}
			");

	// если в my_node_keys пусто, значит тр-ия, мы еще не майнер
	$nodes_keys = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(*)
			FROM `".DB_PREFIX."my_node_keys`
			", 'fetch_one');
	if ($nodes_keys == 0) {
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."my_table`
			SET `miner_id` = 0,
				   `status` = 'user'
			");
	}

	// если в my_keys пусто, значит тр-ия, мы еще не юзер
	$nodes_keys = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(*)
			FROM `".DB_PREFIX."my_keys`
			", 'fetch_one');
	if ($nodes_keys == 0) {
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."my_table`
			SET `user_id` = 0,
				   `status` = 'my_pending'
			");
	}

	$tables_array = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SHOW TABLES
			", 'array');

	foreach($tables_array as $table) {
		if (substr($table, 0, 3) != 'my_' && substr($table, 0, 3) != '_my' && $table!='install' && $table!='deamons') {
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
					TRUNCATE TABLE `".DB_PREFIX."{$table}`
					");
		}
	}

}


?>