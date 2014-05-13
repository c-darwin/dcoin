<?php
if (!defined('DC')) die("!defined('DC')");

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		UPDATE `".DB_PREFIX."install`
		SET`progress` = 3
		");

if (!get_community_users($db))
	require_once( ABSPATH . 'templates/install_step_4.tpl' );
else {
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		UPDATE `".DB_PREFIX."install`
		SET`progress` = 'complete'
		");
	require_once( ABSPATH . 'templates/install_step_6.tpl' );
}
?>