<script>
	$(document).ready(function() {
		$( "#progress_bar" ).load( "ajax/progress_bar.php");
	});
</script>
<script src="js/index.js"></script>

<script>

	function geoFindMe(){

		if(navigator.geolocation){
			navigator.geolocation.getCurrentPosition(success, error, geo_options);
		}else{
			alert("Geolocation services are not supported by your web browser.");
		}

		function success(position) {
			latitude = position.coords.latitude;
			longitude = position.coords.longitude;
			var altitude = position.coords.altitude;
			var accuracy = position.coords.accuracy;
			console.log('success: '+latitude+','+longitude);
			$('#latitude').val(latitude);
			$('#longitude').val(longitude);
		}

		function error(error) {
			console.log("Unable to retrieve your location due to "+error.code + " : " + error.message);
		};

		var geo_options = {
			enableHighAccuracy: true,
			maximumAge : 30000,
			timeout : 27000
		};
	}
	<?php
	if (!$tpl['geolocation_lat']) {
		echo "var latitude = 39.94887\n";
		echo "var longitude = -75.15005\n";
		echo "geoFindMe()\n";
	}
	else {
		echo "var latitude ={$tpl['geolocation_lat']}\n";
		echo "var longitude = {$tpl['geolocation_lon']}\n";
	}
	?>
	$('#latitude').val(latitude);
	$('#longitude').val(longitude);

</script>


<script>

	$('#save').bind('click', function () {
		$('#alert').css("display", "none");

		var latitude = $('#latitude').val();
		var longitude = $('#longitude').val();
		$.post( 'ajax/save_geolocation.php', { 'geolocation' : latitude+', '+longitude } ,
				function (data) {
					if (data.error) {
						$('#alert').css("display", "block");
					}
					else {
						fc_navigate('upgrade_7');
					}
				}, "JSON");
	});

	$('#show_map').bind('click', function () {

		map_init (latitude, longitude, 'map_canvas', true, true);
		google.maps.event.trigger(map, 'resize');
	});

	$("#main_div input").addClass( "form-control" );
	$("#main_div input").width( 150 );

</script>
<div id="main_div">
<h1 class="page-header"><?php echo $lng['upgrade_title']?></h1>
<ol class="breadcrumb">
	<li><a href="#mining_menu"><?php echo $lng['mining'] ?></a></li>
	<li class="active"><?php echo $lng['upgrade_title'] ?></li>
</ol>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
    <ul class="nav nav-tabs">
		<?php echo make_upgrade_menu(6)?>
    </ul>
    
	<h3><?php echo $lng['your_location']?></h3>
<button type="button" class="btn btn-primary" id="show_map">Show map</button><br><br>
	<div id="map_canvas" style="width: 640px; height: 480px; margin-bottom:20px; display:none"></div>
	<input id="latitude" class="input" type="text" placeholder="latitude" value="<?php echo $tpl['geolocation_lat']?>"><input id="longitude" class="input" type="text" placeholder="longitude" value="<?php echo $tpl['geolocation_lon']?>">
	<br>
	<button class="btn btn-success" id="save"><?php echo str_replace('[num]','7',$lng['save_and_goto_step'])?></button>

	<br><br><br><br><br><br><br>
	</div>