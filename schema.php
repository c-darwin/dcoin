<?php

defined('DC') or die('');

$queries = array();

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}abuses`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}abuses` (
  `user_id` bigint(20) unsigned NOT NULL,
  `from_user_id` bigint(20) unsigned NOT NULL,
  `comment` varchar(255) CHARACTER SET utf8 NOT NULL,
  `time` int(11) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Абузы на майнеров от майнеров';
";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}admin_blog`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}admin_blog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned NOT NULL,
  `lng` varchar(5) NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 NOT NULL,
  `message` text CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Блог админа';

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}alert_messages`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}alert_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification` tinyint(1) NOT NULL,
  `close` tinyint(1) NOT NULL COMMENT 'Юзер может закрыть сообщение и оно больше не появится',
  `message` text CHARACTER SET utf8 NOT NULL COMMENT 'json. Каждому языку свое сообщение и gen - для тех, на кого языков не хватило',
  `currency_list` varchar(1024) NOT NULL COMMENT 'Для каких валют выводим сообщение. ALL - всем',
  `block_id` int(10) unsigned NOT NULL COMMENT 'Для откатов',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Сообщения от админа, которые выводятся в интерфейсе софта';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}block_chain`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}block_chain` (
  `id` int(11) NOT NULL,
  `hash` binary(32) NOT NULL COMMENT 'Хэш от полного заголовка блока (new_block_id,prev_block_hash,merkle_root,time,user_id,level). Используется как PREV_BLOCK_HASH',
  `head_hash` binary(32) NOT NULL COMMENT 'Хэш от заголовка блока (user_id,block_id,prev_head_hash). Используется для обновления head_hash в info_block при восстановлении после вилки в upd_block_info()',
  `data` longblob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Главная таблица. Хранит цепочку блоков';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}cash_requests`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}cash_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned NOT NULL COMMENT 'Время создания запроса. От него отсчитываем 48 часов',
  `from_user_id` bigint(20) unsigned NOT NULL,
  `to_user_id` bigint(20) unsigned NOT NULL,
  `notification` tinyint(1) unsigned NOT NULL,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `amount` decimal(13,2)  NOT NULL COMMENT 'На эту сумму должны быть выданы наличные',
  `hash_code` binary(32) NOT NULL COMMENT 'Хэш от кода, а сам код пердается при личной встрече. ',
  `status` enum('approved','pending') NOT NULL DEFAULT 'pending' COMMENT 'Если в блоке указан верный код для хэша, то тут будет approved. Rejected нет, т.к. можно и без него понять, что запрос невыполнен, просто посмотрев время',
  `for_repaid_del_block_id`int(11)  unsigned NOT NULL COMMENT 'если больше нет for_repaid ни по одной валюте у данного юзера, то нужно проверить, нет ли у него просроченных cash_requests, которым нужно отметить for_repaid_del_block_id, чтобы cash_request_out не переводил более обещанные суммы данного юзера в for_repaid из-за просроченных cash_requests',
  `del_block_id`int(11)  unsigned NOT NULL COMMENT 'Во время reduction все текущие cash_requests, т.е. по которым не прошло 2 суток удаляются',
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Запросы на обмен DC на наличные';

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}currency`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}currency` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(3) NOT NULL,
  `full_name` varchar(50) NOT NULL,
  `max_other_currencies` tinyint(3) unsigned NOT NULL COMMENT 'Со сколькими валютами данная валюта может майниться',
  `tmp_curs` double NOT NULL,
  `log_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
";
$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_currency`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_currency` (
  `log_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `max_other_currencies` tinyint(3) unsigned NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}daemons`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}daemons` (
  `name` char(15) NOT NULL COMMENT 'Кодовое обозначение демона',
  `script` char(40) NOT NULL COMMENT 'Название скрипта',
  `param` char(5) NOT NULL COMMENT 'Параметры для запуска',
  `pid` int(11) NOT NULL COMMENT 'Pid демона для детекта дублей',
  `time` int(11) NOT NULL COMMENT 'Время последней активности демона',
  `first` tinyint(1) NOT NULL,
  `memory` int(11) NOT NULL,
  `restart` tinyint(1) NOT NULL COMMENT 'Команда демону, что нужно выйти',
  PRIMARY KEY (`script`)
) ENGINE=MYISAM DEFAULT CHARSET=latin1 COMMENT='Демоны';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}faces`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}faces` (
  `user_id` bigint(20) unsigned NOT NULL,
  `race` tinyint(1) NOT NULL COMMENT 'Раса. От 1 до 3',
  `country` tinyint(3) unsigned NOT NULL,
  `version` int(11) NOT NULL COMMENT 'Версия набора точек',
  `status` enum('pending','used') NOT NULL DEFAULT 'pending' COMMENT 'При new_miner ставим pending, при отрицательном завершении юзерского голосования - pending. used ставится только если юзерское голосование завершилось положительно' ,
  `f1` float NOT NULL COMMENT 'Отрезок 1',
  `f2` float NOT NULL,
  `f3` float NOT NULL,
  `f4` float NOT NULL,
  `f5` float NOT NULL,
  `f6` float NOT NULL,
  `f7` float NOT NULL,
  `f8` float NOT NULL,
  `f9` float NOT NULL,
  `f10` float NOT NULL,
  `f11` float NOT NULL,
  `f12` float NOT NULL,
  `f13` float NOT NULL,
  `f14` float NOT NULL,
  `f15` float NOT NULL,
  `f16` float NOT NULL,
  `f17` float NOT NULL,
  `f18` float NOT NULL,
  `f19` float NOT NULL,
  `f20` float NOT NULL,
  `p1` float NOT NULL,
  `p2` float NOT NULL,
  `p3` float NOT NULL,
  `p4` float NOT NULL,
  `p5` float NOT NULL,
  `p6` float NOT NULL,
  `p7` float NOT NULL,
  `p8` float NOT NULL,
  `p9` float NOT NULL,
  `p10` float NOT NULL,
  `p11` float NOT NULL,
  `p12` float NOT NULL,
  `p13` float NOT NULL,
  `p14` float NOT NULL,
  `p15` float NOT NULL,
  `p16` float NOT NULL,
  `p17` float NOT NULL,
  `p18` float NOT NULL,
  `p19` float NOT NULL,
  `p20` float NOT NULL,
  `log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Точки по каждому юзеру';

";



/*
$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}geolocation`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}geolocation` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `latitude` decimal(8,5) NOT NULL,
  `longitude` decimal(8,5) NOT NULL,
  `votes_start_time` int(11) unsigned NOT NULL COMMENT 'Время начала сбора голосов, т.е. время попадния записи в блок',
  `votes_0` int(11) NOT NULL COMMENT 'Голоса за',
  `votes_1` int(11) NOT NULL COMMENT 'Голоса против',
  `status` enum('approved','rejected','pending') NOT NULL DEFAULT 'pending' COMMENT 'pending-идет сбор голосов',
  `block_id` bigint(20) NOT NULL COMMENT 'Блок, в котором была добавлена запись. Для отката, чтобы точно знать, что удалять',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Места, в которых майнер отметился';

";
*/
$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}holidays`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}holidays` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `delete` tinyint(1) NOT NULL COMMENT '1-удалено. нужно для отката',
  `start_time` int(11) unsigned NOT NULL,
  `end_time` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Время, в которое майнер не получает %, т.к. отдыхает';

";




$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}info_block`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}info_block` (
  `hash` binary(32) NOT NULL COMMENT 'Хэш от полного заголовка блока (new_block_id,prev_block_hash,merkle_root,time,user_id,level). Используется как prev_hash',
  `head_hash` binary(32) NOT NULL COMMENT 'Хэш от заголовка блока (user_id,block_id,prev_head_hash)',
  `block_id` int(11) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL COMMENT 'Время создания блока',
  `level` tinyint(4) unsigned NOT NULL COMMENT 'На каком уровне был сгенерирован блок',
  `current_version` varchar(50) NOT NULL DEFAULT '0.0.1' ,
  `sent` tinyint(4) NOT NULL COMMENT 'Был ли блок отправлен нодам, указанным в nodes_connections'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Текущий блок, данные из которого мы уже занесли к себе';

";


$my_queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_new_users`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_new_users` (
  `user_id` int(10) NOT NULL,
  `add_time` int(11) NOT NULL COMMENT 'для удаления старых my_pending',
  `public_key` varchar(3096) NOT NULL COMMENT 'Нужен просто чтобы опознать в блоке зареганного юзера и отметить approved',
  `private_key` varchar(3096) NOT NULL,
  `status` enum('my_pending','approved') NOT NULL DEFAULT 'my_pending'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Чтобы после генерации нового юзера не потерять его приватный ключ можно сохранить его тут';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}promised_amount`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}promised_amount` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `del_block_id` int(11) NOT NULL,
  `user_id` bigint(16) NOT NULL,
  `amount` decimal(13,2) NOT NULL COMMENT 'Обещанная сумма. На неё влияет reduction и она будет урезаться при обновлении max_promised_amount (очень важно на случай деноминации фиата). Если же статус = repaid, то тут храниться кол-во денег, которые майнер отдал. Нужно хранить только чтобы знать общую сумму и не превысить max_promised_amount. Для WOC  amount не нужен, т.к. WOC полностью зависит от max_promised_amount',
  `amount_backup` decimal(13,2) NOT NULL COMMENT 'Нужно для откатов при reduction',
  `currency_id` tinyint(3) unsigned NOT NULL,
  `ps1` smallint (5) unsigned NOT NULL COMMENT 'ID платежной системы, в валюте которой он готов сделать перевод в случае входящего запроса',
  `ps2` smallint (5) unsigned NOT NULL,
  `ps3` smallint (5) unsigned NOT NULL,
  `ps4` smallint (5) unsigned NOT NULL,
  `ps5` smallint (5) unsigned NOT NULL,
  `start_time` int(11) NOT NULL COMMENT 'Используется, когда нужно узнать, кто имеет право голосовать за данную валюту, т.е. прошло ли 60 дней с момента получения статуса miner или repaid(учитывая время со статусом miner). Изменяется при каждой смене статуса. Сущетвует только со статусом mining и repaid. Это защита от атаки клонов, когда каким-то образом 100500 майнеров прошли проверку, добавили какую-то валюту и проголосовали за reduction 90%. 90 дней - это время админу, чтобы заметить и среагировать на такую атаку',
  `status` enum('pending','mining','rejected','repaid','change_geo','suspended') NOT NULL DEFAULT 'pending' COMMENT 'pending - при первом добавлении или при повтороном запросе.  change_geo ставится когда идет смена местоположения, suspended - когда админ разжаловал майнера в юзеры. TDC набегают только когда статус mining, repaid с майнерским или же юзерским % (если статус майнера = passive_miner)',
  `status_backup` enum('pending','mining','rejected','repaid','change_geo','suspended','') NOT NULL DEFAULT '' COMMENT 'Когда админ банит майнера, то в status пишется suspended, а сюда - статус из  status',
  `tdc_amount` decimal(13,2) NOT NULL COMMENT 'Набежавшая сумма за счет % роста. Пересчитывается при переводе TDC на кошелек',
  `tdc_amount_backup` decimal(13,2) NOT NULL COMMENT 'Нужно для откатов при reduction',
  `tdc_amount_update` int(11) unsigned NOT NULL COMMENT 'Время обновления tdc_amount',
  `video_type` varchar(100) NOT NULL,
  `video_url_id` varchar(255) NOT NULL COMMENT 'Если пусто, то видео берем по ID юзера.flv. На видео майнер говорит, что хочет майнить выбранную валюту',
  `votes_start_time` int(11) NOT NULL COMMENT 'При каждой смене местоположения начинается новое голосование. Менять местоположение можно не чаще раза в сутки',
  `votes_0` int(11) NOT NULL,
  `votes_1` int(11) NOT NULL,
  `woc_block_id` int(11) NOT NULL COMMENT 'Нужно для отката добавления woc',
  `cash_request_out_time` int(11) NOT NULL COMMENT 'Любой cash_request_out приводит к появлению данной записи у получателя запроса. Убирается она только после того, как у юзера не остается непогашенных cash_request-ов. Нужно для reduction_generator, чтобы учитывать только те обещанные суммы, которые еще не заморожены невыполенными cash_request-ами',
  `cash_request_out_time_backup` int(11) NOT NULL COMMENT 'Используется в new_reduction()',
  `cash_request_in_block_id` int(11) NOT NULL COMMENT 'Нужно для отката cash_request_in',
  `del_mining_block_id` int(11) NOT NULL COMMENT 'Нужно для отката del_promised_amount',
  `log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_promised_amount`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_promised_amount` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `del_block_id` int(11) NOT NULL,
  `amount` decimal(13,2) NOT NULL,
  `amount_backup` decimal(13,2) NOT NULL,
  `start_time` int(11) NOT NULL,
  `status` enum('pending','mining','rejected','repaid','change_geo','suspended')  NOT NULL,
  `status_backup` enum('pending','mining','rejected','repaid','change_geo','suspended','')  NOT NULL DEFAULT '',
  `tdc_amount` decimal(13,2)  NOT NULL,
  `tdc_amount_update` int(11) NOT NULL,
  `video_type` varchar(100) NOT NULL,
  `video_url_id` varchar(255) NOT NULL COMMENT 'Если пусто, то видео берем по ID юзера.flv. На видео майнер говорит, что хочет майнить выбранную валюту',
  `votes_start_time` int(11) NOT NULL COMMENT 'При каждой смене местоположения начинается новое голосование',
  `votes_0` int(11) NOT NULL,
  `votes_1` int(11) NOT NULL,
  `cash_request_out_time` int(11) NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;";





