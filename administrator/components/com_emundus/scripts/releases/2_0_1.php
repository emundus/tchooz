<?php
/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusHelperUpdate;

class Release2_0_1Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$query  = $this->db->getQuery(true);
		$result = ['status' => false, 'message' => ''];

		try
		{
			// Add button column in jos_emundus_setup_emails
			if(!EmundusHelperUpdate::addColumn('jos_emundus_setup_emails', 'button', 'VARCHAR(255) NOT NULL DEFAULT ""')['status']) {
				EmundusHelperUpdate::displayMessage('Error adding column button in jos_emundus_setup_emails');
			}
			//

			// Create new template for account_creation and update new_account email
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_email_templates'))
				->where($this->db->quoteName('lbl') . ' LIKE ' . $this->db->quote('account_creation'));
			$this->db->setQuery($query);
			$account_creation_tmpl = $this->db->loadResult();

			if (empty($account_creation_tmpl))
			{
				$account_email_tmpl = file_get_contents(JPATH_ROOT . '/administrator/components/com_emundus/scripts/html/account_creation_template.html');
				$tmpl_insert             = [
					'date_time' => date('Y-m-d H:i:s'),
					'lbl'       => 'account_creation',
					'Template'  => $account_email_tmpl,
					'type'      => 1,
					'published' => 1
				];
				$tmpl_insert             = (object) $tmpl_insert;
				$this->db->insertObject('#__emundus_email_templates', $tmpl_insert);

				$account_creation_tmpl = $this->db->insertid();
			}

			if(!empty($account_creation_tmpl))
			{
				$query->clear()
					->select('id,button')
					->from($this->db->quoteName('#__emundus_setup_emails'))
					->where($this->db->quoteName('lbl') . ' LIKE ' . $this->db->quote('new_account'));
				$this->db->setQuery($query);
				$new_account_tmpl = $this->db->loadObject();

				if (empty($new_account_tmpl->id))
				{
					$new_account = [
						'lbl'        => 'new_account',
						'subject'    => 'Finalisation de la création de votre compte / Complete my account',
						'emailfrom'  => '',
						'message'    => file_get_contents(JPATH_ROOT . '/administrator/components/com_emundus/scripts/html/new_account.html'),
						'type'       => 1,
						'published'  => 1,
						'email_tmpl' => $account_creation_tmpl,
						'category'   => 'Système',
						'cci'        => '',
						'button'     => 'Finaliser mon compte / Complete my account'
					];
					$new_account = (object) $new_account;
					$this->db->insertObject('#__emundus_setup_emails', $new_account);
				}
				else
				{
					$query->clear()
						->update($this->db->quoteName('#__emundus_setup_emails'))
						->set($this->db->quoteName('email_tmpl') . ' = ' . $account_creation_tmpl)
						->where($this->db->quoteName('id') . ' = ' . $new_account_tmpl->id);
					if ($new_account_tmpl->button == '')
					{
						$query->set($this->db->quoteName('button') . ' = ' . $this->db->quote('Finaliser mon compte / Complete my account'));
					}
					$this->db->setQuery($query);
					$this->db->execute();
				}
			}
			//

			// Create new_account_sso email
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_setup_emails'))
				->where($this->db->quoteName('lbl') . ' LIKE ' . $this->db->quote('new_account_sso'));
			$this->db->setQuery($query);
			$account_sso_tmpl = $this->db->loadResult();
			if(empty($account_sso_tmpl)) {
				$account_sso = [
					'lbl' => 'new_account_sso',
					'subject' => 'Un compte a été crée pour vous / An account has been created for you',
					'emailfrom' => '',
					'message' => file_get_contents(JPATH_ROOT . '/administrator/components/com_emundus/scripts/html/new_account_sso.html'),
					'type' => 1,
					'published' => 0,
					'email_tmpl' => 1,
					'category' => 'Système',
					'cci' => '',
					'button' => ''
				];
				$account_sso = (object) $account_sso;
				$this->db->insertObject('#__emundus_setup_emails', $account_sso);
			}
			//

			$result['status'] = true;
		}
		catch (\Exception $e)
		{
			$result['message'] = $e->getMessage();

			return $result;
		}


		return $result;
	}
}