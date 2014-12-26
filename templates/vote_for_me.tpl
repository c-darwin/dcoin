	<h1 class="page-header"><?php echo $lng['vote_for_me']?></h1>
	<ol class="breadcrumb">
		<li><a href="#mining_menu"><?php echo $lng['mining'] ?></a></li>
		<li class="active"><?php echo $lng['vote_for_me']?></li>
	</ol>
	<?php
	echo '<table class="table table-bordered" style="width:600px">';
	echo '<thead><tr><th>type</th><th>vote_id</th><th>comment</th></tr></thead>';
	echo '<tbody>';
	foreach ( $tpl['my_comments'] as $data ) {
		print "<tr><td>{$data['type']}</td><td>{$data['id']}</td><td>{$data['comment']}</td></tr>";
	}
	echo '</tbody>';
	echo '</table>';
	?>