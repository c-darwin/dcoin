
	<h1 class="page-header"><?php echo $lng['holidays_title']?></h1>
	<?php echo ($tpl['alert'])?'<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>'.$tpl['alert'].'</div>':''?>
	<?php echo $lng['holidays_warning']?>
<p><a href="#" onclick="fc_navigate('new_holidays')">Add holidays</a></p>

<?php
	if (isset($tpl['holidays_list']['my_pending'])) {
		echo '<table class="table table-bordered" style="width:500px"><caption><h3>'.$lng['my_pending'].'</h3></caption>';
		echo '<thead><tr><th>'.$lng['start_time'].'</th><th>'.$lng['end_time'].'</th></tr></thead>';
		echo '<tbody>';
		$i=0;
		foreach ($tpl['holidays_list']['my_pending'] as $data) {
			echo "
				<tr>
				<td><span id='holidays_start_{$i}'>{$data['start_time']}</span></td>
				<td><span id='holidays_end_{$i}'>{$data['end_time']}</span></td>
				</tr>
				";
			$i++;
			}
			echo '</tbody>';
			echo '</table>';
	}

if (isset($tpl['holidays_list']['accepted'])) {
echo '<table class="table table-bordered" style="width:500px"><caption><h3>'.$lng['found_in_blocks'].'</h3></caption>';
	echo '<thead><tr><th>'.$lng['start_time'].'</th><th>'.$lng['end_time'].'</th><!--<th colspan="2" style="text-align:center">'.$lng['action'].'</th>--></tr></thead>';
	echo '<tbody>';
	$i=0;
	foreach ($tpl['holidays_list']['accepted'] as $data) {
	echo "
	<tr>
		<td><span id='holidays_start_{$i}'>{$data['start_time']}</span></td>
		<td><span id='holidays_end_{$i}'>{$data['end_time']}</span></td>
	</tr>
	";
	$i++;
	}
	echo '</tbody>';
	echo '</table>';
}
?>
<script>
	var holidays_time = new Date();
	for (i=0; i<<?php echo sizeof($tpl['holidays_list']['accepted'])?>; i++) {
		time = $("#holidays_start_"+i).text()*1000;
		holidays_time.setTime(time);
		$("#holidays_start_"+i).text(holidays_time.toLocaleString());
		time = $("#holidays_end_"+i).text()*1000;
		holidays_time.setTime(time);
		$("#holidays_end_"+i).text(holidays_time.toLocaleString());
	}
</script>
<br>
<p><span class="label label-important"><?php echo $lng['limits']?></span> <?php echo $tpl['limits_text'] ?></p>

