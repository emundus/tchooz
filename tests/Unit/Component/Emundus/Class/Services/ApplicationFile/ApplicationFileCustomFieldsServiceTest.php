<?php

namespace Unit\Component\Emundus\Class\Services\ApplicationFile;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Services\ApplicationFile\ApplicationFileCustomFieldsService;

/**
 * @package     Unit\Component\Emundus\Class\Services\ApplicationFile
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\ApplicationFile\ApplicationFileCustomFieldsService
 */
class ApplicationFileCustomFieldsServiceTest extends UnitTestCase
{
	private const CUSTOM_ELEMENT_NAME = 'unit_test_custom_field';

	private array $createdElementIds = [];

	protected function tearDown(): void
	{
		if (!empty($this->createdElementIds))
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery()
				->delete($db->quoteName('#__fabrik_elements'))
				->where($db->quoteName('id') . ' IN (' . implode(',', array_map('intval', $this->createdElementIds)) . ')');
			$db->setQuery($query)->execute();
		}

		parent::tearDown();
	}

	private function buildElement(string $name, ElementPluginEnum $plugin, string $params = '', string $groupParams = '', int $groupId = 1): FabrikElementEntity
	{
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);

		return new FabrikElementEntity(0, $name, $groupId, $plugin, $name, new \DateTime(), $user, $params, 'jos_emundus_campaign_candidature', '', $groupParams);
	}

	private function mockApplicationFileRepository(array $customColumns): ApplicationFileRepository
	{
		$applicationFileRepository = $this->createMock(ApplicationFileRepository::class);
		$applicationFileRepository->method('getCustomColumnNames')->willReturn($customColumns);

		return $applicationFileRepository;
	}

	private function buildService(array $elements): ApplicationFileCustomFieldsService
	{
		$fabrikRepository = $this->createMock(FabrikRepository::class);
		$fabrikRepository->method('getElements')->willReturn($elements);

		$columns = array_map(fn(FabrikElementEntity $element) => $element->getName(), $elements);

		return new ApplicationFileCustomFieldsService($fabrikRepository, $this->dataset['coordinator'], $this->mockApplicationFileRepository($columns));
	}

	private function createFormElement(string $name, int $published): int
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery()
			->select($db->quoteName('group_id'))
			->from($db->quoteName('#__fabrik_formgroup'))
			->where($db->quoteName('form_id') . ' = ' . ApplicationFileCustomFieldsService::FORM_ID)
			->order($db->quoteName('ordering') . ' ASC');
		$groupId = (int) $db->setQuery($query, 0, 1)->loadResult();
		$this->assertNotEmpty($groupId, 'Le formulaire 102 doit avoir au moins un groupe');

		$element = (object) [
			'name'       => $name,
			'group_id'   => $groupId,
			'plugin'     => 'field',
			'label'      => $name,
			'created'    => date('Y-m-d H:i:s'),
			'created_by' => $this->dataset['coordinator'],
			'modified'   => date('Y-m-d H:i:s'),
			'published'  => $published,
			'params'     => '{}',
		];
		$db->insertObject('#__fabrik_elements', $element);
		$id = (int) $db->insertid();

		$this->createdElementIds[] = $id;

		return $id;
	}

	// -------------------------------------------------------------------------
	// getImportableElements
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationFileCustomFieldsService::getImportableElements
	 * @return void
	 */
	public function testGetImportableElementsQueriesPublishedElementsBackedByCustomColumns(): void
	{
		$fabrikRepository = $this->createMock(FabrikRepository::class);
		$fabrikRepository->expects($this->once())
			->method('getElements')
			->with([
				'form_id'   => ApplicationFileCustomFieldsService::FORM_ID,
				'published' => 1,
				'name'      => ['custom_text'],
			], 0)
			->willReturn([]);

		$service = new ApplicationFileCustomFieldsService($fabrikRepository, $this->dataset['coordinator'], $this->mockApplicationFileRepository(['custom_text']));
		$service->getImportableElements();
		$service->getImportableElements();
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationFileCustomFieldsService::getImportableElements
	 * @return void
	 */
	public function testGetImportableElementsReturnsEmptyWithoutQueryingFabrikWhenTableHasNoCustomColumn(): void
	{
		$fabrikRepository = $this->createMock(FabrikRepository::class);
		$fabrikRepository->expects($this->never())->method('getElements');

		$service = new ApplicationFileCustomFieldsService($fabrikRepository, $this->dataset['coordinator'], $this->mockApplicationFileRepository([]));

		$this->assertSame([], $service->getImportableElements(), 'Sans colonne spécifique dans la table, aucun élément ne doit être importable');
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationFileCustomFieldsService::getImportableElements
	 * @return void
	 */
	public function testGetImportableElementsExcludesNonImportablePlugins(): void
	{
		$service = $this->buildService([
			$this->buildElement('custom_text', ElementPluginEnum::FIELD),
			$this->buildElement('custom_display', ElementPluginEnum::DISPLAY),
			$this->buildElement('custom_upload', ElementPluginEnum::FILEUPLOAD),
		]);

		$this->assertSame(['custom_text'], $service->getImportableColumnNames(), 'Seuls les éléments porteurs d\'une valeur importable doivent être retenus');
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationFileCustomFieldsService::getImportableElements
	 * @return void
	 */
	public function testGetImportableElementsExcludesElementsStoredInJoinTable(): void
	{
		$service = $this->buildService([
			$this->buildElement('custom_single_join', ElementPluginEnum::DATABASEJOIN, '{"database_join_display_type":"dropdown"}'),
			$this->buildElement('custom_multi_join', ElementPluginEnum::DATABASEJOIN, '{"database_join_display_type":"checkbox"}'),
			$this->buildElement('custom_multilist_join', ElementPluginEnum::DATABASEJOIN, '{"database_join_display_type":"multilist"}'),
			$this->buildElement('custom_repeated', ElementPluginEnum::FIELD, '', '{"repeat_group_button":"1"}'),
		]);

		$this->assertSame(['custom_single_join'], $service->getImportableColumnNames(), 'Les éléments stockés en table de jointure ne doivent pas être importables');
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationFileCustomFieldsService::getImportableElements
	 * @return void
	 */
	public function testGetImportableElementsReturnsPublishedCustomElementsBackedByTableColumn(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$existingColumns = $db->setQuery('SHOW COLUMNS FROM #__emundus_campaign_candidature')->loadColumn();
		foreach (array_intersect([self::CUSTOM_ELEMENT_NAME, self::CUSTOM_ELEMENT_NAME . '_unpublished'], $existingColumns) as $leftoverColumn)
		{
			$db->setQuery('ALTER TABLE #__emundus_campaign_candidature DROP COLUMN ' . $leftoverColumn)->execute();
		}
		$db->setQuery('ALTER TABLE #__emundus_campaign_candidature ADD COLUMN ' . self::CUSTOM_ELEMENT_NAME . ' VARCHAR(255) NULL, ADD COLUMN ' . self::CUSTOM_ELEMENT_NAME . '_unpublished VARCHAR(255) NULL')->execute();

		try
		{
			$this->createFormElement(self::CUSTOM_ELEMENT_NAME, 1);
			$this->createFormElement(self::CUSTOM_ELEMENT_NAME . '_unpublished', 0);
			$this->createFormElement(self::CUSTOM_ELEMENT_NAME . '_without_column', 1);

			$columns = (new ApplicationFileCustomFieldsService(userId: $this->dataset['coordinator']))->getImportableColumnNames();

			$this->assertContains(self::CUSTOM_ELEMENT_NAME, $columns, 'L\'élément personnalisé publié du formulaire 102 doit être importable');
			$this->assertNotContains(self::CUSTOM_ELEMENT_NAME . '_unpublished', $columns, 'Un élément dépublié ne doit pas être importable');
			$this->assertNotContains(self::CUSTOM_ELEMENT_NAME . '_without_column', $columns, 'Un élément sans colonne dans la table du dossier (groupe joint) ne doit pas être importable');
			$this->assertEmpty(array_intersect($columns, ApplicationFileRepository::SYSTEM_COLUMNS), 'Aucune colonne système ne doit être importable');
		}
		finally
		{
			$db->setQuery('ALTER TABLE #__emundus_campaign_candidature DROP COLUMN ' . self::CUSTOM_ELEMENT_NAME . ', DROP COLUMN ' . self::CUSTOM_ELEMENT_NAME . '_unpublished')->execute();
		}
	}

	// -------------------------------------------------------------------------
	// filterImportableValues
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationFileCustomFieldsService::filterImportableValues
	 * @return void
	 */
	public function testFilterImportableValuesKeepsOnlyImportableColumns(): void
	{
		$service = $this->buildService([
			$this->buildElement('custom_text', ElementPluginEnum::FIELD),
			$this->buildElement('custom_empty', ElementPluginEnum::FIELD),
		]);

		$filtered = $service->filterImportableValues([
			'custom_text'   => 'value',
			'custom_empty'  => '',
			'status'        => 99,
			'applicant_id'  => 1,
			'unknown_field' => 'x',
		]);

		$this->assertSame(['custom_text' => 'value', 'custom_empty' => ''], $filtered, 'Seules les colonnes importables doivent être conservées, le filtrage des vides relevant du repository');
	}
}
