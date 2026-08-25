<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\ApplicationFile;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Enums\ApplicationFile\ChoicesStateEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Programs\ProgramRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\ApplicationFile
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository
 */
class ApplicationChoicesRepositoryTest extends UnitTestCase
{
	private array $campaignsFixtures = [];

	public function setUp(): void
	{
		parent::setUp();

		$this->model = new ApplicationChoicesRepository();
	}

	private function loadFixtures(): void
	{
		$this->refreshDataset();

		$programRepository  = new ProgramRepository();
		$programEntity      = $programRepository->getById($this->dataset['program']['programme_id']);
		$campaignRepository = new CampaignRepository();
		$parentCampaign     = $campaignRepository->getById($this->dataset['campaign']);

		// TODO: Move to campaign repository when flush will be implemented
		$campaignEntity = new CampaignEntity('Voeu 1', (new \DateTime()), (new \DateTime())->add(new \DateInterval('P1M')), $programEntity, '2025-2026');
		$campaignEntity->setParent($parentCampaign);
		$insert = (object) [
			'date_time'  => new \DateTime(),
			'user'       => $this->dataset['coordinator'],
			'label'      => $campaignEntity->getLabel(),
			'start_date' => $campaignEntity->getStartDate()->format('Y-m-d H:i:s'),
			'end_date'   => $campaignEntity->getEndDate()->format('Y-m-d H:i:s'),
			'profile_id' => 1000,
			'training'   => $programEntity->getCode(),
			'year'       => $campaignEntity->getYear(),
			'published'  => 1,
			'parent_id'  => $parentCampaign->getId(),
		];
		$this->db->insertObject('#__emundus_setup_campaigns', $insert);
		$campaignEntity->setId($this->db->insertid());
		$this->campaignsFixtures[] = $campaignEntity;

		$campaignEntity = new CampaignEntity('Voeu 2', (new \DateTime()), (new \DateTime())->add(new \DateInterval('P1M')), $programEntity, '2025-2026');
		$campaignEntity->setParent($parentCampaign);
		$insert = (object) [
			'date_time'  => new \DateTime(),
			'user'       => $this->dataset['coordinator'],
			'label'      => $campaignEntity->getLabel(),
			'start_date' => $campaignEntity->getStartDate()->format('Y-m-d H:i:s'),
			'end_date'   => $campaignEntity->getEndDate()->format('Y-m-d H:i:s'),
			'profile_id' => 1000,
			'training'   => $programEntity->getCode(),
			'year'       => $campaignEntity->getYear(),
			'published'  => 1,
			'parent_id'  => $parentCampaign->getId(),
		];
		$this->db->insertObject('#__emundus_setup_campaigns', $insert);
		$campaignEntity->setId($this->db->insertid());
		$this->campaignsFixtures[] = $campaignEntity;

		$otherParentCampaignEntity = new CampaignEntity('Campagne parente 2', (new \DateTime()), (new \DateTime())->add(new \DateInterval('P1M')), $programEntity, '2025-2026');
		$insert                    = (object) [
			'date_time'  => new \DateTime(),
			'user'       => $this->dataset['coordinator'],
			'label'      => $otherParentCampaignEntity->getLabel(),
			'start_date' => $otherParentCampaignEntity->getStartDate()->format('Y-m-d H:i:s'),
			'end_date'   => $otherParentCampaignEntity->getEndDate()->format('Y-m-d H:i:s'),
			'profile_id' => 1000,
			'training'   => $programEntity->getCode(),
			'year'       => $otherParentCampaignEntity->getYear(),
			'published'  => 1,
			'parent_id'  => $otherParentCampaignEntity->getId(),
		];
		$this->db->insertObject('#__emundus_setup_campaigns', $insert);
		$otherParentCampaignEntity->setId($this->db->insertid());

		$campaignEntity = new CampaignEntity('Voeu 3', (new \DateTime()), (new \DateTime())->add(new \DateInterval('P1M')), $programEntity, '2025-2026');
		$campaignEntity->setParent($otherParentCampaignEntity);
		$insert = (object) [
			'date_time'  => new \DateTime(),
			'user'       => $this->dataset['coordinator'],
			'label'      => $campaignEntity->getLabel(),
			'start_date' => $campaignEntity->getStartDate()->format('Y-m-d H:i:s'),
			'end_date'   => $campaignEntity->getEndDate()->format('Y-m-d H:i:s'),
			'profile_id' => 1000,
			'training'   => $programEntity->getCode(),
			'year'       => $campaignEntity->getYear(),
			'published'  => 1,
			'parent_id'  => $parentCampaign->getId(),
		];
		$this->db->insertObject('#__emundus_setup_campaigns', $insert);
		$campaignEntity->setId($this->db->insertid());
		$this->campaignsFixtures[] = $campaignEntity;
	}

