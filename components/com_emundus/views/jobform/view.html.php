<?php

/**
 * @version     1.0.0
 * @package     com_emundus
 * @copyright   Copyright (C) 2015. Tous droits réservés.
 * @license     GNU General Public License version 2 ou version ultérieure ; Voir LICENSE.txt
 * @author      emundus <dev@emundus.fr> - http://www.emundus.fr
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View to edit
 */
class EmundusViewJobform extends JViewLegacy
{

	protected $state;
	protected $item;
	protected $form;
	protected $params;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{

		$app = JFactory::getApplication();

		$this->state = $this->get('State');
		$this->item  = $this->get('Data');

		$this->params = $app->getParams('com_emundus');
		$this->form   = $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
			throw new Exception(implode("\n", $errors));

		$this->_prepareDocument();

		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{
		$app   = JFactory::getApplication();
		$menus = $app->getMenu();
		$title = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();
		if ($menu)
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		else
			$this->params->def('page_heading', JText::_('COM_EMUNDUS_DEFAULT_PAGE_TITLE'));

		$title = $this->params->get('page_title', '');
		if (empty($title))
			$title = $app->getCfg('sitename');
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1)
			$title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2)
			$title = JText::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));

		$this->document->setTitle($title);

		if ($this->params->get('menu-meta_description'))
			$this->document->setDescription($this->params->get('menu-meta_description'));

		if ($this->params->get('menu-meta_keywords'))
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));

		if ($this->params->get('robots'))
			$this->document->setMetadata('robots', $this->params->get('robots'));
	}
}
