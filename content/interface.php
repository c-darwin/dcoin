<?php
if (!defined('DC')) die("!defined('DC')");

if (!empty($_SESSION['restricted']) || empty($_SESSION['user_id']))
	die ('Permission denied');

define('MY_PREFIX', get_my_prefix($db));

$name = '';
if (isset($_REQUEST['parameters']['show_map'])) {
	$name = 'show_map';
} else if (isset($_REQUEST['parameters']['show_sign_data'])) {
	$name = 'show_sign_data';
} else if (isset($_REQUEST['parameters']['show_progress_bar'])) {
	$name = 'show_progress_bar';
}

if ($name) {
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX.MY_PREFIX."my_table`
			SET `{$name}` = ".(int)(boolean) $_REQUEST['parameters'][$name]."
			");
	$tpl['alert'] = $lng['done'];
}


$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
			SELECT *
			FROM `'.DB_PREFIX.MY_PREFIX.'my_table`
			', 'fetch_array');
$tpl['show_sign_data'] = $data['show_sign_data'];
$tpl['show_map'] = $data['show_map'];
$tpl['show_progress_bar'] = $data['show_progress_bar'];


require_once( ABSPATH . 'templates/interface.tpl' );

?>