<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$to_id = array();
if (isset($_REQUEST['to_id'])) {
	if (is_array($_REQUEST['to_id'])) {
		for ($i=0; $i<sizeof($_REQUEST['to_id']); $i++) {
			$to_id[$i] = intval($_REQUEST['to_id'][$i]);
		}
	}
	else {
		$to_id = intval($_REQUEST['to_id']);
	}
}

if (!$to_id)
	$to_id = intval($_REQUEST['to_user_id']);

if (strlen($_REQUEST['comment'])>1024)
	die('error comment');

if ($_REQUEST['type'] == 'project') {
	$to_user_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `user_id`
			FROM `".DB_PREFIX."cf_projects`
			WHERE `id` = {$to_id}
			LIMIT 1
			", 'fetch_one' );
}
else
	$to_user_id = $to_id;

if (!is_array($to_user_id)) {
	$to_user_id = array($to_user_id);
}

for ($i=0; $i<sizeof($to_user_id); $i++) {
	if (!$to_user_id[$i]) {
		$enc[$i] = 0;
		continue;
	}
	// если получатель майнер, тогда шифруем нодовским ключем
	$miners_data = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
			SELECT `miner_id`,
						 `node_public_key`
			FROM `" . DB_PREFIX . "miners_data`
			WHERE `user_id` = {$to_user_id[$i]}
			LIMIT 1
			", 'fetch_array');
	if ($miners_data['miner_id'] > 0 && $_POST['type'] != 'cash_request' && $_POST['type'] != 'bug_reporting' && $_POST['type'] != 'project' && $_POST['type'] != 'money_back')
		$public_key = $miners_data['node_public_key'];
	else
		$public_key = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
				SELECT `public_key_0`
				FROM `" . DB_PREFIX . "users`
				WHERE `user_id` = {$to_user_id[$i]}
				LIMIT 1
				", 'fetch_one');

	$rsa = new Crypt_RSA();
	$rsa->loadKey($public_key, CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
	$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
	$enc[$i] = bin2hex($rsa->encrypt($_REQUEST['comment']));
}
if ($_REQUEST['type']!='arbitration_arbitrators') {
	print $enc[0];
}
else {
	print json_encode($enc);
}

?>