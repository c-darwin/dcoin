<div id="sign" style="display:none">
	<div class="form-group">
		<label><?php echo $lng['data']?></label>
		<textarea id="for-signature" class="form-control" style="" rows="4"></textarea>
	</div>
	<?php
	for ($i=1; $i<=$count_sign; $i++) {
		echo "<div class=\"form-group\"><label>{$lng['sign']} ".(($i>1)?$i:'')."</label><textarea class=\"form-control\" id=\"signature{$i}\" style=\"\" rows=\"4\"></textarea></div>";
	}
	?>
	<button class="btn btn-primary"  id="send_to_net"><?php echo $lng['send_to_net']?></button>

</div>