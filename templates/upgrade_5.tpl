<script>
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
	    <?php echo make_upgrade_menu(5)?>
    </ul>
    
	<h3>Host</h3>

	<?php echo $lng['your_host']?>
	<br>
	<div class="alert alert-error" id="alert" style="display: none"><?php echo $lng['invalid_host']?></div>

	<?php
	if (defined('COMMUNITY'))
		echo "<div id='node_host'><input type='hidden' id='host' value='{$tpl['data']['host']}'><p><strong>{$tpl['data']['host']}</strong><br>[<a href='#' id='change_node_host'>изменить</a>]</p></div>";
	else
		echo '<input class="form-control" style="width:300px" type="text" id="host" value="'.$tpl['data']['host'].'"><br>'.$lng['host_example'];
	?>

	<script>
		$('#change_node_host').bind('click', function () {
			$('#node_host').html('<input class="form-control" style="width:300px" type="text" id="host" value="<?php echo $tpl['data']['host']?>"><br><?php echo $lng['host_example']?>');
		});

		$('#save').bind('click', function () {
			$('#alert').css("display", "none");
			$.post( 'ajax/save_host.php', { 'host' : $('#host').val() } ,
				function (data) {
					if (data.error) {
						$('#alert').css("display", "block");
					}
					else {
						map_navigate('upgrade_6');
					}
				}, "JSON");
		});
	</script>
	<button class="btn btn-success" id="save"><?php echo str_replace('[num]','6',$lng['save_and_goto_step'])?></button>
	
	
	<br><br><br><br><br><br><br>
