<?php
/**
 * @version 2: emunduscampaign 2019-04-11 Hugo Moracchini
 * @package Fabrik
 * @copyright Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Création de dossier de candidature automatique.
 */

// No direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Update state from final_grade
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.emundusfinalgrade
 * @since       3.0
 */

class PlgFabrik_FormEmundusFinalGrade extends plgFabrik_Form {

    /**
     * Get an element name
     *
     * @param   string  $pname  Params property name to look up
     * @param   bool    $short  Short (true) or full (false) element name, default false/full
     *
     * @return	string	element full name
     */
    public function getFieldName($pname, $short = false) {
        $params = $this->getParams();

        if ($params->get($pname) == '') {
            return '';
        }

        $elementModel = FabrikWorker::getPluginManager()->getElementPlugin($params->get($pname));

        return $short ? $elementModel->getElement()->name : $elementModel->getFullName();
    }

    /**
     * Get the fields value regardless of whether its in joined data or no
     *
     * @param   string  $pname    Params property name to get the value for
     * @param   mixed   $default  Default value
     *
     * @return  mixed  value
     */
    public function getParam($pname, $default = '') {
        $params = $this->getParams();

        if ($params->get($pname) == '') {
            return $default;
        }

        return $params->get($pname);
    }


    public function onBeforeCalculations()
    {
	    jimport('joomla.log.log');
	    Log::addLogger(array('text_file' => 'com_emundus.emundus-final-grade.php'), Log::ALL, array('com_emundus.emundus-final-grade'));

	    $formModel = $this->getModel();

	    $fnum   = $formModel->formData['fnum_raw'];
	    $status = is_array($formModel->formData['final_grade_raw']) ? $formModel->formData['final_grade_raw'][0] : $formModel->formData['final_grade_raw'];

	    if (!empty($fnum) && !empty($status))
	    {
		    require_once(JPATH_SITE . '/components/com_emundus/models/files.php');
		    $m_files = new EmundusModelFiles();
		    $m_files->updateState([$fnum], $status);
	    }
    }
}
