<link rel="stylesheet" type="text/css" href="css/tooltipster.css" />
<link rel="stylesheet" type="text/css" href="css/tooltipster-shadow.css" />
<script type="text/javascript" src="js/jquery.tooltipster.min.js"></script>
<style>
	@media (min-width: 1024px) {
		.qtip-close{display: none}
	}

	@media (min-width: 768px) {
		.col-sm-6 {
			width: 100%;
		}
	}
	@media (min-width: 840px) {
			.col-sm-6 {
				width: 50%;
			}
	}
	@media  (min-width: 1350px) {
		.col-lg-3 {
			width: 25%;
		}
	}
</style>
<script>
	$(function() {
		$('[title!=""]').qtip( {
			position: {target: 'mouse', adjust: { x: 5, y: 5 }},
			style: { classes: 'qtip-blue qtip-bootstrap qtip-shadow' },
			content: {	button: true }
			}
		);

		$.ajax({
			url: "http://dcoinsimple.com/api.php",
			type: 'GET',
			dataType: "json",
			crossDomain: true,
			success: function (data) {
				console.log(data);
				if(typeof data.total_buy[1] != "undefined")
					$('#dwoc_ex_buy_sum').html('$'+Math.round(data.total_buy[1]*100)/100);
				if(typeof data.max_buy_rate[1] != "undefined")
					$('#dwoc_ex_buy_rate').html('1 dWOC = $'+Math.round(data.max_buy_rate[1]*100)/100);

				if(typeof data.total_buy[72] != "undefined")
					$('#dusd_ex_buy_sum').html('$'+Math.round(data.total_buy[72]*100)/100);
				if(typeof data.max_buy_rate[72] != "undefined")
					$('#dusd_ex_buy_rate').html('1 dUSD = $'+Math.round(data.max_buy_rate[72]*100)/100);

				if(typeof data.total_buy[23] != "undefined")
					$('#deur_ex_buy_sum').html('$'+Math.round(data.total_buy[23]*100)/100);
				if(typeof data.max_buy_rate[23] != "undefined")
					$('#deur_ex_buy_rate').html('1 dEUR = $'+Math.round(data.max_buy_rate[23]*100)/100);

				if(typeof data.total_buy[58] != "undefined")
					$('#drub_ex_buy_sum').html('$'+Math.round(data.total_buy[58]*100)/100);
				if(typeof data.max_buy_rate[58] != "undefined")
					$('#drub_ex_buy_rate').html('1 dRUB = $'+Math.round(data.max_buy_rate[58]*1000)/1000);
			}
		});

	});
	<?php
	if (preg_match('/user|miner/iD', $tpl['my_notice']['account_status']) && !$user_id)
		echo '$("#main-login").html(\'<a href="#myModal" data-backdrop="static" data-toggle="modal" role="button" class="btn btn-danger  btn-block "><i class="fa fa-sign-in fa-lg"></i> Login</a><div style="margin: 2px 10px; font-size: 11px">'.$lng['login_alert'].'</div>\');';
	?>
</script>
<style>
	.alert-info a:link{text-decoration: underline}
	.page-header{margin-top: 10px}
</style>
<link href="css/cf.css" rel="stylesheet">
<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

<style>
	.fd-tile.detail .content {
		background: none repeat scroll 0% 0% transparent;
		padding: 10px 10px 13px;
		display: inline-block;
		position: relative;
		z-index: 3;
	}
	.fd-tile .content p {
		margin-bottom: 0px;
		font-size: 14px;
	}
	.fd-tile .content h1 {
		margin: 0px;
		font-weight: 300;
		font-size: 40px;
	}
	.tab-content h3, h2, h1:first-child {
		margin-top: 0px;
	}
	.cl-mcont h1 {
		line-height: 1.3em;
	}
	.text-left {
		text-align: left;
	}
	h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
		font-family: "Open Sans",sans-serif;
		font-weight: 300;
	}
	h1, .h1 {
		font-size: 36px;
	}
	h1, .h1, h2, .h2, h3, .h3 {
		margin-top: 20px;
		margin-bottom: 10px;
	}
	h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
		font-family: inherit;
		font-weight: 500;
		line-height: 1.1;
		color: inherit;
	}
	p {
		font-size: 13px;
		line-height: 22px;
	}
	p {
		margin: 0px 0px 10px;
	}
	.fd-tile.clean {
		color: #888;
	}
	.cl-mcont {
		color: inherit;
		font-size: 13px;
		font-weight: 200;
		line-height: 21px;
	}

	.ex {
		font-family: "Open Sans",sans-serif;
		font-size: 12px;
		color: #555;
	}
	.fa-flag:before {
		content: "";
	}

	.fd-tile.detail .icon i {
		color: rgba(0, 0, 0, 0.05);
		font-size: 100px;
		line-height: 65px;
	}
	.fd-tile.clean .icon i {
		color: #E5E5E5;
	}
	.fa {
		display: inline-block;
		font-family: FontAwesome;
		font-style: normal;
		font-weight: normal;
		line-height: 1;
	}
	.fd-tile.detail .icon {
		display: block;
		float: right;
		height: 80px;
		margin-bottom: 10px;
		padding-left: 15px;
		padding-top: 10px;
		width: 80px;
	}
	.fd-tile.detail .icon {
		display: block;
		float: right;
		height: 80px;
		margin-bottom: 10px;
		padding-left: 15px;
		padding-top: 10px;
		position: absolute;
		right: 10px;
		top: 0px;
		width: 80px;
	}
	.fd-tile.clean.tile-purple .details {
		background-color: #5D9CEC;
	}
	.fd-tile.detail {
		position: relative;
		overflow: hidden;
	}
	.fd-tile.detail .details {
		clear: both;
		display: block;
		padding: 5px 10px;
		color: #FFF;
		text-transform: uppercase;
		background-color: rgba(0, 0, 0, 0.1);
	}
	.ex a {
		color: #23C0A2;
		text-decoration: none;
		outline: 0px none;
	}
	.ex a {
		color: #428BCA;
		text-decoration: none;
	}
	.ex a {
		background: none repeat scroll 0% 0% transparent;
	}
	.ex a:focus, .ex a:hover, .ex a:active {
		outline: 0px none;
		text-decoration: none;
		color: #0FAC8E;
	}
	.fd-tile.detail .details i {
		font-size: 18px;
		color: rgba(255, 255, 255, 0.4);
	}
	.fa.pull-right {
		margin-left: 0.3em;
	}
	.fd-tile.detail .icon i {
		color: rgba(0, 0, 0, 0.05);
		font-size: 100px;
		line-height: 65px;
	}
	.fd-tile.clean.tile-green .details {
		background-color: #37BC9B;
	}
	.fd-tile.clean.tile-prusia .details {
		background-color: #AD4B84;
	}
	.fd-tile.clean.tile-red .details {
		background-color: #EA6153;
	}
