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
			// Think about how many, if any, links from this item we put in the queue
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

//----------------------------------------------------------------------------------------
// Load one item directly into database without waiting for queue
function load_url($url)
{
	// Ensure item is in the queue 
	enqueue($url);
	// simulate the result of a CouchDB query
	$item = new stdclass;
	$item->value = $url;
	// fetch the item
	fetch($item);
}


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

// Force load
$url = 'http://www.ncbi.nlm.nih.gov/nucore/359280095';
load_url($url);

/*
// Normal operation
enqueue(x);
dequeue();
*/


?>
