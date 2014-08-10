
	<h1 class="page-header"><?php echo $lng['alert_messages_title']?></h1>
	
	<?php
	echo '<table class="table table-bordered" style="width:600px">';
	echo '<thead><tr><th>ID</th><th>Text</th></tr></thead>';
	echo '<tbody>';
	if ($tpl['alert_messages'])
		foreach ( $tpl['alert_messages'] as $data ) {
			print "<tr><td>{$data['id']}</td><td>{$data['message']}</td></tr>";
		}
	echo '</tbody>';
	echo '</table>';
	?>
     
