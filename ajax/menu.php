<?php
session_start();

if ( $_SESSION['DC_ADMIN'] != 1 )
	die('');
	
define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );

if (!isset($lang)) {
	if (@$_SESSION['lang'])
		$lang = $_SESSION['lang'];
	else if (@$_COOKIE['lang'])
		$lang = $_COOKIE['lang'];
}
if (!isset($lang))
	$lang = 'en';

if (!preg_match('/^[a-z]{2}$/iD', $lang))
	die('lang error');

require_once( ABSPATH . 'lang/'.$lang.'.php' );

require_once( ABSPATH . 'templates/menu.tpl' );

?>