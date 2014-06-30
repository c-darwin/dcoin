<?php

$host = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `host`
		FROM `'.DB_PREFIX.MY_PREFIX.'my_table',
		'fetch_one' );
if (defined('COMMUNITY') && !$host) {
	$pool_admin_user_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
			SELECT `pool_admin_user_id`
			FROM `'.DB_PREFIX.'config',
			'fetch_one' );
	$host = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `host`
			FROM `".DB_PREFIX."miners_data`
			WHERE `user_id` = {$pool_admin_user_id}
			", 'fetch_one' );
}
$tpl['data']['host'] = $host;

require_once( ABSPATH . 'templates/upgrade_3.tpl' );

?>