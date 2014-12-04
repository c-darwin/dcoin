<?php
if (!defined('DC')) die("!defined('DC')");

if (isset($_REQUEST['first_load_blockchain'])) {

	if ($_REQUEST['first_load_blockchain']=='nodes')
		$type = 'nodes';
	else
		$type = 'file';

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."config`
			SET`first_load_blockchain` = '{$type}'
			");
}
if (isset($_REQUEST['public_key'])) {

	$public_key = $_REQUEST['public_key'];
	if ( !check_input_data ($public_key, 'hex' ) )
		die('public_key not hex');

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		INSERT INTO `".DB_PREFIX."my_keys`(
			`public_key`,
			`status`
		)
		VALUES (
			0x{$public_key},
			'approved'
		)");
}

require_once( ABSPATH . 'templates/after_install.tpl' );

?>