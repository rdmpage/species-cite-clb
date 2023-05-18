<?php

// Get PDF link from Wikidata

$doi = '';

if (isset($_GET['doi']))
{
	$doi = $_GET['doi'];
}

$callback = '';

if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}

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

$obj = new stdclass;

if ($doi != '')
{
	$json = get('https://doi.org/' . $doi, 'application/vnd.citationstyles.csl+json');
	
	if ($json != '')
	{
		$obj = json_decode($json);
	}
	
}


if ($callback != '')
{
	echo $callack . '(';
}

header("Content-type: application/json\n");

echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

if ($callback != '')
{
	echo ')';
}

?>
