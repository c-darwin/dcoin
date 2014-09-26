<?php
if (!$argv) die('browser');

define( 'DC', true );

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );
require_once( ABSPATH . 'includes/errors.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

// гео-декодирование
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `id`,
					  `latitude`,
					 `longitude`
		FROM `".DB_PREFIX."cf_projects`
		WHERE `geo_checked`= 0
		");
while ( $row =  $db->fetchArray( $res ) ) {
	$tpl['projects'][$row['id']] = $row;
	$data = json_decode(file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?latlng={$row['latitude']},{$row['longitude']}&sensor=true_or_false"), true);
	$data = $data['results'][sizeof($data['results'])-2];
	$country = $db->escape($data['address_components'][1]['short_name']);
	$city = $db->escape($data['address_components'][0]['long_name']);
	print $country.' / '.$city."\n";
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."cf_projects`
			SET `country` = '{$country}',
					`city` = '{$city}',
					`geo_checked`= 1
			WHERE `id` = {$row['id']}
		");
}

// финансирование проектов
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `id`,
					 `project_id`,
					 `amount`
		FROM `".DB_PREFIX."cf_funding`
		WHERE `checked`= 0
		");
while ( $row =  $db->fetchArray( $res ) ) {

	// отмечаем, чтобы больше не брать
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."cf_funding`
			SET  `checked` = 1
			WHERE `id` = {$row['id']}
		");

	// сколько собрано средств
	$funding = (int) $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT sum(`amount`)
			FROM `".DB_PREFIX."cf_funding`
			WHERE `project_id` = {$row['project_id']} AND
						`del_block_id` = 0
			", 'fetch_one');

	// сколько всего фундеров
	$count_funders = (int) $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `id`
			FROM `".DB_PREFIX."cf_funding`
			WHERE `project_id` = {$row['project_id']} AND
						`del_block_id` = 0
			GROUP BY `user_id`
			", 'num_rows');

	// обновляем кол-во фундеров и собранные средства
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."cf_projects`
			SET  `funding` = {$funding},
					`funders` = {$count_funders}
			WHERE `id` = {$row['project_id']}
			");
}

?>