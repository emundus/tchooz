<?php
/**
 * @package     Tchooz\Services\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Addons\Handlers;

use Joomla\CMS\Factory;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Entities\Emails\TagEntity;
use Tchooz\Enums\Automation\EventCategoryEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Services\Addons\AbstractAddonHandler;

class ChoicesAddonHandler extends AbstractAddonHandler
{
	public function onActivate(): bool
	{
		return $this->applyState(true);
	}

	public function onDeactivate(): bool
	{
		return $this->applyState(false);
	}

	private function applyState(bool $state): bool
	{
		$tasks = [];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$state_integer = $state ? 1 : 0;

		try
		{
			// Toggle step type
			$query->update($db->quoteName('#__emundus_setup_step_types'))
				->set($db->quoteName('published') . ' = ' . $db->quote($state_integer))
				->where($db->quoteName('label') . ' = ' . $db->quote('COM_EMUNDUS_WORKFLOW_STEP_TYPE_CHOICES'));
			$db->setQuery($query);
			$tasks[] = $db->execute();

			// Toggle acl
			$actionRepository = new ActionRepository();
			$choicesAction = $actionRepository->getByName('application_choices');
			$choicesAction->setStatus($state);
			$tasks[] = $actionRepository->flush($choicesAction);

			// Toggle application menu
			$query->clear()
				->update($db->quoteName('#__menu'))
				->set($db->quoteName('published') . ' = ' . $db->quote($state_integer))
				->where($db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_emundus&view=application&layout=applicationchoices&format=raw'))
				->where($db->quoteName('menutype') . ' = ' . $db->quote('application'));
			$db->setQuery($query);
			$tasks[] = $db->execute();

			// Toggle applicant menu
			$query->clear()
				->update($db->quoteName('#__menu'))
				->set($db->quoteName('published') . ' = ' . $db->quote($state_integer))
				->where($db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_emundus&view=application_choices'))
				->where($db->quoteName('menutype') . ' = ' . $db->quote('applicantmenu'));
			$db->setQuery($query);
			$tasks[] = $db->execute();

			// Toggle List menu
			$query->clear()
				->update($db->quoteName('#__menu'))
				->set($db->quoteName('published') . ' = ' . $db->quote($state_integer))
				->where($db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_emundus&view=application_choices&layout=list'))
				->where($db->quoteName('menutype') . ' = ' . $db->quote('onboardingmenu'));
			$db->setQuery($query);
			$tasks[] = $db->execute();

			// Toggle the filter on all files views
			$query->clear()
				->select('id, params')
				->from($db->quoteName('#__menu'))
				->where($db->quoteName('link') . ' LIKE ' . $db->quote('index.php?option=com_emundus&view=files'));
			$db->setQuery($query);
			$menus = $db->loadObjectList();

			foreach ($menus as $menu) {
				$params = json_decode($menu->params, true);
				$params['filter_application_choices'] = $state_integer;

				$menu->params = json_encode($params);
				$db->updateObject('#__menu', $menu, 'id');
			}

			// toggle events of category choices
			$query->clear()
				->update($db->quoteName('#__emundus_plugin_events'))
				->set($db->quoteName('available') . ' = ' . $db->quote($state_integer))
				->where($db->quoteName('category') . ' = ' . $db->quote(EventCategoryEnum::CHOICES->value));

			$db->setQuery($query);
			$tasks[] = $db->execute();

			// Toggle the [VOEU] email tag, created on the fly on installations older than the tag
			$query->clear()
				->select($db->quoteName('id'))
				->from($db->quoteName('#__emundus_setup_tags'))
				->where($db->quoteName('tag') . ' = ' . $db->quote(TagEntity::CHOICES_TAG));
			$db->setQuery($query);
			$tag_id = $db->loadResult();

			if (empty($tag_id))
			{
				$tag = (object) [
					'date_time'   => date('Y-m-d H:i:s'),
					'tag'         => TagEntity::CHOICES_TAG,
					'request'     => '[' . TagEntity::CHOICES_TAG . ']',
					'description' => 'Liste des vœux du candidat',
					'published'   => $state_integer,
				];

				$tasks[] = $db->insertObject('#__emundus_setup_tags', $tag);
			}
			else
			{
				$query->clear()
					->update($db->quoteName('#__emundus_setup_tags'))
					->set($db->quoteName('published') . ' = ' . $db->quote($state_integer))
					->where($db->quoteName('tag') . ' = ' . $db->quote(TagEntity::CHOICES_TAG));
				$db->setQuery($query);
				$tasks[] = $db->execute();
			}
		}
		catch (\Exception $e)
		{
			$tasks[] = false;
		}

		return !in_array(false, $tasks, true);
	}
}

