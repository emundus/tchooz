<?php
/**
 * @package    Joomla
 * @subpackage eMundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
//error_reporting(E_ALL);
jimport('joomla.application.component.view');

use Joomla\CMS\Factory;

/**
 * HTML View class for the Emundus Component
 *
 * @package    Emundus
 */
class EmundusViewEvaluation extends JViewLegacy
{
	private $app;
	protected $_user;
	private $_db;
	protected $itemId;
	protected $actions;
	protected bool $use_module_for_filters = true;
	protected bool $open_file_in_modal;

	protected $modal_tabs = null;
	protected string $modal_ratio = '66/33';
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->app   = Factory::getApplication();
		$this->_user = $this->app->getIdentity();

		$menu                         = $this->app->getMenu();
		if (!empty($menu)) {
			$current_menu                 = $menu->getActive();
			if (!empty($current_menu)) {
				$menu_params                  = $menu->getParams($current_menu->id);
				$this->open_file_in_modal     = boolval($menu_params->get('em_open_file_in_modal', 0));

				if ($this->open_file_in_modal) {
					$this->modal_ratio = $menu_params->get('em_modal_ratio', '66/33');

					$tabs = [];
					$menu_tabs = $menu_params->get('modal_tabs');
					foreach ($menu_tabs as $tab) {
						$name = $tab->tab_type == 'component' ? $tab->tab_component : 'custom-' . $tab->tab_label;
						$access = 1;

						if ($tab->tab_type == 'component') {
							switch($tab->tab_component) {
								case 'application':
									$access = 1;
									break;
								case 'attachments':
									$access = 4;
									break;
								case 'comments':
									$access = 10;
									break;
							}
						}

						$tabs[] = [
							'label' => $tab->tab_name,
							'type' => $tab->tab_type,
							'name' => $name,
							'url' => $tab->tab_url,
							'access' => $access,
						];
					}

					$this->modal_tabs = base64_encode(json_encode($tabs));
				}
			}
		}
	}

	public function display($tpl = null)
	{
		$app = Factory::getApplication();

		$this->itemId = $app->input->getInt('Itemid', null);
		$this->cfnum  = $app->input->getString('cfnum', null);

		/* Get the values from the state object that were inserted in the model's construct function */
		$lists['order_dir'] = $app->getSession()->get('filter_order_Dir');
		$lists['order']     = $app->getSession()->get('filter_order');

		$menu         = $this->app->getMenu();
		$current_menu = $menu->getActive();

		$Itemid = $this->app->input->getInt('Itemid', $current_menu->id);

		if (!empty($current_menu))
		{
			$menu_params = $menu->getParams($Itemid);
			require_once JPATH_ROOT . '/components/com_emundus/classes/filters/EmundusFiltersFiles.php';
			try
			{
				$m_filters = new EmundusFiltersFiles($menu_params->toArray());

				$this->filters              = $m_filters->getFilters();
				$this->applied_filters      = $m_filters->getAppliedFilters();
				$this->quick_search_filters = $m_filters->getQuickSearchFilters();
				$this->count_filter_values  = $menu_params->get('count_filter_values', 0);
				$this->allow_add_filter     = $menu_params->get('allow_add_filter', 1);
			}
			catch (Exception $e)
			{
				$this->app->enqueueMessage($e->getMessage());
				$this->app->redirect('/');
			}
		}

		parent::display($tpl);
	}
}

?>

