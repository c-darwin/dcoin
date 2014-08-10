<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'cf_project_data';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

// если есть project_id, значит это добавление новго описания на новом языке
$tpl['project_id'] = intval($_REQUEST['parameters']['project_id']);

// если есть id, значит юзер нажал "редактировать описание"
$tpl['id'] = intval($_REQUEST['parameters']['id']);

if ($tpl['id']) {
	$tpl['cf_data'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."cf_projects_data`
			WHERE `id` = {$tpl['id']}
			", 'fetch_array');
	$tpl['project_id'] = $tpl['cf_data'] ['project_id'];
}

$tpl['cf_currency_name'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `project_currency_name`
		FROM `".DB_PREFIX."cf_projects`
		WHERE `id` = {$tpl['project_id']}
		", 'fetch_one');

$tpl['cf_lng'] = get_all_cf_lng($db);

require_once( ABSPATH . 'templates/add_cf_project_data.tpl' );

?>