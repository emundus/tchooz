<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Jobs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs;

use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Table\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixAssetsJob extends TchoozJob
{
	public function __construct(
		private readonly object            $logger,
		private readonly DatabaseService $databaseServiceSource,
		private readonly DatabaseService $databaseService
	)
	{
		parent::__construct($logger);
	}

	public function execute(InputInterface $input, OutputInterface $output): void
	{
		if (!$this->fixAssets())
		{
			Log::add('Error while fixing assets', Log::ERROR, self::getJobName());

			throw new \RuntimeException('Error while fixing assets');
		}

		Log::add('Assets fixed', Log::INFO, self::getJobName());

		// Merge rules assets
		if (!$this->mergeRulesAssets())
		{
			Log::add('Error while merging rules assets', Log::ERROR, self::getJobName());

			throw new \RuntimeException('Error while merging rules assets');
		}

		Log::add('Rules assets merged', Log::INFO, self::getJobName());
	}

	private function fixAssets(): bool
	{
		$fixed = false;

		$query = $this->databaseService->getDatabase()->getQuery(true);

		$query->delete($this->databaseService->getDatabase()->quoteName('jos_assets'))
			->where($this->databaseService->getDatabase()->quoteName('parent_id') . ' <> 0');
		$this->databaseService->getDatabase()->setQuery($query);
		$deleted = $this->databaseService->getDatabase()->execute();

		if ($deleted)
		{
			$asset = Table::getInstance('Asset');

			$asset->loadByName('root.1');
			$rootId = (int) $asset->id;

			if ($rootId && ($asset->level != 0 || $asset->parent_id != 0))
			{
				$this->fixRoot($rootId);
			}

			if (!$asset->id)
			{
				$rootId = $this->getAssetRootId();
				$this->fixRoot($rootId);
			}

			if ($rootId)
			{
				// Insert extensions as assets
				$query->clear()
					->select('extension_id,name')
					->from($this->databaseService->getDatabase()->quoteName('jos_extensions'))
					->where($this->databaseService->getDatabase()->quoteName('type') . ' = ' . $this->databaseService->getDatabase()->quote('component'));
				$this->databaseService->getDatabase()->setQuery($query);
				$components = $this->databaseService->getDatabase()->loadObjectList();

				foreach ($components as $component)
				{
					$insert_asset = [
						'parent_id' => $rootId,
						'lft'       => 0,
						'rgt'       => 0,
						'level'     => 1,
						'name'      => $component->name,
						'title'     => $component->name,
						'rules'     => '{}'
					];
					$insert_asset = (object) $insert_asset;
					$this->databaseService->getDatabase()->insertObject('jos_assets', $insert_asset);
				}
				//

				// Insert menus as assets
				$query->clear()
					->select('id,title')
					->from($this->databaseService->getDatabase()->quoteName('jos_menu_types'));
				$this->databaseService->getDatabase()->setQuery($query);
				$menu_types = $this->databaseService->getDatabase()->loadObjectList();

				$query->clear()
					->select('id')
					->from($this->databaseService->getDatabase()->quoteName('jos_assets'))
					->where($this->databaseService->getDatabase()->quoteName('parent_id') . ' = ' . $rootId)
					->where($this->databaseService->getDatabase()->quoteName('name') . ' LIKE ' . $this->databaseService->getDatabase()->quote('com_menus'));
				$this->databaseService->getDatabase()->setQuery($query);
				$menu_parent_id = $this->databaseService->getDatabase()->loadResult();

				foreach ($menu_types as $menu_type)
				{
					$insert_asset = [
						'parent_id' => $menu_parent_id,
						'lft'       => 0,
						'rgt'       => 0,
						'level'     => 2,
						'name'      => 'com_menus.menu.' . $menu_type->id,
						'title'     => $menu_type->title,
						'rules'     => '{}'
					];
					$insert_asset = (object) $insert_asset;
					$this->databaseService->getDatabase()->insertObject('jos_assets', $insert_asset);

					$asset_id = $this->databaseService->getDatabase()->insertid();

					$query->clear()
						->update($this->databaseService->getDatabase()->quoteName('jos_menu_types'))
						->set($this->databaseService->getDatabase()->quoteName('asset_id') . ' = ' . $this->databaseService->getDatabase()->quote($asset_id))
						->where($this->databaseService->getDatabase()->quoteName('id') . ' = ' . $this->databaseService->getDatabase()->quote($menu_type->id));
					$this->databaseService->getDatabase()->setQuery($query);
					$this->databaseService->getDatabase()->execute();
				}
				//

				// Insert content category as assets
				$query->clear()
					->select('id,title,extension')
					->from($this->databaseService->getDatabase()->quoteName('jos_categories'))
					->where($this->databaseService->getDatabase()->quoteName('extension') . ' NOT LIKE ' . $this->databaseService->getDatabase()->quote('system'));
				$this->databaseService->getDatabase()->setQuery($query);
				$categories = $this->databaseService->getDatabase()->loadObjectList();

				foreach ($categories as $category)
				{
					$query->clear()
						->select('id')
						->from($this->databaseService->getDatabase()->quoteName('jos_assets'))
						->where($this->databaseService->getDatabase()->quoteName('parent_id') . ' = ' . $rootId)
						->where($this->databaseService->getDatabase()->quoteName('name') . ' LIKE ' . $this->databaseService->getDatabase()->quote($category->extension));
					$this->databaseService->getDatabase()->setQuery($query);
					$parent_id = $this->databaseService->getDatabase()->loadResult();

					$insert_asset = [
						'parent_id' => $parent_id,
						'lft'       => 0,
						'rgt'       => 0,
						'level'     => 2,
						'name'      => $category->extension . '.category.' . $category->id,
						'title'     => $category->title,
						'rules'     => '{}'
					];
					$insert_asset = (object) $insert_asset;
					$this->databaseService->getDatabase()->insertObject('jos_assets', $insert_asset);

					$asset_id = $this->databaseService->getDatabase()->insertid();

					$query->clear()
						->update($this->databaseService->getDatabase()->quoteName('jos_categories'))
						->set($this->databaseService->getDatabase()->quoteName('asset_id') . ' = ' . $this->databaseService->getDatabase()->quote($asset_id))
						->where($this->databaseService->getDatabase()->quoteName('id') . ' = ' . $this->databaseService->getDatabase()->quote($category->id));
					$this->databaseService->getDatabase()->setQuery($query);
					$this->databaseService->getDatabase()->execute();
				}
				//

				// Insert content articles as assets
				$query->clear()
					->select('id,title,catid')
					->from($this->databaseService->getDatabase()->quoteName('jos_content'));
				$this->databaseService->getDatabase()->setQuery($query);
				$articles = $this->databaseService->getDatabase()->loadObjectList();

				foreach ($articles as $article)
				{
					$query->clear()
						->select('id')
						->from($this->databaseService->getDatabase()->quoteName('jos_assets'))
						->where($this->databaseService->getDatabase()->quoteName('parent_id') . ' = ' . $rootId)
						->where($this->databaseService->getDatabase()->quoteName('name') . ' LIKE ' . $this->databaseService->getDatabase()->quote('com_content.category.' . $article->catid));
					$this->databaseService->getDatabase()->setQuery($query);
					$category_parent_id = $this->databaseService->getDatabase()->loadResult();

					if (!empty($category_parent_id))
					{
						$insert_asset = [
							'parent_id' => $category_parent_id,
							'lft'       => 0,
							'rgt'       => 0,
							'level'     => 3,
							'name'      => 'com_content.article.' . $article->id,
							'title'     => $article->title,
							'rules'     => '{}'
						];
						$insert_asset = (object) $insert_asset;
						$this->databaseService->getDatabase()->insertObject('jos_assets', $insert_asset);
					}
				}
				//

				// Insert workflow as assets
				$query->clear()
					->select('id,title')
					->from($this->databaseService->getDatabase()->quoteName('jos_workflows'));
				$this->databaseService->getDatabase()->setQuery($query);
				$workflows = $this->databaseService->getDatabase()->loadObjectList();

				$query->clear()
					->select('id')
					->from($this->databaseService->getDatabase()->quoteName('jos_assets'))
					->where($this->databaseService->getDatabase()->quoteName('parent_id') . ' = ' . $rootId)
					->where($this->databaseService->getDatabase()->quoteName('name') . ' LIKE ' . $this->databaseService->getDatabase()->quote('com_content'));
				$this->databaseService->getDatabase()->setQuery($query);
				$content_parent_id = $this->databaseService->getDatabase()->loadResult();

				foreach ($workflows as $workflow)
				{
					$insert_asset = [
						'parent_id' => $content_parent_id,
						'lft'       => 0,
						'rgt'       => 0,
						'level'     => 1,
						'name'      => 'com_content.workflow.' . $workflow->id,
						'title'     => $workflow->title,
						'rules'     => '{}'
					];
					$insert_asset = (object) $insert_asset;
					$this->databaseService->getDatabase()->insertObject('jos_assets', $insert_asset);

					$asset_id = $this->databaseService->getDatabase()->insertid();

					$query->clear()
						->update($this->databaseService->getDatabase()->quoteName('jos_workflows'))
						->set($this->databaseService->getDatabase()->quoteName('asset_id') . ' = ' . $this->databaseService->getDatabase()->quote($asset_id))
						->where($this->databaseService->getDatabase()->quoteName('id') . ' = ' . $this->databaseService->getDatabase()->quote($workflow->id));
					$this->databaseService->getDatabase()->setQuery($query);
					$this->databaseService->getDatabase()->execute();
				}
				//

				// Insert workflow stages as assets
				$query->clear()
					->select('id,title,workflow_id')
					->from($this->databaseService->getDatabase()->quoteName('jos_workflow_stages'));
				$this->databaseService->getDatabase()->setQuery($query);
				$stages = $this->databaseService->getDatabase()->loadObjectList();

				foreach ($stages as $stage)
				{
					$query->clear()
						->select('id')
						->from($this->databaseService->getDatabase()->quoteName('jos_assets'))
						->where($this->databaseService->getDatabase()->quoteName('name') . ' LIKE ' . $this->databaseService->getDatabase()->quote('com_content.workflow.' . $stage->workflow_id));
					$this->databaseService->getDatabase()->setQuery($query);
					$workflow_parent_id = $this->databaseService->getDatabase()->loadResult();

					if (!empty($workflow_parent_id))
					{
						$insert_asset = [
							'parent_id' => $workflow_parent_id,
							'lft'       => 0,
							'rgt'       => 0,
							'level'     => 3,
							'name'      => 'com_content.stage.' . $stage->id,
							'title'     => $stage->title,
							'rules'     => '{}'
						];
						$insert_asset = (object) $insert_asset;
						$this->databaseService->getDatabase()->insertObject('jos_assets', $insert_asset);

						$asset_id = $this->databaseService->getDatabase()->insertid();

						$query->clear()
							->update($this->databaseService->getDatabase()->quoteName('jos_workflow_stages'))
							->set($this->databaseService->getDatabase()->quoteName('asset_id') . ' = ' . $this->databaseService->getDatabase()->quote($asset_id))
							->where($this->databaseService->getDatabase()->quoteName('id') . ' = ' . $this->databaseService->getDatabase()->quote($stage->id));
						$this->databaseService->getDatabase()->setQuery($query);
						$this->databaseService->getDatabase()->execute();
					}
				}
				//

				// Insert workflow transitions as assets
				$query->clear()
					->select('id,title,workflow_id')
					->from($this->databaseService->getDatabase()->quoteName('jos_workflow_transitions'));
				$this->databaseService->getDatabase()->setQuery($query);
				$transitions = $this->databaseService->getDatabase()->loadObjectList();

				foreach ($transitions as $transition)
				{
					$query->clear()
						->select('id')
						->from($this->databaseService->getDatabase()->quoteName('jos_assets'))
						->where($this->databaseService->getDatabase()->quoteName('name') . ' LIKE ' . $this->databaseService->getDatabase()->quote('com_content.workflow.' . $transition->workflow_id));
					$this->databaseService->getDatabase()->setQuery($query);
					$workflow_parent_id = $this->databaseService->getDatabase()->loadResult();

					if (!empty($workflow_parent_id))
					{
						$insert_asset = [
							'parent_id' => $workflow_parent_id,
							'lft'       => 0,
							'rgt'       => 0,
							'level'     => 3,
							'name'      => 'com_content.transition.' . $transition->id,
							'title'     => $transition->title,
							'rules'     => '{}'
						];
						$insert_asset = (object) $insert_asset;
						$this->databaseService->getDatabase()->insertObject('jos_assets', $insert_asset);

						$asset_id = $this->databaseService->getDatabase()->insertid();

						$query->clear()
							->update($this->databaseService->getDatabase()->quoteName('jos_workflow_transitions'))
							->set($this->databaseService->getDatabase()->quoteName('asset_id') . ' = ' . $this->databaseService->getDatabase()->quote($asset_id))
							->where($this->databaseService->getDatabase()->quoteName('id') . ' = ' . $this->databaseService->getDatabase()->quote($transition->id));
						$this->databaseService->getDatabase()->setQuery($query);
						$this->databaseService->getDatabase()->execute();
					}
				}
				//

				// Insert languages as assets
				$query->clear()
					->select('lang_id, title')
					->from($this->databaseService->getDatabase()->quoteName('jos_languages'));
				$this->databaseService->getDatabase()->setQuery($query);
				$languages = $this->databaseService->getDatabase()->loadObjectList();

				$query->clear()
					->select('id')
					->from($this->databaseService->getDatabase()->quoteName('jos_assets'))
					->where($this->databaseService->getDatabase()->quoteName('parent_id') . ' = ' . $rootId)
					->where($this->databaseService->getDatabase()->quoteName('name') . ' LIKE ' . $this->databaseService->getDatabase()->quote('com_languages'));
				$this->databaseService->getDatabase()->setQuery($query);
				$language_parent_id = $this->databaseService->getDatabase()->loadResult();

				foreach ($languages as $language)
				{
					$insert_asset = [
						'parent_id' => $language_parent_id,
						'lft'       => 0,
						'rgt'       => 0,
						'level'     => 2,
						'name'      => 'com_languages.language.' . $language->lang_id,
						'title'     => $language->title,
						'rules'     => '{}'
					];
					$insert_asset = (object) $insert_asset;
					$this->databaseService->getDatabase()->insertObject('jos_assets', $insert_asset);

					$asset_id = $this->databaseService->getDatabase()->insertid();

					$query->clear()
						->update($this->databaseService->getDatabase()->quoteName('jos_languages'))
						->set($this->databaseService->getDatabase()->quoteName('asset_id') . ' = ' . $this->databaseService->getDatabase()->quote($asset_id))
						->where($this->databaseService->getDatabase()->quoteName('lang_id') . ' = ' . $this->databaseService->getDatabase()->quote($language->lang_id));
					$this->databaseService->getDatabase()->setQuery($query);
					$this->databaseService->getDatabase()->execute();
				}
				//

				// Insert modules as assets
				$query->clear()
					->select('id,title')
					->from($this->databaseService->getDatabase()->quoteName('jos_modules'));
				$this->databaseService->getDatabase()->setQuery($query);
				$modules = $this->databaseService->getDatabase()->loadObjectList();

				$query->clear()
					->select('id')
					->from($this->databaseService->getDatabase()->quoteName('jos_assets'))
					->where($this->databaseService->getDatabase()->quoteName('parent_id') . ' = ' . $rootId)
					->where($this->databaseService->getDatabase()->quoteName('name') . ' LIKE ' . $this->databaseService->getDatabase()->quote('com_modules'));
				$this->databaseService->getDatabase()->setQuery($query);
				$module_parent_id = $this->databaseService->getDatabase()->loadResult();

				foreach ($modules as $module)
				{
					$insert_asset = [
						'parent_id' => $module_parent_id,
						'lft'       => 0,
						'rgt'       => 0,
						'level'     => 2,
						'name'      => 'com_modules.module.' . $module->id,
						'title'     => $module->title,
						'rules'     => '{}'
					];
					$insert_asset = (object) $insert_asset;
					$this->databaseService->getDatabase()->insertObject('jos_assets', $insert_asset);

					$asset_id = $this->databaseService->getDatabase()->insertid();

					$query->clear()
						->update($this->databaseService->getDatabase()->quoteName('jos_modules'))
						->set($this->databaseService->getDatabase()->quoteName('asset_id') . ' = ' . $this->databaseService->getDatabase()->quote($asset_id))
						->where($this->databaseService->getDatabase()->quoteName('id') . ' = ' . $this->databaseService->getDatabase()->quote($module->id));
					$this->databaseService->getDatabase()->setQuery($query);
					$this->databaseService->getDatabase()->execute();
				}
				//

				// Insert schedules as assets
				$query->clear()
					->select('id,title')
					->from($this->databaseService->getDatabase()->quoteName('jos_scheduler_tasks'));
				$this->databaseService->getDatabase()->setQuery($query);
				$schedules = $this->databaseService->getDatabase()->loadObjectList();

				$query->clear()
					->select('id')
					->from($this->databaseService->getDatabase()->quoteName('jos_assets'))
					->where($this->databaseService->getDatabase()->quoteName('parent_id') . ' = ' . $rootId)
					->where($this->databaseService->getDatabase()->quoteName('name') . ' LIKE ' . $this->databaseService->getDatabase()->quote('com_scheduler'));
				$this->databaseService->getDatabase()->setQuery($query);
				$schedule_parent_id = $this->databaseService->getDatabase()->loadResult();

				foreach ($schedules as $schedule)
				{
					$insert_asset = [
						'parent_id' => $schedule_parent_id,
						'lft'       => 0,
						'rgt'       => 0,
						'level'     => 2,
						'name'      => 'com_scheduler.task.' . $schedule->id,
						'title'     => $schedule->title,
						'rules'     => '{}'
					];
					$insert_asset = (object) $insert_asset;
					$this->databaseService->getDatabase()->insertObject('jos_assets', $insert_asset);

					$asset_id = $this->databaseService->getDatabase()->insertid();

					$query->clear()
						->update($this->databaseService->getDatabase()->quoteName('jos_scheduler_tasks'))
						->set($this->databaseService->getDatabase()->quoteName('asset_id') . ' = ' . $this->databaseService->getDatabase()->quote($asset_id))
						->where($this->databaseService->getDatabase()->quoteName('id') . ' = ' . $this->databaseService->getDatabase()->quote($schedule->id));
					$this->databaseService->getDatabase()->setQuery($query);
					$this->databaseService->getDatabase()->execute();
				}
				//

				$fixed = $asset->rebuild($rootId);
			}
		}

		return $fixed;
	}

	private function getAssetRootId(): bool
	{
		// Test for a unique record with parent_id = 0
		$query = $this->databaseService->getDatabase()->getQuery(true);

		$query->select($this->databaseService->getDatabase()->quote('id'))
			->from($this->databaseService->getDatabase()->quoteName('jos_assets'))
			->where($this->databaseService->getDatabase()->quote('parent_id') . ' = 0');
		$result = $this->databaseService->getDatabase()->setQuery($query)->loadColumn();

		if (count($result) == 1)
		{
			return $result[0];
		}

		// Test for a unique record with lft = 0
		$query->clear()
			->select('id')
			->from($this->databaseService->getDatabase()->quoteName('jos_assets'))
			->where($this->databaseService->getDatabase()->quote('lft') . ' = 0');

		$result = $this->databaseService->getDatabase()->setQuery($query)->loadColumn();

		if (count($result) == 1)
		{
			return $result[0];
		}

		// Test for a unique record alias = root
		$query->clear()
			->select($this->databaseService->getDatabase()->quoteName('id'))
			->from($this->databaseService->getDatabase()->quoteName('jos_assets'))
			->where('name LIKE ' . $this->databaseService->getDatabase()->quote('root%'));

		$result = $this->databaseService->getDatabase()->setQuery($query)->loadColumn();

		if (count($result) == 1)
		{
			return $result[0];
		}

		return false;
	}

	private function fixRoot($rootId): bool
	{
		$query = $this->databaseService->getDatabase()->getQuery(true);

		$query->update($this->databaseService->getDatabase()->quoteName('jos_assets'))
			->set($this->databaseService->getDatabase()->quoteName('parent_id') . ' = 0 ')
			->set($this->databaseService->getDatabase()->quoteName('level') . ' =  0 ')
			->set($this->databaseService->getDatabase()->quoteName('lft') . ' = 1 ')
			->set($this->databaseService->getDatabase()->quoteName('name') . ' = ' . $this->databaseService->getDatabase()->quote('root.' . (int) $rootId));
		$query->where('id = ' . (int) $rootId);

		$this->databaseService->getDatabase()->setQuery($query);
		return $this->databaseService->getDatabase()->execute();
	}

	private function mergeRulesAssets(): bool
	{
		$merged = false;

		$query_source = $this->databaseServiceSource->getDatabase()->getQuery(true);
		$query        = $this->databaseService->getDatabase()->getQuery(true);

		$query_source->select('name,rules')
			->from($this->databaseServiceSource->getDatabase()->quoteName('jos_assets'));
		$this->databaseServiceSource->getDatabase()->setQuery($query_source);
		$assets = $this->databaseServiceSource->getDatabase()->loadAssocList();

		foreach ($assets as $asset)
		{
			$query->clear()
				->update($this->databaseService->getDatabase()->quoteName('jos_assets'))
				->set($this->databaseService->getDatabase()->quoteName('rules') . ' = ' . $this->databaseService->getDatabase()->quote($asset['rules']))
				->where($this->databaseService->getDatabase()->quoteName('name') . ' = ' . $this->databaseService->getDatabase()->quote($asset['name']));
			$this->databaseService->getDatabase()->setQuery($query);
			$merged = $this->databaseService->getDatabase()->execute();
		}

		return $merged;
	}


	public static function getJobName(): string
	{
		return 'Fixing assets';
	}

	public static function getJobDescription(): ?string
	{
		return 'Fixing assets table';
	}
}