$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_faces`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_faces` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `race` tinyint(1) NOT NULL,
  `country` tinyint(3) unsigned NOT NULL,
  `version` int(11) NOT NULL COMMENT 'Версия набора точек',
  `status` enum('approved','rejected','pending') NOT NULL,
  `f1` float NOT NULL COMMENT 'Отрезок 1',
  `f2` float NOT NULL,
  `f3` float NOT NULL,
  `f4` float NOT NULL,
  `f5` float NOT NULL,
  `f6` float NOT NULL,
  `f7` float NOT NULL,
  `f8` float NOT NULL,
  `f9` float NOT NULL,
  `f10` float NOT NULL,
  `f11` float NOT NULL,
  `f12` float NOT NULL,
  `f13` float NOT NULL,
  `f14` float NOT NULL,
  `f15` float NOT NULL,
  `f16` float NOT NULL,
  `f17` float NOT NULL,
  `f18` float NOT NULL,
  `f19` float NOT NULL,
  `f20` float NOT NULL,
  `p1` float NOT NULL,
  `p2` float NOT NULL,
  `p3` float NOT NULL,
  `p4` float NOT NULL,
  `p5` float NOT NULL,
  `p6` float NOT NULL,
  `p7` float NOT NULL,
  `p8` float NOT NULL,
  `p9` float NOT NULL,
  `p10` float NOT NULL,
  `p11` float NOT NULL,
  `p12` float NOT NULL,
  `p13` float NOT NULL,
  `p14` float NOT NULL,
  `p15` float NOT NULL,
  `p16` float NOT NULL,
  `p17` float NOT NULL,
  `p18` float NOT NULL,
  `p19` float NOT NULL,
  `p20` float NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Точки по каждому юзеру';

