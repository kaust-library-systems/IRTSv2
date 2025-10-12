<?php
/*

**** This function adds a row to a table

** Parameters :
	$table : the table to add the row to
	$row : the row to add to the table
	
** Return:
	TRUE if the row was added successfully, FALSE otherwise

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function addRow($table, $row){
	
	global $repository, $errors;

	$success = insert($repository, $table, array_keys($row), array_values($row));

	if(!$success){
		$errors[] = 'Error adding row to '.$table.': Row not added: '.json_encode($row);
	}

	set_time_limit(0);
	ob_flush();

	return $success;
}