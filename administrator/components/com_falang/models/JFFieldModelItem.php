<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2017. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Table\Table;
use Joomla\Component\Fields\Administrator\Model\FieldModel;
use Joomla\CMS\Language\Text;

use Joomla\Utilities\ArrayHelper;

class JFTempFieldModelItem extends FieldModel {


	protected function loadForm($name, $source = null, $options = array(), $clear = false, $xpath = false)
	{
		// Handle the optional arguments.
		$options['control']	= ArrayHelper::getValue($options, 'control', false);

		// Create a signature hash.
		$hash = md5($source.serialize($options));

		// Check if we can use a previously loaded form.
		if (isset($this->_forms[$hash]) && !$clear) {
			return $this->_forms[$hash];
		}

		// Get the form.
		if (strpos($name, "com_")===0){
			if (strpos($name , ".")>0){
				$component = substr($name, 0, strpos($name , "."));
			}
			else {
				$component = $name;
			}
			$componentpath = JPATH_BASE."/components/".$component;
			Form::addFormPath($componentpath.'/forms');
			Form::addFieldPath($componentpath.'/forms/fields');
		}
		else {
			Form::addFormPath(JPATH_COMPONENT.'/forms/forms');
			Form::addFieldPath(JPATH_COMPONENT.'/forms/fields');
		}

        try
        {
            $formFactory = $this->getFormFactory();
        }
        catch (\UnexpectedValueException $e)
        {
            // @Todo can be removed when the constructor argument becomes mandatory
            $formFactory = Factory::getContainer()->get(FormFactoryInterface::class);
        }

		try {

			//$form = Form::getInstance($name, $source, $options, false, $xpath);
            $form = $formFactory->createForm($name, $options);

            // Load the data.
            if (substr($source, 0, 1) == '<')
            {
                if ($form->load($source, false, $xpath) == false)
                {
                    throw new \RuntimeException('Form::loadForm could not load form');
                }
            }
            else
            {
                if ($form->loadFile($source, false, $xpath) == false)
                {
                    throw new \RuntimeException('Form::loadForm could not load file');
                }
            }

			if (isset($options['load_data']) && $options['load_data']) {
				// Get the data for the form.
				$data = $this->loadFormData();
			} else {
				$data = array();
			}

			// Allow for additional modification of the form, and events to be triggered.
			// We pass the data because plugins may require it.
			$this->preprocessForm($form, $data);

			// Load the data into the form after the plugins have operated.
			$form->bind($data);

		} catch (Exception $e) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_($e->getMessage()), 'error');
            return false;
		}

		// Store the form for later.
		$this->_forms[$hash] = $form;

		return $form;
	}

	/*
	 * since 4.0
	 * TODO change the way the TableField is loaded
	 * */
    public function getTable($name = 'Field', $prefix = 'JTable', $options = array())
    {
        //JLoader::register('Joomla\Component\Fields\Administrator\Table\FieldTable', PATH_BASE.'/components/com_fields/src/Table/FieldTable.php, true);
        //Todo clean here
        JLoader::registerAlias('JTableField',                        '\\Joomla\\Component\\Fields\\Administrator\\Table\\FieldTable', '5.0');
        //no working
        //JLoader::register('FieldTable',PATH_BASE.'/components/com_fields/src/Table');
        //Table::addIncludePath(JPATH_BASE."/components/com_fields/src/Table");
        // Default to text type
        $table       = Table::getInstance($name, $prefix, $options);;

        return $table;
    }

}


class JFFieldModelItem extends JFTempFieldModelItem {

	function &getItem($translation=null)
	{

        $item = parent::getItem();
		return $item;

	}

}