</style>



<div id="message"></div>
<script>

	console.log('intervalIdArray='+intervalIdArray);
	if (typeof intervalIdArray != "undefined") {
		for (i=0; i<intervalIdArray.length; i++)
			clearInterval(intervalIdArray[i]);
	}
	var intervalIdArray = [];

	function dc_counter(amount, pct, amount_id, characters)
	{
		console.log($('#'+amount_id).text());
		var amount_str = $('#'+amount_id).text();
		var amount = parseFloat(amount_str);
		console.log('dc_counter/'+amount_id+'/'+amount+'/'+pct);
		var i=0;
		pct = pct / 3;

		var intervalID = setInterval( function() {
			 i++;
			 //console.log(i);
			 var new_amount =  Math.pow(1+pct, i) * amount;
			 var number = Math.pow(10, Number(characters));
			//console.log('characters='+characters);
			//console.log('number='+number);
			 if (new_amount<number) {
				 if (parseFloat(new_amount)<1)
					 var s = 2;
				 else
					 var s = 1;
				 new_amount = new_amount.toString();
				 new_amount = new_amount.substr(0, characters+s);
				 //console.log('new_amount<number='+new_amount+'/'+s);
			 }
			 else {
				 new_amount = new_amount.toFixed();
				 //console.log('new_amount.toFixed='+new_amount);
			 }
			if (new_amount!="NaN" ) {
				$('#'+amount_id).text(new_amount);
				//console.log('new_amount='+new_amount);
			}
			else {
				amount = parseFloat($('#'+amount_id).text());
				//console.log('amount='+amount);
			}
		} , 300);
		intervalIdArray.push(intervalID);
	}

</script>
<script>
	$(document).ready(function() {
		$('.tooltip').tooltipster({
			delay: 50,
			contentAsHTML: true,
			interactive: true,
			theme: 'tooltipster-shadow'
		});
	});
</script>

<style>

	.phoney {
		background-color: rgba (0, 0, 0, 0.7);
		background: rgba(0, 0, 0, 0.7);

	}

	.phoneytext {
		text-shadow: 0 -1px 0 #000;
		color: #fff;
		font-family: Helvetica Neue, Helvetica, arial;
		font-size: 18px;
		line-height: 25px;
		padding: 4px 45px 4px 15px;
		font-weight: bold;
		background: url(img/us-ru.png) 95% 50% no-repeat;
	}

	.phoneytab {
		text-shadow: 0 -1px 0 #000;
		color: #fff;
		font-family: Helvetica Neue, Helvetica, arial;
		font-size: 18px;
		background: rgb(112,112,112) !important;
	}

	.img_face{
		width: 80px;
		height: 80px;
		background-size: 80px Auto;
		border-radius: 50%;
		margin:auto;
	}
	.panel-img {
		display: block;
		margin: auto;
		margin-top: -50px;
		border-radius: 50%;
		border: 7px solid #FFF;
		width: 93px;
		height: 93px;
		line-height: 80px;
		text-align: center;
		text-shadow: -6px 8px 5px rgba(0, 0, 0, 0.3);
	}
	.profile_div{
		padding-top: 15px;
		border-top-left-radius: 4px;
		border-top-right-radius: 4px;
		height:75px;
		background:none repeat scroll 0% 0% #5B90BF;
	}
	.profile_text{
		background:none repeat scroll 0% 0% #ffffff;
		text-align:center;
		font-size:13px;
	}
	.profile_text strong {
		font-size:25px;
	}
	.profile_text .dc_amount {
		font-size:20px;
	}
	.profile_text .amount_1year {
		font-size:24px;
	}

	.profile_main_div{
		background:#ffffff;
		padding-bottom:5px;
		border-bottom-left-radius: 4px;
		border-bottom-right-radius: 4px;
		width:198px;
		font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
		line-height: 1.1;
		color:#555;
	}
	.profile_main_div hr{
		width:20%; margin:10px auto; border:none; height:1px; background:#ccc;
	}

</style>

<script src="js/infobubble.js"></script>
<!--<script src="http://google-maps-utility-library-v3.googlecode.com/svn/trunk/infobubble/src/infobubble-compiled.js" type="text/javascript"></script>-->


