<?php
/**
 * @version        $Id: list.php 14401 2010-01-26 14:10:00Z louis $
 * @package        Joomla.Framework
 * @subpackage     Parameter
 * @copyright      Copyright (C) 2005 - 2010 Open Source Matters. All rights reserved.
 * @license        GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

/**
 * Renders a list element
 *
 * @package           Joomla.Framework
 * @subpackage        Parameter
 * @since             1.5
 */
class JFormFieldMultilist extends JFormFieldList
{
	/**
	 * Element type
	 *
	 * @access    protected
	 * @var        string
	 */
	var $_name = 'Multilist';

	function getInput()
	{
		$name  = $this->fieldname;
		$value = $this->value;
		/*$class = ( $node->attributes('class') ? 'class="'.$node->attributes('class').'"' : 'class="inputbox"' );*/

		$options = array();
		$db      = JFactory::getDBO();
		$query   = 'SELECT menutype, title FROM #__menu_types WHERE menutype="' . $name . '"';
		$db->setQuery($query);
		$menutype = $db->loadResult();
		$query    = 'SELECT m.menutype, m.id, m.title, m.parent_id, m.link FROM #__menu m WHERE m.parent_id = 1 AND m.menutype="' . $menutype . '" ORDER BY  m.lft ASC';
		$db->setQuery($query);
		$parents = $db->loadObjectList();
		$size    = 0;
		foreach ($parents as $parent) {
			if ($parent->link == '#' || $parent->link == '')
				$options[] = JHTML::_('select.optgroup', JText::_($parent->title));
			else
				$options[] = JHTML::_('select.option', $parent->id, JText::_($parent->title));
			$query = 'SELECT m.menutype, m.id, m.title, m.parent_id, m.link FROM #__menu m WHERE m.parent_id = ' . $parent->id . ' ORDER BY  m.lft ASC';
			$db->setQuery($query);
			$res  = $db->loadObjectList();
			$size += count($res);

			foreach ($res as $r) {
				$options[] = JHTML::_('select.option', $r->id, JText::_('&nbsp;&nbsp;-' . $r->title));
			}

		}
		if ($size > 5) $size = 10;
		else $size = 5;

		return JHTML::_('select.genericlist', $options, $this->name . '[]', 'multiple="multiple" size="' . $size . '"', 'value', 'text', $value, $name);
	}
}
