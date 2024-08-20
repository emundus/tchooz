<?php
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.controller');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;


/**
 * eMundus Component Controller
 *
 * @package    Joomla.Tutorials
 * @subpackage Components
 */
class EmundusControllerExport_select_columns extends BaseController
{
	protected $app;

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached.
	 * @param   boolean  $urlparams  An array of safe URL parameters and their variable types.
	 *                   @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
	 *
	 * @return  DisplayController  This object to support chaining.
	 *
	 * @since   1.0.0
	 */
	function display($cachable = false, $urlparams = false)
	{
		// Set a default view if none exists
		if (!$this->input->get('view')) {
			$default = 'export_select_columns';
			$this->input->set('view', $default);
		}
		parent::display();
	}

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JController
	 * @since   1.0.0
	 */
	function __construct($config = array())
	{
		parent::__construct($config);

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'files.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'programme.php');

		$this->app = Factory::getApplication();
	}

	public function getformtags()
	{
		$user = $this->app->getIdentity();

		$model = $this->getModel('export_select_columns');

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else {
			$prg     = $this->input->getString('code', null);
			$camp    = $this->input->getInt('camp', null);
			$profile = $this->input->getInt('profile', null);

			$code     = array();
			$camps    = array();
			$code[]   = $prg;
			$camps[]  = $camp;
			$elements = EmundusHelperFiles::getElements($code, $camps, [], $profile);

			$allowed_groups = EmundusHelperAccess::getUserFabrikGroups($user->id);
			if ($allowed_groups !== true) {
				foreach ($elements as $key => $elt) {
					if (!in_array($elt->group_id, $allowed_groups)) {
						unset($elements[$key]);
					}
				}
			}

			$tab = array('status' => true, 'msg' => JText::_("ACCESS_DENIED"), 'tags' => $elements);
		}
		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Gets all eMundus Tags from tags_table
	 */
	public function getalltags()
	{
		$user = $this->app->getIdentity();

		$model = $this->getModel('export_select_columns');

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else {
			$tags = $model->getAllTags();
		}
		echo json_encode((object) [
			'status' => true,
			'tags'   => $tags
		]);
		exit;
	}


} //END CLASS
?>
