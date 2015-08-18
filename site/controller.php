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

jimport('joomla.application.component.controller');

class RssaggregatorController extends JControllerLegacy {

    /**
     * Method to display a view.
     *
     * @param	boolean			$cachable	If true, the view output will be cached
     * @param	array			$urlparams	An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return	JController		This object to support chaining.
     * @since	1.5
     */
    public function display($cachable = false, $urlparams = false) {
        require_once JPATH_COMPONENT . '/helpers/rssaggregator.php';

        $view = JFactory::getApplication()->input->getCmd('view', 'sources');
        JFactory::getApplication()->input->set('view', $view);

        parent::display($cachable, $urlparams);

        return $this;
    }

}
