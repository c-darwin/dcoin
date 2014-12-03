<?php
$modal = <<<EOF
<script>
function show_text_key () {
	$("#modal_key").css("display", "block");
	$("#key_div").css("display", "none");
	$("#key_selector").html('<a href="#" onclick="show_file_key()">{$lng['from_file']}</a>');
	return false;
}

function show_file_key () {
	$("#modal_key").css("display", "none");
	$("#key_div").css("display", "block");
	$("#key_selector").html('<a href="#" onclick="show_text_key()">{$lng['text']}</a>');
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
<div class="modal fade" id="myModal">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<h4 class="modal-title">{$lng['login_title']}</h4>
			</div>
			<div class="modal-body">

				<div id="modal_alert"></div>

				<input type="hidden" id="incorrect_key_or_password" value="{$lng['incorrect_key_or_password']}">
				<input type="hidden" id="pool_is_full" value="{$lng['pool_is_full']}">

					<fieldset>
						<span id="key_selector" style="float:right"><a href="#" onclick="show_text_key()">{$lng['text']}</a></span><div class="clearfix"></div>
							<input multiple type="file" name="upload" id="upload_hidden" style="position: absolute; display: block; overflow: hidden; width: 0; height: 0; border: 0; padding: 0;" />
							<div style="width:100%;  border:2px dashed black; display: flex;  height: 100px; padding: 15px 0px 15px 0px" id="key_div">
								<div style="margin:auto; text-align:center; line-height:22px">
								 <p style="margin-bottom:0px"  id="key_file_name" onclick="document.getElementById('upload_hidden').click();"></p>
								  <button id="key_btn" style="margin-top:0px"  class="btn btn-outline btn-primary" onclick="document.getElementById('upload_hidden').click();">{$lng['select_key']}</button>
								  <p>{$lng['or_dgag_and_drop_key']}</p>
								</div>
							</div>
						<textarea rows="3" id="modal_key" class="form-control" style="display:none"></textarea><br>
						<label>{$lng['key_password']}</label>
						<input type="password" id="modal_password" class="form-control">
					</fieldset>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-success  btn-lg btn-block" data-toggle="button" onclick="save_key();doSign('login')">{$lng['login']}</button>
			</div>
		</div>
	</div>
</div>
EOF;

?>