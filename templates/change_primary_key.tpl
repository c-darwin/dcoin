<script>

function hex_pkey(public_key_id) {
	var public_key = $('#'+public_key_id).val();
	public_key = public_key.replace(/[ \n]+/g, "");
	if (public_key.indexOf('-----') > -1)  {
		public_key = public_key.replace("-----BEGINPUBLICKEY-----", "");
		public_key = public_key.replace("-----ENDPUBLICKEY-----", "");
		public_key = b64tohex(public_key);
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
				fc_navigate ('change_primary_key', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
	);

} );

$('#goto_primary_key').bind('click', function () {

	$.post( 'ajax/generate_new_primary_key.php', { 'password' : $("#new_password").val() }, function (data) {

		console.log( data.private_key );
		console.log( data.public_key );

		$("#public_key_1").val( data.public_key );
		$("#password_hash").val( data.password_hash );
		$("#private_key").val( data.private_key );
		if ($("#save_private_key").prop("checked")) {
			save_private_key = 1;
			private_key = data.private_key;
		}
		else {
			save_private_key = 0;
			private_key = '';
		}
		$("#password_div").css("display", "none");
		$("#primary_key_div").css("display", "block");

		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+data.public_key+',,');

		if ($('input[name=mode]:checked').val()=='1')
			$("#goto_step_3_or_sign").text("<?php echo $lng['send_to_net']?>");

	}, 'json' );

} );

$('#goto_password').bind('click', function () {
	$("#mode_div").css("display", "none");
	$("#password_div").css("display", "block");
} );

$('#goto_step_3_or_sign').bind('click', function () {
	$("#primary_key_div").css("display", "none");
	var mode = $('input[name=mode]:checked').val();
	if (mode==1) {
		<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#sign").css("display", "block");' ?>
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	}
	else {
		$("#two_keys").css("display", "block");
	}
} );


$('#goto_sign').bind('click', function () {

	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#public_key_1").val()+','+$("#public_key_2").val()+','+$("#public_key_3").val());
	$("#two_keys").css("display", "none");
	<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#sign").css("display", "block");' ?>
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>

} );

check_key_and_show_modal();

</script>

<div id="main_div">

	<h1 class="page-header"><?php echo $lng['change_primary_key_title']?></h1>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

	<div id="mode_div">

		<div class="form-horizontal">
			<fieldset>
				<!-- Multiple Radios -->
				<div class="form-group">
					<label class="col-md-4 control-label" for="radios">Выберите режим</label>
					<div class="col-md-4">
						<div class="radio">
							<label>
								<input name="mode" value="1" checked="checked" type="radio">
								Обычный режим. У Вас будет 1 ключ. Если злоумышленник украдет этот ключ, то он получит полный контроль над аккаунтом. Утеря ключа будет означать утерю доступа к аккаунту.
							</label>
						</div>
						<div class="radio">
							<label>
								<input name="mode" value="2" type="radio">
								Режим усиленной защиты. У Вас будет 3 ключа. И каждая транзакция будет требовать 2-е дополнительные подписи. Утеря одного из 3-х ключей будет означать утерю доступа к аккаунту.
							</label>
						</div>
					</div>
				</div>

				<!-- Button -->
				<div class="form-group">
					<label class="col-md-4 control-label" for="singlebutton"></label>
					<div class="col-md-4">
						<button id="goto_password" class="btn btn-outline btn-primary"><?php echo $lng['next']?></button>
					</div>
				</div>

			</fieldset>
		</div>

		<div style="margin: auto; width: 600px">
			<div class="alert alert-info">
				После смены ключа, когда в истории статус станет "Принято", обязательно <a href="#myModal"  data-toggle="modal" data-backdrop="static">перелогиньтесь</a> с новым ключом.
			</div>
			<h3>История</h3>
			<?php
			if ($tpl['my_keys']) {
				echo '<table class="table table-bordered" style="width:600px">';
			echo '<thead><tr><th>ID</th><th>'.$lng['block'].'</th><th>'.$lng['time'].'</th><th>'.$lng['status'].'</th></tr></thead>';
			echo '<tbody>';
			foreach( $tpl['my_keys'] as $k=>$data ) {
			echo "<tr>";
				echo "<td>{$data['id']}</td>";
				echo "<td>{$data['block_id']}</td>";
				echo "<td>{$data['time']}</td>";
				echo "<td>{$status_array[$data['status']]}</td>";
				echo "</tr>";
			}
			echo '</tbody>';
			echo '</table>';
			}
			?>
		</div>

		<div class="alert alert-info"><?php echo $tpl['limits_text']?></div>

	</div>


	<div id="password_div" style="display: none">
		<div class="form-horizontal">
			<fieldset>
				<!-- Password input-->
				<div class="form-group">
					<label class="col-md-4 control-label" for="passwordinput">Пароль</label>
					<div class="col-md-4">
						<input id="new_password" class="form-control input-md" type="password">
						<span class="help-block">Придумайте пароль для Вашего нового праймари ключа</span>
					</div>
				</div>

				<!-- Button -->
				<div class="form-group">
					<label class="col-md-4 control-label" for="singlebutton"></label>
					<div class="col-md-4">
						<button id="goto_primary_key" class="btn btn-outline btn-primary"><?php echo $lng['next']?></button>
					</div>
				</div>

			</fieldset>
		</div>

	</div>


	<div id="primary_key_div" style="display: none">

		<div style="margin: auto; width: 600px">
			<label><?php echo $lng['your_new_key']?></label>
			<textarea class="form-control" rows="10" id="private_key" style="width:600px;text-align: justify"></textarea>
			<div class="alert alert-info" style="width:600px"><strong><?php echo $lng['attention_title']?> </strong> <?php echo $lng['your_new_key_rules']?></div>
			<button id="goto_step_3_or_sign" class="btn btn-outline btn-primary"><?php echo $lng['next']?></button>
		</div>

	</div>

	<div id="two_keys" style="display: none">
		<div style="margin: auto; width: 600px">
			<p>Сгенерируйте где-нибудь 2 разные пары ключей, например, <a href="http://democratic-coin.com/ru/keys/index.html?keys" target="_blank">тут</a>. Полученные 2 приватных ключа надежно сохраните, а лучше распечатайте. А публичные ключи укажите ниже:</p>
			<label><?php echo $lng['your_public_keys_1']?></label>
			<textarea class="form-control" rows="5" id="public_key_2" style="width:600px;text-align: justify"></textarea><br>
			<label><?php echo $lng['your_public_keys_2']?></label>
			<textarea class="form-control" rows="5" id="public_key_3" style="width:600px;text-align: justify"></textarea>
			<br>
			<button id="goto_sign" class="btn btn-outline btn-primary"><?php echo $lng['send_to_net']?></button>
		</div>
	</div>










<!--
	<div id="add" style="display: none">
		<form>
			<fieldset>
				<label><?php echo $lng['new_pass_for_key']?></label>
				<input type="password" placeholder="" id="new_password">
				<?php
				if (!defined('COMMUNITY'))
					echo '<label class="checkbox"><input type="checkbox" id="save_private_key"> '.$lng['save_key'].'</label>';
				?>
				<label class="checkbox"><input type="checkbox" id="three_keys"><?php echo $lng['3_keys']?></label>
				<br>
				<button type="submit" class="btn" id="save"><?php echo $lng['next']?></button>
			</fieldset>
		</form>
		<br>
	</div>-->

	<?php require_once( 'signatures.tpl' );?>

	<input type="hidden" id="public_key_1">
	<input type="hidden" id="password_hash">

</div>