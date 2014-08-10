<script>
	$('#save').bind('click', function () {
		$.post('ajax/save_race_country.php', {
					'race' : $('input:radio[name=race]:checked').val(),
					'country' : $("#country option:selected").val()
				},
				function(data) {
					fc_navigate('change_country_race', {'alert': '<?php echo $lng['saved'] ?>'} );
				});
	});
</script>

	<h1 class="page-header"><?php echo $lng['change_country_race_title']?></h1>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

	<h3><?php echo $lng['your_race']?></h3>

	<img src="img/race.gif"><Br>

		<div style="float: left;width: 190px; text-align: center;"><input type="radio" name="race" value="1" <?php echo ($tpl['race']==1)?'checked':''?>></div>
		<div style="float: left;width: 160px; text-align: center; "><input type="radio" name="race" value="2"  <?php echo ($tpl['race']==2)?'checked':''?>></div>
		<div style="float: left; width: 140px; text-align: center; "><input type="radio" name="race" value="3"  <?php echo ($tpl['race']==3)?'checked':''?>></div>
	<Br>
	<h3>Country</h3>
	<?php
	echo "<select id='country'><option value='0'></option>";
	for ($i=0; $i<sizeof($tpl['countries']); $i++)
		echo "<option value='{$i}' ".($i==$tpl['country']?'selected':'').">{$tpl['countries'][$i]}</option>\n";
	echo '</select>';
	?>
	<br>

	<button class="btn btn-success" id="save">Save</button>
	<br><br><br>
	
	<div class="for-signature"></div>
       