";



$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_miners_data`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_miners_data` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `miner_id` int(11) NOT NULL,
  `reg_time` int(11) NOT NULL,
  `status` enum('miner','user','passive_miner','suspended_miner') NOT NULL,
  `node_public_key` varbinary(512) NOT NULL,
  `face_hash` varchar(128) NOT NULL,
  `profile_hash` varchar(128) NOT NULL,
  `photo_block_id` int(11) NOT NULL,
  `photo_max_miner_id` int(11) NOT NULL,
  `miners_keepers` tinyint(3) unsigned NOT NULL,
  `face_coords` varchar(1024) NOT NULL,
  `profile_coords` varchar(1024) NOT NULL,
  `video_type` varchar(100) NOT NULL,
  `video_url_id` varchar(255) NOT NULL,
  `host` varchar(255) CHARACTER SET utf8 NOT NULL,
  `latitude` decimal(8,5) NOT NULL,
  `longitude` decimal(8,5) NOT NULL,
  `country` tinyint(3) unsigned NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

";



$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_minute`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_minute` (
  `user_id` bigint(20) NOT NULL,
  `count` int(11) NOT NULL COMMENT 'Сколько новых транзакций сделал юзер за минуту',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";
/*
$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_minute_invite`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_minute_invite` (
  `invite` char(128) NOT NULL,
  `count` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Не принимаем запросов на новый акк на 1инвайт более 1 в мину';

";
*/
/*
$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_pct_votes`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_pct_votes` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned NOT NULL,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `miner_pct` int(10) unsigned NOT NULL,
  `user_pct` int(10) unsigned NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

";
*/

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_recycle_bin`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_recycle_bin` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `profile_file_name` varchar(64) NOT NULL,
  `face_file_name` varchar(64) NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_spots_compatibility`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_spots_compatibility` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `version` double NOT NULL,
  `example_spots` text NOT NULL,
  `compatibility` text NOT NULL,
  `segments` text NOT NULL,
  `tolerances` text NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` int(11) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_actualization`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_actualization` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_abuses`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_abuses` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='можно создавать только 1 тр-ю с абузами за 24h';

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_for_repaid_fix`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_for_repaid_fix` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_commission`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_commission` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";




$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_promised_amount`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_promised_amount` (
  `user_id` bigint(20) NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Для учета кол-ва запр. на доб. / удал. / изменение promised_amount. Чистим кроном';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_cash_requests`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_cash_requests` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_change_geolocation`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_change_geolocation` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_holidays`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_holidays` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_message_to_admin`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_message_to_admin` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_mining`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_mining` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_change_host`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_change_host` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_new_miner`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_new_miner` (
  `user_id` bigint(20) NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_new_user`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_new_user` (
  `user_id` bigint(20) NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_node_key`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_node_key` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_primary_key`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_primary_key` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_votes`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_votes` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Храним данные за 1 сутки';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_votes_miners`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_votes_miners` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Лимиты для повторых запросов, за которые голосуют ноды';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_votes_nodes`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_votes_nodes` (
  `user_id` bigint(20) NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Голоса от нодов';

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_votes_complex`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_votes_complex` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT 'Набор голосов из miner_pct/user_pct/max_promised_amount/max_other_currencies/reduction. Валюты тут не учитываются. Важен сам факт комплексного голосования';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_transactions`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_transactions` (
  `hash` binary(16) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Храним данные за сутки, чтобы избежать дублей.';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_users`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_users` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `public_key_0` varbinary(512) NOT NULL,
  `public_key_1` varbinary(512) NOT NULL,
  `public_key_2` varbinary(512) NOT NULL,
  `referral` bigint(20) NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_variables`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_variables` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `data` text NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_votes`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_votes` (
  `user_id` bigint(20) NOT NULL COMMENT 'Кто голосует',
  `voting_id` bigint(20) NOT NULL COMMENT 'За что голосует. тут может быть id geolocation и пр',
  `type` enum('votes_miners','promised_amount') NOT NULL COMMENT 'Нужно для voting_id',
  `del_block_id` int(11) NOT NULL COMMENT 'В каком блоке было удаление. Нужно для чистки по крону старых данных и для откатов.',
  PRIMARY KEY (`user_id`,`voting_id`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Чтобы 1 юзер не смог проголосовать 2 раза за одно и тоже';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_wallets`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_wallets` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `amount` decimal(15,2) UNSIGNED NOT NULL ,
  `amount_backup` decimal(15,2)  NOT NULL,
  `last_update` int(11) NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` bigint(20) NOT NULL COMMENT 'Id предыдщуего log_id, который запишем в wallet',
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Таблица, где будет браться инфа при откате блока';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}main_lock`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}main_lock` (
  `lock_time` int(10) unsigned NOT NULL,
  `script_name` varchar(100) NOT NULL,
  `uniq` tinyint(4) NOT NULL,
  UNIQUE KEY `uniq` (`uniq`)
) ENGINE=MYISAM DEFAULT CHARSET=latin1 COMMENT='Полная блокировка на поступление новых блоков/тр-ий';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}miners`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}miners` (
  `miner_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Если есть забаненные, то на их место становятся новички, т.о. все miner_id будут заняты без пробелов',
  `active` tinyint(1) NOT NULL COMMENT '1 - активен, 0 - забанен',
  `log_id` int(11) NOT NULL COMMENT 'Без log_id нельзя определить, был ли апдейт в табле miners или же инсерт, т.к. по AUTO_INCREMENT не понять, т.к. обновление может быть в самой последней строке',
  PRIMARY KEY (`miner_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_miners`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_miners` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` int(11) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}miners_data`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}miners_data` (
  `user_id` int(11) NOT NULL,
  `miner_id` int(11) NOT NULL COMMENT 'Из таблицы miners',
  `reg_time` int(11) NOT NULL COMMENT 'Время, когда майнер получил miner_id по итогам голосования. Определеяется один раз и не меняется. Нужно, чтобы не давать новым майнерам генерить тр-ии регистрации новых юзеров и исходящих запросов',
  `ban_block_id` int(11) NOT NULL COMMENT 'В каком блоке майнер был разжалован в suspended_miner. Нужно для исключения пересечения тр-ий разжалованного майнера и самой тр-ии разжалования',
  `status` enum('miner','user','passive_miner','suspended_miner') NOT NULL DEFAULT 'user' COMMENT 'Измнеения вызывают персчет TDC в promised_amount',
  `node_public_key` varbinary(512) NOT NULL,
  `face_hash` varchar(128) NOT NULL COMMENT 'Хэш фото юзера',
  `profile_hash` varchar(128) NOT NULL,
  `photo_block_id` int(11) NOT NULL COMMENT 'Блок, в котором было добавлено фото',
  `photo_max_miner_id` bigint(20) NOT NULL COMMENT 'Макс. майнер id в момент добавления фото. Это и photo_block_id нужны для определения 10-и нодов, где лежат фото',
  `miners_keepers` tinyint(3) unsigned NOT NULL COMMENT 'Скольким майнерам копируем фото юзера. По дефолту = 10',
  `face_coords` varchar(1024) NOT NULL,
  `profile_coords` varchar(1024) NOT NULL,
  `video_type` varchar(100) NOT NULL,
  `video_url_id` varchar(255) NOT NULL COMMENT 'Если пусто, то видео берем по ID юзера.flv',
  `host` varchar(255) CHARACTER SET utf8 NOT NULL COMMENT 'адрес хоста или IP, где находится нод майнера',
  `latitude` decimal(8,5) NOT NULL COMMENT 'Местоположение можно сменить без проблем, но это одновременно ведет запуск голосования у promised_amount по всем валютам, где статус mining или hold',
  `longitude` decimal(8,5) NOT NULL,
  `country` tinyint(3) unsigned NOT NULL,
  `log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";

