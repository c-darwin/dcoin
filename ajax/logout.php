<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

unset($_SESSION['user_id']);
unset($_SESSION['private_key']);
unset($_SESSION['public_key']);

?>