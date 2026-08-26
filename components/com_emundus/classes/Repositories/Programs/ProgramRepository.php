<?php
/**
 * @package     Tchooz\Repositories\Programs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Programs;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Joomla\Database\ParameterType;
use Tchooz\Attributes\TableAttribute;
use Tchooz\EmundusResponse;
use Tchooz\Entities\Automation\EventContextEntity;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Factories\Programs\ProgramFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\Groups\GroupRepository;
use Tchooz\Services\UploadService;
use Tchooz\Traits\TraitDispatcher;

#[TableAttribute(
	table: '#__emundus_setup_programmes',
	alias: 'esp',
	columns: [
		'id',
		'code',
		'label',
		'notes',
		'published',
		'programmes',
		'synthesis',
		'apply_online',
		'ordering',
		'logo',
		'color',
		'long_description',
		'must_open_rights'
	]
)
]
class ProgramRepository extends EmundusRepository
{
	use TraitDispatcher;

	const NAME = 'program';

	private ProgramFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, self::NAME, self::class);
		$this->factory = new ProgramFactory();
	}

	public function flush(ProgramEntity $programEntity, ?User $user = null): bool
	{
		if (empty($user))
		{
			$user = Factory::getApplication()->getIdentity();
		}

		$programEntity->sanitize();

		$data = [
			'code'             => $programEntity->getSlug(),
			'label'            => $programEntity->getLabel(),
			'notes'            => $programEntity->getNotes(),
			'long_description' => $programEntity->getLongDescription(),
			'published'        => $programEntity->isPublished() ? 1 : 0,
			'programmes'       => $programEntity->getProgrammes(),
			'synthesis'        => $programEntity->getSynthesis(),
			'apply_online'     => $programEntity->isApplyOnline() ? 1 : 0,
			'logo'             => $programEntity->getLogo(),
			'must_open_rights' => $programEntity->isMustOpenRights() ? 1 : 0
		];

		$isNew = empty($programEntity->getId());

		if ($isNew)
		{
			$this->dispatchJoomlaEvent('onBeforeProgramCreate', ['data' => $data]);
		}

		$data = (object) $data;
		if ($isNew)
		{
			if (!$this->db->insertObject($this->tableName, $data, 'id'))
			{
				throw new \Exception('Failed to insert program');
			}

			$programEntity->setId((int) $data->id);
		}
		else
		{
			$data->id = $programEntity->getId();
			if (!$this->db->updateObject($this->tableName, $data, 'id'))
			{
				throw new \Exception('Failed to update program');
			}
		}

		// Clear cache
		$hCache = new \EmundusHelperCache();
		$hCache->clean(false);

		if ($isNew)
		{
			$this->dispatchJoomlaEvent('onAfterProgramCreate', ['programme' => $data, 'user_id' => $user->id, 'context' => new EventContextEntity($user, [], [], [])]);
		}

		return true;
	}

	public function getById(int $id): ?ProgramEntity
	{
		$program_entity = null;

		$query = $this->db->getQuery(true);
		$query->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where('id = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		$program = $this->db->loadAssoc();

		if (!empty($program))
		{
			$program_entity = $this->factory->fromDbObject($program);
		}

		return $program_entity;
	}

	public function getByCode(string $code): ?ProgramEntity
	{
		$program_entity = null;

		$query = $this->db->getQuery(true);

		$query->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where('code = ' . $this->db->quote($code));
		$this->db->setQuery($query);
		$program = $this->db->loadAssoc();

		if (!empty($program))
		{
			$program_entity = $this->factory->fromDbObject($program);
		}

		return $program_entity;
	}

	public function codeExists(string $code, array $excludedIds = []): bool
	{
		$query = $this->db->getQuery(true);

		$query->select('COUNT(' . $this->db->quoteName('id') . ')')
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where($this->db->quoteName('code') . ' = ' . $this->db->quote($code));

		$excludedIds = array_filter(array_map('intval', $excludedIds), fn(int $id): bool => $id > 0);

		if (!empty($excludedIds))
		{
			$query->where($this->db->quoteName('id') . ' NOT IN (' . implode(',', $excludedIds) . ')');
		}

		$this->db->setQuery($query);

		return (int) $this->db->loadResult() > 0;
	}

	public function getCodesByIds(array $ids): array
	{
		$codes = [];

		if (!empty($ids))
		{
			$query = $this->db->getQuery(true);
			$query->select('code')
				->from($this->db->quoteName($this->tableName, $this->alias))
				->where('id IN (' . implode(',', array_map([$this->db, 'quote'], $ids)) . ')');
			$this->db->setQuery($query);
			$codes = $this->db->loadColumn();
		}

		return $codes;
	}

	public function getCategories(): array
	{
		$cacheKey = 'program_categories';
		if ($this->cache && $this->cache->contains($cacheKey))
		{
			return $this->cache->get($cacheKey);
		}

		$query = $this->db->getQuery(true);
		$query->select('programmes')
			->from($this->db->quoteName($this->tableName))
			->where('published = 1')
			->order('programmes ASC');
		$this->db->setQuery($query);
		$categories = $this->db->loadColumn();
		$categories = array_filter(array_unique($categories));

		if ($this->cache && !empty($categories))
		{
			$this->cache->store($categories, $cacheKey);
		}

		return $categories;
	}

	/**
	 * @param   string  $programCode
	 *
	 * @return array<GroupEntity>
	 *
	 * @since version
	 */
	public function getGroupsByProgramCode(string $programCode): array
	{
		$groups = [];

		$query = $this->db->getQuery(true);
		$query->select('parent_id')
			->from($this->db->quoteName('#__emundus_setup_groups_repeat_course'))
			->where('course = ' . $this->db->quote($programCode));
		$this->db->setQuery($query);
		$groupIds = $this->db->loadColumn();

		$groupRepository = new GroupRepository();
		if (!empty($groupIds))
		{
			$groups = $groupRepository->getItemsByField('id', $groupIds, true);
		}

		return $groups;
	}

	/**
	 * Campaigns attached to a program, through `training` (the code) or `program_id`. The two links
	 * disagree on older data, so either one is enough to block a deletion.
	 *
	 * @param   int     $id    The program id.
	 * @param   string  $code  The program code.
	 *
	 * @return array<object> Campaigns as {id, label, year}.
	 */
	public function getAssociatedCampaigns(int $id, string $code): array
	{
		$query = $this->db->getQuery(true);

		$query->select([$this->db->quoteName('id'), $this->db->quoteName('label'), $this->db->quoteName('year')])
			->from($this->db->quoteName('#__emundus_setup_campaigns'))
			->where('(' . $this->db->quoteName('program_id') . ' = :id OR ' . $this->db->quoteName('training') . ' = :code)')
			->bind(':id', $id, ParameterType::INTEGER)
			->bind(':code', $code, ParameterType::STRING);

		$this->db->setQuery($query);

		return $this->db->loadObjectList() ?: [];
	}

	/**
	 * Delete programs, all or nothing: a single program that still has campaigns cancels the whole batch.
	 *
	 * @param   int[]  $ids
	 *
	 * @return int[] The ids actually deleted.
	 *
	 * @throws \InvalidArgumentException When no id is given, or one of them does not exist.
	 * @throws \RuntimeException         When at least one program still has campaigns.
	 */
	public function deleteBatch(array $ids): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

		if (empty($ids))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$programs = [];
		$blocking = [];

		foreach ($ids as $id)
		{
			$program = $this->getById($id);

			if (empty($program))
			{
				throw new \InvalidArgumentException(Text::sprintf('COM_EMUNDUS_PROGRAM_DELETE_NOT_FOUND', $id), EmundusResponse::HTTP_NOT_FOUND);
			}

			$campaigns = $this->getAssociatedCampaigns($id, $program->getCode());

			if (!empty($campaigns))
			{
				$campaignLabels = [];

				foreach ($campaigns as $campaign)
				{
					// Labels are user-entered and the error modal renders HTML: escape them.
					$campaignLabel = htmlspecialchars($campaign->label);

					if (!empty($campaign->year))
					{
						$campaignLabel .= ' (' . htmlspecialchars($campaign->year) . ')';
					}

					$campaignLabels[] = $campaignLabel;
				}

				$blocking[] = [
					'label'     => htmlspecialchars($program->getLabel()),
					'campaigns' => $campaignLabels,
				];
			}

			$programs[] = $program;
		}

		if (!empty($blocking))
		{
			throw new \RuntimeException($this->buildBlockingMessage($blocking, count($ids) === 1), EmundusResponse::HTTP_CONFLICT);
		}

		$this->dispatchJoomlaEvent('onBeforeProgramDelete', ['data' => $ids]);

		$this->db->transactionStart();

		try
		{
			foreach ($programs as $program)
			{
				$this->deleteProgramRow($program);
			}

			$this->db->transactionCommit();
		}
		catch (\Exception $e)
		{
			$this->db->transactionRollback();

			Log::add('Error on delete programs : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.program');

			throw new \RuntimeException(Text::_('COM_EMUNDUS_PROGRAM_DELETE_FAILED'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		$hCache = new \EmundusHelperCache();
		$hCache->clean(false);

		$this->dispatchJoomlaEvent('onAfterProgramDelete', ['id' => Factory::getApplication()->getIdentity()->id, 'data' => $ids]);

		return $ids;
	}

	/**
	 * Build the message shown when campaigns stand in the way of a deletion.
	 *
	 * A single selected program is the one the user just clicked, no need to name it. Beyond that,
	 * each blocking program is named or the campaign lists cannot be told apart.
	 *
	 * @param   array<array{label: string, campaigns: string[]}>  $blocking  Already HTML-escaped.
	 * @param   bool                                              $isSingle  Whether one single program was selected.
	 *
	 * @return string
	 */
	private function buildBlockingMessage(array $blocking, bool $isSingle): string
	{
		$details = '';

		// Inline styles rather than classes: the modal must read right without a CSS build.
		foreach ($blocking as $program)
		{
			if (!$isSingle)
			{
				$details .= '<p style="text-align: left; margin: 0.75em 0 0;"><strong>' . $program['label'] . '</strong></p>';
			}

			$details .= '<ul style="text-align: left; margin: 0.5em 0 0.5em 1.5em;">'
				. '<li>' . implode('</li><li>', $program['campaigns']) . '</li>'
				. '</ul>';
		}

		$key = $isSingle ? 'COM_EMUNDUS_PROGRAM_DELETE_HAS_CAMPAIGNS' : 'COM_EMUNDUS_PROGRAM_DELETE_HAS_CAMPAIGNS_MULTIPLE';

		return Text::sprintf($key, $details);
	}

	/**
	 * Delete one program row and everything the database will not clean up on its own.
	 *
	 * Left to the foreign keys (ON DELETE CASCADE): setup_groups_repeat_course, setup_programs_languages,
	 * setup_workflows_programs, setup_polls_programs, setup_emails_trigger(_repeat_programme_id),
	 * setup_teaching_unity.
	 *
	 * @param   ProgramEntity  $program
	 *
	 * @return void
	 */
	private function deleteProgramRow(ProgramEntity $program): void
	{
		$id = $program->getId();

		// Done while the row is still there: deleteLogo() reads the path from the database.
		$this->deleteLogo($id);

		$query = $this->db->getQuery(true);

		// Events hold a NO ACTION foreign key on the program: their rows must go first or the delete below fails.
		$query->delete($this->db->quoteName('#__emundus_setup_events_repeat_program'))
			->where($this->db->quoteName('programme') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$this->db->execute();

		// Favorites carry no foreign key at all, they would survive as orphans.
		$query->clear()
			->delete($this->db->quoteName('#__emundus_favorite_programmes'))
			->where($this->db->quoteName('programme_id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$this->db->execute();

		// Falang can translate any field, so the whole reference goes, not just the label.
		$query->clear()
			->delete($this->db->quoteName('#__falang_content'))
			->where($this->db->quoteName('reference_id') . ' = :id')
			->where($this->db->quoteName('reference_table') . ' = ' . $this->db->quote('emundus_setup_programmes'))
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$this->db->execute();

		$query->clear()
			->delete($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$this->db->execute();
	}

	public function deleteLogo(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			$query = $this->db->createQuery();

			$query->select('logo')
				->from($this->tableName)
				->where('id = ' . $id);

			try
			{
				$this->db->setQuery($query);
				$logoPath = $this->db->loadResult();

				if (!empty($logoPath))
				{
					$uploader = new UploadService('images/emundus/programs/');
					$deleted  = $uploader->deleteFile($logoPath);
				}
				else
				{
					$deleted = true;
				}

				if ($deleted)
				{
					$update = $this->db->createQuery();
					$update->update($this->tableName)
						->set('logo = NULL')
						->where('id = ' . $id);

					$this->db->setQuery($update)->execute();
				}
			}
			catch (\Exception $e)
			{
				Log::add('Error on delete program logo : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.program');
			}
		}

		return $deleted;
	}

	public function getFactory(): ProgramFactory
	{
		return $this->factory;
	}
}