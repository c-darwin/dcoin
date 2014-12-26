<script>

function write_for_signature (result) {
	if ($('#comment').val()=='') {
		$('#comment').val('null');
	}
	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']},{$tpl['data']['id']}"?>,'+result+','+$('#comment').val() );
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
}

$('#btn-bad').bind('click', function () {

	$('#step_1').css('display', 'none');	
	$('#sign').css('display', 'block');	
	$('#result').val( '0' );

	write_for_signature(0);

} );


$('#btn-success').bind('click', function () {
	
	$('#step_1').css('display', 'none');
	$('#sign').css('display', 'block');	
	$('#result').val( '1' );

	write_for_signature(1);

} );

$('#send_to_net').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'promised_amount_id' : $('#promised_amount_id').val(),
			'result' : $('#result').val(),
			'comment' : $('#comment').val(),
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
			}, function () {
				fc_navigate ('tasks', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
		} );

});

function map_init (lat, lng, map_canvas, drag) {
	$("#"+map_canvas).css("display", "block");

	var point = new google.maps.LatLng(lat, lng);
	var mapOptions = {
		center: point,
		zoom: 15,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		streetViewControl: false
	};
	map = new google.maps.Map(document.getElementById(map_canvas), mapOptions);

	var marker = new google.maps.Marker({
		position: point,
		map: map,
		draggable: drag,
		title: 'You'
	});

	google.maps.event.trigger(map, 'resize');

	google.maps.event.addListener(marker, "dragend", function() {
		document.getElementById('latitude').value = marker.getPosition().lat();
		document.getElementById('longitude').value = marker.getPosition().lng();

	});
	marker.setMap(map);
}


$('#show_map').bind('click', function () {
	map_init (<?php echo $tpl['data']['user_info']['latitude']?>, <?php echo $tpl['data']['user_info']['longitude']?>, 'map_canvas', true);
	google.maps.event.trigger(map, 'resize');
});




var photo_hosts = [];
var photo_hosts = ['<?php echo implode("','", $tpl['data']['photo_hosts']);?>'];

function get_miner_photos (i) {

	console.log('get_miner_photos');
	var image = new Image();
	var photo_url = photo_hosts[i]+"public/face_<?php echo $tpl['data']['user_info']['user_id']?>.jpg";
	if (typeof photo_hosts[i] != 'undefined' && photo_hosts[i]!='' && photo_hosts[i]!='0') {
		image.src = photo_url;
		image.onload = function(){
			image = null;
			console.log('#face_coords_mouse = url('+photo_hosts[i]+'public/profile_<?php echo $tpl['data']['user_info']['user_id']?>.jpg)');
			$('#face_img').css("background", "url('"+photo_hosts[i]+"public/face_<?php echo $tpl['data']['user_info']['user_id']?>.jpg')  no-repeat 50% 50%");
			$('#face_img').css("background-size", "300px Auto");
			$('#profile_img').css("background", "url('"+photo_hosts[i]+"public/profile_<?php echo $tpl['data']['user_info']['user_id']?>.jpg') no-repeat 50% 50%");
			$('#profile_img').css("background-size", "300px Auto");
		};
		// handle failure
		image.onerror = function(){
			image = null;
			console.log('error get_miner_photos '+photo_url);
			get_miner_photos (i+1);
		};
		setTimeout
		(
			function()
			{
				if ( image!=null && (!image.complete || !image.naturalWidth) )
				{
					image = null;
					console.log('timeout error get_miner_photos '+photo_url);
					get_miner_photos (i+1);
				}
			},
			3000
		);
	}
	else {
		console.log('null');
	}
}

$(function() {
	get_miner_photos (0);
});

</script>

<h1 class="page-header"><?php echo $lng['tasks_title_promised_amount']?></h1>
<ol class="breadcrumb">
	<li><a href="#mining_menu"><?php echo $lng['mining'] ?></a></li>
	<li><a href="#tasks"><?php echo $lng['tasks_title'] ?></a></li>
</ol>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	
	<div id="step_1">

		<?php echo $lng['new_promise_amount']?>
		<div class="clearfix"></div>
				<div style="float:left;width:300px; height:440px" id="face_img"></div>
				<div style="float:left;width:300px; height:440px" id="profile_img"></div>
				<div style="float:left;width:320px; margin-left: 5px">
					<?php
					echo $lng['check_video'].'<br>';
					if ( $tpl['data']['video_url_id']!='null' )
					echo '<iframe width="320" height="240" src="http://www.youtube.com/embed/'.$tpl['data']['video_url_id'].'" frameborder="0" allowfullscreen></iframe>';
					else
					echo '<video class="video-js vjs-default-skin" controls preload="none" width="320" height="240" data-setup="{}"><source src="'.$tpl['data']['host'].'public/promised_amount_'.$tpl['data']['currency_id'].'.mp4" type="video/mp4" /><source src="'.$tpl['data']['host'].'public/promised_amount_'.$tpl['data']['currency_id'].'.webm" type="video/webm" /><source src="'.$tpl['data']['host'].'public/promised_amount_'.$tpl['data']['currency_id'].'.ogv" type="video/ogg" /></video>';
					?>
				</div>
		<input type="hidden" id="candidate-id" value="<?php echo $tpl['data']['user_info']['user_id']?>">
		<div class="clearfix"></div>
		<!-- снизу - юзер на  карте -->
		<?php echo $lng['location_on_map']?>
		<br>
		<button type="button" class="btn btn-primary" id="show_map"><?php echo $lng['show_miner_on_map']?></button><br><br>
		<div id="map_canvas" style="width: 640px; height: 480px; display: none"></div>
		<script>
			//init (<?php echo $tpl['data']['user_info']['latitude']?>, <?php echo $tpl['data']['user_info']['longitude']?>, 'map_canvas');
		</script>

		<?php echo $lng['main_question']?><br>

		Comment: <input type="text" id="comment" value="" class="form-control"><br>
		<button class="btn btn-inverse" id="btn-bad"><?php echo $lng['no']?></button>
		<button class="btn btn-success" id="btn-success"><?php echo $lng['yes']?></button>
	</div>

	<?php require_once( 'signatures.tpl' );?>
    
    <input type="hidden" id="user_id" value="<?php echo $tpl['data']['user_id']?>">
    <input type="hidden" id="promised_amount_id" value="<?php echo $tpl['data']['id']?>">
    <input type="hidden" id="time" value="<?php echo time()?>">
    <input type="hidden" id="result">
    
