
<script>
	$('#next').bind('click', function () {
		$.post('ajax/save_race_country.php', {'race' : $('input:radio[name=race]:checked').val(), 'country' : $("#country option:selected").val()},
				function(data) {
					fc_navigate('upgrade_1');
				});
	});
</script>

	<h1 class="page-header"><?php echo $lng['upgrade_title']?></h1>
	
    <ul class="nav nav-tabs">
		<li class="active"><a href="#" onclick="fc_navigate('upgrade_0')">Step 0</a></li>
		<li><a href="#" onclick="fc_navigate('upgrade_1')">Step 1</a></li>
		<li><a href="#" onclick="fc_navigate('upgrade_2')">Step 2</a></li>
		<li><a href="#" onclick="fc_navigate('upgrade_3')">Step 3</a></li>
	    <li><a href="#" onclick="map_navigate ('upgrade_4')">Step 4</a></li>
	    <li><a href="#" onclick="fc_navigate('upgrade_5')">Step 5</a></li>
    </ul>

	<h3><?php echo $lng['your_race']?></h3>

	<img src="img/race.gif"><Br>

		<div style="float: left;width: 190px; text-align: center;"><input type="radio" name="race" value="1" <?php echo ($tpl['race']==1)?'checked':''?>></div>
		<div style="float: left;width: 160px; text-align: center; "><input type="radio" name="race" value="2"  <?php echo ($tpl['race']==2)?'checked':''?>></div>
		<div style="float: left; width: 140px; text-align: center; "><input type="radio" name="race" value="3"  <?php echo ($tpl['race']==3)?'checked':''?>></div>
<div class="clearfix"></div>
	<Br>
	<?php echo $lng['find_your_race']?>

	<h3><?php echo $lng['country']?></h3>
	<?php echo $lng['any_country']?><br>
	<?php
	echo "<select id='country' class=\"form-control\" style=\"width:300px\"><option value=''></option>";
	for ($i=0; $i<sizeof($tpl['countries']); $i++)
		echo "<option value='{$i}' ".($i===$tpl['country']?'selected':'').">{$tpl['countries'][$i]}</option>\n";
	echo '</select>';
	?>
	<br>

	<button class="btn btn-success" id="next">Step 1</button>
	
	
	<br><br><br><br><br><br><br>
		
	
	<div class="for-signature"></div>
       
