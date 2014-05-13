<?php
session_start();
define( 'DC', TRUE);
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'cron/daemons_inc.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$arr = array();

foreach($daemons as $daemon) {
	$arr[str_replace('.php', '', $daemon)] = 'no';
}

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `script`, `time`
		FROM `'.DB_PREFIX.'daemons`
		');
while ($row = $db->fetchArray($res)) {
	$name = substr( $row['script'], 0, strpos($row['script'], '.'));
	if ($row['time']>time()-600)
		$arr[$name] = 'ok';
}

// ****************************************************************************
//  CHMOD
// ****************************************************************************

// Очищаем кэш состояния файлов
clearstatcache();

$perms = fileperms(ABSPATH);
if ( ($perms & 0x4000) == 0x4000 && ($perms & 0x0080) && ($perms & 0x0010) && ($perms & 0x0002) )
	$arr['chmod0777'] = 'ok';
else {
	$perms = fileperms(ABSPATH . 'public');
		if ( ($perms & 0x4000) == 0x4000 && ($perms & 0x0080) && ($perms & 0x0010) && ($perms & 0x0002) )
			$arr['chmod0777'] = 'ok';
}
echo json_encode( $arr );


?>
