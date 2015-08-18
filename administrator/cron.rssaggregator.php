<?php
// https://docs.joomla.org/Framework_Compatibility
// Define JRequest::clean to protect our variables!
define('_JREQUEST_NO_CLEAN', 1); 
 
//basic to make J! happy
define('_JEXEC', 1); //make j! happy
define('JPATH_BASE', realpath(substr(dirname(__FILE__),0,strpos(dirname(__FILE__),'administrator'))));
define('DS', DIRECTORY_SEPARATOR);


// Load up the standard stuff for testing
require_once JPATH_BASE.DS.'includes'.DS.'defines.php';
require_once JPATH_BASE.DS.'includes'.DS.'framework.php';
// Load model class
require_once JPATH_BASE.DS.'administrator'.DS.'components'.DS.'com_rssaggregator'.DS.'models'.DS.'rssaggregator.php';
// Load RSS reader/parser class
require_once JPATH_BASE.DS.'administrator'.DS.'components'.DS.'com_rssaggregator'.DS.'models'.DS.'RSSReader.php';

//define path to tables classes
JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_rssaggregator/tables');

/*
* setting the path where the errors should be saved. As this script will be executed from cron tab no error will be directly
* shown to users
*/
$config = array(
    'text_file' => 'com_rssaggregator.log'
);

//create logger object to which entries will be added
jimport('joomla.log.logger.formattedtext');
$logger = new JLogLoggerFormattedtext($config);

// if log file older than 48 hours delete the log file. this action is made to prevent having a too large log file
$con = JFactory::getConfig();
$filename= $con->get( 'log_path' ) . DS . $config['text_file'];
if(file_exists($filename)){
    if(time() - filectime($filename) >  48* 3600) unlink($filename);
}

//instantiate rss reader/parser class
$rss=new RSSReader();

//instantiate class responsible with loading feeds list from database table #_rssagregator_source
$model=new rssagregatorModelrssaggregator();

//if reading from database was successfully parse rss feed articles
if ($model->setPropr())
{
	
	$list_of_feed_articles=$rss->getContent($model->feedsList,$model->noOfFeeds,$model->show_graphic,$model->allow_links,$model->split_after_x);
	
    if(isset($list_of_feed_articles[0]))
	{
	
		//$i variable to iterate over arrays containing authors/category/featured
		$i=0;
		//load each article returned from feeds to database in the #__content table
		foreach($list_of_feed_articles as $list_of_articles_from_one_source) 
		{
		
			//there is one author, category and featured status per each Feed source 
			$author=$model->authorArray[$i];
			$category=$model->categoryArray[$i];
			$featured = $model->featuredArray[$i];
			$i++;
			
			foreach($list_of_articles_from_one_source as $feed_article) 
			{
					//create instance of a JTable class which is connecting to #__content table
					$article = JTable::getInstance('content','RssagregatorTable',array());

					//create instance of a JTable class which is connecting to #__rssagregator_source table
					$article_featured = JTable::getInstance('featureditems','RssagregatorTable',array());
					//values to be saved in the database.   
							
					$tobind = array(
						   "title" => $feed_article['title'],
						   "alias" => JFilterOutput::stringURLUnicodeSlug($feed_article['title']),
						   "introtext" => $feed_article['introtext'],
						   "fulltext"=>$feed_article['fulltext'],
						   "state"=>'1',
						   "created"=>JFactory::getDate()->toSql(),
						   "featured"=>$featured,
						   "created_by"=>$author,
						   "language"=>'*',
						   "catid" => $category,
						   "metadata"=>'{"page_title":"","author":"","robots":""}',
						);
					
					//check if an article with same alias exists in the Database. If not than add the new article
					if(!$model->articleExists($tobind['alias']))
					{
			 
						//if new article successfully added to database echo success message
						if ($article->save($tobind)) 
						{
							
							//if the article is flagged as featured than updated the table #__content_frontpage
							if($tobind['featured']=='1'){
								//get the ID of the article just saved in the table #__content;
								$last_id= $article->get('id');
								$tobind = array( "content_id" => $last_id, );
								
								if ($article_featured->save($tobind)) {
									//if success nothing is logged;
								} else {
									//log error
									$comment=" id -- $last_id -- could not be added to the #__content_frontpage table.";
									$status=JLog::ERROR;
									// $status can be JLog::INFO, JLog::WARNING, JLog::ERROR, JLog::ALL, JLog::EMERGENCY or JLog::CRITICAL
									$entry = new JLogEntry($comment, $status);
									$logger->addEntry($entry);
									
								}		
							
							} else {
								//log error
								$comment="article with title --  {$tobind['title']} -- could not be added to the #__content table";
								$status=JLog::ERROR;
								// $status can be JLog::INFO, JLog::WARNING, JLog::ERROR, JLog::ALL, JLog::EMERGENCY or JLog::CRITICAL
								$entry = new JLogEntry($comment, $status);
								$logger->addEntry($entry);
							}
						
							unset($article);
							unset($article_featured);
					
						}
					}else {
					    $comment="an article with alias -- {$tobind['alias']}  --  already exists in the database";
		                $status=JLog::ERROR;
		                // $status can be JLog::INFO, JLog::WARNING, JLog::ERROR, JLog::ALL, JLog::EMERGENCY or JLog::CRITICAL
		                $entry = new JLogEntry($comment, $status);
		                $logger->addEntry($entry);
					
					}
			
		    }
		}
		
	} else {
		$comment="no feed information was returned. it is possible that maybe the feed link provided is wrong or unreachable";
		$status=JLog::ERROR;
		// $status can be JLog::INFO, JLog::WARNING, JLog::ERROR, JLog::ALL, JLog::EMERGENCY or JLog::CRITICAL
		$entry = new JLogEntry($comment, $status);
		$logger->addEntry($entry);
	}
} else {
	$comment="rssaggregatorModelrssaggregator::setPropr() did not return any feeds. maybe table #__rssaggregator_source is empty or cannot be read";
	$status=JLog::ERROR;
	// $status can be JLog::INFO, JLog::WARNING, JLog::ERROR, JLog::ALL, JLog::EMERGENCY or JLog::CRITICAL
	$entry = new JLogEntry($comment, $status);
	$logger->addEntry($entry);
				
}



