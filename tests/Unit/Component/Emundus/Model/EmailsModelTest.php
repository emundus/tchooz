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

		$created = $this->model->createEmail($data, null, null, null, null, null, $this->dataset['coordinator']);
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
		$lbl  = 'Test de la suppression ' . rand(0, 99999);
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
		$created = $this->model->createEmail($data, null, null, null, null, null, $this->dataset['coordinator']);
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

	// -------------------------------------------------------------------------
	// scopeAliasElementsToFnumForms — used by setTagsFabrik to resolve an alias
	// against the file's current campaign forms (moved-campaign bug).
	// -------------------------------------------------------------------------

	/**
	 * @covers EmundusModelEmails::scopeAliasElementsToFnumForms
	 *
	 * @since version 2.0.0
	 */
	public function testScopeAliasElementsToFnumFormsWhenSeveralFormsShareAliasKeepsOnlyCurrentCampaignElement()
	{
		// Two elements share the same alias on the same table: id 10 belongs to the original
		// campaign form, id 20 to the current campaign form. Only the current one must remain.
		$alias_element_ids = [10, 20];
		$fnum_form_elements = [3, 20, 42];

		$scoped = $this->model->scopeAliasElementsToFnumForms($alias_element_ids, $fnum_form_elements);

		$this->assertSame([20], $scoped, 'Seul l\'élément appartenant au formulaire de la campagne courante doit être conservé');
	}

	/**
	 * @covers EmundusModelEmails::scopeAliasElementsToFnumForms
	 *
	 * @since version 2.0.0
	 */
	public function testScopeAliasElementsToFnumFormsIgnoresElementOrderAndPicksCurrentCampaignElement()
	{
		// The original-campaign element (id 5) comes first and has the lowest id: without scoping
		// it would win. Scoping must still keep the current-campaign element (id 30).
		$alias_element_ids = [5, 30];
		$fnum_form_elements = [30];

		$scoped = $this->model->scopeAliasElementsToFnumForms($alias_element_ids, $fnum_form_elements);

		$this->assertSame([30], $scoped, 'Le scoping doit ignorer l\'ordre des éléments et retenir celui de la campagne courante');
	}

	/**
	 * @covers EmundusModelEmails::scopeAliasElementsToFnumForms
	 *
	 * @since version 2.0.0
	 */
	public function testScopeAliasElementsToFnumFormsWhenScopeUnknownReturnsAllElements()
	{
		// No campaign forms could be resolved for the file: fall back to the previous behaviour
		// (every element sharing the alias) rather than dropping the tag entirely.
		$alias_element_ids = [10, 20];

		$scoped = $this->model->scopeAliasElementsToFnumForms($alias_element_ids, []);

		$this->assertSame([10, 20], $scoped, 'Sans périmètre de campagne connu, tous les éléments de l\'alias doivent être conservés');
	}

	/**
	 * @covers EmundusModelEmails::scopeAliasElementsToFnumForms
	 *
	 * @since version 2.0.0
	 */
	public function testScopeAliasElementsToFnumFormsWhenNoAliasElementInScopeReturnsAllElements()
	{
		// The alias only exists in forms outside the current campaign: keep every element rather
		// than resolving to nothing, so a genuinely shared value is not lost.
		$alias_element_ids = [10, 20];
		$fnum_form_elements = [1, 2, 3];

		$scoped = $this->model->scopeAliasElementsToFnumForms($alias_element_ids, $fnum_form_elements);

		$this->assertSame([10, 20], $scoped, 'Si aucun élément de l\'alias n\'est dans le périmètre, tous les éléments sont conservés (repli)');
	}
}