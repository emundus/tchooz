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
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\EmundusResponse;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Factories\Language\LanguageFactory;
use \Tchooz\Traits\TraitResponse;
use Tchooz\Controller\EmundusController;

require_once(JPATH_ROOT . '/components/com_emundus/models/formbuilder.php');

class EmundusControllerFormbuilder extends EmundusController
{
	private EmundusModelFormbuilder $m_formbuilder;

	public function __construct($config = array())
	{
		parent::__construct($config);

		if (!class_exists('EmundusHelperFabrik'))
		{
			require_once(JPATH_BASE . DS . '/components/com_emundus/helpers/fabrik.php');
		}

		$this->m_formbuilder = new EmundusModelFormbuilder();
	}

	public function setMFormbuilder(EmundusModelFormbuilder $m_formbuilder): void
	{
		$this->m_formbuilder = $m_formbuilder;
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updateOrder(): EmundusResponse
	{
		$elements = $this->input->getString('elements');
		$elements = json_decode($elements, true);
		$group_id = $this->input->getInt('group_id');
		$moved_el = $this->input->getString('moved_el');
		$moved_el = json_decode($moved_el, true);
		if (empty($moved_el))
		{
			throw new InvalidArgumentException(Text::_('INVALID_PARAMETERS'));
		}

		if (!$this->m_formbuilder->updateOrder($elements, $group_id, $this->user->id, $moved_el))
		{
			throw new RuntimeException(Text::_('ORDER_NOT_UPDATED'));
		}

		return EmundusResponse::ok([], Text::_('ORDER_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updateelementorder(): EmundusResponse
	{
		$group_id   = $this->input->getInt('group_id');
		$element_id = $this->input->getInt('element_id');
		$new_index  = $this->input->getInt('new_index', 0);
		if (empty($group_id) || empty($element_id))
		{
			throw new InvalidArgumentException(Text::_('INVALID_PARAMETERS'));
		}

		if (!$this->m_formbuilder->updateElementOrder($group_id, $element_id, $new_index))
		{
			throw new RuntimeException(Text::_('ORDER_NOT_UPDATED'));
		}

		return EmundusResponse::ok([], Text::_('ORDER_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updategroupparams(): EmundusResponse
	{
		$label    = $this->input->getString('label');
		$group_id = $this->input->getInt('group_id');
		$params   = $this->input->getRaw('params');
		$params   = json_decode($params, true);
		$lang     = $this->input->getString('lang', '');
		if (empty($params))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->updateGroupParams($label, $group_id, $params, $lang))
		{
			throw new RuntimeException(Text::_('GROUP_PARAMS_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('GROUP_PARAMS_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updatepageparams(): EmundusResponse
	{
		$label   = $this->input->getString('label');
		$intro   = $this->input->getString('intro');
		$page_id = $this->input->getInt('page_id');
		$lang    = $this->input->getString('lang', '');

		if (!$this->m_formbuilder->updatePageParams($label, $intro, $page_id, $lang))
		{
			throw new RuntimeException(Text::_('PAGE_PARAMS_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('PAGE_PARAMS_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function changerequire(): EmundusResponse
	{
		$element = $this->input->getRaw('element');
		if (empty($element))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->ChangeRequire($element, $this->user->id))
		{
			throw new RuntimeException(Text::_('ELEMENT_REQUIRE_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('ELEMENT_REQUIRE_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function publishunpublishelement(): EmundusResponse
	{
		$element = $this->input->getInt('element');
		if (empty($element))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->publishUnpublishElement($element))
		{
			throw new RuntimeException(Text::_('ELEMENT_PUBLISH_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('ELEMENT_PUBLISH_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function hiddenunhiddenelement(): EmundusResponse
	{
		$element = $this->input->getInt('element');
		if (!$this->m_formbuilder->hiddenUnhiddenElement($element))
		{
			throw new RuntimeException(Text::_('COM_EMUNDUS_FORMBUILDER_ELEMENT_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('COM_EMUNDUS_FORMBUILDER_ELEMENT_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updateparams(): EmundusResponse
	{
		$element = $this->input->getRaw('element');
		$element = json_decode($element, true);
		if (empty($element))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->UpdateParams($element, $this->user->id))
		{
			throw new RuntimeException(Text::_('ELEMENT_PARAMS_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('ELEMENT_PARAMS_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function duplicateelement(): EmundusResponse
	{
		$eid       = $this->input->getInt('id');
		$group     = $this->input->getInt('group');
		$old_group = $this->input->getInt('old_group');
		$form_id   = $this->input->getInt('form_id');
		if (empty($eid) || empty($group) || empty($old_group) || empty($form_id))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->duplicateElement($eid, $group, $old_group, $form_id))
		{
			throw new RuntimeException(Text::_('ELEMENT_NOT_DUPLICATED'));
		}

		return EmundusResponse::ok(true, Text::_('ELEMENT_DUPLICATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function formsTrad(): EmundusResponse
	{
		$element     = $this->input->getInt('element', null);
		$group       = $this->input->getInt('group', null);
		$page        = $this->input->getInt('page', null);
		$labelTofind = $this->input->getString('labelTofind');
		$newLabel    = $this->input->getRaw('NewSubLabel');
		if (empty($newLabel) || empty($labelTofind))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$results = $this->m_formbuilder->formsTrad($labelTofind, $newLabel, $element, $group, $page);
		if (empty($results))
		{
			throw new RuntimeException(Text::_('NO_TRANSLATION_FOUND'));
		}

		return EmundusResponse::ok($results, 'Traductions effectués avec succès');
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updateelementlabelwithouttranslation(): EmundusResponse
	{
		$eid   = $this->input->getInt('eid');
		$label = $this->input->getString('label');
		if (empty($eid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->updateElementWithoutTranslation($eid, $label))
		{
			throw new RuntimeException(Text::_('ELEMENT_LABEL_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('ELEMENT_LABEL_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updategrouplabelwithouttranslation(): EmundusResponse
	{
		$gid   = $this->input->getInt('gid');
		$label = $this->input->getString('label');
		if (empty($gid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->updateGroupWithoutTranslation($gid, $label))
		{
			throw new RuntimeException(Text::_('GROUP_LABEL_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('GROUP_LABEL_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updatepagelabelwithouttranslation(): EmundusResponse
	{
		$pid   = $this->input->getInt('pid');
		$label = $this->input->getString('label');
		if (empty($pid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->updatePageWithoutTranslation($pid, $label))
		{
			throw new RuntimeException(Text::_('PAGE_LABEL_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('PAGE_LABEL_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updatepageintrowithouttranslation(): EmundusResponse
	{
		$pid   = $this->input->getInt('pid');
		$intro = $this->input->getString('label');
		if (empty($pid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->updatePageIntroWithoutTranslation($pid, $intro))
		{
			throw new RuntimeException(Text::_('PAGE_LABEL_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('PAGE_LABEL_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getJTEXTA(): EmundusResponse
	{
		$toJTEXT = $this->input->get('toJTEXT');
		if (!is_array($toJTEXT))
		{
			$toJTEXT = array($toJTEXT);
		}

		$translations = LanguageFactory::getJoomlaTranslations($toJTEXT);
		if (empty($translations))
		{
			throw new RuntimeException(Text::_('NO_TRANSLATION_FOUND'));
		}

		return EmundusResponse::ok($translations, Text::_('SUCCESS'));
	}

	public function getJTEXT(): void
	{
		$toJTEXT = $this->input->getString('toJTEXT');

		echo json_encode(Text::_($toJTEXT));
		exit;
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getalltranslations(): EmundusResponse
	{
		$toJTEXT = $this->input->getString('toJTEXT');

		$languages = LanguageHelper::getLanguages();

		$data = new stdClass();
		foreach ($languages as $language)
		{
			$data->{$language->sef} = LanguageFactory::getTranslation($toJTEXT, $language->lang_code);
		}

		return EmundusResponse::ok($data, Text::_('SUCCESS'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function createMenu(): void
	{
		$label    = $this->input->getRaw('label');
		$intro    = $this->input->getRaw('intro');
		$prid     = $this->input->getInt('prid');
		$modelid  = $this->input->getInt('modelid');
		$template = $this->input->getString('template');

		$label = json_decode($label, true);
		$intro = json_decode($intro, true);
		if ($modelid != -1)
		{
			$keep_structure = $this->input->getString('keep_structure') == 'true';
			$response       = $this->m_formbuilder->createMenuFromTemplate($label, $intro, $modelid, $prid, $keep_structure);
		}
		else
		{
			$response = $this->m_formbuilder->createApplicantMenu($label, $intro, $prid, $template);
		}

		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function checkifmodeltableisusedinform(): EmundusResponse
	{
		$model_id   = $this->input->getInt('model_id', 0);
		$profile_id = $this->input->getInt('profile_id', 0);
		if (empty($model_id) || empty($profile_id))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		return EmundusResponse::ok(
			$this->m_formbuilder->checkIfModelTableIsUsedInForm($model_id, $profile_id)
		);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function deletemenu(): EmundusResponse
	{
		$mid = $this->input->getInt('mid');
		if (empty($mid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->deleteMenu($mid))
		{
			throw new RuntimeException(Text::_('MENU_NOT_DELETED'));
		}

		return EmundusResponse::ok(true, Text::_('MENU_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function savemenuastemplate(): EmundusResponse
	{
		$menu     = $this->input->getRaw('menu');
		$template = $this->input->getString('template');

		if (!$this->m_formbuilder->saveAsTemplate($menu, $template))
		{
			throw new RuntimeException(Text::_('TEMPLATE_NOT_SAVED'));
		}

		return EmundusResponse::ok(true, Text::_('TEMPLATE_SAVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function createsimplegroup(): EmundusResponse
	{
		$fid  = $this->input->getInt('fid');
		$mode = $this->input->getString('mode');
		if ($this->input->getRaw('label'))
		{
			$label = $this->input->getRaw('label');
		}
		else
		{
			$label = array(
				'fr' => 'Nouveau groupe',
				'en' => 'New group'
			);
		}

		$group = $this->m_formbuilder->createGroup($label, $fid, 1, $mode);
		if (empty($group['group_id']))
		{
			throw new RuntimeException(Text::_('GROUP_NOT_CREATED'));
		}

		return EmundusResponse::ok($group, Text::_('SUCCESS'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function deleteGroup(): EmundusResponse
	{
		$gid = $this->input->getInt('gid');
		if (empty($gid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->deleteGroup($gid))
		{
			throw new RuntimeException(Text::_('GROUP_NOT_DELETED'));
		}

		return EmundusResponse::ok(true, Text::_('GROUP_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getElement(): void
	{
		$element = $this->input->getInt('element');
		$gid     = $this->input->getInt('gid');
		if (empty($element))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$this->sendJsonResponse($this->m_formbuilder->getElement($element, $gid));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function retriveElementFormAssociatedDoc(): void
	{
		$docid = $this->input->getInt('docid');
		$gid   = $this->input->getInt('gid');
		if (empty($docid) || empty($gid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$this->sendJsonResponse($this->m_formbuilder->retriveElementFormAssociatedDoc($gid, $docid));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function createsimpleelement(): EmundusResponse
	{
		$gid    = $this->input->getInt('gid');
		$plugin = $this->input->getString('plugin');

		if (empty($plugin) || empty($gid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PLUGIN_OR_GROUP'));
		}

		$mode       = $this->input->getString('mode');
		$evaluation = $mode == 'eval';
		if ($this->input->getString('attachmentId'))
		{
			$attachmentId = $this->input->getString('attachmentId');
		}

		if (isset($attachmentId))
		{
			$result = $this->m_formbuilder->createSimpleElement($gid, $plugin, $attachmentId, $evaluation);
		}
		else
		{
			$result = $this->m_formbuilder->createSimpleElement($gid, $plugin, 0, $evaluation);
		}

		if (empty($result))
		{
			throw new RuntimeException(Text::_('COM_EMUNDUS_FORMBUILDER_ELEMENT_NOT_CREATED'));
		}

		return EmundusResponse::ok($result, Text::_('COM_EMUNDUS_FORMBUILDER_ELEMENT_CREATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function createsectionsimpleelements(): EmundusResponse
	{
		$results = [];

		$gid               = $this->input->getInt('gid', 0);
		$fid               = $this->input->getInt('fid', 0);
		$mode              = $this->input->getString('mode', 'form');
		$evaluation        = $mode == 'eval';
		$section_to_insert = array();
		$elements          = array();

		if (empty($gid) || empty($fid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (is_file(JPATH_ROOT . '/components/com_emundus/data/form-builder/form-builder-sections.json'))
		{
			$sections_available = json_decode(file_get_contents(JPATH_ROOT . '/components/com_emundus/data/form-builder/form-builder-sections.json'), true);

			if (!empty($sections_available))
			{
				foreach ($sections_available as $section)
				{
					if ($section['id'] == $gid)
					{
						$section_to_insert = $section;
						break;
					}
				}
			}
		}

		if (!empty($section_to_insert))
		{
			$elements = $section_to_insert['elements'];
		}

		if (empty($elements))
		{
			throw new RuntimeException(Text::_('NO_ELEMENTS_AVAILABLE'));
		}

		$group = $this->m_formbuilder->createGroup($section_to_insert['labels'], $fid);
		if (empty($group['group_id']))
		{
			throw new RuntimeException(Text::_('GROUP_NOT_CREATED'));
		}

		$elements_created = [];
		foreach ($elements as $element)
		{
			$labels    = !empty($element['labels']) ? $element['labels'] : null;
			$elementId = $this->m_formbuilder->createSimpleElement($group['group_id'], $element['value'], 0, $evaluation, $labels);

			if (!empty($elementId))
			{
				$results[]   = $elementId;
				$new_element = $this->m_formbuilder->getSimpleElement($elementId);

				if (!empty($element['params']))
				{
					$new_element['params']   = json_decode($new_element['params'], true);
					$new_element['params']   = array_merge($new_element['params'], $element['params']);
					$new_element['FRequire'] = !empty($element['required']) ? $element['required'] : 'true';

					$this->m_formbuilder->updateParams($new_element, $this->user->id);
				}

				if (!empty($element['options']))
				{
					$this->m_formbuilder->deleteElementSubOption($elementId, 0);
					foreach ($element['options'] as $option)
					{
						$sub_options = $this->m_formbuilder->addElementSubOption($elementId, $option['value'], 'fr');

						if (!empty($sub_options))
						{
							LanguageFactory::translate($sub_options['sub_labels'][sizeof($sub_options['sub_labels']) - 1], $option['labels'], 'fabrik_elements', $elementId);
						}
					}
				}


				$elements_created[] = $new_element;
			}
		}

		foreach ($elements as $key => $element)
		{
			if (!empty($element['jsactions']))
			{
				$re = '/\$\d/m';

				preg_match_all($re, $element['jsactions']['code'], $matches, PREG_SET_ORDER, 0);

				if (!empty($matches[0]))
				{
					foreach ($matches[0] as $match)
					{
						$index                        = str_replace('$', '', $match);
						$element['jsactions']['code'] = str_replace($match, $elements_created[(int) $index]['name'], $element['jsactions']['code']);
					}
				}

				EmundusHelperFabrik::addJsAction($elements_created[$key]['id'], $element['jsactions']);
			}
		}

		if (empty($results))
		{
			throw new RuntimeException(Text::_('ELEMENTS_NOT_CREATED'));
		}

		return EmundusResponse::ok($results, Text::_('ELEMENTS_CREATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function createcriteria(): void
	{
		$gid    = $this->input->getInt('gid');
		$plugin = $this->input->getString('plugin');
		if (empty($gid) || empty($plugin))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$this->sendJsonResponse($this->m_formbuilder->createSimpleElement($gid, $plugin, null, 1));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function deleteElement(): EmundusResponse
	{
		$element = $this->input->getInt('element');
		if (empty($element))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->deleteElement($element))
		{
			throw new RuntimeException(Text::_('ELEMENT_NOT_DELETED'));
		}

		return EmundusResponse::ok(true, Text::_('ELEMENT_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function reordermenu(): EmundusResponse
	{
		$menus   = json_decode($_POST['menus']);
		$profile = $this->input->getInt('profile');
		if (empty($profile))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->reorderMenu($menus, $profile))
		{
			throw new RuntimeException(Text::_('MENU_ORDER_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('MENU_REORDERED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getGroupOrdering(): void
	{
		$gid = $this->input->getInt('gid');
		$fid = $this->input->getInt('fid');
		if (empty($gid) || empty($fid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$this->sendJsonResponse($this->m_formbuilder->getGroupOrdering($gid, $fid));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function reordergroups(): EmundusResponse
	{
		$groups = $this->input->getString('groups');
		$fid    = $this->input->getInt('fid');
		if (empty($groups))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$groups = json_decode($groups, true);
		foreach ($groups as $group)
		{
			$results[] = $this->m_formbuilder->reorderGroup($group['id'], $fid, $group['order']);
		}

		if (in_array(false, $results, true))
		{
			throw new RuntimeException(Text::_('GROUPS_ORDER_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('GROUPS_REORDERED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getPagesModel(): void
	{
		$this->sendJsonResponse($this->m_formbuilder->getPagesModel());
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function checkconstraintgroup(): EmundusResponse
	{
		$cid = $this->input->getInt('cid');
		if (empty($cid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$visibility = $this->m_formbuilder->checkConstraintGroup($cid);

		return EmundusResponse::ok($visibility, Text::_('SUCCESS'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function checkvisibility(): EmundusResponse
	{
		$group = $this->input->getInt('group');
		$cid   = $this->input->getInt('cid');
		if (empty($cid) || empty($group))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$visibility = $this->m_formbuilder->checkVisibility($group, $cid);

		return EmundusResponse::ok($visibility, Text::_('SUCCESS'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getdatabasesjoin(): EmundusResponse
	{
		$databases = $this->m_formbuilder->getDatabasesJoin();

		return EmundusResponse::ok($databases, Text::_('SUCCESS'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getDatabaseJoinOrderColumns(): EmundusResponse
	{
		$database_name = $this->input->getString('database_name');
		if (empty($database_name))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$database_name_columns = $this->m_formbuilder->getDatabaseJoinOrderColumns($database_name);

		return EmundusResponse::ok($database_name_columns, Text::_('SUCCESS'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function enablegrouprepeat(): EmundusResponse
	{
		$gid = $this->input->getInt('gid');
		if (empty($gid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->enableRepeatGroup($gid))
		{
			throw new RuntimeException(Text::_('GROUP_REPEAT_NOT_ENABLED'));
		}

		return EmundusResponse::ok(true, Text::_('GROUP_REPEAT_ENABLED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function disablegrouprepeat(): EmundusResponse
	{
		$gid = $this->input->getInt('gid');
		if (empty($gid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->disableRepeatGroup($gid))
		{
			throw new RuntimeException(Text::_('GROUP_REPEAT_NOT_DISABLED'));
		}

		return EmundusResponse::ok(true, Text::_('GROUP_REPEAT_DISABLED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function displayhidegroup(): EmundusResponse
	{
		$gid = $this->input->getInt('gid');
		if (empty($gid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->displayHideGroup($gid))
		{
			throw new RuntimeException(Text::_('GROUP_VISIBILITY_NOT_CHANGED'));
		}

		return EmundusResponse::ok(true, Text::_('GROUP_VISIBILITY_CHANGED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updatemenulabel(): EmundusResponse
	{
		$label = $this->input->getRaw('label');
		$pid   = $this->input->getString('pid');
		if (empty($pid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->m_formbuilder->updateMenuLabel($label, $pid))
		{
			throw new RuntimeException(Text::_('MENU_LABEL_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('MENU_LABEL_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function gettestingparams(): void
	{
		$prid = $this->input->getInt('prid');
		if (empty($prid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$campaign_files = $this->m_formbuilder->getFormTesting($prid, $this->user->id);
		$response       = array('status' => true, 'user' => $this->user, 'campaign_files' => $campaign_files);
		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function createtestingfile(): void
	{
		$cid = $this->input->getInt('cid');
		if (empty($cid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$fnum = $this->m_formbuilder->createTestingFile($cid, $this->user->id);

		$response = array('status' => true, 'fnum' => $fnum);
		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function deletetestingfile(): void
	{
		$fnum = $this->input->getString('file');
		if (empty($fnum))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$status   = $this->m_formbuilder->deleteFormTesting($fnum, $this->user->id);
		$response = array('status' => $status, 'userid' => $this->user->id);
		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updatedocument(): EmundusResponse
	{
		$document_id = $this->input->getInt('document_id');
		$profile_id  = $this->input->getInt('profile_id');
		$document    = $this->input->getString('document');
		$document    = json_decode($document, true);
		if(empty($document_id) || empty($profile_id) || empty($document))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$types  = $this->input->getString('types');
		$types  = json_decode($types, true);
		$params = ['has_sample' => $this->input->getInt('has_sample', 0)];

		if ($params['has_sample'] && !empty($_FILES['file']))
		{
			$params['file'] = $_FILES['file'];
		}

		require_once JPATH_SITE . '/components/com_emundus/models/campaign.php';
		$m_campaign = $this->getModel('Campaign');

		if(!$m_campaign->updateDocument($document, $types, $document_id, $profile_id, $params))
		{
			throw new RuntimeException(Text::_('DOCUMENT_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('DOCUMENT_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updatedefaultvalue(): void
	{
		$eid   = $this->input->getInt('eid');
		$value = $this->input->getRaw('value');
		if(empty($eid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$status = $this->m_formbuilder->updateDefaultValue($eid, $value);
		$response = array('status' => $status, 'userid' => $this->user->id);
		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getsection(): void
	{
		$section = $this->input->getInt('section');
		if(empty($section))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$group = $this->m_formbuilder->getSection($section);
		$response = array('status' => true, 'group' => $group);
		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updateElementOption(): EmundusResponse
	{
		$element        = $this->input->getInt("element");
		$options        = json_decode($this->input->getString("options"), true);
		$index          = $this->input->getInt("index");
		$newTranslation = $this->input->getString("newTranslation");
		$lang           = $this->input->getString("lang");
		if(empty($element) || empty($options) || empty($newTranslation))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if(!$this->m_formbuilder->updateElementOption($element, $options, $index, $newTranslation, $lang))
		{
			throw new RuntimeException(Text::_('OPTION_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('OPTION_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getelementsuboptions(): void
	{
		$element = $this->input->getInt("element");
		if(empty($element))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$options = $this->m_formbuilder->getElementSubOption($element);
		$response     = array('status' => !empty($options), 'new_options' => $options);
		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function addElementSubOption(): void
	{
		$element   = $this->input->getInt("element");
		$newOption = $this->input->getString("newOption");
		$lang      = $this->input->getString("lang");
		if(empty($element) || empty($newOption))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$options = $this->m_formbuilder->addElementSubOption($element, $newOption, $lang);
		$response     = array('status' => !empty($options), 'options' => $options);
		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function deleteElementSubOption(): EmundusResponse
	{
		$element = $this->input->getInt("element");
		$index   = $this->input->getInt("index");
		if(empty($element) || empty($index))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if(!$this->m_formbuilder->deleteElementSubOption($element, $index))
		{
			throw new RuntimeException(Text::_('OPTION_NOT_DELETED'));
		}

		return EmundusResponse::ok(true, Text::_('OPTION_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updateElementSubOptionsOrder(): EmundusResponse
	{
		$element   = $this->input->getInt("element");
		$old_order = json_decode($this->input->getString("options_old_order"), true);
		$new_order = json_decode($this->input->getString("options_new_order"), true);
		if(empty($element) || empty($new_order) || empty($old_order))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if(!$this->m_formbuilder->updateElementSubOptionsOrder($element, $old_order, $new_order))
		{
			throw new RuntimeException(Text::_('OPTIONS_ORDER_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true, Text::_('OPTIONS_ORDER_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getpagemodels(): EmundusResponse
	{
		$models             = $this->m_formbuilder->getPagesModel();
		return EmundusResponse::ok($models);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getallmodels(): EmundusResponse
	{
		$sort      = $this->input->getString('sort', '');
		$recherche = $this->input->getString('recherche', '');
		$order_by  = $this->input->getString('order_by', '');

		$models             = $this->m_formbuilder->getPagesModel([], [], $sort, $recherche, $order_by);
		return EmundusResponse::ok(['datas' => $models, 'count' => count($models)]);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function addformmodel(): EmundusResponse
	{
		$form_id = $this->input->getInt('form_id');
		$label   = $this->input->getString('label');
		if(empty($form_id) || empty($label))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if(!$this->m_formbuilder->addFormModel($form_id, $label))
		{
			throw new RuntimeException(Text::_('MODEL_NOT_CREATED'));
		}

		return EmundusResponse::ok(true, Text::_('MODEL_CREATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function deleteformmodel(): EmundusResponse
	{
		$form_id = $this->input->getInt('form_id');
		if(empty($form_id))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if(!$this->m_formbuilder->deleteFormModel($form_id))
		{
			throw new RuntimeException(Text::_('MODEL_NOT_DELETED'));
		}

		return EmundusResponse::ok(true, Text::_('MODEL_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function deleteformmodelfromids(): EmundusResponse
	{
		$model_ids = $this->input->getString('id');
		$model_ids = json_decode($model_ids, true);
		if(empty($model_ids))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$model_ids          = is_array($model_ids) ? $model_ids : array($model_ids);
		if(!$this->m_formbuilder->deleteFormModelFromIds($model_ids))
		{
			throw new RuntimeException(Text::_('MODELS_NOT_DELETED'));
		}

		return EmundusResponse::ok(true, Text::_('MODELS_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getdocumentsample(): EmundusResponse
	{
		$document_id = $this->input->getInt('document_id');
		$profile_id  = $this->input->getInt('profile_id');
		if(empty($document_id) || empty($profile_id))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$document = $this->m_formbuilder->getDocumentSample($document_id, $profile_id);
		$document = empty($document) ? array('has_sample' => 0, 'sample_filepath' => '') : $document;
		return EmundusResponse::ok($document);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getsqldropdownoptions(): EmundusResponse
	{
		$table     = $this->input->getString('table', '');
		$key       = $this->input->getString('key', '');
		$value     = $this->input->getString('value', '');
		$translate = $this->input->getString('translate', false);
		$translate = filter_var($translate, FILTER_VALIDATE_BOOLEAN);
		if(empty($table) || empty($key) || empty($value))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$options  = $this->m_formbuilder->getSqlDropdownOptions($table, $key, $value, $translate);
		return EmundusResponse::ok($options);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updateelementparam(): EmundusResponse
	{
		$element_id = $this->input->getInt('element_id', 0);
		$param      = $this->input->getString('param', '');
		$value      = $this->input->getString('value', '');
		if(empty($element_id) || empty($param) || !isset($value))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$allowed_params = ['show_in_list_summary', 'label', 'hidden', 'default', 'ordering'];
		if(!in_array($param, $allowed_params))
		{
			throw new InvalidArgumentException(Text::_('PARAM_NOT_ALLOWED'));
		}

		if(!$this->m_formbuilder->updateElementParam($element_id, $param, $value))
		{
			throw new RuntimeException(Text::_('ELEMENT_PARAM_NOT_UPDATED'));
		}

		return EmundusResponse::ok(true);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getCurrencies(): EmundusResponse
	{
		$currencies = $this->m_formbuilder->getCurrencies();
		return EmundusResponse::ok($currencies);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getCurrencyListOptions(): EmundusResponse
	{
		$currencyList = $this->m_formbuilder->getCurrencyListOptions();
		return EmundusResponse::ok($currencyList);
	}
}


