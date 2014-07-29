<!-- container -->
<div class="container">

	<script>

		var message_id = 0;
		$('#send_to_net').bind('click', function () {

			$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
				'message_id' : message_id,
				'parent_id' : $('#parent_id').val(),
				'subject' : $('#subject').val(),
				'message' : $('#message').val(),
				'message_type' : 0,
				'message_subtype' : subtype,
				'encrypted_message' : $('#encrypted_message').val(),
				'signature1': $('#signature1').val(),
				'signature2': $('#signature2').val(),
				'signature3': $('#signature3').val()
			}, function(data){
				//alert(data);
				fc_navigate ('bug_reporting', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			});

		} );

		$('#save').bind('click', function () {

			subtype = $('input[name=subtype]:checked').val();
			if (!subtype)
				subtype = 0;

			subject =  $('#subject').val();
			if (!subject)
				subject = '';

			$.post( 'ajax/save_message_to_admin.php', {
					'parent_id' : $('#parent_id').val(),
					'subject' : subject,
					'message' : $('#message').val(),
					'message_type' : 0,
					'message_subtype' : subtype

			}, function (message_id_) {
				message_id = message_id_;
				comment = '{"parent_id":"'+$('#parent_id').val()+'","message_id":"'+message_id+'","subject":"'+subject+'","message":"'+$("#message").val()+'","type":"0","subtype":"'+subtype+'"}';

				$.post( 'ajax/encrypt_comment.php', {

					'to_user_id' : 1,
					'type' : 'bug_reporting',
					'comment' : comment

				}, function (data) {

					$("#encrypted_message").val(data);
					$("#data").css("display", "none");
					$("#sign").css("display", "block");
					$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+data );
					doSign();
					<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
				});
			});
		});


		function decrypt_admin_message(id) {
			var key = $("#key").text();
			var e_text = $("#encrypt_message_"+id).val();
			var pass = $("#password").text();
			if (pass) {
				text = decode64(key.replace(/\n|\r/g,""));
				var decrypt_PEM = mcrypt.Decrypt(text, <?php print json_encode(utf8_encode(mcrypt_create_iv(mcrypt_get_iv_size('rijndael-128', MCRYPT_MODE_ECB), MCRYPT_RAND)))?>, hex_md5(pass), 'rijndael-128', 'ecb');
			}
			else {
				decrypt_PEM = key;
			}
			var rsa2 = new RSAKey();
			rsa2.readPrivateKeyFromPEMString(decrypt_PEM); // N,E,D,P,Q,DP,DQ,C

			decrypt_comment = rsa2.decrypt(e_text);
			$.post( 'ajax/save_admin_decrypt_message.php', {
				'id' : id,
				'data' : decrypt_comment
			}, function (data) {
				fc_navigate('bug_reporting', {'parent_id':data.parent_id});
			}, 'json' );
		}

	</script>

	<legend><h2><?php echo $lng['bug_reporting_title']?></h2></legend>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

	<div id="data">

		<table class="table">
		<?php
			foreach( @$tpl['data']['messages'] as $data ) {
				// выводим список веток
				if ( $data['parent_id']==0 && $data['id']!=$tpl['parent_id'] && $data['decrypted']==1 ) {
					echo "<tr><td>parent: {$data['id']} <a href='#' onclick=\"fc_navigate('bug_reporting', {'parent_id':'{$data['id']}'})\">{$data['subject']}</a></td></tr>";
				}
				else if ( ($data['parent_id']!=0 || $data['id']==$tpl['parent_id']) && $data['decrypted']==1 ) {
					// выводим сами сообщения из ветки parent_id
					echo "<tr><td>Type: {$data['type']}<br>Status: {$data['status']}<br>Message: {$data['message']}</td></tr>";
				}
				else {
					echo "<tr><td><a onclick=\"decrypt_admin_message({$data['id']})\">encrypted</a><input type=\"hidden\" id=\"encrypt_message_{$data['id']}\" value=\"".bin2hex($data['encrypted'])."\"></td></tr>";
				}
			}

		?>
		</table>

		<input type="hidden" id="parent_id" value="<?php echo $tpl['parent_id']?>"><br>
		<?php echo ($tpl['parent_id']==0)?'Title: <br><input type="text" id="subject"><br>':'' ?>
		<?php echo $lng['message']?>:<br>
		<textarea id="message" style="width: 600px; height: 300px"></textarea>
		<p><?php echo @$tpl['limits_text']?></p>
		<br>
		<button class="btn" id="save"><?php echo $lng['next']?></button>
	</div>

	<?php require_once( 'signatures.tpl' );?>

	<input type="hidden" id="encrypted_message">

</div>
<!-- /container -->