<script type="text/javascript">
	var infoBubble;

	function get_img_home (i, user_id, urls, lnglat, html, counters, pct_sec, start) {
		console.log('html='+html);
		console.log('counters='+counters);
		console.log('pct_sec='+pct_sec);
		console.log('start='+start);
		if (typeof urls == 'undefined' || typeof urls[i] == 'undefined' )
			return 0;
		$.ajax({
			url: urls[i] + "/ajax/public_img.php?img=" + user_id + "_user_face.jpg",
			type: 'GET',
			error:
				function(){
					console.log('==========error ' + urls[i] + "/ajax/public_img.php?img=" + user_id + "_user_face.jpg");
					var bg = $('#img_' + user_id).css("background-image");
					if (typeof bg == 'undefined' || bg.length < 10)
						get_img_home(i + 1, user_id, urls, lnglat, html, counters, pct_sec, start);
				},
			success:
				function(response) {
					if (response=='ok') {
						if (typeof urls[i] != 'undefined' && urls[i] != '' && urls[i] != '0') {
							var image = new Image();
							console.log('TRY ' + urls[i] + "/public/" + user_id + "_user_face.jpg" + "\ni=" + i);
							image.src = urls[i] + "/public/" + user_id + "_user_face.jpg";
							image.onload = function () {
								console.log('OK ' + urls[i]);
								image = null;
								//$('#img_'+user_id).css("background", "url('"+urls[i]+"/public/"+user_id+"_user_face.jpg')  50% 50%");
								//$('#img_'+user_id).css("background-size", "100px Auto");
								//infowindow = new google.maps.InfoWindow();
								//infowindow.setContent('<div id="infowin_content" style="width:190px; overflow:none"><div id="img_'+user_id+'" style="width:100px; height:100px; background:  url('+urls[i]+'/public/'+user_id+'_user_face.jpg)  50% 50%; background-size: 100px Auto; border-radius:50%"></div>'+html+'</div>');

								//infowindow.setPosition(lnglat);
								//infowindow.open(map);

								console.log('===============>lnglat');
								console.log(lnglat);

								infoBubble2 = new InfoBubble({
									map: map,
									content: '<div class="profile_main_div"><div class="profile_div"></div><div class="panel-img"><div id="img_' + user_id + '" style="width:80px; height:80px; background:  url(' + urls[i] + '/public/' + user_id + '_user_face.jpg)  50% 50%; background-size: 80px Auto; border-radius:50%"></div></div><div class="profile_text">' + html + '</div></div>',
									position: new google.maps.LatLng(lnglat.lat, lnglat.lng),
									shadowStyle: 1,
									padding: 0,
									backgroundColor: 'rgb(0,0,0,0)',
									borderRadius: 4,
									arrowSize: 10,
									borderWidth: 1,
									borderColor: '#ccc',
									disableAutoPan: true,
									hideCloseButton: false,
									arrowPosition: 50,
									backgroundClassName: 'phoney',
									arrowStyle: 2
								});
								infoBubble2.open();

								if (start == 1)
									var lat = lnglat.lat + 3;
								else
									var lat = lnglat.lat;
								var center = new google.maps.LatLng(lat, lnglat.lng);
								map.panTo(center);


								//console.log(i+'/'+user_id+'/'+urls);
								$('#map_canvas').spin(false);
								for (var k = 0; k < counters.length; k++) {
									dc_counter(0, pct_sec, counters[k], 8);
								}
							};
							// handle failure
							image.onerror = function () {
								image = null;
								console.log('error ' + urls[i]);
								var bg = $('#img_' + user_id).css("background-image");
								if (typeof bg == 'undefined' || bg.length < 10)
									get_img_home(i + 1, user_id, urls, lnglat, html, counters, pct_sec, start);
							};
							setTimeout
							(
								function () {
									if (image != null && (!image.complete || !image.naturalWidth)) {
										var bg = $('#img_' + user_id).css("background-image");
										image = null;
										console.log('error');
										if (typeof bg == 'undefined' || bg.length < 10)
											get_img_home(i + 1, user_id, urls, lnglat, html, counters, pct_sec, start);
									}
								},
								2000
							);
						}
						else {
							$('#map_canvas').spin(false);
						}
					}
					else {
						console.log('response='+response+'==========error 2 ' + urls[i] + "/ajax/public_img.php?img=" + user_id + "_user_face.jpg");
						var bg = $('#img_' + user_id).css("background-image");
						if (typeof bg == 'undefined' || bg.length < 10)
							get_img_home(i + 1, user_id, urls, lnglat, html, counters, pct_sec, start);
					}
				},
				timeout: 3000
		});
	}


	var gMapsLoaded = false;
	window.gMapsCallback = function(){
		gMapsLoaded = true;
		$(window).trigger('gMapsLoaded');
	}
	window.loadGoogleMaps = function(){
		if(gMapsLoaded) return window.gMapsCallback();
		var script_tag = document.createElement('script');
		script_tag.setAttribute("type","text/javascript");
		script_tag.setAttribute("src","http://maps.google.com/maps/api/js?sensor=false&callback=gMapsCallback");
		(document.getElementsByTagName("head")[0] || document.documentElement).appendChild(script_tag);
	}

	$(document).ready(function(){
		function initialize(){

			console.log('-----------------------------------------------------------------------');

			$.get( 'ajax/get_miners_data_map.php', function (map_markers) {

				var markers = [];
				var mapOptions = {
					zoom: 5,
					scrollwheel: false,
					center: new google.maps.LatLng(56.55258, 43.83508),
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					styles: [	{		featureType:'water',		stylers:[{color:'#46bcec'},{visibility:'on'}]	},{		featureType:'landscape',		stylers:[{color:'#f2f2f2'}]	},{		featureType:'road',		stylers:[{saturation:-100},{lightness:45}]	},{		featureType:'road.highway',		stylers:[{visibility:'simplified'}]	},{		featureType:'road.arterial',		elementType:'labels.icon',		stylers:[{visibility:'off'}]	},{		featureType:'administrative',		elementType:'labels.text.fill',		stylers:[{color:'#444444'}]	},{		featureType:'transit',		stylers:[{visibility:'off'}]	},{		featureType:'poi',		stylers:[{visibility:'off'}]	}]
				};
				map = new google.maps.Map(document.getElementById('map_canvas'),mapOptions);




				infowindow = new google.maps.InfoWindow();

				markers.markerClickFunction = function(user_id) {
					return function(e) {
						e.cancelBubble = true;
						e.returnValue = false;
						if (e.stopPropagation) {
							e.stopPropagation();
							e.preventDefault();
						}

						$('#map_canvas').spin();
						$.get( 'ajax/get_miner_data.php?user_id='+user_id, function (data) {
							get_img_home (0, user_id, data.hosts, data.lnglat, data.html,  data.counters, data.pct_sec, 0);
						}, "json" );

					};
				};


				for (var i = 0; i < map_markers.info.length; i++) {

					var datainfo = map_markers.info[i];
					var latLng = new google.maps.LatLng(datainfo.latitude,
						datainfo.longitude);
					var marker = new google.maps.Marker({
						position: latLng,
						draggable: true
					});

					var fn = markers.markerClickFunction(datainfo.user_id);
					google.maps.event.addListener(marker, 'click', fn);

					markers[i] = marker;

				}

				var markerCluster = new MarkerClusterer(map, markers, { maxZoom: 18 });

				<?php
				for ($i=0; $i<sizeof($tpl['rand_miners']); $i++) {
					echo "$.get( 'ajax/get_miner_data.php?user_id={$tpl['rand_miners'][$i]}', function (data) {\n
									get_img_home (0, {$tpl['rand_miners'][$i]}, data.hosts, data.lnglat, data.html, data.counters, data.pct_sec, 1);\n
								}, \"json\" );\n";
				}
				?>


			}, "json" );




		}

		$(window).unbind( 'gMapsLoaded' );
		$(window).bind('gMapsLoaded', initialize);
		window.loadGoogleMaps();
	});
