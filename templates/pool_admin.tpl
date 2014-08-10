	<script>
		$('#save').bind('click', function () {
			$.post( 'ajax/pool_add_users.php', {
						'pool_data' : $('#pool_data').val()
					} ,
					function () { 
						fc_navigate ('pool_admin', {'alert': '<?php echo $lng['saved']?>'} );
					});
		});
	</script>

  <h1 class="page-header">Pool admin</h1>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

    <div id="new">
	    format: user_id;public_key<br>
		<textarea style="width: 600px; height: 100px" id="pool_data"></textarea>
		<br>

		<button class="btn" id="save">Ok</button>

    </div>
     
