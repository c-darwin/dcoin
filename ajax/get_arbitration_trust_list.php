<?php
session_start();
define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$lang = get_lang();
require_once( ABSPATH . 'lang/'.$lang.'.php' );

$get_user_id = intval($_REQUEST['user_id']);
$arbitration_trust_list = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT `arbitrator_user_id`
		FROM `" . DB_PREFIX . "arbitration_trust_list`
		WHERE `user_id` = {$get_user_id}
		", 'array');
print json_encode(array('trust_list'=>$arbitration_trust_list));

?>