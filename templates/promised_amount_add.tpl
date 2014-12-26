<script>
	$(document).ready(function() {
		$( "#progress_bar" ).load( "ajax/progress_bar.php");
	});
</script>
<script type="text/javascript" src="js/uploader.js"></script>
<script src="js/js.js"></script>
<link rel="stylesheet" href="css/progress.css" type="text/css" />
<script>
	var max_promised_amounts = new Array();
	<?php
			foreach($tpl['max_promised_amounts'] as $id=>$max_promised_amount)
	echo "max_promised_amounts[{$id}] = {$max_promised_amount};\n";
	?>
var currency_name = new Array();
	<?php
			foreach($tpl['currency_list_name'] as $id=>$currency_name)
	echo "currency_name[{$id}] = '{$currency_name}';\n";
	?>

var video_url_id = '';
var video_type = '';
var payment_systems_ids = '';

$('#add_promised_amount').bind('click', function () {

	if (!video_url_id) {
		if ($("#video_url").val()) {
			var re = /watch\?v=([0-9A-Za-z_-]+)/i;
			var res = re.exec($("#video_url").val());
			if (res != null && typeof res[1] != 'undefined')
				video_url_id = res[1];
			if (!video_url_id) {
				var re = /youtu\.be\/([0-9A-Za-z_-]+)/i
				var res = re.exec($("#video_url").val());
				if (res != null && typeof res[1] != 'undefined')
					video_url_id = res[1];
			}
			console.log(video_url_id);
		}
	}

	if (!$('#amount').val()) {
		$('#errors').html('<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button><?php echo $lng['invalid_amount']?></div>');
	}
	else if (!video_url_id) {
		$('#errors').html('<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button><?php echo $lng['video_not_uploaded']?></div>');
		//video_url_id='null';
		//video_type='null';
	}
	else {

		video_type = 'youtube';

		var ps_id;
		for (i = 1; i < 6; i++) {
			ps_id = $('#ps' + i).val();
			if (ps_id > 0) {
				payment_systems_ids = payment_systems_ids + ps_id + ',';
			}
		}
		if (payment_systems_ids.length > 1)
			payment_systems_ids = payment_systems_ids.substr(0, payment_systems_ids.length - 1);
		else
			payment_systems_ids = '0';

		<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#add").css("display", "none");	$("#sign").css("display", "block");' ?>

		$("#for-signature").val('<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,' + $("#currency_id").val() + ',' + $("#amount").val() + ',' + video_type + ',' + video_url_id + ',' + payment_systems_ids);
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	}

});

$('#send_to_net').bind('click', function () {

		$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
				'currency_id' :  $('#currency_id').val(),
				'amount' :  $('#amount').val(),
				'video_type' :  video_type,
				'video_url_id' :  video_url_id,
				'payment_systems_ids' :  payment_systems_ids,
				'signature1': $('#signature1').val(),
				'signature2': $('#signature2').val(),
				'signature3': $('#signature3').val()
		}, function(data) {
			//alert(data);
			fc_navigate ('<?php echo $tpl['navigate']?>', {'alert': '<?php echo $lng['promised_amount_add_wait_24h'] ?>'} );
		});
});

$('#skip').bind('click', function () {
	fc_navigate ('<?php echo $tpl['navigate']?>', {'skip_promised_amount': 1} );
});

var currency_id;
$( "#currency_id" ).change(function () {
			$( "#currency_id option:selected" ).each(function() {
				currency_id = $(this).val();
				$("#max_promised_amount").text( max_promised_amounts[currency_id] ) ;
				$("#promised_amount_currency_name").text( currency_name[currency_id] ) ;
				$("#promised_amount_currency_full_name").text( $(this).text() ) ;
			});
		})
