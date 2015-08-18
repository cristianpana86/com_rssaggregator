<?php

class RSSReader
{

	/**
	*Receive the list of feeds. Reads the RSS feeds using cURL. Randomize the array containing the output while parsing it.
	*
	*@param   array $feeds_list -read from database
	*@return  string $result contains the feed data read with cURL
	*/
    public function getContent($feedsList,$noOfFeeds,$show_graphic)
	{   
		try{
			$feeds_array=array();
			$n=count($feedsList);
			for($i=0;$i<$n;$i++){
				//corresponding to each feed we will load the xml page downloaded via cURL. (entire feed) 
				$feeds_array[$i]['feedObj']=$this->getData($feedsList[$i]);
				$feeds_array[$i]['noOfFeeds']=$noOfFeeds[$i];
				$feeds_array[$i]['show_graphic']=$show_graphic[$i];
				
			}
			$output=array();
			for($i=0;$i<$n;$i++){
			   //reads $noOfFeeds posts from each $feedObj read from each feedLink in DB, 
			   $output[$i]=$this->parseData($feeds_array[$i]['feedObj'],$feeds_array[$i]['noOfFeeds'],$feeds_array[$i]['show_graphic']);
			}
		
		    return $output;
		
		}catch (Exception $e){
		    return null;
		}
	
	}
  
	/**
	*
	*@param   string  $rss_link
	*@return  $string $result contains the feed data read with cURL
	*/
	public function getData($rss_link)
	{
		$result=null;
		$curl = curl_init();
		
		try{
			curl_setopt ($curl, CURLOPT_URL, "$rss_link");
			
			//add proxy details if behind proxy
			
			
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

			if(curl_exec($curl) === false)
			{
				return $result;
			}
			else
			{
				$result = curl_exec ($curl);
			}
			
		} catch (Exception $e) {
		    return $result;
		}
		
		curl_close ($curl);
		
		return $result;
    }
	/**
	*
	*@param   object $feedObj - xml downloaded with cURL containing the feed.
	*@param   integer $limit  - number of article to be returned from feed
	*@return  array $feed - 
	*/
	public function parseData($feedObj,$limit,$show_graphic)
	{
		if(isset($feedObj) and (isset($limit)))
		{
			$rss = new DOMDocument();
			$rss->loadXML($feedObj);
			
			$feed = array();
			$x=1;
			foreach ($rss->getElementsByTagName('item') as $node) {
				$item = array ( 
					'title' => iconv("UTF-8","ISO-8859-1//IGNORE",$node->getElementsByTagName('title')->item(0)->nodeValue),
				    'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
				    'desc'=>iconv("UTF-8","ISO-8859-1//IGNORE",$node->getElementsByTagName('description')->item(0)->nodeValue),
				);
				/*
				* area of code handling  different modes in which images are declared in feeds 
				*/
				if ($node->getElementsByTagName('enclosure')->length !== 0) {
				    $item['enclosure']= $node->getElementsByTagName('enclosure')->item(0)->getAttribute('url');
				}
				if ($node->getElementsByTagName('image')->length !== 0) {
				    $item['enclosure']= $node->getElementsByTagName('image')->item(0)->getAttribute('url');
				}
				
				//get url attribute of a picture from tag media:thumbnail
				if ($node->getElementsByTagNameNS('http://search.yahoo.com/mrss/', 'thumbnail')->length !== 0) {
					
				   $item['enclosure']= $node->getElementsByTagNameNS('http://search.yahoo.com/mrss/', 'thumbnail')->item(0)->getAttribute('url');
				}
				//
				//-------------------------- some extra logic to add images to article content -------------------
				
				if($show_graphic==1)
				{
					$description ="";
					if(isset($item['enclosure'])) { 
				
						$enclosure = $item['enclosure']; 
						$description = '<img src='. $enclosure. ' align="left">' . $item['desc'];
					
					}else if (isset($item['content']) and isset($item['content_type']) and isset($item['content_medium']) ){
						if(($item['content_medium']=='video/mp4')or ($item['content_medium']=='video/ogg') or ($item['content_medium']=='video/webm')){
							$description = '<video controls><source src='. $item['content']. ' '.' type='. $item['content_type'].'></video>' . $item['desc'];
						}else if ($item['content_medium']=='image'){
							$enclosure = $item['content']; 
							$description = '<img src='. $enclosure. ' align="left">' . $item['desc'];
						}
					}else if((isset($item['content'])) and (!isset($item['content_type'])) and (!isset($item['content_medium']))){
						$enclosure = $item['content']; 
						$description = '<img src='. $enclosure. ' align="left">' . $item['desc'];
					
					}else{
					
					   $description =  $item['desc'];
					}
					$item['desc']=$description;
				}
				//--------------------------------- end of logic to add images to article content ---------------------------
			
				array_push($feed, $item);
				if($x==$limit) { //retrieve only $limit number of posts from feed
					break;
				} else {
					$x++;
				}
				
		    }
			
			return $feed;
		}else {
		    return null;
		}
		
	
		
		
	}
}
?>