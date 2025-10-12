<?php
	//Define function to update bird records
	function updateBirdCollection($report, $errors, $recordTypeCounts)
	{
		global $irts;

		$run = FALSE;

		if(isset($_GET['regenerate']))
		{
			if($_GET['regenerate']==='yes')
			{
				$run = TRUE;
			}
		}
		else
		{
			$recentEntries = getValues($irts, "SELECT idInSource FROM `metadata` 
			WHERE `source` LIKE 'repository'
			AND `field` LIKE 'dspace.community.handle'
			AND `value` LIKE '10754/631995'
			AND `added` LIKE '".TODAY."%'
			AND `deleted` IS NULL",	array('idInSource'), 'arrayOfValues');
			
			if(count($recentEntries)>0)
			{
				$run = TRUE;
			}
		}
		
		if($run)
		{
			$token = loginToDSpaceRESTAPI();
			
			$collectionID = '43675';
			
			$communityID = '24568';
			
			$collectionTitle = 'Birds';
			
			$communityTitle = 'KAUST Bird Observation Records';
			
			$introductoryText = '';
			
			$introductoryText .= '<a href="https://repository.kaust.edu.sa/handle/10754/631995"><img class="object-fit" src="https://repository.kaust.edu.sa/bitstream/handle/10754/660641/10YearsOfKAUSTBirds.png?sequence=1&isAllowed=y" alt="10 Years of KAUST Birds Image" style="max-width:100%;" /></a>';
			
			/* 
				
			$recentImages = getValues($irts, "SELECT DISTINCT c.`idInSource`, 
			l.value location, 
			d.value date, 
			o.value observer, 
			t.value title, 
			h.value handle 
			FROM `metadata` c 
				LEFT JOIN metadata l USING(idInSource) 
				LEFT JOIN metadata d USING(idInSource) 
				LEFT JOIN metadata o USING(idInSource)
				LEFT JOIN metadata t USING(idInSource)
				LEFT JOIN metadata h USING(idInSource)
				WHERE c.`source` LIKE 'dspace' 
				AND c.`field` LIKE 'dc.type'
				AND c.`value` LIKE 'Image'
				AND c.`deleted` IS NULL 

				AND l.`field` LIKE 'dwc.location.locality' 
				AND l.`deleted` IS NULL 

				AND d.field LIKE 'dc.date.issued'
				AND d.value LIKE '%-%'
				AND d.`deleted` IS NULL 

				AND o.`field` LIKE 'dc.contributor.photographer' 
				AND o.`deleted` IS NULL
				
				AND t.`field` LIKE 'dc.title' 
				AND t.`deleted` IS NULL
				
				AND h.`field` LIKE 'dc.identifier.uri' 
				AND h.`deleted` IS NULL

				ORDER BY date DESC", 
			array('idInSource','location','date','observer','title','handle'), 
			'arrayOfValues');
			
			$recentImages = array_slice($recentImages, 0, 5);
			
			foreach($recentImages as $recentImage)
			{
				$bitstreams = getBitstreamListForItemFromDSpaceRESTAPI($recentImage['idInSource'], $token);

				if(is_string($bitstreams))
				{
					$bitstreams = json_decode($bitstreams, TRUE);

					$bitstreamURL = '';

					foreach($bitstreams as $bitstream)
					{
						if($bitstream['bundleName'] === 'THUMBNAIL')
						{
							$bitstreamURL = '/bitstream/id/'.$bitstream['id'].'/'.$bitstream['name'];
						}
					}
				}
				if(!empty($bitstreamURL))
				{
					$introductoryText .= '<a href="'.$recentImage['handle'].'"><img class="object-fit" title="'.$recentImage['title'].' (by '.$recentImage['observer'].')" src="'.$bitstreamURL.'" alt="Thumbnail" height="128" width="128" /></a>';
				}
			} */
			
			$introductoryText .= '<h5><b>Welcome!</b></h5>
				<p>KAUST is home to a thriving bird population. Since KAUST opened, records of birds sighted have been made on a regular basis, and over 250 different bird species have been recorded. The <u><a href="https://hse.kaust.edu.sa/Services/Pages/Biodiversity.aspx">Health, Safety & Environment (HSE) Department</a></u> has been leading efforts across KAUST to catalog such vital records and biodiversity in general. On this site, you will find information about the birds seen, where and how often, view pictures of many of the species and find guides to the best places to explore.</p>
				<p>Most bird records currently come from four specific locations, as indicated on <u><a href="#map">the map below</a></u>, although other sites within KAUST will also hold interesting birds. Many of the birds are migrant species as KAUST lies on a major bird migratory route. Other species are resident to the area and have either visited KAUST or become established here. The aim of this site is to catalog bird records, but also to encourage new bird watchers to record their observations and so further our understanding of the avifauna of KAUST.</p>
				<p><b>The four primary locations for birding in KAUST:</b><br/><i>- Each of these is registered as a hotspot on eBird (an online database of bird observations managed by the Cornell Lab of Ornithology).</i></p>
				<ul>
				<li><u><a href="/handle/10754/644909" target="_blank" rel="noopener">Golf Course</a></u></li>

				<p>The golf course has a variety of habitats that are unusual in the region and is a favorite haunt for many species of bird. A number of areas are locally protected and managed to attract the maximum variety of biodiversity. Water birds are attracted to the lakes and a well situated bird hide looks out to some large trees where roosting herons and cormorants are usually present. The wooded areas offer a safe haven for migratory birds to rest. The variety of plants attracts insects on which the birds feed and the lakes provide water to drink. Other habitats include reed beds over one of the lakes where such rarely seen species as Corn Crakes are often encountered on passage and the fairways themselves are attractive to species collecting invertebrates from the soil.</p>
				
				<li><u><a href="/handle/10754/644912" target="_blank" rel="noopener">Island mangroves</a></u>
				<p>This circular walk passes alongside stands of mangroves, bush areas where a variety of local plants have become established and leads out to the jetty from which not only birds but fish including rays and sharks are frequently seen. The final part of the walk goes along the sea wall where passing seabirds can be viewed before going through the gardens where other species might be found. The walk is best in the mornings not just because it is cooler but also because the waters either side of the jetty is calmer, making it much easier to see into the water.</p>
				</li>
				<li><u><a href="/handle/10754/644910" target="_blank" rel="noopener">King Abdullah Monument</a></u>
				<p>The King Abdullah Monument area offers a range of habitats to the visiting birdwatcher. The sea front offers good views of the sea where gulls and terns may be observed. The gardens always hold a variety of birds and during migratory periods all sorts of species may turn up. A walk back towards KAUST is highly recommended giving great views over the mangroves were both resident birds and migrants may be seen. Larger herons including spoonbills are frequently seen in the area and Western Ospreys are often found sitting on the lamp posts feasting on a recent catch.</p>
				</li>
				<li><u><a href="/handle/10754/644908" target="_blank" rel="noopener">South Beach</a></u></li>
				
				<p>South Beach is an excellent spot to view many of the water birds found at KAUST. Different species of waders, herons and terns frequent the area, attracted by the mudflats, beach and stands of mangrove. There is a bird hide located there which provides an excellent vantage point particularly when the water level is high. The peek viewing times are in the migratory periods but many species also stay in the area during the winter months. In summer, the number of birds is much lower. An early morning visit to the site is recommended because the sun rises behind the hide and the birds are that much easier to observe.</p></ul>
				<p></p>
				<p>You can contribute your own bird observations at one of these locations via the eBird system by clicking the "Submit Data" button on the eBird hotspot page for your location. You will first have to create an account with eBird, but will then be able to enter observations that will show in eBird and will also be used to update the KAUST bird records in this collection. <b>Periodically a report is created listing information about the birds seen in KAUST, the current version can be downloaded <u><a href="https://repository.kaust.edu.sa/handle/10754/652823">here</a></u>.</b><br /> <br /> <u><a name="map"></a></u> Map of the main birding areas in KAUST:</p>
				<p><img class="object-fit" title="Map showing four main KAUST birding locations" src="/bitstream/handle/10754/644904/Map%20of%20birding%20areas.JPG" alt="Thumbnail" width="640" /></p>';
				
				$introductoryText .= '<br/><p><b>Most Recent Checklists Submitted to eBird for KAUST hotspots:</b><br/><ul>';
				
				$recentChecklists = getValues($irts, "SELECT DISTINCT c.`value` checklist, l.value location, d.value date, o.value observer FROM `metadata` c LEFT JOIN metadata l USING(idInSource) LEFT JOIN metadata d USING(idInSource) LEFT JOIN metadata o USING(idInSource)
				WHERE c.`source` LIKE 'dspace' 
				AND c.`field` LIKE 'ebird.checklist.id' 
				AND c.`deleted` IS NULL 

				AND l.`field` LIKE 'dwc.location.locality' 
				AND l.`deleted` IS NULL 

				AND d.field LIKE 'dc.date.observed'
				AND d.value LIKE '%-%'
				AND d.`deleted` IS NULL 

				AND o.`field` LIKE 'dwc.occurrence.recordedBy' 
				AND o.`deleted` IS NULL

				ORDER BY date DESC", 
				array('checklist','location','date','observer'), 
				'arrayOfValues');
				
				$recentChecklists = array_slice($recentChecklists, 0, 10);
				
				foreach($recentChecklists as $recentChecklist)
				{
					$date = date_create($recentChecklist['date']);			
					
					$introductoryText .= '<li><u><a title="Search KAUST repository records from this checklist" href="/discover?filtertype_1=dateIssued&filter_relational_operator_1=equals&filter_1='.$recentChecklist['date'].'&filtertype_2=location&filter_relational_operator_2=equals&filter_2='.$recentChecklist['location'].'" target="_blank" rel="noopener">Observations from the '.$recentChecklist['location'].' on '.date_format($date, 'F j, Y').' by '.$recentChecklist['observer'].'</a></u> - <a title="Original eBird checklist" href="https://ebird.org/view/checklist/'.$recentChecklist['checklist'].'" target="_blank" rel="noopener"><a class="object-fit" src="'.EBIRD_LOGO.'" alt="Thumbnail" width="42" /></a></li>';
				}
				
				$introductoryText .= '</ul>';
				
				$introductoryText .= '<br/>The KAUST bird records collection owes its creation to the efforts of Brian James, Biodiversity and Conservation Coordinator with the KAUST Health, Safety & Environment Department.</p>
				<div>
				<details>
				<summary><b>Brian James Biography:</b></summary>
				
				<table style="table-layout: fixed; width: 100%; margin-left: auto; margin-right: auto;">
				<tbody>
				<tr>
				<td style="vertical-align: top; width: 50%;">
				Brian James was a founding member of the KAUST Community who finally retired to the UK in 2019. He first worked as a Primary Teacher at the KAUST School and for the last two years as the Biodiversity Conservation Coordinator for the KAUST Health Safety and Environment Department.<br/><br/>
				Brian taught internationally for over 30 years in a number of different countries. This enabled him to pursue his interest in wildlife, but particularly in ornithology. Since arriving at Saudi Arabia he had explored many birdwatching destinations and kept careful records of the sightings made. When the opportunity came to combine these sightings, with the eBird recording system organised by Cornell University and the KAUST Library Repository he realised this was a unique opportunity. KAUST lies on a major migratory bird routes and the surrounding area also has unique resident avifauna. However, there are very few wildlife records from the region. By making these records available to the wider community it is hoped that the information contained will assist future efforts to protect these birds and their habitats. He also hopes to inspire future KAUST residents to record their findings and so further our understanding of the unique biodiversity found in the region.			
				</td>
				
				<td style="vertical-align: top; width: 50%;">
				<img class="object-fit" title="Profile Image for Brian James Biography" src="https://repository.kaust.edu.sa/bitstream/handle/10754/656158/BrianProfileImage.jpg" alt="Thumbnail" width="320" />
				</td>
				</tr>
				</tbody>
				</table>
				</details>
				</div>';
			
			$collection = array('id'=>$collectionID,'name'=>$collectionTitle,'introductoryText'=>$introductoryText);
			
			$collection = json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT);

			$response = putCollectionToDSpaceRESTAPI($collectionID, $collection, $token);

			$community = array('id'=>$communityID,'name'=>$communityTitle,'introductoryText'=>$introductoryText);
			
			$community = json_encode($community, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT);
			
			$response = putCommunityToDSpaceRESTAPI($communityID, $community, $token);
		}

		$summary = saveReport(__FUNCTION__, $report, $recordTypeCounts, $errors);

		return array('changedCount'=>$recordTypeCounts['all']-$recordTypeCounts['unchanged'],'summary'=>$summary);
	}
