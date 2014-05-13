<div id="sign" style="display:none">

	<label><?php echo $lng['data']?></label>
	<textarea id="for-signature" style="width:500px;" rows="4"></textarea>
	<?php
	for ($i=1; $i<=$count_sign; $i++) {
		echo "<label>{$lng['sign']} ".(($i>1)?$i:'')."</label><textarea id=\"signature{$i}\" style=\"width:500px;\" rows=\"4\"></textarea>";
	}
	?>
	<br>

	<button class="btn"  id="send_to_net"><?php echo $lng['send_to_net']?></button>

</div>