$my_queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_admin_messages`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_admin_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `add_time` int(11) NOT NULL COMMENT 'для удаления старых my_pending',
  `parent_id` int(11) NOT NULL,
  `subject` varchar(255) CHARACTER SET utf8 NOT NULL COMMENT 'Появляется после расшифровки',
  `message` text CHARACTER SET utf8 NOT NULL,
  `message_type` tinyint(1) NOT NULL COMMENT '0-баг-репорты',
  `message_subtype` tinyint(1) NOT NULL,
  `encrypted` blob NOT NULL,
  `decrypted` tinyint(1) NOT NULL,
  `status` enum('approved','my_pending') NOT NULL DEFAULT 'my_pending',
  `type` enum('from_admin','to_admin') NOT NULL DEFAULT 'to_admin',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Общение с админом, баг-репорты и пр.';

";


$my_queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_promised_amount`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_promised_amount` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `add_time` int(11) NOT NULL COMMENT 'для удаления старых my_pending',
  `amount` decimal(13,2) NOT NULL,
  `currency_id` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Просто показываем, какие данные еще не попали в блоки. Те, что уже попали тут удалены';

";


$my_queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_cash_requests`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_cash_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `add_time` int(11) NOT NULL COMMENT 'для удаления старых my_pending',
  `time` int(11) unsigned NOT NULL COMMENT 'Время попадания в блок',
  `notification` tinyint(1) NOT NULL,
  `to_user_id` bigint(20) NOT NULL,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `amount` decimal(13,2) NOT NULL,
  `comment` text CHARACTER SET utf8 NOT NULL,
  `comment_status` enum('encrypted','decrypted') NOT NULL DEFAULT 'decrypted',
  `code` varchar(64) NOT NULL COMMENT 'Секретный код, который нужно передать тому, кто отдает фиат',
  `hash_code` varchar(64) NOT NULL,
  `status` enum('my_pending','pending','approved','rejected') NOT NULL DEFAULT 'my_pending',
  `cash_request_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

";


$my_queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_ddos_protection`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_ddos_protection` (
  `ip` int(11) NOT NULL COMMENT 'Раз в минуту удаляется',
  `req` int(11) NOT NULL COMMENT 'Кол-во запросов от ip. ',
  PRIMARY KEY (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Защита от случайного ддоса';

";

$my_queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_dc_transactions`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_dc_transactions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `status` enum('pending','approved') NOT NULL DEFAULT 'approved' COMMENT 'pending - только при отправки DC с нашего кошелька, т.к. нужно показать юзеру, что запрос принят',
  `notification` tinyint(1) NOT NULL COMMENT 'Уведомления по sms и email',
  `type` enum('cash_request','from_mining_id','from_repaid','from_user','node_commission','system_commission','referral') NOT NULL,
  `type_id` bigint(20) NOT NULL,
  `to_user_id` bigint(20) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `commission` decimal(15,2) NOT NULL,
  `del_block_id` int(11) unsigned NOT NULL COMMENT 'Блок, в котором данная транзакция была отменена',
  `time` int(10) unsigned NOT NULL COMMENT 'Время, когда транзакцию создал юзер',
  `block_id` int(10) unsigned NOT NULL COMMENT 'Блок, в котором данная транзакция была запечатана. При откате блока все транзакции с таким block_id будут удалены',
  `currency_id` tinyint(3) unsigned NOT NULL,
  `comment` text CHARACTER SET utf8 NOT NULL COMMENT 'Если это перевод средств между юзерами или это комиссия, то тут будет расшифрованный комментарий',
  `comment_status` enum('encrypted','decrypted') NOT NULL DEFAULT 'decrypted',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Нужно только для отчетов, которые показываются юзеру';

";

/*
$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}my_geolocation`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}my_geolocation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `latitude` decimal(8,5) NOT NULL,
  `longitude` decimal(8,5) NOT NULL,
  `current` tinyint(1) NOT NULL,
  `current_status` enum('','approved','my_pending') NOT NULL DEFAULT '' COMMENT 'Статус нашей заявки сделать данное местоположение текущим. Не путить со статусом всего geolocation',
  `status` enum('approved','rejected','my_pending','pending') NOT NULL DEFAULT 'my_pending',
  `geolocation_id` bigint(20) NOT NULL COMMENT 'Нужно при смене текущего местоположения',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

";*/


$my_queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_holidays`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_holidays` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `add_time` int(11) NOT NULL COMMENT 'для удаления старых my_pending',
  `start_time` int(10) unsigned NOT NULL,
  `end_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

";



$my_queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_keys`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `add_time` int(11) NOT NULL COMMENT 'для удаления старых my_pending',
  `notification` tinyint(1) NOT NULL,
  `public_key` varbinary(512) NOT NULL COMMENT 'Нужно для поиска в users',
  `private_key` varchar(3096) NOT NULL COMMENT 'Хранят те, кто не боятся',
  `password_hash` varchar(64) NOT NULL COMMENT 'Хранят те, кто не боятся',
  `status` enum('my_pending','approved') NOT NULL DEFAULT 'my_pending',
  `my_time` int(10) unsigned NOT NULL COMMENT 'Время создания записи',
  `time` int(10) unsigned NOT NULL COMMENT 'Время из блока',
  `block_id` int(11) NOT NULL COMMENT 'Для откатов и определения крайнего',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Ключи для авторизации юзера. Используем крайний';

";


