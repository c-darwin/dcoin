<!-- container -->
<div class="container">

<script>

$('#save').bind('click', function () {

	var data='';
	var comment='';
	var user_id='';
	for (i=0; i<100; i++)	{

		comment = $('#comment_'+i).val();
		user_id = $('#user_id_'+i).val();
		if ( user_id!='' && comment!='' && ( /^[a-zA-Z0-9\,\s\.\-]*$/.test(comment) ) )
			data = data + '"'+user_id + '":"' + comment + '",';

	}
	data = '{'+data.substr(0, data.length-1)+'}';

	$("#generate").css("display", "none");
	$("#sign").css("display", "block");
	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+data );
	$('#abuses').val( data );

} );

$('#send_to_net').bind('click', function () {

		$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'abuses' :  $('#abuses').val(),
						'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
		}, function(data) {
			fc_navigate ('abuse', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
		});

	} );

</script>
	<legend><h2><?php echo $lng['abuses_title']?></h2></legend>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	
	<div id="generate">

		<table>
			<thead>
			<tr>
				<th>user_id</th>
				<th>abuse</th>
			</tr>
			</thead>

			<tbody>

				<?php
				for ($i=0; $i<100;  $i++) {
					echo "<tr><td><input class='input-mini' id='user_id_{$i}' type='text'></td><td><input class='input-xxlarge' id='comment_{$i}' type='text'></td></tr>";
				}
				?>

			</tbody>
		</table>

		<br>
	
		<button class="btn" type="button" id="save"><?php echo $lng['next']?></button>
		<br><br>

		<p><span class="label label-important"><?php echo $lng['limits']?></span> <?php echo $tpl['limits_text']?></p>

	</div>

	<?php require_once( 'signatures.tpl' );?>

	<input type="hidden" id="abuses">
    
</div>
<!-- /container -->