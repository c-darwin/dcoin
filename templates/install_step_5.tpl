<div class="container">

	<ul class="nav nav-tabs">
		<li><a href="#" onclick="fc_navigate('install_step_0')">Step 0</a></li>
		<li><a href="#" onclick="fc_navigate('install_step_1')">Step 1</a></li>
		<li><a href="#" onclick="fc_navigate('install_step_2')">Step 2</a></li>
		<li><a href="#" onclick="fc_navigate('install_step_2_1')">Step 3</a></li>
		<li><a href="#" onclick="fc_navigate('install_step_3')">Step 4</a></li>
		<li><a href="#" onclick="fc_navigate('install_step_4')">Step 5</a></li>
		<li class="active"><a href="#" onclick="fc_navigate('install_step_5')">Step 6</a></li>
	</ul>

<?php echo $lng['your_primary_key']?>: <br>
<textarea rows="10" style="width: 600px" name="key">
<?php print $tpl['key']?>
</textarea><br>
<br>
<button class="btn btn-success" onclick="fc_navigate('install_step_6')"><?php echo $lng['next']?></button>
</div>