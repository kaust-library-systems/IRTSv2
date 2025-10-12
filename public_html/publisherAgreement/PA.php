<!--
=========================================================
* Argon Dashboard - v1.2.0
=========================================================
* Product Page: https://www.creative-tim.com/product/argon-dashboard


* Copyright  Creative Tim (http://www.creative-tim.com)
* Coded by www.creative-tim.com

=========================================================
* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
-->
<?php

	header('Content-Type: text/html; charset=UTF-8');

	//This allows users to use the back button smoothly without receiving a warning from the browser
	header('Cache-Control: no cache');
	session_cache_limiter('private_no_expire');

	ini_set('display_errors', 1);

	set_include_path('/var/www/irts');

	//include core configuration and common function files
	include_once 'include.php';
	
	$pageTitle = 'OA Publishing Agreement';
	$pageLink = 'PA.php';
	
	//initialize the session.
	session_start();

	// check for authenticated user
	if(!isset($_SESSION['username']))
    {
		$location = 'PA.php';
		include_once 'snippets/login.php';
	}
	elseif(!isset($_SESSION['role']))
	{	
		
		include_once 'snippets/html/header.php';

		include_once 'snippets/html/startBody.php';

		echo '<div class="text">
		Your email address ('.$_SESSION['mail'].') is not in the list of authorized processors for the new items form. If you believe you should have access to this form, please email <a href="mailto:'.IR_EMAIL.'">'.IR_EMAIL.'</a> for access.</a>.
		</div>';
	}
	elseif($_SESSION['role'] == 'ADMIN' || $_SESSION['role'] == 'AUTHORIZED_PROCESSOR_PUBLISHER_AGREEMENTS' || $_SESSION['role'] == 'AUTHORIZED_PROCESSOR')
	{		
		include_once 'snippets/problemTypes.php';
		include 'snippets/setFormVariables.php';

		// initial
		$name = explode(' ', $_SESSION['displayname'])[0];
		$actual_link = "https://$_SERVER[HTTP_HOST]/irts/publisherAgreement/PA.php";
		
		// max & min date for the start date
		$currentYear = date("Y");
		$today = date('Y-m-d');
		$min = ( $currentYear - 2 )."-01-01";
		$max = $currentYear."-12-31";		
		
		// get all the seleted publishers
		$selectedPublishers = getValues($irts, "SELECT idInSource, `value` FROM `metadata` WHERE`source` = 'PA' AND `field` = 'pa.publisher'  ANd idInSource IN ( SELECT idInSource FROM `metadata` WHERE`source` = 'PA' AND `field` = 'pa.date.end' AND VALUE >= '".$today."%' AND `deleted` IS NULL  ) AND `deleted` IS NULL", array('idInSource', 'value'), 'arrayOfValues');
	
		$histPublsihers = getValues($irts, "SELECT idInSource, `value` FROM `metadata` WHERE`source` = 'PA' AND `field` = 'pa.publisher'  ANd idInSource IN ( SELECT idInSource FROM `metadata` WHERE`source` = 'PA' AND `field` = 'pa.date.end' AND VALUE < '".$today."%' AND `deleted` IS NULL  ) AND `deleted` IS NULL", array('idInSource', 'value'), 'arrayOfValues');

		// get all publisher
		$publishers = getValues($irts,"SELECT DISTINCT `value`, `idInSource` FROM `metadata` WHERE `source` = 'crossref' AND `field` = 'crossref.member.name' AND deleted IS NULL order by value ASC", array('value', "idInSource"), 'arrayOfValues');

		// create new array to fill it with the publisher ID as key and name as value
		$publisherByID = array();
		
		//get the history list of publishers
		$histPublisherIDs = array();

		//$SelectedPublishers = array_map('ucwords',$SelectedPublishers['value']);
		if(isset($_POST['deletePublisher'])){
			
			$publisherID = $_POST['publisherID'];
			deletePublisher($publisherID);
		}	
		elseif(isset($_POST['text_list']) && isset($_POST['publisherID']))
		{			
			$publisherID = $_POST['publisherID'];
			
			if(!empty($publisherID))
			{				
				$list = $_POST['text_list'];
				$notify = '';
				
				if(isset($_POST['check_list']))
				{					
					$notify = $_POST['check_list'];
				}
				
				//Publisher name, Publisher Agreements, Start Date, End Date
				savePA($publisherID, $list, $notify, (sizeof($selectedPublishers) + 1 ));
				
				$publishers = getValues($irts,"SELECT DISTINCT `value`, `idInSource` FROM `metadata` WHERE `source` = 'crossref' AND `field` = 'crossref.member.name' AND deleted IS NULL order by value ASC", array('value', "idInSource"), 'arrayOfValues');
				
				if(isset($_POST['id']))
				{	
					header("Location: $actual_link?id=".$_POST['id']);
					die();
				} 
				else
				{
					
					header("Location: $actual_link");
					die();
				}	
			}
		}



			echo "
			<!DOCTYPE html>
			<html>

			<head>
			  <meta charset='utf-8'>
			  <meta name='viewport' content='width=device-width, initial-scale=1, shrink-to-fit=no'>
			  <meta name='description' content='Start your development with a Dashboard for Bootstrap 4.'>
			  <meta name='author' content='Creative Tim'>
			  <title>OA Publishing Agreement</title>
			  <!-- Favicon -->
			  
			  <!-- Fonts -->
			  <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700'>
			  <!-- Icons -->
			  <link rel='stylesheet' href='./assets/vendor/nucleo/css/nucleo.css' type='text/css'>
			  <link rel='stylesheet' href='./assets/vendor/@fortawesome/fontawesome-free/css/all.min.css' type='text/css'>
			  <!-- Page plugins -->
			  <!-- Argon CSS -->
			  <link rel='stylesheet' href='./assets/css/argon.css' type='text/css'>
			</head>

			<body style='background-color:#D1D3D4'>
			  <!-- Sidenav -->
			  <nav class='sidenav navbar navbar-vertical  fixed-left  navbar-expand-xs navbar-light bg-white' id='sidenav-main'>
				<div class='scrollbar-inner'>
				  <!-- Brand -->
				  <div class='sidenav-header  align-items-center'>
					<div class='navbar-brand' >
					  Hi $name,
					</div>
				  </div>
			  
			  
			  
			  
			  
			  
			  
			  <div class='navbar-inner'>
				<!-- Collapse -->
				<div class='collapse navbar-collapse' id='sidenav-collapse-main'>
				
				  <ul class='navbar-nav'>
					
					<li class='nav-item'>
					 
					
						<button class='btn btn-secondary btn-list-second dropdown-btn' type='button'  data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Publishers 
							<i class='fa fa-caret-down'></i>
						  </button>
						  <div class='dropdown-container'>
						  <a class='nav-link' href='#' data-toggle='modal' data-target='#informationModal'> Add Publisher</a>
						  
						  ";
						
							foreach ($selectedPublishers as $publisher ){
								
								$publisherPageURL = "https://$_SERVER[HTTP_HOST]/irts/publisherAgreement/PA.php"."?id=".$publisher['idInSource'];
							
								echo "<a class='nav-link' href='$publisherPageURL'>".ucwords($publisher['value'])."</a>";
							  
								$publisherByID[$publisher['idInSource']] =ucwords($publisher['value']);
								
							}
					
						  echo "
							
						</div>
					
					</li>
					  
			  
						  
				
					  </a>
					
				  </ul>
				  
				  
				  	  <ul class='navbar-nav'>
						<li class='nav-item'>
							<button class='btn btn-secondary btn-list-second dropdown-btn' type='button'  data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>History 
								<i class='fa fa-caret-down'></i>
							  </button>
							  <div class='dropdown-container'> 
							 
							  
							  ";
							  
								
								
								$histPublsihersPreYear = array();
								
								foreach ($histPublsihers as $histPublisher ){
									
									$endDate =  getValues($irts, "SELECT value FROM `metadata` WHERE`source` = 'PA' AND `field` = 'pa.date.end' AND idInSource = '".$histPublisher['idInSource']."' AND `deleted` IS NULL ", array('value'), 'singleValue');
									
									$idAndDate = explode("-", $histPublisher['idInSource']);
									$endDateYear =  explode("-", $endDate)[0];
									
									if(!isset($histPublsihersPreYear[$endDateYear]))
										$histPublsihersPreYear[$endDateYear] = array(); 
									
									array_push($histPublsihersPreYear[$endDateYear], $histPublisher['idInSource']);
									
									$histPublisherIDs[$histPublisher['idInSource']] =  ucwords($histPublisher['value']);
									
								}
								
								foreach ($histPublsihersPreYear as $year =>  $histPublsihersArray ) {
									 
									echo "
											<button class='btn btn-secondary btn-list-second dropdown-btn' type='button'  data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>$year 
												<i class='fa fa-caret-down'></i>
											  </button>
											  <div class='dropdown-container'>";
									
									foreach ($histPublsihersArray as $publisher ) {
										
										
										
										echo "<a class='nav-link' href='$actual_link?id=".$publisher."'>".$histPublisherIDs[$publisher]."</a>";
									   
									  
										
									   
									  
									}


										
										
									echo "	</div>";
								}
									
								
							
						
							  echo "
						</div>
						</li>
					
				  </ul> 
				  
				  
				  
			
				  
				  
				 
				  
					
				   <!-------- AGREEMENT FORM ---->
						<div id='informationModal' tabindex='-1' role='dialog' aria-labelledby='informationModal' aria-hidden='true' class='modal fade'>
						  <div class='modal-dialog modal-dialog-scrollable' role='document'>
							<div class='modal-content'>
							  <div class='modal-header'>
								<h5  class='modal-title'> Add Publisher</h5>
							  </div>
							  
							  
							  <div class='modal-body'>
								<p>Fill The Form</p>
								
								<form action='PA.php' method='post'>
									  <div class='line'></div>
									  
									  
									  <div class='form-group'>
										<label for='publsiher-name' class='col-form-label'>Publisher Name:</label>
										<select class='mdb-select md-form form-control' id='publsiher-name' name='publisherID'  required='required'>
										<option value='' selected disabled></option>";
										
										foreach ($publishers as $publisher ){
											
											
											echo "<option value='".$publisher['idInSource']."'>".$publisher['value']."</option>";
											
										}
										
										echo "
										</select>
										
						
									  </div>
									  
									  
									  
									 
									  
									<div class='form-group  float-sm-right add-agreement' style='display: block;'>
									   <button type='button' onclick='cloneTheAgreement(1)' class='btn btn-success btn-circle btn-sm '>+</button>
									   
									   </div> 
									   
									   <div class='line'></div>
									   
									   
									   <br><br>
									   
									<div class='agreements1' >
									 <div class='card' id='agreement-class-1'>
									 
										<div class='card-body'>
										  <div class='form-group'>
												<label for='agreement1' class='col-form-label'> Agreement:</label>
											</div>
											<div class='form-group '>
										 
												<label class='form-control-label' for='AgreementType'>Agreement Type: </label>
																						
													<select class='mdb-select md-form form-control' id='agreement-type' name='text_list[]' required='required'>
													
													<option value='' selected disabled></option>
													
													<option value='R&P'>R&P</option>
													
													<option value='APC discount'>APC discount</option>
													
													<option value='Subscribe to open (S2O)'>Subscribe to open (S2O)</option>
			
												</select>
											</div>
				  
											 <div class='form-group '>
										 
												<label class='form-control-label' for='eligibleauthors'>Eligible author(s): </label>
												
												
													<select class='mdb-select md-form form-control' id='eligibleauthors' name='text_list[]' required='required'>
													
													<option value='' selected disabled></option>
													
													<option value='First corresponding author'>First corresponding author</option>
													
													<option value='Any corresponding author'>Any corresponding author</option>
													
													<option value='Any author'>Any author</option>		
												</select>
											</div>

										  <div class='form-group row'>
												<label class='col-sm form-control-label' for='startDate'>OA Publishing agreement start date</label>
												<div class='col-sm-6'>
												  <input type='date' id='start-date' name='text_list[]' class='form-control' min='$min' max='$max'  required='required'>
												</div>
											</div>
										  
										  
										  <div class='form-group row'>
											<label class='col-sm form-control-label' for='endDate'>OA Publishing agreement end date</label>
											<div class='col-sm-6'>
											  <input type='date' id='end-date' name='text_list[]'   class='form-control' min='$min' required='required'>
											</div>
										  </div>
											
											<div class='form-group '>
											<label class='form-control-label' for='notify'>Email notification</label>
											<input type='checkbox' id='notify' name='check_list[]' value='notify' class='form-control-custom col-sm-0 form-control-label' style='margin-left: 15px;'  checked>
											 </div>
											
										  </div>
									  </div>
									</div>
									  
									  
									  
									  
									  
									  <div class='modal-footer'>
									<button type='button' data-dismiss='modal' class='btn btn-secondary'>Close</button>
									<!-- <input type='reset' class='btn btn-secondary'> -->
									<input type='submit' class='btn btn-primary' style='background-color:#F18F00;border:none'>
									
									
									
								  </div>
								</form>
								
								
							  </div>
							 
									  
							  
							</div>
						  </div>
						</div>
						
				  
				  
				  
				    <!-------- AGREEMENT FORM ---->
				</div>
			  </div>
			</div>
		  </nav>
		  
		  
				 
				   
						
		  <!-- Main content -->
		  <div class='main-content' id='panel'>
			<!-- Topnav -->
			<nav class='navbar navbar-top navbar-expand navbar-dark bg-primary border-bottom'>
			
			  <div class='container-fluid'>
			  <img  src='../images/logo.png' >

			   
				<div class='collapse navbar-collapse' id='navbarSupportedContent'>
			
				  <!-- Navbar links -->
				  <ul class='navbar-nav align-items-center  ml-md-auto '>
				   <li class='nav-item '>
					  <!--  Sidenav toggler --> 
					  <div class='pr-3 sidenav-toggler sidenav-toggler-dark' data-action='sidenav-pin' data-target='#sidenav-main'> 
						<div class='sidenav-toggler-inner' > 
						  <i class='sidenav-toggler-line'></i> 
						   <i class='sidenav-toggler-line'></i> 
						  <i class='sidenav-toggler-line'></i> 
						</div> 
					   </div> 
					</li> 
					
					<li class='nav-item dropdown'>
					  
					  <div class='dropdown-menu dropdown-menu-xl  dropdown-menu-right  py-0 overflow-hidden'>
						
						<div class='list-group list-group-flush'>
						  
						
						
						</div>
						
					  </div>
					</li>
				
				  </ul>
				 
				</div>
			  </div>
			</nav>
			<!-- Header -->
			<div class='header bg-primary pb-6'>
			  <div class='container-fluid'>
				<div class='header-body'>
				  <div class='row align-items-center py-4'>
					<div class='col-lg-6 col-7'>
					  
					  <nav aria-label='breadcrumb' class='d-none d-md-inline-block ml-md-4'>
						<ol class='breadcrumb breadcrumb-links breadcrumb-dark'  style='color:white'>
						  <li class='breadcrumb-item'><i class='fas fa-home'></i></a>
						 <li class='breadcrumb-item'><a href='$actual_link'  style='color:white'>&nbsp; Transformative Agreements </a></i>";
						 
						 
						 
						 if(isset($_GET['id'])){
							
							if(!empty($_GET['id'])) {
								
								$selectedID = $_GET['id'];
							
								
								if(isset($publisherByID[$selectedID]) || isset($histPublisherIDs[$selectedID])){
									
							
									
									$publisherPageURL = "https://$_SERVER[HTTP_HOST]/irts/publisherAgreement/PA.php?id=".$selectedID;
											
									if(	isset($publisherByID[$selectedID]) )
										echo " <li class='breadcrumb-item' aria-current='page'><a href=' $publisherPageURL' style='color:white'>".$publisherByID[$selectedID]."</a></li>  ";
									 
									
							
									else 
										echo " <li class='breadcrumb-item' aria-current='page'><a href='$publisherPageURL ' style='color:white'>History / ".$histPublisherIDs[$selectedID]."</a></li> ";
									
			
								}
								
							}
						 
						 
						 } 
						 
						  
						  echo "
						</ol>
					 </nav>
					</div>
					
				  </div>
				 ";
				
				if(!isset($_GET['id'])){
				
					echo "
					  </div>
					</div>
					</div>
					<div class='col-xl-12' style='padding-top: 15px'>
					  <div class='card'>
						<div class='card-header bg-transparent'>
						  <div class='row align-items-center'>
							<div class='col'>
							  <h6 class='text-uppercase text-muted ls-1 mb-1'>Dashboard</h6>
							
							  
							</div>
						<iframe title='OADashboardPB - Transformative Agreements' width='2200' height='1000' src='https://app.powerbi.com/reportEmbed?reportId=0b6b4e44-f2c8-4535-9c35-a2143f643b6f&autoAuth=true&ctid=80e8c927-cda4-40a1-9ea0-d8e641feda34&config=eyJjbHVzdGVyVXJsIjoiaHR0cHM6Ly93YWJpLXdlc3QtZXVyb3BlLXJlZGlyZWN0LmFuYWx5c2lzLndpbmRvd3MubmV0LyJ9&pageName=ReportSectiond4248b81809e19cd61f9&filterPaneEnabled=false&navContentPaneEnabled=false' frameborder='0' allowFullScreen='true'></iframe>
						  </div>
						</div>
						<div class='card-body'>
							
							 <br><br>
						  <br><br>
						  <br><br>
						";

				} else {
					
					$selectedID = $_GET['id'];
				
					if(isset($publisherByID[$selectedID]) || isset($histPublisherIDs[$selectedID] )) {
						
						$value =  '';
						$isHistory = False;
						if(isset($publisherByID[$selectedID]))
							$value = $publisherByID[$selectedID];
						else {							
							$value = $histPublisherIDs[$selectedID];
							$isHistory = True;							
						}
						
						echo "
						  </div>
						</div>
						</div>
						<div class='col-xl-12' style='padding-top: 15px'>
						  <div class='card'>
							<div class='card-header bg-transparent'>
							  <div class='row align-items-center'>
								<div class='col'>
								  <h6 class='text-uppercase text-muted ls-1 mb-1'>Publisher Page</h6>
								  <h5 class='h3 mb-0'>".$value."</h5>
								</div>
							  </div>
							</div>
							<div class='card-body'>";
								
							$output = getPublisherAgreements($selectedID);
							echo addPublisherForm($selectedID ,$output, $isHistory);
								
							echo "
								 <br><br>
							  <br><br>
							  <br><br>
							";
					 } //elseif(isset($histPublisherIDs[$selectedID])){
						
						
						// echo "
						  // </div>
						// </div>
						// </div>
						// <div class='col-xl-12' style='padding-top: 15px'>
						  // <div class='card'>
							// <div class='card-header bg-transparent'>
							  // <div class='row align-items-center'>
								// <div class='col'>
								  // <h6 class='text-uppercase text-muted ls-1 mb-1'>Publisher Page</h6>
								  // <h5 class='h3 mb-0'>".$histPublisherIDs[$selectedID]."</h5>
								// </div>
							  // </div>
							// </div>
							// <div class='card-body'>";
								
								
								// $output = publisherPage($selectedID);
								
								// echo addPublisherForm($selectedID ,$output);
								
								
								
								
							// echo "
								 // <br><br>
							  // <br><br>
							  // <br><br>
							// ";
						
					
					// }
				
					
				}
					echo "
					
		  <!-- Argon Scripts -->
		  <!-- Core -->
		  <script src='./assets/vendor/jquery/dist/jquery.min.js'></script>
		  <script src='./assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
		  <script src='./assets/vendor/js-cookie/js.cookie.js'></script>
		  <script src='./assets/vendor/jquery.scrollbar/jquery.scrollbar.min.js'></script>
		  <script src='./assets/vendor/jquery-scroll-lock/dist/jquery-scrollLock.min.js'></script>
		  <!-- Optional JS -->
		  <script src='./assets/vendor/chart.js/dist/Chart.min.js'></script>
		  <script src='./assets/vendor/chart.js/dist/Chart.extension.js'></script>
		  <!-- Argon JS -->
		  <script src='./assets/js/argon.js' ></script>
		  
		   
		   
		  <script>
			
			var counter = 1;
			var dropdown = document.getElementsByClassName('dropdown-btn');
			var i;
			
			for (i = 0; i < dropdown.length; i++) {
			  dropdown[i].addEventListener('click', function() {
			 
			  var dropdownContent = this.nextElementSibling;
			  if (dropdownContent.style.display === 'block') {
				dropdownContent.style.display = 'none';
			  } else {
				dropdownContent.style.display = 'block';
			  }
			  });
			}
			
			function cloneTheAgreement(id) {
				
				if(id == '1') {
					
					var elmnt = document.getElementById('agreement-class-1');
					var agreements = document.getElementsByClassName('agreements1');
					agreements = agreements[agreements.length - 1 ];
					
				} else {
					
					var elmnt = document.getElementById('agreement-class-1');
					var agreements = document.getElementsByClassName('agreements2');
					agreements = agreements[agreements.length - 1 ];
				}
				var cln = elmnt.cloneNode(true);
				cln.id = 'cloned'+counter;
				
				let btn = document.createElement('button');
				btn.innerHTML = '-';
				btn.type = 'button';
				btn.id = 'cloned'+counter;
				btn.classList.add('btn');
				btn.classList.add('btn-danger');
				btn.classList.add('btn-circle');
				btn.classList.add('btn-sm');
				
				btn.onclick = function(event) {

					removeAgreement(this.id)
					
				};
			
				cln.appendChild(btn);
				agreements.appendChild(cln);
				counter++;
				
			}				
			
			function removeAgreement(id){
				
				var cln = document.getElementById(id);
				cln.remove();
			}
			
		  </script>
		</body>

		</html> ";
	}
