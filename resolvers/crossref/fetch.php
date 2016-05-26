<?php

// CrossRef API

require_once (dirname(dirname(dirname(__FILE__))) . '/utilities/lib.php');
require_once (dirname(dirname(dirname(__FILE__))) . '/utilities/nameparse.php');

require_once (dirname(dirname(__FILE__)) . '/ncbi/fetch.php');

//----------------------------------------------------------------------------------------
// Use search API
function crossref_search($citation)
{
	global $config;
	
	$result = null;
		
	$post_data = array();
	$post_data[] = $citation;
		
	$ch = curl_init(); 
	
	$url = 'http://search.crossref.org/links';
	
	curl_setopt ($ch, CURLOPT_URL, $url); 
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 

	// Set HTTP headers
	$headers = array();
	$headers[] = 'Content-type: application/json'; // we are sending JSON
	
	// Override Expect: 100-continue header (may cause problems with HTTP proxies
	// http://the-stickman.com/web-development/php-and-curl-disabling-100-continue-header/
	$headers[] = 'Expect:'; 
	curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
	
	if ($config['proxy_name'] != '')
	{
		curl_setopt($ch, CURLOPT_PROXY, $config['proxy_name'] . ':' . $config['proxy_port']);
	}

	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
	
	$response = curl_exec($ch);
	
	$obj = json_decode($response);
	if (count($obj->results) == 1)
	{
		if ($obj->results[0]->match)
		{
			$obj->results[0]->doi = str_replace('http://dx.doi.org/', '', $obj->results[0]->doi);
			$result = $obj->results[0];
		}
	}
	
	return $result;
	
}


//----------------------------------------------------------------------------------------
// CrossRef API
function get_work($doi)
{
	$data = null;
	
	$url = 'https://api.crossref.org/v1/works/http://dx.doi.org/' . $doi;
	
	$json = get($url);
	
	if ($json != '')
	{
		$obj = json_decode($json);
		if ($obj)
		{
			$data = new stdclass;
			$data->content = $obj->message;
			
			// authors
			if (isset($data->content->author))
			{
				foreach ($data->content->author as $author)
				{
					if (isset($author->ORCID))
					{
						$data->links[] = $author->ORCID;
					}
				}
			}
			
			// funders
			
			
			// augment
			$pmid = doi_to_pmid($doi);
			if ($pmid != 0)
			{
				$data->content->pmid = $pmid;
				
				// add to link to PMID so we augment this reference
				// need to think this through regarding authors and other info which may be replicated
				$data->alternative_identifiers[] = 'http://www.ncbi.nlm.nih.gov/pubmed/' . $data->content->pmid;
			}
			
			$pmc = doi_to_pmc($doi);
			if ($pmc != 0)
			{
				$data->content->pmc = 'PMC' . $pmc;
				
				// add to link to PMID so we augment this reference
				// need to think this through regarding authors and other info which may be replicated
				$data->alternative_identifiers[] = 'http://www.ncbi.nlm.nih.gov/pmc/articles/' . $data->content->pmc;
				
				$data->content->cites = pmc_cites_in_pubmed($pmc);	
				$data->content->cited_by = pmc_cited_by_pmc($pmc);
			}
			
		}
	}
	
	return $data;
}


//----------------------------------------------------------------------------------------
function crossref_fetch($doi)
{
	$data = get_work($doi);
	return $data;
}

//----------------------------------------------------------------------------------------

// can we get xref to PMID?
// can we get citations from XML?
// can we get links to sequences?

if (0)
{
	$doi = '10.1371/journal.pone.0139421'; // no links to XML
	
	$doi = '10.3897/zookeys.520.6185'; // has links to XML
	
	$doi = '10.7554/eLife.08347';
	
	$doi = '10.1038/sdata.2015.35';
	
	$doi = '10.15585/mmwr.mm6503e3';
	
	$doi = '10.3897/phytokeys.44.7993'; 
	
	//$doi = '10.1016/j.ympev.2011.05.006';
	
	//$doi = '10.3897/zookeys.446.8195';
	
	$data = crossref_fetch($doi);
	
	print_r($data);
}

	

?>