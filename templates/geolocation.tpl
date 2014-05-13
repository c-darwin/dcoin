<!-- container -->
<div class="container">

<script>
function next_step()
{
	$("#geo").css("display", "none");
	$("#sign").css("display", "block");
	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#latitude").val()+','+$("#longitude").val()+','+$("#country").val() );
	doSign();
}

$('#send_to_net').bind('click', function () {
	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'latitude' : $('#latitude').val(),
			'longitude' : $('#longitude').val(),
			'country' : $('#country').val(),
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
			}, function () {
					fc_navigate ('geolocation', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			} );
} );


</script>

<script>

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

			document.getElementById('latitude').value = marker.getPosition().lat().toFixed(5);
			document.getElementById('longitude').value = marker.getPosition().lng().toFixed(5);
			
		});
		marker.setMap(map);
}
</script>

  <legend><h2><?php echo $lng['geolocation_title']?></h2></legend>
  <?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	<div id="geo">
		<p><?php  echo $lng['location_alert']?></p>
		<br>
		<strong>Country</strong><br>
				<?php
				echo "<select id='country'><option value='0'></option>";
				for ($i=0; $i<sizeof($tpl['countries']); $i++)
				echo "<option value='{$i}' ".($i==$tpl['country']?'selected':'').">{$tpl['countries'][$i]}</option>\n";
				echo '</select>';
				?>

		<div id="map_canvas" style="width: 640px; height: 480px; margin-bottom:20px; display:none"></div>
		<input id="latitude" class="input" type="text" placeholder="latitude" value="<?php echo $tpl['geolocation_lat']?>"><input id="longitude" class="input" type="text" placeholder="longitude" value="<?php echo $tpl['geolocation_lon']?>">
		<br>
		<button class="btn" onclick="next_step()"><?php echo $lng['next'] ?></button>
	</div>
    
    <div id="new" style="display:none">
		<label><?php echo $lng['new_geolocation']?></label>
		<div id="map_canvas" style="width: 640px; height: 480px;"></div>
		<input id="latitude" class="input" type="text" placeholder="latitude"><input id="longitude" class="input" type="text" placeholder="longitude">
		<br>
		<button class="btn" onclick="next_step()"><?php echo $lng['next']?></button>

    </div>

<?php require_once( 'signatures.tpl' );?>
    
    <br><br><p><span class="label label-important"><?php echo $lng['limits']?></span> <?php echo $tpl['limits_text'] ?></p>


</div>
<!-- /container -->

<script>
	init (<?php echo $tpl['geolocation']?>, 'map_canvas', true);
	google.maps.event.trigger(map, 'resize');
</script>