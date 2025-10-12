<?php
	//form button template
	$replyFormButtonTemplate = '<form action="reviewCenter.php?formType=approveEmbargoExtension" method="post">
		{bodyFieldPlaceholder}
		<input type="hidden" name="handle" value="'.$handle.'">
		<input type="hidden" name="itemID" value="'.$itemID.'">
		<input type="hidden" name="embargoEndDate" value="'.$embargoEndDate.'">
		<input type="hidden" name="receivedEmailID" value="'.$receivedEmailID.'">
		<input type="hidden" name="receivedEmail" value="'.htmlspecialchars($receivedEmail).'">
		<input type="hidden" name="emailDetailsJSON" value="'.htmlspecialchars(json_encode($emailDetails)).'">
		<input type="hidden" name="bundleUUIDsJSON" value="'.htmlspecialchars(json_encode($bundleUUIDs)).'">
		<input type="hidden" name="bundleFilesJSON" value="'.htmlspecialchars(json_encode($bundleFiles)).'">
		<input type="hidden" name="itemMetadataJSON" value="'.htmlspecialchars(json_encode($item['metadata'])).'">
		<button class="btn btn-block {buttonType}" type="submit" id="submit" name="action" value="{action}">{label}</button>
	</form>';