<?php

/*

**** This file defines a function to display the form used to generate and retrieve Scopus exports as Pure XML for individual authors.

** Parameters :
	No parameters required


** Created by : Yasmeen Alsaedy
** Institute : King Abdullah University of Science and Technology | KAUST
** Date : 21 November 2019- 9:00 AM

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function individualExportForm()
{
	// assign the divisions to the array
	$divisions = array(
		array('label' => 'CEMSE', 'ownerID' => '30000171'),
		array( 'label' => 'PSE', 'ownerID' => '30000283'),
		array( 'label' => 'BESE', 'ownerID' => '30000284'),
		array( 'label' => 'Office of the Provost', 'ownerID' => '30000046'));

	// display the interface
	$form = '
	<form action="pureExport.php" class="generateXMLForm" method="post" name="generateXML" id="generateXML" enctype="multipart/form-data" required>
	<p>* This tool will return only the list of publications for this author before they joined KAUST.</p>
		  <div class="row">

			<div class="col">
			<input class="form-control" id="Scopus_Author_ID" name="Scopus_Author_ID" placeholder="Scopus Author ID" onclick="HideTheMessage()" autocomplete="off" required />

			</div>

			<div class="col">
				<select name="divisions" class="form-control form-control-sm" id="list" required>
				   <option value="" hidden disabled selected value>Select the person\'s division</option>';

	// display the division as dropadown list
	foreach($divisions as $division)
	{
		$form .= '<option value="'.$division['ownerID'].'" >'.$division['label'].'</option>';
	}

	$form .= 	'</select>
			</div>

			<div class="w-100"></div>

			<div class="col">

			</div>

			<div class="col" style="padding-top:25px;left:25%" id="buttonContainer">
			 <button onclick="showTheprogress()" type="submit" id="submit" name="import"
				class="btn btn-info" >Generate</button>
			</div>

			</div>
		</form>


		<div id="myProgress" style="display:block"> </div > <div id="loading" style="display:none" class="spinner-border text-secondary" role="status">
		<span class="sr-only">Loading...</span></div>

		<br>
		<br>


		'.listRecentExports(__FUNCTION__);

	return $form;
}
