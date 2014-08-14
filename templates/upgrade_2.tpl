

<script src="js/spots.js?r=<?php print rand(0, 99999)?>"></script>

<h1 class="page-header"><?php echo $lng['upgrade_title']?></h1>
<ol class="breadcrumb">
	<li><a href="#" onclick="fc_navigate('mining_menu')"><?php echo $lng['mining'] ?></a></li>
	<li class="active"><?php echo $lng['upgrade_title'] ?></li>
</ol>
	
    <ul class="nav nav-tabs">
		<li><a href="#" onclick="fc_navigate('upgrade_0')">Step 0</a></li>
		<li><a href="#" onclick="fc_navigate('upgrade_1')">Step 1</a></li>
		<li class="active"><a href="#" onclick="fc_navigate('upgrade_2')">Step 2</a></li>
		<li><a href="#" onclick="fc_navigate('upgrade_3')">Step 3</a></li>
	    <li><a href="#" onclick="map_navigate('upgrade_4')">Step 4</a></li>
	    <li><a href="#" onclick="fc_navigate('upgrade_5')">Step 5</a></li>
    </ul>
    
	<h3><?php echo $lng['put_points_on_photo']?></h3>


<div id="main1" style="position: relative;">


	<div id="comment-face" style="font-weight:bold;">1</div>

	<canvas id="example_face" style="position: absolute; background-image: url('img/face.jpg?r=<?php echo rand(0, getrandmax())?>'); background-size: 350px;" width="350" height="500"></canvas>
	<canvas style="position: relative; top:0px; left:0px;" width="350" height="500"></canvas>

	<canvas  id="face_coords_mouse" style="position: absolute; background-image: url('<?php echo $tpl['user_face'].'?r='.rand(0, getrandmax())?>'); background-size: 350px;" width="350" height="500"></canvas>
	<canvas id="face" style="position: relative; top:0px; left:0px; height:500px" width="350" height="500"></canvas>
	<br><input type="button" onclick="fclear('face')" value="clear face"><br>

	<br><br><br>

	<div id="comment-profile" style="font-weight:bold;">2</div>
	<canvas id="example_profile" style="position: absolute; background-image: url('img/profile.jpg?r=<?php echo rand(0, getrandmax())?>'); background-size: 350px;" width="350" height="500"></canvas>
	<canvas style="position: relative; top:0px; left:0px;" width="350" height="500"></canvas>

	<canvas  id="profile_coords_mouse" style="position: absolute; background-image: url('<?php echo $tpl['user_profile'].'?r='.rand(0, getrandmax())?>'); background-size: 350px;" width="350" height="500"></canvas>
	<canvas id="profile" style="position: relative; top:0px; left:0px; height:500px" width="350" height="500"></canvas>

	<br><input type="button" onclick="fclear('profile')" value="clear profile">





<script>

coords.getObject("face").init({
	for_mouse_move : "face_coords_mouse",
	example_area : "example_face",
	main_area : "face",
	type : "face",
	line_color : "#593AE0",
	<?php echo ( $tpl['face_coords'] ) ? "user_coords: {$tpl['face_coords']}," : "" ?>

	example_coords : [
		<?php echo $tpl['example_points']['face'] ; ?>
	]

});

coords.getObject("profile").init({

	for_mouse_move : "profile_coords_mouse",
	example_area : "example_profile",
	main_area : "profile",
	type : "profile",
	line_color : "#593AE0",
	<?php echo ( $tpl['profile_coords'] ) ? "user_coords: {$tpl['profile_coords']}," : "" ?>

	example_coords : [
		<?php echo $tpl['example_points']['profile'] ; ?>
	]
});		
	
	function fclear (name) {
		coords.getObject(name).clear();
	}

	$('#comment-face').text( '' );
	$('#comment-profile').text( '' );
	
	</script>
	</div>
	<br><br>
	
	<button class="btn btn-success" onclick="fc_navigate('upgrade_3')">Step 3</button>
	
	
	
	
	
	<br><br><br><br><br><br><br>
		
	
	<div class="for-signature"></div>
       
