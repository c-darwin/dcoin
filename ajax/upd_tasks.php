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


if (version_compare($cur_ver, '0.0.1b13') == -1) {

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

	$bad_blocks = json_encode(array(800=>'85621082c3fd2eab59c21f72df50b91520166e39fb3a83dc9bc7daf11812c850f018de93e77479517bd5ea201ffd1632b7c65ae8306128013cad204cbc900f0de8e6eb5251fa309bd177e0a10668f219aa521deb27233a269ccea75ae79ddf39603ddb01e664d0ef3573844d67a5f94dea6e56b9d9192ae95ab7466381ca1fcb'));
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