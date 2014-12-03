	<script>
		$('#sql').change(function () {
			file_upload('sql', 'sql_progress', 'sql', 'pool_add_users.php');
		});

		$('#save').bind('click', function () {

			if($("#pool_tech_works").is(':checked'))
				var pool_tech_works = '1';
			else
				var pool_tech_works = '0';

			fc_navigate ('pool_admin', {'pool_tech_works':pool_tech_works, 'pool_max_users':$('#pool_max_users').val(), 'commission':$('#commission').val() } );
		} );

	</script>

	<link rel="stylesheet" href="css/progress.css" type="text/css" />
	<script type="text/javascript" src="js/uploader.js"></script>
	<script src="js/js.js"></script>
  <h1 class="page-header">Pool admin</h1>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

    <div id="new">

	    <div class="form-horizontal">
		    <fieldset>
			    <div class="form-group">
				    <label class="col-md-4 control-label" for="pool_tech_works">pool_tech_works</label>
				    <div class="col-md-4">
						    <input name="pool_tech_works" id="pool_tech_works" type="checkbox" <?php echo $tpl['config']['pool_tech_works']?'checked':''?>>
				    </div>
			    </div>
			    <div class="form-group">
				    <label class="col-md-4 control-label" for="pool_max_users">pool_max_users</label>
				    <div class="col-md-4">
					    <input id="pool_max_users" name="pool_max_users" class="form-control input-md" type="text" value="<?php echo $tpl['config']['pool_max_users']?>">
				    </div>
			    </div>
			    <div class="form-group">
				    <label class="col-md-4 control-label" for="commission">Commission</label>
				    <div class="col-md-4">
					    <textarea class="form-control" id="commission" name="commission"><?php echo $tpl['config']['commission']?></textarea>
				    </div>
			    </div>
			    <div class="form-group">
				    <label class="col-md-4 control-label" for="save"></label>
				    <div class="col-md-4">
					    <button id="save" name="save" class="btn btn-primary"><?php echo $lng['save']?></button>
				    </div>
			    </div>
		    </fieldset>
	    </div>



	    <div id="sql_progress" class="my_progress">0%</div><br>
	    <div id="sql_ok" class="alert alert-success" style="display: none"></div>
	    <div class="form-horizontal">
		    <fieldset>
			    <div class="form-group">
				    <label class="col-md-4 control-label" for="filebutton">Import users from sql</label>
				    <div class="col-md-4">
					    <input id="sql" name="file" class="input-file" type="file">
				    </div>
			    </div>
			    <div class="form-group">
				    <label class="col-md-4 control-label" for="filebutton">Export users to sql</label>
				    <div class="col-md-4">
					    <a type="button"  href="ajax/pool_mysql_dump.php" id="singlebutton" name="singlebutton" class="btn btn-primary">Download</a>
				    </div>
			    </div>

		    </fieldset>
	    </div>


	    <?php
	    echo "<h3>Users</h3><table class='table' style='width: 500px'><thead><tr><th>user_id</th><th>miner_id</th><th>email</th><th>del</th></tr></thead>";
	    // список юзеров и их удаление
		foreach ($tpl['users'] as $uid=>$data){
			echo "<tr><td>{$uid}</td><td>{$data['miner_id']}</td><td>{$data['email']}</td><td><a class=\"btn btn-danger\" href=\"#\" onclick=\"fc_navigate('pool_admin', {'del_id':'".$uid."'}); return false;\"><i class=\"fa fa-trash-o fa-lg\"></i> {$lng['delete']}</a></td></tr>";
		}
	    echo "</table>";

	    // лист ожидания
	    echo "<h3>Pool waiting list</h3><table class='table' style='width: 500px'><thead><tr><th>time</th><th>email</th><th>user_id</th></tr></thead>";
		foreach ($tpl['waiting_list'] as $data){
			echo "<tr><td>{$data['time']}</td><td>{$data['email']}</td><td>{$data['user_id']}</td></tr>";
		}
	    echo "</table>";
	    ?>


    </div>
     
