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
use Joomla\CMS\Language\Text;
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
		$result = array('status' => false, 'msg' => '', 'data' => null);

		if(!$this->_user->guest)
		{
			$size = $this->input->getInt('size');

			$result['data'] = $this->m_dashboard->getallwidgetsbysize($size, $this->_user->id);
			$result['status'] = true;
		}

		echo json_encode((object) $result);
		exit;
	}

	/**
	 * Get widgets to display for logged user for current profile
	 *
	 * @since version 1.0.0
	 */
	public function getwidgets()
	{
		$result = array('status' => false, 'msg' => '', 'data' => null);

		if(!$this->_user->guest)
		{
			$all_widgets = $this->input->getString('all', false) == 'true';
			$profile     = $this->app->getSession()->get('emundusUser')->profile;

			$result['data']   = $this->m_dashboard->getwidgets($this->_user->id, $profile, $all_widgets);
			$result['status'] = true;
		}

		echo json_encode((object) $result);
		exit;
	}

	/**
	 * Update widgets to display for logged user for current profile
	 *
	 * @since version 1.0.0
	 */
	public function updatemydashboard()
	{
		$result = array('status' => false, 'msg' => '', 'data' => null);

		if(!$this->_user->guest)
		{
			$widget   = $this->input->getInt('widget',0);
			$position = $this->input->getInt('position',0);

			if(!empty($widget) && !empty($position))
			{
				$result['data']   = $this->m_dashboard->updatemydashboard($widget, $position, $this->_user->id);
				$result['status'] = true;
			}
		}

		echo json_encode((object) $result);
		exit;
	}

	/**
	 * Get filters for a widget
	 *
	 * @since version 1.0.0
	 */
	public function getfilters()
	{
		$result = array('status' => false, 'filters' => array());

		if(!$this->_user->guest)
		{
			$widget = $this->input->getInt('widget',0);

			if(!empty($widget))
			{
				$result['filters'] = json_encode($this->app->getSession()->get('widget_filters_' . $widget, []));
				$result['status']  = true;
			}
		}

		echo json_encode((object) $result);
		exit;
	}

	/**
	 * Render chart
	 *
	 * @since version 1.0.0
	 */
	public function renderchartbytag()
	{
		$result = array('status' => false, 'dataset' => null);

		if(!$this->_user->guest)
		{
			$widget  = $this->input->getInt('widget',0);
			$filters = $this->input->getRaw('filters', '');

			if(!empty($widget))
			{
				if (!empty($filters))
				{
					$filters = json_decode($filters, true);
				}
				else
				{
					$filters = array();
				}

				$session = $this->app->getSession();
				$session->set('widget_filters_' . $widget, $filters);

				$result['dataset'] = $this->m_dashboard->renderchartbytag($widget);
				$result['status']  = true;
			}
		}

		echo json_encode((object) $result);
		exit;
	}

	/**
	 * Get article to display in widget
	 *
	 * @since version 1.0.0
	 */
	public function getarticle()
	{
		$result = array('status' => false, 'data' => null);

		if(!$this->_user->guest) {
			$widget  = $this->input->getInt('widget',0);
			$article = $this->input->getInt('article',0);

			if(!empty($widget) && !empty($article))
			{
				$result['data']   = $this->m_dashboard->getarticle($widget, $article);
				$result['status'] = true;
			}
		}

		echo json_encode((object) $result);
		exit;
	}

	/**
	 * Render widget via PHP Code (cannot be applied for applicant users)
	 *
	 * @since version 1.0.0
	 */
	public function geteval()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => null];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$widget = $this->input->getInt('widget',0);

			if(!empty($widget))
			{
				$response['data']   = $this->m_dashboard->renderchartbytag($widget);
				$response['msg']    = '';
				$response['status'] = true;
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getfilterprogramme() {
		$result = array('status' => false, 'msg' => '', 'data' => '');

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$result['status'] = true;
			$result['data'] = Factory::getApplication()->getUserState('dashboard.emundus.filter.programme', '');
		}

		echo json_encode((object) $result);
		exit;
	}

	public function setfilterprogramme() {
		$result = array('status' => false, 'msg' => '');

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$result['status'] = true;
			$code = $this->input->getString('code','');

			Factory::getApplication()->setUserState('dashboard.emundus.filter.programme', $code);
		}

		echo json_encode((object) $result);
		exit;
	}
}
