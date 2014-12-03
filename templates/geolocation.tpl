<script src="js/index.js"></script>
<script>
function next_step()
{
	$("#geo").css("display", "none");
	$("#sign").css("display", "block");
	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#latitude").val()+','+$("#longitude").val()+','+$("#country").val() );
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
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

$('#show_map').bind('click', function () {
	map_init (<?php echo $tpl['geolocation']?>, 'map_canvas', true, true);
	google.maps.event.trigger(map, 'resize');
});

$("#main_div select").addClass( "form-control" );
$("#main_div input").addClass( "form-control" );
$("#main_div button").addClass( "btn-outline btn-primary" );

$("#main_div input[type=text]").width( 200 );

</script>
<div id="main_div">
<h1 class="page-header"><?php echo $lng['geolocation_title']?></h1>
<ol class="breadcrumb">
	<li><a href="#mining_menu"><?php echo $lng['mining'] ?></a></li>
	<li class="active"><?php echo $lng['geolocation_title'] ?></li>
</ol>

  <?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	<div id="geo">
		<p><?php  echo $lng['location_alert']?></p>
		<br>
		<strong>Country</strong><br>
				<?php
				echo "<select id='country' class=\"form-control\" style=\"width:300px\"><option value='0'></option>";
				for ($i=0; $i<sizeof($tpl['countries']); $i++)
				echo "<option value='{$i}' ".($i==$tpl['country']?'selected':'').">{$tpl['countries'][$i]}</option>\n";
				echo '</select>';
				?>
		<br>
		<button type="button" class="btn btn-primary" id="show_map">Show map</button><br><br>

		<div id="map_canvas" style="width: 640px; height: 480px; margin-bottom:20px; display:none"></div>
		<input id="latitude" class="input" type="text" placeholder="latitude" value="<?php echo $tpl['geolocation_lat']?>"><input id="longitude" class="input" type="text" placeholder="longitude" value="<?php echo $tpl['geolocation_lon']?>">
		<br>
		<button class="btn" onclick="next_step()"><?php echo $lng['next'] ?></button>
	</div>
    
    <div id="new" style="display:none">
		<label><?php echo $lng['new_geolocation']?></label>
		<div id="map_canvas" style="width: 640px; height: 480px;"></div>
		<input id="latitude" type="text" placeholder="latitude" class="form-control">
	    <input id="longitude"  type="text" placeholder="longitude" class="form-control">
	    <br><br>
		<button class="btn" onclick="next_step()"><?php echo $lng['next']?></button>

    </div>

<?php require_once( 'signatures.tpl' );?>
    
    <br><br><div class="alert alert-info"><?php echo $lng['limits']?> <?php echo $tpl['limits_text'] ?></div>
</div>