	public function clearFixtures(): void
	{
		if (!empty($this->campaignsFixtures)) {
			foreach ($this->campaignsFixtures as $campaignsFixture) {
				$query = $this->db->getQuery(true)
					->delete($this->db->quoteName('#__emundus_setup_campaigns'))
					->where($this->db->quoteName('id') . ' = ' . (int) $campaignsFixture->getId());
				$this->db->setQuery($query);
				$this->db->execute();
			}

			$this->campaignsFixtures = [];
		}
	}

	public function testFlush()
	{
		$this->loadFixtures();

		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);

		$applicationChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[0], $this->campaignsFixtures[0]->getId());

		// Create new choice
		$flushed = $this->model->flush($applicationChoice);
		$this->assertTrue($flushed);

		// Update status of choice
		$applicationChoice->setState(ChoicesStateEnum::WAITING);
		$flushed = $this->model->flush($applicationChoice);
		$this->assertTrue($flushed);

		$this->clearFixtures();
	}

	public function testFlushExceptionEmptyFnum()
	{
		$this->loadFixtures();

		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Campaign ID and Fnum are required to flush ApplicationChoicesEntity');
		$otherCampaignChoice = new ApplicationChoicesEntity('', $user, $this->campaignsFixtures[2], $this->campaignsFixtures[2]->getId());
		$this->model->flush($otherCampaignChoice);

		$this->clearFixtures();
	}

	public function testFlushExceptionInvalidParent()
	{
		$this->loadFixtures();

		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(Text::_('PLG_EMUNDUS_APPLICATION_CHOICES_INVALID_PARENT'));
		$otherCampaignChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[2], $this->campaignsFixtures[2]->getId());
		$this->model->flush($otherCampaignChoice);

		$this->clearFixtures();
	}

	public function testFlushExceptionAlreadyChoice()
	{
		$this->loadFixtures();

		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);

		$applicationChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[0], $this->campaignsFixtures[0]->getId());
		$this->model->flush($applicationChoice);

		// Try to create another choice for the same campaign
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(Text::_('PLG_EMUNDUS_APPLICATION_CHOICES_ALREADY_EXIST'));
		$duplicateChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[0], $this->campaignsFixtures[0]->getId());
		$this->model->flush($duplicateChoice);

		$this->clearFixtures();
	}

	public function testFlushExceptionInvalidCampaign()
	{
		$this->loadFixtures();

		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);

		$this->campaignsFixtures[0]->setPublished(false);
		// Try to create another choice for an unpublished campaign
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(Text::_('PLG_EMUNDUS_APPLICATION_CHOICES_INVALID'));
		$unpublishedCampaignChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[0], $this->campaignsFixtures[0]->getId());
		$this->model->flush($unpublishedCampaignChoice);

		$this->clearFixtures();
	}

	public function testDelete()
	{
		$this->loadFixtures();

		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);

		$applicationChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[0], $this->campaignsFixtures[0]->getId());
		$this->model->flush($applicationChoice);

		// Delete choice
		$deleted = $this->model->delete($applicationChoice->getId());
		$this->assertTrue($deleted);
		$this->assertNull($this->model->getById($applicationChoice->getId()));

		$this->clearFixtures();
	}

	public function testGetById()
	{
		$this->loadFixtures();

		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);

		$applicationChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[0], $this->campaignsFixtures[0]->getId());
		$this->model->flush($applicationChoice);

		// Retrieve choice by ID
		$retrievedChoice = $this->model->getById($applicationChoice->getId());
		$this->assertInstanceOf(ApplicationChoicesEntity::class, $retrievedChoice);
		$this->assertEquals($applicationChoice->getId(), $retrievedChoice->getId());

		$notExistingChoice = $this->model->getById(999999);
		$this->assertNull($notExistingChoice);

		$this->clearFixtures();
	}

	public function testGetChoicesByFnum()
	{
		$this->loadFixtures();

		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);

		$choices = $this->model->getChoicesByFnum($this->dataset['fnum']);
		$this->assertIsArray($choices);
		$this->assertCount(0, $choices);

		$applicationChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[0], $this->campaignsFixtures[0]->getId());
		$this->model->flush($applicationChoice);

		// Retrieve choices by Fnum
		$choices = $this->model->getChoicesByFnum($this->dataset['fnum']);
		$this->assertIsArray($choices);
		$this->assertCount(1, $choices);
		$this->assertEquals($applicationChoice->getId(), $choices[0]->getId());

		// Add another choice. The fixture has no choices step, so the configuration falls back to a maximum
		// of one: this test covers retrieval, not that rule, hence the rules are skipped.
		$applicationChoice2 = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[1], $this->campaignsFixtures[1]->getId(), 0, ChoicesStateEnum::ACCEPTED);
		$this->model->flush($applicationChoice2, false);
		$choices = $this->model->getChoicesByFnum($this->dataset['fnum']);
		$this->assertIsArray($choices);
		$this->assertCount(2, $choices);

		// Filter on accepted state
		$acceptedChoices = $this->model->getChoicesByFnum($this->dataset['fnum'], [], ChoicesStateEnum::ACCEPTED);
		$this->assertIsArray($acceptedChoices);
		$this->assertCount(1, $acceptedChoices);
		$this->assertEquals(ChoicesStateEnum::ACCEPTED, $acceptedChoices[0]->getState());

		$this->clearFixtures();
	}

	// =====================
	// assertApplicantCanUpdate — la règle affichée par le front, imposée côté serveur
	// =====================

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository::assertApplicantCanUpdate
	 */
	public function testAssertApplicantCanUpdateWhenChoicesAreNotEditableThrows()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(Text::_('PLG_EMUNDUS_APPLICATION_CHOICES_NOT_EDITABLE'));

		$this->model->assertApplicantCanUpdate($this->dataset['fnum'], false, ['can_be_updated' => 0, 'can_be_ordering' => 1]);
	}

	/**
	 * Un appel API direct ne doit pas contourner la règle : le refus porte le code 403.
	 *
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository::assertApplicantCanUpdate
	 */
	public function testAssertApplicantCanUpdateRefusalCarriesForbiddenCode()
	{
		try
		{
			$this->model->assertApplicantCanUpdate($this->dataset['fnum'], false, ['can_be_updated' => 0]);
			$this->fail('Le garde doit refuser une édition hors phase');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertEquals(403, $e->getCode(), 'Le refus doit se traduire par un 403, pas par une erreur serveur');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository::assertApplicantCanUpdate
	 */
	public function testAssertApplicantCanUpdateWhenChoicesAreEditablePasses()
	{
		$this->model->assertApplicantCanUpdate($this->dataset['fnum'], false, ['can_be_updated' => 1, 'can_be_ordering' => 0]);

		$this->assertTrue(true, 'Une configuration éditable ne doit pas lever');
	}

	/**
	 * Le classement a sa propre règle : éditable ne veut pas dire classable.
	 *
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository::assertApplicantCanUpdate
	 */
	public function testAssertApplicantCanUpdateWhenOrderingIsRequiredButDisabledThrows()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(Text::_('PLG_EMUNDUS_APPLICATION_CHOICES_ORDERING_DISABLED'));

		$this->model->assertApplicantCanUpdate($this->dataset['fnum'], true, ['can_be_updated' => 1, 'can_be_ordering' => 0]);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository::assertApplicantCanUpdate
	 */
	public function testAssertApplicantCanUpdateWhenOrderingIsRequiredAndAllowedPasses()
	{
		$this->model->assertApplicantCanUpdate($this->dataset['fnum'], true, ['can_be_updated' => 1, 'can_be_ordering' => 1]);

		$this->assertTrue(true, 'Une configuration éditable et classable ne doit pas lever');
	}

	/**
	 * Sans configuration fournie, le garde va la lire lui-même : la règle ne dépend pas de l'appelant.
	 *
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository::assertApplicantCanUpdate
	 */
	public function testAssertApplicantCanUpdateReadsTheConfigurationWhenNotGiven()
	{
		$this->loadFixtures();

		if (!class_exists('EmundusModelWorkflow'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/workflow.php';
		}
		$config = (new \EmundusModelWorkflow())->getChoicesConfigurationFromFnum($this->dataset['fnum']);

		$thrown = false;
		try
		{
			$this->model->assertApplicantCanUpdate($this->dataset['fnum']);
		}
		catch (\InvalidArgumentException $e)
		{
			$thrown = true;
		}

		$this->assertSame(empty($config['can_be_updated']), $thrown, 'Le garde doit suivre la configuration du dossier');

		$this->clearFixtures();
	}
}