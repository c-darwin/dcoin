<?php
if (!defined('DC')) die("!defined('DC')");


require_once( ABSPATH . 'phpseclib/Math/BigInteger.php');
require_once( ABSPATH . 'phpseclib/Crypt/Random.php');
require_once( ABSPATH . 'phpseclib/Crypt/Hash.php');
require_once( ABSPATH . 'phpseclib/Crypt/RSA.php');
require_once( ABSPATH . 'phpseclib/Crypt/AES.php');


if ($_SESSION['install_progress'] < 5)
	die('access denied');


$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		UPDATE `".DB_PREFIX."install`
		SET`progress` = 'complete'
		");

require_once( ABSPATH . 'templates/install_step_6.tpl' );


?>