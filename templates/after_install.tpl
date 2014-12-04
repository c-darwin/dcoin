<style>
	#page-wrapper{
		margin: 0px 10% 0px 10%;
		border: 1px solid #E7E7E7;
		min-height: 550px;
	}
	#wrapper{height: 100%;}
	#dc_content{
		height: 550px;
		vertical-align: middle;
	}
</style>
<script>
	function show_text_key () {
		$("#modal_key").css("display", "block");
		$("#key_div").css("display", "none");
		$("#key_selector").html('<a href="#" onclick="show_file_key()"><?php echo $lng['from_file']?></a>');
		return false;
	}

	function show_file_key () {
		$("#modal_key").css("display", "none");
		$("#key_div").css("display", "block");
		$("#key_selector").html('<a href="#" onclick="show_text_key()"><?php echo $lng['text']?></a>');
		return false;
	}

	function handleFileSelect2(evt) {
		$('#key_file_name').html(this.value);
		var f = evt.target.files[0];
		handleFileSelect(f);
	}

	var handleFileSelect = function(f) {

		$('#key_file_name').html(f.name);
		var reader = new FileReader();
		if (f.type.substr(0,5) =='image') {
			reader.onload = (function(theFile) {
				return function(e) {
					console.log('img2key');
					img2key(e.target.result, 'modal_key');
				};
			})(f);
			reader.readAsDataURL(f);
		}
		else {
			reader.onload = (function(theFile) {
				return function(e) {
					console.log(e.target.result);
					$('#modal_key').val(e.target.result);
				};
			})(f);
			reader.readAsText(f);
		}
	}

	$( document ).ready(function() {
		if (window.FileReader === undefined) {
			$("#modal_key").css("display", "block");
			$("#key_div").css("display", "none");
			$("#key_selector").css("display", "none");
		}
		document.getElementById('upload_hidden').addEventListener('change', handleFileSelect2, false);
	});


	$('#next').bind('click', function () {

		var e_n_sign = get_e_n_sign($("#modal_key").val(), $("#modal_password").val(), <?php print json_encode(utf8_encode(mcrypt_create_iv(mcrypt_get_iv_size('rijndael-128', MCRYPT_MODE_ECB), MCRYPT_RAND)))?>, '', 'key_alert');
		var public_key = make_public_key(e_n_sign['modulus'], e_n_sign['exp']);
		if (public_key.length < 512) {
			$("#key_alert").html('<div id="alertModalPull" class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button><p><?php echo $lng['incorrect_key_or_password']?></p></div>');
		}
		else {
			$.post('content.php', {
				'tpl_name': 'after_install',
				'public_key': public_key,
				'first_load_blockchain': $('#first_load_blockchain_type').val()
			}, function (data) {
				fc_navigate('updating_blockchain');
			}, 'html');
		}
	});

	$('#key_div').on(
		'dragover',
		function(e) {
			e.preventDefault();
			e.stopPropagation();
		}
	)
	$('#key_div').on(
		'dragenter',
		function(e) {
			e.preventDefault();
			e.stopPropagation();
		}
	)
	$('#key_div').on(
		'drop',
		function(e){
			if(e.originalEvent.dataTransfer){
				if(e.originalEvent.dataTransfer.files.length) {
					e.preventDefault();
					e.stopPropagation();
					handleFileSelect(e.originalEvent.dataTransfer.files[0]);
				}
			}
		}
	);

</script>
<script>
	function show_key_form(){
		$('#new_or_key').css('display', 'none');
		$('#key_form').css('display', 'block');
	}
	function hide_key_form(){
		$('#new_or_key').css('display', 'block');
		$('#key_form').css('display', 'none');
	}

	function first_load_blockchain_node() {
		$('#first_load_blockchain_type').val('nodes');
		$('#first_load_blockchain').html('<?php echo $lng['initial_loading_blockchain_from_nodes']?><br><a href="#" onclick="first_load_blockchain_file()"><?php echo $lng['change']?></a>');
	}
	function first_load_blockchain_file() {
		$('#first_load_blockchain_type').val('file');
		$('#first_load_blockchain').html('<?php echo $lng['initial_loading_blockchain_from_a_file']?><br><a href="#" onclick="first_load_blockchain_node()"><?php echo $lng['change']?></a>');
	}

</script>
<div style="max-width: 600px; margin: auto; margin-top: 50px">
	<div id="new_or_key" style="display: block;">
		<?php echo $lng['i_a_new_user']?><br>
		<a href="#" onclick="show_key_form()"><?php echo $lng['change']?></a>
	</div>
	<div id="key_form" style="display: none">
		<fieldset>
			<div id="key_alert"></div>
			<span id="key_selector" style="float:right"><a href="#" onclick="show_text_key()"><?php echo $lng['text']?></a></span><div class="clearfix"></div>
			<input multiple type="file" name="upload" id="upload_hidden" style="position: absolute; display: block; overflow: hidden; width: 0; height: 0; border: 0; padding: 0;" />
			<div style="width:100%;  border:2px dashed black; display: flex;  height: 100px; padding: 15px 0px 15px 0px" id="key_div">
				<div style="margin:auto; text-align:center; line-height:22px">
					<p style="margin-bottom:0px"  id="key_file_name" onclick="document.getElementById('upload_hidden').click();"></p>
					<button id="key_btn" style="margin-top:0px"  class="btn btn-outline btn-primary" onclick="document.getElementById('upload_hidden').click();"><?php echo $lng['select_key']?></button>
					<p><?php echo $lng['or_dgag_and_drop_key']?></p>
				</div>
			</div>
			<textarea rows="3" id="modal_key" class="form-control" style="display:none"></textarea><br>
			<label><?php echo  $lng['key_password']?></label>
			<input type="password" id="modal_password" class="form-control">
		</fieldset>
		<a href="#" onclick="hide_key_form()"><?php echo $lng['i_want_to_enter_the_key_later']?></a>
	</div>
	<div id="first_load_blockchain" style="margin-top: 20px; margin-bottom: 20px">
		<?php echo $lng['initial_loading_blockchain_from_a_file']?><br>
		<a href="#" onclick="first_load_blockchain_node()"><?php echo $lng['change']?></a>
	</div>
	<input type="hidden" id="first_load_blockchain_type" value="file">
	<button id="next" class="btn btn-primary"><?php echo $lng['next']?></button>
</div>

<?php
require_once( ABSPATH . 'templates/modal.tpl' );
echo str_ireplace('myModal', 'myModalLogin', $modal);
?>

<script>
	$('#myModal').remove();
	$('#show_login').bind('click', function () {
		$('#myModalLogin').modal({ backdrop: 'static' });
	});

	$( document ).ready(function() {
		$('#show_login').css('display', 'block');
		$('#wrapper').spin(false);
	});

</script>