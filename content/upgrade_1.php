<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['user_face'] = '';
$tpl['user_profile'] = '';

$path = 'public/'.$_SESSION['user_id'].'_user_profile.jpg';
if ( file_exists( ABSPATH . $path ) )
	$tpl['user_profile'] = $path;

$path = 'public/'.$_SESSION['user_id'].'_user_face.jpg';
if ( file_exists( ABSPATH . $path ) )
	$tpl['user_face'] = $path;

$tpl['step'] = '1';
$tpl['next_step'] = '2';
$tpl['photo_type'] = 'face';
require_once( ABSPATH . 'templates/upgrade_1_and_2.tpl' );

?>