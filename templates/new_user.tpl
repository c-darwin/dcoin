<script>
	$('#new_user').bind('click', function () {

		$.post( 'ajax/generate_new_primary_key.php', function (data) {
			$("#div_new_user_0").css("display", "none");
			<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#div_new_user_1").css("display", "block");' ?>
			$("#public_key").val( data.public_key );
			$("#private_key").val( data.private_key );
			$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#public_key").val());
			doSign();
			<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
		}, 'json' );

	} );

	$('#next').bind('click', function () {
		$("#div_new_user_1").css("display", "none");
		$("#sign").css("display", "block");
	} );

	$('#send_to_net').bind('click', function () {

		$.post( 'ajax/save_queue.php', {
					'type' : '<?php echo $tpl['data']['type']?>',
					'time' : '<?php echo $tpl['data']['time']?>',
					'user_id' : '<?php echo $tpl['data']['user_id']?>',
					'public_key' : $('#public_key').val(),
					'private_key' : $('#private_key').val(),
					'signature1': $('#signature1').val(),
					'signature2': $('#signature2').val(),
					'signature3': $('#signature3').val()
				}, function (data) {
					fc_navigate ('new_user', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
				}
		);
	} );

	function get_img_refs (i, user_id, urls) {
		if (typeof urls == 'undefined' )
			return 0;
		var image = new Image();
		if (typeof urls[i] != 'undefined' && urls[i]!='' && urls[i]!='0') {
			console.log('TRY '+urls[i]+"/public/"+user_id+"_user_face.jpg"+"\ni="+i);
			image.src = urls[i]+"/public/"+user_id+"_user_face.jpg";
			image.onload = function(){
				console.log('OK '+urls[i]);
				image=null;
				$('.img_'+user_id).css("background", 'url('+urls[i]+'/public/'+user_id+'_user_face.jpg)  50% 50%');
				$('.img_'+user_id).css("background-size", "60px Auto");
			};
			// handle failure
			image.onerror = function(){
				image=null;
				console.log('error '+urls[i]);
				var bg = $('.img_'+user_id).css("background-image");
				if (typeof bg == 'undefined' || bg.length<10)
					get_img_refs (i+1, user_id, urls);
			};
			setTimeout
			(
				function()
				{
					if ( image!=null && (!image.complete || !image.naturalWidth) )
					{
						var bg = $('.img_'+user_id).css("background-image");
						image = null;
						console.log('error');
						if (typeof bg == 'undefined' || bg.length<10)
							get_img_refs (i+1, user_id, urls);
					}
				},
				2000
			);
		}
	}
</script>
<style>
	.ref .fa{margin-right: 5px;margin-left: 5px;}
</style>

<h1 class="page-header"><?php echo $lng['reg_users']?></h1>
<ol class="breadcrumb">
	<li><a href="#mining_menu"><?php echo $lng['mining'] ?></a></li>
	<li class="active"><?php echo $lng['reg_users'] ?></li>
</ol>

<?php require_once( ABSPATH . 'templates/alert_success.php' );?>


	<div id="div_new_user_0">
		<button id="new_user" type="button" class="btn btn-outline btn-primary">new user</button><br><br>
		<?php
		$ref_photos = array();
		if ($tpl['my_refs']) {
			echo '<table class="table table-striped table-bordered table-hover" style="max-width:600px">
			<thead>
			<tr>
				<th>User ID</th>
				<th>'.$lng['profit'].'</th>
				<th>'.$lng['action'].'</th>
			</tr>
			</thead>';
			echo '<tbody>';
			foreach($tpl['my_refs'] as $ref_user_id=>$data) {

				if ($data['hosts']) // чтобы вывести фотки
					$ref_photos[$ref_user_id] = json_encode($data['hosts']);

				if ($data['key']) {
					$key_url = $tpl['pool_url']."public/".substr(md5($data['key']), 0, 16);
				}
				echo "<tr>";
				if ($data['hosts']) // фото только у майнеров
					echo "<td style='text-align:center;vertical-align:middle'><div class='img_{$ref_user_id}' style='width:60px;height:60px; border-radius:50%;margin:auto'></div>user_id: {$ref_user_id}</td>";
				else
					echo "<td style='text-align:center;vertical-align:middle'>user_id: {$ref_user_id}</td>";
				echo "<td style='vertical-align:middle'>";
				if (!empty($data['amounts'])) {
					foreach($data['amounts'] as $currency_id=>$amount) {
						echo "{$amount} d{$tpl['currency_list'][$currency_id]}<br>";
					}
				}
				else {
					echo "0";
				}
				echo "</td>";
				if (empty($data['key']))
					echo "<td>{$lng['key_has_been_changed']}</td>";
				else
					echo "<td style='font-size: 25px' class='ref'><a href='{$key_url}.png' target='_blank'><i class='fa fa-download'></i></a> <a href='{$key_url}.txt' target='_blank'><i class='fa fa-file-text-o'></i></a> <a href='https://www.facebook.com/sharer/sharer.php?u={$key_url}.txt' target='_blank'><i class='fa fa-facebook-square'></i></a> <a href='https://twitter.com/home?status={$key_url}.txt' target='_blank'><i class='fa fa-twitter-square'></i></a> <a href='http://vkontakte.ru/share.php?url={$key_url}.txt' target='_blank'><i class='fa fa-vk'></i></a> <a href='mailto:?subject=Dcoin&amp;body={$key_url}.txt' target='_blank'><i class='fa fa-envelope-o'></i></a></td></tr>";
			}
			echo '</tbody>';
			echo '</table>';
		}
		?>

		</div>

	<div id="div_new_user_1" style="display: none">
		<textarea id="public_key" style="width: 700px; height: 300px; display: none" class="form-control"></textarea><br>
		<textarea id="private_key" style="width: 730px; height: 300px" class="form-control"></textarea><br>
		<button class="btn" id="next"><?php echo $lng['next']?></button>
	</div>

	<?php require_once( 'signatures.tpl' );?>

<div class="clearfix"></div>
<?php echo $tpl['last_tx_formatted']?>
<script src="js/unixtime.js"></script>

<?php
if ($tpl['global_refs']) {
	echo '<h3>'.$lng['leaders_dcoin'].'</h3><table class="table table-striped table-bordered table-hover" style="max-width:400px">
			<thead>
			<tr>
				<th style="width:100px">Miner</th>
				<th>'.$lng['profit'].'</th>
			</tr>
			</thead>';
	echo '<tbody>';
	foreach($tpl['global_refs'] as $ref_user_id => $data) {
		$ref_photos[$ref_user_id] = json_encode($data['hosts']);
		echo "<tr><td style='text-align:center;vertical-align:middle'><div class='img_{$ref_user_id}' style='width:60px;height:60px; border-radius:50%;margin:auto'></div>user_id: {$ref_user_id}</td>";
		echo "<td style='vertical-align:middle'>";
		foreach ($data['amounts'] as $ref_data) {
			echo "{$ref_data['amount']} d{$tpl['currency_list'][$ref_data['currency_id']]}<br>";
		}
		echo "</td></tr>";
	}
	echo '</tbody>';
	echo '</table>';
}
?>

<script>
	<?php
	foreach ($ref_photos as $user_id=>$hosts) {
		echo "get_img_refs (0, {$user_id}, {$hosts});\n";
	}
	?>
</script>