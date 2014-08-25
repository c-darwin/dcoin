
	<h1 class="page-header"><?php echo $lng['vote_for_me']?></h1>

	<?php
	echo '<table class="table table-bordered" style="width:600px">';
	echo '<thead><tr><th>type</th><th>vote_id</th><th>comment</th></tr></thead>';
	echo '<tbody>';
	foreach ( $tpl['my_comments'] as $data ) {
		print "<tr><td>{$data['type']}</td><td>{$data['vote_id']}</td><td>{$data['comment']}</td></tr>";
	}
	echo '</tbody>';
	echo '</table>';
	?>