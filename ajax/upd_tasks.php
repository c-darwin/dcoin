<?php

if (!defined('DC'))
	die('!DC');


if (version_compare($cur_ver, '0.0.1b15') == -1) {
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			ALTER TABLE `".DB_PREFIX."forex_orders` CHANGE `sell_rate` `sell_rate` DECIMAL(20,10) NOT NULL COMMENT 'По какому курсу к buy_currency_id';
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


if (version_compare($cur_ver, '0.0.2b6') == -1) {

	 // Пример отката с нуля до опредленного блока

	$last_block_id = 799;

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

	$bad_blocks = json_encode(array(800=>'1545c610462e96b3e86d1c6af010dcebdf910d67c405cb48091e8d1943766f24abeb41efce5dacaeb0aa0b1a6bf5d3f56e860386d219be56e7e27b928a57aa4bed9252e0b04ce4f087eb1b16d54a7390ab6a49e0c6a4db3c93b8b324d1739ff78587f7800a8d797b0f73ad51a9cd9171fd9e5d934f17a41ba5cd5bed5208a12a'));
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