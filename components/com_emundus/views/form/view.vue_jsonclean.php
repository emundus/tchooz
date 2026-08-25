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

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
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

			$data         = new stdClass();
			$data->errors = [];

			$app    = Factory::getApplication();
			$formid = $app->input->getInt('formid', 0);

			if (empty($formid))
			{
				Log::add('component/com_emundus/views/view.vue_jsonclean | Missing or invalid formid: ' . $app->input->getString('formid', ''), Log::ERROR, 'com_emundus');

				return 0;
			}

			$fabrikRepository = new FabrikRepository(true);
			$fabrikFactory    = new FabrikFactory($fabrikRepository);
			$fabrikRepository->setFactory($fabrikFactory);

			$form = $fabrikRepository->getFormById($formid);

			if (empty($form))
			{
				Log::add('component/com_emundus/views/view.vue_jsonclean | Form ' . $formid . ' not found or unpublished', Log::ERROR, 'com_emundus');

				return 0;
			}

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

					if(in_array($element->getName(), ['id', 'user', 'time_date', 'fnum', 'date_time', 'parent_id']))
					{
						continue;
					}

					$elementParams = $element->getParams();

					$elementObject->id       = $element->getId();
					$elementObject->name     = $element->getName();
					$elementObject->group_id = $group->getId();
					$elementObject->plugin   = $element->getPlugin()->value;
					$elementObject->hidden   = $element->getHidden();
					$elementObject->eval     = $element->getEval();
					$elementObject->db_table_name = $element->getDbTableName();

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

					if(!empty($element->getAlias()))
					{
						$elementParams->alias = $element->getAlias();
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
							$joinValColumnConcat = str_replace('{thistable}', $elementParams->join_db_name, $elementParams->join_val_column_concat);
							$joinValColumnConcat = str_replace('{shortlang}', $lang, $joinValColumnConcat);
							$select[]            = 'CONCAT(' . $joinValColumnConcat . ') AS label';
						}

						$query->clear()
							->select($select)
							->from($db->qn($elementParams->join_db_name))
							->where($db->qn($elementParams->join_key_column) . ' IS NOT NULL');

						try {
							$db->setQuery($query, 0, 1);
							$exampleData = $db->loadObject();

							$elementObject->example_data        = $exampleData;
							$elementObject->please_select_label = Text::_($elementParams->database_join_noselectionlabel);
						}
						catch (Exception $e)
						{
							Log::addLogger(['text_file' => 'com_emundus.view.form'], Log::ERROR, 'com_emundus.view.form');
							Log::add('Failed to get example data for element ' . $element->getId() . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.view.form');

							// Likely a misconfiguration in jos_emundus_datas_library (e.g. translation set to 1 while the matching columns do not exist in the joined table).
							$elementLabel = !empty($element->getLabel()) ? Text::_($element->getLabel()) : $element->getName() . ' [' . $element->getId() . ']';
							$error          = new stdClass();
							$error->element = $element->getId();
							$error->label   = $elementLabel;
							$error->table   = $elementParams->join_db_name;
							$error->message = Text::sprintf('COM_EMUNDUS_FORM_BUILDER_ELEMENT_DATABASEJOIN_ERROR', $elementLabel, $elementParams->join_db_name);
							$error->details = $e->getMessage();
							$data->errors[] = $error;

							// Keep a stable shape so the front-end preview does not crash on the error path.
							$elementObject->example_data        = null;
							$elementObject->please_select_label = Text::_($elementParams->database_join_noselectionlabel);
						}
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
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/views/view.vue_jsonclean | Cannot getting the form datas : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return 0;
		}
	}
}
