<?php

// NCBI API that may be used in several places

require_once (dirname(dirname(dirname(__FILE__))) . '/utilities/lib.php');
require_once (dirname(dirname(dirname(__FILE__))) . '/utilities/nameparse.php');

//----------------------------------------------------------------------------------------
// Convert NCBI style date (e.g., "07-OCT-2015") to array
function parse_ncbi_date($date_string)
{
	$date_array = array();
	
	if (false != strtotime($date_string))
	{
		// format without leading zeros
		$ymd = date("Y-n-j", strtotime($date_string));
		
		$date_array = explode('-', $ymd);		
	}	
	
	return $date_array;
}

//----------------------------------------------------------------------------------------
function doi_to_pmid($doi)
{
	$pmid = 0;
	$url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pubmed&term=' . urlencode($doi . '[DOI]');
	
	//echo $url . "\n";
	
	$xml = get($url);
	
	//echo $xml;
	
	// Did we get a hit?
	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	$xpath_query = '//eSearchResult/Count';
	$nodeCollection = $xpath->query ($xpath_query);
	foreach($nodeCollection as $node)
	{
		$count = $node->firstChild->nodeValue;
	}
	
	if ($count == 1)
	{
		$xpath_query = '//eSearchResult/IdList/Id';
		$nodeCollection = $xpath->query ($xpath_query);
		foreach($nodeCollection as $node)
		{
			$pmid = $node->firstChild->nodeValue;
		}
	}
	
	return $pmid;
}

//----------------------------------------------------------------------------------------
function doi_to_pmc($doi)
{
	$pmc = 0;
	$url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pmc&term=' . urlencode($doi . '[DOI]');
	
	//echo $url . "\n";
	
	$xml = get($url);
	
	//echo $xml;
	
	// Did we get a hit?
	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	$xpath_query = '//eSearchResult/Count';
	$nodeCollection = $xpath->query ($xpath_query);
	foreach($nodeCollection as $node)
	{
		$count = $node->firstChild->nodeValue;
	}
	
	if ($count == 1)
	{
		$xpath_query = '//eSearchResult/IdList/Id';
		$nodeCollection = $xpath->query ($xpath_query);
		foreach($nodeCollection as $node)
		{
			$pmc = $node->firstChild->nodeValue;
		}
	}
	
	return $pmc;
}


//----------------------------------------------------------------------------------------
// Given a PMID return articles in PMC that cite this article (PMC articles are identiifed by pmid)
function pmid_cite($pmid)
{
	$list = array();

	$url = 	'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/elink.fcgi?db=pubmed&dbfrom=pubmed&id=' . $pmid . '&retmode=xml';
	$xml = get($url);
	
	$dom = new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	// pubmed_pubmed_citedin
	$nodeCollection = $xpath->query ('//LinkSetDb/LinkName[text()="pubmed_pubmed_citedin"]');
	foreach ($nodeCollection as $node)
	{	
		$nc = $xpath->query ('../Link/Id', $node);
		foreach ($nc as $n)
		{	
			$list[] = 'http://www.ncbi.nlm.nih.gov/pubmed/' . $n->firstChild->nodeValue;
		}
	}
	
	return $list;
}


// cites

//----------------------------------------------------------------------------------------
// Given a PMC return PMIDs of articles that this article cites
function pmc_cites_in_pubmed($pmc)
{
	$list = array();
	
	$pmc = str_replace('PMC', '', $pmc);

	// PMIDs cited db=pubmed&dbfrom=pmc&id=4536039&retmode=xml
	$url = 	'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/elink.fcgi?db=pubmed&dbfrom=pmc&id=' . $pmc . '&retmode=xml';
	$xml = get($url);
	
	$dom = new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	// second //LinkSetDb has citations
	$nodeCollection = $xpath->query ('//LinkSetDb[2]/Link/Id');
	foreach ($nodeCollection as $node)
	{	
		$list[] = 'http://www.ncbi.nlm.nih.gov/pubmed/' . $node->firstChild->nodeValue;
	}
	
	return $list;
}



// citedby

//----------------------------------------------------------------------------------------
// Given a PMC return PMC's of articles that cite this article
function pmc_cited_by_pmc($pmc)
{
	$list = array();
	
	$pmc = str_replace('PMC', '', $pmc);

	// Cited by in PMC
	$url = 	'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/elink.fcgi?db=pmc&dbfrom=pmc&id=' . $pmc . '&retmode=xml';
	$xml = get($url);
	
	$dom = new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	$nodeCollection = $xpath->query ('//LinkSetDb/Link/Id');
	foreach ($nodeCollection as $node)
	{	
		$list[] = 'http://www.ncbi.nlm.nih.gov/pmc/articles/PMC' . $node->firstChild->nodeValue;
	}
	
	return $list;
	
}


//----------------------------------------------------------------------------------------
// Given PMIDs return list of linked nucleotides
function pubmed_to_nucleotides($pmid)
{
	$list = array();

	$url = 	'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/elink.fcgi?db=nucleotide&dbfrom=pubmed&id=' . $pmid . '&retmode=xml';
	$xml = get($url);
	
	$dom = new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	// second //LinkSetDb has citations
	$nodeCollection = $xpath->query ('//LinkSetDb/Link/Id');
	foreach ($nodeCollection as $node)
	{	
		$list[] = 'http://www.ncbi.nlm.nih.gov/nuccore/' . $node->firstChild->nodeValue;
	}
	return $list;
}


// linkouts


// test
if (0)
{

	$pmid = 21653447;
	$list = pmid_cite($pmid);
	
	
	print_r($list);
}
?>