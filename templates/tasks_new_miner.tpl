

<script src="js/spots.js"></script>

<script>

	var comment = '';

	function reload_photo(user_id, face_id, profile_id) {
		$.post( 'ajax/new_photo.php', {
			'user_id' : user_id
		}, function (data) {

			alert(data.face+"\n"+data.profile+"\n"+face_id+"\n"+profile_id);
			$('#'+face_id).css("background-image", "url("+data.face+")");
			$('#'+profile_id).css("background-image", "url("+data.profile+")");

		}, "json" );
	}

	function reload_photo2(user_id, face_id, profile_id) {
		$.post( 'ajax/new_photo.php', {
			'user_id' : user_id
		}, function (data) {

			alert(data.face+"\n"+data.profile+"\n"+face_id+"\n"+profile_id);
			$('#'+face_id).attr('src', data.face);
			$('#'+profile_id).attr('src', data.profile);

		}, "json" );
	}

	function write_for_signature (result) {
		if (comment=='') {
			comment = 'null';
		}
		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']},{$tpl['user_info']['vote_id']}"?>,'+result+','+comment);
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	}

	$('#btn-bad').bind('click', function () {
		comment = $('#comment_step_1').val();
	});

	$('#btn-bad2').bind('click', function () {
		comment = $('#comment_step_2').val();
	});

	$('#btn-bad3').bind('click', function () {
		comment = $('#comment_step_3').val();
	});

$('#btn-bad,#btn-bad2,#btn-bad3').bind('click', function () {

	$('#step_1').css('display', 'none');
	$('#step_2').css('display', 'none');
	$('#step_3').css('display', 'none');	
	$('#sign').css('display', 'block');	
	$('#result').val( '0' );

	write_for_signature(0);

} );

$('#reload-user-photos').bind('click', function () {

	reload_photo($('#candidate-id').val(), 'face_coords_mouse', 'profile_coords_mouse')

} );

$('#btn-step1-back').bind('click', function () {

	$('#step_1').css('display', 'block');
	$('#step_2').css('display', 'none');
	$('#step_3').css('display', 'none');
	//$('#title').css('display', 'none');

} );

$('#btn-step2,#btn-step2-back').bind('click', function () {

	comment = $('#comment_step_1').val();
	$('#comment_step_2').val(comment);

	$('#step_1').css('display', 'none');
	$('#step_2').css('display', 'block');
	$('#step_3').css('display', 'none');
	//$('#title').css('display', 'none');
} );

$('#btn-step3,#btn-step3-back').bind('click', function () {

	comment = $('#comment_step_2').val();
	$('#comment_step_3').val(comment);

	$('#step_1').css('display', 'none');
	$('#step_2').css('display', 'none');
	$('#step_3').css('display', 'block');
	$('#sign').css('display', 'none');
	//$('#title').css('display', 'block');

} );

$('#btn-step4').bind('click', function () {

	comment = $('#comment_step_3').val();

	$('#step_1').css('display', 'none');
	$('#step_2').css('display', 'none');
	$('#step_3').css('display', 'none');	
	$('#sign').css('display', 'block');	
	$('#result').val( '1' );

	write_for_signature(1);

} );

$('#send_to_net').bind('click', function () {
	
	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'vote_id' : $('#vote_id').val(),
			'result' : $('#result').val(),
			'comment' : comment,
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
			}, function () { } );
	fc_navigate ('tasks', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
	
} );

</script>
<h1 class="page-header"><?php echo $lng['tasks_title_new_miner']?></h1>
<ol class="breadcrumb">
	<li><a href="#" onclick="fc_navigate('mining_menu')"><?php echo $lng['mining'] ?></a></li>
	<li><a href="#" onclick="fc_navigate('tasks')"><?php echo $lng['tasks_title'] ?></a></li>
</ol>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

	<!-- S T E P   1 -->

 	<div id="step_1" style="position: relative;">
	    <p><?php echo $lng['tasks_new_miner_spots']?></p>

