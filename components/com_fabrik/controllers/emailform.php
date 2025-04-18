<?php
/**
 * Fabrik Email Form Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Factory;

jimport('joomla.application.component.controller');

/**
 * Fabrik Email Form Controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       1.5
 */
class FabrikControllerEmailform extends BaseController
{
	/**
	 * Display the view
	 *
	 * @param   boolean          $cachable    If true, the view output will be cached - NOTE not actually used to control caching!!!
	 * @param   array|boolean    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link InputFilter::clean()}.
	 *
	 * @return  JController  A JController object to support chaining.
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$document = Factory::getDocument();
		$app = Factory::getApplication();
		$input = $app->getInput();
		$viewName = $input->get('view', 'emailform');
		$modelName = 'form';

		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view (may have been set in content plugin already)
		try {
			if ($model = Factory::getApplication()->bootComponent('com_fabrik')->getMVCFactory()->createModel($modelName, 'FabrikFEModel'))
			{
				$view->setModel($model, true);
			}
		} catch (\Exception $e) {
			$view->error = 	\Joomla\CMS\Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
		// Display the view
		$view->display();

		return $this;
	}
}
