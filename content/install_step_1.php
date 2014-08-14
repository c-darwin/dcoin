<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['php_version'] = 'ok';
$tpl['php_mysqli'] = 'ok';
$tpl['php_curl'] = 'ok';
$tpl['php_gd'] = 'ok';
$tpl['php_zip'] = 'ok';
$tpl['php_json'] = 'ok';
$tpl['php_mcrypt'] = 'ok';
$tpl['php_32'] = 'ok';

$extensions = get_loaded_extensions();

if ( version_compare(PHP_VERSION, '5.2.4', '<=') )
	$tpl['php_version'] = 'no';

if ( !in_array("mysqli", $extensions) )
	$tpl['php_mysqli'] = 'no';

if ( !in_array("curl", $extensions) )
	$tpl['php_curl'] = 'no';

if ( !in_array("gd", $extensions) )
	$tpl['php_gd'] = 'no';

if ( !in_array("zip", $extensions) )
	$tpl['php_zip'] = 'no';

if ( !in_array("json", $extensions) )
	$tpl['php_json'] = 'no';

if ( !in_array("mcrypt", $extensions) )
	$tpl['php_mcrypt'] = 'no';

if ( PHP_INT_MAX!=2147483647 )
	$tpl['php_32'] = 'no';

$_SESSION['install_progress'] = 1;

require_once( ABSPATH . 'templates/install_step_1.tpl' );

?>