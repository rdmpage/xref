<?php

// Add object to Neo4J by querying cypher view in CouchDB

require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/neo4j.php');

global $couch;

// Get statements from CouchDB

$id = 'http://dx.doi.org/10.7554/eLife.08347';
//$id = 'http://dx.doi.org/10.1016/j.pt.2015.09.006';

$id = 'http://dx.doi.org/10.11646/phytotaxa.208.1.1';
$id = 'http://dx.doi.org/10.11646/phytotaxa.227.1.9';
$id = 'http://dx.doi.org/10.1186/s40529-015-0087-5';
$id = 'http://dx.doi.org/10.11646/phytotaxa.222.2.1';
$id = 'http://dx.doi.org/10.3897/phytokeys.44.7993';
$id = 'http://www.ncbi.nlm.nih.gov/pubmed/21605690';
$id = 'http://www.ncbi.nlm.nih.gov/pubmed/21653447';
$id = 'http://www.ncbi.nlm.nih.gov/pubmed/27058864';
$id = 'http://www.ncbi.nlm.nih.gov/pubmed/21605690';
$id = 'http://www.ncbi.nlm.nih.gov/pubmed/26346718';


$id = 'http://www.worldcat.org/issn/1313-2970';
$id = 'http://dx.doi.org/10.3897/zookeys.324.5827';
$id = 'http://dx.doi.org/10.7554/eLife.08347';

//$id = 'http://www.ncbi.nlm.nih.gov/pubmed/12125878';

$id = 'http://www.ncbi.nlm.nih.gov/pubmed/24315868';


// get nodes------------------------------------------------------------------------------
$key = array($id, "node");
$url = '_design/new/_view/cypher' . '?key=' . urlencode(json_encode($key));
$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
$response_obj = json_decode($resp);

print_r($response_obj);

$obj = new stdclass;
$obj->statements = array();

foreach ($response_obj->rows as $row)
{
	echo $row->value . "\n";

	$statement = new stdclass;

	$statement->statement = $row->value;
	$statement->statement = str_replace('\&', '&', $statement->statement);
		
	$obj->statements[] = $statement;
}

$neo4j->send('POST', 'transaction/commit', json_encode($obj));

//exit();

// get edges------------------------------------------------------------------------------
$key = array($id, "relationship");
$url = '_design/new/_view/cypher' . '?key=' . urlencode(json_encode($key));
$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
$response_obj = json_decode($resp);

//print_r($response_obj);

$obj->statements = array();

foreach ($response_obj->rows as $row)
{
	echo $row->value . "\n";

	$statement = new stdclass;

	$statement->statement = $row->value;	
	$obj->statements[] = $statement;
}

print_r($obj);

$neo4j->send('POST', 'transaction/commit', json_encode($obj));








?>
