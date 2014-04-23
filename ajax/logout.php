<?php
session_start();

if ( $_SESSION['DC_ADMIN'] != 1 )
	die('!DC_ADMIN');

$_SESSION['DC_ADMIN'] = 0;

?>