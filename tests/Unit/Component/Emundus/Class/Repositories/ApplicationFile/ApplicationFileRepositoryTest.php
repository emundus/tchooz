<?php

namespace Unit\Component\Emundus\Class\Repositories\ApplicationFile;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\ApplicationFile
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository
 */
class ApplicationFileRepositoryTest extends UnitTestCase
{
	private ApplicationFileRepository $repository;

	public function setUp(): void
	{
		parent::setUp();
		$this->repository = new ApplicationFileRepository();
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getByFnum
	 * @return void
	 */
	public function testGetByFnum(): void
	{
		$applicationFile = $this->repository->getByFnum($this->dataset['fnum']);
		$this->assertNotNull($applicationFile, 'Le dossier doit exister');
		$this->assertInstanceOf(ApplicationFileEntity::class, $applicationFile, 'Le résultat doit être une instance de ApplicationFileEntity');
		$this->assertEquals($this->dataset['fnum'], $applicationFile->getFnum(), 'Le fnum doit correspondre');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getByFnum
	 * @return void
	 */
	public function testGetByFnumReturnsNullForInvalidFnum(): void
	{
		$applicationFile = $this->repository->getByFnum('invalid_fnum_000000000000000');
		$this->assertNull($applicationFile, 'Un fnum invalide doit retourner null');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getByFnum
	 * @return void
	 */
	public function testGetByFnumReturnsNullForEmptyFnum(): void
	{
		$applicationFile = $this->repository->getByFnum('');
		$this->assertNull($applicationFile, 'Un fnum vide doit retourner null');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getById
	 * @return void
	 */
	public function testGetById(): void
	{
		$applicationFile = $this->repository->getById($this->dataset['ccid']);
		$this->assertNotNull($applicationFile, 'Le dossier doit exister');
		$this->assertInstanceOf(ApplicationFileEntity::class, $applicationFile, 'Le résultat doit être une instance de ApplicationFileEntity');
		$this->assertEquals($this->dataset['fnum'], $applicationFile->getFnum(), 'Le fnum doit correspondre');
		$this->assertEquals($this->dataset['campaign'], $applicationFile->getCampaignId(), 'Le campaign_id doit correspondre');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getById
	 * @return void
	 */
	public function testGetByIdReturnsNullForInvalidId(): void
	{
		$applicationFile = $this->repository->getById(0);
		$this->assertNull($applicationFile, 'Un id invalide doit retourner null');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getAll
	 * @return void
	 */
	public function testGetAll(): void
	{
		$applicationFiles = $this->repository->getAll();
		$this->assertIsArray($applicationFiles, 'Le résultat doit être un tableau');
		$this->assertNotEmpty($applicationFiles, 'Le tableau ne doit pas être vide');

		$fnums = array_map(fn(ApplicationFileEntity $af) => $af->getFnum(), $applicationFiles);
		$this->assertContains($this->dataset['fnum'], $fnums, 'Le dossier créé doit être dans les résultats');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getAll
	 * @return void
	 */
	public function testGetAllWithStatusFilter(): void
	{
		$applicationFile = $this->repository->getByFnum($this->dataset['fnum']);
		$currentStatus = is_object($applicationFile->getStatus()) ? $applicationFile->getStatus()->getStep() : (int) $applicationFile->getStatus();

		$applicationFiles = $this->repository->getAll(['status' => $currentStatus]);
		$this->assertIsArray($applicationFiles, 'Le résultat doit être un tableau');

		foreach ($applicationFiles as $af)
		{
			$status = is_object($af->getStatus()) ? $af->getStatus()->getStep() : (int) $af->getStatus();
			$this->assertEquals($currentStatus, $status, 'Tous les dossiers doivent avoir le statut filtré');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getAll
	 * @return void
	 */
	public function testGetAllWithCampaignFilter(): void
	{
		$applicationFiles = $this->repository->getAll(['campaign_id' => $this->dataset['campaign']]);
		$this->assertIsArray($applicationFiles, 'Le résultat doit être un tableau');
		$this->assertNotEmpty($applicationFiles, 'Le tableau ne doit pas être vide avec le filtre campaign_id');

		foreach ($applicationFiles as $af)
		{
			$this->assertEquals($this->dataset['campaign'], $af->getCampaignId(), 'Tous les dossiers doivent appartenir à la campagne filtrée');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getAll
	 * @return void
	 */
	public function testGetAllWithApplicantFilter(): void
	{
		$applicationFiles = $this->repository->getAll(['applicant_id' => $this->dataset['applicant']]);
		$this->assertIsArray($applicationFiles, 'Le résultat doit être un tableau');
		$this->assertNotEmpty($applicationFiles, 'Le tableau ne doit pas être vide avec le filtre applicant_id');

		foreach ($applicationFiles as $af)
		{
			$this->assertEquals($this->dataset['applicant'], $af->getUser()->id, 'Tous les dossiers doivent appartenir au candidat filtré');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getAll
	 * @return void
	 */
	public function testGetAllWithFnumFilter(): void
	{
		$applicationFiles = $this->repository->getAll(['fnum' => $this->dataset['fnum']]);
		$this->assertIsArray($applicationFiles, 'Le résultat doit être un tableau');
		$this->assertCount(1, $applicationFiles, 'Le filtre par fnum doit retourner exactement un résultat');
		$this->assertEquals($this->dataset['fnum'], $applicationFiles[0]->getFnum(), 'Le fnum doit correspondre');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getAll
	 * @return void
	 */
	public function testGetAllWithFnumArrayFilter(): void
	{
		$fnum2 = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$this->assertNotEmpty($fnum2, 'Le deuxième dossier doit être créé');

		$applicationFiles = $this->repository->getAll(['fnum' => [$this->dataset['fnum'], $fnum2]]);
		$this->assertIsArray($applicationFiles, 'Le résultat doit être un tableau');
		$this->assertCount(2, $applicationFiles, 'Le filtre par tableau de fnums doit retourner 2 résultats');

		$fnums = array_map(fn(ApplicationFileEntity $af) => $af->getFnum(), $applicationFiles);
		$this->assertContains($this->dataset['fnum'], $fnums, 'Le premier fnum doit être dans les résultats');
		$this->assertContains($fnum2, $fnums, 'Le deuxième fnum doit être dans les résultats');

		$this->h_dataset->deleteSampleFile($fnum2);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getAll
	 * @return void
	 */
	public function testGetAllWithPublishedFilter(): void
	{
		$applicationFiles = $this->repository->getAll(['published' => 1]);
		$this->assertIsArray($applicationFiles, 'Le résultat doit être un tableau');

		foreach ($applicationFiles as $af)
		{
			$this->assertEquals(1, $af->getPublished(), 'Tous les dossiers doivent être publiés');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getAll
	 * @return void
	 */
	public function testGetAllWithMultipleFilters(): void
	{
		$applicationFiles = $this->repository->getAll([
			'campaign_id'  => $this->dataset['campaign'],
			'applicant_id' => $this->dataset['applicant'],
			'published'    => 1,
		]);
		$this->assertIsArray($applicationFiles, 'Le résultat doit être un tableau');
		$this->assertNotEmpty($applicationFiles, 'Le tableau ne doit pas être vide avec des filtres combinés');

		$fnums = array_map(fn(ApplicationFileEntity $af) => $af->getFnum(), $applicationFiles);
		$this->assertContains($this->dataset['fnum'], $fnums, 'Le dossier créé doit être dans les résultats');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getCampaignIds
	 * @return void
	 */
	public function testGetCampaignIds(): void
	{
		$applicationFile = $this->repository->getById($this->dataset['ccid']);
		$campaignIds = $this->repository->getCampaignIds([$this->dataset['fnum']]);
		$this->assertIsArray($campaignIds, 'Le résultat doit être un tableau');
		$this->assertNotEmpty($campaignIds, 'Le tableau ne doit pas être vide');
		$this->assertContains($applicationFile->getCampaignId(), $campaignIds, 'Le campaign_id doit être dans les résultats');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getCampaignIds
	 * @return void
	 */
	public function testGetCampaignIdsWithMultipleFnums(): void
	{
		$fnum2 = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$this->assertNotEmpty($fnum2, 'Le deuxième dossier doit être créé');

		$campaignIds = $this->repository->getCampaignIds([$this->dataset['fnum'], $fnum2]);
		$this->assertIsArray($campaignIds, 'Le résultat doit être un tableau');
		$this->assertNotEmpty($campaignIds, 'Le tableau ne doit pas être vide');

		$this->h_dataset->deleteSampleFile($fnum2);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::flush
	 * @return void
	 */
	public function testFlush(): void
	{
		$applicationFile = $this->repository->getByFnum($this->dataset['fnum']);
		$applicationFile->setStatus(1);
		$applicationFile->setDateSubmitted(new \Datetime());

		$flushed = $this->repository->flush($applicationFile, $this->dataset['coordinator']);
		$this->assertTrue($flushed, 'Le flush doit réussir');

		$updatedApplicationFile = $this->repository->getByFnum($this->dataset['fnum']);
		$this->assertEquals(1, $updatedApplicationFile->getStatus()->getStep(), 'Le statut doit être mis à jour');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::flush
	 * @return void
	 */
	public function testFlushWithInvalidUserReturnsFalse(): void
	{
		$invalidUser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById(0);
		$applicationFile = new ApplicationFileEntity($invalidUser);
		$applicationFile->setFnum($this->dataset['fnum']);
		$applicationFile->setCampaignId($this->dataset['campaign']);
		$applicationFile->setStatus(0);
		$applicationFile->setPublished(1);

		$flushed = $this->repository->flush($applicationFile, $this->dataset['coordinator']);
		$this->assertFalse($flushed, 'Le flush doit échouer avec un utilisateur invalide');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::flush
	 * @return void
	 */
	public function testFlushWithEmptyFnumReturnsFalse(): void
	{
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);
		$applicationFile = new ApplicationFileEntity($user);
		$applicationFile->setFnum('');
		$applicationFile->setCampaignId($this->dataset['campaign']);
		$applicationFile->setStatus(0);
		$applicationFile->setPublished(1);

		$flushed = $this->repository->flush($applicationFile, $this->dataset['coordinator']);
		$this->assertFalse($flushed, 'Le flush doit échouer avec un fnum vide');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::flush
	 * @return void
	 */
	public function testFlushCreatesNewCampaignCandidature(): void
	{
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);
		$applicationFile = new ApplicationFileEntity($user);
		$applicationFile->generateFnum($this->dataset['campaign'], $this->dataset['applicant']);
		$applicationFile->setCampaignId($this->dataset['campaign']);
		$applicationFile->setStatus(0);
		$applicationFile->setPublished(1);

		$flushed = $this->repository->flush($applicationFile, $this->dataset['coordinator']);
		$this->assertTrue($flushed, 'Le flush doit réussir pour un nouveau dossier');
		$this->assertNotEmpty($applicationFile->getId(), 'L\'id doit être défini après le flush');

		$retrieved = $this->repository->getByFnum($applicationFile->getFnum());
		$this->assertNotNull($retrieved, 'Le nouveau dossier doit être récupérable');
		$this->assertEquals($applicationFile->getFnum(), $retrieved->getFnum(), 'Le fnum doit correspondre');

		$this->h_dataset->deleteSampleFile($applicationFile->getFnum());
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getApplicationFilesByApplicantId
	 * @return void
	 */
	public function testGetApplicationFilesByApplicantId(): void
	{
		$applicationFiles = $this->repository->getApplicationFilesByApplicantId($this->dataset['applicant']);
		$this->assertIsArray($applicationFiles, 'Le résultat doit être un tableau');
		$this->assertNotEmpty($applicationFiles, 'Le tableau ne doit pas être vide');

		$fnums = array_map(fn(ApplicationFileEntity $af) => $af->getFnum(), $applicationFiles);
		$this->assertContains($this->dataset['fnum'], $fnums, 'Le dossier créé doit être dans les résultats');

		foreach ($applicationFiles as $af)
		{
			$this->assertInstanceOf(ApplicationFileEntity::class, $af, 'Chaque élément doit être une instance de ApplicationFileEntity');
			$this->assertEquals($this->dataset['applicant'], $af->getUser()->id, 'Tous les dossiers doivent appartenir au candidat');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getApplicationFilesByApplicantId
	 * @return void
	 */
	public function testGetApplicationFilesByApplicantIdReturnsEmptyForUnknownUser(): void
	{
		$applicationFiles = $this->repository->getApplicationFilesByApplicantId(999999999);
		$this->assertIsArray($applicationFiles, 'Le résultat doit être un tableau');
		$this->assertEmpty($applicationFiles, 'Le tableau doit être vide pour un utilisateur inconnu');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getApplicationFilesByApplicantId
	 * @return void
	 */
	public function testGetApplicationFilesByApplicantIdWithMultipleFiles(): void
	{
		$fnum2 = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$this->assertNotEmpty($fnum2, 'Le deuxième dossier doit être créé');

		$applicationFiles = $this->repository->getApplicationFilesByApplicantId($this->dataset['applicant']);
		$this->assertGreaterThanOrEqual(2, count($applicationFiles), 'Au moins 2 dossiers doivent être retournés');

		$fnums = array_map(fn(ApplicationFileEntity $af) => $af->getFnum(), $applicationFiles);
		$this->assertContains($this->dataset['fnum'], $fnums, 'Le premier dossier doit être dans les résultats');
		$this->assertContains($fnum2, $fnums, 'Le deuxième dossier doit être dans les résultats');

		$this->h_dataset->deleteSampleFile($fnum2);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getByFnum
	 * @return void
	 */
	public function testGetByFnumEntityProperties(): void
	{
		$applicationFile = $this->repository->getByFnum($this->dataset['fnum']);
		$this->assertNotNull($applicationFile, 'Le dossier doit exister');

		$this->assertNotEmpty($applicationFile->getId(), 'L\'id ne doit pas être vide');
		$this->assertEquals($this->dataset['applicant'], $applicationFile->getUser()->id, 'L\'applicant doit correspondre');
		$this->assertEquals($this->dataset['campaign'], $applicationFile->getCampaignId(), 'Le campaign_id doit correspondre');
		$this->assertIsInt($applicationFile->getPublished(), 'Published doit être un entier');
		$this->assertIsInt($applicationFile->getFormProgress(), 'FormProgress doit être un entier');
		$this->assertIsInt($applicationFile->getAttachmentProgress(), 'AttachmentProgress doit être un entier');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::getByFnum
	 * @return void
	 */
	public function testGetByFnumWithoutRelations(): void
	{
		$repositoryNoRelations = new ApplicationFileRepository(false);
		$applicationFile = $repositoryNoRelations->getByFnum($this->dataset['fnum']);
		$this->assertNotNull($applicationFile, 'Le dossier doit exister même sans relations');
		$this->assertEquals($this->dataset['fnum'], $applicationFile->getFnum(), 'Le fnum doit correspondre');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::buildQuery
	 * @return void
	 */
	public function testBuildQueryReturnsQueryInterface(): void
	{
		$query = $this->repository->buildQuery();
		$this->assertInstanceOf(\Joomla\Database\QueryInterface::class, $query, 'buildQuery doit retourner une instance de QueryInterface');
	}
}