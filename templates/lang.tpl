<!-- container -->
<div class="container">

	<script>
		$('#save').bind('click', function () {
			$.post( 'ajax/save_interface.php', {
						'show_sign_data' : $('#show_sign_data').val()
					} ,
					function () { 
						fc_navigate ('interface', {'alert': '<?php echo $lng['saved']?>'} );
					});
		});
	</script>

  <legend><h2><?php echo $lng['lang']?></h2></legend>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

    <div>

	    <button class="btn" onclick="fc_navigate('home', 'lang=ru'); load_menu();">ru</button> <button class="btn" onclick="fc_navigate('home', 'lang=en'); load_menu();">en</button>

    </div>

</div>
<!-- /container -->