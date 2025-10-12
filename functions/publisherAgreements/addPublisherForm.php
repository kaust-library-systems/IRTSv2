<?php

/*

**** This file is responsible displaying the publisher form.

** Parameters :
	$selectedPublisherID: string the seletced publisher ID.
	$selectedPublisherAgreements: array the seletced publisher's agreements.
	$isHistory: boolean if the user is viewing the history of the publisher.

** Output : returns a HTML form.

*/

//------------------------------------------------------------------------------------------------------------

function addPublisherForm($selectedPublisherID, $selectedPublisherAgreements, $isHistory ){
	
	// init 
	$selected = '';
	$types = array('R&P', 'APC discount', 'Subscribe to open (S2O)');
	$eligibleauthors = array('First corresponding author', 'Any corresponding author', 'Any author');
	$checked = '';
	$form = '';
	// max & min date for the start date
	// max & min date for the start date
	$currentYear = date("Y");
	$min = ( $currentYear - 2 )."-01-01";
	$max = $currentYear."-12-31";
	$info = explode("-", $selectedPublisherID);
	
	if(!($isHistory)){
		$form =  "			
		  <!-- Modal-->
		  
			<form action='PA.php' method='POST'>
					  <div class='row'>
						<div class='col text-right'>
						 <input type='hidden' id='PublisherID' name='publisherID' value='".$selectedPublisherID ."'>
						 <input type='hidden' name='id' value='".$selectedPublisherID ."'>
							<input type='submit' name='deletePublisher' class='btn btn-danger' style='border:none' value='Delete This Publisher'>
						</div>
					</div>
					
			</form>";
			
	}
		
	$form .= "
			
			  <div class='modal-body'>
				<p>Fill The Form</p>
				
				<form action='PA.php' method='POST'>";

	if(!($isHistory))
		echo "<div class='form-group  float-sm-right add-agreement' style='display: block;'>
				<button type='button' onclick='cloneTheAgreement(2)' class='btn btn-success btn-circle btn-sm '>+</button>";


echo "
			</div> 			
	<div class='line'></div>
	<br><br>";

	foreach ($selectedPublisherAgreements as $key => $values ){	
		if(isset($values['pa.notification']))
			$checked = 'checked';
		
		$form .= "
		<div class='agreements2' id='cloned".$key."'>
		<div class='card' id='agreement-class-2'  >

		<div class='card-body'>
		  <div class='form-group'>
				<label for='agreement1' class='col-form-label'> Agreement:</label>
			</div>
			<div class='form-group '>
		 
				<label class='form-control-label' for='AgreementType'>Agreement Type: </label>
														
					<select class='mdb-select md-form form-control'  name='text_list[]' required='required'>
					
					<option value='' selected disabled></option>";					
					
				foreach ($types as $type ){
					if(strpos($values['pa.type'], $type ) !== FALSE )
						$selected = 'selected';

					$form .=  "<option value='".$type."' ".$selected." >".$type."</option>";
					
					// reset
					$selected = '';					
				}
				
				$form .= "	
					
		
				</select>
			</div>

			 <div class='form-group '>
		 
				<label class='form-control-label' for='eligibleauthors'>Eligible author(s): </label>
				
				
					<select class='mdb-select md-form form-control'  name='text_list[]' required='required'>
					
					<option value='' selected disabled></option>";

						foreach ($eligibleauthors as $eligibleauthor ){
						
							if(strpos($values['pa.eligibleauthors'], $eligibleauthor ) !== FALSE )
								$selected = 'selected';
						
						
							$form .=  "<option value='".$eligibleauthor."' ".$selected." >". $eligibleauthor ."</option>";
							
							
							// reset
							$selected = '';
						
						}
						
			$form .= "
			
				</select>
			</div>

		  <div class='form-group row'>
				<label class='col-sm form-control-label' for='startDate'>OA Publishing agreement start date</label>
				<div class='col-sm-6'>
				  <input type='date'  name='text_list[]' class='form-control' min='$min' max='$max' value = '".$values['pa.date.start']."' required='required'>
				</div>
			</div>
		  
		  
		  <div class='form-group row'>
			<label class='col-sm form-control-label' for='endDate'>OA Publishing agreement end date</label>
			<div class='col-sm-6'>
			  <input type='date' min='$min' name='text_list[]' value ='".$values['pa.date.end']."'  class='form-control' required='required'>
			</div>
		  </div>
			
			<div class='form-group '>
			<label class='form-control-label' for='notify'>Email notification</label>
			<input type='checkbox'  name='check_list[]' value='notify' class='form-control-custom col-sm-0 form-control-label' style='margin-left: 15px;'  $checked>
			 </div>
			
		  </div>
		";
		
		// reset the check 
		$checked = '';
		
		// add delete button 
		if(!($isHistory)){
			
			$form .= "<button type='button' id='cloned".$key."' onclick='removeAgreement(this.id)' class='btn btn-danger btn-circle btn-sm'>-</button>";
		}
		
		$form .= "</div>
		</div>";
	}
			
		$form .= "<div class='row'>
					<div class='col text-center'>
					 <input type='hidden' id='publisherID' name='publisherID' value='".$selectedPublisherID ."'>
					 <input type='hidden' name='id' value='".$selectedPublisherID ."'>";
					 
					 
			 if(!($isHistory)){
				 
				$form .=  "<input type='submit' class='btn btn-primary btn-lg btn-block' style='background-color:#F18F00;border:none'>";
			 }	
			 
		$form .= "
					</div>
				</div>		
			</form> 
		</div>";
	
	return $form;
}