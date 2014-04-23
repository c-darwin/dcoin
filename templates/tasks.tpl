<!-- container -->
<div class="container">

<script src="js/spots.js"></script>
<script>
$('#btn-bad,#btn-bad2,#btn-bad3').bind('click', function () {

	$('#step_1').css('display', 'none');
	$('#step_2').css('display', 'none');
	$('#step_3').css('display', 'none');	
	$('#sign').css('display', 'block');	
	$('#result').val( '0' );
	
	$("#for-signature").val( '5,'+$('#time').val()+','+$('#user_id').val()+','+$('#contender_id').val()+','+$('#result').val() );

	
} );

$('#btn-step2').bind('click', function () {
	
	$('#step_1').css('display', 'none');
	$('#step_2').css('display', 'block');
	$('#title').css('display', 'none');

} );

$('#btn-step3').bind('click', function () {
	
	$('#step_2').css('display', 'none');
	$('#step_3').css('display', 'block');
	$('#title').css('display', 'block');

} );

$('#btn-step4').bind('click', function () {
	
	$('#step_1').css('display', 'none');
	$('#step_2').css('display', 'none');
	$('#step_3').css('display', 'none');	
	$('#sign').css('display', 'block');	
	$('#result').val( '1' );
	
	$("#for-signature").val( '5,'+$('#time').val()+','+$('#user_id').val()+','+$('#contender_id').val()+','+$('#result').val() );

	

} );

$('#send_data').bind('click', function () {
	
	$.post( 'ajax/save_queue.php', {
			'type' : 'votes_miner',
			'time' : $('#time').val(), 
			'user_id' : $('#user_id').val(), 
			'contender_id' : $('#contender_id').val(), 
			'result' : $('#result').val(), 
						'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
			}, function () { } );
	fc_navigate ('tasks', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
} );
	
</script>
	<legend id="title"><h2><?php echo $lng['tasks_title']?></h2></legend>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	<!-- S T E P   1 -->
	
 	<div id="step_1" style="position: relative;">

<canvas id="example_face" style="position: absolute; background-image: url('<?php echo $tpl['user_profile']?>'); background-size: 300px;" width="300" height="500"></canvas>
<canvas style="position: relative; top:0px; left:0px;" width="300" height="500"></canvas>


<canvas  id="face_coords_mouse" style="position: absolute; background-image: url(img/profile.jpg); background-size: 300px;" width="300" height="500"></canvas>
<canvas id="face" style="position: relative; top:0px; left:0px; height:500px" width="300" height="500"></canvas>

<br>


<br>

<canvas id="example_profile" style="position: absolute; background-image: url('<?php echo $tpl['user_face']?>'); background-size: 300px;" width="300" height="500"></canvas>
<canvas style="position: relative; top:0px; left:0px;" width="300" height="500"></canvas>


<canvas  id="profile_coords_mouse" style="position: absolute; background-image: url(img/face.jpg); background-size: 300px;" width="300" height="500"></canvas>
<canvas id="profile" style="position: relative; top:0px; left:0px; height:500px" width="300" height="500"></canvas>

<br><br>

    <?php echo $lng['attention']?>
    <br>

<button class="btn btn-inverse" id="btn-bad"><?php echo $lng['errors']?></button>
<button class="btn btn-success" id="btn-step2"><?php echo $lng['all_right']?></button>


<br><br><br>

<script>
coords.getObject("face").init({

	for_mouse_move : "face_coords_mouse",
	example_area : "example_face",
	main_area : "face",
	<?php echo ( $tpl['user_info']['face_coords'] ) ? "user_coords: {$tpl['user_info']['face_coords']}," : "" ?>
	
	example_coords : [
				[100, 200], // 0
				[120, 220, [0, 1, 'naprav'] ], // 1
				[133, 249, [0, 1, 'p2p'] ], // 2
				[130, 290, [2, 3, 'make_ugol_left_bottom'] ], // 3
				[166, 255, [3, 4, 'make_ugol_right_bottom'] ], // 4
				[156, 223, [3, 4, 'x_line']], // 5
				[133, 269], // 6
				[194, 295], // 7
				[111, 264], // 8
				[132, 212], // 9
				[182, 298]  // 10
			]
});




coords.getObject("profile").init({

	for_mouse_move : "profile_coords_mouse",
	example_area : "example_profile",
	main_area : "profile",
	<?php echo ( $tpl['user_info']['profile_coords'] ) ? "user_coords: {$tpl['user_info']['profile_coords']}," : "" ?>
	
	example_coords : [
				[100, 200], // 0
				[120, 220, [0, 1, 'naprav'] ], // 1			
				[133, 249, [0, 1, 'p2p'] ], // 2
				[130, 290, [2, 3, 'make_ugol_left_bottom'] ], // 3
				[166, 255, [3, 4, 'make_ugol_right_bottom'] ], // 4
				[156, 223, [3, 4, 'x_line']], // 5
				[133, 269], // 6
				[194, 295], // 7
				[111, 264], // 8
				[132, 212], // 9
				[182, 298]  // 10
			]
});		
	
	

	</script>
	</div>
	
	
	<!-- S T E P   2 -->
	
    <div id="step_2" style="display:none; width:300px;">
	
		<div id="xx1" style="width:300px; position:fixed;">
			<div style="float: right;"><img src="img/face.jpg" style="width:150px; height:220px;"></div>
			<div><img src="img/profile.jpg" style="width:150px; height:220px;"></div>
		</div>
		
		<div style="width:300px;padding-top:220px">
			<?php
			for ($i=0; $i<20; $i++) {
				print '<div style="float: right;"><img src="img/face.jpg" style="width:150px; height:220px;"></div>
			<div><img src="img/profile.jpg" style="width:150px; height:220px;"></div>';
			}
			?>
		</div>
		<br><br>
		<button class="btn btn-inverse" id="btn-bad2"><?php echo $lng['clones_are_present']?></button>
		<button class="btn btn-success" id="btn-step3"><?php echo $lng['all_right']?></button>
		<br><br>

    </div>
    
	
	
	<!-- S T E P   3 -->
	
    <div id="step_3" style="display:none;">
	
		<div style="width:300px;float: left;">
			<div style="float: right;"><img src="img/face.jpg" style="width:150px; height:220px;"></div>
			<div><img src="img/profile.jpg" style="width:150px; height:220px;"></div>
		</div>
		<div>
			<iframe width="320" height="240" src="http://www.youtube.com/watch?v=ZSt9tm3RoUU" frameborder="0" allowfullscreen></iframe>
		</div>
		
		<button class="btn btn-inverse" id="btn-bad3"><?php echo $lng['something_is_wrong']?></button>
		<button class="btn btn-success" id="btn-step4"><?php echo $lng['all_right']?></button>
		
    </div>
    
    
    
    <!-- S I G N -->
	<?php require_once( 'signatures.tpl' );?>
    
    <input type="hidden" id="user_id" value="<?php echo $_SESSION['DC_ADMIN']?>">
    <input type="hidden" id="time" value="<?php echo time()?>">
    <input type="hidden" id="contender_id" value="<?php echo $tpl['user_info']['user_id']?>">
    <input type="hidden" id="result" value="">
    
</div>
<!-- /container -->