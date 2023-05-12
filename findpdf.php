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
function literal_value_simple($obj)
{
	$result = '';
	
	foreach ($obj as $k => $v)
	{
		if ($v->rank == 'normal')
		{
			$result = $v->mainsnak->datavalue->value;
		}
	}

	return $result;

}

//----------------------------------------------------------------------------------------

$hit_servers = true;

$doi = '';
$wikidata = '';

if (isset($_GET['wikidata']))
{
	$wikidata = $_GET['wikidata'];
}

if (isset($_GET['doi']))
{
	$doi = $_GET['doi'];
}

$pdf_urls = array();

if ($wikidata != '')
{
	$json = get('https://www.wikidata.org/w/api.php?action=wbgetentities&ids=' . $wikidata . '&format=json');

	$wd = json_decode($json);
	
	if ($wd)
	{
	
		if (!isset($wd->entities->{$wikidata}->claims))
		{
			return null;
		}

		foreach ($wd->entities->{$wikidata}->claims as $k => $claim)
		{
			switch ($k)
			{	
				// BioStor
				case 'P5315':
					$value = literal_value_simple($claim);
				
					if ($value != '')
					{
						if ($hit_servers)
						{
							$ia_id = 'biostor-' . $value;
				
							// could use simple rule but I don't have all of BioStor in IA yet
							$ia_url = 'https://archive.org/metadata/' . $ia_id;
				
							$ia_json = get($ia_url);
				
							$ia_obj = json_decode($ia_json);
							if ($ia_obj)
							{
								$pdf_name = '';
								if (isset($ia_obj->files))
								{
									foreach ($ia_obj->files as $file)
									{
										if ($file->format == 'Text PDF')
										{
											$pdf_urls[] = 'https://archive.org/download/' . $ia_id . '/' . $file->name;
										}						
									}
								}
							}
						}					
				
						$pdf[] = 'https://archive.org/download/biostor-' . $value;
					}			
					break;
		
				// PDF			
				case 'P724': // Internet Archive
					$value = literal_value_simple($claim);
				
					if ($value != '')
					{
						// We can't always rely on simple rules as some archives (e.g. PubMed Central)
						// have their own rules for files
					
						if ($hit_servers)
						{
							if (preg_match('/pubmed-PMC/', $value))
							{
								$ia_url = 'https://archive.org/metadata/' . $value;
					
								$ia_json = get($ia_url);
					
								$ia_obj = json_decode($ia_json);
								if ($ia_obj)
								{
									$pdf_name = '';
									foreach ($ia_obj->files as $file)
									{
										if ($file->format == 'Text PDF')
										{
											$pdf_urls[] = 'https://archive.org/download/' . $value . '/' . $file->name;
										}						
									}
								}
							}
							else
							{
								$pdf_urls[] = 'https://archive.org/download/' . $value . '/' . $value . '.pdf';
							}
						}
					}
					break;
				
				
				case 'P953': // fulltext 
					foreach ($claim as $c)
					{
						$link = new stdclass;
					
						if (isset($c->qualifiers))
						{
							// PDF?
							if (isset($c->qualifiers->{'P2701'}))
							{
								if ($c->qualifiers->{'P2701'}[0]->datavalue->value->id == 'Q42332')
								{
									// Archived?
									if (isset($c->qualifiers->{'P1065'}))
									{
										$url = $c->qualifiers->{'P1065'}[0]->datavalue->value;
										// direct link to PDF
										$url = str_replace("/http", "if_/http", $url);
							
										$pdf_urls[] = $url;
									}	
								}
							}					
						}
					}		
					break;
			
	
				default:
					break;
			}
		}
	}
}

if ($doi != '' && count($pdf_urls) == 0)
{
	$url = "https://api.oadoi.org/v2/" . urlencode($doi) . "?email=rdmpage@gmail.com";
	$json = get($url);
	
	//echo $json;
	
	$obj = json_decode($json);
	
	
	if ($obj)
	{
		if ($obj->is_oa)
		{
			if (isset($obj->best_oa_location))
			{
				if isset($obj->best_oa_location->url_for_pdf)
				{
					$pdf_urls[] = $obj->best_oa_location->url_for_pdf;
				}
			}
		}
	
	}

}

echo json_encode($pdf_urls);


?>
