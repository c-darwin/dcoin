<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$mysqli_link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!node_admin_access($db))
	die('Permission denied');

$db_name = DB_NAME;
$prefix = DB_PREFIX;
include ABSPATH.'schema.php';

$error = pool_add_users ($_POST['pool_data'], $my_queries, $mysqli_link, DB_PREFIX);
if ($error)
	print $error;
?>