$my_queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_log`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_log` (
  `id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `data` varchar(1024) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Всё, что шлется на мыло - логируется тут';

";




$my_queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_node_keys`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_node_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `add_time` int(11) NOT NULL COMMENT 'для удаления старых my_pending',
  `public_key` varbinary(512) NOT NULL,
  `private_key` varchar(3096) NOT NULL,
  `status` enum('my_pending','approved') NOT NULL DEFAULT 'my_pending',
  `my_time` int(11) NOT NULL COMMENT 'Время создания записи',
  `time` bigint(20) NOT NULL,
  `block_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

";


$my_queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_notifications`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_notifications` (
  `name` varchar(200) NOT NULL,
  `email` tinyint(1) NOT NULL,
  `sms` tinyint(1) NOT NULL,
  `sort` tinyint(3) unsigned NOT NULL,
  `important` tinyint(1) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";


$my_queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_complex_votes`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_complex_votes` (
  `last_voting` int(11) unsigned NOT NULL COMMENT 'Время последнего голосования',
  `notification` tinyint(1) NOT NULL COMMENT 'Уведомление о том, что со времени последнего голоса прошло более 2 недель',
  PRIMARY KEY (`last_voting`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Нужно только для отсылки уведомлений, что пора голосовать';

";

/*
 * уведомлять не нужно, это не рядовая процедура. Уведомлять будет админ через алерты
$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}my_votes_reduction`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}my_votes_reduction` (
  `last_voting` int(11) unsigned NOT NULL COMMENT 'Время последнего голосования',
  `notification` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Уведомление о том, что со времени последнего голоса прошло более 2 недель',
  PRIMARY KEY (`last_voting`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Нужно только для отсылки уведомлений, что пора голосовать';

";
*/

$my_queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_table`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_table` (
  `user_id` bigint(20) NOT NULL,
  `miner_id` int(11) NOT NULL,
  `status` enum('bad_key','my_pending','miner','user','passive_miner','suspended_miner') NOT NULL DEFAULT 'my_pending' COMMENT 'bad_key - это когда юзер зарегался по чужому ключу, который нашел в паблике, либо если указал старый ключ вместо нового',
  `race` tinyint(1) NOT NULL COMMENT 'Раса. От 1 до 3',
  `country` tinyint(3) unsigned NOT NULL COMMENT 'Используется только локально для проверки майнеров из нужной страны',
  `notification_status` tinyint(1) NOT NULL COMMENT 'Уведомления. При смене статуса обнуляется',
  `mail_code` varchar(100) NOT NULL,
  `login_code` int(11) NOT NULL COMMENT 'Для подписания при авторизации',
  `email` varchar(100) NOT NULL,
  `notification_email` tinyint(1) NOT NULL,
  `host` varchar(255) NOT NULL COMMENT 'Хост юзера, по которому он доступен из вне',
  `host_status` enum('my_pending','approved') NOT NULL DEFAULT 'my_pending',
  `geolocation` varchar(200) NOT NULL COMMENT 'Текущее местонахождение майнера',
  `geolocation_status` enum('my_pending','approved') NOT NULL DEFAULT 'my_pending',
  `location_country` tinyint(3) unsigned NOT NULL,
  `invite` char(128) NOT NULL,
  `face_coords` varchar(1024) NOT NULL COMMENT 'Точки, которе юзер нанес на свое фото',
  `node_voting_send_request` int(10) unsigned NOT NULL COMMENT 'Когда мы отправили запрос в DC-сеть на присвоение нам статуса \"майнер\"',
  `profile_coords` varchar(1024) NOT NULL COMMENT 'Точки, которе юзер нанес на свое фото',
  `video_url_id` varchar(255) NOT NULL COMMENT 'Видео, где показывается лицо юзера',
  `video_type` varchar(100) NOT NULL,
  `lang` char(2) NOT NULL COMMENT 'Запоминаем язык для юзера',
  `use_smtp` tinyint(1) NOT NULL,
  `smtp_server` varchar(100) NOT NULL,
  `smtp_port` int(11) NOT NULL,
  `smtp_ssl` tinyint(1) NOT NULL,
  `smtp_auth` tinyint(1) NOT NULL,
  `smtp_username` varchar(100) NOT NULL,
  `smtp_password` varchar(100) NOT NULL,
  `miner_pct_id` smallint(5) NOT NULL,
  `user_pct_id` smallint(5) NOT NULL,
  `repaid_pct_id` smallint(5) NOT NULL,
  `api_token_hash` varchar(64) NOT NULL,
  `sms_http_get_request` varchar(255) NOT NULL,
  `notification_sms_http_get_request` int(11) NOT NULL,
  `show_sign_data` tinyint(1) NOT NULL COMMENT 'Если 0, тогда не показываем данные для подписи, если у юзера только один праймари ключ',
  `uniq` set('1') NOT NULL DEFAULT '1',
  UNIQUE KEY `uniq` (`uniq`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}new_version`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}new_version` (
  `version` varchar(50) NOT NULL,
  `alert` tinyint(1) NOT NULL,
  `notification` tinyint(1) NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Сюда пишется новая версия, которая загружена в public';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}nodes_ban`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}nodes_ban` (
  `user_id` int(11) NOT NULL,
  `ban_start` int(10) unsigned NOT NULL,
  `info` text NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Баним на 1 час тех, кто дает нам данные с ошибками';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}nodes_connection`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}nodes_connection` (
  `host` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Чтобы получать открытый ключ, которым шифруем блоки и тр-ии',
  `block_id` int(11) NOT NULL COMMENT 'ID блока, который есть у данного нода. Чтобы слать ему только >=',
  PRIMARY KEY (`host`)
) ENGINE=MEMORY DEFAULT CHARSET=latin1 COMMENT='Ноды, которым шлем данные и от которых принимаем данные';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}pct`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}pct` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned NOT NULL COMMENT 'Время блока, в котором были новые %',
  `notification` tinyint(1) NOT NULL,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `miner` decimal(13,13) NOT NULL,
  `user` decimal(13,13) NOT NULL,
  `block_id` int(10) unsigned NOT NULL COMMENT 'Нужно для откатов',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='% майнера, юзера. На основе  pct_votes';

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}max_promised_amounts`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}max_promised_amounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned NOT NULL COMMENT 'Время блока, в котором были новые max_promised_amount',
  `notification` tinyint(1) NOT NULL,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `amount` int(11) NOT NULL,
  `block_id` int(10) unsigned NOT NULL COMMENT 'Нужно для откатов',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='На основе votes_max_promised_amount';

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}max_other_currencies_time`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}max_other_currencies_time` (
  `time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`time`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Время последнего обновления max_other_currencies_time в currency ';

";
/*
$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}max_other_currencies`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}max_other_currencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned NOT NULL COMMENT 'Время блока, в котором были новые max_other_currencies',
  `notification` tinyint(1) NOT NULL,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `count` int(11) NOT NULL,
  `block_id` int(10) unsigned NOT NULL COMMENT 'Нужно для откатов',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='На основе votes_max_other_currencies';

";*/

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}reduction`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}reduction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned NOT NULL COMMENT 'Время блока, в котором было произведено уполовинивание',
  `notification` tinyint(1) NOT NULL,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `pct` tinyint(2) unsigned NOT NULL,
  `block_id` int(10) unsigned NOT NULL COMMENT 'Нужно для откатов',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Когда была последняя процедура урезания для конкретной валюты. Чтобы отсчитывать 2 недели до следующей';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}votes_miner_pct`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}votes_miner_pct` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(11) unsigned NOT NULL COMMENT 'Нужно только для того, чтобы определять, голосовал ли юзер или нет. От этого зависит, будет он получать майнерский или юзерский %',
  `currency_id` tinyint(3) unsigned NOT NULL,
  `pct`  decimal(13,13) NOT NULL,
  `log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`,`currency_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Голосвание за %. Каждые 14 дней пересчет';
";
$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_votes_miner_pct`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_votes_miner_pct` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `time` int(11) unsigned NOT NULL,
  `pct`  decimal(13,13) NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

";




