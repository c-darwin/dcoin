<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!empty($_SESSION['restricted']))
	die('Permission denied');

require_once( ABSPATH . 'includes/class.phpmailer.php');
require_once( ABSPATH . 'includes/class.smtp.php');

define('MY_PREFIX', get_my_prefix($db));

// делаем выборку, т.к. данные для smtp могли быть записаны юзером ранее
$smtp = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT * FROM `'.DB_PREFIX.MY_PREFIX.'my_table`
		', 'fetch_array' );

$mail                = new PHPMailer();
if ($smtp['use_smtp'] && $smtp['smtp_server'])
{
	$mail->IsSMTP();
	$mail->SMTPAuth      = ($smtp['smtp_auth']?true:false);
	$mail->SMTPSecure    = ($smtp['smtp_ssl']?'ssl':'');
	$mail->Host          = $smtp['smtp_server'];
	$mail->Port          = $smtp['smtp_port'];
	$mail->Username      = $smtp['smtp_username'];
	$mail->Password      = $smtp['smtp_password'];
}
$mail->SetFrom($smtp['email'], 'Server');

$mail->Subject       = "test";
$mail->Body    = "test";
$mail->AddAddress($smtp['email'], 'Server');

if(!$mail->Send()) {
	echo json_encode(
				array('error'=>'Mailer Error (' . str_replace("@", "&#64;", $smtp["email"]) . ') ' . $mail->ErrorInfo . '<br />')
			);
} else {
	echo json_encode(
		array('error'=>'null')
	);
}


?>