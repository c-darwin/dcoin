<style>
	.progress {
		width:0%;
		overflow:hidden;
		height:20px;
		display:inline-block;
		vertical-align:middle;
		color:#FFF;
		text-align:right;
		text-shadow:1px 1px 0 #000;
		background:-o-linear-gradient(top,#888888,#333333);
		background:-moz-linear-gradient(top,#888888,#333333);
		background:-webkit-gradient(linear,left top,left bottom,from(#888888),to(#333333));
		background:-webkit-linear-gradient(top,#888888,#333333);
		-o-transition-property:width;
		-o-transition-duration:.5s;
		-moz-transition-property:width;
		-moz-transition-duration:.5s;
		-webkit-transition-property:width;
		-webkit-transition-duration:.5s;
	}

</style>
<script type="text/javascript" src="js/uploader.js"></script>
<script src="js/js.js"></script>

<!-- container -->
<div class="container">

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

	if ($("#video_url").val()) {
		var re = /watch\?v=([0-9A-Za-z_-]+)/i;
		var res;
		res = re.exec($("#video_url").val());
		video_url_id = res[1];
	}
	if (!video_url_id) {
		video_url_id='null';
		video_type='null';
	}
	else
		video_type='youtube';

	var ps_id;
	for (i=1; i<6; i++)	{
		ps_id = $('#ps'+i).val();
		if ( ps_id > 0 ) {
			payment_systems_ids = payment_systems_ids+ps_id+',';
		}
	}
	if (payment_systems_ids.length>1)
		payment_systems_ids = payment_systems_ids.substr(0, payment_systems_ids.length-1);
	else
		payment_systems_ids = '0';

	$("#add").css("display", "none");
	$("#sign").css("display", "block");
	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#currency_id").val()+','+$("#amount").val()+','+video_type+','+video_url_id+','+payment_systems_ids );
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>

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
			fc_navigate ('promised_amount_list', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
		});
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


</script>
  <legend><h2><?php echo $lng['promised_amount_add_title']?></h2></legend>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

    <div id="add">
	
		<label><?php echo $lng['currency']?></label>
		<select id="currency_id">
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
		<label><?php echo $lng['amount']?></label>
		<input id="amount" class="input-mini" type="text"> max: <span id="max_promised_amount"></span>
		<br>
	    <label><?php echo $lng['promised_amount_payment_systems']?></label>
		 <?php
		 for ($i=1; $i<6; $i++) {
		    echo '<select id="ps'.$i.'" style="width:100px">';
	        echo '<option value="0">----</option>';
			foreach ($tpl['payment_systems'] as $id => $name)
			    echo "<option value='{$id}'>{$name}</option>";
	        echo ' </select>';
	     }
		 ?>

	    <br>

	    <?php echo $lng['promised_amount_add_video_text']?>

	    <div>
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
								    <div id="video_mp4_progress" class="progress">0%</div><br>
								    <div id="video_mp4_ok" class="alert alert-success" style="display: none"></div>
								    <button id="del_mp4" style="display: none">Delete</button>

							    </td><td>

								    WebM or Ogg: <input type="file" id="video_webm_ogg" name="file" accept="video/webm, video/ogg"/>
								    <div id="video_webm_ogg_progress" class="progress" >0%</div>
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


	<p><span class="label label-important"><?php echo $lng['limits'] ?></span>  <?php echo $tpl['limits_text'] ?></p>


		<button class="btn" id="add_promised_amount"><?php echo $lng['next']?></button><br>

    </div>
    
	<?php require_once( 'signatures.tpl' );?>


</div>
<!-- /container -->
