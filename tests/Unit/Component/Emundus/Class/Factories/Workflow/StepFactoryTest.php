<?php

namespace Unit\Component\Emundus\Class\Factories\Workflow;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Factories\Workflow\StepFactory;

class StepFactoryTest extends UnitTestCase
{

	/**
	 * @covers \Tchooz\Factories\Workflow\StepFactory::fromV1Step
	 * @return void
	 */
	public function testFromV1Step()
	{
		try {
			$this->assertEmpty(StepFactory::fromV1Step([]), 'Expected null when input is empty array');

			$oldStep = [
				'id' => 1,
				'profile' => 1000,
				'entry_status' => [1],
				'start_date' => '2024-01-01 00:00:00',
				'end_date' => '2024-12-31 23:59:59',
				'campaign_ids' => [$this->dataset['campaign']],
				'program_ids' => [$this->dataset['program']['programme_id']],
			];

			$newStep = StepFactory::fromV1Step($oldStep);
			$this->assertNotNull($newStep, 'Expected StepEntity object when input is valid old step array');
			$this->assertNotEmpty($newStep->getLabel(), 'Expected StepEntity object when input is valid old step array');
			$this->assertEquals($oldStep['profile'], $newStep->getProfileId(), 'Profile ID should match');
			$this->assertEquals($oldStep['entry_status'], $newStep->getEntryStatus(), 'Entry status should match');
			$this->assertNotEmpty($newStep->campaignsDates, 'Expected campaign dates to be set');
			$this->assertCount(1, $newStep->campaignsDates, 'Expected one campaign date to be created');
			$this->assertEquals($oldStep['start_date'], $newStep->campaignsDates[0]->getStartDate()->format('Y-m-d H:i:s'), 'Start date should match');
			$this->assertEquals($oldStep['end_date'], $newStep->campaignsDates[0]->getEndDate()->format('Y-m-d H:i:s'), 'End date should match');
			$this->assertEquals($oldStep['campaign_ids'][0], $newStep->campaignsDates[0]->getCampaignId(), 'Campaign ID should match');
		} catch (\Exception $e) {
			$this->fail('Failed to test fromV1Step: ' . $e->getMessage());
		}
	}
}