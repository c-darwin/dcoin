
<script>
$('#generate_change_node_key').bind('click', function () {
	
	$.post( 'ajax/generate_new_node_key.php', function (data) {

			$("#generate").css("display", "none"); 
			$("#sign").css("display", "block");
			$("#public_key").val( data.public_key );
			$("#private_key").val( data.private_key );
			$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#public_key").val() );
			doSign();
			<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
		}, 'json' );
		
} );

$('#send_to_net').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
				'public_key' : $('#public_key').val(),
				'private_key' : $('#private_key').val(),
							'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
			}, function (data) {
				//alert(data);
				fc_navigate ('change_node_key', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
	);

} );

</script>

	<h1 class="page-header"><?php echo $lng['change_node_key_title']?></h1>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

	<div id="generate">
		<button class="btn" type="button" id="generate_change_node_key"><?php echo $lng['generate_new_node_key']?></button>
	</div>

	<?php require_once( 'signatures.tpl' );?>

	<input type="hidden" id="public_key">
	<input type="hidden" id="private_key">
