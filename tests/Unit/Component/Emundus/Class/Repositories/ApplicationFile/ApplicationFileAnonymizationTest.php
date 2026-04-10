<?php

namespace Unit\Component\Emundus\Class\Repositories\ApplicationFile;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\Campaigns\AnonymizationPolicyEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\ApplicationFile
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::flush
 *
 * Tests unitaires pour vérifier que la politique d'anonymisation de la campagne
 * est correctement appliquée lors de la création d'un dossier (flush).
 */
class ApplicationFileAnonymizationTest extends UnitTestCase
{
	private ApplicationFileRepository $repository;

	/**
	 * @var array Fnums créés pendant les tests, à nettoyer dans tearDown
	 */
	private array $createdFnums = [];

	public function setUp(): void
	{
		parent::setUp();
		$this->repository = new ApplicationFileRepository();
	}

	protected function tearDown(): void
	{
		// Nettoyer les dossiers créés pendant les tests
		foreach ($this->createdFnums as $fnum)
		{
			$this->h_dataset->deleteSampleFile($fnum);
		}

		parent::tearDown();
	}

	/**
	 * Crée un ApplicationFileEntity prêt à être flush, avec un fnum unique.
	 */
	private function createNewApplicationFileEntity(bool $isAnonymous = false): ApplicationFileEntity
	{
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);

		$applicationFile = new ApplicationFileEntity($user);
		$applicationFile->generateFnum($this->dataset['campaign'], $this->dataset['applicant']);
		$applicationFile->setCampaignId($this->dataset['campaign']);
		$applicationFile->setStatus(0);
		$applicationFile->setPublished(1);
		$applicationFile->setIsAnonymous($isAnonymous);

