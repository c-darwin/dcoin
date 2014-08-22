<?php
if (!defined('DC')) die("!defined('DC')");

if (!node_admin_access($db))
	die ('Permission denied');


// удаление юзера с пула
$del_id = intval(@$_REQUEST['parameters']['del_id']);
if ($del_id) {
	$tables_array = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SHOW TABLES
			", 'array');

	foreach ($my_tables as $table) {
		if (in_array(DB_PREFIX."{$del_id}_{$table}", $tables_array)) {
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DROP TABLE `".DB_PREFIX."{$del_id}_{$table}`
					");
		}
	}
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			DELETE FROM `".DB_PREFIX."community`
			WHERE `user_id` = {$del_id}
			");
}

$tpl['users'] = array();
$community = get_community_users($db);
for ($i=0; $i<sizeof($community); $i++) {
	if ($community[$i] != $user_id) {
		$tpl['users'][$community[$i]] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `miner_id`,
							 `email`
				FROM `".DB_PREFIX."{$community[$i]}_my_table`
				LIMIT 1
				", 'fetch_array' );
	}
}

require_once( ABSPATH . 'templates/pool_admin.tpl' );
?>