.change();

	$('#amount').keyup(function(e) {
		var amount = $("#amount").val();
		$("#promised_amount").text( amount ) ;
		$("#promised_amount2").text( amount ) ;
	})

	$('#video_mp4').change(function () {
		send_video('video_mp4', 'video_mp4_progress', 'promised_amount-'+currency_id);
		//$("#source_mp4").attr('src', 'public/promised_amount_'+currency_id+'.mp4');
	})

	$('#video_webm_ogg').change(function () {
		send_video('video_webm_ogg', 'video_webm_ogg_progress', 'promised_amount-'+currency_id);
		//$("#source_webm").attr('src', 'public/promised_amount_'+currency_id+'.webm');
		//$("#source_ogg").attr('src', 'public/promised_amount_'+currency_id+'.ogg');
	})


	delete window['YT'];
	delete window['YTConfig'];

	var YT = {loading: 0,loaded: 0};
	var YTConfig = {'host': 'http://www.youtube.com'};
	YT.loading = 1;(function(){var l = [];YT.ready = function(f) {if (YT.loaded) {f();} else {l.push(f);}};window.onYTReady = function() {YT.loaded = 1;for (var i = 0; i < l.length; i++) {try {l[i]();} catch (e) {}}};YT.setConfig = function(c) {for (var k in c) {if (c.hasOwnProperty(k)) {YTConfig[k] = c[k];}}};var a = document.createElement('script');a.id = 'www-widgetapi-script';a.src = 'https:' + '//s.ytimg.com/yts/jsbin/www-widgetapi-vfleeBgRM/www-widgetapi.js';a.async = true;var b = document.getElementsByTagName('script')[0];b.parentNode.insertBefore(a, b);})();

	// 3. Define global variables for the widget and the player.
	// The function loads the widget after the JavaScript code has
	// downloaded and defines event handlers for callback notifications
	// related to the widget.
	var widget;
	var player;
	function onYouTubeIframeAPIReady() {
		widget = new YT.UploadWidget('widget', {
			width: 500,
			events: {
				'onUploadSuccess': onUploadSuccess,
				'onProcessingComplete': onProcessingComplete
			}
		});
	}

	// 4. This function is called when a video has been successfully uploaded.
	function onUploadSuccess(event) {
		//alert('Video ID ' + event.data.videoId + ' was uploaded and is currently being processed. Please wait.');
		player = new YT.Player('player', {
			height: 390,
			width: 640,
			videoId: event.data.videoId,
			events: {}
		});
		$("#refresh_youtube_div").css("display", "block");

		video_url_id = event.data.videoId;
	}

	// 5. This function is called when a video has been successfully processed.
	function onProcessingComplete(event) {

	}
	$( "#from_webcam_show" ).click(function() {
		$("#from_webcam").css("display", "block");
		$("#from_file").css("display", "none");
		return false;
	});

	$( "#from_file_show" ).click(function() {
		$("#from_file").css("display", "block");
		$("#from_webcam").css("display", "none");
		return false;
	});

	$( "#refresh_youtube" ).click(function() {
		var iframe = document.getElementById('player');
		iframe.src = iframe.src;
		console.log('player');
		return false;
	});

	$("#main_div select").addClass( "form-control" );
	$("#main_div input").addClass( "form-control" );
	$("#main_div button").addClass( "btn-outline btn-primary" );
	$("#main_div textarea").width( 500 );

</script>
<div id="main_div">
<h1 class="page-header"><?php echo $lng['promised_amount_add_title']?></h1>
<ol class="breadcrumb">
	<li><a href="#mining_menu"><?php echo $lng['mining'] ?></a></li>
	<li><a href="#promised_amount_list"><?php echo $lng['promised_amount_title'] ?></a></li>
	<li class="active"><?php echo $lng['promised_amount_add_title'] ?></li>
