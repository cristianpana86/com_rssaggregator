<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_cpanarss
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');


 
/**
 * HelloWorld Model
 *
 * @since  0.0.1
 */
class rssagregatorModelrssaggregator extends JModelItem
{
	
	/**
	 * @var array feedsList - list of feeds read from DB
	 */
		
	public $feedsList;
	public $noOfFeeds;
	public $featuredArray;
	/**
	 * @var  array author - one author per each feed source
	 */
	public $authorArray;
	/**
	 * @var  array category - one category  per each feed source
	 */
	public $categoryArray;
	
	/**
	 * @var  array show graphic yes/no condition, actual values holded are 1 and 0
	 */
	public $show_graphic;
 
	/**
	 * Get the message
         *
	 * @return  string  The message to be displayed to the user
	 */
	public function setPropr()
	{	
		try{
			// Get a db connection.
			$db = JFactory::getDbo();
			 
			// Create a new query object.
			$query = $db->getQuery(true);
			 
			
			$query->select('*')-> from($db->quoteName('#__rssaggregator_source'));
						 
			// Reset the query using our newly populated query object.
			$db->setQuery($query);
			 
			// Load the results as a list of stdClass objects (see later for more options on retrieving data).
			$this->feedsList = $db->loadColumn(1);
			
			$db->setQuery($query);
			$this->authorArray = $db->loadColumn(4);
			
			
			
			$db->setQuery($query);
			$this->noOfFeeds = $db->loadColumn(3);
			
			$db->setQuery($query);
			$this->categoryArray = $db->loadColumn(5);
			
			$db->setQuery($query);
			$this->featuredArray = $db->loadColumn(6);
			
			$db->setQuery($query);
			$this->show_graphic = $db->loadColumn(7);
			
		}catch(Exception $e){
		    return false;
		}
		
		
		if(is_null($this->feedsList) or is_null($this->noOfFeeds)){		 
		    return false;
		} else {
		    return true;
		}
	
	}
	
	/**
	 * Verify if an article with same slug exists in the database
     *
	 * @return  boolean  - true if an article with same slug exists in DB
	 */
	public function articleExists($slug)
	{
	
		try{
			// Get a db connection.
			$db = JFactory::getDbo();
			 
			// Create a new query object.
			$query = $db->getQuery(true);
			
			$query->select($db->quoteName(array('alias')));
			$query->from($db->quoteName('#__content'));
			$query->where($db->quoteName('alias') . ' = '. '"' .$slug . '"');
			
 
			// Reset the query using our newly populated query object.
			$db->setQuery($query);
 
						 
			// Load the results as a list of stdClass objects (see later for more options on retrieving data).
			$result = $db->loadObjectList();
			
			
			if(count($result)!=0) { return true; } else { return false;}
			
		}catch(Exception $e){
            return false;
		}
	
	
	}
	

}