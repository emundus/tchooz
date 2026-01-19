<?php
/**
 * HTML Form view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;


jimport('joomla.application.component.view');
jimport('joomla.application.component.model');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\JsonView;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Factories\Language\LanguageFactory;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Repositories\Language\LanguageRepository;

/**
 * HTML Form view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0.6
 */
class EmundusViewForm extends JsonView
{
	/**
	 * Main setup routine for displaying the form/detail view
	 * @since 0.1.0
	 */
	public function display($tpl = null)
	{
		try
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$lang = Factory::getApplication()->getLanguage();
			$lang = substr($lang->getTag(), 0, 2);

			$data = new stdClass();

			$app    = Factory::getApplication();
			$formid = $app->input->getString('formid', null);

			$fabrikRepository = new FabrikRepository();
			$fabrikFactory    = new FabrikFactory($fabrikRepository);
			$fabrikRepository->setFactory($fabrikFactory);

			$languageRepository = new LanguageRepository();
			$languages          = LanguageHelper::getLanguages();

			$form          = $fabrikRepository->getFormById($formid);
			$data->id      = $form->getId();
			$data->menu_id = $fabrikRepository->getMenuItemIdByFormId($form->getId());

			// Form label
			$formLabel           = new stdClass();
			$formLabel->titleraw = $form->getLabel();
			$formLabel->label    = Text::_($form->getLabel());
			$data->show_title    = $formLabel;
			//

			// Form intro
			$data->intro     = Text::_($form->getIntro());
			$data->intro_raw = strip_tags($form->getIntro());
			//

			$groups         = new stdClass();
			$groupsOrdering = $fabrikRepository->getGroupsOrdering($form->getId());
			foreach ($form->getGroups() as $group)
			{
				$groupObject         = new stdClass();
				$groupObject->params = json_decode($group->getParamsRaw());

				$groupOrdering         = array_search($group->getId(), $groupsOrdering);
				$groupObject->ordering = $groupOrdering !== false ? $groupOrdering : 0;
				$groupObject->label    = Text::_($group->getLabel());

				if (!empty($groupObject->params->intro))
				{
					$groupObject->params->intro = Text::_(strip_tags($groupObject->params->intro));
					$groupObject->params->intro = strip_tags($groupObject->params->intro);
				}

				$groupObject->group_id     = $group->getId();
				$groupObject->repeat_group = false;
				if ($groupObject->params->repeat_group_button == 1)
				{
					$groupObject->repeat_group = true;
				}

				$display_group = true;
				if (sizeof($group->getElements()) > 0)
				{
					$display_group = false;
				}

				$elements = new stdClass();
				foreach ($group->getElements() as $element)
				{
					if($element->getPlugin() === ElementPluginEnum::REFERENT)
					{
						continue;
					}

					$elementObject = new stdClass();

					if (in_array($element->getName(), ['id', 'user', 'time_date', 'fnum', 'date_time']))
					{
						$groupObject->cannot_delete = true;
						if (!$display_group)
						{
							continue;
						}
					}
					else {
						$display_group = true;
					}

					// TODO: Test with a referent element
					$elementParams = $element->getParams();

					$elementObject->id       = $element->getId();
					$elementObject->name     = $element->getName();
					$elementObject->group_id = $group->getId();
					$elementObject->plugin   = $element->getPlugin()->value;
					$elementObject->hidden   = $element->getHidden();
					$elementObject->eval     = $element->getEval();

					$elementObject->default = $element->getDefault();
					if ($element->getPlugin() === ElementPluginEnum::PANEL)
					{
						$elementObject->default_tag = $element->getDefault();
						$elementObject->default     = Text::_($element->getDefault());
					}

					$elementObject->FRequire = false;
					if ($elementParams->validations->plugin != null)
					{
						if (is_array($elementParams->validations->plugin))
						{
							$elementObject->FRequire = in_array('notempty', $elementParams->validations->plugin);
						}
						elseif ($elementParams->validations->plugin == 'notempty')
						{
							$elementObject->FRequire = true;
						}
					}

					if (!empty($elementParams->database_join_where_sql))
					{
						preg_match_all("/\bwhere(.*) not in\b(.*)/i", $elementParams->database_join_where_sql, $elementParams->database_join_exclude, PREG_SET_ORDER, 0);
						if (!empty($elementParams->database_join_exclude))
						{
							preg_match_all("/\((.*)\)/i", $elementParams->database_join_exclude[0][0], $ids, PREG_SET_ORDER, 0);

							if (!empty($ids))
							{
								$elementParams->database_join_exclude = $ids[0][1];
							}
						}
					}

					if (!empty($elementParams->rollover))
					{
						$elementObject->rollover_tag = $elementParams->rollover;
						$elementParams->rollover     = Text::_($elementParams->rollover);
					}

					// If sub_labels available translate them
					if (!empty($elementParams->sub_options->sub_labels))
					{
						$subLabels = [];
						foreach ($elementParams->sub_options->sub_labels as $subLabel)
						{
							$subLabels[] = Text::_($subLabel);
						}
						$elementParams->sub_options->sub_labels = $subLabels;
					}

					// If databasejoin get the first value as example
					if ($element->getPlugin() === ElementPluginEnum::DATABASEJOIN)
					{
						$select = [$db->quoteName($elementParams->join_key_column, 'value')];
						if (empty($elementParams->join_val_column_concat))
						{
							$select[] = $db->quoteName($elementParams->join_val_column, 'label');
						}
						else
						{
							$joinValColumnConcat = str_replace('{thistable}', '', $elementParams->join_val_column_concat);
							$joinValColumnConcat = str_replace('{shortlang}', $lang, $joinValColumnConcat);
							$select[]            = $db->quoteName($joinValColumnConcat, 'label');
						}

						$query->clear()
							->select($select)
							->from($db->qn($elementParams->join_db_name))
							->where($db->qn($elementParams->join_key_column) . ' IS NOT NULL');
						$db->setQuery($query, 0, 1);
						$exampleData = $db->loadObject();

						$elementObject->example_data        = $exampleData;
						$elementObject->please_select_label = Text::_($elementParams->database_join_noselectionlabel);
					}

					$elementObject->params               = $elementParams;
					$elementObject->label_tag            = $element->getLabel();
					$elementObject->label                = Text::_($element->getLabel());
					$elementObject->labelToFind          = $element->getLabel();
					$elementObject->publish              = $element->isPublished();
					$elementObject->show_in_list_summary = $element->getShowInListSummary();

					$elements->{"element" . $element->getId()} = $elementObject;
				}

				if ($display_group)
				{
					$groupObject->elements                = $elements;
					$groups->{"group_" . $group->getId()} = $groupObject;
				}

			}

			$data->Groups = $groups;

			echo json_encode($data);

			/*if(1 == 0)
			{
				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_fabrik/models');
				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_emundus/models');

				$app    = Factory::getApplication();
				$formid = $app->input->getString('formid', null);

				$formbuilder = BaseDatabaseModel::getInstance('formbuilder', 'EmundusModel');
				$form        = BaseDatabaseModel::getInstance('Form', 'FabrikFEModel');

				$languageRepository = new LanguageRepository();
				$languages          = LanguageHelper::getLanguages();

				$form->setId(intval($formid));
				$formParams = $form->getParams();
				$formGroups = $form->getGroups();

				$returnObject     = new stdClass();
				$returnObject->id = $form->id;

				$db    = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->getQuery(true);

				$query->select('id')
					->from($db->quoteName('#__menu'))
					->where($db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_fabrik&view=form&formid=' . $form->id));
				$db->setQuery($query);
				$returnObject->menu_id = $db->loadResult();

				if ($formParams->get('show_page_heading') == 1)
				{
					$show_page_heading               = new stdClass();
					$show_page_heading->class        = 'componentheading a' . $formParams->get("pageclass_sfx");
					$show_page_heading->page_heading = $this->escape($formParams->get('page_heading'));
					$returnObject->show_page_heading = $show_page_heading;
				}

				$show_title           = new stdClass();
				$show_title->class    = "page-header";
				$show_title->titleraw = $form->form->label;
				$show_title->value    = $form->getLabel();
				$show_title->label    = new stdClass;
				foreach ($languages as $language)
				{
					$languageRepository->setLangCode($language->lang_code);
					$translation                         = $languageRepository->getByTag($form->form->label);
					$show_title->label->{$language->sef} = !empty($translation) ? $translation->getOverride() : $form->form->label;
				}
				$returnObject->show_title = $show_title;

				if ($form->getIntro())
				{
					$returnObject->intro_value = $form->getIntro();
					$returnObject->intro       = new stdClass;
					foreach ($languages as $language)
					{
						$languageRepository->setLangCode($language->lang_code);
						$translation                           = $languageRepository->getByTag($form->form->intro);
						$returnObject->intro->{$language->sef} = !empty($translation) ? $translation->getOverride() : $form->form->intro;
					}
					$returnObject->intro_raw = strip_tags($form->form->intro);
				}

				if ($form->attribs)
				{
					$returnObject->attribs = $form->attribs;
				}

				if ($this->plugintop)
				{
					$returnObject->plugintop = $this->plugintop;
				}

				$db_table = $form->getTable();

				$Groups = new stdClass();
				foreach ($formGroups as $group)
				{
					$this->group     = $group;
					$GroupProperties = $group->getGroupProperties($group->getFormModel());
					$groupElement    = $group->getMyElements();

					${"group_" . $GroupProperties->id} = new stdClass();

					$query->clear()
						->select('fg.label,ffg.ordering,fg.params')
						->from($db->quoteName('#__fabrik_formgroup', 'ffg'))
						->leftJoin($db->quoteName('#__fabrik_groups', 'fg') . ' ON ' . $db->quoteName('fg.id') . ' = ' . $db->quoteName('ffg.group_id'))
						->where($db->quoteName('ffg.group_id') . ' = ' . $db->quote($GroupProperties->id));
					$db->setQuery($query);
					$group_infos = $db->loadObject();

					${"group_" . $GroupProperties->id}->params           = json_decode($group_infos->params);
					${"group_" . $GroupProperties->id}->ordering         = (int) $group_infos->ordering;
					${"group_" . $GroupProperties->id}->group_showLegend = $GroupProperties->title;
					${"group_" . $GroupProperties->id}->group_tag        = $group_infos->label != '' ? $group_infos->label : strtoupper(LanguageFactory::replaceAccents($GroupProperties->name));
					${"group_" . $GroupProperties->id}->label            = new stdClass;
					foreach ($languages as $language)
					{
						$languageRepository->setLangCode($language->lang_code);
						$translation                                                = $languageRepository->getByTag($group_infos->label);
						${"group_" . $GroupProperties->id}->label->{$language->sef} = !empty($translation) ? $translation->getOverride() : $group_infos->label;
					}

					if ($GroupProperties->class)
					{
						${"group_" . $GroupProperties->id}->group_class = $GroupProperties->class;
					}
					if ($GroupProperties->id)
					{
						${"group_" . $GroupProperties->id}->group_id = $GroupProperties->id;
					}
					if ($GroupProperties->css)
					{
						${"group_" . $GroupProperties->id}->group_css = $GroupProperties->css;
					}
					if ($GroupProperties->intro)
					{
						${"group_" . $GroupProperties->id}->group_intro = $GroupProperties->intro;
					}

					${"group_" . $GroupProperties->id}->repeat_group = false;
					if ($GroupProperties->canRepeat == 1)
					{
						${"group_" . $GroupProperties->id}->repeat_group = true;
					}

					$elements = new stdClass();
					if (sizeof($groupElement) > 0)
					{
						$display_group = false;
					}
					else
					{
						$display_group = true;
					}

					foreach ($groupElement as $element)
					{
						$this->element = $element;
						$d_element     = $this->element;
						$o_element     = $d_element->element;
						if (in_array($o_element->name, ['id', 'user', 'time_date', 'fnum', 'date_time']))
						{
							${"group_" . $GroupProperties->id}->cannot_delete = true;
							if (!$display_group)
							{
								continue;
							}
						}
						else
						{
							$display_group = true;
						}

						if ($o_element->plugin != 'emundusreferent')
						{
							$elementParams = json_decode($o_element->params);
							if ($o_element->plugin != 'calc')
							{
								$content_element = $element->preRender('0', '1', 'emundus');
							}
							else
							{
								// We build the calc element because we don't want to execute the preRender function
								$content_element                 = new stdClass();
								$content_element->startRow       = 0;
								$content_element->endRow         = 0;
								$content_element->error          = '';
								$content_element->plugin         = 'calc';
								$content_element->hidden         = !($o_element->hidden == "0");
								$content_element->id             = $db_table->db_table_name . '___' . $o_element->name;
								$content_element->className      = 'fb_el_' . $db_table->db_table_name . '___' . $o_element->name;
								$content_element->element        = '<span class="fabrikinput fabrikElementReadOnly em-w-100 em-bg-neutral-200" style="display:inline-block;background-color: #B2B7C7; border: unset" name="' . $content_element->id . '" id="' . $content_element->id . '"></span>';
								$content_element->label_raw      = $o_element->label;
								$content_element->label          = '<label for="' . $content_element->id . '" class="fabrikLabel control-label" >' . $o_element->label . '</label>';
								$content_element->errorTag       = '<span class="fabrikErrorMessage"></span>';
								$content_element->element_ro     = '';
								$content_element->value          = '';
								$content_element->containerClass = 'fabrikElementContainer plg-calc fb_el_' . $content_element->id;
								$content_element->element_raw    = '';
								$content_element->dataEmpty      = false;
								$content_element->labels         = 1;
								$content_element->dlabels        = 1;
								$content_element->tipAbove       = '';
								$content_element->tipBelow       = '';
								$content_element->tipSide        = '';
							}
							${"element" . $o_element->id} = new stdClass();

							$labelsAbove                            = $content_element->labels;
							${"element" . $o_element->id}->id       = $o_element->id;
							${"element" . $o_element->id}->name     = $o_element->name;
							${"element" . $o_element->id}->group_id = $GroupProperties->id;
							${"element" . $o_element->id}->hidden   = $content_element->hidden;
							if ($o_element->plugin === 'panel')
							{
								${"element" . $o_element->id}->default_tag = $o_element->default;
								${"element" . $o_element->id}->default     = new stdClass;
								foreach ($languages as $language)
								{
									$languageRepository->setLangCode($language->lang_code);
									$translation                                             = $languageRepository->getByTag(${"element" . $o_element->id}->default_tag);
									${"element" . $o_element->id}->default->{$language->sef} = !empty($translation) ? $translation->getOverride() : $o_element->default;
								}
							}
							else
							{
								${"element" . $o_element->id}->default = $o_element->default;
							}

							${"element" . $o_element->id}->eval        = $o_element->eval;
							${"element" . $o_element->id}->labelsAbove = $labelsAbove;
							${"element" . $o_element->id}->plugin      = $o_element->plugin;
							if ($elementParams->validations->plugin != null)
							{
								if (is_array($elementParams->validations->plugin))
								{
									$FRequire = in_array('notempty', $elementParams->validations->plugin);
								}
								elseif ($elementParams->validations->plugin == 'notempty')
								{
									$FRequire = true;
								}
								else
								{
									$FRequire = false;
								}
							}
							else
							{
								$FRequire = false;
							}

							if (!empty($elementParams->database_join_where_sql))
							{
								preg_match_all("/\bwhere(.*) not in\b(.*)/i", $elementParams->database_join_where_sql, $elementParams->database_join_exclude, PREG_SET_ORDER, 0);
								if (!empty($elementParams->database_join_exclude))
								{
									preg_match_all("/\((.*)\)/i", $elementParams->database_join_exclude[0][0], $ids, PREG_SET_ORDER, 0);

									if (!empty($ids))
									{
										$elementParams->database_join_exclude = $ids[0][1];
									}
								}
							}

							// Translate rollover parameter
							if (!empty($elementParams->rollover))
							{
								${"element" . $o_element->id}->rollover_tag = $elementParams->rollover;
								$elementParams->rollover                    = new stdClass;
								foreach ($languages as $language)
								{
									$elementParams->rollover->{$language->sef} = htmlspecialchars_decode(LanguageFactory::getTranslation(${"element" . $o_element->id}->rollover_tag, $language->lang_code));
								}
							}
							else
							{
								$elementParams->rollover = new stdClass;
								foreach ($languages as $language)
								{
									$elementParams->rollover->{$language->sef} = '';
								}
							}


							${"element" . $o_element->id}->FRequire  = $FRequire;
							${"element" . $o_element->id}->params    = $elementParams;
							${"element" . $o_element->id}->label_tag = $o_element->label;
							${"element" . $o_element->id}->label     = new stdClass;
							foreach ($languages as $language)
							{
								$languageRepository->setLangCode($language->lang_code);
								$translation                                           = $languageRepository->getByTag(${"element" . $o_element->id}->label_tag);
								${"element" . $o_element->id}->label->{$language->sef} = !empty($translation) ? $translation->getOverride() : ${"element" . $o_element->id}->label_tag;
							}
							${"element" . $o_element->id}->labelToFind          = $element->label;
							${"element" . $o_element->id}->publish              = $element->isPublished();
							${"element" . $o_element->id}->show_in_list_summary = $element->getElement()->show_in_list_summary;

							if ($labelsAbove == 2)
							{
								if ($elementParams->tipLocation == 'above') :
									${"element" . $o_element->id}->tipAbove = $content_element->tipAbove;
								endif;
								if ($content_element->element) :
									if (in_array($o_element->plugin, ['date', 'jdate']))
									{
										${"element" . $o_element->id}->element = '<input data-v-8d3bb2fa="" class="form-control" type="date">';
									}
									else
									{
										${"element" . $o_element->id}->element = $content_element->element;
									}
								endif;
								if ($content_element->error) :
									${"element" . $o_element->id}->error      = $content_element->error;
									${"element" . $o_element->id}->errorClass = $elementParams->class;
								endif;
								if ($elementParams->tipLocation == 'side') :
									${"element" . $o_element->id}->tipSide = $content_element->tipSide;
								endif;
								if ($elementParams->tipLocation == 'below') :
									${"element" . $o_element->id}->tipBelow = $content_element->tipBelow;
								endif;
							}
							else
							{
								${"element" . $o_element->id}->label_value = $content_element->label;

								if ($elementParams->tipLocation == 'above') :
									${"element" . $o_element->id}->tipAbove = $content_element->tipAbove;
								endif;
								if ($content_element->element) :
									if (in_array($o_element->plugin, ['date', 'jdate']))
									{
										${"element" . $o_element->id}->element = '<input data-v-8d3bb2fa="" class="form-control" type="date">';
									}
									else
									{
										${"element" . $o_element->id}->element = $content_element->element;
									}
								endif;
								if ($content_element->error) :
									${"element" . $o_element->id}->error      = $content_element->error;
									${"element" . $o_element->id}->errorClass = $elementParams->class;
								endif;
								if ($elementParams->tipLocation == 'side') :
									${"element" . $o_element->id}->tipSide = $content_element->tipSide;
								endif;
								if ($elementParams->tipLocation == 'below') :
									${"element" . $o_element->id}->tipBelow = $content_element->tipBelow;
								endif;
							}

							$elements->{"element" . $o_element->id} = ${"element" . $o_element->id};
							//}
						}
					}
					${"group_" . $GroupProperties->id}->elements = $elements;

					if ($GroupProperties->outro)
					{
						${"group_" . $GroupProperties->id}->group_outro = $GroupProperties->outro;
					}

					if (${"group_" . $GroupProperties->id}->group_css === ";display:none;")
					{
						${"group_" . $GroupProperties->id}->hidden_group = -1;
						${"group_" . $GroupProperties->id}->group_css    = '';
					}
					else
					{
						${"group_" . $GroupProperties->id}->hidden_group = 1;
					}

					if ($display_group)
					{
						$Groups->{"group_" . $GroupProperties->id} = ${"group_" . $GroupProperties->id};
					}
				}

				$returnObject->Groups = $Groups;

				if ($this->pluginbottom)
				{
					$returnObject->pluginbottom = $this->pluginbottom;
				}

				echo json_encode($returnObject);
			}*/
		}
		catch (Exception $e)
		{
			JLog::add('component/com_emundus/views/view.vue_jsonclean | Cannot getting the form datas : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), JLog::ERROR, 'com_emundus');

			return 0;
		}
	}
}
