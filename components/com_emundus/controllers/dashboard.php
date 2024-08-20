<?php
/**
 * @package    eMundus
 * @subpackage Components
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Emundus Dashboard Controller
 * @package     Emundus
 */
class EmundusControllerDashboard extends BaseController
{
	/**
	 * @var \Joomla\CMS\User\User|JUser|mixed|null
	 * @since version 1.0.0
	 */
	private $_user;

	/**
	 * @var EmundusModelDashboard
	 * @since version 1.0.0
	 */
	private $m_dashboard;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JController
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->m_dashboard = $this->getModel('Dashboard');
		$this->_user       = $this->app->getIdentity();
	}

	/**
	 * Get all widgets by size
	 * @deprecated since version 2.0.0
	 *
	 * @since version 1.0.0
	 */
	public function getallwidgetsbysize()
	{
		try {
			$size = $this->input->getInt('size');

			$widgets = $this->m_dashboard->getallwidgetsbysize($size, $this->_user->id);

			$tab = array('status' => 0, 'msg' => 'success', 'data' => $widgets);
		}
		catch (Exception $e) {
			$tab = array('status' => 0, 'msg' => $e->getMessage(), 'data' => null);
		}
		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Get colors to apply on widgets
	 * @deprecated since version 2.0.0
	 *
	 * @since version 1.0.0
	 */
	public function getpalettecolors()
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		try {
			$menu   = $this->app->getMenu();
			$active = $menu->getActive();
			if (empty($active)) {
				$menuid = 1079;
			}
			else {
				$menuid = $active->id;
			}

			$query->select('m.params')
				->from($db->quoteName('#__modules', 'm'))
				->leftJoin($db->quoteName('#__modules_menu', 'mm') . ' ON ' . $db->quoteName('mm.moduleid') . ' = ' . $db->quoteName('m.id'))
				->where($db->quoteName('m.module') . ' LIKE ' . $db->quote('mod_emundus_dashboard_vue'))
				->andWhere($db->quoteName('mm.menuid') . ' = ' . $menuid);

			$db->setQuery($query);
			$modules = $db->loadColumn();

			foreach ($modules as $module) {
				$params = json_decode($module, true);
				if (in_array($this->app->getSession()->get('emundusUser')->profile, $params['profile'])) {
					$colors = $params['colors'];
				}
			}

			$tab = array('status' => 0, 'msg' => 'success', 'data' => $colors);
		}
		catch (Exception $e) {
			$tab = array('status' => 0, 'msg' => $e->getMessage(), 'data' => null);
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Get widgets to display for logged user for current profile
	 *
	 * @since version 1.0.0
	 */
	public function getwidgets()
	{
		try {
			$all_widgets = $this->input->getString('all',false) == 'true';
			$profile = $this->app->getSession()->get('emundusUser')->profile;
			$widgets = $this->m_dashboard->getwidgets($this->_user->id, $profile, $all_widgets);

			$tab = array('status' => 0, 'msg' => 'success', 'data' => $widgets);
		}
		catch (Exception $e) {
			$tab = array('status' => 0, 'msg' => $e->getMessage(), 'data' => null);
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Update widgets to display for logged user for current profile
	 *
	 * @since version 1.0.0
	 */
	public function updatemydashboard()
	{
		try {
			$widget   = $this->input->getInt('widget');
			$position = $this->input->getInt('position');

			$result = $this->m_dashboard->updatemydashboard($widget, $position, $this->_user->id);

			$tab = array('status' => 0, 'msg' => 'success', 'data' => $result);
		}
		catch (Exception $e) {
			$tab = array('status' => 0, 'msg' => $e->getMessage(), 'data' => null);
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Get filters for a widget
	 *
	 * @since version 1.0.0
	 */
	public function getfilters()
	{
		try {
			$widget = $this->input->getInt('widget');

			$tab = array('msg' => 'success', 'filters' => json_encode($this->app->getSession()->get('widget_filters_' . $widget)));
		}
		catch (Exception $e) {
			$tab = array('status' => 0, 'msg' => $e->getMessage(), 'data' => null);
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Render chart
	 *
	 * @since version 1.0.0
	 */
	public function renderchartbytag()
	{
		try {
			$widget  = $this->input->getInt('widget');
			$filters = $this->input->getRaw('filters','');
			if(!empty($filters)) {
				$filters = json_decode($filters, true);
			}
			else {
				$filters = array();
			}

			$session = $this->app->getSession();
			$session->set('widget_filters_' . $widget, $filters);

			$results = $this->m_dashboard->renderchartbytag($widget);

			$tab = array('msg' => 'success', 'dataset' => $results);
		}
		catch (Exception $e) {
			$tab = array('status' => 0, 'msg' => $e->getMessage(), 'data' => null);
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Get article to display in widget
	 *
	 * @since version 1.0.0
	 */
	public function getarticle()
	{
		try {
			$widget  = $this->input->getInt('widget');
			$article = $this->input->getInt('article');

			$results = $this->m_dashboard->getarticle($widget, $article);

			$tab = array('msg' => 'success', 'data' => $results);
		}
		catch (Exception $e) {
			$tab = array('status' => 0, 'msg' => $e->getMessage(), 'data' => null);
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Render widget via PHP Code (cannot be applied for applicant users)
	 *
	 * @since version 1.0.0
	 */
	public function geteval()
	{
		$response = ['status' => 0, 'msg' => JText::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			try {
				$widget = $this->input->getInt('widget');

				$results  = $this->m_dashboard->renderchartbytag($widget);
				$response = array('msg' => 'success', 'data' => $results, 'status' => 1);
			}
			catch (Exception $e) {
				$response['msg'] = $e->getMessage();
			}
		}

		echo json_encode((object) $response);
		exit;
	}
}
