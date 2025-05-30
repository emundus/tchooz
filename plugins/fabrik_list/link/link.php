<?php
/**
 *  Add an action button to run PHP
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.php
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 *  Add an action button to run PHP
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.php
 * @since       3.0
 */

class PlgFabrik_ListLink extends plgFabrik_List
{
	protected $buttonPrefix = 'link';

	protected $msg = null;

	protected $heading = false;

	/**
	 * Prep the button if needed
	 *
	 * @param   array  &$args  Arguments
	 *
	 * @return  bool;
	 */

	public function button(&$args)
	{
		if (is_array($args) && array_key_exists(0, $args))
		{
			$this->heading = FArrayHelper::getValue($args[0], 'heading', false);
		}
		else
		{
			$this->heading = false;
		}

		parent::button($args);

		return true;
	}

	/**
	 * Get button image
	 *
	 * @since   3.1b
	 *
	 * @return   string  image
	 */

	protected function getImageName()
	{
		$img = parent::getImageName();

		return $img;
	}

	/**
	 * Get the button label
	 *
	 * @return  string
	 */

	protected function buttonLabel()
	{
		return Text::_($this->getParams()->get('table_link_button_label', parent::buttonLabel()));
	}

	/**
	 * Build the HTML for the plug-in button
	 *
	 * @return  string
	 */
	public function button_result()
	{
		if ($this->heading)
		{
			return '&nbsp;';
		}
		else
		{
			return parent::button_result();
		}
	}

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 */

	protected function getAclParam()
	{
		return 'table_link_access';
	}

	/**
	 * Can the plug-in select list rows
	 *
	 * @return  bool
	 */

	public function canSelectRows()
	{
		return false;
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   array  $args  Array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 */

	public function onLoadJavascriptInstance($args)
	{
		parent::onLoadJavascriptInstance($args);
		$opts = $this->getElementJSOptions();
		$params = $this->getParams();
		$rawLink = Route::_($params->get('table_link_link', ''));
		$opts->link = htmlspecialchars_decode($rawLink, ENT_QUOTES);
		$opts->newTab = $params->get('table_link_new_tab', '0') === '1';
		$opts->fabrikLink = ($params->get('table_link_isfabrik', '0') === '1' && $this->getModel()->getParams()->get('list_ajax_links') == '1')
			|| $params->get('table_link_isfabrik', '0') === '2';
		$opts->windowTitle = Text::_($params->get('table_link_fabrik_window_title', ''));
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListLink($opts)";

		return true;
	}

	/**
	 * Load the AMD module class name
	 *
	 * @return string
	 */
	public function loadJavascriptClassName_result()
	{
		return 'FbListLink';
	}

}
