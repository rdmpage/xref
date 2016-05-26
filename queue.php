<?php

// Manage a queue of objects

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/resolve.php');


//----------------------------------------------------------------------------------------
// Put an item in the queue 
function enqueue($url)
{
	global $config;
	global $couch;
	
	// Check whether this URL already exists (have we done this object already?)
	// to do: what about having multiple URLs for same thing, check this
	$exists = $couch->exists($url);

	if (!$exists)
	{
		$doc = new stdclass;
		$doc->_id = $url;	
		$doc->type = 'url';
		$doc->url = $url;
		$doc->timestamp = date("c", time());
		$doc->modified = $doc->timestamp;
		
		$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . urlencode($doc->_id), json_encode($doc));
		var_dump($resp);
	}
	else
	{
		echo "Exists\n";
	}
}

//----------------------------------------------------------------------------------------
function queue_is_empty()
{
}

//----------------------------------------------------------------------------------------
function fetch($item)
{
	global $config;
	global $couch;
	
	// log
	echo "Resolving " . $item->value . "\n";
	//exit();
	$data = resolve_url($item->value);
	
	print_r($data);
	
	if ($data)
	{
		// if we have content, update object with content, which will remove it from the queue
		if (isset($data->content))
		{
			/*
			// add any links in this object to the queue
			if (isset($data->links))
			{
				foreach ($data->links as $link)
				{
					// log
					echo "Adding " . $link . " to queue\n";

					enqueue($link);
				}
			}
			
			// add citation links to queue
			if (isset($data->content->cites))
			{
				foreach ($data->content->cites as $link)
				{
					echo "Adding " . $link . " to queue [cites]\n";
					enqueue($link);
				}
			}
			if (isset($data->content->cited_by))
			{
				foreach ($data->content->cited_by as $link)
				{
					echo "Adding " . $link . " to queue [cited_by]\n";
					enqueue($link);
				}
			}
			*/
			
			// update item with content
			$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($item->value));
			var_dump($resp);
			if ($resp)
			{
				$doc = json_decode($resp);
				if (!isset($doc->error))
				{
					$doc->modified = $doc->timestamp;
					$doc->content = $data->content;
					$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . urlencode($doc->_id), json_encode($doc));
					var_dump($resp);
				}
			}	
		}		
	}
	
	
	
	
	// push item to Neo4J (or do we let the changes API handle this?)
	
}

//----------------------------------------------------------------------------------------
// to do: if we get just one object, and that fails, we may end up with a queue that is 
// forever stuck, so maybe get a bunch of items, and resolve those.
function dequeue($n = 5, $descending = false)
{
	global $config;
	global $couch;
	
	$url = '_design/queue/_view/todo?limit=' . $n;
	
	if ($descending)
	{
		$url .= "&descending=true";
	}
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
	$response_obj = json_decode($resp);

	print_r($response_obj);
		
	// fetch content
	foreach ($response_obj->rows as $row)
	{
		fetch($row);
	}
		
}

//enqueue ('http://dx.doi.org/10.1080/00222934908526725');
//enqueue('http://dx.doi.org/10.3897/BDJ.4.e7386');

// eLife article
//enqueue('http://dx.doi.org/10.7554/eLife.08347');

// eLife article author
//enqueue('http://orcid.org/0000-0001-8916-5570');

// Journal with ISSN
//enqueue('http://www.worldcat.org/issn/0075-5036');

// Phytotaxa 2015
//enqueue('http://dx.doi.org/10.11646/phytotaxa.208.1.1');
//enqueue('http://dx.doi.org/10.11646/phytotaxa.227.1.9');
//enqueue('http://dx.doi.org/10.1186/s40529-015-0087-5');
//enqueue('http://dx.doi.org/10.11646/phytotaxa.222.2.1');

//enqueue('http://dx.doi.org/10.3897/phytokeys.44.7993');
//enqueue('http://www.ncbi.nlm.nih.gov/pubmed/21605690');
//enqueue('http://www.worldcat.org/issn/1313-2970');

enqueue('http://dx.doi.org/10.3897/zookeys.324.5827');

enqueue('http://www.ncbi.nlm.nih.gov/pubmed/12125878');

// force fetch

$item = new stdclass;
$item->value = 'http://www.ncbi.nlm.nih.gov/pubmed/21653447';

$item->value = 'http://www.ncbi.nlm.nih.gov/pubmed/27058864';

$item->value = 'http://www.ncbi.nlm.nih.gov/pubmed/21605690';
$item->value = 'http://www.ncbi.nlm.nih.gov/pubmed/26346718';

$item->value = 'http://www.worldcat.org/issn/1313-2970'; // ZooKeys online

$item->value = 'http://dx.doi.org/10.3897/zookeys.324.5827';

$item->value = 'http://dx.doi.org/10.7554/eLife.08347';
$item->value = 'http://www.ncbi.nlm.nih.gov/pubmed/12125878';


enqueue('http://www.ncbi.nlm.nih.gov/pubmed/24315868');
$item->value = 'http://www.ncbi.nlm.nih.gov/pubmed/24315868';


fetch($item);


//dequeue(1, true);

//dequeue(5,true);

?>
