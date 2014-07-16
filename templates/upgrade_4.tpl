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
						fc_navigate('upgrade_5');
					}
				}, "JSON");
	});

	function init (lat, lng, map_canvas, drag) {

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

			var lat = marker.getPosition().lat();
			lat = lat.toFixed(5);
			var lng = marker.getPosition().lng();
			lng = lng.toFixed(5);
			document.getElementById('latitude').value = lat;
			document.getElementById('longitude').value = lng;

		});
		marker.setMap(map);
	}


</script>

<!-- container -->
<div class="container">

	<legend><h2><?php echo $lng['upgrade_title']?></h2></legend>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
    <ul class="nav nav-tabs">
		<li><a href="#" onclick="fc_navigate('upgrade_0')">Step 0</a></li>
		<li><a href="#" onclick="fc_navigate('upgrade_1')">Step 1</a></li>
		<li><a href="#" onclick="fc_navigate('upgrade_2')">Step 2</a></li>
		<li><a href="#" onclick="fc_navigate('upgrade_3')">Step 3</a></li>
	    <li class="active"><a href="#" onclick="fc_navigate('upgrade_4')">Step 4</a></li>
	    <li><a href="#" onclick="fc_navigate('upgrade_5')">Step 5</a></li>
    </ul>
    
	<legend><?php echo $lng['your_location']?></legend>

	<div id="map_canvas" style="width: 640px; height: 480px; margin-bottom:20px; display:none"></div>
	<input id="latitude" class="input" type="text" placeholder="latitude" value="<?php echo $tpl['geolocation_lat']?>"><input id="longitude" class="input" type="text" placeholder="longitude" value="<?php echo $tpl['geolocation_lon']?>">
	<br>
	<button class="btn btn-success" id="save"><?php echo $lng['save_and_goto_step_5']?></button>

	<br><br><br><br><br><br><br>

</div>
<!-- /container -->
<script>
	init (<?php echo $tpl['geolocation']?>, 'map_canvas', true);
	google.maps.event.trigger(map, 'resize');
</script>