<?php echo $lng['accounted_country']?>: <?php echo $tpl['my_country']?> <?php echo $lng['and_race']?>: <?php echo $tpl['my_race']?> <a href="#" onclick="fc_navigate('change_country_race')"><?php echo $lng['change']?></a><br>
	<input type="hidden" id="candidate-id" value="<?php echo $tpl['user_info']['user_id']?>">
	<button class="btn" id="reload-user-photos"><?php echo $lng['reload']?></button> (<?php echo $lng['if_photo_not_booted']?>)<br><br>

<canvas id="example_face" style="position: absolute; background-image: url('img/face.jpg'); background-size: 350px;" width="350" height="500"></canvas>
<canvas style="position: relative; top:0px; left:0px;" width="350" height="500"></canvas>

<canvas  id="face_coords_mouse" style="position: absolute; background-image: url('<?php echo $tpl['user_info']['miner_host'].'public/face_'.$tpl['user_info']['user_id'].'.jpg'; ?>'); background-size: 350px;" width="350" height="500"></canvas>
<canvas id="face" style="position: relative; top:0px; left:0px; height:500px" width="350" height="500"></canvas>

<br>


<br>

<canvas id="example_profile" style="position: absolute; background-image: url('img/profile.jpg'); background-size: 350px;" width="350" height="500"></canvas>
<canvas style="position: relative; top:0px; left:0px;" width="350" height="500"></canvas>

<canvas  id="profile_coords_mouse" style="position: absolute; background-image: url('<?php echo $tpl['user_info']['miner_host'].'public/profile_'.$tpl['user_info']['user_id'].'.jpg'; ?>'); background-size: 350px;" width="350" height="500"></canvas>
<canvas id="profile" style="position: relative; top:0px; left:0px; height:500px" width="350" height="500"></canvas>

<br><br>

		<?php echo $lng['attention']?>
    <br>
	    Comment: <input type="text" id="comment_step_1" value="">English only<br>
<button class="btn btn-inverse" id="btn-bad"><?php echo $lng['errors']?></button>
<button class="btn btn-success" id="btn-step2"><?php echo $lng['all_right']?></button>


<br><br><br>

