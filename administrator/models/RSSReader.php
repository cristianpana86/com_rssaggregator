<?php

class RSSReader
{

	/**
	*Receive the list of feeds. Reads the RSS feeds using cURL. 
	*
	*@param   array $feeds_list -read from database
	*@return  string $result contains the feed data read with cURL
	*/
    public function getContent($feedsList,$noOfFeeds,$show_graphic,$allow_links,$split_after_x)
	{   

		try{
			$feeds_array=array();
			$n=count($feedsList);
			for($i=0;$i<$n;$i++){
				//corresponding to each feed we will load the xml page downloaded via cURL. (entire feed) 
				$feeds_array[$i]['feedObj']=$this->getData($feedsList[$i]);
				$feeds_array[$i]['noOfFeeds']=$noOfFeeds[$i];
				$feeds_array[$i]['show_graphic']=$show_graphic[$i];
				$feeds_array[$i]['allow_links']=$allow_links[$i];
				$feeds_array[$i]['split_after_x']=$split_after_x[$i];
				
			}
			$output=array();
			for($i=0;$i<$n;$i++){
			   //reads $noOfFeeds posts from each $feedObj read from each feedLink in DB, 
			
			   $output[$i]=$this->parseData($feeds_array[$i]['feedObj'],$feeds_array[$i]['noOfFeeds'],$feeds_array[$i]['show_graphic'],$feeds_array[$i]['allow_links'],$feeds_array[$i]['split_after_x']);
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
			
			//if you are behind a proxy you should  insert proxy details between ''
			curl_setopt($curl, CURLOPT_PROXY, '');
			
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
	public function parseData($feedObj,$limit,$show_graphic,$allow_links,$split_after_x)
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
				//------- locally download images and split the text between intro and the rest ------
				$temp=$this->htmlParser($item['desc'],$allow_links,$split_after_x); //htmlParser(input_html_string,allow_links,split_after_x)
				
				$item['introtext']=$temp[0];
				$item['fulltext']=$temp[1];
				
				//-------------------------------------------
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
	
	/**
	* This function identifies images in HTML, download them locally and replace links to external images 
	* with links to the local copy. Also splits article into Intro and Fulltext
	*
	* @param string $htmlInput  - string containing HTML to be parsed
    * @param integer $allow_links  - if 0 delete links, if 1 allow links
	* @return array $article_array, $article_array[0] contains introtext, $article_array[1] contains fulltext
	*/
	public function htmlParser($htmlInput,$allow_links,$split_after_x)
	{	
		$split_after_x=(integer)$split_after_x;
		$editedHTML=$htmlInput;
		
	   /*
		*  download images locally
		*/
		$DOM = new DOMDocument;
		$DOM->loadHTML($editedHTML);

		//get all img
		$items = $DOM->getElementsByTagName('img');
   
		foreach($items as $item){
			$src = $item->getAttribute('src');  
			$local_src = $this->getImage($src);
			
			$item->setAttribute('src', $local_src);
					
		}
		//save changes made to $editedHTML
		$editedHTML=$DOM->saveHTML();
		
	   /*
		* if links are not allowed change them to #
		*/
		if ($allow_links===0) { 
			
			$DOM2 = new DOMDocument;
			$DOM2->loadHTML($editedHTML);

			//get all anchors and change them to #
			$items = $DOM2->getElementsByTagName('a');
	   
			foreach($items as $item){
				if($item->hasAttribute('href')) {
					$item->setAttribute('href','#');  
				}
			}
			//save changes made to $editedHTML
			$editedHTML=$DOM2->saveHTML();
			
		}
		
		
	   /*
		* when using saveHTML function is saving a complete HTML containing <DOCTYPE> <body> and closing tags </body>
		* the below lines of code remove those tags
		*/
		$start=strpos($editedHTML,'<body>')+6;
		$extract=substr($editedHTML,$start);
		
		$final=strpos($extract,'</body>');
		$extract=substr($extract,0,$final);
		
		$editedHTML=$extract;
		
	   /*
		* split the post in 2 parts if "$split_after_x" bigger than zero, one for introtext and one for fulltext columns of the #__content table
		* 
		*/
		$article_array=array();
		
		if ($split_after_x>0) {
			$introtext=$this->truncateHtml($editedHTML,$split_after_x,'', false,true);
			$fulltext= substr($editedHTML,(strlen($introtext[0])-strlen($introtext[1])));
			
			
			$article_array[0]= $introtext[0];
			$article_array[1]= $fulltext;
		} else {
			
			$article_array[0]= $editedHTML;
			$article_array[1]= null;
		
		}
		return $article_array;
		
	}
	
	/**
	* This function saves locally an image from a URL. If operation is successfully returns path to the local saved file else
	* returns false
	*
	* @param string $link  - link to image
	* @return mixed
	*/
	
	public function getImage($link)
	{
		//assume initially that downloading operation is unsuccessfully
		$flag_success=false;
		
	  
		
		
		$local_path= JPATH_BASE.DS.'administrator'.DS.'components'.DS.'com_rssaggregator'.DS.'assets'.DS.'images'.DS;
		
		//get information from path using pathinfo
		$path_parts = pathinfo($link);
		
		//if file has no extension consider default to be .gif
		if(isset($path_info['extension'])){
			$extension = '.' . substr($path_parts['extension'],0,strpos($path_parts['extension'],'?'));
		}else {
		    $extension = '.gif';
		}
		
		//name of the file without extension
		$base_name = $path_parts['filename'];
		
		//local file name will be source file name + random string in order to avoid replacing an image with same name + extension
		$local_file_name= $base_name . '_' . $this->generateRandomString(10). $extension;
		
		$complete_local_path = $local_path . $local_file_name;
		$local_link='/administrator/components/com_rssaggregator/assets/images/'.$local_file_name;
		
		// test for success
		if (copy($link, $complete_local_path)) {
			$flag_success=true;
		}
			
			
		if ($flag_success===true) {
			return $local_link;
		} else {
		   return false;
		}
	}
	
	// function to generate random string
	function generateRandomString($length = 10) 
	{
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }
	
	/**
	 * from CakePHP via https://dodona.wordpress.com/2009/04/05/how-do-i-truncate-an-html-string-without-breaking-the-html-code/
	 * truncateHtml can truncate a string up to a number of characters while preserving whole words and HTML tags
	 *
	 * @param string $text String to truncate.
	 * @param integer $length Length of returned string, including ellipsis.
	 * @param string $ending Ending to be appended to the trimmed string.
	 * @param boolean $exact If false, $text will not be cut mid-word
	 * @param boolean $considerHtml If true, HTML tags would be handled correctly
	 *
	 * @return array $return_array - containing at position 0 the actual text and at position 1 the added closing tags
	 */
	function truncateHtml($text, $length, $ending = '', $exact = false, $considerHtml = true) 
	{	
		//return array containing at position 0 the actual text and at position 1 the added closing tags
		$return_array=array();
		if ($considerHtml) {
			// if the plain text is shorter than the maximum length, return the whole text
			if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
				return $text;
			}
			// splits all html-tags to scanable lines
			preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
			$total_length = strlen($ending);
			$open_tags = array();
			$truncate = '';
			foreach ($lines as $line_matchings) {
				// if there is any html-tag in this line, handle it and add it (uncounted) to the output
				if (!empty($line_matchings[1])) {
					// if it's an "empty element" with or without xhtml-conform closing slash
					if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
						// do nothing
					// if tag is a closing tag
					} else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
						// delete tag from $open_tags list
						$pos = array_search($tag_matchings[1], $open_tags);
						if ($pos !== false) {
						unset($open_tags[$pos]);
						}
					// if tag is an opening tag
					} else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
						// add tag to the beginning of $open_tags list
						array_unshift($open_tags, strtolower($tag_matchings[1]));
					}
					// add html-tag to $truncate'd text
					$truncate .= $line_matchings[1];
				}
				// calculate the length of the plain text part of the line; handle entities as one character
				$content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
				if ($total_length+$content_length> $length) {
					// the number of characters which are left
					$left = $length - $total_length;
					$entities_length = 0;
					// search for html entities
					if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
						// calculate the real length of all entities in the legal range
						foreach ($entities[0] as $entity) {
							if ($entity[1]+1-$entities_length <= $left) {
								$left--;
								$entities_length += strlen($entity[0]);
							} else {
								// no more characters left
								break;
							}
						}
					}
					$truncate .= substr($line_matchings[2], 0, $left+$entities_length);
					// maximum lenght is reached, so get off the loop
					break;
				} else {
					$truncate .= $line_matchings[2];
					$total_length += $content_length;
				}
				// if the maximum length is reached, get off the loop
				if($total_length>= $length) {
					break;
				}
			}
		} else {
			if (strlen($text) <= $length) {
				return $text;
			} else {
				$truncate = substr($text, 0, $length - strlen($ending));
			}
		}
		// if the words shouldn't be cut in the middle...
		if (!$exact) {
			// ...search the last occurance of a space...
			$spacepos = strrpos($truncate, ' ');
			if (isset($spacepos)) {
				// ...and cut the text in this position
				$truncate = substr($truncate, 0, $spacepos);
			}
		}
		// add the defined ending to the text
		$truncate .= $ending;
		//modified by me
		$closing_tags='';
		if($considerHtml) {
			// close all unclosed html-tags
			foreach ($open_tags as $tag) {
				$closing_tags .= '</' . $tag . '>';
			}
			$truncate .=$closing_tags;
		}
		//return truncated string + the added closing tags
		$return_array[0]=$truncate;
		$return_array[1]=$closing_tags;
		return $return_array;
	}
	
}
?>