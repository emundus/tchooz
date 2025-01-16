<?php

/**
 * @package         Joomla.UnitTest
 * @subpackage      Extension
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Unit\Component\Emundus\Model;

use EmundusModelEvents;
use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;
use stdClass;

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelEvents
 */
class EventsModelTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('events', $data, $dataName, 'EmundusModelEvents');
	}

	/**
	 * @group   application
	 * @covers EmundusModelEvents::getEvents
	 *
	 * @since version 2.0.0
	 */
	public function testGetEvents()
	{
		$events = $this->model->getEvents();
		$this->assertIsArray($events, 'The method getEvents should return an array');
	}

	public function testGetAllLocations()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);

		$locations = $this->model->getAllLocations();
		$this->assertIsArray($locations, 'The method getAllLocations should return an array');
		$this->assertNotEmpty($locations['datas'], 'The method getAllLocations should return a non empty array');
		$this->assertObjectHasProperty('label',$locations['datas'][0], 'The location object should have a label property');
		$this->assertObjectHasProperty('id',$locations['datas'][0], 'The location object should have a id property');
		$this->assertObjectHasProperty('address',$locations['datas'][0], 'The location object should have a address property');
		$this->assertObjectHasProperty('nb_events',$locations['datas'][0], 'The location object should have a number of events property');

		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
	}

	public function testGetLocations()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);

		$locations = $this->model->getLocations();
		$this->assertIsArray($locations, 'The method getLocations should return an array');
		$this->assertNotEmpty($locations, 'The method getLocations should return a non empty array');
		$this->assertObjectHasProperty('label',$locations[0], 'The location object should have a label property');
		$this->assertObjectHasProperty('value',$locations[0], 'The location object should have a value property');

		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
	}

	public function testGetLocation()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$location = $this->model->getLocation($location_id);
		$this->assertIsObject($location, 'The method getLocation should return an object');
		$this->assertObjectHasProperty('name',$location, 'The location object should have a name property');
		$this->assertObjectHasProperty('address',$location, 'The location object should have an address property');
		$this->assertObjectHasProperty('rooms',$location, 'The location object should have a rooms property');
		$this->assertSame('Lieu de test', $location->name, 'The location name should be "Lieu de test"');

		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
	}

	public function testGetRooms()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);

		$rooms = $this->model->getRooms($location_id);
		$this->assertIsArray($rooms, 'The method getRooms should return an array');
		$this->assertEmpty($rooms, 'The method getRooms should return an empty array');

		$room = new stdClass();
		$room->name = 'Salle de test';
		$room->specifications = [];
		$specifications = $this->model->getSpecifications();
		$room->specifications[] = $specifications[0];
		$this->model->saveLocation('Nouveau nom', 'Adresse de test', [$room], $user_id_coordinator,$location_id);

		$rooms = $this->model->getRooms($location_id);
		$this->assertIsArray($rooms, 'The method getRooms should return an array');
		$this->assertNotEmpty($rooms, 'The method getRooms should return a non empty array');
		$this->assertObjectHasProperty('label',$rooms[0], 'The room object should have a label property');
		$this->assertObjectHasProperty('value',$rooms[0], 'The room object should have a value property');

		$rooms = $this->model->getRooms($location_id, true);
		$this->assertIsArray($rooms, 'The method getRooms should return an array');
		$this->assertNotEmpty($rooms, 'The method getRooms should return a non empty array');
		$this->assertObjectHasProperty('specifications',$rooms[0], 'The room object should have a specifications property');

		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
	}

	public function testGetSpecifications()
	{
		$specifications = $this->model->getSpecifications();
		$this->assertIsArray($specifications, 'The method getSpecifications should return an array');
		$this->assertNotEmpty($specifications, 'The method getSpecifications should return a non empty array');
		$this->assertObjectHasProperty('label',$specifications[0], 'The specification object should have a label property');
		$this->assertObjectHasProperty('value',$specifications[0], 'The specification object should have a value property');
	}

	public function testGetEvent()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$event = $this->h_dataset->createEvent($location_id,$user_id_coordinator,null,null);
		$event_id = $event['event_id'];

		$event = $this->model->getEvent($event_id);
		$this->assertIsObject($event, 'The method getEvent should return an object');
		$this->assertObjectHasProperty('name',$event, 'The event object should have a name property');
		$this->assertObjectHasProperty('color',$event, 'The event object should have a color property');
		$this->assertObjectHasProperty('location',$event, 'The event object should have a location property');
		$this->assertObjectHasProperty('slots',$event, 'The event object should have a slots property');
		$this->assertObjectHasProperty('notifications',$event, 'The event object should have a slots property');

		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
	}

	public function testGetEventsSlots()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$event = $this->h_dataset->createEvent($location_id,$user_id_coordinator);
		$event_id = $event['event_id'];
		$event_slots = $event['event_slots'];

		$eventSlots = $this->model->getEventsSlots('','',$event_id);
		$this->assertIsArray($eventSlots, 'The method getEventsSlots should return an array');

		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleEventSlots($event_slots);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
	}

	public function testSaveLocation()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$this->assertIsInt($location_id, 'The method saveLocation should return an integer');
		$this->assertNotEmpty($location_id, 'The method saveLocation should return a non empty value');

		$location = $this->model->getLocation($location_id);
		$this->assertIsObject($location, 'The method getLocation should return an object');
		$this->assertSame('Lieu de test', $location->name, 'The location name should be "Lieu de test"');
		$this->assertSame('Adresse de test', $location->address, 'The location address should be "Adresse de test"');
		$this->assertEmpty($location->rooms, 'The location should have no rooms');

		$this->model->saveLocation('Nouveau nom', 'Adresse de test', [], $user_id_coordinator,$location_id);
		$location = $this->model->getLocation($location_id);
		$this->assertSame('Nouveau nom', $location->name, 'The location name should be "Nouveau nom"');

		$room = new stdClass();
		$room->name = 'Salle de test';
		$room->specifications = [];
		$specifications = $this->model->getSpecifications();
		$room->specifications[] = $specifications[0];
		$this->model->saveLocation('Nouveau nom', 'Adresse de test', [$room], $user_id_coordinator,$location_id);
		$location = $this->model->getLocation($location_id);
		$this->assertSame('Salle de test', $location->rooms[0]->label, 'The room name should be "Salle de test"');

		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
	}

	public function testDeleteLocation()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$this->model->deleteLocation($location_id);
		$location = $this->model->getLocation($location_id);
		$this->assertEmpty($location, 'The location should be deleted');

		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleLocation($location_id);
	}

	public function testCreateEvent()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);

		$event = [
			'name' => 'Event test',
			'color' => '#000000',
			'location' => $location_id,
			'is_conference_link' => 0,
			'conference_engine' => '',
			'link' => '',
			'generate_link_by' => 0,
			'manager' => null,
			'available_for' => 1,
			'campaigns' => [],
			'programs' => [],
			'user_id' => $user_id_coordinator,
		];
		$event_id = $this->model->createEvent($event['name'], $event['color'], $event['location'], $event['is_conference_link'], $event['conference_engine'], $event['link'], $event['generate_link_by'], $event['manager'], $event['available_for'], $event['campaigns'], $event['programs'], $event['user_id']);
		$this->assertIsInt($event_id, 'The method createEvent should return an integer');
		$this->assertNotEmpty($event_id, 'The method createEvent should return a non empty value');

		$program = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$event['campaigns'][] = $campaign_id;

		$event_id_2 = $this->model->createEvent($event['name'], $event['color'], $event['location'], $event['is_conference_link'], $event['conference_engine'], $event['link'], $event['generate_link_by'], $event['manager'], $event['available_for'], $event['campaigns'], $event['programs'], $event['user_id']);
		$this->assertIsInt($event_id_2, 'The method createEvent should return an integer');
		$this->assertNotEmpty($event_id_2, 'The method createEvent should return a non empty value');

		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleEvent($event_id_2);
		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleCampaign($campaign_id);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testSaveEventSlot()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$event = $this->h_dataset->createEvent($location_id,$user_id_coordinator,null,null);
		$event_id = $event['event_id'];

		$event_slot = [
			'start_date' => '2026-01-01 00:00:00',
			'end_date' => '2026-01-01 06:00:00',
			'room' => null,
			'slot_capacity' => 1,
			'more_infos' => '',
			'users' => [],
			'event_id' => $event_id,
			'repeat_dates' => []
		];
		$event_slots = $this->model->saveEventSlot($event_slot['start_date'], $event_slot['end_date'], $event_slot['room'], $event_slot['slot_capacity'], $event_slot['more_infos'], $event_slot['users'], $event_slot['event_id'], $event_slot['repeat_dates'], 0, 0, 1, [], $user_id_coordinator);
		$this->assertIsArray($event_slots, 'The method saveEventSlot should return an array');
		$this->assertNotEmpty($event_slots, 'The method saveEventSlot should return a non empty array');

		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleEventSlots($event_slots);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
	}

	public function testDeleteEventSlot()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$event = $this->h_dataset->createEvent($location_id,$user_id_coordinator);
		$event_id = $event['event_id'];
		$event_slots = $event['event_slots'];

		$deleted = $this->model->deleteEventSlot($event_slots[0]->id, $user_id_coordinator);
		$this->assertIsBool($deleted, 'The method deleteEventSlot should return a boolean');
		$this->assertTrue($deleted, 'The method deleteEventSlot should return true');

		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
	}

	public function testSetupSlot()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);

		$event = [
			'name' => 'Event test',
			'color' => '#000000',
			'location' => $location_id,
			'is_conference_link' => 0,
			'conference_engine' => '',
			'link' => '',
			'generate_link_by' => 0,
			'manager' => null,
			'available_for' => 1,
			'campaigns' => [],
			'programs' => [],
			'user_id' => $user_id_coordinator,
		];
		$event_id = $this->model->createEvent($event['name'], $event['color'], $event['location'], $event['is_conference_link'], $event['conference_engine'], $event['link'], $event['generate_link_by'], $event['manager'], $event['available_for'], $event['campaigns'], $event['programs'], $event['user_id']);

		$setup_slot = [
			'event_id' => $event_id,
			'slot_duration' => '30 minutes',
			'slot_break_every' => 0,
			'slot_break_time' => '0 minutes',
			'slots_availables_to_show' => 0,
			'slot_can_book_until' => null,
			'slot_can_cancel' => 0,
			'slot_can_cancel_until' => null,
			'user_id' => $user_id_coordinator,
		];
		$setuped = $this->model->setupSlot($setup_slot['event_id'], $setup_slot['slot_duration'], $setup_slot['slot_break_every'], $setup_slot['slot_break_time'], $setup_slot['slots_availables_to_show'], $setup_slot['slot_can_book_until'], $setup_slot['slot_can_cancel'], $setup_slot['slot_can_cancel_until'], $setup_slot['user_id']);
		$this->assertIsBool($setuped, 'The method setupSlot should return a boolean');
		$this->assertTrue($setuped, 'The method setupSlot should return true');

		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
	}

	public function testSaveBookingNotifications()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$event = $this->h_dataset->createEvent($location_id,$user_id_coordinator,null,null);
		$event_id = $event['event_id'];

		$booking_notifications = [
			'applicant_notify' => 1,
			'applicant_notify_email' => null,
			'applicant_recall' => 1,
			'applicant_recall_frequency' => 7,
			'applicant_recall_email' => null,
			'manager_recall' => 1,
			'manager_recall_frequency' => 7,
			'manager_recall_email' => null,
			'users_recall' => 1,
			'users_recall_frequency' => 7,
			'users_recall_email' => null,
		];
		$saved = $this->model->saveBookingNotifications($event_id, $booking_notifications, $user_id_coordinator);
		$this->assertIsBool($saved, 'The method saveBookingNotifications should return a boolean');
		$this->assertTrue($saved, 'The method saveBookingNotifications should return true');

		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
	}

	public function testEditEvent()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);

		$event = [
			'name' => 'Event test',
			'color' => '#000000',
			'location' => $location_id,
			'is_conference_link' => 0,
			'conference_engine' => '',
			'link' => '',
			'generate_link_by' => 0,
			'manager' => null,
			'available_for' => 1,
			'campaigns' => [],
			'programs' => [],
			'user_id' => $user_id_coordinator,
		];
		$event_id = $this->model->createEvent($event['name'], $event['color'], $event['location'], $event['is_conference_link'], $event['conference_engine'], $event['link'], $event['generate_link_by'], $event['manager'], $event['available_for'], $event['campaigns'], $event['programs'], $event['user_id']);

		$event['name'] = 'Event test 2';
		$event['color'] = '#FFFFFF';
		$event_id_saved = $this->model->editEvent($event_id, $event['name'], $event['color'], $event['location'], $event['is_conference_link'], $event['conference_engine'], $event['link'], $event['generate_link_by'], $event['manager'], $event['available_for'], $event['campaigns'], $event['programs'], $event['user_id']);
		$this->assertIsInt($event_id_saved, 'The method editEvent should return an integer');
		$this->assertSame($event_id, $event_id_saved, 'The method editEvent should return the same event id');

		$event_object = $this->model->getEvent($event_id);
		$this->assertSame('Event test 2', $event_object->name, 'The event name should be "Event test 2"');
		$this->assertSame('#FFFFFF', $event_object->color, 'The event color should be "#FFFFFF"');

		$program = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$event['campaigns'][] = $campaign_id;
		$this->model->editEvent($event_id, $event['name'], $event['color'], $event['location'], $event['is_conference_link'], $event['conference_engine'], $event['link'], $event['generate_link_by'], $event['manager'], $event['available_for'], $event['campaigns'], $event['programs'], $event['user_id']);

		$event_object = $this->model->getEvent($event_id);
		$this->assertObjectHasProperty('campaigns', $event_object, 'The event should have campaigns');
		$this->assertContains($campaign_id, $event_object->campaigns, 'The event should have the campaign id');

		$event['programs'][] = $program['programme_id'];
		$event['campaigns'] = [];
		$event['available_for'] = 2;
		$this->model->editEvent($event_id, $event['name'], $event['color'], $event['location'], $event['is_conference_link'], $event['conference_engine'], $event['link'], $event['generate_link_by'], $event['manager'], $event['available_for'], $event['campaigns'], $event['programs'], $event['user_id']);
		$event_object = $this->model->getEvent($event_id);
		$this->assertContains($program['programme_id'], $event_object->programs, 'The event should have the program id');
		$this->assertObjectNotHasProperty('campaigns', $event_object, 'The event should not have campaigns');
		
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleCampaign($campaign_id);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testDeleteEvent()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$event = $this->h_dataset->createEvent($location_id,$user_id_coordinator,null,null);
		$event_id = $event['event_id'];

		$deleted = $this->model->deleteEvent($event_id, $user_id_coordinator);
		$this->assertIsBool($deleted, 'The method deleteEvent should return a boolean');
		$this->assertTrue($deleted, 'The method deleteEvent should return true');

		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleLocation($location_id);
	}

	public function testGetEventsAvailabilities()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$event = $this->h_dataset->createEvent($location_id,$user_id_coordinator);
		$event_id = $event['event_id'];
		$event_slots = $event['event_slots'];

		$availabilities = $this->model->getEventsAvailabilities();
		$this->assertIsArray($availabilities, 'The method getEventsAvailabilities should return an array');
		$this->assertNotEmpty($availabilities, 'The method getEventsAvailabilities should return a non empty array');
		$this->assertObjectHasProperty('start',$availabilities[0], 'The availability object should have a start property');
		$this->assertObjectHasProperty('end',$availabilities[0], 'The availability object should have a end property');
		$this->assertObjectHasProperty('event_id',$availabilities[0], 'The availability object should have an event_id property');
		$this->assertObjectHasProperty('capacity',$availabilities[0], 'The availability object should have a capacity property');
		$this->assertObjectHasProperty('slot',$availabilities[0], 'The availability object should have a slot property');

		$availabilities = $this->model->getEventsAvailabilities('2026-01-01 00:00:00', '2026-01-01 06:00:00');
		$this->assertIsArray($availabilities, 'The method getEventsAvailabilities should return an array');
		$this->assertNotEmpty($availabilities, 'The method getEventsAvailabilities should return a non empty array');
		
		$availabilities = $this->model->getEventsAvailabilities('2026-01-01 00:00:00', '2026-01-01 06:00:00', [$event_id]);
		$this->assertIsArray($availabilities, 'The method getEventsAvailabilities should return an array');
		$this->assertNotEmpty($availabilities, 'The method getEventsAvailabilities should return a non empty array');
		$this->assertSame('2026-01-01 00:00', $availabilities[0]->start, 'The availability start should be "2026-01-01 00:00"');
		$availability_id = $availabilities[0]->id;

		$availabilities = $this->model->getEventsAvailabilities('2026-01-01 00:00:00', '2026-01-01 06:00:00', [], $availability_id);
		$this->assertSame(1, count($availabilities), 'The method getEventsAvailabilities should return an array with one element');

		$availabilities = $this->model->getEventsAvailabilities('2026-01-01 00:00:00', '2026-01-01 00:30:00', [$event_id]);
		$this->assertSame(1, count($availabilities), 'The method getEventsAvailabilities should return an array with one element');

		$availabilities = $this->model->getEventsAvailabilities('2025-01-01 00:00:00', '2025-01-01 06:00:00', [$event_id]);
		$this->assertIsArray($availabilities, 'The method getEventsAvailabilities should return an array');
		$this->assertEmpty($availabilities, 'The method getEventsAvailabilities should return an empty array');

		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleEventSlots($event_slots);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
	}

	public function testGetAvailabilitiesByCampaignsAndPrograms()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$program = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$campaign_id_2 = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$event = $this->h_dataset->createEvent($location_id,$user_id_coordinator, '2026-01-01 00:00:00', '2026-01-01 06:00:00', 'Event test',1,[$campaign_id]);
		$event_id = $event['event_id'];
		$event_slots = $event['event_slots'];

		$availabilities = $this->model->getAvailabilitiesByCampaignsAndPrograms($campaign_id);
		$this->assertIsArray($availabilities, 'The method getAvailabilitiesByCampaignsAndPrograms should return an array');
		$this->assertNotEmpty($availabilities, 'The method getAvailabilitiesByCampaignsAndPrograms should return a non empty array');
		$this->assertObjectHasProperty('start', $availabilities[0], 'The availability should have a start property');
		$this->assertObjectHasProperty('end', $availabilities[0], 'The availability should have a end property');
		$this->assertObjectHasProperty('id', $availabilities[0], 'The availability should have a id property');
		$this->assertObjectHasProperty('event_id', $availabilities[0], 'The availability should have an event_id property');
		$this->assertObjectHasProperty('capacity', $availabilities[0], 'The availability should have a capacity property');
		$this->assertObjectHasProperty('slot', $availabilities[0], 'The availability should have a slot property');

		$availabilities = $this->model->getAvailabilitiesByCampaignsAndPrograms($campaign_id_2);
		$this->assertIsArray($availabilities, 'The method getAvailabilitiesByCampaignsAndPrograms should return an array');
		$this->assertEmpty($availabilities, 'The method getAvailabilitiesByCampaignsAndPrograms should return an empty array');

		$availabilities = $this->model->getAvailabilitiesByCampaignsAndPrograms($campaign_id,'','2026-01-01 00:00:00', '2026-01-01 06:00:00');
		$this->assertNotEmpty($availabilities, 'The method getAvailabilitiesByCampaignsAndPrograms should return a non empty array');

		$availabilities = $this->model->getAvailabilitiesByCampaignsAndPrograms($campaign_id,'','2025-01-01 00:00:00', '2025-01-01 06:00:00');
		$this->assertEmpty($availabilities, 'The method getAvailabilitiesByCampaignsAndPrograms should return an empty array');

		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleEventSlots($event_slots);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleCampaign($campaign_id);
		$this->h_dataset->deleteSampleCampaign($campaign_id_2);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testGetEventsByCampaignIds()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$program = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$campaign_id_2 = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);

		$event = [
			'name' => 'Event test',
			'color' => '#000000',
			'location' => $location_id,
			'is_conference_link' => 0,
			'conference_engine' => '',
			'link' => '',
			'generate_link_by' => 0,
			'manager' => null,
			'available_for' => 1,
			'campaigns' => [$campaign_id],
			'programs' => [],
			'user_id' => $user_id_coordinator,
		];
		$event_id = $this->model->createEvent($event['name'], $event['color'], $event['location'], $event['is_conference_link'], $event['conference_engine'], $event['link'], $event['generate_link_by'], $event['manager'], $event['available_for'], $event['campaigns'], $event['programs'], $event['user_id']);

		$events = $this->model->getEventsByCampaignIds([$campaign_id]);
		$this->assertIsArray($events, 'The method getEventsByCampaignIds should return an array');
		$this->assertNotEmpty($events, 'The method getEventsByCampaignIds should return a non empty array');

		$events = $this->model->getEventsByCampaignIds([$campaign_id_2]);
		$this->assertIsArray($events, 'The method getEventsByCampaignIds should return an array');
		$this->assertEmpty($events, 'The method getEventsByCampaignIds should return an empty array');

		$event['campaigns'][] = $campaign_id_2;
		$this->model->editEvent($event_id, $event['name'], $event['color'], $event['location'], $event['is_conference_link'], $event['conference_engine'], $event['link'], $event['generate_link_by'], $event['manager'], $event['available_for'], $event['campaigns'], $event['programs'], $event['user_id']);

		$events = $this->model->getEventsByCampaignIds([$campaign_id_2]);
		$this->assertNotEmpty($events, 'The method getEventsByCampaignIds should return a non empty array');

		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleCampaign($campaign_id);
		$this->h_dataset->deleteSampleCampaign($campaign_id_2);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testGetEventsByProgramCodes()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$program = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$program_2 = $this->h_dataset->createSampleProgram('Programme Test Unitaire 2', $user_id_coordinator);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);

		$event = [
			'name' => 'Event test',
			'color' => '#000000',
			'location' => $location_id,
			'is_conference_link' => 0,
			'conference_engine' => '',
			'link' => '',
			'generate_link_by' => 0,
			'manager' => null,
			'available_for' => 2,
			'campaigns' => [],
			'programs' => [$program['programme_id']],
			'user_id' => $user_id_coordinator,
		];
		$event_id = $this->model->createEvent($event['name'], $event['color'], $event['location'], $event['is_conference_link'], $event['conference_engine'], $event['link'], $event['generate_link_by'], $event['manager'], $event['available_for'], $event['campaigns'], $event['programs'], $event['user_id']);

		$events = $this->model->getEventsByProgramCodes([$program['programme_code']]);
		$this->assertIsArray($events, 'The method getEventsByProgramCodes should return an array');
		$this->assertNotEmpty($events, 'The method getEventsByProgramCodes should return a non empty array');

		$events = $this->model->getEventsByProgramCodes([$program_2['programme_code']]);
		$this->assertIsArray($events, 'The method getEventsByProgramCodes should return an array');
		$this->assertEmpty($events, 'The method getEventsByProgramCodes should return an empty array');

		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
		$this->h_dataset->deleteSampleProgram($program_2['programme_id']);
	}

	public function testCreateAvailabilityRegistrant()
	{
		$applicant_email = 'applicant' . rand(0, 1000) . '@emundus.test.fr';
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$applicant = $this->h_dataset->createSampleUser(1000, $applicant_email);
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$program = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$applicant_file = $this->h_dataset->createSampleFile($campaign_id, $applicant);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$event = $this->h_dataset->createEvent($location_id,$user_id_coordinator, '2026-01-01 00:00:00', '2026-01-01 06:00:00', 'Event test',1,[$campaign_id]);
		$event_id = $event['event_id'];
		$event_slots = $event['event_slots'];

		$availabilities = $this->model->getEventsAvailabilities('2026-01-01 00:00:00', '2026-01-01 06:00:00', [$event_id]);
		$availability_id = $availabilities[0]->id;

		$registrant_id = $this->model->createAvailabilityRegistrant($availability_id, $applicant_file);
		$this->assertIsInt($registrant_id, 'The method createAvailabilityRegistrant should return an integer');
		$this->assertGreaterThan(0, $registrant_id, 'The method createAvailabilityRegistrant should return a positive integer');

		$registrant_id = $this->model->createAvailabilityRegistrant($availability_id, $applicant_file);
		$this->assertIsInt($registrant_id, 'The method createAvailabilityRegistrant should return an integer');
		$this->assertSame(0, $registrant_id, 'The method createAvailabilityRegistrant should return 0');

		$this->h_dataset->deleteSampleFile($applicant_file);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleUser($applicant);
		$this->h_dataset->deleteSampleEventSlots($event_slots);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleCampaign($campaign_id);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testGetAvailabilityRegistrants()
	{
		$applicant_email = 'applicant' . rand(0, 1000) . '@emundus.test.fr';
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$applicant = $this->h_dataset->createSampleUser(1000, $applicant_email);
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$program = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$applicant_file = $this->h_dataset->createSampleFile($campaign_id, $applicant);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$event = $this->h_dataset->createEvent($location_id,$user_id_coordinator, '2026-01-01 00:00:00', '2026-01-01 06:00:00', 'Event test',1,[$campaign_id]);
		$event_id = $event['event_id'];
		$event_slots = $event['event_slots'];

		$availabilities = $this->model->getEventsAvailabilities('2026-01-01 00:00:00', '2026-01-01 06:00:00', [$event_id]);
		$availability_id = $availabilities[0]->id;

		$registrant_id = $this->model->createAvailabilityRegistrant($availability_id, $applicant_file);

		$registrants = $this->model->getAvailabilityRegistrants();
		$this->assertIsArray($registrants, 'The method getAvailabilityRegistrants should return an array');
		$this->assertNotEmpty($registrants, 'The method getAvailabilityRegistrants should return a non empty array');
		$this->assertObjectHasProperty('id', $registrants[0], 'The registrant should have an id property');
		$registrants_id = array_map(function($registrant) {
			return $registrant->id;
		}, $registrants);
		$this->assertContains($registrant_id, $registrants_id, 'The registrant id should be in the array');
		$this->assertObjectHasProperty('availability', $registrants[0], 'The registrant should have an availability_id property');
		$this->assertObjectHasProperty('event', $registrants[0], 'The registrant should have an event property');
		$this->assertObjectHasProperty('slot', $registrants[0], 'The registrant should have a slot property');
		$this->assertObjectHasProperty('link', $registrants[0], 'The registrant should have a link property');

		$registrants = $this->model->getAvailabilityRegistrants($availability_id);
		$this->assertNotEmpty($registrants, 'The method getAvailabilityRegistrants should return a non empty array');

		$registrants = $this->model->getAvailabilityRegistrants($availability_id, 0, $event_id);
		$this->assertNotEmpty($registrants, 'The method getAvailabilityRegistrants should return a non empty array');

		$registrants = $this->model->getAvailabilityRegistrants($availabilities[1]->id);
		$this->assertEmpty($registrants, 'The method getAvailabilityRegistrants should return an empty array');

		$this->h_dataset->deleteSampleFile($applicant_file);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleUser($applicant);
		$this->h_dataset->deleteSampleEventSlots($event_slots);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleCampaign($campaign_id);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testGetMyBookings()
	{
		$applicant_email = 'applicant' . rand(0, 1000) . '@emundus.test.fr';
		$applicant_email_2 = 'applicant' . rand(0, 1000) . '@emundus.test.fr';
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$applicant = $this->h_dataset->createSampleUser(1000, $applicant_email);
		$applicant_2 = $this->h_dataset->createSampleUser(1000, $applicant_email_2);
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$program = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$applicant_file = $this->h_dataset->createSampleFile($campaign_id, $applicant);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$event = $this->h_dataset->createEvent($location_id,$user_id_coordinator, '2026-01-01 00:00:00', '2026-01-01 06:00:00', 'Event test',1,[$campaign_id]);
		$event_id = $event['event_id'];
		$event_slots = $event['event_slots'];

		$availabilities = $this->model->getEventsAvailabilities('2026-01-01 00:00:00', '2026-01-01 06:00:00', [$event_id]);
		$availability_id = $availabilities[0]->id;

		$registrant_id = $this->model->createAvailabilityRegistrant($availability_id, $applicant_file);

		$registrants = $this->model->getMyBookings([$event_id], $applicant);
		$this->assertIsArray($registrants, 'The method getMyBookings should return an array');
		$this->assertNotEmpty($registrants, 'The method getMyBookings should return a non empty array');
		$this->assertObjectHasProperty('id', $registrants[0], 'The registrant should have an id property');
		$this->assertSame($registrant_id, $registrants[0]->id, 'The registrant id should be the same as the one created');
		$this->assertObjectHasProperty('availability', $registrants[0], 'The registrant should have an availability property');
		$this->assertObjectHasProperty('event', $registrants[0], 'The registrant should have an event property');
		$this->assertObjectHasProperty('slot', $registrants[0], 'The registrant should have an slot property');
		$this->assertObjectHasProperty('link', $registrants[0], 'The registrant should have a link property');
		$this->assertObjectHasProperty('start', $registrants[0], 'The registrant should have a start property');
		$this->assertObjectHasProperty('end', $registrants[0], 'The registrant should have a end property');

		$registrants = $this->model->getMyBookings([$event_id], $applicant_2);
		$this->assertIsArray($registrants, 'The method getMyBookings should return an array');
		$this->assertEmpty($registrants, 'The method getMyBookings should return an empty array');

		$this->h_dataset->deleteSampleFile($applicant_file);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleUser($applicant);
		$this->h_dataset->deleteSampleUser($applicant_2);
		$this->h_dataset->deleteSampleEventSlots($event_slots);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleCampaign($campaign_id);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testUpdateLink()
	{
		$applicant_email = 'applicant' . rand(0, 1000) . '@emundus.test.fr';
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$applicant = $this->h_dataset->createSampleUser(1000, $applicant_email);
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$program = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$applicant_file = $this->h_dataset->createSampleFile($campaign_id, $applicant);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$event = $this->h_dataset->createEvent($location_id,$user_id_coordinator, '2026-01-01 00:00:00', '2026-01-01 06:00:00', 'Event test',1,[$campaign_id]);
		$event_id = $event['event_id'];
		$event_slots = $event['event_slots'];

		$availabilities = $this->model->getEventsAvailabilities('2026-01-01 00:00:00', '2026-01-01 06:00:00', [$event_id]);
		$availability_id = $availabilities[0]->id;

		$registrant_id = $this->model->createAvailabilityRegistrant($availability_id, $applicant_file);
		$updated_link = $this->model->updateLink($registrant_id,'https://zoom.us/dfdfdsfd');
		$this->assertSame(true,$updated_link);
		$updated_link = $this->model->updateLink($registrant_id,'https://teams.com/3434343','fdfdf-FDFD3434-fdf');
		$this->assertSame(true,$updated_link);

		$this->h_dataset->deleteSampleFile($applicant_file);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleUser($applicant);
		$this->h_dataset->deleteSampleEventSlots($event_slots);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleCampaign($campaign_id);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testGetRegistrantCount()
	{
		$applicant_email = 'applicant' . rand(0, 1000) . '@emundus.test.fr';
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$applicant = $this->h_dataset->createSampleUser(1000, $applicant_email);
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$program = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$applicant_file = $this->h_dataset->createSampleFile($campaign_id, $applicant);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$event = $this->h_dataset->createEvent($location_id,$user_id_coordinator, '2026-01-01 00:00:00', '2026-01-01 06:00:00', 'Event test',1,[$campaign_id]);
		$event_id = $event['event_id'];
		$event_slots = $event['event_slots'];

		$availabilities = $this->model->getEventsAvailabilities('2026-01-01 00:00:00', '2026-01-01 06:00:00', [$event_id]);
		$availability_id = $availabilities[0]->id;

		$registrant_id = $this->model->createAvailabilityRegistrant($availability_id, $applicant_file);

		$registrant_count = $this->model->getRegistrantCount($event_id);
		$this->assertIsInt($registrant_count);
		$this->assertSame(1,$registrant_count);

		$this->h_dataset->deleteSampleFile($applicant_file);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleUser($applicant);
		$this->h_dataset->deleteSampleEventSlots($event_slots);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleCampaign($campaign_id);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testDuplicateEvent()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);
		$program = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$event = $this->h_dataset->createEvent($location_id,$user_id_coordinator, '2026-01-01 00:00:00', '2026-01-01 06:00:00', 'Event test',1,[$campaign_id]);
		$event_id = $event['event_id'];
		$event_slots = $event['event_slots'];

		$new_event_id = $this->model->duplicateEvent($event_id,$user_id_coordinator);
		$this->assertIsInt($new_event_id);
		$this->assertGreaterThan(0,$new_event_id);

		$event = $this->model->getEvent($new_event_id);
		$this->assertEmpty($event->campaigns);

		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleEventSlots($this->model->getEventsSlots('','',$new_event_id));
		$this->h_dataset->deleteSampleEventSlots($event_slots);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleEvent($new_event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleCampaign($campaign_id);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testGetProgramsCampaignsCount()
	{
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);
		$program = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);

		$location_id = $this->model->saveLocation('Lieu de test', 'Adresse de test', [], $user_id_coordinator);
		$event = $this->h_dataset->createEvent($location_id,$user_id_coordinator, '2026-01-01 00:00:00', '2026-01-01 06:00:00', 'Event test',1,[$campaign_id]);
		$event_id = $event['event_id'];
		$event_slots = $event['event_slots'];

		$count = $this->model->getProgramsCampaignsCount($event_id);
		$this->assertSame(1,$count);

		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleEventSlots($event_slots);
		$this->h_dataset->deleteSampleEvent($event_id);
		$this->h_dataset->deleteSampleLocation($location_id);
		$this->h_dataset->deleteSampleCampaign($campaign_id);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}
}
