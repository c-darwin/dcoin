
<script>

$('#save').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'holidays_id' : <?php echo $tpl['del_id']?>,
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
			}, function (data) {
				fc_navigate ('holidays_list', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			} );
	} );

</script>

  <h1 class="page-header">Удаление holidays</h1>
      
    <div id="sign">
	
		<label>Данные</label>
		<textarea id="for-signature" style="width:500px;" rows="4"><?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']},{$tpl['del_id']}" ?></textarea>
		<label>Подпись</label>
		<textarea id="signature" style="width:500px;" rows="4"></textarea>
		<br>
		<button class="btn"  id="save">Отправить в FC-сеть</button>

    </div>

