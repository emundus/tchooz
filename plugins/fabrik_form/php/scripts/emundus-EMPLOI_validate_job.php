<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\User\UserFactoryInterface;

defined('_JEXEC') or die();

include_once(JPATH_BASE . '/components/com_emundus/models/emails.php');
include_once(JPATH_BASE . '/components/com_emundus/helpers/files.php');

$app            = Factory::getApplication();
$db             = Factory::getContainer()->get('DatabaseDriver');
$user           = $app->getIdentity();
$mailer         = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
$email_from_sys = $app->get('mailfrom');

//$eMConfig = JComponentHelper::getParams('com_emundus');

$deposant       = $fabrikFormData['user_raw'][0];
$deposant       = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($deposant);
$university     = $fabrikFormData['etablissement_raw'][0];
$valide         = $fabrikFormData['valide_raw'][0];
$valide_comite  = $fabrikFormData['valide_comite_raw'][0];
$intitule_poste = $fabrikFormData['intitule_poste_raw'];

$elements_valide = (new EmundusHelperFiles)->getElementsValuesOther(2280);
$i               = 0;
foreach ($elements_valide->sub_values as $key => $value)
{
	if ($value == $valide)
	{
		$valide = $elements_valide->sub_labels[$i];
		break;
	}
	$i++;
}
$elements_valide_comite = (new EmundusHelperFiles)->getElementsValuesOther(3872);
$i                      = 0;
foreach ($elements_valide_comite->sub_values as $key => $value)
{
	if ($value == $valide_comite)
	{
		$valide_comite = $elements_valide_comite->sub_labels[$i];
		break;
	}
	$i++;
}

$emails = new EmundusModelEmails;

$post  = array('FICHE_EMPLOI'               => $intitule_poste,
               'FICHE_EMPLOI_VALIDE'        => $valide,
               'FICHE_EMPLOI_VALIDE_COMITE' => $valide_comite
);
$email = $emails->getEmail("validate_job");

$tags = $emails->setTags($deposant->id, $post, null, '', $email->message);
// Mail
$from      = $email->emailfrom;
$from_id   = 62;
$fromname  = $email->name;
$recipient = $deposant->email;
$subject   = $email->subject;
$body      = preg_replace($tags['patterns'], $tags['replacements'], $email->message);
$mode      = 1;

//$attachment[] = $path_file;
$replyto     = $user->email;
$replytoname = $user->name;

// setup mail
$sender = array(
	$email_from_sys,
	$fromname
);

if (!empty($recipient))
{
	$mailer->setSender($sender);
	$mailer->addReplyTo($from, $fromname);
	$mailer->addRecipient($recipient);
	$mailer->setSubject($subject);
	$mailer->isHTML(true);
	$mailer->Encoding = 'base64';
	$mailer->setBody($body);

	$send = $mailer->Send();
	if ($send !== true)
	{
		echo 'Error sending email: TO ' . $recipient . ' FROM ' . $from . ' ' . $send->__toString();
		die();
	}
	else
	{
		$message = array(
			'user_id_from' => $from_id,
			'user_id_to'   => $deposant->id,
			'subject'      => $subject,
			'message'      => $body,
			'email_to'     => $recipient
		);
		$emails->logEmail($message);
	}
}
?>