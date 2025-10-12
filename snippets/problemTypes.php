<?php
	$problemTypes = array(
			//Marked as completed, but no matching record in DSpace
			'notTransferred'=>
				array(
					'description' => 'Marked completed, but without a matching record in the repository based on DOI',
					'query' =>
						"SELECT * FROM `metadataReviewStatus` 
							WHERE `Status` IN ('completed')
							AND `Date Harvested` > '2023-07-01'
							AND `Type in IRTS` IN ('Article','Conference Paper')
							AND `Has Repository Record` LIKE 'No'
							AND `Source` NOT IN ('dspace','irts','')"
				)		
		);
?>
