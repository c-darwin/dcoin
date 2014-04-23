<?php
if (!defined('DC')) die("!defined('DC')");


$tpl['public_key'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `public_key`
		FROM `".DB_PREFIX."my_keys`
		WHERE `block_id` = (SELECT max(`block_id`) FROM `".DB_PREFIX."my_keys` )
		", 'fetch_one');
$tpl['public_key'] = bin2hex($tpl['public_key']);
$tpl['script_version'] = str_ireplace('[ver]', get_current_version($db), $lng['script_version']);

$script_name = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `script_name`
		FROM `".DB_PREFIX."main_lock`
		", 'fetch_one');
if ($script_name == 'my_lock')
	$tpl['demons_status'] = 'OFF';
else
	$tpl['demons_status'] = 'ON';

$tpl['my_notice'] = get_my_notice_data();
//var_dump($tpl['my_notice']);

require_once( ABSPATH . 'templates/home.tpl' );

?>