</ol>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

    <div id="add" class="form-inline">
	
		<label><?php echo $lng['currency']?></label>
		<select id="currency_id" style="width: 150px">
		<?php
		foreach ($tpl['currency_list'] as $id => $data) {
			if ($id == @$tpl['currency_id'])
				$selected = 'selected';
			else
				$selected = '';
			echo "<option value='{$id}' {$selected}>{$data['full_name']}</option>";
		}
		?>
		</select>
		<label style="margin-left: 10px"><?php echo $lng['amount']?></label>
		<input id="amount" class="input-mini" type="text" style="width: 70px;"> max: <span id="max_promised_amount"></span>
		<br>
	    <p style="margin-top: 20px"><?php echo $lng['promised_amount_payment_systems']?></p>
		 <?php
		 for ($i=1; $i<6; $i++) {
		    echo '<select id="ps'.$i.'" style="width:100px">';
	        echo '<option value="0">----</option>';
			foreach ($tpl['payment_systems'] as $id => $name)
				if ($id!=57 && $id!=44) {
					echo "<option value='{$id}'>{$name}</option>";
				}
	        echo ' </select>';
	     }
		 ?>

	    <br>

	   <p style="margin-top: 20px"><?php echo $lng['promised_amount_add_video_text']?></p>

	    <p><a href="#" id="from_webcam_show"><?php echo $lng['from_webcam']?></a> <?php echo $lng['or']?> <a href="#" id="from_file_show"><?php echo $lng['from_file']?></a></p>
	    <div id="from_webcam">
		    <div id="widget"></div>
		    <div id="player"><?php echo (@$tpl['video_url'])?"<iframe width=640 height=480  src='{$tpl['video_url']}' frameborder=0 allowfullscreen></iframe>":""?></div>
		    <div id="refresh_youtube_div" style="display: none"><a href="#" id="refresh_youtube"><i class="fa fa-refresh fa-fw" style="font-size: 30px"></i></a></div>
	    </div>

		<div id="from_file" style="display: none">
		    <table class="table table-bordered">
			    <tr><td>
					    <span class="btn btn-file"><input id="video_url" type="text" style="width:500px"></span>
					    <br>Example: http://www.youtube.com/watch?v=ZSt9tm3RoUU<br>

				    </td></tr>
			<?php
			if (!defined('COMMUNITY')) {
			?>
			    <tr><td>
					    <?php echo $lng['2_video_file']?>:<br>

					    <table><tr><td>

								    mp4:<input type="file" id="video_mp4" name="file" accept="video/mp4" />
								    <div id="video_mp4_progress" class="my_progress">0%</div><br>
								    <div id="video_mp4_ok" class="alert alert-success" style="display: none"></div>
								    <button id="del_mp4" style="display: none">Delete</button>

							    </td><td>

								    WebM or Ogg: <input type="file" id="video_webm_ogg" name="file" accept="video/webm, video/ogg"/>
								    <div id="video_webm_ogg_progress" class="my_progress" >0%</div>
								    <div id="video_webm_ogg_ok" class="alert alert-success" style="display: none"></div>
								    <button id="del_webm_ogg" style="display: none">Delete</button>

							    </td></tr></table>



					    <br>
<!--
					    <div id="video" style="display: none"><video id="example_video_1" class="video-js vjs-default-skin" controls preload="none" width="640" height="468" data-setup="{}"><source  src="" id="source_mp4" type='video/mp4' /><source  src="" id="source_webm" type='video/webm' /><source src="" id="source_ogg" type='video/ogg' /></video></div>-->

				    </td></tr>
			<?php
			}
			?>
		    </table>
	    </div>


	    <div class="alert alert-info" style="margin-top: 30px"><strong><?php echo $lng['limits'] ?></strong>  <?php echo $tpl['limits_text'] ?></div>

		<div id="errors"></div>
		<button class="btn" id="add_promised_amount"><?php echo $lng['send_to_net']?></button>  <button  class="btn" id="skip"><?php echo $lng['skip']?></button><br><br>

    </div>
    
	<?php require_once( 'signatures.tpl' );?>

</div>