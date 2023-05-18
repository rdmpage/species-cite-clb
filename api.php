<?php

//----------------------------------------------------------------------------------------
function get($url, $content_type = '')
{	
	$data = null;

	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE,
	  
	  CURLOPT_SSL_VERIFYHOST=> FALSE,
	  CURLOPT_SSL_VERIFYPEER=> FALSE,
	  
	);

	if ($content_type != '')
	{
		$opts[CURLOPT_HTTPHEADER] = array(
			"Accept: " . $content_type 
		);		
	}
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
	curl_close($ch);
	
	return $data;
}

//----------------------------------------------------------------------------------------


// datasets we will search. These are datasets where we know we have bibliographic identifiers
$datasets = array(
	//2073,
	129659, // Index Fungorum with Literature
	164203, // IPNI with literature
	//127379, // AFD
	128415, // BioNames
	//223917, // ReptileDB with literature
	);	

// name to search for
$q = "Mitrula brevispora";

if (isset($_GET['q']))
{
	$q = $_GET['q'];
}

// result data structure
$doc = new stdclass;
$doc->query = $q;
$doc->results = array();

// add datasets to search
$parameters = array();
foreach ($datasets as $datasetKey)
{
	$parameters[] = 'datasetKey=' . $datasetKey;
}

// add name
$parameters[] = "q=" . urlencode($q);

$parameters[] = "content=SCIENTIFIC_NAME";
$parameters[] = "type=EXACT";

$url = 'https://api.checklistbank.org/nameusage/search';
$url .= '?' . join('&', $parameters);

$json = get($url);




$response = json_decode($json);

if ($response && !$response->empty)
{
	foreach ($response->result as $result)
	{
		if (isset($result->usage->name->publishedInId))
		{
			// we have a reference
			
			$hit = new stdclass;
			$hit->name = $result->usage->name->scientificName;
			
			$api_url = 'https://api.checklistbank.org/dataset/' 
				. $result->usage->datasetKey 
				. '/reference/' . $result->usage->name->publishedInId;
				
			$json = get($api_url);
			
			$reference_response = json_decode($json);
			
			//print_r($reference_response);
			
			if ($reference_response)
			{
				$hit->reference = new stdclass;
				
				$ok = false;
				
				if (isset($reference_response->csl))
				{
					$hit->reference->csl = $reference_response->csl;
					
					$ok = isset($hit->reference->csl->DOI);
					
					if (preg_match('/^Q\d+$/', $result->usage->name->publishedInId))
					{
						$hit->reference->csl->WIKIDATA = $result->usage->name->publishedInId;
						$ok = true;
					}
					
				}

				if (isset($reference_response->citation))
				{
					$hit->reference->citation = $reference_response->citation;
				}
				
				if (isset($reference_response->title))
				{
					$hit->reference->title = $reference_response->title;
				}				
				
				if ($ok)
				{
					$doc->results[] = $hit;
				}
			}
		}
	
	}

}

echo json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

?>
