<?php

// Get PDF link from Wikidata

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


// name to search for
$id = "Q100549178";

if (isset($_GET['id']))
{
	$id = $_GET['id'];
}

// result data structure
$doc = new stdclass;
$doc->wikidata = $id;

$url = 'https://wikicite-search.herokuapp.com/api.php?id=' . $id;

$json = get($url);


$response = json_decode($json);

if ($response)
{
	if (isset($response->link))
	{
		foreach ($response->link as $link)
		{
			if ($link->{'content-type'} == 'application/pdf')
			{
				$doc->link = $link;
			}
		}	
	}
}

echo json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

?>
