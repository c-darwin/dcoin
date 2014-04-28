<!-- container -->
<div class="container">

<script>

$('#save').bind('click', function () {
	
	$.post( 'ajax/generate_new_primary_key.php', { 'password' : $("#new_password").val() }, function (data) {

			$("#public_key").val( data.public_key );
			if ($("#save_private_key").prop("checked")) {
				$("#password_hash").val( data.password_hash );
			}
			else {
				$("#password_hash").val( '' );
			}
			$("#add").css("display", "none");
			$("#show_key").css("display", "block");
			$("#private_key").val( data.private_key );
		}, 'json' );
		
} );

$('#save2').bind('click', function () {

	$("#add").css("display", "none");
	$("#show_key").css("display", "none");
	$("#public_keys").css("display", "block");

} );

$('#save3').bind('click', function () {

	$("#public_keys").css("display", "none");
	$("#show_key").css("display", "none");
	$("#sign").css("display", "block");
	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#public_key").val()+','+$("#public_key_1").val()+','+$("#public_key_2").val() );

} );


$('#send_to_net').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'public_key' : $('#public_key').val(),
			'public_key_1' : $('#public_key_1').val(),
			'public_key_2' : $('#public_key_2').val(),
			'private_key' : $('#private_key').val(),
			'password_hash' : $('#password_hash').val(),
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
		}, function (data) {
				//alert(data);
				fc_navigate ('change_primary_key', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
	);

} );

</script>

	<legend><h2><?php echo $lng['change_primary_key_title']?></h2></legend>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	
	<div id="add">
		<form>
			<fieldset>
				<label><?php echo $lng['new_pass_for_key']?></label>
				<input type="password" placeholder="" id="new_password">
				<label class="checkbox">
					<input type="checkbox" id="save_private_key"> <?php echo $lng['save_key']?>
				</label>
				<button type="submit" class="btn" id="save"><?php echo $lng['next']?></button>
			</fieldset>
		</form>


		<?php
		if ($tpl['my_keys']) {
			echo '<table class="table table-bordered" style="width:500px">';
		echo '<thead><tr><th>ID</th><th>'.$lng['block'].'</th><th>'.$lng['time'].'</th><th>'.$lng['status'].'</th></tr></thead>';
		echo '<tbody>';
		foreach( $tpl['my_keys'] as $k=>$data ) {
		echo "<tr>";
			echo "<td>{$data['id']}</td>";
			echo "<td>{$data['block_id']}</td>";
			echo "<td>{$data['time']}</td>";
			echo "<td>{$status_array[$data['status']]}</td>";
			echo "</tr>";
		}
		echo '</tbody>';
		echo '</table>';
		}
		?>

		<p><span class="label label-important"><?php echo $lng['limits']?></span> <?php echo $tpl['limits_text']?></p>

	</div>


	<div id="show_key" style="display:none">
		<label><?php echo $lng['your_new_key']?></label>
		<textarea rows="5" id="private_key" style="width:600px;text-align: justify"></textarea>
		<div class="alert alert-info" style="width:500px"><strong><?php echo $lng['attention_title']?> </strong> <?php echo $lng['your_new_key_rules']?></div>
		<button class="btn" type="button" id="save2"><?php echo $lng['next']?></button>

	</div>

	<div id="public_keys" style="display:none">
		<label><?php echo $lng['your_public_keys_1']?></label>
		<textarea rows="5" id="public_key_1" style="width:600px;text-align: justify"></textarea>
		<label><?php echo $lng['your_public_keys_2']?></label>
		<textarea rows="5" id="public_key_2" style="width:600px;text-align: justify"></textarea>
		<br>
		<button class="btn" type="button" id="save3"><?php echo $lng['next']?></button>
	</div>


	<?php require_once( 'signatures.tpl' );?>

	<input type="hidden" id="public_key">
	<input type="hidden" id="password_hash">

</div>
<!-- /container -->