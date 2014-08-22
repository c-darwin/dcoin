<?php
if (!defined('DC')) die("!defined('DC')");

// проверим, не идут ли тех. работы на пуле
$config = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `pool_admin_user_id`,
					  `pool_tech_works`
		FROM `'.DB_PREFIX.'config`
		', 'fetch_array');
if ($config['pool_admin_user_id'] && $config['pool_admin_user_id']!=$_SESSION['user_id'] && $config['pool_tech_works']==1)
	$tpl['pool_tech_works'] = 1;
else
	$tpl['pool_tech_works'] = 0;

$tpl['ver'] = file_get_contents(ABSPATH.'version');

require_once( ABSPATH . 'templates/login.tpl' );

?>