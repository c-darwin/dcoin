<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['category_id'] = intval($_REQUEST['parameters']['category_id']);
$add_sql = '';
if ($tpl['category_id'])
	$add_sql = " AND `category_id` = '{$tpl['category_id']}' ";

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."cf_projects`
		WHERE `del_block_id`=0
				 	  {$add_sql}
		");
while ( $row =  $db->fetchArray( $res ) ) {
	$row = array_merge (project_data($row), $row);
	$tpl['projects'][$row['id']] = $row;
}

asort($lng['cf_category']);

require_once( ABSPATH . 'templates/cf_catalog.tpl' );

?>