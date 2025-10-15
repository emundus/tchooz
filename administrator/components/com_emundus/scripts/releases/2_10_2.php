<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusHelperUpdate;
use Joomla\CMS\Language\Text;

class Release2_10_2Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$tasks = [];

		try
		{
			$query = $this->db->getQuery(true);

			// Create a translation tag for each default column of panel elements
			$query->clear()
				->select(['id', 'group_id', $this->db->quoteName('default')])
				->from($this->db->quoteName('#__fabrik_elements'))
				->where($this->db->quoteName('plugin') . ' = ' . $this->db->quote('panel'));
			$this->db->setQuery($query);
			$panel_elements = $this->db->loadObjectList();

			foreach ($panel_elements as $element)
			{
				// Check if not already done
				$query->clear()
					->select('COUNT(id)')
					->from($this->db->quoteName('#__emundus_setup_languages'))
					->where($this->db->quoteName('tag') . ' = ' . $this->db->quote($element->default));
				$this->db->setQuery($query);
				$count = $this->db->loadResult();

				if ($count == 0)
				{
					// Remove whitespaces at beginning and end
					$element->default = trim($element->default);

					$tag = 'ELEMENT_' . $element->group_id . '_' . $element->id . '_DEFAULT';

					if ($tasks[] = EmundusHelperUpdate::insertTranslationsTag($tag, $element->default, 'override', $element->id, 'fabrik_elements', 'default'))
					{
						$tasks[] = EmundusHelperUpdate::insertTranslationsTag($tag, $element->default, 'override', $element->id, 'fabrik_elements', 'default', 'en-GB');

						// Update element default value to use the new tag
						$update_elt = (object) [
							'id'      => $element->id,
							'default' => $tag
						];

						$tasks[] = $this->db->updateObject('#__fabrik_elements', $update_elt, 'id');
					}
				}
			}
			//

			// Create a translation tag for each helptext of elements
			$query->clear()
				->select(['id', 'params', 'group_id'])
				->from($this->db->quoteName('#__fabrik_elements'))
				->where('JSON_EXTRACT(' . $this->db->quoteName('params') . ', ' . $this->db->quote('$.rollover') . ') <> ""');
			$this->db->setQuery($query);
			$elements_with_help = $this->db->loadObjectList();

			foreach ($elements_with_help as $element)
			{
				$params = json_decode($element->params);

				// Remove whitespaces at beginning and end
				$params->rollover = trim($params->rollover);

				// Do not translate rollover if tipseval = 1
				if ((empty($params->tipseval) || $params->tipseval != 1) && !empty($params->rollover))
				{
					// Check if not already done
					$query->clear()
						->select('COUNT(id)')
						->from($this->db->quoteName('#__emundus_setup_languages'))
						->where($this->db->quoteName('tag') . ' = ' . $this->db->quote($params->rollover));
					$this->db->setQuery($query);
					$count = $this->db->loadResult();

					if ($count == 0)
					{
						$tag = 'ELEMENT_HELP_' . $element->group_id . '_' . $element->id;

						if ($tasks[] = EmundusHelperUpdate::insertTranslationsTag($tag, $params->rollover, 'override', $element->id, 'fabrik_elements', 'rollover'))
						{
							$tasks[] = EmundusHelperUpdate::insertTranslationsTag($tag, $params->rollover, 'override', $element->id, 'fabrik_elements', 'rollover', 'en-GB');

							$params->rollover = $tag;
							// Update element params rollover value to use the new tag
							$update_elt = (object) [
								'id'     => $element->id,
								'params' => json_encode($params)
							];

							$tasks[] = $this->db->updateObject('#__fabrik_elements', $update_elt, 'id');
						}
					}
				}
			}

			$tasks[] = EmundusHelperUpdate::installExtension('PLG_FABRIK_FORM_EMUNDUSCAMPAIGNMORE', 'emunduscampaignmore', null, 'plugin', 1, 'fabrik_form');
			// Set it in campaigns more form
			$query->clear()
				->select('f.id,f.params')
				->from($this->db->quoteName('#__fabrik_lists', 'fl'))
				->leftJoin($this->db->quoteName('#__fabrik_forms', 'f') . ' ON ' . $this->db->quoteName('f.id') . ' = ' . $this->db->quoteName('fl.form_id'))
				->where($this->db->quoteName('fl.db_table_name') . ' = ' . $this->db->quote('jos_emundus_setup_campaigns_more'));
			$campaigns_more_forms = $this->db->setQuery($query)->loadObjectList();

			foreach ($campaigns_more_forms as $campaigns_more_form)
			{
				if (!empty($campaigns_more_form->id))
				{
					$params = json_decode($campaigns_more_form->params, true);

					if(!in_array('emunduscampaignmore', $params['plugins']))
					{
						$params['plugins'][]          = 'emunduscampaignmore';
						$params['plugin_locations'][] = 'both';
						$params['plugin_events'][]    = 'both';
						$params['plugin_state'][]     = 1;

						$update  = (object) [
							'id'     => $campaigns_more_form->id,
							'params' => json_encode($params),
						];
						$tasks[] = $this->db->updateObject('#__fabrik_forms', $update, 'id');
					}
				}
			}


			$result['status']  = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}