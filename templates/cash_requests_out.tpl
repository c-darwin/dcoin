
<style>

	.table td {
		vertical-align: middle;
	}
	.table input, .table textarea {
		margin-bottom: 0px;
	}
</style>


<!-- container -->
<div class="container">
<script>
$('#next').bind('click', function () {

	var error_message='';
	to_user_id = $("#to_user_id").text();
	comment = $("#comment").val();

	if ( comment.length<10 ) {
		error_message = '<?php echo $lng['invalid_contacts']?>';
	}
	if ( !to_user_id ) {
		error_message = '<?php echo $lng['user_not_selected']?>';
	}
	if (error_message!='') {
		$("#message").html( '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>'+error_message+'</div>' );
	}
	else {
		$.post( 'ajax/encrypt_comment.php', {

			'to_user_id' : to_user_id,
			'comment' :  $("#comment").val()

		}, function (data) {

			//alert(data);

			$("#comment_encrypted").val(data);

			$("#wallets").css("display", "none");
			$("#onmap").css("display", "none");
			$("#sign").css("display", "block");
			$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"?>,'+$('#to_user_id').text()+','+$('#send_amount').val()+','+data+','+$('#currency_id').val()+','+$('#hash_code').val() );
			doSign();
			<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
		});
	}
} );

$('#send_to_net').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'to_user_id' : $('#to_user_id').text(),
			'currency_id' : currency_id,
			'amount' : $('#send_amount').val(),
			'hash_code' : $('#hash_code').val(),
			'code' : $('#code').val(),
			'comment' : $('#comment_encrypted').val(),
			'comment_text' : $('#comment').val(),
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
			}, function (data) { } );

	fc_navigate ('cash_requests_out', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );

} );


</script>

<script>
var map;

var currency_name;
var currency_id;

var currency_data = '{<?php print $tpl['json_currency_wallets']?>}';
currency_data = JSON.parse(currency_data);

$('#show_map').bind('click', function () {

	$("#amount,#total_amount,#total_amount_text,#to_user_id").text(0);

	$("#new").css("display", "block");
	$("#map_canvas_list").css("display", "none");

	//min_amount = $("#need_min_amount").val();
	min_amount=100;

	currency_id = $("#need_currency :selected").val();
	$("#currency_id").val(currency_id);
	currency_name = currency_data[currency_id][0];
	$("#available").text(currency_data[currency_id][1]+' D'+currency_name);
	$("[id = 'currency_name']").text(currency_name);

	$.post('ajax/miners_map.php', {'min_amount': min_amount, 'currency_id': currency_id}, function(data) {

					var markers = [];

					var center = new google.maps.LatLng(35, -100);

					map = new google.maps.Map(document.getElementById('map_canvas'), {
						zoom: 1,
						center: center,
						mapTypeId: google.maps.MapTypeId.ROADMAP
					});
					google.maps.event.trigger(map, 'resize');

					infowindow = new google.maps.InfoWindow({ maxWidth: 350 });

					markers.markerClickFunction = function(user_id, amount, currency, latlng) {
						return function(e) {
							e.cancelBubble = true;
							e.returnValue = false;
							if (e.stopPropagation) {
								e.stopPropagation();
								e.preventDefault();
							}

							min = Math.ceil(amount/<?php echo $tpl['min_promised_amount']?>)
							infowindow.setContent('User ID: '+user_id+'<input type="hidden" id="find_user_id" value="'+user_id+'"><br>min:<Br>'+min.toFixed(0)+'<br>max:'+amount);
							infowindow.setContent('User ID: '+user_id+'<input type="hidden" id="find_user_id" value="'+user_id+'"><br>min:<Br>'+min.toFixed(0)+'<br>max:'+amount);
							$("#to_user_id").text(user_id);
							$('#send_amount').val(min);
							$("#amount_due").text(min);
							infowindow.setPosition(latlng);
							infowindow.open(map);
						};
					};


					for (var i = 0; i < data.info.length; i++) {

						var datainfo = data.info[i];

						var latLng = new google.maps.LatLng(datainfo.latitude,
								datainfo.longitude);
						var marker = new google.maps.Marker({
							position: latLng
						});

						var fn = markers.markerClickFunction(datainfo.user_id, datainfo.amount, datainfo.currency, latLng);
						google.maps.event.addListener(marker, 'click', fn);

						markers[i] = marker;

					}

					var markerCluster = new MarkerClusterer(map, markers);

	}, 'json');
	google.maps.event.trigger(map, 'resize');
});

