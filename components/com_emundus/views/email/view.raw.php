<?php
/**
 * @package    Joomla
 * @subpackage emundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// no direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Input\Input;

/**
 * HTML View class for the Emundus Component
 *
 * @package    Emundus
 */
class EmundusViewEmail extends JViewLegacy
{
	private $app;
	private $_user;

	protected $fnum_array = [];

	function __construct($config = array())
	{
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'javascript.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'filters.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'files.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'list.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'emails.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'export.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'menu.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');

		$this->app   = Factory::getApplication();
		$this->_user = $this->app->getIdentity();

		parent::__construct($config);
	}

	function display($tpl = null)
	{

		$h_emails = new EmundusHelperEmails();
		
		$post = file_get_contents("php://input");
		$post = json_decode($post, true);
		$fnums = $post['fnums'];

		$dest = $this->app->input->getInt('desc', 0);

		if ($dest === 3)
		{
			if ($fnums == 'all')
			{
				$m_files     = new EmundusModelFiles;
				$fnums       = $m_files->getAllFnums();
				$fnums_infos = $m_files->getFnumsInfos($fnums, 'object');
				$fnums       = $fnums_infos;
			}
			else
			{
				$fnums = (array) json_decode(stripslashes($fnums), false, 512, JSON_BIGINT_AS_STRING);
			}
			

			foreach ($fnums as $key => $fnum)
			{

				if ($fnum->fnum === 'em-check-all')
				{
					unset($fnums[$key]);
					continue;
				}

				if (EmundusHelperAccess::asAccessAction(18, 'c', $this->_user->id, $fnum->fnum))
				{
					$this->fnum_array[] = $fnum->fnum;
				}
			}

			// Store the fnums in the session
			$this->app->setUserState('com_emundus.email.expert.fnums', $this->fnum_array);
		}

		parent::display($tpl);
	}
}