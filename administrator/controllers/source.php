<?php
/**
 * @version     1.0.0
 * @package     com_rssaggregator
 * @copyright   Copyright (C) 2015. All rights reserved.
 * @license     GPL 2
 * @author      Pana Cristian <cristianpana86@yahoo.com> - http://
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * Source controller class.
 */
class RssaggregatorControllerSource extends JControllerForm
{

    function __construct() {
        $this->view_list = 'sources';
        parent::__construct();
    }

}