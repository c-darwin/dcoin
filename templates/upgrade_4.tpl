<script>
	$(document).ready(function() {
		$( "#progress_bar" ).load( "ajax/progress_bar.php");
	});
</script>
<script type="text/javascript">

		$('#save_youtube').bind('click', function () {

			var video_url = $('#video_url').val();
			$.post('ajax/save_video.php', {'video_url' : video_url },
					function(data) {
						if (data.url != '') {

							//$('#video_url_src').attr('src') = $('#video_url').val();
							$('#video_url_iframe').html('<iframe width=640 height=480  src="'+data.url+'" frameborder=0 allowfullscreen></iframe>');
						}

					}, "json");
		});

		$('#clear_youtube').bind('click', function () {

			$.post('ajax/clear_video.php', { },
					function(data) {
						$('#video_url_iframe').html('');
						$('#video_url').val('');
					});
		});

		$('#del_mp4').bind('click', function () {
			$.post('ajax/delete_video.php', {'type' : 'mp4' },
					function(data) {
						$('#video_mp4_ok').css("display", "none");
						$('#del_mp4').css("display", "none");
					});
		});

		$('#del_webm_ogg').bind('click', function () {
			$.post('ajax/delete_video.php', {'type' : 'webm_ogg' },
					function(data) {
						$('#video_webm_ogg_ok').css("display", "none");
						$('#del_webm_ogg').css("display", "none");
					});
		});
		$( "#from_webcam_show" ).click(function() {
			$("#from_webcam").css("display", "block");
			$("#from_file").css("display", "none");
			return false;
		});
		$( "#from_file_show" ).click(function() {
			$("#from_file").css("display", "block");
			$("#from_webcam").css("display", "none");
			return false;
		});

		$( "#refresh_youtube" ).click(function() {
			var iframe = document.getElementById('player');
			iframe.src = iframe.src;
			console.log('player');
			return false;
		});

	</script>

	<script src="js/uploader.js"></script>
	<script src="js/js.js"></script>

	<link rel="stylesheet" href="css/progress.css" type="text/css" />
<h1 class="page-header"><?php echo $lng['upgrade_title']?></h1>
<ol class="breadcrumb">
	<li><a href="#mining_menu"><?php echo $lng['mining'] ?></a></li>
	<li class="active"><?php echo $lng['upgrade_title'] ?></li>
</ol>

    <ul class="nav nav-tabs">
	    <?php echo make_upgrade_menu(4)?>
    </ul>


	<h3><?php echo $lng['upload_face_video']?></h3>

	<?php echo $lng['upload_face_video_rules']?>
	<?php echo $lng['upload_face_video_rules2']?>

	<p><a href="#" id="from_webcam_show">С web-камеры</a> или <a href="#" id="from_file_show">из файла</a></p>
	<div id="from_webcam">

		<div id="widget"></div>
		<div id="player"><?php echo (@$tpl['video_url'])?"<iframe width=640 height=480  src='{$tpl['video_url']}' frameborder=0 allowfullscreen></iframe>":""?></div>
		<div id="refresh_youtube_div" style="display: none"><a href="#" id="refresh_youtube"><i class="fa fa-refresh fa-fw" style="font-size: 30px"></i></a></div>

		<script src="js/youtube_webcam.js"></script>
	</div>

	<div id="from_file" style="display: none">

		<table class="table table-bordered">
			<tr><td>

	<span class="btn btn-file"><input id="video_url" type="text" value="<?php echo @$tpl['video_url']?>" style="width:500px"><button id="save_youtube"><?php echo $lng['save']?></button><button id="clear_youtube"><?php echo $lng['clear']?></button></span>
		<br>Example: http://www.youtube.com/watch?v=ZSt9tm3RoUU<br>
			<div id="video_url_iframe"><?php echo (@$tpl['video_url'])?"<iframe width=640 height=480  src='{$tpl['video_url']}' frameborder=0 allowfullscreen></iframe>":""?></div>
				</td></tr>

				<tr><td>
				or upload mp4/mov video:<br>

					<table>
						<tr>
							<td>

								mp4:<input type="file" id="video_mp4" name="file" accept="video/mov,video/mp4" onchange="send_video('video_mp4', 'video_mp4_progress', 'user_video')" />
								<div id="video_mp4_progress" class="my_progress">0%</div><br>
								<div id="video_mp4_ok" class="alert alert-success" style="display: none"></div>
								<button id="del_mp4" style="display: none">Delete</button>

							</td>
							<!--<td>

								WebM or Ogg: <input type="file" id="video_webm_ogg" name="file" accept="video/webm, video/ogg" onchange="send_video('video_webm_ogg', 'video_webm_ogg_progress', 'user_video')" />
								<div id="video_webm_ogg_progress" class="progress" >0%</div>
								<div id="video_webm_ogg_ok" class="alert alert-success" style="display: none"></div>
								<button id="del_webm_ogg" style="display: none">Delete</button>

							</td>-->
							</tr>
					</table>
						<br>

						<div id="video" style="display: none"><video id="example_video_1" class="video-js vjs-default-skin" controls preload="none" width="640" height="468" data-setup="{}"><source src="public/<?php echo $user_id ?>_user_video.mp4" type='video/mp4' /><source src="public/<?php echo $user_id ?>_user_video.webm" type='video/webm' /><source src="public/<?php echo $user_id ?>_user_video.ogv" type='video/ogg' /></video></div>

					</td></tr>


		</table>

	</div>

	<script>
		<?php
		if ($tpl['user_video_mp4'])
			print "	$('#video_mp4_ok').css(\"display\", \"block\");
		$('#video_mp4_ok').html('{$lng['file_successfully_downloaded']}');
		$('#del_mp4').css(\"display\", \"block\");
		$('#video').css(\"display\", \"block\");";

		if ($tpl['user_video_webm'] || $tpl['user_video_ogg'])
			print "	$('#video_webm_ogg_ok').css(\"display\", \"block\");
		$('#video_webm_ogg_ok').html('{$lng['file_successfully_downloaded']}');
		$('#del_webm_ogg').css(\"display\", \"block\");
		$('#video').css(\"display\", \"block\");";

		?>
	</script>


	<br>

	<button class="btn btn-success" onclick="$('#save_youtube').trigger('click');fc_navigate('upgrade_5');"><?php echo str_replace('[num]','5',$lng['save_and_goto_step'])?></button>
	
	
	<br><br><br><br><br><br><br>
		
	
	<div class="for-signature"></div>
       
