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
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\NumericSign\Request
 */
class ApplicationChoicesRepositoryTest extends UnitTestCase
{
	private array $campaignsFixtures = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();

		$this->model = new ApplicationChoicesRepository();
	}

	private function loadFixtures(): void
	{
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

		$applicationChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[0]);

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
		$otherCampaignChoice = new ApplicationChoicesEntity('', $user, $this->campaignsFixtures[2]);
		$this->model->flush($otherCampaignChoice);

		$this->clearFixtures();
	}

	public function testFlushExceptionInvalidParent()
	{
		$this->loadFixtures();

		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(Text::_('PLG_EMUNDUS_APPLICATION_CHOICES_INVALID_PARENT'));
		$otherCampaignChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[2]);
		$this->model->flush($otherCampaignChoice);

		$this->clearFixtures();
	}

	public function testFlushExceptionAlreadyChoice()
	{
		$this->loadFixtures();

		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);

		$applicationChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[0]);
		$this->model->flush($applicationChoice);

		// Try to create another choice for the same campaign
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Ce voeu a déjà été sélectionné.');
		$duplicateChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[0]);
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
		$unpublishedCampaignChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[0]);
		$this->model->flush($unpublishedCampaignChoice);

		$this->clearFixtures();
	}

	public function testDelete()
	{
		$this->loadFixtures();

		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);

		$applicationChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[0]);
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

		$applicationChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[0]);
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

		$applicationChoice = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[0]);
		$this->model->flush($applicationChoice);

		// Retrieve choices by Fnum
		$choices = $this->model->getChoicesByFnum($this->dataset['fnum']);
		$this->assertIsArray($choices);
		$this->assertCount(1, $choices);
		$this->assertEquals($applicationChoice->getId(), $choices[0]->getId());

		// Add another choice
		$applicationChoice2 = new ApplicationChoicesEntity($this->dataset['fnum'], $user, $this->campaignsFixtures[1], 0, ChoicesStateEnum::ACCEPTED);
		$this->model->flush($applicationChoice2);
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
}