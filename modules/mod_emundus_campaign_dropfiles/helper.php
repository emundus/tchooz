<?php
defined('_JEXEC') or die('Access Deny');

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;

jimport('joomla.access.access');

class modEmundusCampaignDropfilesHelper
{

	public function getFiles($column = null, $cid = null, $fnum = null)
	{
		$files = [];

		$app   = Factory::getApplication();
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$id    = $app->input->get('id') ? $app->input->getInt('id', null) : $app->input->getInt('cid', null);
		$id    = empty($id) ? $cid : $id;
		if (empty($id))
		{
			$menu_params = $app->getMenu()->getActive()->getParams();
			$id          = $menu_params->get('com_emundus_programme_campaign_id', 0);
		}

		$groupUser = JFactory::getUser()->getAuthorisedGroups();
		$dateTime  = new Date('now', 'UTC');
		$now       = $dateTime->toSQL();

		// If empty id module is probably on a form
		if (!empty($fnum))
		{
			// we should check current campaign workflow and get files from it if there are any
			require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			$m_workflow    = new EmundusModelWorkflow;
			$current_phase = $m_workflow->getCurrentWorkflowStepFromFile($fnum);

			if (!empty($current_phase->id) && !empty($current_phase->documents))
			{
				foreach ($current_phase->documents as $key => $document)
				{
					$file_ext = explode('.', $document->href);
					$file_ext = end($file_ext);

					$file                 = new stdClass();
					$file->id             = $key;
					$file->catid          = 0;
					$file->title_file     = $document->title;
					$file->ext            = $file_ext;
					$file->title_category = 'Documents';
					$file->href           = $document->href;

					$files[] = $file;
				}

				return $files;
			}
		}

		$current_profile = $app->getSession()->get('emundusUser')->profile;

		if (!empty($column))
		{
			try
			{
				$query->clear()
					->select([$db->quoteName('df.id', 'id'), $db->quoteName('df.catid', 'catid'), $db->quoteName('df.title', 'title_file'), $db->quoteName('df.ext', 'ext'), $db->quoteName('cat.path', 'title_category')])
					->from($db->quoteName('#__emundus_campaign_workflow_repeat_' . $column, 'cdf'))
					->leftJoin($db->quoteName('jos_emundus_campaign_workflow', 'cw') . ' ON ' . $db->quoteName('cw.id') . ' = ' . $db->quoteName('cdf.parent_id'))
					->leftJoin($db->quoteName('jos_dropfiles_files', 'df') . ' ON ' . $db->quoteName('df.id') . ' = ' . $db->quoteName('cdf.' . $column))
					->leftJoin($db->quoteName('jos_categories', 'cat') . ' ON ' . $db->quoteName('cat.id') . ' = ' . $db->quoteName('df.catid'))
					->where($db->quoteName('cw.profile') . ' = ' . $db->quote($current_profile))
					->order($db->quoteName('df.ordering'));
				$db->setQuery($query);
				$files = $db->loadObjectList();
			}
			catch (Exception $e)
			{
				return false;
			}
		}
		elseif (!empty($id))
		{
			$query
				->clear()
				->select([$db->quoteName('df.id', 'id'), $db->quoteName('df.catid', 'catid'), $db->quoteName('df.title', 'title_file'), $db->quoteName('df.ext', 'ext'), $db->quoteName('cat.path', 'title_category')])
				->from($db->quoteName('jos_dropfiles_files', 'df'))
				->leftJoin($db->quoteName('jos_categories', 'cat') . ' ON ' . $db->quoteName('cat.id') . ' = ' . $db->quoteName('df.catid'))
				->where($db->quoteName('df.publish') . ' <= ' . $db->quote($now))
				->andWhere([$db->quoteName('df.publish_down') . ' >= ' . $db->quote($now), $db->quoteName('df.publish_down') . ' = ' . $query->quote('0000-00-00 00:00:00')])
				->andWhere($db->quoteName('df.state') . ' = 1')
				->andWhere($db->quoteName('cat.extension') . ' = ' . $db->quote('com_dropfiles'))
				->andWhere('json_valid(`cat`.`params`)')
				->andWhere('json_extract(`cat`.`params`, "$.idCampaign") LIKE ' . $db->quote('"' . $id . '"'))
				->andWhere($db->quoteName('cat.access') . ' IN (' . implode(' , ', $groupUser) . ')')
				->order('df.ordering');

			try
			{
				$db->setQuery($query);
				$files = $db->loadObjectList();
			}
			catch (Exception $e)
			{
				return false;
			}
		}

		foreach ($files as $file)
		{
			$file->href = 'files/' . $file->catid . '/' . $file->title_category . '/' . $file->id . '/' . $file->title_file . '.' . $file->ext;
		}

		return $files;
	}
}