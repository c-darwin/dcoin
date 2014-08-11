
<script>

	$('#send_test_sms').bind('click', function () {
		$.post( 'ajax/send_sms.php', { 'text' : $('#sms_text').val() } ,
				function (data) {
					//alert(data);
					fc_navigate ('notifications', {'alert': '<?php echo $lng['sms_sent']?>'} );
				});
	});

	$('#send_test_email').bind('click', function () {


		$.ajax({
			type: "POST",
			url: "ajax/send_test_email.php",
			data: { text: $('#sms_text').val() },
			dataType: "json",
			timeout: 5000, // in milliseconds
			success: function(data) {
				if (data.error!='null') {
					$("#html_errors").html(data.error);
					$("#html_errors").css("display", "block");
				}
				else	{
					fc_navigate ('notifications', {'alert': '<?php echo $lng['mail_sent']?>'} );
				}
			},
			error: function(request, status, err) {
				fc_navigate ('notifications', {'alert': '<?php echo $lng['mail_sent']?>'} );
				$("#html_errors").html('Error ('+status+')');
				$("#html_errors").css("display", "block");
			}
		});
		/*
		$.post( 'ajax/send_test_email.php', { 'text' : $('#sms_text').val() } ,
				function (data) {
					alert(data);

					if (data.error!='null') {
						$("#html_errors").html(data);
						$("#html_errors").css("display", "block");
					}
					else	{
						fc_navigate ('notifications', {'alert': '<?php echo $lng['mail_sent']?>'} );
					}
				}, 'JSON');
			*/
	});

	$('#save1').bind('click', function () {

		if	($("#use_smtp").is(':checked'))
			use_smtp = 1;
		else
			use_smtp = 0;

		$.post( 'ajax/save_email_sms.php', {
					'email' : $('#email').val(),
					'sms_http_get_request' : $('#sms_http_get_request').val(),
					'use_smtp' :  use_smtp,
					'smtp_server' :  $('#smtp_server').val(),
					'smtp_port' :  $('#smtp_port').val(),
					'smtp_ssl' :  $("#smtp_ssl :selected").val(),
					'smtp_auth' :  $("#smtp_auth :selected").val(),
					'smtp_username' :  $('#smtp_username').val(),
					'smtp_password' :  $('#smtp_password').val()
				} ,
				function (data) {
					//alert(data);
					fc_navigate ('notifications', {'alert': '<?php echo $lng['email_and_sms_change']?>'} );
				});
	});

	/*$('#show_smtp').bind('click', function () {
		$("#smtp").css("display", "block");
		$("#smtp_data").html("<a onlick='hide_smtp()'>Убрать</a>");
	});*/

	function show_smtp () {

		$("#smtp").css("display", "block");
		$("#smtp_data").html("<a href='#' onclick='hide_smtp()'><?php echo $lng['hide_smtp']?></a>");
	}

	function hide_smtp () {

		//$("#use_smtp").attr("checked","checked");
		$("#use_smtp").removeAttr("checked");
		$("#smtp").css("display", "none");
		$("#smtp_data").html("<a href='#' onclick='show_smtp()'><?php echo $lng['mail_problems']?></a>");
	}


	var my_notifications = [];
	$('#save2').bind('click', function () {

		i=0;
		$("input[type=hidden]", $("#notifications")).each(function(){
			name = $(this).val();
			sms = name+'_sms';
			email = name+'_email';
			my_notifications[i] = {};
			my_notifications[i].name = name;
			if	($("#"+sms).is(':checked'))
				my_notifications[i].sms = 1;
			else
				my_notifications[i].sms = 0;

			if	($("#"+email).is(':checked'))
				my_notifications[i].email = 1;
			else
				my_notifications[i].email = 0;

			i++;

		});

		$.post( 'ajax/save_notifications.php', { 'data' : JSON.stringify(my_notifications) } ,
				function (data) {
					//alert(data);
					fc_navigate ('notifications', {'alert': '<?php echo $lng['saved']?>'} );
					window.scrollTo(0, 0);
				});
	});

	$("select").addClass( "form-control" );
	$("input").addClass( "form-control" );
	$("textarea").addClass( "form-control" );
	$("input[type=text]").width( 500 );
	$("input[type=checkbox]").width( 30 );
	$("textarea").width( 500 );
	$("select").width( 500 );
	$("button").addClass( "btn-outline btn-primary" );
</script>
	<h1 class="page-header"><?php echo $lng['notifications_title']?></h1>

<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

<div id="html_errors" class="alert alert-error" style="display: none"></div>

<a href="http://sms.democratic-coin.com/?lang=<?php echo $lang?>" target="_blank" class="btn btn-outline btn-primary"><?php echo $lng['notifications_simple_sms']?></a>
<br><br>
<h3><?php echo $lng['notifications_advanced']?></h3>

