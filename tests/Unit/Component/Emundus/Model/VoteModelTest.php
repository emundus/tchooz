<?php
/**
 * @package     Unit\Component\Emundus\Model
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Model;

use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;

class VoteModelTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '', $className = null)
	{
		parent::__construct('vote', $data, $dataName, 'EmundusModelVote');
	}

	public function testGetVotesByUser()
	{
		$ip = '1.1.1.1';
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);
		$username = 'test-candidat-' . rand(0, 1000) . '@emundus.fr';
		$applicant = $this->h_dataset->createSampleUser(9, $username);
		$file = $this->h_dataset->createSampleFile($campaign,$applicant);

		$query = $this->db->getQuery(true);
		$query->select('id')
			->from($this->db->quoteName('#__emundus_campaign_candidature'))
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($file));
		$this->db->setQuery($query);
		$ccid = $this->db->loadResult();

		// 1. En tant qu'utilisateur non connecté, je n'ai pas encore voté
		$guest_user = Factory::getUser();
		$votes = $this->model->getVotesByUser($guest_user,null,$ip);
		$this->assertIsArray($votes);
		$this->assertEmpty($votes);

		// 2. En tant qu'utilisateur non connecté, je viens de voter
		$this->model->vote('test-votant-guest@emundus.fr', $ccid, $guest_user->id,$ip);
		$votes = $this->model->getVotesByUser($guest_user,null,$ip);
		$this->assertNotEmpty($votes);

		// 3. En tant qu'utilisateur connecté, je n'ai pas encore voté
		$username = 'test-votant-' . rand(0, 1000) . '@emundus.fr';
		$uid = $this->h_dataset->createSampleUser(9, $username);
		$registered_user = Factory::getUser($uid);
		$votes = $this->model->getVotesByUser($registered_user,null,$ip);
		$this->assertIsArray($votes);
		$this->assertEmpty($votes);

		// 4. En tant qu'utilisateur connecté, je viens de voter
		$this->model->vote($registered_user->email, $ccid, $registered_user->id,$ip);

		$votes = $this->model->getVotesByUser($registered_user,null,$ip);
		$this->assertNotEmpty($votes);

		$this->h_dataset->deleteSampleUser($registered_user->id);
		$this->h_dataset->deleteSampleUser($applicant);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);

		$query = $this->db->getQuery(true);
		$query->delete($this->db->quoteName('#__emundus_vote'))
			->where($this->db->quoteName('user') . ' = ' . $this->db->quote($registered_user->id))
			->orWhere($this->db->quoteName('email') . ' LIKE ' . $this->db->quote('test-votant-guest@emundus.fr'));
		$this->db->setQuery($query);
		$this->db->execute();
	}

	public function testVote()
	{
		$ip = '1.1.1.1';
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);
		$username = 'test-candidat-' . rand(0, 1000) . '@emundus.fr';
		$applicant = $this->h_dataset->createSampleUser(9, $username);
		$file = $this->h_dataset->createSampleFile($campaign,$applicant);

		$query = $this->db->getQuery(true);
		$query->select('id')
			->from($this->db->quoteName('#__emundus_campaign_candidature'))
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($file));
		$this->db->setQuery($query);
		$ccid = $this->db->loadResult();

		// 1. En tant qu'utilisateur non connecté, je peux voter pour un projet
		$guest_user = Factory::getUser();
		$this->assertTrue($this->model->vote('test-votant-guest@emundus.fr', $ccid, $guest_user->id,$ip));

		// 2. En tant qu'utilisateur non connecté je ne peux pas voter 2 fois pour le même projet
		$this->assertFalse($this->model->vote('test-votant-guest@emundus.fr', $ccid, $guest_user->id,$ip));

		// 3. En tant qu'utilisateur connecté, je peux voter un projet
		$username = 'test-votant-' . rand(0, 1000) . '@emundus.fr';
		$uid = $this->h_dataset->createSampleUser(9, $username);
		$registered_user = Factory::getUser($uid);
		$this->assertTrue($this->model->vote($registered_user->email, $ccid, $registered_user->id,$ip));

		// 4. En tant qu'utilisateur connecté je ne peux pas voter 2 fois pour le même projet
		$this->assertFalse($this->model->vote($registered_user->email, $ccid, $registered_user->id,$ip));

		$this->h_dataset->deleteSampleUser($registered_user->id);
		$this->h_dataset->deleteSampleUser($applicant);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);

		$query = $this->db->getQuery(true);
		$query->delete($this->db->quoteName('#__emundus_vote'))
			->where($this->db->quoteName('user') . ' = ' . $this->db->quote($registered_user->id))
			->orWhere($this->db->quoteName('email') . ' LIKE ' . $this->db->quote('test-votant-guest@emundus.fr'));
		$this->db->setQuery($query);
		$this->db->execute();
	}
}