</script>


<div id="generate" style="margin-top: 15px">

<style>
	.mini-box {
		min-height: 105px;
		padding: 20px;
	}
	.panel {
		margin-bottom: 20px;
		background-color: #FFF;
		border: 1px solid transparent;
		border-radius: 2px;
		box-shadow: 0px 1px 1px rgba(0, 0, 0, 0.05);
	}
	.mini-box .box-icon {
		display: block;
		float: left;
		margin: 0px 10px 10px 0px;
		width: 65px;
		height: 65px;
		border-radius: 50%;
		line-height: 65px;
		vertical-align: middle;
		text-align: center;
		font-size: 35px;
	}
	.bg-success {
		background-color: #5D9CEC;
		color: #FFF;
	}
	.bg-info {
		background-color: #37BC9B;
		color: #FFF;
	}
	.bg-danger {
		background-color: #EA6153;
		color: #FFF;
	}
	.bg-warning {
		background-color: #AD4B84;
		color: #FFF;
	}
	.panel0 {
		font-family: "Lato",Helvetica,Arial,sans-serif;
		font-size: 14px;
		line-height: 1.42857;
		color: #767676;
	}
	.mini-box .box-info p {
		margin: 0px;
	}
	.size-h2 {
		font-size: 30px;
		font-family: sans-serif;
		line-height: 33px;
	}
	.size-h4 {
		 font-size: 18px;
	 }
	.text-muted {
		color: #777;
		font-family: "Open Sans",sans-serif;
		font-size: 14px;
		line-height: 14px;
	}

</style>
<div class="panel0">
	<div class="col-lg-3 col-sm-6">
		<div class="panel mini-box" title="<?php echo $lng['home_account_text']?>">
	                <span class="box-icon bg-success">
	                    <i class="fa fa-credit-card"></i>
	                </span>
			<div class="box-info">
				<p class="size-h2"><?php echo $user_id ?></p>
				<p class="text-muted"><span data-i18n="Growth"><?php echo $lng['account_number']?></span></p>
			</div>
		</div>
	</div>
	<?php
	if($tpl['my_notice']['account_status']=='Miner'){
	?>
	<div class="col-lg-3 col-sm-6">
		<div class="panel mini-box">
	                <span class="box-icon bg-danger">
	                    <i class="fa fa-bell"></i>
	                </span>
			<div class="box-info">
				<p class="size-h2"><a href="#cash_requests_in"><?php echo $tpl['cash_requests']?></a></p>
				<p class="text-muted"><span data-i18n="New users"><?php echo $lng['inbox']?></span></p>
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-sm-6">
		<div class="panel mini-box">
	                <span class="box-icon bg-usd">
	                    <i class="fa fa-check"></i>
	                </span>
			<div class="box-info">
				<p class="size-h2"><a href="#points"><?php echo $tpl['points']?></a></p>
				<p class="text-muted"><span data-i18n="New users"><?php echo $lng['points']?></span></p>
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-sm-6">
		<div class="panel mini-box">
	                <span class="box-icon bg-euro">
	                    <i class="fa fa-camera"></i>
	                </span>
			<div class="box-info">
				<p class="size-h2"><a href="#tasks"><?php echo $tpl['tasks_count']?></a></p>
				<p class="text-muted"><span data-i18n="New users"><?php echo $lng['tasks']?></span></p>
			</div>
		</div>
	</div>
	<?php
	}
	?>
</div>
<div style="clear: both"></div>


