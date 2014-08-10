<?php
if (!defined('DC')) die("!defined('DC')");


$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."cf_projects`
		WHERE `user_id` = {$user_id} AND
					 `del_block_id`=0
		");
while ( $row =  $db->fetchArray( $res ) ) {

	$row = array_merge (project_data($row), $row);

	$tpl['projects'][$row['id']] = $row;
}

$tpl['cf_lng'] = get_all_cf_lng($db);

require_once( ABSPATH . 'templates/my_cf_projects.tpl' );
?>