<form class="form-horizontal">

	<div class="control-group">
		<label class="control-label" for="inputEmail">Email</label>
		<div class="controls">
			<input type="text" id="email" placeholder="Email" class="input-xlarge" value="<?php echo $tpl['data']['email']?>">
			<div id="smtp_data"><a href="#" onclick="show_smtp()"><?php echo $lng['mail_problems']?></a></div>
		</div>
	</div>

	<div id="smtp" <?php echo ($tpl['data']['use_smtp'])?'':' style="display: none"'?>>

		<div class="control-group">
			<label class="control-label" for="inputEmail"><?php echo $lng['use_smtp']?></label>
			<div class="controls">
				<input type="checkbox" id="use_smtp" <?php echo ($tpl['data']['use_smtp'])?'checked':''?>>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="inputEmail">SMTP server</label>
			<div class="controls">
				<input type="text" id="smtp_server" class="input-xlarge" value="<?php echo $tpl['data']['smtp_server']?>">
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="inputEmail">SMTP port</label>
			<div class="controls">
				<input type="text" id="smtp_port" class="input-xlarge" value="<?php echo $tpl['data']['smtp_port']?$tpl['data']['smtp_port']:465 ?>">
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="inputEmail">Requires SSL</label>
			<div class="controls">
				<select id="smtp_ssl" class="input-xlarge">
					<option value="1" <?php echo (!$tpl['data']['use_smtp'] || $tpl['data']['smtp_ssl'])?'selected="selected"':''?>>Yes</option>
					<option value="0" <?php echo (!$tpl['data']['use_smtp'] || $tpl['data']['smtp_ssl'])?'':'selected="selected"'?>>No</option>
				</select>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="inputEmail">Requires authentication</label>
			<div class="controls">
				<select id="smtp_auth" class="input-xlarge">
					<option value="1" <?php echo (!$tpl['data']['use_smtp'] || $tpl['data']['smtp_auth'])?'selected="selected"':''?>>Yes</option>
					<option value="0" <?php echo (!$tpl['data']['use_smtp'] || $tpl['data']['smtp_auth'])?'':'selected="selected"'?>>No</option>
				</select>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="inputEmail">SMTP account username</label>
			<div class="controls">
				<input type="text" id="smtp_username" class="input-xlarge" value="<?php echo $tpl['data']['smtp_username']?>">
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="inputEmail">SMTP password</label>
			<div class="controls">
				<input type="text" id="smtp_password" class="input-xlarge" value="<?php echo $tpl['data']['smtp_password']?>">
			</div>
		</div>


	</div>

<?php
if (node_admin_access($db)) {
?>
<br><br><div class="control-group">
		<label class="control-label" for="inputPassword">SMS HTTP GET-request</label>
		<div class="controls">
			<textarea id="sms_http_get_request" class="input-xlarge"><?php echo $tpl['data']['sms_http_get_request']?></textarea>
		</div>
	</div>
<?php
}
?>
	<div class="control-group">
		<div class="controls">
			<button class="btn" id="save1">Save</button>
		</div>
	</div>
</form>

<p><span class="label label-important"><?php echo $lng['warn']?></span> <?php echo $lng['after_saving_changes_to_the_email']?></p>

<form class="form-inline">

	<input type="hidden" id="sms_text" value="test test"><button type="submit" class="btn" id="send_test_email"><?php echo $lng['send_test_email']?></button>
</form>
<?php
if (node_admin_access($db)) {
?>
<p><span class="label label-important"><?php echo $lng['warn']?></span> <?php echo $lng['after_saving_changes_to_the_sms']?></p>

<form class="form-inline">
	<button type="submit" class="btn" id="send_test_sms"><?php echo $lng['send_a_test_sms']?></button>
</form>
<?php
}
?>

<h3><?php echo $lng['configuring_notifications']?></h3>
<div id="notifications">
<table class="table table-striped" style="width:500px">
	<tr><th></th><?php echo (node_admin_access($db))?'<th>SMS</th>':''?><th>Email</th></tr>
	<?php
	$i=0;
	foreach ($tpl['my_notifications'] as $name=>$data) {
		echo "<tr><td><input type='hidden' id='names' value='{$name}'><span ".($data['important']?" class='text-error'":"").">{$lng["notifications_".$name]}</span></td>";
		if (node_admin_access($db))
			echo "<td><input id='{$name}_sms' type='checkbox' ".($data['sms']?"checked":"")."></td>";
		else
			echo "<!--<td><input id='{$name}_sms' type='checkbox' ".($data['sms']?"checked":"")."></td>-->";
		echo "<td><input id='{$name}_email' type='checkbox' ".($data['email']?"checked":"")."></td></tr>";
		$i++;
	}
	?>
	<tr><td colspan="2" style="text-align: center"><button type="submit" class="btn" id="save2"><?php echo $lng['save']?></button></td></tr>
</table>
</div>
<br><br>


<script>
if	($("#use_smtp").is(':checked'))
	show_smtp ();
</script>