$('#send_amount').keyup(function(e) {
	$("#amount_due").text($('#send_amount').val());
});

</script>

	<legend><h2><?php echo $lng['cash_request_out_title']?></h2></legend>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

	<div id="onmap">
		<h3><?php echo $lng['search']?></h3>
		<div class="form-inline" style="padding-bottom: 10px">
				<select id="need_currency" class="span2">
					<?php
					foreach ($tpl['available_currency'] as $k => $currency_id)
						print "<option value='{$currency_id}'>{$tpl['currency_list'][$currency_id]}</option>";
					?>
				</select>
			<button class="btn" id="show_map"><?php echo $lng['find_on_map']?></button>
		</div>



		<div id="new" style="display:none">

			<div id="map_canvas" style="width: 640px; height: 320px;"></div>
			<br>


		</div>

	</div>


	<div id="wallets">
		<h3><?php echo $lng['send_request']?></h3>
		<table style="width: 380px" class="table">
		<tr><td><?php echo $lng['to']?></td><td><span id="to_user_id"></span></td></tr>
		<tr><td><?php echo $lng['available']?></td><td><span id="available"></span><input id="currency_id" type="hidden"></td></tr>
		<tr><td><?php echo $lng['you_send']?></td><td><input type="text" id="send_amount" class="input-mini"> D<span id="currency_name"></span></td></tr>
		<tr><td><?php echo $lng['amount_due']?></td><td><span id="amount_due"></span> <span id="currency_name"></span></td></tr>
		<tr><td><?php echo $lng['you_contacts']?></td><td><textarea id="comment"></textarea></td></tr>
		<tr><td><?php echo $lng['code']?></td><td><?php echo $tpl['code']?><br>(<?php echo $lng['after_transfer']?>)</td></tr>
		</table>
		<div id="message"></div>
		<button id="next" class="btn btn-primary" type="button"><?php echo $lng['next']?></button>

		<div style="padding-top:10px"><p><span class="label label-important"><?php echo $lng['limits']?></span> <?php echo $tpl['limits_text']?> </p></div>


	</div>

	<?php require_once( 'signatures.tpl' );?>

	<div id="list">
	<?php
	if (isset($tpl['my_cash_requests'])) {
		echo '<h3>'.$lng['you_requests'].'</h3>';
		echo '<table class="table" style="width:500px">';
		echo '<tr><th>'.$lng['time'].'</th><th>'.$lng['currency'].'</th><th>'.$lng['recipient'].'</th><th>'.$lng['amount'].'</th><th>'.$lng['secret_code'].'</th><th>'.$lng['comment'].'</th><th>'.$lng['status'].'</th></tr>';
		foreach ($tpl['my_cash_requests'] as $key => $data) {
			print "<tr>";
				if ($data['time'])
					print "<td>".date('d-m-Y H:i:s', $data['time'])."</td>";
				else
					print "<td></td>";
				print "<td>{$tpl['currency_list'][$data['currency_id']]}</td><td>{$data['to_user_id']}</td><td>{$data['amount']}</td><td>{$data['code']}</td><td>{$data['comment']}</td><td>".$cash_requests_status[$data['status']]."</td></tr>";
		}
		echo '</table>';
	}
	?>
	</div>


<input type="hidden" id="comment_encrypted" value="">
<input type="hidden" id="hash_code" value="<?php echo $tpl['hash_code']?>">
<input type="hidden" id="code" value="<?php echo $tpl['code']?>">

	
</div>
<!-- /container -->

