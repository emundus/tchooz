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
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\User\UserFactoryInterface;

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
	protected CMSApplicationInterface|null $app;
	
	protected $itemId;
	protected $cfnum;
	protected $actions;
	protected bool $use_module_for_filters = true;
	protected array $lists;
	protected JPagination $pagination;

	private EmundusModelMessenger $m_messenger;
	private EmundusModelEvaluation $m_evaluation;
	private EmundusModelFiles $m_files;
	private EmundusModelUsers $m_users;

	public function __construct($config = array())
	{
		$this->app  = Factory::getApplication();
		$this->user = $this->app->getIdentity();

		if (!class_exists(JPATH_SITE . '/components/com_emundus/models/messenger.php'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/messenger.php';
		}
		$this->m_messenger = new EmundusModelMessenger();

		if (!class_exists(JPATH_SITE . '/components/com_emundus/models/evaluation.php'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/evaluation.php';
		}
		$this->m_evaluation = new EmundusModelEvaluation();

		if (!class_exists(JPATH_SITE . '/components/com_emundus/models/files.php'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/files.php';
		}
		$this->m_files = new EmundusModelFiles();

		if (!class_exists(JPATH_SITE . '/components/com_emundus/models/users.php'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/users.php';
		}
		$this->m_users = new EmundusModelUsers();

		parent::__construct($config);
	}

	public function display($tpl = null)
	{
		$current_user = $this->app->getIdentity();
		if (!EmundusHelperAccess::asPartnerAccessLevel($current_user->id))
		{
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		$this->itemId = $this->app->input->getInt('Itemid', null);
		$this->cfnum  = $this->app->input->getString('cfnum', null);

		$h_files              = new EmundusHelperFiles;
		$params               = ComponentHelper::getParams('com_emundus');
		$default_actions      = $params->get('default_actions', '[]');
		$hide_default_actions = $params->get('hide_default_actions', 0);
		$h_files->setMenuFilter();

		/* Get the values from the state object that were inserted in the model's construct function */
		$lists['order_dir'] = $this->app->getSession()->get('filter_order_Dir');
		$lists['order']     = $this->app->getSession()->get('filter_order');

		$menu         = $this->app->getMenu();
		$current_menu = $menu->getActive();

		$Itemid = $this->app->input->getInt('Itemid', $current_menu->id);

		if (!empty($current_menu))
		{
			$menu_params = $menu->getParams($Itemid);
			require_once JPATH_ROOT . '/components/com_emundus/classes/filters/EmundusFiltersFiles.php';

			try
			{
				$m_filters                  = new EmundusFiltersFiles($menu_params->toArray());
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

		$e_user       = $this->app->getSession()->get('emundusUser');
		$menu         = $this->app->getMenu();
		$current_menu = $menu->getActive();

		$Itemid      = $this->app->input->getInt('Itemid', $current_menu->id);
		$menu_params = $menu->getParams($Itemid);

		$columnSupl = explode(',', $menu_params->get('em_other_columns'));
		
		$groups = $this->m_users->getUserGroups($this->user->id, 'Column', $e_user->profile);

		// get all fnums manually associated to user
		$fnum_assoc_to_groups = $this->m_users->getApplicationsAssocToGroups($groups);
		$fnum_assoc           = $this->m_users->getApplicantsAssoc($this->user->id);

		$this->m_files->fnum_assoc = array_merge($fnum_assoc_to_groups, $fnum_assoc);
		$this->m_files->code       = [];
		if (!empty($groups))
		{
			$this->m_files->code = $this->m_users->getUserGroupsProgrammeAssoc($this->user->id, 'jesgrc.course', $groups);
		}

		$this->code       = $this->m_files->code;
		$this->fnum_assoc = $this->m_files->fnum_assoc;

		if (!empty($this->m_files->fnum_assoc) || !empty($this->m_files->code))
		{
			// get applications files
			$users = $this->m_files->getUsers();
		}
		else
		{
			$users = array();
		}

		// Get elements from model and proccess them to get an easy to use array containing the element type
		$elements = $this->m_files->getElementsVar();
		foreach ($elements as $elt)
		{
			$elt_name          = $elt->tab_name . "___" . $elt->element_name;
			$eltarr[$elt_name] = [
				"plugin"    => $elt->element_plugin,
				"tab_name"  => $elt->tab_name,
				"params"    => $elt->element_attribs,
				"fabrik_id" => $elt->id
			];
		}

		if (isset($eltarr))
		{
			$elements = $eltarr;
		}

		// Do not display photos unless specified in params
		$displayPhoto = false;

		$defaultElements = $this->get('DefaultElements');
		$data            = array(array('check' => '#', 'name' => Text::_('COM_EMUNDUS_FILES_APPLICATION_FILES'), 'status' => Text::_('COM_EMUNDUS_STATUS')));
		$fl              = array();
		if (count($defaultElements) > 0)
		{
			foreach ($defaultElements as $elt)
			{
				$fl[$elt->tab_name . '___' . $elt->element_name] = $elt->element_label;
			}
		}

		$data[0]   = array_merge($data[0], $fl);
		$fnumArray = [];
		$objAccess = [];
		$colsSup   = [];

		if (!empty($users))
		{
			$i          = 1;
			$taggedFile = array();
			foreach ($columnSupl as $col)
			{
				$col = explode('.', $col);
				switch ($col[0])
				{
					case 'evaluators':
						$data[0]['EVALUATORS'] = Text::_('COM_EMUNDUS_EVALUATION_EVALUATORS');
						$colsSup['evaluators'] = $h_files->createEvaluatorList($col[1], $this->m_files);
						break;
					case 'overall':
						$data[0]['overall'] = Text::_('COM_EMUNDUS_EVALUATIONS_OVERALL');
						$colsSup['overall'] = array();
						break;
					case 'tags':
						$taggedFile            = $this->m_files->getTaggedFile();
						$data[0]['eta.id_tag'] = Text::_('COM_EMUNDUS_TAGS');
						$colsSup['id_tag']     = array();
						break;
					case 'access':
						$data[0]['access'] = Text::_('COM_EMUNDUS_ASSOCIATED_TO');
						$colsSup['access'] = array();
						break;
					case 'photos':
						$displayPhoto = true;
						break;
					case 'form_progress':
						$data[0]['form_progress'] = Text::_('COM_EMUNDUS_FORM_PROGRESS');
						$colsSup['form_progress'] = array();
						break;
					case 'attachment_progress':
						$data[0]['attachment_progress'] = Text::_('COM_EMUNDUS_ATTACHMENT_PROGRESS');
						$colsSup['attachment_progress'] = array();
						break;
					case 'unread_messages':
						$data[0]['unread_messages'] = Text::_('COM_EMUNDUS_UNREAD_MESSAGES');
						$colsSup['unread_messages'] = array();
						break;
					case 'commentaire':
						$data[0]['commentaire'] = Text::_('COM_EMUNDUS_COMMENTAIRE');
						$colsSup['commentaire'] = array();
						break;
					case 'module':
						// Get every module without a positon.
						$mod_emundus_custom = array();
						foreach (ModuleHelper::getModules('') as $module)
						{
							if ($module->module == 'mod_emundus_custom' && ($module->menuid == 0 || $module->menuid == $this->app->input->get('Itemid', null)))
							{
								$mod_emundus_custom[$module->title] = $module->content;
								$data[0][$module->title]            = Text::_($module->title);
								$colsSup[$module->title]            = array();
							}
						}
						break;
					default:
						break;
				}
			}

			$unread_messages = array();
			if ($this->m_messenger->checkMessengerState())
			{
				$unread_messages[] = $this->m_files->getUnreadMessages($this->user->id);
				$unread_messages   = $h_files->createUnreadMessageList($unread_messages[0]);
				$keys              = array_keys($unread_messages);
				natsort($keys);
			}

			foreach ($users as $user)
			{
				$usObj       = new stdClass();
				$usObj->val  = 'X';
				$fnumArray[] = $user['fnum'];
				$line        = array('check' => $usObj);

				if (array_key_exists($user['fnum'], $taggedFile))
				{
					$class        = $taggedFile[$user['fnum']]['class'];
					$usObj->class = $taggedFile[$user['fnum']]['class'];
				}
				else
				{
					$class        = null;
					$usObj->class = null;
				}

				foreach ($user as $key => $value)
				{
					$userObj = new stdClass();

					if ($key == 'fnum')
					{
						$userObj->val   = $value;
						$userObj->class = $class;
						$userObj->type  = 'fnum';
						if ($displayPhoto)
						{
							$userObj->photo = $h_files->getPhotos($value);
						}
						$userObj->user            = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $user['applicant_id']);
						$userObj->user->name      = $user['name'];
						$userObj->unread_messages = !empty($unread_messages) ? $unread_messages[$value] : '';

						$line['fnum'] = $userObj;
					}
					elseif ($key == 'name' || $key == 'status_class' || $key == 'step' || $key == 'applicant_id' || $key == 'campaign_id' || $key == 'unread_messages' || $key == 'commentaire')
					{
						continue;
					}
					elseif (isset($elements) && in_array($key, array_keys($elements)))
					{
						$userObj->val          = $value;
						$userObj->type         = $elements[$key]['plugin'];
						$userObj->status_class = $user['status_class'];
						$userObj->id           = $elements[$key]['fabrik_id'];
						$userObj->params       = $elements[$key]['params'];
						$line[$key]            = $userObj;

						// Radiobuttons are a strange beast, we need to get all of the values
						if ($userObj->type == 'radiobutton')
						{
							$params         = json_decode($userObj->params);
							$userObj->radio = array_combine($params->sub_options->sub_labels, $params->sub_options->sub_values);
						}
					}
					else
					{
						$userObj->val          = $value;
						$userObj->type         = 'text';
						$userObj->status_class = $user['status_class'];
						$line[$key]            = $userObj;
					}
				}

				if (count($colsSup) > 0)
				{
					foreach ($colsSup as $key => $obj)
					{
						$userObj = new stdClass();
						if (!is_null($obj))
						{
							if (array_key_exists($user['fnum'], $obj))
							{
								$userObj->val                    = $obj[$user['fnum']];
								$userObj->type                   = 'html';
								$userObj->fnum                   = $user['fnum'];
								$line[Text::_(strtoupper($key))] = $userObj;
							}
							else
							{
								$userObj->val  = '';
								$userObj->type = 'html';
								$line[$key]    = $userObj;
							}
						}
						elseif ($key === 'overall' || $key === 'id_tag' || $key === 'access' || (!empty($mod_emundus_custom) && array_key_exists($key, $mod_emundus_custom)))
						{
							$line[$key] = "";
						}
					}
				}
				$data[$line['fnum']->val . '-' . $i] = $line;
				$i++;
			}

			if (isset($colsSup['overall']))
			{
				$colsSup['overall'] = $this->m_evaluation->getEvaluationAverageByFnum($fnumArray);
			}

			if (isset($colsSup['id_tag']))
			{
				$tags              = $this->m_files->getTagsByFnum($fnumArray);
				$colsSup['id_tag'] = $h_files->createTagsList($tags);
			}

			if (isset($colsSup['access']))
			{
				$objAccess = $this->m_files->getAccessorByFnums($fnumArray);
			}

			if (isset($colsSup['form_progress']))
			{
				$forms_progress           = $this->m_files->getFormProgress($fnumArray);
				$colsSup['form_progress'] = $h_files->createFormProgressList($forms_progress);
			}

			if (isset($colsSup['attachment_progress']))
			{
				$attachments_progress           = $this->m_files->getAttachmentProgress($fnumArray);
				$colsSup['attachment_progress'] = $h_files->createAttachmentProgressList($attachments_progress);
			}

			if (isset($colsSup['commentaire']))
			{
				foreach ($fnumArray as $fnum)
				{
					$notifications_comments        = sizeof($this->m_files->getCommentsByFnum([$fnum]));
					$colsSup['commentaire'][$fnum] = '<p class="messenger__notifications_counter">' . $notifications_comments . '</p> ';
				}
			}

			if (!empty($mod_emundus_custom))
			{
				foreach ($mod_emundus_custom as $key => $module)
				{
					if (isset($colsSup[$key]))
					{
						$colsSup[$key] = $h_files->createHTMLList($module, $fnumArray);
					}
				}
			}

			$this->keys_order = ['check' => -1, 'fnum' => 0];

			if (!empty($menu_params->get('em_columns_ordered')))
			{
				$columns_ordered = explode(',', $menu_params->get('em_columns_ordered'));
				$i               = 1;
				foreach ($columns_ordered as $key)
				{
					$this->keys_order[$key] = $i;
					$i++;
				}
			}

			foreach ($data[0] as $k => $v)
			{
				if (!array_key_exists($k, $this->keys_order) && $k != 'name')
				{
					$this->keys_order[$k] = 999;
				}
			}
		}
		else
		{
			$data = Text::_('COM_EMUNDUS_NO_RESULT');
		}

		/* Get the values from the state object that were inserted in the model's construct function */
		$this->lists          = $lists;
		$this->pagination     = $this->m_files->getPagination();
		$this->pageNavigation = $this->m_files->getPageNavigation();
		$this->users          = $users;
		$this->datas          = $data;

		$this->submitForm = EmundusHelperJavascript::onSubmitForm();
		$this->delayAct   = EmundusHelperJavascript::delayAct();
		$this->accessObj  = $objAccess;
		$this->colsSup    = $colsSup;

		parent::display($tpl);
	}

}

?>

