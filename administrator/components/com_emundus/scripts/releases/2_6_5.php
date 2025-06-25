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
use Joomla\CMS\Component\ComponentHelper;

class Release2_6_5Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query  = $this->db->createQuery();
		$tasks = [];

		try
		{
			EmundusHelperUpdate::insertTranslationsTag('ADD_APPLICATION_FILE', 'Déposer un nouveau dossier');
			EmundusHelperUpdate::insertTranslationsTag('ADDRESS', 'Adresse');
			EmundusHelperUpdate::insertTranslationsTag('ADMIN_SETUP_STATUS', 'Statuts de dossier');
			EmundusHelperUpdate::insertTranslationsTag('BACK_TO_LOGIN', '<br />Déjà un compte ? <a href=\"connexion\">Connectez-vous</a>');
			EmundusHelperUpdate::insertTranslationsTag('BLANK', '');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ACCOUNT_PERSONAL_DETAILS', 'Informations personnelles');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_COMMENTAIRE', 'Commentaires');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_DASHBOARD_FAQ_QUESTION', 'Une question ?');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FABRIK_NEW_FILE', 'Nouveau dossier');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FABRIK_NEW_FILE_DESC', 'Votre dossier est en cours de création, merci de patienter...');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FABRIK_WANT_EXIT_FORM_CANCEL', 'Retour');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FABRIK_WANT_EXIT_FORM_CONFIRM', 'Quitter sans enregistrer');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FABRIK_WANT_EXIT_FORM_TEXT', 'Certaines informations saisies n’ont pas été enregistrées. Si vous quittez cette page maintenant, elles ne seront pas conservées.');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FABRIK_WANT_EXIT_FORM_TITLE', 'Voulez-vous vraiment quitter le formulaire ?');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_MESSAGE_SENT_TO', 'Message envoyé à ');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_NEWSLETTER', 'Newsletter');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_SHARE_MESSAGE', 'Souhaitez-vous partager ces dossiers avec des groupes ou des utilisateurs ?');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_UNIVERSITY', 'Université');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_USERS_DEFAULT_LANGAGE', 'Langue de préférence');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_USERS_NATIONALITY', 'Nationalité');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_WANT_RESET_PASSWORD', 'Souhaitez-vous envoyer un lien de réinitialisation ?');
			EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_OPTIONNAL_FIELD', 'facultatif');
			EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_REPEAT_GROUP_MAX', 'Vous pouvez saisir jusqu\'à %s entrées');
			EmundusHelperUpdate::insertTranslationsTag('COM_FABRIK_REQUIRED_ICON_NOT_DISPLAYED', 'Tous les champs sont obligatoires sauf mention contraire');
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_EMAIL_PASSWORD_RESET_BODY_FOR_OTHER', '<p>Madame, Monsieur,</p>\n<p>Une demande de réinitialisation du mot de passe de votre compte <b> %s</b> a été effectuée par un administrateur.</p>\n<p>Cliquez sur le lien ci-dessous pour finaliser la réinitialisation :</p>\n<p>%3$s</p>\n<p>Si ce lien ne fonctionne pas, voici le code de vérification à saisir sur la page de réinitialisation de mot de passe :  %2$s</p>');
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_EMAIL_PASSWORD_RESET_SUBJECT', 'Réinitialisation de mot de passe pour %s');
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_EMAIL_PASSWORD_RESET_SUBJECT_FOR_OTHER', '%s - Une demande de réinitialisation de mot de passe a été effectuée pour vous');
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_FIELD_RESET_PASSWORD1_LABEL', 'Nouveau mot de passe');
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_RESET_REQUEST_FAILED', 'Si un compte est associé à cette adresse, alors vous avez reçu un e-mail afin de réinitialiser votre mot de passe');
			EmundusHelperUpdate::insertTranslationsTag('ELEMENT_FILTER', 'Filtre avancé');
			EmundusHelperUpdate::insertTranslationsTag('MAJOR_DEGREE_OTHER', 'Autre discipline principale (le cas échéant)');
			EmundusHelperUpdate::insertTranslationsTag('MAJOR_DEGREE', 'Majorité du diplôme de bachelier');
			EmundusHelperUpdate::insertTranslationsTag('ENGLISH_TITLE', 'Titre anglais de votre diplôme');
			EmundusHelperUpdate::insertTranslationsTag('OFFICIAL_TITLE_OF_YOUR_DEGREE', 'Titre officiel du diplôme');
			EmundusHelperUpdate::insertTranslationsTag('FORM_SUBMITTING_APPLICATION', 'Confirmation d\'envoi de dossier');
			EmundusHelperUpdate::insertTranslationsTag('FORM_SUBMITTING_APPLICATION_HEADER', '');
			EmundusHelperUpdate::insertTranslationsTag('JGLOBAL_AUTH_INVALID_PASS', 'Cette combinaison adresse e-mail/mot de passe est incorrecte');
			EmundusHelperUpdate::insertTranslationsTag('JGLOBAL_AUTH_NO_USER', 'Cette combinaison adresse e-mail/mot de passe est incorrecte');
			EmundusHelperUpdate::insertTranslationsTag('MESSAGE_SENT', 'Message envoyé à ');
			EmundusHelperUpdate::insertTranslationsTag('MOD_EM_FOOTER_LEGAL_INFO_LINK', 'Mentions légales');
			EmundusHelperUpdate::insertTranslationsTag('PART_D_WARNING', 'Veuillez vous référer aux exigences linguistiques spécifiques liées à la filière d\'études que vous avez l\'intention de suivre.');
			EmundusHelperUpdate::insertTranslationsTag('PASSWORD_RESET_CONFIRMATION_EMAIL_TEXT', 'Bonjour,</br></br> Une demande de réinitialisation du mot de passe de votre compte <b>%s</b> a été faite.</br></br>Pour réinitialiser votre mot de passe, cliquez sur le lien ci-dessous :</br>%3$s</br></br>Si ce lien ne correspond pas, vous devrez soumettre ce jeton sur la page de réinitialisation du mot de passe :%2$s</br>');
			EmundusHelperUpdate::insertTranslationsTag('PHASE_DETAILS', 'Paramétres des phases');
			EmundusHelperUpdate::insertTranslationsTag('PLEASE_CHECK_THIS_FIELD', 'Veuillez cocher la case');
			EmundusHelperUpdate::insertTranslationsTag('PROGRAM_LOGO', 'Logo du programme');
			EmundusHelperUpdate::insertTranslationsTag('SETUP_STATUS', 'Paramétrer un statut de dossier');
			EmundusHelperUpdate::insertTranslationsTag('STREET_LINE_2', 'Adresse 2');
			EmundusHelperUpdate::insertTranslationsTag('WARNING', 'Attention !');
			EmundusHelperUpdate::insertTranslationsTag('WELCOME_PROJECT', 'Bienvenue dans votre espace de gestion de projet');

			$result['status']  = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}