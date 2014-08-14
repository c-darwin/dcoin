<!--
<script src="js/jquery.min.js"></script>-->
<script src="js/jquery.Jcrop.js"></script>

<link rel="stylesheet" href="css/jquery.Jcrop.css" type="text/css" />

<!--
	<script src="js/jquery.Jcrop.js"></script>
-->
	<script type="text/javascript">

	
	function showCoords_user_face_coords(c) {
		$('#user_face_coords').text(c.x+';'+c.y+';'+c.x2+';'+c.y2+';'+c.w+';'+c.h);
	};
	
	function showCoords_user_profile_coords(c) {
		$('#user_profile_coords').text(c.x+';'+c.y+';'+c.x2+';'+c.y2+';'+c.w+';'+c.h);
	};

	</script>

<!--
	<link rel="stylesheet" href="js/jquery.Jcrop.css" type="text/css" />
--->

<style>
		.progress {
			width:0%;
			overflow:hidden;
			height:20px;
			display:inline-block;
			vertical-align:middle;
			color:#FFF;
			text-align:right;
			text-shadow:1px 1px 0 #000;
			background:-o-linear-gradient(top,#888888,#333333);
			background:-moz-linear-gradient(top,#888888,#333333);
			background:-webkit-gradient(linear,left top,left bottom,from(#888888),to(#333333));
			background:-webkit-linear-gradient(top,#888888,#333333);
			-o-transition-property:width;
			-o-transition-duration:.5s;
			-moz-transition-property:width;
			-moz-transition-duration:.5s;
			-webkit-transition-property:width;
			-webkit-transition-duration:.5s;
		}

	</style>


	<script type="text/javascript" src="js/uploader.js"></script>

	<script type="text/javascript">
		
		function send_crop (type, coords, img_id) {
					
			$.post('ajax/crop_photo.php', {'type' : type, 'coords' : $('#'+coords).text() },
				function(data) {
				
					$('#'+img_id).html('<img width="350" src="'+data.url+'?r='+Math.random()+'" id="'+type+'">');
					
				}, "json");

		}
		
		function send1 (file_id, progress, img_id, type) {
			var 
				$f = $('#'+file_id),
				$p = $('#'+progress),
				up = new uploader($f.get(0), {
					url:'ajax/upload.php',
					prefix:'image',
					type:type,
					progress:function(ev){ $p.html(((ev.loaded/ev.total)*100)+'%'); $p.css('width',$p.html()); },
					error:function(ev){
						alert('error');
					},
					success:function(data){

						if (data.error !== undefined) {
							alert(data.error)
						}
						else {
							$p.html('100%');
							$p.css('width',$p.html());
							$('#'+img_id).html('<?php echo $lng['crop_img']?><br><img width="350" src="'+data.url+'?r='+Math.random()+'" id="'+type+'">');

							if (type=='user_face_tmp') {
								$('#'+type).Jcrop({
											onSelect: showCoords_user_face_coords,
											onChange: showCoords_user_face_coords,
											bgColor:     'black',
											bgOpacity:   .4,
											aspectRatio: 7/10
										});
							}
							else {
								$('#'+type).Jcrop({
											onSelect: showCoords_user_profile_coords,
											onChange: showCoords_user_profile_coords,
											bgColor:     'black',
											bgOpacity:   .4,
											aspectRatio: 7/10
										});
							}
						}
					}
				});

			up.send();

		}


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



	</script>

<script src="js/js.js"></script>


<h1 class="page-header"><?php echo $lng['upgrade_title']?></h1>
<ol class="breadcrumb">
	<li><a href="#" onclick="fc_navigate('mining_menu')"><?php echo $lng['mining'] ?></a></li>
	<li class="active"><?php echo $lng['upgrade_title'] ?></li>
</ol>

    <ul class="nav nav-tabs">
		<li><a href="#" onclick="fc_navigate('upgrade_0')">Step 0</a></li>
		<li class="active"><a href="#" onclick="fc_navigate('upgrade_1')">Step 1</a></li>
		<li><a href="#" onclick="fc_navigate('upgrade_2')">Step 2</a></li>
		<li><a href="#" onclick="fc_navigate('upgrade_3')">Step 3</a></li>
	    <li><a href="#" onclick="map_navigate('upgrade_4')">Step 4</a></li>
	    <li><a href="#" onclick="fc_navigate('upgrade_5')">Step 5</a></li>
    </ul>
    
	<h3><?php echo $lng['upload_2_photo']?></h3>

	<?php echo $lng['upload_2_photo_rules']?>

<div style="height:550px">
	<img src="img/face.jpg">
	<span class="btn btn-file">

		<input type="file" id="user_face_file" name="file" accept="image/jpg, image/jpeg, image/gif, image/png" onchange="send1('user_face_file', 'user_face_progress', 'user_face', 'user_face_tmp')" />
		
		<p><span id="user_face_progress" class="progress">0%</span></p>

	<div id="user_face"><?php echo ($tpl['user_face'])?"<img width='350' src='{$tpl['user_face']}?r=".rand(0, getrandmax())."' id='user_face_tmp'>":"" ?></div>
	<div id="user_face_coords" style="display: none"></div>
	<p><button onclick="send_crop('user_face_tmp', 'user_face_coords', 'user_face')">Save</button></p>
	</span>
</div>

<br><br><br><br><br><br>


<div style="height:550px">
	<img src="img/profile.jpg">
	<span class="btn btn-file">

		<input type="file" id="user_profile_file" name="file" accept="image/jpg, image/jpeg, image/gif, image/png" onchange="send1('user_profile_file', 'user_profile_progress', 'user_profile', 'user_profile_tmp')" />
		
		<p><span id="user_profile_progress" class="progress">0%</span></p>

	<div id="user_profile"><?php echo ($tpl['user_profile'])?"<img width='350' src='{$tpl['user_profile']}?r=".rand(0, getrandmax())."' id='user_profile_tmp'>":"" ?></div>
	<div id="user_profile_coords" style="display: none"></div>
	<p><button onclick="send_crop('user_profile_tmp', 'user_profile_coords', 'user_profile')">Save</button></p>
	</span>
</div>

	<br><br><br><br><br><br>

	<div id="progress">
		<div class="bar" style="width: 0%;"></div>
	</div>
	
	<h3><?php echo $lng['upload_face_video']?></h3>

	<?php echo $lng['upload_face_video_rules']?>
	<?php echo $lng['upload_face_video_rules2']?>


	<div>

		<table class="table table-bordered">
			<tr><td>

	<span class="btn btn-file"><input id="video_url" type="text" value="<?php echo @$tpl['video_url']?>" style="width:500px"><button id="save_youtube"><?php echo $lng['save']?></button><button id="clear_youtube"><?php echo $lng['clear']?></button></span>
		<br>Example: http://www.youtube.com/watch?v=ZSt9tm3RoUU<br>
			<div id="video_url_iframe"><?php echo (@$tpl['video_url'])?"<iframe width=640 height=480  src='{$tpl['video_url']}' frameborder=0 allowfullscreen></iframe>":""?></div>
				</td></tr>

			<?php
			if (!defined('COMMUNITY')) {
			?>
				<tr><td>
				<?php echo $lng['2_video_file']?>:<br>

					<table><tr><td>

								mp4:<input type="file" id="video_mp4" name="file" accept="video/mp4" onchange="send_video('video_mp4', 'video_mp4_progress', 'user_video')" />
								<div id="video_mp4_progress" class="progress">0%</div><br>
								<div id="video_mp4_ok" class="alert alert-success" style="display: none"></div>
								<button id="del_mp4" style="display: none">Delete</button>

					</td><td>

								WebM or Ogg: <input type="file" id="video_webm_ogg" name="file" accept="video/webm, video/ogg" onchange="send_video('video_webm_ogg', 'video_webm_ogg_progress', 'user_video')" />
								<div id="video_webm_ogg_progress" class="progress" >0%</div>
								<div id="video_webm_ogg_ok" class="alert alert-success" style="display: none"></div>
								<button id="del_webm_ogg" style="display: none">Delete</button>

					</td></tr></table>
						<br>

						<div id="video" style="display: none"><video id="example_video_1" class="video-js vjs-default-skin" controls preload="none" width="640" height="468" data-setup="{}"><source src="public/<?php echo $user_id ?>_user_video.mp4" type='video/mp4' /><source src="public/<?php echo $user_id ?>_user_video.webm" type='video/webm' /><source src="public/<?php echo $user_id ?>_user_video.ogv" type='video/ogg' /></video></div>

					</td></tr>
			<?php
			}
			?>


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

	<button class="btn btn-success" onclick="$('#save_youtube').trigger('click');fc_navigate('upgrade_2');">Step 2</button>
	
	
	<br><br><br><br><br><br><br>
		
	
	<div class="for-signature"></div>
       
