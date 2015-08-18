<?php

/**
 * @version     1.0.0
 * @package     com_rssaggregator
 * @copyright   Copyright (C) 2015. All rights reserved.
 * @license     GPL 2
 * @author      Pana Cristian <cristianpana86@yahoo.com> - http://
 */
defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Rssaggregator records.
 */
class RssaggregatorModelSources extends JModelList {

    /**
     * Constructor.
     *
     * @param    array    An optional associative array of configuration settings.
     * @see        JController
     * @since    1.6
     */
    public function __construct($config = array()) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                                'id', 'a.id',
                'rss_link', 'a.rss_link',
                'rss_name', 'a.rss_name',
                'no_of_posts', 'a.no_of_posts',
                'author', 'a.author',
                'category', 'a.category',
                'featured', 'a.featured',
                'show_graphic', 'a.show_graphic',
                'allow_links', 'a.allow_links',
                'split_after_x', 'a.split_after_x',

            );
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     */
    protected function populateState($ordering = null, $direction = null) {
        // Initialise variables.
        $app = JFactory::getApplication('administrator');

        // Load the filter state.
        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
        $this->setState('filter.state', $published);

        

        // Load the parameters.
        $params = JComponentHelper::getParams('com_rssaggregator');
        $this->setState('params', $params);

        // List state information.
        parent::populateState('a.rss_name', 'asc');
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param	string		$id	A prefix for the store id.
     * @return	string		A store id.
     * @since	1.6
     */
    protected function getStoreId($id = '') {
        // Compile the store id.
        $id.= ':' . $this->getState('filter.search');
        $id.= ':' . $this->getState('filter.state');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return	JDatabaseQuery
     * @since	1.6
     */
    protected function getListQuery() {
        // Create a new query object.
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
                $this->getState(
                        'list.select', 'DISTINCT a.*'
                )
        );
        $query->from('`#__rssaggregator_source` AS a');

        
		// Join over the user field 'author'
		$query->select('author.name AS author');
		$query->join('LEFT', '#__users AS author ON author.id = a.author');
		// Join over the foreign key 'category'
		$query->select('#__categories_1990366.title AS categories_title_1990366');
		$query->join('LEFT', '#__categories AS #__categories_1990366 ON #__categories_1990366.id = a.category');

        

        // Filter by search in title
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr($search, 3));
            } else {
                $search = $db->Quote('%' . $db->escape($search, true) . '%');
                $query->where('( a.rss_name LIKE '.$search.'  OR  a.category LIKE '.$search.' )');
            }
        }

        


        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering');
        $orderDirn = $this->state->get('list.direction');
        if ($orderCol && $orderDirn) {
            $query->order($db->escape($orderCol . ' ' . $orderDirn));
        }

        return $query;
    }

    public function getItems() {
        $items = parent::getItems();
        
		foreach ($items as $oneItem) {

			if (isset($oneItem->category)) {
				$values = explode(',', $oneItem->category);

				$textValue = array();
				foreach ($values as $value){
					$db = JFactory::getDbo();
					$query = $db->getQuery(true);
					$query
							->select($db->quoteName('title'))
							->from('`#__categories`')
							->where($db->quoteName('id') . ' = '. $db->quote($db->escape($value)));
					$db->setQuery($query);
					$results = $db->loadObject();
					if ($results) {
						$textValue[] = $results->title;
					}
				}

			$oneItem->category = !empty($textValue) ? implode(', ', $textValue) : $oneItem->category;

			}
					$oneItem->featured = JText::_('COM_RSSAGGREGATOR_SOURCES_FEATURED_OPTION_' . strtoupper($oneItem->featured));
					$oneItem->show_graphic = JText::_('COM_RSSAGGREGATOR_SOURCES_SHOW_GRAPHIC_OPTION_' . strtoupper($oneItem->show_graphic));
					$oneItem->allow_links = JText::_('COM_RSSAGGREGATOR_SOURCES_ALLOW_LINKS_OPTION_' . strtoupper($oneItem->allow_links));
		}
        return $items;
    }

}
