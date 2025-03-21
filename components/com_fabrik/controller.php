<?php
/**
 * Fabrik Front end controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Factory;
use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Uri\Uri;

/**
 * Fabrik Component Controller
 * DEPRECIATED - should always get directed to specific controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       1.5
 */

class FabrikController extends BaseController
{
	/**
	 * Is the controller inside a content plug-in
	 *
	 * @var  bool
	 */
	public $isMambot = false;

	/**
	 * Id used from content plugin when caching turned on to ensure correct element rendered)
	 *
	 * @var  int
	 */
	public $cacheId = 0;

	/**
	 * Display the view
	 *
	 * @param   bool   $cachable   If true, the view output will be cached - NOTE not actually used to control caching!!!
	 * @param   array  $urlparams  An array of safe url parameters and their variable types, for valid values see {@link InputFilter::clean()}.
	 *
	 * @return  null
	 */

	public function display($cachable = false, $urlparams = false)
	{
		$app = Factory::getApplication();
		$input = $app->getInput();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');

		// Menu links use fabriklayout parameters rather than layout
		$flayout = $input->get('fabriklayout');

		if ($flayout != '')
		{
			$input->set('layout', $flayout);
		}

		$document = Factory::getDocument();

		$viewName = $input->get('view', 'form');
		$modelName = $viewName;

		if ($viewName == 'emailform')
		{
			$modelName = 'form';
		}

		if ($viewName == 'details')
		{
			$viewName = 'form';
			$modelName = 'form';
		}

		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view
		if ($model = $this->getModel($modelName))
		{
			$view->setModel($model, true);
		}

		// Display the view

		$view->error = $this->getError();

		if (($viewName = 'form' || $viewName = 'details'))
		{
			$cachable = true;
		}

		$user = Factory::getUser();

		if (Worker::useCache() && !$this->isMambot)
		{
			$user = Factory::getUser();
			$uri = Uri::getInstance();
			$uri = $uri->toString(array('path', 'query'));
			$cacheid = serialize(array($uri, $input->post, $user->get('id'), get_class($view), 'display', $this->cacheId));
			$cache = Factory::getCache('com_' . $package, 'view');
			Html::addToSessionCacheIds($cacheid);
			echo $cache->get($view, 'display', $cacheid);
		}
		else
		{
			return $view->display();
		}
	}
}
