<?php
/**
 * @version     1.0.0
 * @package     com_rssaggregator
 * @copyright   Copyright (C) 2015. All rights reserved.
 * @license     GPL 2
 * @author      Pana Cristian <cristianpana86@yahoo.com> - http://
 */

defined('_JEXEC') or die;

// Include dependancies
jimport('joomla.application.component.controller');

// Execute the task.
$controller	= JControllerLegacy::getInstance('Rssaggregator');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