<style>
	.panel {
		margin-bottom: 21px;
		background-color: #FFF;
		border: 1px solid transparent;
		border-radius: 4px;
		box-shadow: 0px 1px 1px rgba(0, 0, 0, 0.05);
	}
	.widget.panel, .widget .panel {
		overflow: hidden;
	}
	.widget {
		margin-bottom: 20px;
		border: 0px none;
	}
	.row-table > [class*="col-"] {
		display: table-cell;
		float: none;
		table-layout: fixed;
		vertical-align: middle;
	}

	.pv-lg {
		padding-top: 15px !important;
		padding-bottom: 15px !important;
	}
	.bg-primary-dark {
		color: #FFF !important;
	}
	.text-center {
		text-align: center;
	}
	.pv-lg {
		padding-top: 15px !important;
		padding-bottom: 15px !important;
	}
	.mt0 {
		margin-top: 0px !important;
	}
	.dc_promised {
		font-size: 28px;
		font-weight: 600;
	}
	.text-uppercase {
		text-transform: uppercase;
	}
	.bg-primary {
		color: #FFF !important;
	}

	.bg-primary-dark {
		background-color: #2F80E7;
		color: #FFF !important;
	}
	.bg-primary {
		background-color: #5D9CEC;
		color: #FFF !important;
	}
	.bg-red {
		background-color: #F27B56;
		color: #FFF !important;
	}
	.bg-red-dark {
		background-color: #D66543;
		color: #FFF !important;
	}

	.pct small{color: #ffffff}

	.bg-usd {
		background-color: #37BC9B;
		color: #FFF !important;
	}
	.bg-usd-dark {
		background-color: #2B957A;
		color: #FFF !important;
	}

	.bg-woc {
		background-color: #5D9CEC;
		color: #FFF !important;
	}
	.bg-woc-dark {
		background-color: #2F80E7;
		color: #FFF !important;
	}

	.bg-euro {
		background-color: #AD4B84;
		color: #FFF !important;
	}
	.bg-euro-dark {
		background-color: #89456C;
		color: #FFF !important;
	}

	.bg-rub {
		background-color: #EA6153;
		color: #FFF !important;
	}
	.bg-rub-dark {
		background-color: #BC5247;
		color: #FFF !important;
	}
	.col-xs-8{width:66.6667%; float:none}
	.col-xs-4{width:33.3333%; float:none}

</style>

<h3 class="page-header" style="margin-top: 20px"><?php echo $lng['mining']?></h3>
<div class="pct">
	<div class="col-lg-3 col-sm-6">
		<div class="panel widget bg-woc">
			<div class="row-table">
				<div class="col-xs-4 text-center bg-woc-dark pv-lg">
					<em class="fa fa-globe fa-3x" style="width: 40px; margin: 0px 10px 0px 10px"></em>
				</div>
				<div class="col-xs-8 pv-lg">
					<div class="dc_promised mt0" id="promised_currency_1"><?php echo echo_zero($tpl['promised_amount_list_gen'][1]['tdc'], '0.00')?></div>
					<div class="h4 mt0"><?php echo echo_zero($tpl['promised_amount_list_gen'][1]['amount'], 0)?>&nbsp;WOC</div>
					<?php
					if ($tpl['promised_amount_list_gen'][1]['pct_sec']>0)
						echo "<script>dc_counter({$tpl['promised_amount_list_gen'][1]['tdc']}, {$tpl['promised_amount_list_gen'][1]['pct_sec']}, 'promised_currency_1', 8);\n</script>";
					?>
				</div>
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-sm-6">
		<div class="panel widget bg-usd">
			<div class="row-table">
				<div class="col-xs-4 text-center bg-usd-dark pv-lg">
					<em class="fa fa-usd fa-3x" style="width: 40px; margin: 0px 10px 0px 10px"></em>
				</div>
				<div class="col-xs-8 pv-lg">
					<div class="dc_promised mt0" id="promised_currency_72"><?php echo echo_zero($tpl['promised_amount_list_gen'][72]['tdc'], '0.00')?></div>
					<div class="h4 mt0"><?php echo echo_zero($tpl['promised_amount_list_gen'][72]['amount'], 0)?>&nbsp;USD</div>
					<?php
					if ($tpl['promised_amount_list_gen'][72]['pct_sec']>0)
						echo "<script>dc_counter({$tpl['promised_amount_list_gen'][72]['tdc']}, {$tpl['promised_amount_list_gen'][72]['pct_sec']}, 'promised_currency_72', 8);\n</script>";
					?>
				</div>
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-sm-6">
		<div class="panel widget bg-euro">
			<div class="row-table">
				<div class="col-xs-4 text-center bg-euro-dark pv-lg">
					<em class="fa fa-euro fa-3x" style="width: 40px; margin: 0px 10px 0px 10px"></em>
				</div>
				<div class="col-xs-8 pv-lg">
					<div class="dc_promised mt0" id="promised_currency_23"><?php echo echo_zero($tpl['promised_amount_list_gen'][23]['tdc'], '0.00')?></div>
					<div class="h4 mt0"><?php echo echo_zero($tpl['promised_amount_list_gen'][23]['amount'], 0)?>&nbsp;EUR</div>
					<?php
					if ($tpl['promised_amount_list_gen'][23]['pct_sec']>0)
						echo "<script>dc_counter({$tpl['promised_amount_list_gen'][23]['tdc']}, {$tpl['promised_amount_list_gen'][23]['pct_sec']}, 'promised_currency_23', 8);\n</script>";
					?>
				</div>
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-sm-6">
		<div class="panel widget bg-rub">
			<div class="row-table">
				<div class="col-xs-4 text-center bg-rub-dark pv-lg">
					<em class="fa fa-ruble fa-3x" style="width: 40px; margin: 0px 10px 0px 10px"></em>
				</div>
				<div class="col-xs-8 pv-lg">
					<div class="dc_promised mt0" id="promised_currency_58"><?php echo echo_zero($tpl['promised_amount_list_gen'][58]['tdc'], '0.00')?></div>
					<div class="h4 mt0"><?php echo echo_zero($tpl['promised_amount_list_gen'][58]['amount'], 0)?>&nbsp;RUB</div>
					<?php
					if ($tpl['promised_amount_list_gen'][58]['pct_sec']>0)
						echo "<script>dc_counter({$tpl['promised_amount_list_gen'][58]['tdc']}, {$tpl['promised_amount_list_gen'][58]['pct_sec']}, 'promised_currency_58', 8);\n</script>";
					?>
				</div>
			</div>
		</div>
	</div>
</div>


<div style="clear: both"></div>

<style>
	.m-bottom-md {
		margin-bottom: 20px;
	}
	.statistic-box {
		padding: 20px;
		text-align: center;
		position: relative;
		overflow: hidden;
		border-radius: 6px;
	}
	.statistic-box .statistic-title {
		font-size: 20px;
		z-index: 2;
	}
	.statistic-box .statistic-value {
		font-size: 36px;
		font-weight: 600;
		z-index: 2;
	}
	.m-top-md {
		margin-top: 20px;
	}
	.statistic-box .statistic-icon-background {
		position: absolute;
		font-size: 160px;
		right: -5px;
		bottom: -50px;
		opacity: 0.2;
	}
	.statistic-box .statistic-icon-background {
		font-size: 160px;
	}
</style>


<h3 class="page-header" title="<?php echo $lng['home_account_text']?>"><?php echo $lng['my_accounts']?></h3>
<div>


	<div class="col-lg-3 col-sm-6">
		<div class="statistic-box bg-success m-bottom-md" title="<?php echo $lng['home_dwoc_text']?>">
			<div class="statistic-title">
				dWOC
			</div>

			<div class="statistic-value" id="currency_1">
				<?php echo $tpl['wallets'][1]['amount']?$tpl['wallets'][1]['amount']:'0.00' ?>
			</div>
			<?php
			if ($tpl['wallets'][1]['pct_sec']>0)
				echo "<script>dc_counter({$tpl['wallets'][1]['amount']}, {$tpl['wallets'][1]['pct_sec']}, 'currency_1', 8);\n</script>";
			?>
			<div class="statistic-icon-background">
				<i class="ion-stats-bars"></i>
			</div>
		</div>
	</div>

	<div class="col-lg-3 col-sm-6">
		<div class="statistic-box bg-info m-bottom-md" title="<?php echo str_replace('[currency]', 'USD', $lng['home_dc_text'])?>">
			<div class="statistic-title">
				dUSD
			</div>

			<div class="statistic-value" id="currency_72">
				<?php echo $tpl['wallets'][72]['amount']?$tpl['wallets'][72]['amount']:'0.00' ?>
			</div>
			<?php
			if ($tpl['wallets'][72]['pct_sec']>0)
				echo "<script>dc_counter({$tpl['wallets'][72]['amount']}, {$tpl['wallets'][72]['pct_sec']}, 'currency_72', 8);\n</script>";
			?>

			<div class="statistic-icon-background">
				<i class="ion-ios7-cart-outline"></i>
			</div>
		</div>
	</div>



	<div class="col-lg-3 col-sm-6">
		<div class="statistic-box bg-euro m-bottom-md" title="<?php echo str_replace('[currency]', 'EUR', $lng['home_dc_text'])?>">
			<div class="statistic-title">
				dEUR
			</div>

			<div class="statistic-value" id="currency_23">
				<?php echo $tpl['wallets'][23]['amount']?$tpl['wallets'][23]['amount']:'0.00' ?>
			</div>
			<?php
			if ($tpl['wallets'][23]['pct_sec']>0)
				echo "<script>dc_counter({$tpl['wallets'][23]['amount']}, {$tpl['wallets'][23]['pct_sec']}, 'currency_23', 8);\n</script>";
			?>

			<div class="statistic-icon-background">
				<i class="ion-person-add"></i>
			</div>
		</div>
	</div>


	<div class="col-lg-3 col-sm-6">
		<div class="statistic-box bg-danger m-bottom-md" title="<?php echo str_replace('[currency]', 'RUB', $lng['home_dc_text'])?>">
			<div class="statistic-title">
				dRUB
			</div>

			<div class="statistic-value" id="currency_58">
				<?php echo $tpl['wallets'][58]['amount']?$tpl['wallets'][58]['amount']:'0.00' ?>
			</div>
			<?php
			if ($tpl['wallets'][58]['pct_sec']>0)
				echo "<script>dc_counter({$tpl['wallets'][58]['amount']}, {$tpl['wallets'][58]['pct_sec']}, 'currency_58', 8);\n</script>";
			?>


			<div class="statistic-icon-background">
				<i class="ion-eye"></i>
			</div>
		</div>
	</div>

</div>

<div style="clear: both"></div>
<h3 class="page-header" title="<?php echo $lng['home_ex_text']?>"><?php echo $lng['applications_from_exchange']?></h3>
<div class="ex">
	<div class="col-lg-3 col-sm-6">
		<div class="fd-tile detail clean tile-purple">
			<div class="content"><p><?php echo $lng['total']?></p><h1 class="text-left" id="dwoc_ex_buy_sum">0</h1><p><?php echo $lng['best_rate']?>:</p><p id="dwoc_ex_buy_rate" style="font-weight: bold">0</p></div>
			<div class="icon"><i class="fa fa-globe"></i></div>
			<a class="details" href="http://dcoinsimple.com" target="_blank"><?php echo $lng['details']?> <span><i class="fa fa-arrow-circle-right pull-right"></i></span></a>
		</div>
	</div>


	<div class="col-lg-3 col-sm-6">
		<div class="fd-tile detail clean tile-green">
			<div class="content"><p><?php echo $lng['total']?></p><h1 class="text-left" id="dusd_ex_buy_sum">0</h1><p><?php echo $lng['best_rate']?>:</p><p id="dusd_ex_buy_rate" style="font-weight: bold">0</p></div>
			<div class="icon"><i class="fa fa-usd"></i></div>
			<a class="details" href="http://dcoinsimple.com" target="_blank"><?php echo $lng['details']?> <span><i class="fa fa-arrow-circle-right pull-right"></i></span></a>
		</div>
	</div>



	<div class="col-lg-3 col-sm-6">
		<div class="fd-tile detail clean tile-prusia">
			<div class="content"><p><?php echo $lng['total']?></p><h1 class="text-left" id="deur_ex_buy_sum">0</h1><p><?php echo $lng['best_rate']?>:</p><p id="deur_ex_buy_rate" style="font-weight: bold">0</p></div>
			<div class="icon"><i class="fa fa-euro"></i></div>
			<a class="details" href="http://dcoinsimple.com" target="_blank"><?php echo $lng['details']?> <span><i class="fa fa-arrow-circle-right pull-right"></i></span></a>
		</div>
	</div>

	<div class="col-lg-3 col-sm-6">
		<div class="fd-tile detail clean tile-red">
			<div class="content"><p><?php echo $lng['total']?></p><h1 class="text-left" id="drub_ex_buy_sum">0</h1><p><?php echo $lng['best_rate']?>:</p><p id="drub_ex_buy_rate" style="font-weight: bold">0</p></div>
			<div class="icon"><i class="fa fa-ruble"></i></div>
			<a class="details" href="http://dcoinsimple.com" target="_blank"><?php echo $lng['details']?> <span><i class="fa fa-arrow-circle-right pull-right"></i></span></a>
		</div>
	</div>
</div>

<?php
if ($tpl['show_map']) {
	?>
	<div style="clear: both"></div>
	<h3 class="page-header" style="margin-top: 30px;margin-bottom: 0px;"
	    title="<?php echo $lng['home_miners_text'] ?>"><?php echo $lng['miners_on_the_map'] ?></h3>
	<?php echo empty($_SESSION['restricted'])?'<div style="float: right"><a href="#interface/show_map=0">Убрать карту</a></div>':''?>
	<div class="clearfix"></div>
	<div id="map_canvas" style="width: 100%; height:500px;"></div>
<?php
}
?>

	<style>
		.ibox {
			clear: both;
			margin-bottom: 25px;
			margin-top: 0px;
			padding: 0px 0px 10px 0px;
		}

		.ibox-title {

			color: inherit;
			margin-bottom: 0px;
			padding: 14px 15px 7px;
			height: 48px;
		}

		.ibox-title .label {
			float: left;
			margin-left: 4px;
		}

		.label-success, .badge-success {
			background-color: #1C84C6;
			color: #FFF;
		}

		.label {
			background-color: #D1DADE;
			color: #5E5E5E;
			font-family: "Open Sans";
			font-size: 10px;
			font-weight: 600;
			padding: 3px 8px;
			text-shadow: none;
		}

		.pull-right {
			float: right;
		}

		.pull-right {
			float: right !important;
		}

		.label-success {
			background-color: #5CB85C;
		}

		.label {
			display: inline;
			padding: 0.2em 0.6em 0.3em;
			font-size: 75%;
			font-weight: 700;
			line-height: 1;
			color: #FFF;
			text-align: center;
			white-space: nowrap;
			vertical-align: baseline;
			border-radius: 0.25em;
		}

		.ibox-title h5 {
			display: inline-block;
			font-size: 14px;
			margin: 0px 0px 7px;
			padding: 0px;
			text-overflow: ellipsis;
			float: left;
		}

		.ibox-content {
			clear: both;
		}

		.ibox-content {
			color: inherit;
			padding: 15px 20px 20px;
			border-color: #E7EAEC;
			border-image: none;
			border-style: solid solid none;
			border-width: 1px 0px;
		}

		.no-margins {
			margin: 0px !important;
		}

		.no-margins h1 {
			font-size: 30px;
		}

		.text-success {
			color: #1C84C6;
		}

		.font-bold {
			font-weight: 600;
		}

		.stat-percent {
			float: right;
		}

		.ibox-title h5 {
			font-size: 20px
		}
	</style>
	<div style="clear: both"></div>

<h3 class="page-header" style="margin-top: 30px" title="<?php echo $lng['home_promised_text']?>"><?php echo $lng['promised_amounts_of_cash']?></h3>
<div>
	<div class="col-lg-3 col-sm-6">
		<div class="ibox float-e-margins bg-woc">
			<div class="ibox-title">
				<h5>WOC</h5>
			</div>
			<div class="ibox-content">
				<h1 class="no-margins">0</h1>
				<!--<div class="stat-percent font-bold" title="<?php echo str_replace('[currency]', 'dWOC', $lng['home_pct_y_promised_text'])?>"><?php echo $tpl['currency_pct'][1]['miner_block']?><?php echo $lng['pct_block']?> </div>-->
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-sm-6">
		<div class="ibox float-e-margins bg-usd">
			<div class="ibox-title">
				<h5 title="<?php echo str_replace('[currency]', 'dUSD', $lng['home_promised_text_personal'])?>">USD</h5>
			</div>
			<div class="ibox-content">
				<h1 class="no-margins" title="<?php echo str_replace('[currency]', 'dUSD', $lng['home_promised_text_personal'])?>"><?php echo echo_zero($sum_promised_amount[72])?></h1>
				<!--<div class="stat-percent font-bold" title="<?php echo str_replace('[currency]', 'dUSD', $lng['home_pct_y_promised_text'])?>"><?php echo $tpl['currency_pct'][72]['miner_block']?><?php echo $lng['pct_block']?> </div>-->
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-sm-6">
		<div class="ibox float-e-margins bg-euro">
			<div class="ibox-title">
				<h5 title="<?php echo str_replace('[currency]', 'dEUR', $lng['home_promised_text_personal'])?>">EUR</h5>
			</div>
			<div class="ibox-content">
				<h1 class="no-margins" title="<?php echo str_replace('[currency]', 'dEUR', $lng['home_promised_text_personal'])?>"><?php echo echo_zero($sum_promised_amount[23])?></h1>
				<!--<div class="stat-percent font-bold" title="<?php echo str_replace('[currency]', 'dEUR', $lng['home_pct_y_promised_text'])?>"><?php echo $tpl['currency_pct'][23]['miner_block']?><?php echo $lng['pct_block']?> </div>-->
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-sm-6">
		<div class="ibox float-e-margins bg-rub">
			<div class="ibox-title">
				<h5   title="<?php echo str_replace('[currency]', 'dRUB', $lng['home_promised_text_personal'])?>">RUB</h5>
			</div>
			<div class="ibox-content">
				<h1 class="no-margins"  title="<?php echo str_replace('[currency]', 'dRUB', $lng['home_promised_text_personal'])?>"><?php echo echo_zero($sum_promised_amount[58])?></h1>
				<!--<div class="stat-percent font-bold" title="<?php echo str_replace('[currency]', 'dRUB', $lng['home_pct_y_promised_text'])?>"><?php echo $tpl['currency_pct'][58]['miner_block']?><?php echo $lng['pct_block']?> </div>-->
			</div>
		</div>
	</div>
</div>

<div style="clear: both"></div>
<h3 class="page-header" style="margin-top: 0px" title="<?php echo $lng['home_total_coins']?>"><?php echo $lng['total_amount_of_coins']?></h3>
<div>
	<div class="col-lg-3 col-sm-6">
		<div class="ibox float-e-margins bg-woc">
			<div class="ibox-title">
				<h5 title="<?php echo str_replace('[currency]', 'dWOC', $lng['home_total_coins_personal'])?>">dWOC</h5>
			</div>
			<div class="ibox-content">
				<h1 id="total_currency_1" class="no-margins" title="<?php echo str_replace('[currency]', 'dWOC', $lng['home_total_coins_personal'])?>"><?php echo echo_zero($sum_wallets[1])?></h1>
				<!--<div class="stat-percent font-bold" title="<?php echo str_replace('[currency]', 'dWOC', $lng['home_coins_pct_y'])?>"><?php echo $tpl['currency_pct'][1]['user_block']?><?php echo $lng['pct_block']?> </div>-->
				<?php
				if ($tpl['currency_pct'][1]['user_sec']>0)
					echo "<script>dc_counter({$sum_wallets[1]}, {$tpl['currency_pct'][1]['user_sec']}, 'total_currency_1', 8);\n</script>";
				?>
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-sm-6">
		<div class="ibox float-e-margins bg-usd">
			<div class="ibox-title">
				<h5 title="<?php echo str_replace('[currency]', 'dUSD', $lng['home_total_coins_personal'])?>">dUSD</h5>
			</div>
			<div class="ibox-content">
				<h1 id="total_currency_72"  class="no-margins" title="<?php echo str_replace('[currency]', 'dUSD', $lng['home_total_coins_personal'])?>"><?php echo echo_zero($sum_wallets[72])?></h1>
				<!--<div class="stat-percent font-bold" title="<?php echo str_replace('[currency]', 'dUSD', $lng['home_coins_pct_y'])?>"><?php echo $tpl['currency_pct'][72]['user_block']?><?php echo $lng['pct_block']?> </div>-->
				<?php
				if ($tpl['currency_pct'][72]['user_sec']>0)
					echo "<script>dc_counter({$sum_wallets[72]}, {$tpl['currency_pct'][72]['user_sec']}, 'total_currency_72', 8);\n</script>";
				?>
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-sm-6">
		<div class="ibox float-e-margins bg-euro">
			<div class="ibox-title">
				<h5 title="<?php echo str_replace('[currency]', 'dEUR', $lng['home_total_coins_personal'])?>">dEUR</h5>
			</div>
			<div class="ibox-content">
				<h1 id="total_currency_23" class="no-margins" title="<?php echo str_replace('[currency]', 'dEUR', $lng['home_total_coins_personal'])?>"><?php echo echo_zero($sum_wallets[23])?></h1>
				<!--<div class="stat-percent font-bold" title="<?php echo str_replace('[currency]', 'dEUR', $lng['home_coins_pct_y'])?>"><?php echo $tpl['currency_pct'][23]['user_block']?><?php echo $lng['pct_block']?> </div>-->
				<?php
				if ($tpl['currency_pct'][23]['user_sec']>0)
					echo "<script>dc_counter({$sum_wallets[23]}, {$tpl['currency_pct'][23]['user_sec']}, 'total_currency_23', 8);\n</script>";
				?>
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-sm-6">
		<div class="ibox float-e-margins bg-rub">
			<div class="ibox-title">
				<h5 title="<?php echo str_replace('[currency]', 'dRUB', $lng['home_total_coins_personal'])?>">dRUB</h5>
			</div>
			<div class="ibox-content">
				<h1 id="total_currency_58" class="no-margins" title="<?php echo str_replace('[currency]', 'dRUB', $lng['home_total_coins_personal'])?>"><?php echo echo_zero($sum_wallets[58])?></h1>
				<!--<div class="stat-percent font-bold" title="<?php echo $lng['home_coins_pct_y']?>"><?php echo $tpl['currency_pct'][58]['user_block']?><?php echo $lng['pct_block']?> </div>-->
				<?php
				if ($tpl['currency_pct'][58]['user_sec']>0)
					echo "<script>dc_counter({$sum_wallets[58]}, {$tpl['currency_pct'][58]['user_sec']}, 'total_currency_58', 8);\n</script>";
				?>
			</div>
		</div>
	</div>
</div>

<script>
	var currency_list = [];
	<?php
	foreach ($tpl['currency_pct'] as $id => $data) {
		if ($data['miner']) {
			echo "currency_list[{$id}] = []\n";
			echo "currency_list[{$id}]['miner'] = {$data['miner_sec']}\n";
			echo "currency_list[{$id}]['user'] = {$data['user_sec']}\n";
			echo "currency_list[{$id}]['name'] = '{$data['name']}'\n";
		}
	}
	?>

	$('#calc_amount, #calc_currency_id, #calc_status, #calc_period').bind("keyup change", function(e) {
		var amount = $('#calc_amount').val();
		var pct_sec = currency_list[$('#calc_currency_id').val()][$('#calc_status').val()];
		var sec =  $('#calc_period').val();
		var new_amount =  Math.pow(1+pct_sec, sec) * amount - amount;

		var number = Math.pow(10, 2);
		if (new_amount<number) {
			if (parseFloat(new_amount)<1)
				var s = 2;
			else
				var s = 1;
			new_amount = new_amount.toString();
			new_amount = new_amount.substr(0, 3+s);
		}
		else {
			new_amount = new_amount.toFixed();
		}
		new_amount = new_amount.replace(/(\d)(?=(\d\d\d)+([^\d]|$))/g, '$1 ');
		$('#calc_total').text('+'+new_amount+' d'+currency_list[$('#calc_currency_id').val()]['name']);
	});
</script>
<div style="clear: both;"></div>
<h3 class="page-header" style="margin-top: 0px" title="<?php echo $lng['home_total_coins']?>"><?php echo $lng['forecast_number_coins']?></h3>
<div class="form-inline">
	<?php echo $lng['my_balance']?>: <input id="calc_amount" type="text" class="form-control" value="100"  style="width: 80px; display: inline-block">
	<select id="calc_currency_id" style="width: 100px; display: inline-block" class="form-control" >
		<?php
		foreach ($tpl['currency_pct'] as $id => $data) {
			if ($id==72)
				$selected = 'selected';
			else
				$selected = '';
			if ($data['miner'])
				echo "<option value='{$id}' {$selected}>d{$data['name']}</option>";
		}
		?>
	</select><br><br>
	<?php echo $lng['my_status']?>:  <select id="calc_status" style="width: 100px; display: inline-block" class="form-control" >
		<option value='miner' selected><?php echo $lng['status_miner']?></option>
		<option value='user'><?php echo $lng['status_user']?></option>
	</select>
	<br><br>
	<?php echo $lng['home_after']?> <select id="calc_period" style="width: 100px; display: inline-block" class="form-control" >
		<option value='86400'><?php echo $lng['day']?></option>
		<option value='604800'><?php echo $lng['week']?></option>
		<option value='2592000' selected><?php echo $lng['month']?></option>
		<option value='15768000'><?php echo $lng['half_year']?></option>
		<option value='31536000'><?php echo $lng['year']?></option>
		<option value='63072000'><?php echo $lng['2_years']?></option>
		<option value='94608000'><?php echo $lng['3_years']?></option>
		<option value='157680000'><?php echo $lng['5_years']?></option>
	</select> <?php echo $lng['i_will']?> <span title="<?php echo $lng['unless_reduction']?>" style="border-bottom: 1px dotted black;"><?php echo $lng['probably']?></span> <?php echo $lng['be']?> <span id="calc_total">+<?php echo round(100*pow(1+$tpl['currency_pct'][72]['miner_sec'], 3600*24*30)-100) ?> dUSD</span>
</div>

<div style="clear: both; margin-bottom: 55px"></div>

<?php if (@$_SESSION['ADMIN']==1) {?>
			<br><br><br><br><button type="button" class="btn" data-toggle="button"  onclick="$.post('admin/content.php', { tpl_name: 'index', parameters: '' },
	              function(data) {
	              $('#dc_content').html( data );
	              }, 'html');" style="margin-left:30px">admin</button>
		<?php } ?>
	</div>

	<?php require_once( 'signatures.tpl' );?>

<script>
	$('#wrapper').spin(false);
	$(document).ready(function() {
		$( "#progress_bar" ).load( "ajax/progress_bar.php");
	});
</script>
