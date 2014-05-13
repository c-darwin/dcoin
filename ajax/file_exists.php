<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

if ( file_exists(ABSPATH . $_REQUEST['path']) )
	echo json_encode( array('result'=>'1') );
else
	echo json_encode( array('result'=>'0') );

?>