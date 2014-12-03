	<div id="main_div">
  <h1 class="page-header"><?php echo $lng['interface']?></h1>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

    <div>
	    <p>show_sign_data: <?php echo $tpl['show_sign_data']?$lng['now_enable']:$lng['now_off']?></p>
	    <a class="btn btn-outline btn-primary" href="#interface/show_sign_data=<?php echo $tpl['show_sign_data']?0:1?>"><?php echo $tpl['show_sign_data']?$lng['off']:$lng['enable']?></a>
	    <br><br>
	    <p>map: <?php echo $tpl['show_map']?$lng['now_enable']:$lng['now_off']?></p>
	    <a class="btn btn-outline btn-primary" href="#interface/show_map=<?php echo $tpl['show_map']?0:1?>"><?php echo $tpl['show_map']?$lng['off']:$lng['enable']?></a>
	    <br><br>
	    <p>progess bar: <?php echo $tpl['show_progress_bar']?$lng['now_enable']:$lng['now_off']?></p>
	    <a class="btn btn-outline btn-primary" href="#interface/show_progress_bar=<?php echo $tpl['show_progress_bar']?0:1?>"><?php echo $tpl['show_progress_bar']?$lng['off']:$lng['enable']?></a>
	    <br><br>

	    <?php echo isset($_REQUEST['parameters']['show_progress_bar'])?'<script>$( "#progress_bar" ).load( "ajax/progress_bar.php");</script>':''?>


    </div>
</div>