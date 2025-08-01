<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 * @copyright   Copyright (C) 2016 eMundus. All rights reserved.
 * @license     GNU/GPL
 * @author      James Dean
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

require_once(JPATH_ROOT  . '/components/com_emundus/models/formbuilder.php');

/**
 * FormBuilder Controller
 *
 * @package    Joomla
 * @subpackage eMundus
 * @since      5.0.0
 */
class EmundusControllerFormbuilder extends BaseController
{

	protected $app;

	private $user;
	private EmundusModelFormbuilder $m_formbuilder;

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

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'fabrik.php');

		$this->app           = Factory::getApplication();
		$this->user          = $this->app->getIdentity();
		$this->m_formbuilder = new EmundusModelFormbuilder();
	}

	public function updateOrder()
	{
		$update = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$elements = $this->input->getString('elements');
			$elements = json_decode($elements, true);
			$group_id = $this->input->getInt('group_id');
			$moved_el = $this->input->getString('moved_el');
			$moved_el = json_decode($moved_el, true);

			if (empty($moved_el)) {
				$update['msg'] = Text::_('INVALID_PARAMETERS');
			}
			else {
				$update['status'] = $this->m_formbuilder->updateOrder($elements, $group_id, $this->user->id, $moved_el);
				$update['msg']    = $update['status'] ? Text::_('SUCCESS') : Text::_('FAILURE');
			}
		}

		echo json_encode((object) $update);
		exit;
	}

	public function updateelementorder()
	{
		$return = array(
			'status' => 0,
			'msg'    => Text::_("INVALID_PARAMETERS")
		);

		$user = $this->app->getIdentity();
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$return['msg'] = Text::_("ACCESS_DENIED");
		}
		else {

			$group_id   = $this->input->getInt('group_id');
			$element_id = $this->input->getInt('element_id');
			$new_index  = $this->input->getInt('new_index', 0);

			if (empty($group_id) || empty($element_id)) {
				$return['msg'] = Text::_("INVALID_PARAMETERS " . $group_id . " " . $element_id . " " . $new_index);
			}
			else {
				$return = $this->m_formbuilder->updateElementOrder($group_id, $element_id, $new_index);
			}
		}

		echo json_encode((object) $return);
		exit;
	}

	public function updategroupparams()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$group_id = $this->input->getInt('group_id');
			$params   = $this->input->getRaw('params');
			$params = json_decode($params, true);
			$lang     = $this->input->getString('lang', '');

			if (!empty($params)) {
				$response = array(
					'status' => 1,
					'data'   => $this->m_formbuilder->updateGroupParams($group_id, $params, $lang)
				);
			} else {
				$response['msg'] = Text::_('MISSING_PARAMS');
				JLog::add('Nothing to update in group params', JLog::WARNING, 'com_emundus');
			}
		}
		echo json_encode($response);
		exit;
	}

	public function changerequire()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$element = $this->input->getRaw('element');

			$changeresponse = $this->m_formbuilder->ChangeRequire($element, $user->id);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}


	public function publishunpublishelement()
	{
		$update = array('status' => false, 'msg' => '');
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$update['msg'] = Text::_("ACCESS_DENIED");
		}
		else {
			$element = $this->input->getInt('element');

			$update['status'] = $this->m_formbuilder->publishUnpublishElement($element);
		}

		echo json_encode((object) $update);
		exit;
	}

	public function hiddenunhiddenelement()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$update = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$element = $this->input->getInt('element');

			$update = $this->m_formbuilder->hiddenUnhiddenElement($element);
		}
		echo json_encode((object) $update);
		exit;
	}


	public function updateparams()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$element = $this->input->getRaw('element');
			$element = json_decode($element, true);

			$changeresponse = $this->m_formbuilder->UpdateParams($element, $user->id);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function duplicateelement()
	{
		$response = array('status' => 0, 'msg' => Text::_("ACCESS_DENIED"));
		$user = $this->app->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$eid       = $this->input->getInt('id');
			$group     = $this->input->getInt('group');
			$old_group = $this->input->getInt('old_group');
			$form_id   = $this->input->getInt('form_id');

			$response = $this->m_formbuilder->duplicateElement($eid, $group, $old_group, $form_id);
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Update translations of an element
	 *
	 * @throws Exception
	 */
	public function formsTrad()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];
		$user     = $this->app->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$element     = $this->input->getInt('element', null);
			$group       = $this->input->getInt('group', null);
			$page        = $this->input->getInt('page', null);
			$labelTofind = $this->input->getString('labelTofind');
			$newLabel    = $this->input->getRaw('NewSubLabel');

			if (!empty($labelTofind) && !empty($newLabel)) {
				$results = $this->m_formbuilder->formsTrad($labelTofind, $newLabel, $element, $group, $page);

				if (!empty($results)) {
					$response = ['status' => true, 'msg' => 'Traductions effectués avec succès', 'data' => $results];
				}
				else {
					$response['msg'] = Text::_('NO_TRANSLATION_FOUND');
				}
			}
			else {
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function updateelementlabelwithouttranslation()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$eid   = $this->input->getInt('eid');
			$label = $this->input->getString('label');

			$changeresponse = $this->m_formbuilder->updateElementWithoutTranslation($eid, $label);
		}

		echo json_encode((object) $changeresponse);
		exit;
	}

	public function updategrouplabelwithouttranslation()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$gid   = $this->input->getInt('gid');
			$label = $this->input->getString('label');

			$changeresponse = $this->m_formbuilder->updateGroupWithoutTranslation($gid, $label);
		}

		echo json_encode((object) $changeresponse);
		exit;
	}

	public function updatepagelabelwithouttranslation()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$pid   = $this->input->getInt('pid');
			$label = $this->input->getString('label');

			$changeresponse = $this->m_formbuilder->updatePageWithoutTranslation($pid, $label);
		}

		echo json_encode((object) $changeresponse);
		exit;
	}

	public function updatepageintrowithouttranslation()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$pid   = $this->input->getInt('pid');
			$intro = $this->input->getString('label');

			$changeresponse = $this->m_formbuilder->updatePageIntroWithoutTranslation($pid, $intro);
		}

		echo json_encode((object) $changeresponse);
		exit;
	}

    public function getJTEXTA() {
        $response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));
        $user = $this->app->getIdentity();

        if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
            $toJTEXT = $this->input->getString('toJTEXT');
            $response['data'] = $this->m_formbuilder->getJTEXTA($toJTEXT);

			if (!empty($response['data'])) {
				$response['status'] = true;
				$response['msg'] = Text::_('SUCCESS');
			} else {
				$response['msg'] = Text::_('NO_TRANSLATION_FOUND');
			}
        }

		echo json_encode((object) $response);
		exit;
	}

	public function getJTEXT()
	{


		$toJTEXT = $this->input->getString('toJTEXT');

		$getJtext = $this->m_formbuilder->getJTEXT($toJTEXT);

		echo json_encode((string) $getJtext);
		exit;
	}

	public function getalltranslations()
	{
		$response = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));
		$user = $this->app->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$toJTEXT = $this->input->getString('toJTEXT');

			$languages = JLanguageHelper::getLanguages();

			$data = new stdClass();
			foreach ($languages as $language) {
				$data->{$language->sef} = $this->m_formbuilder->getTranslation($toJTEXT,$language->lang_code);
			}

			$response['data'] = $data;
			$response['status'] = 1;
			$response['msg'] = Text::_('SUCCESS');
		}

		echo json_encode((object)$response);
		exit;
	}

    public function createMenu()
    {
        $user = $this->app->getIdentity();
        $response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {

			$label    = $this->input->getRaw('label');
			$intro    = $this->input->getRaw('intro');
			$prid     = $this->input->getInt('prid');
			$modelid  = $this->input->getInt('modelid');
			$template = $this->input->getString('template');

			$label = json_decode($label, true);
			$intro = json_decode($intro, true);
			if ($modelid != -1) {
				$keep_structure = $this->input->getString('keep_structure') == 'true';
				$response       = $this->m_formbuilder->createMenuFromTemplate($label, $intro, $modelid, $prid, $keep_structure);
			}
			else {
				$response = $this->m_formbuilder->createApplicantMenu($label, $intro, $prid, $template);
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function checkifmodeltableisusedinform()
	{
		$user     = $this->app->getIdentity();
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {

			$model_id   = $this->input->getInt('model_id', 0);
			$profile_id = $this->input->getInt('profile_id', 0);

			if (!empty($model_id) && !empty($profile_id)) {
				$response['data']   = $this->m_formbuilder->checkIfModelTableIsUsedInForm($model_id, $profile_id);
				$response['status'] = true;
				$response['msg']    = '';
			}
			else {
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Delete a page of a form
	 *
	 * @since version 1.0.0
	 */
	public function deletemenu()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$mid = $this->input->getInt('mid');

			$response['status'] = $this->m_formbuilder->deleteMenu($mid);
		}

		echo json_encode((object) $response);
		exit;
	}


	public function savemenuastemplate()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$menu     = $this->input->getRaw('menu');
			$template = $this->input->getString('template');

			$changeresponse = $this->m_formbuilder->saveAsTemplate($menu, $template);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}


	public function createsimplegroup()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));
		$user     = $this->app->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {


			$fid = $this->input->getInt('fid');
			$mode = $this->input->getString('mode');
			if ($this->input->getRaw('label')) {
				$label = $this->input->getRaw('label');
			}
			else {
				$label = array(
					'fr' => 'Nouveau groupe',
					'en' => 'New group'
				);
			}

			$group = $this->m_formbuilder->createGroup($label, $fid,1,$mode);

			if (!empty($group['group_id'])) {
				$response           = $group;
				$response['status'] = true;
			}
		}
		echo json_encode((object) $response);
		exit;
	}


	public function deleteGroup()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$gid = $this->input->getInt('gid');

			$changeresponse = $this->m_formbuilder->deleteGroup($gid);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function getElement()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$element = $this->input->getInt('element');
			$gid     = $this->input->getInt('gid');

			$changeresponse = $this->m_formbuilder->getElement($element, $gid);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function retriveElementFormAssociatedDoc()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
		{
			$docid = $this->input->getInt('docid');
			$gid   = $this->input->getInt('gid');

			$response = $this->m_formbuilder->retriveElementFormAssociatedDoc($gid, $docid);
		}
		echo json_encode((object) $response);
		exit;
	}


	public function createsimpleelement()
	{
		$user     = $this->app->getIdentity();
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$response['msg'] = Text::_('MISSING_PLUGIN_OR_GROUP');


			$gid    = $this->input->getInt('gid');
			$plugin = $this->input->getString('plugin');

			if (!empty($plugin) && !empty($gid)) {
				$mode       = $this->input->getString('mode');
				$evaluation = $mode == 'eval';
				if ($this->input->getString('attachmentId')) {
					$attachmentId = $this->input->getString('attachmentId');
				}

				if (isset($attachmentId)) {
					$response['data'] = $this->m_formbuilder->createSimpleElement($gid, $plugin, $attachmentId, $evaluation);
				}
				else {
					$response['data'] = $this->m_formbuilder->createSimpleElement($gid, $plugin, 0, $evaluation);
				}

				if (!empty($response['data'])) {
					$response['status'] = true;
					$response['msg']    = Text::_('COM_EMUNDUS_FORMBUILDER_ELEMENT_CREATED');
				}
				else {
					$response['msg'] = Text::_('COM_EMUNDUS_FORMBUILDER_ELEMENT_NOT_CREATED');
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function createsectionsimpleelements() {
		$user = $this->app->getIdentity();
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$jinput = JFactory::getApplication()->input;
			$gid = $jinput->getInt('gid', 0);
			$fid = $jinput->getInt('fid', 0);
			$mode = $jinput->getString('mode', 'form');
			$evaluation = $mode == 'eval';
			$section_to_insert = array();
			$elements = array();

			if(!empty($gid) && !empty($fid)) {

				if (is_file(JPATH_ROOT . '/components/com_emundus/data/form-builder/form-builder-sections.json')) {
					$sections_available = json_decode(file_get_contents(JPATH_ROOT . '/components/com_emundus/data/form-builder/form-builder-sections.json'), true);

					if (!empty($sections_available)) {
						foreach ($sections_available as $section) {
							if ($section['id'] == $gid) {
								$section_to_insert = $section;
								break;
							}
						}
					}
				}

				if (!empty($section_to_insert)) {
					$elements = $section_to_insert['elements'];
				}

				if (!empty($elements)) {
					$group = $this->m_formbuilder->createGroup($section_to_insert['labels'], $fid);

					if(!empty($group['group_id'])) {
						$elements_created = [];
						foreach ($elements as $element) {
							$labels = !empty($element['labels']) ? $element['labels'] : null;
							$elementId = $this->m_formbuilder->createSimpleElement($group['group_id'], $element['value'], 0, $evaluation, $labels);

							if(!empty($elementId)) {
								$response['data'][] = $elementId;
								$new_element = $this->m_formbuilder->getSimpleElement($elementId);

								if(!empty($element['params'])) {
									$new_element['params'] = json_decode($new_element['params'], true);
									$new_element['params'] = array_merge($new_element['params'], $element['params']);
									$new_element['FRequire'] = !empty($element['required']) ? $element['required'] : 'true';

									$this->m_formbuilder->updateParams($new_element, $user->id);
								}

								if(!empty($element['options'])) {
									$this->m_formbuilder->deleteElementSubOption($elementId,0);
									foreach ($element['options'] as $option) {
										$sub_options = $this->m_formbuilder->addElementSubOption($elementId, $option['value'],'fr');

										if(!empty($sub_options)) {
											$this->m_formbuilder->updateTranslation($sub_options['sub_labels'][sizeof($sub_options['sub_labels'])-1], $option['labels'], 'fabrik_elements',$elementId);
										}
									}
								}


								$elements_created[] = $new_element;
							}
						}

						foreach ($elements as $key => $element) {
							if(!empty($element['jsactions'])) {
								$re = '/\$\d/m';

								preg_match_all($re, $element['jsactions']['code'], $matches, PREG_SET_ORDER, 0);

								if(!empty($matches[0])) {
									foreach ($matches[0] as $match) {
										$index = str_replace('$','',$match);
										$element['jsactions']['code'] = str_replace($match,$elements_created[(int)$index]['name'],$element['jsactions']['code']);
									}
								}

								EmundusHelperFabrik::addJsAction($elements_created[$key]['id'], $element['jsactions']);
							}
						}
					} else {
						$response['msg'] = Text::_('GROUP_NOT_CREATED');
					}

					if (!empty($response['data'])) {
						$response['status'] = true;
						$response['msg']    = Text::_('ELEMENTS_CREATED');
					}
					else {
						$response['msg'] = Text::_('ELEMENTS_NOT_CREATED');
					}
				}
				else {
					$response['msg'] = Text::_('NO_ELEMENTS_AVAILABLE');
				}
			} else {
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object)$response);
		exit;
	}

	public function createcriteria()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$gid    = $this->input->getInt('gid');
			$plugin = $this->input->getString('plugin');

			$changeresponse = $this->m_formbuilder->createSimpleElement($gid, $plugin, null, 1);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}


	public function deleteElement()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$element = $this->input->getInt('element');

			$changeresponse = $this->m_formbuilder->deleteElement($element);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}


	public function reordermenu()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));
		$user     = $this->app->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {

			$menus   = json_decode($_POST['menus']);
			$profile = $this->input->getInt('profile');

			if (!empty($profile)) {
				$response['status'] = $this->m_formbuilder->reorderMenu($menus, $profile);
				$response['msg']    = $response['status'] ? Text::_('MENU_ORDER_UPDATED') : Text::_('MENU_ORDER_NOT_UPDATED');
			}
			else {
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}


	public function getGroupOrdering()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$gid = $this->input->getInt('gid');
			$fid = $this->input->getInt('fid');

			$changeresponse = $this->m_formbuilder->getGroupOrdering($gid, $fid);
		}
		echo $changeresponse;
		exit;
	}

	public function reordergroups()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {

			$groups = $this->input->getString('groups');
			$fid    = $this->input->getInt('fid');

			if (!empty($groups)) {
				$groups = json_decode($groups, true);

				foreach ($groups as $group) {
					$changeresponse[] = $this->m_formbuilder->reorderGroup($group['id'], $fid, $group['order']);
				}
			}
		}

		echo json_encode((object) $changeresponse);
		exit;
	}

	public function getPagesModel()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {
			$changeresponse = $this->m_formbuilder->getPagesModel();
		}

		echo json_encode((object) $changeresponse);
		exit;
	}

	public function checkconstraintgroup()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$cid = $this->input->getInt('cid');

			$visibility = $this->m_formbuilder->checkConstraintGroup($cid);

			$tab = array('status' => 1, 'msg' => 'worked', 'data' => $visibility);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function checkvisibility()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$group = $this->input->getInt('group');
			$cid   = $this->input->getInt('cid');

			$visibility = $this->m_formbuilder->checkVisibility($group, $cid);

			$tab = array('status' => 1, 'msg' => 'worked', 'data' => $visibility);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getdatabasesjoin()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {
			$databases = $this->m_formbuilder->getDatabasesJoin();

			$tab = array('status' => 1, 'msg' => 'worked', 'data' => $databases);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getDatabaseJoinOrderColumns()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {

			$database_name = $this->input->getString('database_name');

			if (!empty($database_name)) {
				$database_name_columns = $this->m_formbuilder->getDatabaseJoinOrderColumns($database_name);
				$tab                   = array('status' => 1, 'msg' => 'worked', 'data' => $database_name_columns);
			}
			else {
				$tab = array('status' => 0, 'msg' => 'Missing database_name parameter');
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function enablegrouprepeat()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$gid = $this->input->getInt('gid');

			$state = $this->m_formbuilder->enableRepeatGroup($gid);

			$tab = array('status' => $state, 'msg' => 'worked');
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function disablegrouprepeat()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$gid = $this->input->getInt('gid');

			$state = $this->m_formbuilder->disableRepeatGroup($gid);

			$tab = array('status' => $state, 'msg' => 'worked');
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function displayhidegroup()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$gid = $this->input->getInt('gid');

			$state = $this->m_formbuilder->displayHideGroup($gid);

			$tab = array('status' => $state, 'msg' => 'worked');
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function updatemenulabel()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$label = $this->input->getRaw('label');
			$pid   = $this->input->getString('pid');

			$state = $this->m_formbuilder->updateMenuLabel($label, $pid);

			$tab = array('status' => $state, 'msg' => 'worked');
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function gettestingparams()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$prid = $this->input->getInt('prid');

			$campaign_files = $this->m_formbuilder->getFormTesting($prid, $user->id);

			$tab = array('status' => true, 'user' => $user, 'campaign_files' => $campaign_files);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function createtestingfile()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$cid = $this->input->getInt('cid');

			$fnum = $this->m_formbuilder->createTestingFile($cid, $user->id);

			$tab = array('status' => true, 'fnum' => $fnum);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function deletetestingfile()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$fnum = $this->input->getString('file');

			$status = $this->m_formbuilder->deleteFormTesting($fnum, $user->id);

			$tab = array('status' => $status, 'userid' => $user->id);
		}
		echo json_encode((object) $tab);
		exit;
	}

    public function updatedocument()
    {
        $response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));
	    $user_id = Factory::getApplication()->getIdentity()->id;

        if (EmundusHelperAccess::asCoordinatorAccessLevel($user_id)) {
            $document_id = $this->input->getInt('document_id');
            $profile_id = $this->input->getInt('profile_id');
            $document = $this->input->getString('document');
            $document = json_decode($document, true);

            if (!empty($document_id) && !empty($document) && !empty($profile_id)) {
	            $types = $this->input->getString('types');
	            $types = json_decode($types, true);
	            $params = ['has_sample' => $this->input->getInt('has_sample', 0)];

	            if ($params['has_sample'] && !empty($_FILES['file'])) {
		            $params['file'] = $_FILES['file'];
	            }

	            require_once JPATH_SITE . '/components/com_emundus/models/campaign.php';
                $m_campaign = $this->getModel('Campaign');

				$result = $m_campaign->updateDocument($document, $types, $document_id, $profile_id, $params);

                if ($result) {
	                $response['status'] = true;
	                $response['msg'] = 'SUCCESS';
                }
            } else {
				$response['msg'] = Text::_('ERROR');
            }
        }

		echo json_encode((object) $response);
		exit;
	}

	public function updatedefaultvalue()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$eid   = $this->input->getInt('eid');
			$value = $this->input->getRaw('value');

			$status = $this->m_formbuilder->updateDefaultValue($eid, $value);

			$tab = array('status' => $status, 'userid' => $user->id);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getsection()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {
			$section = $this->input->getInt('section');

			$group = $this->m_formbuilder->getSection($section);

			$tab = array('status' => true, 'group' => $group);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function updateElementOption()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {

			$element        = $this->input->getInt("element");
			$options        = json_decode($this->input->getString("options"), true);
			$index          = $this->input->getInt("index");
			$newTranslation = $this->input->getString("newTranslation");
			$lang           = $this->input->getString("lang");

			if (!empty($element) && !empty($options) && $newTranslation !== '') {
				$translated = $this->m_formbuilder->updateElementOption($element, $options, $index, $newTranslation, $lang);
				$tab        = array('status' => $translated);
			}
			else {
				$tab = array('status' => false, 'msg' => "MISSING_PARAMETERS");
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function getelementsuboptions()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {

			$element = $this->input->getInt("element");

			if (!empty($element)) {
				$options = $this->m_formbuilder->getElementSubOption($element);
				$tab     = array('status' => !empty($options), 'new_options' => $options);
			}
			else {
				$tab = array('status' => false, 'msg' => "MISSING_PARAMETERS");
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function addElementSubOption()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {

			$element   = $this->input->getInt("element");
			$newOption = $this->input->getString("newOption");
			$lang      = $this->input->getString("lang");

			if (!empty($element) && !empty($newOption)) {
				$options = $this->m_formbuilder->addElementSubOption($element, $newOption, $lang);
				$tab     = array('status' => !empty($options), 'options' => $options);
			}
			else {
				$tab = array('status' => false, 'msg' => "MISSING_PARAMETERS");
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function deleteElementSubOption()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {

			$element = $this->input->getInt("element");
			$index   = $this->input->getInt("index");

			if (!empty($element) && !empty($index)) {
				$deleted = $this->m_formbuilder->deleteElementSubOption($element, $index);
				$tab     = array('status' => $deleted);
			}
			else {
				$tab = array('status' => false, 'msg' => "MISSING_PARAMETERS");
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function updateElementSubOptionsOrder()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {

			$element   = $this->input->getInt("element");
			$old_order = json_decode($this->input->getString("options_old_order"), true);
			$new_order = json_decode($this->input->getString("options_new_order"), true);

			if (!empty($element) && !empty($new_order) && !empty($old_order)) {
				$updated = $this->m_formbuilder->updateElementSubOptionsOrder($element, $old_order, $new_order);
				$tab     = array('status' => $updated);
			}
			else {
				$tab = array('status' => false, 'msg' => "MISSING_PARAMETERS");
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function getpagemodels()
	{
		$user     = $this->app->getIdentity();
		$response = array('status' => false, 'msg' => Text::_("ACCESS_DENIED"));
		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$models             = $this->m_formbuilder->getPagesModel();
			$response['status'] = true;
			$response['data']   = $models;
			$response['msg']    = 'Succès';
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getallmodels()
	{
		$user     = $this->app->getIdentity();
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id)) {
			$models             = $this->m_formbuilder->getPagesModel();
			$response['status'] = true;
			$response['data']   = ['datas' => $models, 'count' => count($models)];
			$response['msg']    = 'Succès';
		}

		echo json_encode((object) $response);
		exit;
	}

	public function addformmodel()
	{
		$user     = $this->app->getIdentity();
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {

			$form_id = $this->input->getInt('form_id');
			$label   = $this->input->getString('label');

			if (!empty($form_id) && !empty($label)) {
				$response['status'] = $this->m_formbuilder->addFormModel($form_id, $label);
				$response['msg']    = $response['status'] ? Text::_('SUCCESS') : Text::_('FAILED');
			}
			else {
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function deleteformmodel()
	{
		$user     = $this->app->getIdentity();
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {

			$form_id = $this->input->getInt('form_id');

			if (!empty($form_id)) {
				$response['status'] = $this->m_formbuilder->deleteFormModel($form_id);
				$response['msg']    = $response['status'] ? Text::_('SUCCESS') : Text::_('FAILED');
			}
			else {
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function deleteformmodelfromids()
	{
		$user     = $this->app->getIdentity();
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {

			$model_ids = $this->input->getString('id');
			$model_ids = json_decode($model_ids, true);

			if (!empty($model_ids)) {
				$model_ids          = is_array($model_ids) ? $model_ids : array($model_ids);
				$response['status'] = $this->m_formbuilder->deleteFormModelFromIds($model_ids);
				$response['msg']    = $response['status'] ? Text::_('SUCCESS') : Text::_('FAILED');
			}
			else {
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getdocumentsample()
	{
		$user     = $this->app->getIdentity();
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403);

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$response = array('status' => false, 'msg' => Text::_('MISSING_PARAMS'));


			$document_id = $this->input->getInt('document_id');
			$profile_id  = $this->input->getInt('profile_id');

			if (!empty($document_id) && !empty($profile_id)) {
				$document = $this->m_formbuilder->getDocumentSample($document_id, $profile_id);
				$document = empty($document) ? array('has_sample' => 0, 'sample_filepath' => '') : $document;
				$response = array('status' => true, 'msg' => Text::_('SUCCESS'), 'code' => 200, 'data' => $document);
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getsqldropdownoptions() {
		$user     = $this->app->getIdentity();
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => []);

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$response = array('status' => false, 'msg' => Text::_('MISSING_PARAMS'));

			$jinput = JFactory::getApplication()->input;
			$table = $jinput->getString('table', '');
			$key  = $jinput->getString('key', '');
			$value  = $jinput->getString('value', '');
			$translate  = $jinput->getString('translate', false);
			$translate = filter_var($translate, FILTER_VALIDATE_BOOLEAN);

			if(!empty($table) && !empty($key) && !empty($value)) {
				$options = $this->m_formbuilder->getSqlDropdownOptions($table, $key, $value, $translate);
				$response = array('status' => true, 'msg' => Text::_('SUCCESS'), 'code' => 200, 'data' => $options);
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function updateelementparam() {
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403);

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$element_id = $this->input->getInt('element_id', 0);
			$param = $this->input->getString('param', '');
			$value = $this->input->getString('value', '');

			if (!empty($element_id) && !empty($param) && isset($value)) {
				$allowed_params = ['show_in_list_summary', 'label', 'hidden', 'default', 'ordering'];

				if (in_array($param, $allowed_params)) {
					$udpated = $this->m_formbuilder->updateElementParam($element_id, $param, $value);

					$response['status'] = $udpated;
					$response['msg'] = $udpated ? Text::_('SUCCESS') : Text::_('FAILED');
					$response['code'] = 200;
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getCurrencies()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$currencies = $this->m_formbuilder->getCurrencies();
			$response = ['status' => true, 'msg' => Text::_('SUCCESS'), 'code' => 200, 'data' => $currencies];
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getCurrencyListOptions()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$currencyList = $this->m_formbuilder->getCurrencyListOptions();
			$response = ['status' => true, 'msg' => Text::_('SUCCESS'), 'code' => 200, 'data' => $currencyList];
		}

		echo json_encode((object) $response);
		exit;
	}
}


