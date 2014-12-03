<script>
	$(document).ready(function() {
		$( "#progress_bar" ).load( "ajax/progress_bar.php");
	});
</script>
<script>
	$('#send_to_net').bind('click', function () {

		if ($('#agree').is(':checked')) {
			$.post('ajax/save_queue.php', {
				'type': '<?php echo $tpl['data']['type']?>',
				'time': '<?php echo $tpl['data']['time']?>',
				'user_id': '<?php echo $tpl['data']['user_id']?>',
				'race': '<?php echo $tpl['data']['race']?>',
				'country': '<?php echo $tpl['data']['country']?>',
				'latitude': '<?php echo $tpl['data']['latitude']?>',
				'longitude': '<?php echo $tpl['data']['longitude']?>',
				'host': '<?php echo $tpl['data']['host']?>',
				'face_hash': '<?php echo $tpl['data']['face_hash']?>',
				'profile_hash': '<?php echo $tpl['data']['profile_hash']?>',
				'face_coords': '<?php echo $tpl['data']['face_coords']?>',
				'profile_coords': '<?php echo $tpl['data']['profile_coords']?>',
				'video_type': '<?php echo $tpl['data']['video_type']?>',
				'video_url_id': '<?php echo $tpl['data']['video_url_id']?>',
				'node_public_key': '<?php echo $tpl['data']['node_public_key']?>',
				'signature1': $('#signature1').val(),
				'signature2': $('#signature2').val(),
				'signature3': $('#signature3').val()
			}, function (data) {
				if (!data)
					var my_alert = '<?php echo $lng['sent_to_the_net'] ?>';
				else
					var my_alert = data;
				fc_navigate('upgrade', {'alert': my_alert});
			});
		}
		else {
			$('#errors').html('<div class="alert alert-danger"><?php echo $lng['do_not_check_the_agreement']?></div>');
		}
	} );

	<?php echo !defined('SHOW_SIGN_DATA')?'$("#sign_data").css("display", "none");':'' ?>

	doSign();

	$("#main_div textarea").addClass( "form-control" );
</script>

<div id="main_div">
<h1 class="page-header"><?php echo $lng['upgrade_title']?></h1>
<ol class="breadcrumb">
	<li><a href="#mining_menu"><?php echo $lng['mining'] ?></a></li>
	<li class="active"><?php echo $lng['upgrade_title'] ?></li>
</ol>
	
    <ul class="nav nav-tabs">
	    <?php echo make_upgrade_menu(7)?>
    </ul>
    
	<h3><?php echo $lng['sending_data_to_net']?></h3>

	<div id="main_data">
		<?php
		if (!$tpl['data']['race'])
			echo "<div class='alert alert-danger'>{$lng['empty_race']}</div>";
		else if (!$tpl['data']['country'])
			echo "<div class='alert alert-danger'>{$lng['empty_country']}</div>";
		else if (!$tpl['data']['face_hash'])
			echo "<div class='alert alert-danger'>{$lng['empty_photo']}</div>";
		else if (!$tpl['data']['profile_hash'])
			echo "<div class='alert alert-danger'>{$lng['empty_photo2']}</div>";
		else if (!$tpl['data']['face_coords'] || !$tpl['data']['profile_coords'])
			echo "<div class='alert alert-danger'>{$lng['empty_points']}</div>";
		else if ( ($tpl['data']['video_url_id']=='null' || $tpl['data']['video_type']=='null') && !file_exists(ABSPATH."public/{$_SESSION['user_id']}_user_video.mp4"))
			echo "<div class='alert alert-danger'>{$lng['empty_video']}</div>";
		else if (!$tpl['data']['host'])
			echo "<div class='alert alert-danger'>{$lng['empty_node']}</div>";
		else if (!$tpl['data']['latitude'] || !$tpl['data']['longitude'])
			echo "<div class='alert alert-danger'>{$lng['empty_geolocation']}</div>";
		else {
			?>
			<div id="sign_data">
				<?php echo $lng['data'] ?>:<br>
				<textarea class="form-control" id="for-signature" style="width:500px; height:100px"><?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']},{$tpl['data']['race']},{$tpl['data']['country']},{$tpl['data']['latitude']},{$tpl['data']['longitude']},{$tpl['data']['host']},{$tpl['data']['face_hash']},{$tpl['data']['profile_hash']},{$tpl['data']['face_coords']},{$tpl['data']['profile_coords']},{$tpl['data']['video_type']},{$tpl['data']['video_url_id']},{$tpl['data']['node_public_key']}"; ?></textarea><br>
				<?php
				for ($i = 1; $i <= $count_sign; $i++) {
					echo "<label>{$lng['sign']} " . (($i > 1) ? $i : '') . "</label><textarea class=\"form-control\" id=\"signature{$i}\" style=\"width:500px;\" rows=\"4\"></textarea>";
				}
				?>
				<br>
			</div>
			<div id="errors"></div>
			<input id="agree" type="checkbox"> <?php echo $lng['i_realize_that_my_photos']?><br><br>
			<button class="btn btn-success" id="send_to_net"><?php echo $lng['send_to_net'] ?></button>
		<?php
		}
		?>
	</div>

</div>