<?php
/**
 * @version 2: emundusisapplicationsent 2018-12-04 Hugo Moracchini
 * @package Fabrik
 * @copyright Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Locks access to a file if the file is not of a certain status.
 */

// No direct access
use Fabrik\Helpers\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Run emundus triggers link to Fabrik events
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juseremundus
 * @since       3.0
 */

class PlgFabrik_FormEmundustriggers extends PlgFabrik_Form
{

    /**
     * Get an element name
     *
     * @param   string  $pname  Params property name to look up
     * @param   bool    $short  Short (true) or full (false) element name, default false/full
     *
     * @return	string	element full name
     * @since 1.0.0
     */
    public function getFieldName($pname, $short = false) {
        $params = $this->getParams();

        if ($params->get($pname) == '')
            return '';

        $elementModel = FabrikWorker::getPluginManager()->getElementPlugin($params->get($pname));

        return $short ? $elementModel->getElement()->name : $elementModel->getFullName();
    }

    /**
     * Get the fields value regardless of whether its in joined data or no
     *
     * @param   string  $pname    Params property name to get the value for
     * @param   array   $data     Posted form data
     * @param   mixed   $default  Default value
     *
     * @return  mixed  value
     * @since 1.0.0
     */
    public function getParam($pname, $default = '') {
        $params = $this->getParams();

        if ($params->get($pname) == '') {
            return $default;
        }

        return $params->get($pname);
    }

    /**
     * Before loading form data
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onBeforeLoad() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus');

	    $result = Factory::getApplication()->triggerEvent('onCallEventHandler', ['onBeforeLoad', ['formModel' => $formModel, 'plugin_options' => $this->getParams()]]);

        return true;
    }

    /**
     * Before loading the form
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onLoad() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onLoad', ['formModel' => $formModel]]);

        return true;
    }

    /**
     * When the JS form is assembled and ready during page load
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onJSReady() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onJSReady', ['formModel' => $formModel]]);

        return true;
    }

    /**
     * Before the JSON encoding of the main JS options, in $opts
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onJSOpts() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onJSOpts', ['formModel' => $formModel]]);

        return true;
    }

    /**
     * When groups are rendered on the form
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onCanEditGroup() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onCanEditGroup', ['formModel' => $formModel]]);

        return true;
    }

    /**
     * Start of form submission
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onBeforeProcess() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onBeforeProcess', ['formModel' => $formModel]]);

        return true;
    }

    /**
     * After all images have been downloaded
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onBeforeStore() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onBeforeStore', ['formModel' => $formModel]]);

        return true;
    }

    /**
     * After data has been stored, before calculations
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onBeforeCalculations() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onBeforeCalculations', ['formModel' => $formModel]]);

        return true;
    }

    /**
     * End of form submission
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onAfterProcess() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onAfterProcess', ['formModel' => $formModel, 'plugin_options' => $this->getParams()]]);

        return true;
    }

    /**
     * If an error occurs in form submission
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onError() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onError', ['formModel' => $formModel]]);

        return true;
    }

    /**
     * At top of form
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function getTopContent() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['getTopContent', ['formModel' => $formModel]]);

        return true;
    }

    /**
     * At bottom of form
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function getBottomContent() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['getBottomContent', ['formModel' => $formModel]]);

        return true;
    }

    /**
     * After the end of the form
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function getEndContent() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['getEndContent', ['formModel' => $formModel]]);

        return true;
    }

    /**
     * On record deletion
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onDeleteRowsForm(&$groups) {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onDeleteRowsForm', ['formModel' => $formModel, 'groups' => $groups]]);

        return true;
    }

    /**
     * After record deletion
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onAfterDeleteRowsForm(&$groups) {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onAfterDeleteRowsForm', ['formModel' => $formModel, 'groups' => $groups]]);

        return true;
    }

    /**
     * On saving a page in a multi-page form
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onSavePage() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onSavePage', ['formModel' => $formModel]]);

        return true;
    }

    /**
     * On canUse test for elements in form
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onElementCanUse($args) {
        $formModel = $this->getModel();
	    $elementModel = ArrayHelper::getValue($args, 0, false);

        PluginHelper::importPlugin('emundus','custom_event_handler');
        $results = Factory::getApplication()->triggerEvent('onCallEventHandler', ['onElementCanUse', ['formModel' => $formModel, 'elementModel' => $elementModel]]);
		
		if(!empty($results) && !empty($results[0])) {
			$onElementCanUse = array_map(function($result) {
				return $result['onElementCanUse'];
			}, $results);
		}

		if(!empty($onElementCanUse)) {
			return in_array(false, $onElementCanUse) ? false : true;
		} else {
			return true;
		}
	}

    /**
     * On canView test for elements in form
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onElementCanView() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onElementCanView', ['formModel' => $formModel]]);

        return true;
    }

    /**
     * One assigning container class in form
     *
     * @return  mixed
     * @since 1.0.0
     */
    public function onElementContainerClass() {
        $formModel = $this->getModel();

        PluginHelper::importPlugin('emundus','custom_event_handler');
        Factory::getApplication()->triggerEvent('onCallEventHandler', ['onElementContainerClass', ['formModel' => $formModel]]);

        return true;
    }
}
