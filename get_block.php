<?php

define( 'DC', TRUE);

define( 'ABSPATH', dirname(__FILE__) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (check_input_data($_REQUEST['id'], 'int') ) {

	if (isset($_REQUEST['download'])) {
		header('Content-type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.$_REQUEST['id'].'"');
	}

	$block = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `data`
			FROM `".DB_PREFIX."block_chain`
			WHERE `id` = {$_REQUEST['id']}
			", 'fetch_one' );
	echo $block;
}
else {
	echo json_encode( array('error' => 'bad id') );
}

?>