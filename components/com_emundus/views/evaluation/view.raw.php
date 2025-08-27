<?php
/**
 * @package    eMundus
 * @subpackage Components
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// no direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * HTML View class for the Emundus Component
 *
 * @package    Emundus
 */
class EmundusViewEvaluation extends JViewLegacy
{
	private $app;
	protected $_user;

	protected $itemId;
	protected $actions;
	protected $items;
	protected $display;
	protected $cfnum;
	protected $code;
	protected $fnum_assoc;
	protected $form_url_edit;
	protected $datas;
	protected $colsSup;
	protected $accessObj;
	protected $pageNavigation;
	protected $users;
	protected $formid;
	protected $lists;
	protected $pagination;
	protected bool $use_module_for_filters = true;
	protected bool $open_file_in_modal = false;
	protected string $modal_ratio = '66/33';

	protected $modal_tabs = null;

	/** FILTERS */
	protected $applied_filters;
	protected $filters;
	protected $quick_search_filters;
	protected int $count_filter_values;
	protected int $allow_add_filter;

	public function __construct($config = array())
	{
		require_once(JPATH_ROOT . '/components/com_emundus/helpers/list.php');
		require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
		require_once(JPATH_ROOT . '/components/com_emundus/helpers/emails.php');
		require_once(JPATH_ROOT . '/components/com_emundus/helpers/export.php');
		require_once(JPATH_ROOT . '/components/com_emundus/helpers/filters.php');
		require_once(JPATH_ROOT . '/components/com_emundus/helpers/date.php');
		require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
		require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
		require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');

		$this->app   = Factory::getApplication();
		$this->_user = $this->app->getIdentity();

		$menu = $this->app->getMenu();
		if (!empty($menu))
		{
			$current_menu = $menu->getActive();
			if (!empty($current_menu))
			{
				$menu_params              = $menu->getParams($current_menu->id);
				$this->open_file_in_modal = boolval($menu_params->get('em_open_file_in_modal', 0));

				if ($this->open_file_in_modal)
				{
					$this->modal_ratio = $menu_params->get('em_modal_ratio', '66/33');
				}
			}
		}

		parent::__construct($config);
	}

