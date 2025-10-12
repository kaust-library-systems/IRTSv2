<?php
	if(!isset($_POST['reason'])&&!isset($_POST['other']))
	{
		echo 'A new version of the item with the handle: '.$handle.' will be created using the metadata for the item '.$idInIRTS.'.
            <br> - Please select the reason for creating the version:
            <br><hr><form method="post" action="reviewCenter.php?'.$selections.'">';
		
		$reasonArray = array(
            "Published in a journal.",
            "Published as a conference paper.",
            "New preprint version.");

		foreach($reasonArray as $reason)
		{
			echo '<label class="radio"><input type="radio" name="reason" value="'.$reason.'">'.$reason.'</label><br>';
		}

		echo '<div class="form-group">
			  <label for="other">Other reason for creating a version:</label>
			  <textarea class="form-control" rows="1" name="other"></textarea>
			</div>';

		echo '<input type="hidden" name="page" value="'.($page).'">
			<input type="hidden" name="idInIRTS" value="'.$idInIRTS.'">
            <input type="hidden" name="handle" value="'.$handle.'">
			<input type="hidden" name="transferType" value="createVersion">
			<button class="btn btn-lg btn-warning" type="submit" name="action" value="transfer">Confirm reason for creating version</button>
		</form>';
	}
