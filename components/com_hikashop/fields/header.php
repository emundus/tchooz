<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.5
 * @author	hikashop.com
 * @copyright	(C) 2010-2025 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php

use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

\defined('JPATH_PLATFORM') or die;

class JFormFieldHeader extends FormField
{
    protected $type = 'Header';

    protected $hiddenLabel = true;

    protected $hiddenDescription = true;

    public function __construct($form = null)
    {
        parent::__construct($form);
    }

    protected function getLabel()
    {
        $label = $this->value;

        if (empty($label) && empty($label = (string)$this->element['label']) && empty($label = (string)$this->element['description'])
            && empty($label = (string)$this->element['title'])) {
            return '';
        }

        $html  = [];
        $class = [];

        if (!empty($this->class)) {
            $class[] = $this->class;
        }

        if ($close = (string)$this->element['close']) {
            HTMLHelper::_('bootstrap.alert');
            $close   = $close === 'true' ? 'alert' : $close;
            $html[]  = '<button type="button" class="btn-close" data-bs-dismiss="' . $close . '"></button>';
            $class[] = 'alert-dismissible show';
        }

        $class       = $class ? ' class="' . implode(' ', $class) . '"' : '';
        $title       = $label;
        $heading     = $this->element['heading'] ? (string)$this->element['heading'] : 'h4';
        $description = (string)$this->element['description'];
        $html[]      = !empty($title) ? '<' . $heading . '>' . Text::_($title) . '</' . $heading . '>' : '';
        $html[]      = !empty($description) ? Text::_($description) : '';

        return '</div><div ' . $class . '>' . implode('', $html);
    }

    protected function getInput()
    {
        return '<input name="' . $this->formControl . '[' . $this->element['name'] . ']" type="hidden" value="' . $this->value . '"/>';
    }
}
