<?php
 
defined('_JEXEC') or die();
 
class RssagregatorTablecontent extends JTable
{
	


	public function __construct($db)
	{
		
		parent::__construct( '#__content', 'id', $db );
		
	}
	
	
}