
<script>
	$('#next').bind('click', function () {
		$.post('ajax/save_race_country.php', {'race' : $('input:radio[name=race]:checked').val(), 'country' : $("#country option:selected").val()},
				function(data) {
					user_photo_navigate('upgrade_1');
					window.scrollTo(0,0);
				});
	});
	check_key_and_show_modal();
	$(document).ready(function() {
		$( "#progress_bar" ).load( "ajax/progress_bar.php");
	});
</script>

<h1 class="page-header"><?php echo $lng['upgrade_title']?></h1>
<ol class="breadcrumb">
	<li><a href="#mining_menu"><?php echo $lng['mining'] ?></a></li>
	<li class="active"><?php echo $lng['upgrade_title'] ?></li>
</ol>

    <ul class="nav nav-tabs">
	    <?php echo make_upgrade_menu(0)?>
    </ul>

	<h3><?php echo $lng['your_race']?></h3>

	<img src="img/race.gif"><Br>
	<div style="width: 490px">
		<div style="float: left;width: 190px; text-align: center;"><input type="radio" name="race" value="1" <?php echo ($tpl['race']==1)?'checked':''?>></div>
		<div style="float: left;width: 160px; text-align: center; "><input type="radio" name="race" value="2"  <?php echo ($tpl['race']==2)?'checked':''?>></div>
		<div style="float: left; width: 140px; text-align: center; "><input type="radio" name="race" value="3"  <?php echo ($tpl['race']==3)?'checked':''?>></div>
	</div>
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

	<button class="btn btn-success" id="next"><?php echo str_replace('[num]','1',$lng['save_and_goto_step'])?></button>
	
	
	<br><br><br><br><br><br><br>
		
	
	<div class="for-signature"></div>
       
