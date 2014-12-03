<script>

$('#next').bind('click', function () {

	<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#main").css("display", "none");	$("#sign").css("display", "block");' ?>

	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#currency_id").val()+','+$("#amount").val()+','+$('#end_time').val()+','+$("#latitude").val()+','+$("#longitude").val()+','+$("#category_id").val()+','+$("#cf_currency").val());
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
});

function make_my_time(days)
{
	var unixtime = Number((new Date().getTime() / 1000).toFixed(0));
	var end_time = Number(days) * 3600*24 + unixtime;
	$('#end_time').val(end_time);
	var end_date = new Date(end_time*1000);
	var curr_date = end_date.getDate();
	var curr_month = end_date.getMonth() + 1;
	var curr_year = end_date.getFullYear();
	var curr_min = end_date.getMinutes();
	var curr_hour = end_date.getHours();
	$('#end_date').text(curr_date+"/"+curr_month+"/"+curr_year+" "+curr_hour+":"+curr_min);
}

$('#days').on('change', function() {
	make_my_time(this.value);
});

$('#send_to_net').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'currency_id' : $('#currency_id').val(),
			'amount' : $('#amount').val(),
			'end_time' : $('#end_time').val(),
			'latitude' : $('#latitude').val(),
			'longitude' : $('#longitude').val(),
			'category_id' : $('#category_id').val(),
			'currency_name' : $('#cf_currency').val(),
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
		}, function (data) {
				fc_navigate ('my_cf_projects', {'alert': '<?php echo $lng['sent_to_DC_CF'] ?>'} );
			}
	);
} );

function init (lat, lng, map_canvas, drag) {

	$("#"+map_canvas).css("display", "block");

	var point = new google.maps.LatLng(lat, lng);
	var geocoder = new google.maps.Geocoder();

	var mapOptions = {
		center: point,
		zoom: 1,
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


		geocoder.geocode({'latLng': marker.getPosition()}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				if (results[results.length-2]) {
					$("#my_location").html(results[results.length-2].formatted_address);
				} else {
					alert('No results found');
				}
			} else {
				alert('Geocoder failed due to: ' + status);
			}
		});




	});
	marker.setMap(map);
}

$('#check_cf_currency').bind('click', function () {
	var cf_currency = $("#cf_currency").val().toUpperCase();
	$("#cf_currency").val(cf_currency);
	$.post( 'ajax/check_cf_currency.php', {
				'project_currency_name' : cf_currency
			}, function (data) {
				if (data.success) {
					$("#check_result").attr( "class", "has-success" );
					$("#check_result_text").html('<label class="control-label">'+data.success+'</label>');
				}
				else {
					$("#check_result").attr( "class", "has-error" );
					$("#check_result_text").html('<label class="control-label">error: '+data.error+'</label>');
				}
			},
			'JSON'
	);

});

$('#cf_currency').keyup(function(e) {
	$("#cf_currency").val($("#cf_currency").val().toUpperCase());
});

</script>

	<h1 class="page-header"><?php echo $lng['new_cf_project_title']?></h1>
	<ol class="breadcrumb">
		<li><a href="#">CrowdFunding</a></li>
		<li><a href="#my_cf_projects"><?php echo $lng['my_cf_projects_title']?></a></li>
		<li class="active"><?php echo $lng['new_project']?></li>
	</ol>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	
	<div id="main">
		<form class="form-horizontal">
			<fieldset>

				<div class="form-group">
					<label class="col-md-4 control-label" for="amount"><?php echo $lng['amount']?></label>
					<div class="col-md-4">
						<div class="input-group">
							<input style="min-width: 100px" id="amount" name="amount" class="form-control" type="text">
							<div class="input-group-btn">
								<select class="form-control" id="currency_id" style="min-width: 100px">
									<?php
									foreach ($tpl['currency_list'] as $id=>$name) {
										$sel = '';
										if ($id==72)
											$sel = 'selected';
										echo "<option value='{$id}' {$sel}>D{$name}</option>";
									}
									?>
								</select>
							</div>
						</div>
						<span class="help-block"><?php echo $lng['cf_target']?></span>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-4 control-label" for="days"><?php echo $lng['number_of_days']?></label>
					<div class="col-md-4">
						<select id="days" name="days" class="form-control">
							<?php
								for ($i=7; $i<=90; $i++)
									echo "<option value='{$i}'>{$i}</option>";
							?>
						</select>
						<span class="help-block"><?php echo $lng['how_many_days']?></span>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-4 control-label" for="days"><?php echo $lng['end_date']?></label>
					<div class="col-md-4">
						<p class="form-control-static" id="end_date"></p>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-4 control-label" for="category_id"><?php echo $lng['category']?></label>
					<div class="col-md-4">
						<select id="category_id" name="category_id" class="form-control">
							<?php
							foreach ($lng['cf_category'] as $id=>$name)
								echo "<option value='{$id}'>{$name}</option>";
							?>
						</select>
						<span class="help-block"><?php echo $lng['category_for_your_project']?></span>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-4 control-label" for="cf_currency"><?php echo $lng['name_of_the_currency']?></label>
					<div class="col-md-4">
						<div id="check_result">
							<div id="check_result_text"></div>
							<div class="input-group">
								<input style="min-width: 100px" id="cf_currency" name="cf_currency" class="form-control" type="text">
								<div class="input-group-btn">
									<button type="button" class="btn btn-primary" id="check_cf_currency"><?php echo $lng['check_currency_name']?></button>
								</div>
							</div>
						</div>
						<span class="help-block"><?php echo $lng['name_for_currency']?></span>
					</div>
				</div>


				<div class="form-group">
					<label class="col-md-4 control-label" for="city"><?php echo $lng['your_city']?></label>
					<div class="col-md-4">
						<div id="my_location" style="font-weight: bold"><?php echo $tpl['city']?></div>
						<a id="show_map" href="#"><?php echo $lng['show_map']?></a>
						<div id="map_canvas" style="width: 400px; height: 300px; display:none"></div>
						<span class="help-block"><?php echo $lng['your_city_on_map']?></span>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-4 control-label" for="singlebutton"></label>
					<div class="col-md-4">
						<button type="button" class="btn btn-outline btn-primary" id="next"><?php echo $lng['send_to_net']?></button>
					</div>
				</div>

			</fieldset>
		</form>

		<input id="latitude" class="input" type="hidden" value="<?php echo $tpl['latitude']?>">
		<input id="longitude" class="input" type="hidden" value="<?php echo $tpl['longitude']?>">
		<input id="end_time" class="input" type="hidden" value="<?php echo time()+3600*24*7+3600?>">


		<div class="alert alert-info">
			<strong><?php echo $lng['limits']?>:</strong> <?php echo $lng['cf_new_projects_limit']?>
		</div>

	</div>

	<?php require_once( 'signatures.tpl' );?>

<script>
	$('#show_map').bind('click', function () {
		init (<?php echo $tpl['latitude'].','.$tpl['longitude']?>, 'map_canvas', true);
		google.maps.event.trigger(map, 'resize');
	});
	make_my_time(7);
</script>