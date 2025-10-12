<?php
/*

**** This function retrieves basic metadata export templates by name

** Parameters :
	$collectionName : key of template in templates array
	
** Return:
	$template: array of labels and fields

*/

//--------------------------------------------------------------------------------------------------------------------------------------------------

function getExportTemplate($collectionName)
{
	$mapping = array(
		'Articles' => 'Articles',
		'Book Chapters' => 'Book Chapters',
		'Books' => 'Books',
		'Conference Papers' => 'Conference Papers',
		'Posters' => 'Conference Papers',
		'Presentations' => 'Conference Papers',
		'Datasets' => 'Datasets',
		'Software' => 'Datasets',	
	);

	$templates = array(
		'Articles' => array(
			'Type'=>'dc.type', 
			'Title'=>'dc.title', 
			'Authors'=>'dc.contributor.author',
			'KAUST Department'=>'dc.contributor.department',		 
			'Publication Date'=>'dc.date.issued', 		
			'Abstract'=>'dc.description.abstract',
			'Journal'=>'dc.identifier.journal',
			'Publisher'=>'dc.publisher',
			'DOI'=>'dc.identifier.doi',
			'Additional Links'=>'dc.relation.url',
			'Citation'=>'dc.identifier.citation', 
			'Link to License'=>'dc.rights.uri',
			'Related Datasets or Software'=>'dc.relation.issupplementedby',
			'Repository Record Created'=>'dc.date.accessioned'
		),
		'Book Chapters' => array(
			'Type'=>'dc.type', 
			'Title'=>'dc.title', 
			'Authors'=>'dc.contributor.author',
			'KAUST Department'=>'dc.contributor.department',		 
			'Publication Date'=>'dc.date.issued', 		
			'Abstract'=>'dc.description.abstract',
			'Publisher'=>'dc.publisher',
			'DOI'=>'dc.identifier.doi',
			'Additional Links'=>'dc.relation.url',
			'Citation'=>'dc.identifier.citation', 
			'Link to License'=>'dc.rights.uri',
			'Related Datasets or Software'=>'dc.relation.issupplementedby',
			'Repository Record Created'=>'dc.date.accessioned'
		),
		'Books' => array(
			'Type'=>'dc.type', 
			'Title'=>'dc.title', 
			'Authors'=>'dc.contributor.author',
			'Editors'=>'dc.contributor.editor',
			'KAUST Department'=>'dc.contributor.department',		 
			'Publication Date'=>'dc.date.issued', 		
			'Abstract'=>'dc.description.abstract',
			'Publisher'=>'dc.publisher',
			'DOI'=>'dc.identifier.doi',
			'ISBN'=>'dc.identifier.isbn',
			'Additional Links'=>'dc.relation.url',
			'Citation'=>'dc.identifier.citation', 
			'Link to License'=>'dc.rights.uri',
			'Repository Record Created'=>'dc.date.accessioned'
		),
		'Conference Papers' => array(
			'Type'=>'dc.type', 
			'Title'=>'dc.title', 
			'Authors'=>'dc.contributor.author',
			'KAUST Department'=>'dc.contributor.department',		 
			'Publication Date'=>'dc.date.issued', 		
			'Abstract'=>'dc.description.abstract',
			'Conference/Event Name'=>'dc.conference.name',
			'Publisher'=>'dc.publisher',
			'DOI'=>'dc.identifier.doi',
			'Additional Links'=>'dc.relation.url',
			'Citation'=>'dc.identifier.citation', 
			'Link to License'=>'dc.rights.uri',
			'Related Datasets or Software'=>'dc.relation.issupplementedby',
			'Repository Record Created'=>'dc.date.accessioned'
		),
		'Datasets' => array(
			'Type'=>'dc.type', 
			'Title'=>'dc.title', 
			'Authors'=>'dc.contributor.author',
			'KAUST Department'=>'dc.contributor.department',		 
			'Publication Date'=>'dc.date.issued', 		
			'Description/Abstract'=>'dc.description.abstract',
			'Publisher'=>'dc.publisher',
			'DOI'=>'dc.identifier.doi',
			'Additional Links'=>'dc.relation.url',
			'Citation'=>'dc.identifier.citation', 
			'Link to License'=>'dc.rights.uri',
			'Related Publication'=>'dc.relation.issupplementto',
			'Repository Record Created'=>'dc.date.accessioned'
		),
		'Default' => array(
			'Type'=>'dc.type', 
			'Title'=>'dc.title', 
			'Authors'=>'dc.contributor.author',
			'Editors'=>'dc.contributor.editor',
			'KAUST Department'=>'dc.contributor.department',		 
			'Publication Date'=>'dc.date.issued', 		
			'Description/Abstract'=>'dc.description.abstract',
			'Journal'=>'dc.identifier.journal',
			'Conference/Event Name'=>'dc.conference.name',
			'Publisher'=>'dc.publisher',
			'DOI'=>'dc.identifier.doi',
			'arXiv'=>'dc.identifier.arxivid',
			'ISBN'=>'dc.identifier.isbn',
			'Additional Links'=>'dc.relation.url',
			'Citation'=>'dc.identifier.citation', 
			'Link to License'=>'dc.rights.uri',
			'Related Publication'=>'dc.relation.issupplementto',
			'Related Datasets or Software'=>'dc.relation.issupplementedby',
			'Repository Record Created'=>'dc.date.accessioned'
		),
		'Preprints' => array(
			'Type'=>'dc.type', 
			'Title'=>'dc.title', 
			'Authors'=>'dc.contributor.author',
			'KAUST Department'=>'dc.contributor.department',		 
			'Preprint Posting Date'=>'dc.date.issued', 		
			'Abstract'=>'dc.description.abstract',
			'DOI'=>'dc.identifier.doi',
			'arXiv'=>'dc.identifier.arxivid',
			'Additional Links'=>'dc.relation.url',
			'Citation'=>'dc.identifier.citation', 
			'Link to License'=>'dc.rights.uri',
			'Related Datasets or Software'=>'dc.relation.issupplementedby',
			'Repository Record Created'=>'dc.date.accessioned'
		)
	);

	if(isset($mapping[$collectionName]))
	{
		$template = $templates[$mapping[$collectionName]];
	}
	else
	{
		$template = $templates['Default'];
	}
	
	return $template;
}