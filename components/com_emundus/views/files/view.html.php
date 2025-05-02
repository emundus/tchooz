<?php
/**
 * @package    eMundus
 * @subpackage Components
 *             components/com_emundus/emundus.php
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// no direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;

defined('_JEXEC') or die('Restricted access');
//error_reporting(E_ALL);
jimport('joomla.application.component.view');

/**
 * HTML View class for the Emundus Component
 *
 * @package    Emundus
 */
class EmundusViewFiles extends HtmlView
{
	protected $itemId;
	protected $cfnum;
	protected $actions;
	protected bool $use_module_for_filters = true;
	protected array $lists;
	protected JPagination $pagination;

	public function __construct($config = array())
	{
		parent::__construct($config);
	}

	public function display($tpl = null)
	{
		$app = Factory::getApplication();
		$current_user = $app->getIdentity();
		if (!EmundusHelperAccess::asPartnerAccessLevel($current_user->id)) {
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		$m_files = new EmundusModelFiles();

		$this->itemId = $app->input->getInt('Itemid', null);
		$this->cfnum  = $app->input->getString('cfnum', null);

		/* Get the values from the state object that were inserted in the model's construct function */
		$lists['order_dir'] = $app->getSession()->get('filter_order_Dir');
		$lists['order']     = $app->getSession()->get('filter_order');
		$this->lists        = $lists;
		$this->pagination   = $m_files->getPagination();

		parent::display($tpl);
	}

}

?>

