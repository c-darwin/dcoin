<?php
session_start();

if ( $_SESSION['DC_ADMIN'] != 1 )
	die('!DC_ADMIN');

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

if ( file_exists(ABSPATH . $_REQUEST['path']) )
	echo json_encode( array('result'=>'1') );
else
	echo json_encode( array('result'=>'0') );

?>