	public function display($tpl = null)
	{
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		$jinput       = $this->app->getInput();
		$this->itemId = $jinput->getInt('Itemid', null);

		$menu         = $this->app->getMenu();
		$current_menu = $menu->getActive();
		$menu_params  = $menu->getParams($current_menu->id);

		$columnSupl              = explode(',', $menu_params->get('em_other_columns'));
		$show_evaluator          = $menu_params->get('em_show_evaluator', 1);
		$display_state_column    = $menu_params->get('em_display_state_column', 1);
		$display_associated_date = $menu_params->get('em_display_associated_date_column', 1);
		$layout                  = $jinput->getString('layout', 0);

		$m_files = new EmundusModelFiles();

		switch ($layout)
		{
			case 'menuactions':
				$this->display = $jinput->getString('display', 'none');

				$items   = EmundusHelperFiles::getMenuList($menu_params);
				$actions = $m_files->getAllActions();

				$menuActions = array();
				foreach ($items as $item)
				{
					if (!empty($item->note))
					{
						$note = explode('|', $item->note);
						if ($actions[$note[0]][$note[1]] == 1)
						{
							$actions[$note[0]]['multi'] = $note[2];
							$actions[$note[0]]['grud']  = $note[1];
							$item->action               = $actions[$note[0]];
							$menuActions[]              = $item;
						}
					}
					else
					{
						$menuActions[] = $item;
					}
				}

				$this->items = $menuActions;
				break;

			default :
				$this->cfnum = $jinput->getString('cfnum', null);

				$params                        = JComponentHelper::getParams('com_emundus');
				$evaluators_can_see_other_eval = $params->get('evaluators_can_see_other_eval', 0);

				$m_evaluation = $this->getModel('Evaluation');
				$h_files      = new EmundusHelperFiles();
				$m_files      = new EmundusModelFiles();
				$m_user       = new EmundusModelUsers();

				$m_evaluation->code = $m_user->getUserGroupsProgrammeAssoc($this->_user->id);

				// get all fnums manually associated to user
				$groups                   = $m_user->getUserGroups($this->_user->id, 'Column');
				$fnum_assoc_to_groups     = $m_user->getApplicationsAssocToGroups($groups);
				$fnum_assoc               = $m_user->getApplicantsAssoc($this->_user->id);
				$m_evaluation->fnum_assoc = array_merge($fnum_assoc_to_groups, $fnum_assoc);
				$this->code               = $m_evaluation->code;
				$this->fnum_assoc         = $m_evaluation->fnum_assoc;

				// reset filter
				$this->filters = EmundusHelperFiles::resetFilter();

				// Do not display photos unless specified in params
				$displayPhoto = false;

				if (!empty($m_evaluation->fnum_assoc) || !empty($m_evaluation->code))
				{
					// get applications files
					$this->users = $m_evaluation->getUsers($this->cfnum);
				}
				else
				{
					$this->users = array();
				}

				// Get elements from model and proccess them to get an easy to use array containing the element type
				$elements = $m_evaluation->getElementsVar();
				if (count($elements) > 0)
				{
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
				}

				if (isset($eltarr))
				{
					$elements = $eltarr;
				}

				// Columns
				$defaultElements              = $this->get('DefaultElements');
				$this->datas                  = array(array('check' => '#', 'fnum' => Text::_('COM_EMUNDUS_FILES_APPLICATION_FILES'), 'status' => Text::_('COM_EMUNDUS_STATUS')));
				$fl                           = array();
				$fl['evaluations_step_label'] = Text::_('COM_EMUNDUS_EVALUATION_EVAL_STEP');
				if ($show_evaluator)
				{
					$fl['evaluator'] = Text::_('COM_EMUNDUS_EVALUATION_EVALUATOR');
				}
				if ($display_state_column == 1)
				{
					$fl['evaluated'] = Text::_('COM_EMUNDUS_EVALUATION_IS_EVALUATED');
				}
				if ($display_associated_date == 1)
				{
					$fl['associated_date'] = Text::_('COM_EMUNDUS_ASSOCIATED_DATE');
				}

				// Get eval crieterion
				if (count($defaultElements) > 0)
				{
					foreach ($defaultElements as $key => $elt)
					{
						$fl[$elt->tab_name . '___' . $elt->element_name] = $elt->element_label;
					}
				}

				// merge eval criterion on application files
				$this->datas[0] = array_merge($this->datas[0], $fl);

				$fnumArray = array();

				if (!empty($this->users))
				{

					$taggedFile = array();
					foreach ($columnSupl as $col)
					{
						$col = explode('.', $col);
						switch ($col[0])
						{
							case 'evaluators':
								$this->datas[0]['EVALUATORS'] = Text::_('COM_EMUNDUS_EVALUATION_EVALUATORS');
								$this->colsSup['evaluators']  = $h_files->createEvaluatorList($col[1], $m_evaluation);
								break;
							case 'overall':
								$this->datas[0]['overall'] = Text::_('COM_EMUNDUS_EVALUATIONS_OVERALL');
								$this->colsSup['overall']  = array();
								break;
							case 'tags':
								$taggedFile               = $m_evaluation->getTaggedFile();
								$this->datas[0]['id_tag'] = Text::_('COM_EMUNDUS_TAGS');
								$this->colsSup['id_tag']  = array();
								break;
							case 'access':
								$this->datas[0]['access'] = Text::_('COM_EMUNDUS_ASSOCIATED_TO');
								$this->colsSup['access']  = array();
								break;
							case 'photos':
								$displayPhoto = true;
								break;
							case 'form_progress':
								$this->datas[0]['form_progress'] = Text::_('COM_EMUNDUS_FORM_PROGRESS');
								$this->colsSup['form_progress'] = array();
								break;
							case 'attachment_progress':
								$this->datas[0]['attachment_progress'] = Text::_('COM_EMUNDUS_ATTACHMENT_PROGRESS');
								$this->colsSup['attachment_progress'] = array();
								break;
							case 'unread_messages':
								$this->datas[0]['unread_messages'] = Text::_('COM_EMUNDUS_UNREAD_MESSAGES');
								$this->colsSup['unread_messages'] = array();
								break;
							case 'commentaire':
								$this->datas[0]['commentaire'] = Text::_('COM_EMUNDUS_COMMENTAIRE');
								$this->colsSup['commentaire'] = array();
								break;
							case 'module':
								// Get every module without a positon.
								$mod_emundus_custom = array();
								foreach (JModuleHelper::getModules('') as $module)
								{
									if ($module->module == 'mod_emundus_custom' && ($module->menuid == 0 || $module->menuid == $jinput->get('Itemid', null)))
									{
										$mod_emundus_custom[$module->title] = $module->content;
										$this->datas[0][$module->title]     = Text::_($module->title);
										$this->colsSup[$module->title]      = array();
									}
								}
								break;
							default:
								break;
						}
					}

					$i = 0;

					$m_workflow = new EmundusModelWorkflow();

					$unread_messages   = array();
					if(!class_exists(JPATH_SITE.'/components/com_emundus/models/messenger.php'))
					{
						require_once JPATH_SITE . '/components/com_emundus/models/messenger.php';
					}
					$m_messenger = new EmundusModelMessenger();
					if($m_messenger->checkMessengerState())
					{
						$unread_messages[] = $m_files->getUnreadMessages($this->_user->id);
						$unread_messages   = $h_files->createUnreadMessageList($unread_messages[0]);
						$keys              = array_keys($unread_messages);
						natsort($keys);
					}

					foreach ($this->users as $user)
					{
						$usObj       = new stdClass();
						$usObj->val  = 'X';
						$fnumArray[] = $user['fnum'];

						// get evaluation form ID

						if (!empty($user['evaluations_step_id']))
						{
							$step_data = $m_workflow->getStepData($user['evaluations_step_id']);
						}
						else
						{
							$step_data = [];
						}

						$current_row_form = $this->formid;
						if (!empty($step_data))
						{
							$ccid = EmundusHelperFiles::getIdFromFnum($user['fnum']);

							if ($display_state_column == 1)
							{
								$user['evaluated'] = $m_workflow->isEvaluated($step_data, $this->_user->id, $ccid) ? Text::_('COM_EMUNDUS_EVALUATION_EVALUATED') : Text::_('COM_EMUNDUS_EVALUATION_TO_EVALUATE');
							}

							$current_row_form    = $step_data->form_id;
							$form_url_view       = 'evaluation-step-form?view=details&formid=' . $step_data->form_id . '&tmpl=component&iframe=1&' . $step_data->table . '___ccid=' . $ccid . '&' . $step_data->table . '___step_id=' . $step_data->id . '&rowid=';
							$this->form_url_edit = 'evaluation-step-form?formid=' . $step_data->form_id . '&tmpl=component&iframe=1&' . $step_data->table . '___ccid=' . $ccid . '&' . $step_data->table . '___step_id=' . $step_data->id . '&rowid=';
						}
						else
						{
							if ($display_state_column == 1)
							{
								$user['evaluated'] = Text::_('COM_EMUNDUS_EVALUATION_TO_EVALUATE');
							}
							$form_url_view       = '';
							$this->form_url_edit = '';
						}

						if ($display_associated_date == 1)
						{
							$associated_date         = $m_files->getAssociatedDate($user['fnum'], $this->_user->id);
							$user['associated_date'] = !empty($associated_date) ? EmundusHelperDate::displayDate($associated_date, 'DATE_FORMAT_LC3') : '';
						}

						$line = array('check' => $usObj);

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
								else
								{
									$userObj->photo = "";
								}
								$userObj->user       = JFactory::getUser((int) substr($value, -7));
								$userObj->user->name = $user['name'];
								$userObj->unread_messages = !empty($unread_messages) ? $unread_messages[$value] : '';

								$line['fnum']        = $userObj;

							}
							elseif ($key == 'name' || $key == 'status_class' || $key == 'step' || $key == 'code' || $key == 'applicant_id' || $key == 'campaign_id' || $key == 'unread_messages' || $key == 'commentaire')
							{
								continue;
							}
							elseif ($key == 'evaluator' && $show_evaluator)
							{
								if ($current_row_form > 0 && !empty($value))
								{
									$action_id = 5;
									if (isset($user['evaluations_step_id']))
									{
										$step_data = $m_workflow->getStepData($user['evaluations_step_id']);

										if (!empty($step_data))
										{
											$action_id = $step_data->action_id;
										}
									}

									$link_view     = '';
									$link_edit     = '';
									$delete_button = '';

									if ($evaluators_can_see_other_eval || EmundusHelperAccess::asAccessAction($action_id, 'r', $this->_user->id))
									{
										$link_view = '<a href="' . $form_url_view . $user['evaluation_id'] . '" target="_blank" data-remote="' . $form_url_view . $user['evaluation_id'] . '" id="em_form_eval_' . $i . '-' . $user['evaluation_id'] . '"><span class="material-symbols-outlined tw-cursor-pointer" title="' . Text::_('COM_EMUNDUS_DETAILS') . '">visibility</span></a>';
									}

									if (EmundusHelperAccess::asAccessAction($action_id, 'u', $this->_user->id) || (EmundusHelperAccess::asAccessAction($action_id, 'c', $this->_user->id) && $user['evaluator_id'] == $this->_user->id))
									{
										$link_edit = '<a href="' . $this->form_url_edit . $user['evaluation_id'] . '" target="_blank"><span class="material-symbols-outlined tw-cursor-pointer" title="' . Text::_('COM_EMUNDUS_ACTIONS_EDIT') . '">edit</span></a>';
									}

									if (EmundusHelperAccess::asAccessAction($action_id, 'd', $this->_user->id))
									{
										$delete_button = '<span 
											title="' . Text::_("COM_EMUNDUS_EVALUATIONS_DELETE_SELECTED_EVALUATIONS") . '"
											id="delete_evaluation" 
											class="material-symbols-outlined tw-cursor-pointer"
											data-fnum="' . $user['fnum'] . '"
											data-step_id="' . $user['evaluations_step_id'] . '"
											data-row_id="' . $user['evaluation_id'] . '"
										>delete_outline</span>';
									}

									$userObj->val = $link_view . ' ' . $link_edit . ' ' . $delete_button . ' ' . $value;
								}
								else
								{
									$userObj->val = $value;
								}

								$userObj->type     = 'html';
								$line['evaluator'] = $userObj;

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

						if (isset($this->colsSup) && is_array($this->colsSup) && count(@$this->colsSup) > 0)
						{

							foreach ($this->colsSup as $key => $obj)
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
								elseif (!empty($mod_emundus_custom) && array_key_exists($key, $mod_emundus_custom))
								{
									$line[$key] = "";
								}
							}
						}
						$this->datas[$line['fnum']->val . '-' . $i] = $line;
						if (!$show_evaluator && !empty($this->datas[$line['fnum']->val . '-' . $i]['evaluator']))
						{
							unset($this->datas[$line['fnum']->val . '-' . $i]['evaluator']);
						}
						$i++;
					}

					if (isset($this->colsSup['overall']))
					{
						$this->colsSup['overall'] = $m_evaluation->getEvaluationAverageByFnum($fnumArray);
					}

					if (isset($this->colsSup['id_tag']))
					{
						$tags                    = $m_files->getTagsByFnum($fnumArray);
						$this->colsSup['id_tag'] = EmundusHelperFiles::createTagsList($tags);
					}

					if (isset($this->colsSup['access']))
					{
						$this->accessObj = $m_files->getAccessorByFnums($fnumArray);
					}

					if (isset($this->colsSup['form_progress']))
					{
						$forms_progress           = $m_files->getFormProgress($fnumArray);
						$this->colsSup['form_progress'] = $h_files->createFormProgressList($forms_progress);
					}

					if (isset($this->colsSup['attachment_progress']))
					{
						$attachments_progress           = $m_files->getAttachmentProgress($fnumArray);
						$this->colsSup['attachment_progress'] = $h_files->createAttachmentProgressList($attachments_progress);
					}

					if (isset($this->colsSup['commentaire']))
					{
						foreach ($fnumArray as $fnum)
						{
							$notifications_comments        = sizeof($m_files->getCommentsByFnum([$fnum]));
							$this->colsSup['commentaire'][$fnum] = '<p class="messenger__notifications_counter">' . $notifications_comments . '</p> ';
						}
					}

					if (!empty($mod_emundus_custom))
					{
						foreach ($mod_emundus_custom as $key => $module)
						{
							if (isset($this->colsSup[$key]))
							{
								$this->colsSup[$key] = $h_files->createHTMLList($module, $fnumArray);
							}
						}
					}

				}
				else
				{
					$this->datas = Text::_('COM_EMUNDUS_NO_RESULT');
				}

				/* Get the values from the state object that were inserted in the model's construct function */
				$this->lists['order_dir'] = $this->app->getSession()->get('filter_order_Dir');
				$this->lists['order']     = $this->app->getSession()->get('filter_order');
				$this->pagination         = $this->get('Pagination');
				$this->pageNavigation     = $this->get('PageNavigation');

				$tabs      = [];
				$menu_tabs = $menu_params->get('modal_tabs');
				foreach ($menu_tabs as $tab)
				{
					$name   = $tab->tab_type == 'component' ? $tab->tab_component : 'custom-' . $tab->tab_label;
					$access = 1;

					if ($tab->tab_type == 'component')
					{
						switch ($tab->tab_component)
						{
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
						'label'  => $tab->tab_name,
						'type'   => $tab->tab_type,
						'name'   => $name,
						'url'    => $tab->tab_url,
						'access' => $access,
					];
				}
				$this->modal_tabs = base64_encode(json_encode($tabs));
				break;
		}

		parent::display($tpl);
	}

}


