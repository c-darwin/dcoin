<!-- container -->

<div class="container">

	<legend><h2><?php echo $lng['alert_messages_title']?></h2></legend>
	
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
     

</div>

<!-- /container -->