$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}votes_user_pct`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}votes_user_pct` (
  `user_id` bigint(20) unsigned NOT NULL,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `pct`  decimal(13,13) NOT NULL,
  `log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`,`currency_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Голосвание за %. Каждые 14 дней пересчет';
";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_votes_user_pct`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_votes_user_pct` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `pct`  decimal(13,13) NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
";



$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}votes_reduction`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}votes_reduction` (
  `user_id` bigint(20) unsigned NOT NULL,
  `time` int(11) unsigned NOT NULL COMMENT 'Учитываются только свежие голоса, т.е. один голос только за одно урезание',
  `currency_id` tinyint(3) unsigned NOT NULL,
  `pct` tinyint(2) unsigned NOT NULL,
  `log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`,`currency_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Голосвание за уполовинивание денежной массы. Каждые 14 дней пересчет';
";
$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_votes_reduction`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_votes_reduction` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(11) unsigned NOT NULL,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `pct` tinyint(2) unsigned NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` bigint(20) NOT NULL,
   PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='';
";



$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}votes_max_promised_amount`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}votes_max_promised_amount` (
  `user_id` bigint(20) unsigned NOT NULL,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `amount`  int(11) unsigned NOT NULL COMMENT 'Возможные варианты задаются в скрипте, иначе будут проблемы с поиском варианта-победителя',
  `log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`,`currency_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT 'Раз в 2 неделе на основе этих голосов обновляетя currency.max_promised_amount';
";
$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_votes_max_promised_amount`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_votes_max_promised_amount` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `amount`  int(11) unsigned NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` bigint(20) NOT NULL,
   PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='';
";



$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}votes_max_other_currencies`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}votes_max_other_currencies` (
  `user_id` bigint(20) unsigned NOT NULL,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `count`  int(11) unsigned NOT NULL COMMENT 'Возможные варианты задаются в скрипте, иначе будут проблемы с поиском варианта-победителя',
  `log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`,`currency_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT 'Раз в 2 неделе на основе этих голосов обновляетя currency.max_other_currencies';
