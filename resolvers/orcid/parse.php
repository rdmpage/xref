<?php


//----------------------------------------------------------------------------------------
function orcid_parse($obj)
{
	$links = array();
	
	// Extract works
	
	$works = $obj->{'orcid-profile'}->{'orcid-activities'}->{'orcid-works'}->{'orcid-work'};

	foreach ($works as $work)
	{
		$reference = new stdclass;
	
		// Use put-code as bnode identifier
		$reference->id = $work->{'put-code'};
		
		$reference->title = $work->{'work-title'}->{'title'}->value;
	
		// Journal?
		if (isset($work->{'journal-title'}->value))
		{
			$reference->journal = $work->{'journal-title'}->value;
		}		
		
		// date
        $date = '';
		if (isset($work->{'publication-date'}))
		{
			if (isset($work->{'publication-date'}->{'year'}->value))
			{
				$date = $work->{'publication-date'}->{'year'}->value;
			}
			$reference->date = $date;
		}
		

		// Parse BibTex-------------------------------------------------------------------
		if (isset($work->{'work-citation'}->citation))
		{
			$bibtext = $work->{'work-citation'}->citation;
		
			if (!isset($work->{'journal-title'}->value))
			{
				if (preg_match('/journal = \{(?<journal>.*)\}/Uu', $bibtext, $m))
				{
					$reference->journal = $m['journal'];
				}
			}
	
			if ($date == '')
			{
				if (preg_match('/year = \{(?<year>[0-9]{4})\}/', $bibtext, $m))
				{
					$reference->date = $m['year'];
				}
			}
			
			if (preg_match('/volume = \{(?<volume>.*)\}/Uu', $bibtext, $m))
			{
				$reference->volume = $m['volume'];
			}

			if (preg_match('/number = \{(?<issue>.*)\}/Uu', $bibtext, $m))
			{
				$reference->issue = $m['issue'];
			}

			// pages = {41-68}
			if (preg_match('/pages = \{(?<pages>.*)\}/Uu', $bibtext, $m))
			{
				$pages = $m['pages'];
				if (preg_match('/(?<spage>\d+)-[-]?(?<epage>\d+)/', $pages, $mm))
				{
					$reference->pageStart = $mm['spage'];
					$reference->pageEnd = $mm['epage'];
				}
				else
				{	
					$reference->pages = $pages;
				}
			}
		}
		
		// Identifiers
		if (isset($work->{'work-external-identifiers'}))
		{
			foreach ($work->{'work-external-identifiers'}->{'work-external-identifier'} as $identifier)
			{
				switch ($identifier->{'work-external-identifier-type'})
				{
					case 'DOI':
						$value = $identifier->{'work-external-identifier-id'}->value;
						// clean
						$value = preg_replace('/^doi:/', '', $value);
						$value = preg_replace('/\.$/', '', $value);
					
						// DOI
						$reference->doi = $value;
						break;
						
					case 'ISBN':
						$value = $identifier->{'work-external-identifier-id'}->value;
						
						if ($work_type == 'BOOK')
						{
							$reference->isbn = $value;
						}												
						break;
					
					case 'ISSN':
						$value = $identifier->{'work-external-identifier-id'}->value;
						$parts = explode(";", $value);
					
						$reference->issn = $parts[0]; // just use one
						
						/*
						foreach ($parts as $issn)
						{					
							$reference->{'http://purl.org/dc/terms/isPartOf'}[] = 'http://www.worldcat.org/issn/' . $issn;
						}
						*/
						break;

					case 'PMID':
						$value = $identifier->{'work-external-identifier-id'}->value;
						$reference->pmid = $value;
						break;
					
					default:
						break;
				}
			}
		}
	
		// URL
		if (isset($work->{'url'}))
		{
			if (isset($work->{'url'}->{'value'}))
			{
				$urls = explode(",", $work->{'url'}->{'value'});
				$reference->url = $urls[0];
			}
		}
		
		if (isset($reference->doi))
		{
			$links[] = $reference->doi;
		}
	
	}
	
	print_r($links);	
	
}



$json = file_get_contents('0000-0001-7698-3945.json');

// get links
$obj = json_decode($json);

orcid_parse($obj);

?>




