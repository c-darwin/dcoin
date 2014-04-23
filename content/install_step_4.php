<?php
if (!defined('DC')) die("!defined('DC')");

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		UPDATE `".DB_PREFIX."install`
		SET`progress` = 3
		");

require_once( ABSPATH . 'templates/install_step_4.tpl' );


?>