";
$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_votes_max_other_currencies`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_votes_max_other_currencies` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `count`  int(11) unsigned NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` bigint(20) NOT NULL,
   PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='';
";




$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}points`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}points` (
  `user_id` bigint(20) NOT NULL,
  `time_start` int(11) unsigned NOT NULL COMMENT 'От какого времени отсчитывается 1 месяц',
  `points` int(11) NOT NULL COMMENT 'Баллы, полученные майнером за голосования',
  `log_id` bigint(20)  NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Баллы майнеров, по которым решается - получат они майнерские % или юзерские';
";
$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_points`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_points` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `time_start` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` int(11) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}points_status`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}points_status` (
  `user_id` bigint(20) NOT NULL,
  `time_start` int(11) NOT NULL COMMENT 'Время начала действия статуса. До какого времени действует данный статус определяем простым добавлением в массив времени, которое будет через 30 дней',
  `status` enum('user','miner') NOT NULL DEFAULT 'user',
  `block_id` int(11) NOT NULL COMMENT 'Нужно для удобного отката'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Статусы юзеров на основе подсчета points';

";



$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}queue_blocks`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}queue_blocks` (
  `head_hash` binary(32) NOT NULL,
  `hash` binary(32) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `block_id` int(11) NOT NULL,
  PRIMARY KEY (`head_hash`,`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Блоки, которые мы должны забрать у указанных нодов';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}queue_testblock`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}queue_testblock` (
  `head_hash` binary(32) NOT NULL COMMENT 'Хэш от заголовка блока (user_id,block_id,prev_head_hash)',
  `data` longblob NOT NULL,
  PRIMARY KEY (`head_hash`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Очередь на фронтальную проверку соревнующихся блоков';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}queue_tx`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}queue_tx` (
  `hash` binary(16) NOT NULL COMMENT 'md5 от тр-ии',
  `high_rate` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Если 1, значит это админская тр-ия',
  `data` longblob NOT NULL,
  `_tmp_node_user_id` VARCHAR(255),
  PRIMARY KEY (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Тр-ии, которые мы должны проверить';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}recycle_bin`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}recycle_bin` (
  `user_id` bigint(20) NOT NULL,
  `profile_file_name` varchar(64) NOT NULL,
  `face_file_name` varchar(64) NOT NULL,
  `log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}spots_compatibility`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}spots_compatibility` (
  `version` int(11) NOT NULL AUTO_INCREMENT,
  `example_spots` text NOT NULL COMMENT 'Точки, которые наносим на 2 фото-примера (анфас и профиль)',
  `compatibility` text NOT NULL COMMENT 'С какими версиями совместимо',
  `segments` text NOT NULL COMMENT 'Нужно для составления отрезков в new_miner()',
  `tolerances` text NOT NULL COMMENT 'Допустимые расхождения между точками при поиске фото-дублей',
  `log_id` int(11) NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Совместимость текущей версии точек с предыдущими';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}testblock`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}testblock` (
  `block_id` int(11) NOT NULL COMMENT 'ID тестируемого блока',
  `time` int(10) unsigned NOT NULL COMMENT 'Время, когда блок попал сюда',
  `level` tinyint(4) NOT NULL COMMENT 'Пишем сюда для использования при формировании заголовка',
  `user_id` int(11) NOT NULL COMMENT 'По id вычисляем хэш шапки',
  `header_hash` binary(32) NOT NULL COMMENT 'Хэш шапки, им меряемся, у кого меньше - тот круче. Хэш генерим у себя, при получении данных блока',
  `signature` blob NOT NULL COMMENT 'Подпись блока юзером, чей минимальный хэш шапки мы приняли',
  `mrkl_root` binary(32) NOT NULL COMMENT 'Хэш тр-ий. Чтобы каждый раз не проверять теже самые данные, просто сравниваем хэши',
  `status` enum('active','pending') NOT NULL DEFAULT 'active' COMMENT 'Указание скрипту testblock_disseminator.php',
  `uniq` tinyint(1) NOT NULL,
  PRIMARY KEY (`block_id`),
  UNIQUE KEY `uniq` (`uniq`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Нужно на этапе соревнования, у кого меньше хэш';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}testblock_lock`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}testblock_lock` (
  `lock_time` int(10) unsigned NOT NULL,
  `script_name` varchar(30) NOT NULL,
  `uniq` tinyint(4) NOT NULL,
  UNIQUE KEY `uniq` (`uniq`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}transactions`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}transactions` (
  `hash` binary(16) NOT NULL COMMENT 'Все хэши из этой таблы шлем тому, у кого хотим получить блок (т.е. недостающие тр-ии для составления блока)',
  `data` longblob NOT NULL COMMENT 'Само тело тр-ии',
  `verified` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Оставшиеся после прихода нового блока тр-ии отмечаются как \"непроверенные\" и их нужно проверять по новой',
  `used` tinyint(1) NOT NULL COMMENT 'После того как попадют в блок, ставим 1, а те, у которых уже стояло 1 - удаляем',
  `high_rate` tinyint(1) NOT NULL COMMENT '1 - админские, 0 - другие',
  `for_self_use` tinyint(1) NOT NULL COMMENT 'для new_pct(pct_generator.php), т.к. эта тр-ия валидна только вместе с блоком, который сгенерил тот, кто сгенерил эту тр-ию',
  `type` tinyint(4) NOT NULL COMMENT 'Тип тр-ии. Нужно для недопущения попадения в блок 2-х тр-ий одного типа от одного юзера',
  `user_id` tinyint(4) NOT NULL COMMENT 'Нужно для недопущения попадения в блок 2-х тр-ий одного типа от одного юзера',
  `third_var` int(11) NOT NULL COMMENT 'Для исключения пересения в одном блоке удаления обещанной суммы и запроса на её обмен на DC. И для исключения голосования за один и тот же объект одним и тем же юзеров и одном блоке',
  `counter` tinyint(3) NOT NULL COMMENT 'Чтобы избежать зацикливания при проверке тр-ии: verified=1, новый блок, verified=0. При достижении 10-и - удаляем тр-ию ',
  `sent` tinyint(1) NOT NULL COMMENT 'Была отправлена нодам, указанным в nodes_connections',
  PRIMARY KEY (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Все незанесенные в блок тр-ии, которые у нас есть';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}transactions_testblock`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}transactions_testblock` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Порядок следования очень важен',
  `hash` binary(16) NOT NULL COMMENT 'md5 для обмена только недостающими тр-ми',
  `data` longblob NOT NULL,
  `type` tinyint(4) NOT NULL COMMENT 'Тип тр-ии. Нужно для недопущения попадения в блок 2-х тр-ий одного типа от одного юзера',
  `user_id` tinyint(4) NOT NULL COMMENT 'Нужно для недопущения попадения в блок 2-х тр-ий одного типа от одного юзера',
  `third_var` int(11) NOT NULL COMMENT 'Для исключения пересения в одном блоке удаления обещанной суммы и запроса на её обмен на DC. И для исключения голосования за один и тот же объект одним и тем же юзеров и одном блоке',
    UNIQUE KEY `hash` (`hash`),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Тр-ии, которые используются в текущем testblock';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_commission`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_commission` (
   `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `commission` text NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'В каком блоке было занесено. Нужно для удаления старых данных',
  `prev_log_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Каждый майнер определяет, какая комиссия с тр-ий будет доставаться ему, если он будет генерить блок';


";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}commission`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}commission` (
  `user_id` bigint(20) unsigned NOT NULL,
  `commission` text NOT NULL COMMENT 'Комиссии по всем валютам в json. Если какой-то валюты нет в списке, то комиссия будет равна нулю. currency_id, %, мин., макс.',
  `log_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Каждый майнер определяет, какая комиссия с тр-ий будет доставаться ему, если он будет генерить блок';

";

$my_queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_commission`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_commission` (
  `currency_id` int(11) unsigned NOT NULL,
  `pct` float NOT NULL,
  `min` float NOT NULL,
  `max` float NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Каждый майнер определяет, какая комиссия с тр-ий будет доставаться ему, если он будет генерить блок';

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}users`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}users` (
  `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'На него будут слаться деньги',
  `public_key_0` varbinary(512) NOT NULL COMMENT 'Открытый ключ которым проверяются все транзакции от юзера',
  `public_key_1` varbinary(512) NOT NULL COMMENT '2-й ключ, если есть',
  `public_key_2` varbinary(512) NOT NULL COMMENT '3-й ключ, если есть',
  `referral` bigint(20) NOT NULL COMMENT 'Тот, кто зарегал данного юзера и теперь получает с него рефские',
  `log_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}variables`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}variables` (
  `name` varchar(35) NOT NULL,
  `value` text NOT NULL,
  `comment` varchar(255) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}votes_miners`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}votes_miners` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('node_voting','user_voting') NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'За кого голосуем',
  `votes_start_time` int(10) unsigned NOT NULL,
  `votes_0` int(11) unsigned NOT NULL,
  `votes_1` int(11) unsigned NOT NULL,
  `votes_end` tinyint(1) unsigned NOT NULL,
  `end_block_id` bigint(20) unsigned NOT NULL COMMENT 'В каком блоке мы выставили принудительное end для node',
  `cron_checked_time` int(11) NOT NULL COMMENT 'По крону проверили, не нужно ли нам скачать фотки юзера к себе на сервер',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Отдел. от miners_data, чтобы гол. шли точно за свежие данные';

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}votes_referral`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}votes_referral` (
  `user_id` bigint(20) unsigned NOT NULL,
  `first` tinyint(2) unsigned NOT NULL,
  `second` tinyint(2) unsigned NOT NULL,
  `third` tinyint(2) unsigned NOT NULL,
  `log_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Голосвание за рефские %. Каждые 14 дней пересчет';

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_votes_referral`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_votes_referral` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `first` tinyint(2) unsigned NOT NULL,
  `second` tinyint(2) unsigned NOT NULL,
  `third` tinyint(2) unsigned NOT NULL,
  `block_id` int(11) NOT NULL,
  `prev_log_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}referral`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}referral` (
  `first` tinyint(2) unsigned NOT NULL,
  `second` tinyint(2) unsigned NOT NULL,
  `third` tinyint(2) unsigned NOT NULL,
  `log_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_referral`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_referral` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `first` tinyint(2) unsigned NOT NULL,
  `second` tinyint(2) unsigned NOT NULL,
  `third` tinyint(2) unsigned NOT NULL,
  `block_id` int(11) NOT NULL,
  `prev_log_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}install`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}install` (
  `progress` varchar(10) NOT NULL COMMENT 'На каком шаге остановились'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Используется только в момент установки';
";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}wallets`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}wallets` (
  `user_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `amount` decimal(15,2) unsigned NOT NULL ,
  `amount_backup` decimal(15,2) unsigned NOT NULL COMMENT 'Может неравномерно обнуляться из-за обработки, а затем - отката new_reduction()',
  `last_update` int(11) NOT NULL COMMENT 'Время последнего пересчета суммы с учетом % из miner_pct',
  `log_id` bigint(20) NOT NULL COMMENT 'ID log_wallets, откуда будет брать данные при откате на 1 блок. 0 - значит при откате нужно удалить строку',
  PRIMARY KEY (`user_id`,`currency_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='У кого сколько какой валюты';
";



$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}wallets_buffer`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}wallets_buffer` (
  `hash` binary(32) NOT NULL COMMENT 'Хэш транзакции. Нужно для удаления данных из буфера, после того, как транзакция была обработана в блоке, либо анулирована из-за ошибок при повторной проверке',
  `del_block_id` bigint(20) NOT NULL COMMENT 'Т.к. удалять нельзя из-за возможного отката блока, приходится делать delete=1, а через сутки - чистить',
  `user_id` bigint(20) NOT NULL,
  `currency_id` tinyint(3) unsigned NOT NULL,
  `amount` decimal(15,2) unsigned NOT NULL ,
  `block_id` bigint(20) NOT NULL COMMENT 'Может быть = 0. Номер блока, в котором была занесена запись. Если блок в процессе фронт. проверки окажется невалдиным, то просто удалим все данные по block_id',
  PRIMARY KEY (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Суммируем все списания, которые еще не в блоке';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}forex_orders`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}forex_orders` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL COMMENT 'Чей ордер',
  `sell_currency_id` tinyint(3) unsigned NOT NULL COMMENT  'Что продается',
  `sell_rate` decimal(20,10) NOT NULL COMMENT 'По какому курсу к buy_currency_id',
  `amount` decimal(15,2)  NOT NULL COMMENT 'Сколько осталось на данном ордере',
  `buy_currency_id` int(10) NOT NULL COMMENT 'Какая валюта нужна',
  `commission` decimal(15,2)  NOT NULL COMMENT 'Какую отдали комиссию ноду-генератору',
  `empty_block_id` bigint(20) NOT NULL COMMENT 'Если ордер опустошили, то тут будет номер блока. Чтобы потом удалить старые записи',
  `del_block_id` bigint(20) NOT NULL COMMENT 'Если юзер решил удалить ордер, то тут будет номер блока',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='';

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_forex_orders`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_forex_orders` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `main_id` bigint(20) NOT NULL COMMENT 'ID из log_forex_orders_main. Для откатов',
  `order_id` bigint(20) unsigned NOT NULL COMMENT 'Какой ордер был задействован. Для откатов',
  `amount` decimal(15,2) unsigned NOT NULL COMMENT 'Какая сумма была вычтена из ордера',
  `to_user_id` bigint(20) unsigned NOT NULL COMMENT 'Какому юзеру была начислено amount ',
  `new` tinyint(3) unsigned NOT NULL COMMENT 'Если 1, то был создан новый  ордер. при 1 amount не указывается, т.к. при откате будет просто удалена запись из forex_orders',
  `block_id` bigint(20) NOT NULL COMMENT 'Для откатов',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Все ордеры, который были затронуты в результате тр-ии';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_forex_orders_main`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_forex_orders_main` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `block_id` bigint(20) NOT NULL COMMENT 'Чтобы можно было понять, какие данные можно смело удалять из-за их давности',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Каждый ордер пишется сюда. При откате любого ордера просто берем последнюю строку отсюда';

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}log_time_money_orders`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}log_time_money_orders` (
  `tx_hash` binary(16)  COMMENT 'По этому хэшу отмечается, что данная тр-ия попала в блок и ставится del_block_id',
  `user_id` bigint(20) NOT NULL,
  `del_block_id` bigint(20) NOT NULL COMMENT 'block_id сюда пишется в тот момент, когда тр-ия попала в блок и уже не используется для фронтальной проверки. Нужно чтобы можно было понять, какие данные можно смело удалять из-за их давности',
  PRIMARY KEY (`tx_hash`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='В один блок не должно попасть более чем 10 тр-ий перевода средств или создания forex-ордеров на суммы менее эквивалента 0.05-0.1$ по текущему курсу';

";



$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}_my_admin_messages`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}_my_admin_messages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `add_time` int(11) NOT NULL COMMENT 'для удаления старых my_pending',
  `user_int_message_id` int(11) NOT NULL COMMENT 'ID сообщения, который присылает юзер',
  `parent_user_int_message_id` int(11) NOT NULL COMMENT 'Parent_id, который присылает юзер',
  `user_id` bigint(20) NOT NULL,
  `type` enum('from_user','to_user') NOT NULL,
  `subject` varchar(255) CHARACTER SET utf8 NOT NULL,
  `encrypted` blob NOT NULL,
  `decrypted` tinyint(1) NOT NULL,
  `message` text CHARACTER SET utf8 NOT NULL,
  `message_type` tinyint(1) NOT NULL,
  `message_subtype` tinyint(1) NOT NULL,
  `status` enum('my_pending','approved') NOT NULL DEFAULT 'my_pending',
  `close` tinyint(1) NOT NULL COMMENT 'Воспрос закрыли, чтобы больше не маячил',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Эта табла видна только админу';

