<?php
if (!defined('DC')) die("!defined('DC')");

if (!isset($tpl['cf_url'])) $tpl['cf_url'] = '';

$tpl['category_id'] = intval($_REQUEST['parameters']['category_id']);
$add_sql = '';
if ($tpl['category_id'])
	$add_sql = " AND `category_id` = '{$tpl['category_id']}' ";

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `".DB_PREFIX."cf_projects`.*
		FROM `".DB_PREFIX."cf_projects`
		LEFT JOIN `".DB_PREFIX."cf_projects_data` ON  `".DB_PREFIX."cf_projects_data`.`project_id` = `".DB_PREFIX."cf_projects`.`id`
		WHERE `del_block_id` = 0 AND
					 `lang_id` = {$lang}
				 	  {$add_sql}
		ORDER BY `funders` DESC
		");
while ( $row =  $db->fetchArray( $res ) ) {
	$row = array_merge (project_data($row, $tpl['cf_url']), $row);
	$tpl['projects'][$row['id']] = $row;
}

asort($lng['cf_category']);

$tpl['currency_list'] = get_currency_list($db);

if (isset($_REQUEST['parameters']['category_id']))
	$tpl['cur_category'] = $lng['cf_category'][$tpl['category_id']];
else
	$tpl['cur_category'] = false;

require_once( ABSPATH . 'templates/cf_catalog.tpl' );

?>