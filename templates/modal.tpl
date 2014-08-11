<?php
$modal = <<<EOF
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


				<form>
					<fieldset>
						<label>{$lng['key']}</label>
						<textarea rows="3" id="modal_key" class="form-control"></textarea><br>
						<label>{$lng['key_password']}</label>
						<input type="password" id="modal_password" class="form-control">
					</fieldset>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-success  btn-lg btn-block" data-toggle="button" onclick="save_key();doSign('login')">{$lng['login']}</button>
			</div>
		</div>
	</div>
</div>
EOF;

?>