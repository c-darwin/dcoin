<?php
if (!defined('DC')) die("!defined('DC')");

if (!isset($user_id)) 	$user_id = false;

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");

$tpl['cf_url'] = get_cf_url();

if (isset($_REQUEST['parameters']['page']) && !preg_match('/^[a-z]{0,10}$/iD', $_REQUEST['parameters']['page']))
	die ('error page');

$cf_currency_name = @$_REQUEST['parameters']['only_cf_currency_name'];
if (isset($cf_currency_name) && !preg_match('/^[a-z0-9]{7}$/iD', $cf_currency_name))
	die ('error only_cf_currency_name');

$tpl['page'] = @$_REQUEST['parameters']['page'];
if (!$tpl['page'])
	$tpl['page'] = 'home';

$tpl['lang_id'] = intval(@$_REQUEST['parameters']['lang_id']);
$tpl['project_id'] = intval(@$_REQUEST['parameters']['only_project_id']);

if ($_REQUEST['parameters']['only_project_id'] || $cf_currency_name) {

	if (!$tpl['project_id']) {
		$tpl['project_id'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `id`
			FROM `".DB_PREFIX."cf_projects`
			WHERE  `project_currency_name` = '{$cf_currency_name}'
			", 'fetch_one');
	}

	if ($tpl['lang_id'])
		$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."cf_projects_data`
				WHERE  `project_id` = {$tpl['project_id']} AND
							 `lang_id` = {$tpl['lang_id']}
				", 'fetch_array');
	else { // Если язык не указан, то просто берем первое добавленное описание
		$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."cf_projects_data`
				WHERE  `project_id` = {$tpl['project_id']}
				ORDER BY `id` ASC
				LIMIT 1
				", 'fetch_array');
		$tpl['lang_id'] = $data['lang_id'];
	}

	$tpl['blurb_img'] = $data['blurb_img'];
	$tpl['head_img'] = $data['head_img'];
	$tpl['description_img'] = $data['description_img'];
	$tpl['picture'] = $data['picture'];
	$tpl['video_type'] = $data['video_type'];
	$tpl['video_url_id'] = $data['video_url_id'];
	$tpl['news_img']= $data['news_img'];
	$tpl['links']= json_decode($data['links'], true);
}
else {
	$tpl['project_id'] = intval($_REQUEST['project_id']);
	$tpl['blurb_img'] = $_REQUEST['blurb_img'];
	$tpl['head_img'] = $_REQUEST['head_img'];
	$tpl['description_img'] = $_REQUEST['description_img'];
	$tpl['picture'] = $_REQUEST['picture'];
	$tpl['video_type'] = $_REQUEST['video_type'];
	$tpl['video_url_id'] = $_REQUEST['video_url_id'];
	$tpl['news_img']= $_REQUEST['news_img'];
	$tpl['links']= json_decode($_REQUEST['links'], true);
}

$img_blank = $tpl['cf_url'].'img/blank.png';

if ( !check_input_data ($tpl['blurb_img'], 'img_url'))
	$tpl['blurb_img'] = $img_blank;
if ( !check_input_data ($tpl['head_img'], 'img_url'))
	$tpl['head_img'] = $img_blank;
if ( !check_input_data ($tpl['description_img'], 'img_url'))
	$tpl['description_img'] = $img_blank;
if ( !check_input_data ($tpl['picture'], 'img_url'))
	$tpl['picture'] = $img_blank;
if ( !check_input_data ($tpl['news_img'], 'img_url'))
	$tpl['news_img'] = $img_blank;
if ( !check_input_data ($tpl['video_type'], 'video_type'))
	$tpl['video_type'] = '';
if ( !check_input_data ($tpl['video_url_id'], 'video_url_id'))
	$tpl['video_url_id'] = '';


$tpl['project'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."cf_projects`
		WHERE `id` = {$tpl['project_id']}
		", 'fetch_array');
// сколько дней осталось
$tpl['project']['days'] = round( ($tpl['project']['end_time'] - time() ) / (3600*24));
if ($tpl['project']['days']<=0)
	$tpl['project']['days'] = 0;
if ($tpl['project']['close_block_id'] || $tpl['project']['del_block_id'])
	$tpl['project']['ended'] = 1;

// дата старта
$tpl['project']['start_date'] = date('d-m-Y H:i', $tpl['project']['start_time']);
// в какой валюте идет сбор
$currency_list = get_currency_list($db);
$tpl['project']['currency'] = $currency_list[$tpl['project']['currency_id']];

// на каких языках есть описание
// для home/news можно скрыть язык
$add_sql = '';
if ($tpl['page'] == 'home' || $tpl['page'] == 'news')
	$add_sql = ' AND `hide` = 0';
$tpl['project']['lang'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `id`,
						  `lang_id`
			FROM `".DB_PREFIX."cf_projects_data`
			WHERE `project_id` = {$tpl['project_id']} {$add_sql}
			", 'list', array('id', 'lang_id'));

// сколько собрано средств
$tpl['project']['funding'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT sum(`amount`)
		FROM `".DB_PREFIX."cf_funding`
		WHERE `project_id` = {$tpl['project_id']} AND
					`del_block_id` = 0
		", 'fetch_one');
$tpl['project']['funding'] = ($tpl['project']['funding']==0)?0:$tpl['project']['funding'];

// сколько всего фундеров
$tpl['project']['count_funders'] = (int) $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `id`
		FROM `".DB_PREFIX."cf_funding`
		WHERE `project_id` = {$tpl['project_id']} AND
					`del_block_id` = 0
		GROUP BY `user_id`
		", 'num_rows');

// список фундеров
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `".DB_PREFIX."users`.`user_id`, sum(`amount`) as `amount`, `time`,  `name`, `avatar`
		FROM `".DB_PREFIX."cf_funding`
		LEFT JOIN `".DB_PREFIX."users` ON `".DB_PREFIX."users`.`user_id` = `".DB_PREFIX."cf_funding`.`user_id`
		WHERE `project_id` = {$tpl['project_id']} AND
					`del_block_id` = 0
		GROUP BY `user_id`
		ORDER BY `time` DESC
		LIMIT 100
		");
while ( $row =  $db->fetchArray( $res ) ) {
	$row['time'] = date('d.m.Y H:i', $row['time']);
	if (!$row['avatar'])
		$row['avatar'] = $tpl['cf_url'].'img/noavatar.png';
	if (!$row['name'])
		$row['name'] = 'Noname';
	$tpl['funders'][] = $row;
}

// список комментов
if ($tpl['lang_id']) {
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `".DB_PREFIX."users`.`user_id`, `comment`, `time`, `name`, `avatar`
			FROM `".DB_PREFIX."cf_comments`
			LEFT JOIN `".DB_PREFIX."users` ON `".DB_PREFIX."users`.`user_id` = `".DB_PREFIX."cf_comments`.`user_id`
			WHERE `project_id` = {$tpl['project_id']} AND
						 `lang_id` = {$tpl['lang_id']}
			ORDER BY `time` DESC
			LIMIT 100
			");
	while ( $row =  $db->fetchArray( $res ) ) {
		$row['time'] = date('d.m.Y H:i', $row['time']);
		if (!$row['avatar'])
			$row['avatar'] = $tpl['cf_url'].'img/noavatar.png';
		if (!$row['name'])
			$row['name'] = 'Noname';
		$tpl['comments'][] = $row;
	}
}

// сколько всего комментов на каждом языке
$tpl['project']['lang_comments'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `lang_id`,
					   count(`id`) as `count`
		FROM `".DB_PREFIX."cf_comments`
		WHERE `project_id` = {$tpl['project_id']}
		GROUP BY `lang_id`
		", 'list', array('lang_id', 'count'));
$tpl['project']['count_comments'] = array_sum($tpl['project']['lang_comments']);

// 66 языков
$tpl['cf_lng'] = get_all_cf_lng($db);

// инфа об авторе проекта
$tpl['project']['author'] = get_cf_author_name($db, $tpl['project']['user_id'], $tpl['cf_url']);

// возможно наш юзер фундер
if ($user_id)
	$tpl['project']['funder'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `id`
			FROM `".DB_PREFIX."cf_funding`
			WHERE `project_id` = {$tpl['project_id']} AND
						 `user_id` = {$user_id} AND
						 `del_block_id` = 0
			");

$tpl['comment_data']['type'] = 'cf_comment';
$tpl['comment_data']['type_id'] = ParseData::findType($tpl['comment_data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['pages_array'] = array('home', 'news', 'funders', 'comments');

require_once( ABSPATH . 'templates/cf_page_preview.tpl' );

?>