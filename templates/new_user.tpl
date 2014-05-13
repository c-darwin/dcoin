<script>
	$('#new_user').bind('click', function () {
		$.post( 'ajax/generate_new_primary_key.php', function (data) {
			$("#div_new_user_0").css("display", "none");
			$("#div_new_user_1").css("display", "block");
			$("#public_key").val( data.public_key );
			$("#private_key").val( data.private_key );
			$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#public_key").val());
			doSign();
		}, 'json' );

	} );

	$('#next').bind('click', function () {
		$("#div_new_user_1").css("display", "none");
		$("#sign").css("display", "block");
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
					fc_navigate ('new_user', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
				}
		);
	} );
</script>

<!-- container -->
<div class="container">

	<legend><h2><?php echo $lng['reg_users']?></h2></legend>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

	<div id="div_new_user_0">
		<button id="new_user" class="btn">new user</button><br><br>
		<?php
		if ($tpl['new_users']) {
			echo '<table class="table table-bordered" style="width:200px">';
			echo '<thead><tr><th>user_id</th><th>status</th><th>private_key</th></tr></thead>';
			echo '<tbody>';
			foreach($tpl['new_users'] as $data) {
				print "<tr><td>{$data['user_id']}</td><td>{$lng['status_'.$data['status']]}</td><td><pre style='width: 630px'>{$data['private_key']}</pre></td></tr>";
			}
			echo '</tbody>';
			echo '</table>';
		}
		?>
		</div>

	<div id="div_new_user_1" style="display: none">
		<textarea id="public_key" style="width: 700px; height: 300px; display: none"></textarea><br>
		<textarea id="private_key" style="width: 730px; height: 300px"></textarea><br>
		<button class="btn" id="next"><?php echo $lng['next']?></button>
	</div>

	<?php require_once( 'signatures.tpl' );?>

</div>
<!-- /container -->