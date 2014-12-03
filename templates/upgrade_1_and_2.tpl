<script>
	var jcrop_api = '';
	window['crop_img_text'] = "<?php echo htmlspecialchars(strip_tags($lng['crop_img']))?>";
	$(document).ready(function() {
		$( "#progress_bar" ).load( "ajax/progress_bar.php");
	});
</script>
<style>
	#from_file_form{display: none}
	#from_webcam{display: block}
	@media screen and (max-width: 1024px) {
		#from_file_form{display: block}
		#from_webcam{display: none}
	}
</style>
<script src="js/cropper.js"></script>
<link href="css/cropper.css" rel="stylesheet">

<link rel="stylesheet" href="css/AS3Cam.css" type="text/css" />

<h1 class="page-header"><?php echo $lng['upgrade_title']?></h1>
<ol class="breadcrumb">
	<li><a href="#mining_menu"><?php echo $lng['mining'] ?></a></li>
	<li class="active"><?php echo $lng['upgrade_title'] ?></li>
</ol>

    <ul class="nav nav-tabs">
	    <?php echo make_upgrade_menu($tpl['step'])?>
    </ul>
    
	<h3><?php echo $lng['upload_2_photo']?></h3>
	<div style="display: none">Please upgrade your browser to latest version</div>
	<?php echo $lng['upload_2_photo_rules']?>
	<p><a href="#" id="from_webcam_show"><?php echo $lng['from_webcam']?></a> <?php echo $lng['or']?> <a href="#" id="from_file_show"><?php echo $lng['from_file']?></a></p>
	<div id="from_webcam">

		<table border="0" cellspacing="0" cellpadding="0">
			<tr>
				<td class="webcam-container size640x480">
					<div id="webcam" class="size640x480"></div>
				</td>
			</tr>
			<tr>
				<td class="webcam-text">
					<!--<div>
						<select id="popup-webcam-cams"></select>
					</div>-->
					<div>
						<input
						    class="btn btn-primary btn-lg btn-block"
							id="popup-webcam-take-photo"
							type="button"
							disabled="disabled"
							value="<?php echo $lng['take_a_photo']?>"
							style="display:none" />
					</div>
					<!--<p class="webcam-error"></p>-->
				</td>
			</tr>
		</table>
		<br/>
		<!--<div id="result0"><table><tr><td><img src="img/<?php echo $tpl['photo_type']?>.jpg"></td><td><div id="result" style="width:640px;"><?php echo $tpl['user_'.$tpl['photo_type']]?'<img src="'.$tpl['user_'.$tpl['photo_type']].'?r='.rand(0, getrandmax()).'" width="350">':''?></div></td></tr></table></div>
		<div id="<?php echo $tpl['photo_type']?>_photo_div" style="width: 350px; height: 500px"><canvas id="<?php echo $tpl['photo_type']?>_photo"></canvas></div>

		<input type="hidden" id="img_b64">-->

	</div>
	<div id="from_file">
		<fieldset id="from_file_form">
			<input accept="image/*"  capture="camera" type="file" name="upload" id="change_pkey_upload_hidden" style="position: absolute; display: block; overflow: hidden; width: 0; height: 0; border: 0; padding: 0;" />
			<div style="width:100%;  border:2px dashed black; height: 100px; padding: 15px 0px 15px 0px" id="change_pkey_key_div">
				<div style="margin:auto; text-align:center; line-height:22px">
					<p style="margin-bottom:0px"  id="change_pkey_key_file_name" onclick="document.getElementById('change_pkey_upload_hidden').click();"></p>
					<button id="key_btn" style="margin-top:0px"  class="btn btn-outline btn-primary" onclick="document.getElementById('change_pkey_upload_hidden').click();"><i class="fa fa-camera"></i> Select photo</button>
					<p><?php echo $lng['or_dgag_and_drop_key']?></p>
				</div>
			</div>
		</fieldset>
		<div class="clearfix" style="margin-top: 20px"></div>

		<img src="img/<?php echo $tpl['photo_type']?>.jpg" style="float: left">
		<div style="float: left; text-align: center">
			<div id="<?php echo $tpl['photo_type']?>_photo_div" style="width: 350px; height: 500px"><canvas id="<?php echo $tpl['photo_type']?>_photo"></canvas></div>
			<div style="display: none; text-align: center" id="crop_text">
				<p>Поместите Ваше лицо в прямоульник, как на примере</p>
				<button id="rotate"class="btn btn-outline btn-primary"><i class="fa fa-repeat"></i> Rotate</button>
			</div>
		</div>
	</div>
<div class="clearfix"></div>
	<button class="btn btn-success" id="next_step" style="margin-top: 50px; margin-bottom: 10px"><?php echo str_replace('[num]',$tpl['next_step'],$lng['save_and_goto_step'])?></button>
	<input type="hidden" id="img_type" value="<?php echo $tpl['photo_type']?>">

	<div class="for-signature"></div>



<div id="coords" style="display: block"></div>



