<script>

var progress_pct_width = $('#progress_pct').width()/$('#progress_pct').parent().width()*100;

function hex_pkey(public_key_id) {
	var public_key = $('#'+public_key_id).val();
	public_key = public_key.replace(/[ \n]+/g, "");
	if (public_key.indexOf('-----') > -1)  {
		public_key = public_key.replace("-----BEGINPUBLICKEY-----", "");
		public_key = public_key.replace("-----ENDPUBLICKEY-----", "");
		public_key = b64tohex(public_key);
		console.log('b64tohex:'+public_key);
		$('#'+public_key_id).val(public_key);
	}
}

$('#public_key_2').keyup(function() {
	hex_pkey('public_key_2');
});

$('#public_key_3').keyup(function() {
	hex_pkey('public_key_3');
});


var save_private_key = 0;
var private_key = 0;
var mode = 'simple_protection_mode';

$('#send_to_net').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'public_key_1' : $('#public_key_1').val(),
			'public_key_2' : $('#public_key_2').val(),
			'public_key_3' : $('#public_key_3').val(),
			'private_key' : private_key,
			'password_hash' : $('#password_hash').val(),
			'save_private_key' : save_private_key,
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
		}, function (data) {
				//alert(data);
				fc_navigate ('home', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
	);

} );

$('#goto_confirm_key').bind('click', function () {

	if (progress_pct_width<20) {
		var new_pct = progress_pct_width + 7;
		$('#progress_pct').width(new_pct * $('#progress_pct').parent().width() / 100);
		$('#progress_pct').html(new_pct + '%');
	}

	$("#tx_history").css("display", "none");
	$("#password_div").css("display", "none");
	$("#confirm_key").css("display", "block");

	$("#step0").attr('class', '');
	$("#step1").attr('class', '');
	$("#step2").attr('class', 'active');

	if (mode=='enhanced_protection_mode') {
		console.log('goto_step_3_or_sign');
		$("#goto_step_3_or_sign").text('<?php echo $lng['next'] ?>');
	}

} );

$('#goto_password').bind('click', function () {

	if (progress_pct_width<20) {
		var new_pct = progress_pct_width + 4;
		$('#progress_pct').width(new_pct * $('#progress_pct').parent().width() / 100);
		$('#progress_pct').html(new_pct + '%');
	}

	$("#tx_history").css("display", "none");
	$("#mode_div").css("display", "none");
	$("#password_div").css("display", "block");
	$("#step0").attr('class', '');
	$("#step1").attr('class', 'active');
	$("#tx_history").css("display", "none");
} );