		return $applicationFile;
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::flush
	 *
	 * Quand la politique d'anonymisation est FORCED, le dossier DOIT être anonyme,
	 * peu importe le choix du candidat.
	 */
	public function testFlushWithForcedAnonymizationPolicySetsAnonymousToTrue(): void
	{
		// Arrange : configurer la campagne avec la politique FORCED
		$this->h_dataset->updateCampaignAnonymizationPolicy(
			$this->dataset['campaign'],
			AnonymizationPolicyEnum::FORCED->value
		);

		// Le candidat ne demande PAS l'anonymat (isAnonymous = false)
		$applicationFile = $this->createNewApplicationFileEntity(false);

		// Act : créer le dossier
		$flushed = $this->repository->flush($applicationFile, $this->dataset['coordinator']);
		$this->createdFnums[] = $applicationFile->getFnum();

		// Assert
		$this->assertTrue($flushed, 'Le flush doit réussir');

		// Vérifier en base de données (la politique est appliquée par le subscriber onCreateNewFile)
		$retrieved = $this->repository->getByFnum($applicationFile->getFnum());
		$this->assertNotNull($retrieved, 'Le dossier doit exister en base');
		$this->assertTrue(
			$retrieved->isAnonymous(),
			'Le dossier récupéré en base doit être marqué comme anonyme'
		);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::flush
	 *
	 * Quand la politique d'anonymisation est FORCED, même si le candidat demande l'anonymat,
	 * le dossier doit être anonyme (cas nominal confirmé).
	 */
	public function testFlushWithForcedAnonymizationPolicyWhenCandidateAlsoRequestsAnonymous(): void
	{
		$this->h_dataset->updateCampaignAnonymizationPolicy(
			$this->dataset['campaign'],
			AnonymizationPolicyEnum::FORCED->value
		);

		// Le candidat demande aussi l'anonymat
		$applicationFile = $this->createNewApplicationFileEntity(true);

		$flushed = $this->repository->flush($applicationFile, $this->dataset['coordinator']);
		$this->createdFnums[] = $applicationFile->getFnum();

		$this->assertTrue($flushed, 'Le flush doit réussir');

		// Vérifier en base de données
		$retrieved = $this->repository->getByFnum($applicationFile->getFnum());
		$this->assertNotNull($retrieved, 'Le dossier doit exister en base');
		$this->assertTrue(
			$retrieved->isAnonymous(),
			'Avec la politique FORCED et le choix du candidat, le dossier doit être anonyme'
		);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::flush
	 *
	 * Quand la politique d'anonymisation est FORBIDDEN, le dossier ne doit PAS être anonyme,
	 * même si le candidat le demande.
	 */
	public function testFlushWithForbiddenAnonymizationPolicySetsAnonymousToFalse(): void
	{
		$this->h_dataset->updateCampaignAnonymizationPolicy(
			$this->dataset['campaign'],
			AnonymizationPolicyEnum::FORBIDDEN->value
		);

		// Le candidat demande l'anonymat (isAnonymous = true)
		$applicationFile = $this->createNewApplicationFileEntity(true);

		$flushed = $this->repository->flush($applicationFile, $this->dataset['coordinator']);
		$this->createdFnums[] = $applicationFile->getFnum();

		$this->assertTrue($flushed, 'Le flush doit réussir');

		// Vérifier en base de données (la politique est appliquée par le subscriber onCreateNewFile)
		$retrieved = $this->repository->getByFnum($applicationFile->getFnum());
		$this->assertNotNull($retrieved, 'Le dossier doit exister en base');
		$this->assertFalse(
			$retrieved->isAnonymous(),
			'Le dossier récupéré en base ne doit pas être marqué comme anonyme'
		);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::flush
	 *
	 * Quand la politique d'anonymisation est FORBIDDEN, sans demande d'anonymat,
	 * le dossier ne doit pas être anonyme.
	 */
	public function testFlushWithForbiddenAnonymizationPolicyWhenCandidateDoesNotRequestAnonymous(): void
	{
		$this->h_dataset->updateCampaignAnonymizationPolicy(
			$this->dataset['campaign'],
			AnonymizationPolicyEnum::FORBIDDEN->value
		);

		$applicationFile = $this->createNewApplicationFileEntity(false);

		$flushed = $this->repository->flush($applicationFile, $this->dataset['coordinator']);
		$this->createdFnums[] = $applicationFile->getFnum();

		$this->assertTrue($flushed, 'Le flush doit réussir');

		// Vérifier en base de données
		$retrieved = $this->repository->getByFnum($applicationFile->getFnum());
		$this->assertNotNull($retrieved, 'Le dossier doit exister en base');
		$this->assertFalse(
			$retrieved->isAnonymous(),
			'Avec la politique FORBIDDEN et sans demande d\'anonymat, le dossier ne doit pas être anonyme'
		);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::flush
	 *
	 * Quand la politique d'anonymisation est OPTIONAL et que le candidat demande l'anonymat,
	 * le dossier doit être anonyme.
	 */
	public function testFlushWithOptionalAnonymizationPolicyRespectsUserChoiceAnonymous(): void
	{
		$this->h_dataset->updateCampaignAnonymizationPolicy(
			$this->dataset['campaign'],
			AnonymizationPolicyEnum::OPTIONAL->value
		);

		// Le candidat demande l'anonymat
		$applicationFile = $this->createNewApplicationFileEntity(true);

		$flushed = $this->repository->flush($applicationFile, $this->dataset['coordinator']);
		$this->createdFnums[] = $applicationFile->getFnum();

		$this->assertTrue($flushed, 'Le flush doit réussir');
		$this->assertTrue(
			$applicationFile->isAnonymous(),
			'Avec la politique OPTIONAL et le choix du candidat, le dossier doit être anonyme'
		);

		// Vérifier en base de données
		$retrieved = $this->repository->getByFnum($applicationFile->getFnum());
		$this->assertNotNull($retrieved, 'Le dossier doit exister en base');
		$this->assertTrue(
			$retrieved->isAnonymous(),
			'Le dossier récupéré en base doit être marqué comme anonyme'
		);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::flush
	 *
	 * Quand la politique d'anonymisation est OPTIONAL et que le candidat ne demande PAS l'anonymat,
	 * le dossier ne doit pas être anonyme.
	 */
	public function testFlushWithOptionalAnonymizationPolicyRespectsUserChoiceNotAnonymous(): void
	{
		$this->h_dataset->updateCampaignAnonymizationPolicy(
			$this->dataset['campaign'],
			AnonymizationPolicyEnum::OPTIONAL->value
		);

		// Le candidat ne demande PAS l'anonymat
		$applicationFile = $this->createNewApplicationFileEntity(false);

		$flushed = $this->repository->flush($applicationFile, $this->dataset['coordinator']);
		$this->createdFnums[] = $applicationFile->getFnum();

		$this->assertTrue($flushed, 'Le flush doit réussir');
		$this->assertFalse(
			$applicationFile->isAnonymous(),
			'Avec la politique OPTIONAL et sans demande d\'anonymat, le dossier ne doit pas être anonyme'
		);

		// Vérifier en base de données
		$retrieved = $this->repository->getByFnum($applicationFile->getFnum());
		$this->assertNotNull($retrieved, 'Le dossier doit exister en base');
		$this->assertFalse(
			$retrieved->isAnonymous(),
			'Le dossier récupéré en base ne doit pas être marqué comme anonyme'
		);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileRepository::flush
	 *
	 * Par défaut (sans modifier la politique de la campagne), la politique est FORBIDDEN.
	 * Le dossier ne doit pas être anonyme.
	 */
	public function testFlushWithDefaultAnonymizationPolicyIsForbidden(): void
	{
		// Ne pas modifier la politique, la valeur par défaut doit être FORBIDDEN
		$applicationFile = $this->createNewApplicationFileEntity(true);

		$flushed = $this->repository->flush($applicationFile, $this->dataset['coordinator']);
		$this->createdFnums[] = $applicationFile->getFnum();

		$this->assertTrue($flushed, 'Le flush doit réussir');

		// Vérifier en base de données (la politique est appliquée par le subscriber onCreateNewFile)
		$retrieved = $this->repository->getByFnum($applicationFile->getFnum());
		$this->assertNotNull($retrieved, 'Le dossier doit exister en base');
		$this->assertFalse(
			$retrieved->isAnonymous(),
			'Avec la politique par défaut (FORBIDDEN), le dossier ne doit pas être anonyme même si le candidat le demande'
		);
	}
}

