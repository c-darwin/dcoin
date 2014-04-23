<?php
define( 'DC', TRUE);
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$hash_pass= $_POST['hash_pass'];

if ( check_input_data ($hash_pass, 'hash_code') ) {

	$private_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `private_key`
			FROM `".DB_PREFIX."my_keys`
			WHERE `block_id` = (SELECT max(`block_id`) FROM `".DB_PREFIX."my_keys` ) AND
						 `password_hash` = '{$hash_pass}'
			", 'fetch_one' );
	//print $db->printsql();
	if ($private_key) {
		session_start();
		$_SESSION['DC_ADMIN'] = 1;
		$my_user_id = get_my_user_id($db);
		if ($my_user_id==1)
			$_SESSION['ADMIN'] = 1;
		print json_encode(array('result'=>1, 'key'=>$private_key));
	}
	else
		print json_encode(array('result'=>0));
}


?>