<script>

	$('#rotate').bind('click', function () {
		$('#<?php echo $tpl['photo_type']?>_photo').cropper("rotate", 90);
	});

	$( document ).ready(function() {

		if (window.FileReader === undefined) {
			$("#old_browser").css("display", "block");
		}
		document.getElementById('change_pkey_upload_hidden').addEventListener('change', change_handleFileSelect2, false);

	});

	var first_load = true;

	function change_handleFileSelect(f) {
		$('#change_pkey_key_file_name').html(f.name);
		var reader = new FileReader();
		reader.onload = (function(theFile) {
			return function(e) {
				var image = new Image();
				image.src = e.target.result;
				image.onload = function() {

					$('#<?php echo $tpl['photo_type']?>_photo').attr('width', 350);
					var k = this.width/350;
					var new_height = Math.round(this.height/k);
					$('#<?php echo $tpl['photo_type']?>_photo').attr('height', new_height);
					$('#<?php echo $tpl['photo_type']?>_photo_div').css('width', 350);

					var c=document.getElementById("<?php echo $tpl['photo_type']?>_photo");
					var ctx=c.getContext("2d");
					ctx.drawImage(image, 0, 0, this.width, this.height, 0, 0, 350, new_height);
					if (first_load==false) {
						$('#<?php echo $tpl['photo_type']?>_photo').cropper("destroy");
					}
					crop_img ('#<?php echo $tpl['photo_type']?>_photo');
					first_load = false;
				}
			};
		})(f);
		reader.readAsDataURL(f);
	}

	function change_handleFileSelect2(evt) {
		$('#change_pkey_key_file_name').html(this.value);
		var f = evt.target.files[0];
		change_handleFileSelect(f);
	}

	$('#change_pkey_key_div').on(
		'dragover',
		function(e) {
			e.preventDefault();
			e.stopPropagation();
		}
	)
	$('#change_pkey_key_div').on(
		'dragenter',
		function(e) {
			e.preventDefault();
			e.stopPropagation();
		}
	)
	$('#change_pkey_key_div').on(
		'drop',
		function(e){
			if(e.originalEvent.dataTransfer){
				if(e.originalEvent.dataTransfer.files.length) {
					e.preventDefault();
					e.stopPropagation();
					change_handleFileSelect(e.originalEvent.dataTransfer.files[0]);
				}
			}
		}
	);


	function crop_img (id) {
		$('#crop_text').css('display', 'block');
		if ($(window).width()>1024) {
			$(id).cropper({
				aspectRatio: 350 / 500,
				autoCropArea: 0.8, // Center 60%
				multiple: true,
				dragCrop: true,
				dashed: false,
				movable: true,
				resizable: true,
				done: function (data) {
					console.log(data);
					$("#coords").val(data.x + ';' + data.y + ';' + data.height + ';' + data.width);
				}
			});
		}
		else {
			console.log($(window).width());
			$(id).cropper({
				aspectRatio: 350 / 500,
				autoCropArea: 0.90, // Center 60%
				multiple: true,
				dragCrop: false,
				dashed: false,
				movable: false,
				resizable: false,
				done: function (data) {
					console.log(data);
					$("#coords").val(data.x + ';' + data.y + ';' + data.height + ';' + data.width);
				}
			});
		}
	}

	$('#next_step').bind('click', function () {

		if (first_load==false) {
			var coords = $('#<?php echo $tpl['photo_type']?>_photo').cropper("getData");
			var canvas = document.getElementById('<?php echo $tpl['photo_type']?>_photo');
			var dataURL = canvas.toDataURL();

			var image = new Image();
			image.src = dataURL;
			image.onload = function () {
				var c = document.getElementById('cropped_photo');
				var ctx = c.getContext("2d");
				ctx.drawImage(image, coords.x, coords.y, coords.width, coords.height, 0, 0, 350, 500);
				console.log(c.toDataURL());

				$.post('ajax/crop_photo.php', {
						'photo': c.toDataURL(),
						'type': '<?php echo $tpl['photo_type']?>'
					}, function (data) {
						console.log('ok');
						user_photo_navigate('upgrade_<?php echo $tpl['next_step']?>');
					}
				);
			}
		}
		else {
			user_photo_navigate('upgrade_<?php echo $tpl['next_step']?>');
		}

	});

	<?php
	// ранее загруженная картинка
	 if ($tpl['user_'.$tpl['photo_type']])
	    echo "var canvas = document.getElementById('{$tpl['photo_type']}_photo');
			      var context = canvas.getContext('2d');
			      var imageObj = new Image();
			      imageObj.src = '{$tpl['user_'.$tpl['photo_type']]}?r=".rand(0, getrandmax())."';
			      imageObj.onload = function() {
						  $('#{$tpl['photo_type']}_photo').attr('width', 350);
					      $('#{$tpl['photo_type']}_photo').attr('height', 500);
					      context.drawImage(imageObj, 0, 0, 350, 500);
			      };
		";
	?>

</script>

<input type="hidden" id="photo_type" value="<?php echo $tpl['photo_type']?>">
<canvas id="cropped_photo" width="350" height="500" style="display: none"></canvas>