";

$my_queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_comments`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}[my_prefix]my_comments` (
  `type` enum('miner','promised_amount') NOT NULL,
  `vote_id` int(11) NOT NULL,
  `comment` varchar(255) CHARACTER SET utf8 NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Чтобы было проще понять причину отказов';
";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}authorization`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}authorization` (
  `hash` binary(16) NOT NULL,
  `data` varchar(20) NOT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}community`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}community` (
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Если не пусто, то работаем в режиме пула';

";

$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}backup_community`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}backup_community` (
  `uniq` enum('1') NOT NULL DEFAULT '1',
  `data` text NOT NULL,
  PRIMARY KEY (`uniq`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}payment_systems`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}payment_systems` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Для тех, кто не хочет встречаться для обмена кода на наличные';

";
$queries[] = "INSERT INTO `{$db_name}`.`{$prefix}payment_systems` (`name`)
					VALUES ('Adyen'),('Alipay'),('Amazon Payments'),('AsiaPay'),('Atos'),('Authorize.Net'),('BIPS'),('BPAY'),('Braintree'),('CentUp'),('Chargify'),('Citibank'),('ClickandBuy'),('Creditcall'),('CyberSource'),('DataCash'),('DigiCash'),('Digital River'),('Dwolla'),('ecoPayz'),('Edy'),('Elavon'),('Euronet Worldwide'),('eWAY'),('Flooz'),('Fortumo'),('Google'),('GoCardless'),('Heartland Payment Systems'),('HSBC'),('iKobo'),('iZettle'),('IP Payments'),('Klarna'),('Live Gamer'),('Mobilpenge'),('ModusLink'),('MPP Global Solutions'),('Neteller'),('Nochex'),('Ogone'),('Paymate'),('PayPal'),('Payoneer'),('PayPoint'),('Paysafecard'),('PayXpert'),('Payza'),('Peppercoin'),('Playspan'),('Popmoney'),('Realex Payments'),('Recurly'),('RBK Money'),('Sage Group'),('Serve'),('Skrill (Moneybookers)'),('Stripe'),('Square, Inc.'),('TFI Markets'),('TIMWE'),('Use My Services (UMS)'),('Ukash'),('V.me by Visa'),('VeriFone'),('Vindicia'),('WebMoney'),('WePay'),('Wirecard'),('Western Union'),('WorldPay'),('Yandex money'),('Qiwi'),('OK Pay'),('Bitcoin'),('Perfect Money')";


$queries[] = "DROP TABLE IF EXISTS `{$db_name}`.`{$prefix}config`;
CREATE TABLE IF NOT EXISTS `{$db_name}`.`{$prefix}config` (
  `php_path` varchar(255) NOT NULL COMMENT 'Нужно для запуска демонов',
  `my_block_id` int(11) NOT NULL COMMENT 'Параллельно с info_block пишем и сюда. Нужно при обнулении рабочих таблиц, чтобы знать до какого блока не трогаем таблы my_',
  `local_gate_ip` varchar(255) NOT NULL COMMENT 'Если тут не пусто, то connector.php будет не активным, а ip для disseminator.php будет браться тут. Нужно для защищенного режима',
  `static_node_user_id` int(11) NOT NULL COMMENT 'Все исходящие тр-ии будут подписаны публичным ключем этой ноды. Нужно для защищенного режима',
  `in_connections_ip_limit` int(11) NOT NULL COMMENT 'Кол-во запросов от 1 ip за минуту',
  `in_connections` int(11) NOT NULL COMMENT 'Кол-во нодов и просто юзеров, от кого принимаем данные. Считаем кол-во ip за 1 минуту',
  `out_connections` int(11) NOT NULL COMMENT 'Кол-во нодов, кому шлем данные',
  `bad_blocks` text NOT NULL COMMENT 'Номера и sign плохих блоков. Нужно, чтобы не подцепить более длинную, но глючную цепочку блоков',
  `pool_max_users` int(11) NOT NULL DEFAULT '100',
  `pool_admin_user_id`  int(11) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

";

$my_queries[] = "INSERT INTO `{$db_name}`.`{$prefix}[my_prefix]my_notifications` (`name`, `email`, `sms`)
					VALUES ('admin_messages',1,1),('change_in_status',1,0),('fc_came_from',1,0),('fc_sent',1,0),('incoming_cash_requests',1,1),('new_version',1,1),('node_time',1,1),('system_error',1,1),('update_email',1,0),('update_primary_key',1,0),('update_sms_request',1,0),('voting_results',1,0),('voting_time',1,0)";


?>