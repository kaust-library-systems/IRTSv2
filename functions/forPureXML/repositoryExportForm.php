<?php

/*

**** This file defines a function to display the form used to retrieve repository exports as Pure XML.

** Parameters :
	No parameters required
	

** Created by : Yasmeen Alsaedy
** Institute : King Abdullah University of Science and Technology | KAUST
** Date : 21 November 2019- 9:00 AM 

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function repositoryExportForm()
{
	// item types to list in form
	$types = array('All ETDs','All Publications','Article','Book Chapter','Conference Paper','Dataset','Dissertation','Thesis');
	
	// form to retrieve a set of new and modified records from a date range (this is used to recover records that may have been skipped due to a temporary failure of the Pure import job) for a selected type
	$form = '
	<form action="pureExport.php" class="generateXMLForm" method="post" name="generateXML" id="generateXML" enctype="multipart/form-data" required>
	<p>* This tool will return new and modified items from this data range.</p>
		  <div class="row">

			<div class="col">
			<input class="form-control" id="Start_Date" name="Start_Date" placeholder="YYYY-MM-DD" onclick="HideTheMessage()" autocomplete="off" required />

			</div>
			
			<div class="col">
			<input class="form-control" id="End_Date" name="End_Date" placeholder="YYYY-MM-DD" onclick="HideTheMessage()" autocomplete="off" required />

			</div>

			<div class="col">
				<select name="type" class="form-control form-control-sm" id="list" required>
				   <option value="" hidden disabled selected value>Select the type</option>';

	// display the types as dropdown list
	foreach($types as $type)
	{
		$form .= '<option value="'.$type.'" >'.$type.'</option>';
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
		<span class="sr-only">Loading...</span></div>';
	
	// display the interface
	$form .= '
	<p>* Select an option to "Retrieve Pure XML" from the below list.</p>';
	
	$form .= listRecentExports(__FUNCTION__);

	return $form;
}	
