<?php

if (!defined('DC'))
	die('!DC');

if (version_compare($cur_ver, '0.0.1b3') == -1) {

	/*
	 * Пример отката до опредленного блока
	 *  */

	$last_block_id = 600;

	// на всякий случай пометим, что работаем
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."main_lock`
			SET `script_name` = 'cleaning_db'
			");

	$bad_blocks = json_encode(array(601=>'1003ee2ee3ab7cb0ec84d87962cc0481abf0adf623efd669d3cc617ab9e3aa4010fd1dc77ca405c5ae3e8346132eadac5122ae7adc25a6347f39210950f58d542b3ffb905c7de0c4d4cb124aaf50f7e375dbed7b97839816b090309c22ba6f428e803285bac022ac3898de40a820c983e2a02c80f6766c4ced5c74b95b39b3af'));
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