$('#goto_sign').bind('click', function () {

	$("#tx_history").css("display", "none");
	$("#two_keys").css("display", "none");
	$("#for-signature").val('<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#public_key_1").val()+','+$("#public_key_2").val()+','+$("#public_key_3").val());
	confirm_key
	<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#sign").css("display", "block");' ?>
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	<?php echo defined('SHOW_SIGN_DATA')?'$("#step0,#step1,#step2,#step3").attr(\'class\', \'\');$("#step4").attr(\'class\', \'active\');':'' ?>

} );

$('#goto_step_3_or_sign').bind('click', function () {

	$("#tx_history").css("display", "none");

	var e_n_sign = get_e_n_sign($("#change_pkey_private_key").val(), $("#change_pkey_password").val(), <?php print json_encode(utf8_encode(mcrypt_create_iv(mcrypt_get_iv_size('rijndael-128', MCRYPT_MODE_ECB), MCRYPT_RAND)))?>, '', 'change_pkey_alert');
	var public_key = make_public_key(e_n_sign['modulus'], e_n_sign['exp']);
	$("#public_key_1").val( public_key );
	$('#password_hash').val(hex_sha256(hex_sha256($('#change_pkey_password').val())));

	if (public_key.length < 512) {
		$("#change_pkey_alert").html('<div id="alertModalPull" class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button><p>'+$('#incorrect_key_or_password').val()+'</p></div>');
	}
	else if (mode=='simple_protection_mode') {

		if (progress_pct_width<20) {
			var new_pct = progress_pct_width + 10;
			$('#progress_pct').width(new_pct * $('#progress_pct').parent().width() / 100);
			$('#progress_pct').html(new_pct + '%');
		}

		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#public_key_1").val()+',,');
		$("#confirm_key").css("display", "none");
		$("#step2").attr('class', '');
		$("#step3").attr('class', 'active');
		<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#sign").css("display", "block");' ?>
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	}
	else {
		$("#two_keys").css("display", "block");
		$("#confirm_key").css("display", "none");
		$("#step0,#step1,#step2").attr('class', '');
		$("#step3").attr('class', 'active');
		<?php echo defined('SHOW_SIGN_DATA')?'$("#goto_sign").text("'.$lng['next'].'");':'' ?>

	}
} );



function change_pkey_show_text_key () {
	$("#change_pkey_private_key").css("display", "block");
	$("#change_pkey_key_div").css("display", "none");
	$("#change_pkey_key_selector").html('<a href="#" onclick="change_pkey_show_file_key();return false;"><?php echo $lng['from_file']?></a>');
}

function change_pkey_show_file_key () {
	$("#change_pkey_private_key").css("display", "none");
	$("#change_pkey_key_div").css("display", "block");
	$("#change_pkey_key_selector").html('<a href="#" onclick="change_pkey_show_text_key();return false;"><?php echo $lng['text']?></a>');
}

function change_handleFileSelect(f) {
	$('#change_pkey_key_file_name').html(f.name);
	var reader = new FileReader();
	if (f.type.substr(0,5) =='image') {
		reader.onload = (function(theFile) {
			return function(e) {
				img2key(e.target.result, 'change_pkey_private_key');
			};
		})(f);
		reader.readAsDataURL(f);
	}
	else {
		reader.onload = (function(theFile) {
			return function(e) {
				console.log(e.target.result);
				$('#change_pkey_private_key').val(e.target.result);
			};
		})(f);
		reader.readAsText(f);
	}
}

function show_steps(mode) {
	var count_steps = <?php echo !defined('SHOW_SIGN_DATA')?2:3 ?>;
	var steps = '';
	if (mode=='enhanced_protection_mode') // режим услиенной защиты
		count_steps++
	for (var i=0; i<=count_steps; i++) {
		if (i==0)
			var active = 'active';
		else
			var active = '';
		steps = steps + '<li class="'+active+'" id="step'+i+'"><a aria-expanded="false" href="#" onclick="step'+i+'()">step '+i+'</a></li>';
	}
	$( "#steps" ).html( steps );
}

$( document ).ready(function() {
	if (window.FileReader === undefined) {
		$("#change_pkey_private_key").css("display", "block");
		$("#change_pkey_key_file").css("display", "none");
		$("#change_pkey_key_selector").css("display", "none");
	}
	$("#tx_history").css("display", "block");
	show_steps('simple_protection_mode');
	<?php
	if ( (time()-$tpl['last_change_key_time'])<86400 || !empty($tpl['last_tx'][0]['queue_tx']) || !empty($tpl['last_tx'][0]['tx']) ) {}
	else {
		echo "document.getElementById('change_pkey_upload_hidden').addEventListener('change', change_handleFileSelect2, false);\n";
		echo "check_key_and_show_modal();\n";
	}
	?>

});

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


$('#download').bind('click', function () {
	$( "#goto_confirm_key" ).prop( "disabled", false );
	$( "#goto_confirm_key" ).removeAttr("disabled");
});


function step0() {
	$("#mode_div").css("display", "block");
	$("#password_div").css("display", "none");
	$("#confirm_key").css("display", "none");
	$("#two_keys").css("display", "none");
	$("#step0").attr('class', 'active');
	$("#step1,#step2,#step3").attr('class', '');
	$("#tx_history").css("display", "block");
	return false;
}

function step1() {
	$("#mode_div").css("display", "none");
	$("#password_div").css("display", "block");
	$("#confirm_key").css("display", "none");
	$("#two_keys").css("display", "none");
	$("#step0,#step2,#step3").attr('class', '');
	$("#step1").attr('class', 'active');
	$("#tx_history").css("display", "none");
	return false;
}

function step2() {
	$("#mode_div").css("display", "none");
	$("#password_div").css("display", "none");
	$("#confirm_key").css("display", "block");
	$("#two_keys").css("display", "none");
	$("#step0,#step1,#step3").attr('class', '');
	$("#step2").attr('class', 'active');
	$("#tx_history").css("display", "none");
	return false;
}

function enhanced_protection_mode () {
	show_steps('enhanced_protection_mode');
	$("#mode_text").html("<?php echo $lng['enhanced_protection_mode_3_key'] ?>");
	 $("#select_mode_link").html('<a href="#" onclick="simple_protection_mode()"><?php echo $lng['normal_mode'] ?></a>');
	mode = 'enhanced_protection_mode';
	return false;
}

function simple_protection_mode() {
	show_steps('simple_protection_mode');
	$("#mode_text").html("<?php echo $lng['normal_mode_1_key'] ?>");
	$("#select_mode_link").html('<a href="#" onclick="enhanced_protection_mode()"><?php echo $lng['enhanced_protection_mode'] ?></a>');
	mode = 'simple_protection_mode';
	return false;
}



</script>

<div id="main_div">

	<h1 class="page-header"><?php echo $lng['change_primary_key_title']?></h1>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

	<?php


	if ((time()-$tpl['last_change_key_time'])<86400) {
		echo "<div class=\"alert alert-info\">{$lng['change_primary_key_limit_text']}</div>";
	}
	else if (!empty($tpl['last_tx'][0]['queue_tx']) || !empty($tpl['last_tx'][0]['tx'])) {
		echo "<div class=\"alert alert-info\">{$lng['change_primary_key_exists_request']}</div>";
	}
	else {
		?>

		<div class="panel-body" style="max-width: 500px; margin: auto">
			<!-- Nav tabs -->
			<ul class="nav nav-tabs" id="steps">
			</ul>
		</div>

		<div id="mode_div" style="max-width: 600px; margin: auto">

			<p id="mode_text"><?php echo $lng['normal_mode_1_key'] ?></p>
			<p id="select_mode_link" style="float: right"><a href="#" onclick="enhanced_protection_mode()"><?php echo $lng['enhanced_protection_mode'] ?></a></p>
			<div class="clearfix"></div>
			<button id="goto_password" class="btn btn-outline btn-primary"><?php echo $lng['next'] ?></button>

		</div>

		<div id="password_div" style="display: none">
			<div class="form-horizontal">
				<form method="post" action="ajax/Dcoin-key.php" target="_blank">
					<fieldset>
						<!-- Password input-->
						<div class="form-group">
							<label class="col-md-4 control-label"
							       for="passwordinput"><?php echo $lng['password'] ?></label>

							<div class="col-md-4">
								<input id="new_password" class="form-control input-md" name="password" type="password">
								<span class="help-block"><?php echo $lng['choose_a_password'] ?></span>
							</div>
						</div>

						<!-- Button -->
						<div class="form-group">
							<label class="col-md-4 control-label" for="singlebutton"></label>

							<div class="col-md-4">
								<button id="download" class="btn btn-default"
								        type="submit"><?php echo $lng['download'] ?></button>
							</div>
						</div>

						<!-- Button -->
						<div class="form-group">
							<label class="col-md-4 control-label" for="singlebutton"></label>

							<div class="col-md-4">
								<a id="goto_confirm_key" class="btn btn-primary"
								   disabled="disabled"><?php echo $lng['next'] ?></a>
							</div>
						</div>

					</fieldset>
				</form>
			</div>

		</div>

		<div id="confirm_key" style="display: none; max-width: 600px; margin: auto">
			<fieldset>
				<div id="change_pkey_alert"></div>
				<p><?php echo $lng['please_upload_new_key'] ?></p>
				<span id="change_pkey_key_selector" style="float:right"><a href="#" onclick="change_pkey_show_text_key();return false;"><?php echo $lng['text'] ?></a></span>
				<div class="clearfix"></div>
				<input multiple type="file" name="upload" id="change_pkey_upload_hidden" style="position: absolute; display: block; overflow: hidden; width: 0; height: 0; border: 0; padding: 0;" />
				<div style="width:100%;  border:2px dashed black; height: 100px; padding: 15px 0px 15px 0px" id="change_pkey_key_div">
					<div style="margin:auto; text-align:center; line-height:22px">
						<p style="margin-bottom:0px"  id="change_pkey_key_file_name" onclick="document.getElementById('change_pkey_upload_hidden').click();"></p>
						<button id="key_btn" style="margin-top:0px"  class="btn btn-outline btn-primary" onclick="document.getElementById('change_pkey_upload_hidden').click();"><?php echo $lng['select_key']?></button>
						<p><?php echo $lng['or_dgag_and_drop_key']?></p>
					</div>
				</div>
				<textarea rows="3" id="change_pkey_private_key" class="form-control" style="display:none"></textarea><br>
				<label><?php echo $lng['password'] ?></label>
				<input type="password" id="change_pkey_password" class="form-control"><Br>
				<button id="goto_step_3_or_sign" class="btn btn-outline btn-primary"
				        type="submit"><?php echo !defined('SHOW_SIGN_DATA') ? $lng['send_to_net'] : $lng['next'] ?></button>
			</fieldset>
		</div>

		<div id="two_keys" style="display: none">
			<div style="margin: auto; max-width: 600px">
				<p><?php echo $lng['generate_somewhere_two_different_pairs_of_keys'] ?></p>
				<label><?php echo $lng['your_public_keys_1'] ?></label>
				<textarea class="form-control" rows="5" id="public_key_2"
				          style="width:600px;text-align: justify"></textarea><br>
				<label><?php echo $lng['your_public_keys_2'] ?></label>
				<textarea class="form-control" rows="5" id="public_key_3"
				          style="width:600px;text-align: justify"></textarea>
				<br>
				<button id="goto_sign" class="btn btn-outline btn-primary"><?php echo $lng['send_to_net'] ?></button>
			</div>
		</div>


		<?php require_once('signatures.tpl'); ?>

		<input type="hidden" id="public_key_1">
		<input type="hidden" id="password_hash">
	<?php
	}
	?>

	<div style="margin: auto; max-width: 600px; display: none" id="tx_history">

		<?php
		if (isset($tpl['last_tx_formatted'])) {
			echo $tpl['last_tx_formatted'];
		}
		?>

		<?php
		/*
		if (isset($tpl['my_keys'])) {
			echo '<h3>' . $lng['history'] . '</h3>';
			echo '<table class="table table-bordered" style="width:600px">';
			echo '<thead><tr><th>ID</th><th>' . $lng['block'] . '</th><th>' . $lng['time'] . '</th><th>' . $lng['status'] . '</th></tr></thead>';
			echo '<tbody>';
			foreach ($tpl['my_keys'] as $k => $data) {
				echo "<tr>";
				echo "<td>{$data['id']}</td>";
				echo "<td>{$data['block_id']}</td>";
				echo "<td>{$data['time']}</td>";
				echo "<td>{$status_array[$data['status']]}</td>";
				echo "</tr>";
			}
			echo '</tbody>';
			echo '</table>';
		}*/
		?>
	</div>

</div>
<script src="js/unixtime.js"></script>