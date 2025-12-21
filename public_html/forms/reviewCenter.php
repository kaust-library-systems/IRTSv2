<?php
	header('Content-Type: text/html; charset=UTF-8');

	//This allows users to use the back button smoothly without receiving a warning from the browser
	session_cache_limiter('private_no_expire');
	header('Cache-Control: no cache');

	ini_set('display_errors', 1);

	$includeDirectory = '/var/www/irts';

	set_include_path($includeDirectory);

	//include core configuration and common function files
	include_once 'include.php';

	$pageTitle = 'Metadata Review Center';
	$pageLink = 'reviewCenter.php';

	//initialize the session.
	session_start();

	// check for authenticated user
	if(!isset($_SESSION['username'])) {
		$location = 'reviewCenter.php';

		include_once 'snippets/login.php';
	}
	else {
		include_once 'snippets/html/header.php';

		include_once 'snippets/html/startBody.php';
		
		if(!isset($_SESSION['role'])) {
			echo '<div class="text">
			Your email address ('.$_SESSION['mail'].') is not in the list of authorized processors for the new items form. If you believe you should have access to this form, please email <a href="mailto:'.IR_EMAIL.'">'.IR_EMAIL.'</a> for access.</a>.
			</div>';
		}
		elseif($_SESSION['role'] == 'ADMIN' || $_SESSION['role'] == 'AUTHORIZED_PROCESSOR')
		{
			include_once 'snippets/problemTypes.php';

			//for development - print out what was posted
			//print_r($_POST);

			include 'snippets/setFormVariables.php';

			$reviewer = $_SESSION['mail'];

			//If the user is logged in to DSpace and the CSRF token and bearer header is less than 20 minutes old, do not log them in again, the old token and header can be used
			if(isset($_SESSION['dspaceCsrfTokenTimestamp']) && isset($_SESSION['dspaceBearerHeaderTimestamp']) &&
				(time() - $_SESSION['dspaceCsrfTokenTimestamp'] < 1200) &&
				(time() - $_SESSION['dspaceBearerHeaderTimestamp'] < 1200))
			{
				//CSRF token and bearer header are still valid, no need to log in again
			}
			else
			{
				//Get initial CSRF token and set in session
				$response = dspaceGetStatus();
					
				//Log in
				$response = dspaceLogin();
			}

			$message = '';

			if(!isset($_GET['formType']))
			{
				unset($_SESSION['variables']);

				unset($idInIRTS);

				unset($action);

				$_SESSION['variables']['page'] = 0;

				// if the users is admin show the button
				if($_SESSION['role'] == 'ADMIN')
				{
					echo '<div class="btn-group" style="margin: 0px 5px 0px 100px;">
							<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							Template
							</button>
							<div class="dropdown-menu">
								<a class="dropdown-item" href="reviewCenter.php?formType=createTemplate">Create A New Template</a>';

					$templates = getValues($irts, "SELECT DISTINCT `idInSource`  FROM `metadata` WHERE `source` LIKE 'irts' AND `idInSource` LIKE 'itemType_%' AND `deleted` IS NULL", array('idInSource'));
									
					foreach ($templates as $template)
					{
						$template = str_replace('itemType_', '', $template);
						echo '<a  class="dropdown-item" href="reviewCenter.php?formType=editTemplate&itemType='.$template.'">Edit '.$template.' Template</a>';
					}

					echo '
						</div>
						</div>';
				}

				include_once 'snippets/reviewCenterLandingPage.php';
			}
			else
			{
				if(isset($_POST['action']) && $_POST['action']==='skip')
				{
					// continue to the main form
					$page++;
					
					//reset selections to include new page number
					$selections = array();
					
					foreach($_SESSION['selections'] as $selection=>$value)
					{
						$selections[] = $selection.'='.$value;
					}

					if(isset($page))
					{
						$selections[]='page='.$page;
					}

					$selections = implode('&', $selections);
					
					header("Location: reviewCenter.php?$selections");
					exit();
				}
				else
				{
					$selections = [];
					foreach($_SESSION['selections'] as $selection=>$value)
					{
						$selections[] = $selection.'='.$value;
					}

					if(isset($page))
					{
						$selections[] = 'page='.$page;
					}

					$selections = implode('&', $selections);
				}
				
				if($_GET['formType']==='approveEmbargoExtension')
				{
					include_once 'snippets/approveEmbargoExtension.php';
				}
				elseif($_GET['formType']==='checkThesisSubmission')
				{
					include_once 'snippets/checkThesisSubmission.php';
				}
				elseif($_GET['formType']==='checkDirectSubmission')
				{
					include_once 'snippets/checkDirectSubmission.php';
				}
				elseif($_GET['formType']==='checkPossibleDuplicates')
				{
					include_once 'snippets/checkPossibleDuplicates.php';
				}
				elseif($_GET['formType']==='checkReceivedFiles')
				{
					include_once 'snippets/checkReceivedFiles.php';
				}
				elseif($_GET['formType']==='checkMissingFiles')
				{
					include_once 'snippets/checkMissingFiles.php';
				}
				elseif($_GET['formType']==='addNewItem')
				{
					include_once 'snippets/addNewItem.php';
				}
				elseif($_GET['formType']==='editExistingItem')
				{
					include_once 'snippets/editExistingItem.php';
				}
				elseif($_GET['formType']==='mergeRecords')
				{
					include_once 'snippets/mergeRecords.php';
				}
				elseif($_GET['formType']==='updateEmbargo')
				{
					include_once 'snippets/updateEmbargo.php';
				}
				elseif($_GET['formType']==='uploadFile')
				{
					include_once 'snippets/reviewCenterActions/uploadFile.php';
				}
				elseif(isset($itemType)) {
					if($formType === 'variantMatching')	{
						$formHeader = $itemType.' Unmatched Variants';

						echo '<div class="container"><h3 class="text-center"><b>'.$formHeader.'</b></h3><hr></div>';

						echo '<div class="container">';

						include_once "snippets/reviewCenterActions/matchVariant.php";
					}
					elseif($formType === 'reviewStep')
					{
						$formHeader = 'Review '.$itemType.' Step';

						echo '<div class="container"><h3 class="text-center"><b>'.$formHeader.'</b></h3><hr></div>';

						echo '<div class="container">';

						include_once "snippets/reviewCenterActions/reviewStep.php";

						echo '</div>';
					}
					else
					{
						//print_r($_POST);
						if(!isset($template))
						{
							$template = prepareTemplate($itemType);
							$_SESSION['variables']['template'] = $template;
						}

						if($formType === 'processNew')
						{
							if(!isset($_GET['idInIRTS']))
							{
								$formHeader = 'New '.$itemType.' Records';
							}						
						}
						elseif($formType === 'review')
						{
							$formHeader = 'Old '.$itemType.' Records to Review for Problem Description: '.$problemTypes[$problemType]['description'];
							
							if(!isset($_GET['idInIRTS']))
							{
								//get list of items for this itemType and problemType
								$items = getValues($irts, "SELECT DISTINCT `type`.idInSource, `type`.added, `type`.rowID 
									FROM `metadata` `type` 
									LEFT JOIN metadata `problem` USING(idInSource)
										WHERE type.`source` LIKE 'irts' 
										AND type.`field` LIKE 'dc.type' 
										AND type.value LIKE '$itemType'
										AND type.deleted IS NULL
										AND problem.parentRowID IS NULL
										AND problem.field LIKE 'irts.note' 
										AND problem.value LIKE '$problemType'
										AND problem.deleted IS NULL", 
									array('idInSource', 'added')
								);
							}
						}
						elseif($formType === 'editExisting')
						{
							$formHeader = 'Editing Existing '.$itemType.' Record';
						}

						/* //Display reason for status, if available
						if(count($items)!==0)
						{
							$statusRowID = $items[$page]['rowID'];

							$reason = getValues($irts, "SELECT value FROM `metadata` WHERE `source` LIKE 'irts' AND parentRowID LIKE '$statusRowID' AND `field` LIKE 'irts.status.reason' AND deleted IS NULL", array('value'), 'singleValue');

							if(!empty($reason))
							{
								echo '<p><b>'.ucwords($formType).' reason:</b> '.$reason.'</p><hr>';
							}
						} */

						//If user is ready to process a new item
						if(!isset($_POST['action']))
						{
							unset($idInIRTS);
							unset($_SESSION['variables']);
							
							if(isset($_GET['idInIRTS']))
							{
								$idInIRTS = $_GET['idInIRTS'];
							}
							else
							{
								if($formType === 'processNew')
								{
									$items = getValues($irts, "SELECT DISTINCT idInSource, status.added, status.rowID 
										FROM `metadata` `type` 
										LEFT JOIN metadata `status` USING(idInSource)
										WHERE type.`source` LIKE 'irts' 
										AND type.`field` LIKE 'dc.type' 
										AND type.value LIKE '$itemType'
										AND status.field LIKE 'irts.status' 
										AND status.value LIKE 'inProcess' 
										AND status.deleted IS NULL
									", array('idInSource', 'added'));			
								}
								
								echo '<div class="container"><h3 class="text-center"><b>'.$formHeader.': '.count($items).'</b></h3><hr></div>';
								if(count($items)!==0)
								{
									if(isset($items[$page]['idInSource']))
									{
										$idInIRTS = $items[$page]['idInSource'];
									}
									elseif(isset($items[$page]))
									{
										$idInIRTS = $items[$page];
									}
								}
							}

							if(isset($idInIRTS))
							{
								itemToProcess($formType, $template, $idInIRTS);
							}
							else
							{
								echo '<br> -- No more records to process --';
							}
						}
						//if form submission received with action to perform
						else
						{
							$action = $_POST['action'];
							
							if($action === 'jumpToReview')
							{
								$action = 'deposit';
								$step = 'review';
							}

							echo '<div class="container">';

							include_once "snippets/reviewCenterActions/$action.php";

							echo '</div>';
						}
					}
				}		
			}
		}
		else
		{
			echo '<div class="text">
			Your email address ('.$_SESSION['mail'].') is not in the list of authorized processors for the new items form. If you believe you should have access to this form, please email <a href="mailto:'.IR_EMAIL.'">'.IR_EMAIL.'</a> for access.
			</div>';
		}
	}
	include_once 'snippets/html/footer.php';

	//For development - uncomment to see the contents of the session
	//print_r($_SESSION);
	
	//For development - uncomment to see the contents of the record
	//print_r($record);
	
	//For development - uncomment to see the contents of the template
	//print_r($template);
	
	//For development - uncomment to see the selections
	//print_r($selections);
?>
