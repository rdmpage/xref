<?php

// Fetch reference from PubMed
require_once (dirname(dirname(dirname(__FILE__))) . '/utilities/lib.php');
require_once (dirname(dirname(dirname(__FILE__))) . '/utilities/nameparse.php');

require_once (dirname(dirname(__FILE__)) . '/ncbi/fetch.php');


//----------------------------------------------------------------------------------------
function pubmed_parse_xml($xml)
{
	$dom = new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	$reference = new stdclass;
	$reference->content = new stdclass;
	
	// PMID is identifier
	$nodeCollection = $xpath->query ('//PubmedArticle/MedlineCitation/PMID');
	foreach ($nodeCollection as $node)
	{		
		$reference->content->pmid = $node->firstChild->nodeValue;
	}

	// title
	$nodeCollection = $xpath->query ('//ArticleTitle');
	foreach ($nodeCollection as $node)
	{	
		$reference->content->title[] = $node->firstChild->nodeValue;
	}

	// abstract
	$nodeCollection = $xpath->query ('//Abstract/AbstractText');
	foreach ($nodeCollection as $node)
	{	
		$reference->content->abstract = $node->firstChild->nodeValue;
	}
            
	// Pagination
	$nodeCollection = $xpath->query ('//Pagination/MedlinePgn');
	foreach ($nodeCollection as $node)
	{	
		$reference->content->page = $node->firstChild->nodeValue;
		
		if (preg_match('/(?<spage>\d+)-(?<epage>\d+)/', $reference->content->page, $m))
		{
			$length_spage = strlen($m['spage']);
			$length_epage = strlen($m['epage']);
			
			if ($length_spage > $length_epage)
			{
				$pageStart = $m['spage'];
				$pageEnd = substr($m['spage'], 0, ($length_spage - $length_epage)) . $m['epage'];
				$reference->content->page = $pageStart . '-' . $pageEnd;
			}
		}
		
	}

	$nodeCollection = $xpath->query ('//Journal');
	foreach ($nodeCollection as $journal_node)
	{	
		$nc = $xpath->query ('Title', $journal_node);
		foreach ($nc as $n)
		{	
			$reference->content->{'container-title'}[] = $n->firstChild->nodeValue;
		}
					
		$nc = $xpath->query ('JournalIssue/Volume', $journal_node);
		foreach ($nc as $n)
		{	
			$reference->content->volume =  $n->firstChild->nodeValue;
		}
		$nc = $xpath->query ('JournalIssue/Issue', $journal_node);
		foreach ($nc as $n)
		{	
			$reference->content->issue =  $n->firstChild->nodeValue;
		}

		$nc = $xpath->query ('ISSN[@IssnType="Print"]', $journal_node);
		foreach ($nc as $n)
		{	
			$reference->content->ISSN[] = $n->firstChild->nodeValue;
		}
		$nc = $xpath->query ('ISSN[@IssnType="Electronic"]', $journal_node);
		foreach ($nc as $n)
		{	
			$reference->content->ISSN[] = $n->firstChild->nodeValue;
		}
		
		// date
		$nc = $xpath->query ('JournalIssue/PubDate/Year', $journal_node);
		foreach ($nc as $n)
		{	
			$reference->content->issued['date-parts'][0][] = (Integer)$n->firstChild->nodeValue;
		}
		$nc = $xpath->query ('JournalIssue/PubDate/Month', $journal_node);
		foreach ($nc as $n)
		{	
			$months = array(
				'Jan'=>1,
				'Feb'=>2,
				'Mar'=>3,
				'Apr'=>4,
				'May'=>5,
				'Jun'=>6,
				'Jul'=>7,
				'Aug'=>8,
				'Sep'=>9,
				'Oct'=>10,
				'Nov'=>11,
				'Dec'=>12);
		
			$reference->content->issued['date-parts'][0][] = $months[$n->firstChild->nodeValue];
		}
		$nc = $xpath->query ('JournalIssue/PubDate/Day', $journal_node);
		foreach ($nc as $n)
		{	
			$reference->content->issued['date-parts'][0][] = (Integer)$n->firstChild->nodeValue;
		}
				
	}
	
	$reference->content->author = array();
	
	// authors
	$nodeCollection = $xpath->query ('//AuthorList/Author');
	foreach ($nodeCollection as $node)
	{	
		$author = new stdclass;

		$nc = $xpath->query ('ForeName', $node);
		foreach ($nc as $n)
		{	
			$author->given = $n->firstChild->nodeValue;
		}
		if (!isset($author->given))
		{
			$nc = $xpath->query ('Initials', $node);
			foreach ($nc as $n)
			{	
				$author->givenName = $n->firstChild->nodeValue;
			}
		}
		$nc = $xpath->query ('LastName', $node);
		foreach ($nc as $n)
		{	
			$author->family = $n->firstChild->nodeValue;
		}
		
		// Use address for affiliation as affiliation is a schema.org type that expects
		// a class, not text.
		$nc = $xpath->query ('AffiliationInfo/Affiliation', $node);
		foreach ($nc as $n)
		{	
			$author->affiliation[] = $n->firstChild->nodeValue;
		}
		
		
		$reference->content->author[] = $author;		
	}		
	
	// identifiers
	$nodeCollection = $xpath->query ('//ArticleIdList/ArticleId[@IdType = "doi"]');
	foreach ($nodeCollection as $node)
	{	
		$reference->content->DOI = $node->firstChild->nodeValue;
	}
	$nodeCollection = $xpath->query ('//ArticleIdList/ArticleId[@IdType = "pmc"]');
	foreach ($nodeCollection as $node)
	{	
		$reference->content->pmc = $node->firstChild->nodeValue;
	}
	
	// citations
	$reference->content->cited_by = pmid_cite($reference->content->pmid);
	
	if (isset($reference->content->pmc))
	{
		$reference->content->cites = pmc_cites_in_pubmed($reference->content->pmc);	
		$reference->content->cited_by = pmc_cited_by_pmc($reference->content->pmc);
	}
	
	/*
	// mesh	
	$nodeCollection = $xpath->query ('//MeshHeadingList/MeshHeading/DescriptorName/@UI');
	foreach ($nodeCollection as $node)
	{	
		$reference->content->mesh[] = $node->firstChild->nodeValue;
	}
	*/
	
	if (isset($reference->content->pmc))
	{
		cites_in_pubmed($reference->content->pmc, $reference);
		cited_by_in_pmc($reference->content->pmc, $reference);
	}
	
	$reference->content->sequences = pubmed_to_nucleotides($reference->content->pmid);
	//print_r($reference);
	
	
	//echo json_format(json_encode($reference));
	
	return $reference;

}

//----------------------------------------------------------------------------------------
function pubmed_fetch($pmid)
{
	$data = null;
	//$xml = file_get_contents('17148433.xml');
	//pubmed_parse_xml($xml);
	
	if (1)
	{
		// Eutils XML		
		$parameters = array(
			'db'		=> 'pubmed',
			'id' 		=> $pmid,
			'retmode'	=> 'xml'
		);
	
		$url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?' . http_build_query($parameters);
		
		//echo $url . "\n";
	
		$xml = get($url);
		
		//echo $xml;
		
		if ($xml != '')
		{
			$data = pubmed_parse_xml($xml);
		}
	}
	
	return $data;

}
	
if (0)
{	
	$pmid = 21605690;
	$pmid = 21653447;
	$pmid = 24315868;
	$data = pubmed_fetch($pmid);
	print_r($data);
}





?>
