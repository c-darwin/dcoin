<?php

session_start();

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");

$lang = get_lang();
require_once( ABSPATH . 'lang/'.$lang.'.php' );

$tpl['ver'] = file_get_contents( ABSPATH . 'version' );

if ($_GET) {
	$page = each($_REQUEST);
	if (preg_match('/category\-([0-9]+)/', $page[0], $m)) {
		$tpl['nav'] = "fc_navigate ('cf_catalog', {'category_id':{$m[1]}})\n";
	}
	else if (preg_match('/([A-Z0-9]{7}|id-[0-9]+)\-?([0-9]+)?\-?(funders|comments|news|home)?/', $page[0], $m)) {
		// $m[1] - название валюты или id валюты
		// $m[2] - id языка
		// $m[3] - тип страницы (funders|comments|news)
		$add_nav = '';
		if (preg_match('/id\-([0-9]+)/', $m[1], $c_id))
			$add_nav .= "'only_project_id':'{$c_id[1]}',";
		else
			$add_nav .= "'only_cf_currency_name':'{$m[1]}',";
		if (@$m[2])
			$add_nav .= "'lang_id':'{$m[2]}',";
		if (@$m[3])
			$add_nav .= "'page':'{$m[3]}',";
		$add_nav = substr($add_nav, 0, -1);
		$tpl['nav'] = "fc_navigate ('cf_page_preview', {{$add_nav}})\n";
	}
}
else
	$tpl['nav'] = "fc_navigate ('cf_catalog')\n";

$tpl['cf_url'] = get_cf_url();
if (!$tpl['cf_url'])
	die ('index access denied');

$tpl['cf_lang'] = get_all_cf_lng($db);

require_once( ABSPATH . 'templates/index_cf.tpl' );

print '<!--';
print_R($_SESSION);
print '-->';
?>