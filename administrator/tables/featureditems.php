<?php
 
defined('_JEXEC') or die();
 
class RssagregatorTablefeatureditems extends JTable
{
	public function __construct($db)
	{
		parent::__construct( '#__content_frontpage', 'id', $db );
	}
}