<script>

	coords.getObject("face").init({
		for_mouse_move : "face_coords_mouse",
		example_area : "example_face",
		main_area : "face",
		type : "face",
		line_color : "#593AE0",
			<?php echo ( $tpl['user_info']['face_coords'] ) ? "user_coords: {$tpl['user_info']['face_coords']}," : "" ?>
		example_coords : [
			<?php
			print $tpl['user_info']['example_points']['face'] ; ?>
	]
	});



	coords.getObject("profile").init({
		for_mouse_move : "profile_coords_mouse",
		example_area : "example_profile",
		main_area : "profile",
		type : "profile",
		line_color : "#593AE0",
			<?php echo ( $tpl['user_info']['profile_coords'] ) ? "user_coords: {$tpl['user_info']['profile_coords']}," : "" ?>
		example_coords : [
			<?php
			print $tpl['user_info']['example_points']['profile'] ; ?>
	]
	});


	</script>
	</div>
	
	
	<!-- S T E P   2 -->

    <div id="step_2" style="display:none; ">
	    <p><?php echo $lng['tasks_new_miner_clones']?></p>
	    <button class="btn" onclick="reload_photo2(<?php echo $tpl['user_info']['user_id']?>, 'main_face', 'main_profile')"><?php echo $lng['reload']?></button>
		<div id="xx1" style="width:300px; position:fixed;">
			<div style="float:left;"><img id="main_face" src="<?php echo $tpl['user_info']['miner_host'].'public/face_'.$tpl['user_info']['user_id'].'.jpg'; ?>" style="width:150px; height:220px;"></div>
			<div><img id="main_profile" src="<?php echo $tpl['user_info']['miner_host'].'public/profile_'.$tpl['user_info']['user_id'].'.jpg'; ?>" style="float:left; width:150px; height:220px;"></div>
		</div>

		<div style="padding-top:220px">
			<?php
			if (isset($tpl['search']))
			for ($i=0; $i<sizeof($tpl['search']); $i++) {
				print '<div style="float:left;"><img id="face_'.$tpl['search'][$i]['user_id'].'" src="'.$tpl['search'][$i]['host'].'public/face_'.$tpl['search'][$i]['user_id'].'.jpg" style="width:150px; height:220px;"></div>
						<div style="float:left;"><img id="profile_'.$tpl['search'][$i]['user_id'].'" src="'.$tpl['search'][$i]['host'].'public/profile_'.$tpl['search'][$i]['user_id'].'.jpg" style="width:150px; height:220px;"></div>
						<div style="padding-top: 50px; height: 220px"><button class="btn" onclick="reload_photo2('.$tpl['search'][$i]['user_id'].', \'face_'.$tpl['search'][$i]['user_id'].'\', \'profile_'.$tpl['search'][$i]['user_id'].'\')">'.$lng['reload'].'</button><br>('.$lng['if_photo_not_booted'].')<br></div>';
			}
			?>
		</div>
	<div style="margin-top: 15px; ">
		Comment: <input type="text" id="comment_step_2" value="">English only<br>
		<button class="btn btn-success" id="btn-step1-back"><?php echo $lng['back']?></button>
		<button class="btn btn-inverse" id="btn-bad2"><?php echo $lng['errors']?></button>
		<button class="btn btn-success" id="btn-step3"><?php echo $lng['all_right']?></button>
	</div>


	</div>
    
	
	
	<!-- S T E P   3 -->
	
    <div id="step_3" style="display:none;">
	    <p><?php echo $lng['tasks_new_miner_video']?><br><button class="btn" onclick="reload_photo2(<?php echo $tpl['user_info']['user_id']?>, 'step_3_face', 'step_3_pofile')"><?php echo $lng['reload']?></button></p>
		<div style="width:300px;float: left;">
			<div style="float: left;"><img id="step_3_face" src="<?php echo $tpl['user_info']['miner_host'].'public/face_'.$tpl['user_info']['user_id'].'.jpg'; ?>" style="width:150px; height:220px;"></div>
			<div><img id="step_3_pofile" src="<?php echo $tpl['user_info']['miner_host'].'public/profile_'.$tpl['user_info']['user_id'].'.jpg'; ?>" style="width:150px; height:220px;"></div>
		</div>
		<div>
			<?php
			if ( $tpl['user_info']['video_url_id']!='null' )
				echo '<iframe width="320" height="240" src="http://www.youtube.com/embed/'.$tpl['user_info']['video_url_id'].'" frameborder="0" allowfullscreen></iframe>';
			else
				echo '<video class="video-js vjs-default-skin" controls preload="none" width="320" height="240" data-setup="{}"><source src="'.$tpl['user_info']['host'].'public/user_video.mp4" type="video/mp4" /><source src="'.$tpl['user_info']['host'].'public/user_video.webm" type="video/webm" /><source src="'.$tpl['user_info']['host'].'public/user_video.ogv" type="video/ogg" /></video>';
			?>

		</div>

	    Comment: <input type="text" id="comment_step_3" value="">(English only)<br>
		<button class="btn btn-success" id="btn-step2-back"><?php echo $lng['back']?></button>
		<button class="btn btn-inverse" id="btn-bad3"><?php echo $lng['errors']?></button>
		<button class="btn btn-success" id="btn-step4"><?php echo $lng['all_right']?></button>
		
    </div>

    <!-- S I G N -->
    
    <div id="sign" style="display:none">

		<label><?php echo $lng['data']?></label>
		<textarea id="for-signature" style="width:500px;" rows="4"></textarea><br>
	    <?php
	for ($i=1; $i<=$count_sign; $i++) {
		echo "<label>{$lng['sign']} ".(($i>1)?$i:'')."</label><textarea id=\"signature{$i}\" style=\"width:500px;\" rows=\"4\"></textarea>";
	    }
	    ?>
		<br>
		<button class="btn btn-success" id="btn-step3-back"><?php echo $lng['back']?></button>
		<button class="btn" id="send_to_net"><?php echo $lng['send_to_net']?></button>

    </div>
    
    <input type="hidden" id="user_id" value="<?php echo $_SESSION['user_id']?>">
    <input type="hidden" id="time" value="<?php echo time()?>">
    <input type="hidden" id="vote_id" value="<?php echo $tpl['user_info']['vote_id']?>">
	<input type="hidden" id="result" value="">

    
