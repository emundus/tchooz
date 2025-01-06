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

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelEmails
 */
class EmailsModelTest extends UnitTestCase
{

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('emails', $data, $dataName, 'EmundusModelEmails');
	}

	/**
	 * @covers EmundusModelEmails::deleteEmail
	 *
	 * @since version 1.0.0
	 */
	public function testDeleteSystemEmails()
	{
		$data = $this->model->getAllEmails(999, 0, '', '', '');
		$this->assertNotEmpty($data);

		// select one email with type 1
		$systemodel = array_filter($data['datas'], function ($email) {
			return $email->type == 1;
		});

		$deleted = $this->model->deleteEmail(current($systemodel)->id);
		$this->assertFalse($deleted, 'La suppression de l\'email n\'a pas fonctionné, car c\'est un email système');

		$email = $this->model->getEmailById(current($systemodel)->id);
		$this->assertNotEmpty($email->id, 'On retrouve bien l\'email par son id');
	}

	/**
	 * @covers EmundusModelEmails::createEmail
	 *
	 * @since version 1.0.0
	 */
	public function testCreateEmail()
	{
		$data = [
			'lbl'       => 'Test de la création',
			'subject'   => 'Test de la création',
			'name'      => '',
			'emailfrom' => '',
			'message'   => '<p>Test de la création</p>',
			'type'      => 2,
			'category'  => '',
			'published' => 1
		];

		$created = $this->model->createEmail($data);
		$this->assertNotFalse($created, 'La création de l\'email a fonctionné');
		$created_email = $this->model->getEmailById($created);

		$this->assertNotNull($created_email, 'L\'email a bien été créé, on le retrouve par son sujet');

		$email_by_id = $this->model->getEmailById($created_email->id);
		$this->assertNotNull($email_by_id, 'L\'email a bien été créé, on le retrouve par son id');
		$this->assertSame($created_email->subject, $email_by_id->subject, 'L\'email a bien été créé, on le retrouve par son id et il est le même que par son libelle');

		$this->model->deleteEmail($created_email->id);
	}

	/**
	 * @covers EmundusModelEmails::deleteEmail
	 *
	 * @since version 1.0.0
	 */
	public function testDeleteEmails()
	{
		$lbl  = 'Test de la suppression ' . rand(0, 1000);
		$data = [
			'lbl'       => $lbl,
			'subject'   => 'Test de la création',
			'name'      => 'Test de la création',
			'emailfrom' => '',
			'message'   => '<p>Test de la création</p>',
			'type'      => 2,
			'category'  => '',
			'published' => 1
		];

		sleep(1);
		$created = $this->model->createEmail($data);
		$this->assertNotFalse($created, 'La création de l\'email a fonctionnée');

		$deleted = $this->model->deleteEmail($created);
		$this->assertTrue($deleted, 'La suppression de l\'email a fonctionnée d\'après le retour de la fonction.');

		$email = $this->model->getEmailById($created);
		$this->assertNull($email, 'L\'email a bien été supprimé, on ne le retrouve plus en base');
	}

	/**
	 * @covers EmundusModelEmails::sendExpertMail
	 *
	 * @since version 1.0.0
	 */
	public function testsendExpertMail()
	{
		$response = $this->model->sendExpertMail([],0,'','','',[],'');
		$this->assertEmpty($response['sent'], 'L\'envoi de l\'email a échoué, car il n\'y a pas de fichier');

		$response = $this->model->sendExpertMail([$this->dataset['fnum']],0,'','','',[],'');
		$this->assertEmpty($response['sent'], 'L\'envoi de l\'email a échoué, car il manque des paramètres');
	}
}