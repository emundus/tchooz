<?php
/**
 * @package     Tchooz\Services\Emails
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Emails;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Mail\MailerInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Symfony\Component\Yaml\Yaml;
use Tchooz\Entities\User\EmundusUserEntity;
use Tchooz\Repositories\User\EmundusUserRepository;

class EmailService
{
	private MailerInterface $mailer;

	private Registry $emConfig;

	private \EmundusModelEmails $mEmails;

	public function __construct(?MailerInterface $mailer = null)
	{
		$this->mailer = $mailer ?? Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
		$this->emConfig = ComponentHelper::getParams('com_emundus');

		if(!class_exists('EmundusModelEmails'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/emails.php';
		}
		$this->mEmails = new \EmundusModelEmails();
	}

	public function correctEmail($email): bool
	{
		if (empty($email)) {
			return false;
		}
		else {
			$validator = new EmailValidator();
			$multipleValidations = new MultipleValidationWithAnd([
				new RFCValidation(),
				new DNSCheckValidation()
			]);
			return $validator->isValid($email, $multipleValidations);
		}
	}

	public function getCustomHeader(): string
	{
		$result = '';

		$custom_email_tag = $this->emConfig->get('email_custom_tag', null);

		if (!empty($custom_email_tag)) {
			$custom_email_tag = explode(',', $custom_email_tag);

			$result = $custom_email_tag[0] . ':' . $custom_email_tag[1];
		}

		return $result;
	}

	static function getLogo($only_filename = false, $training = null, $file_path = false): string
	{
		$logo = 'images/custom/logo_custom.png';

		if (!empty($training) && file_exists(JPATH_ROOT . '/images/custom/' . $training . '.png')) {
			$logo = 'images/custom/' . $training . '.png';
		}
		else if(file_exists(JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/logo.yaml')) {
			$yaml = Yaml::parse(file_get_contents(JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/logo.yaml'));

			if (!empty($yaml)) {
				$logo_gantry = $yaml['image'];

				if (!empty($logo_gantry)) {
					$logo = str_replace('gantry-media:/', 'images', $logo_gantry);

					if (!file_exists(JPATH_ROOT . '/' . $logo)) {
						$logo = 'images/custom/logo_custom.png';
					}
				}
			}
		}

		if (!file_exists($logo)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('id,content')
				->from($db->quoteName('#__modules'))
				->where($db->quoteName('module') . ' = ' . $db->quote('mod_custom'))
				->where($db->quoteName('title') . ' LIKE ' . $db->quote('Logo'));
			$db->setQuery($query);
			$logo_module = $db->loadObject();

			preg_match('#src="(.*?)"#i', $logo_module->content, $tab);
			$pattern = "/^(?:ftp|https?|feed)?:?\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
        (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:
        (?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?]
        (?:[\w#!:\.\?\+\|=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?$/xi";

			if (preg_match($pattern, $tab[1])) {
				$logo = parse_url($tab[1], PHP_URL_PATH);
			}
		}

		if($only_filename)
		{
			return basename($logo);
		}

		// Check if we are on http or https
		if (Factory::getApplication()->isClient('cli')) {
			$base_url = Factory::getApplication()->getConfig()->get('live_site');
		}
		elseif ($file_path)
		{
			$base_url = JPATH_BASE . '/';
		}
		else
		{
			$base_url = Uri::base();
		}

		if(!file_exists($logo)) {
			$logo = 'images/custom/logo.png';
		}

		if (!empty($base_url) && !str_ends_with($base_url, '/') && !str_starts_with($logo, '/')) {
			$base_url .= '/';
		}

		return $base_url . $logo;
	}

	private function preparePost($to, ?string $fnum = null): array
	{
		$config = Factory::getApplication()->getConfig();

		$default_post = [
			'SITE_URL'   => Uri::base(),
			'SITE_NAME' => $config->get('sitename'),
			'USER_EMAIL' => $to,
			'LOGO' => self::getLogo()
		];
		if(!empty($fnum)) {
			$default_post['FNUM'] = $fnum;
		}

		if (!empty($post)) {
			$post = array_merge($default_post, $post);
		} else {
			$post = $default_post;
		}

		return $post;
	}

	private function getBaseTemplate(): ?object
	{
		$template = null;

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		try
		{
			$query->select('*')
				->from($db->quoteName('#__emundus_email_templates'))
				->where($db->quoteName('lbl') . ' = ' . $db->quote('default'));
			$db->setQuery($query);
			$template = $db->loadObject();
		}
		catch (\Exception $e)
		{
			Log::add('Error fetching email template: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			return null;
		}

		return $template;
	}

	public function sendEmailWithTemplate()
	{

	}

	public function sendEmailWithoutTemplate(string $to, string $subject, string $body, ?array $post = null, ?int $user_id = null, ?array $attachments = [], ?string $fnum = null, array $emails_cc = [], ?int $user_id_from = null): void
	{
		if(empty($to) || empty($body))
		{
			throw new \InvalidArgumentException('To and Body fields are required.');
		}

		$emundusUserRepository = new EmundusUserRepository();
		$config = Factory::getApplication()->getConfig();

		if (empty($user_id_from)) {
			$user = Factory::getApplication()->getIdentity();
			if (!empty($user->id)) {
				$user_id_from = $user->id;
			} else {
				$automated_task_user = $this->emConfig->get('automated_task_user', 1);
				$user_id_from = $automated_task_user;
			}
		}

		// Get default mail sender info
		$mail_from = $config->get('mailfrom');
		$mail_from_name = $config->get('fromname');
		$reply_to = $config->get('replyto', $mail_from);
		$reply_to_name = $config->get('replytoname', $mail_from_name);

		$post = $this->preparePost($to, $fnum);

		$user_id_to = !empty($user_id) ? $user_id : null;
		if ($user_id_to === null) {
			$user_id = $emundusUserRepository->getIdByEmail($to);
		}

		if ($user_id != null) {
			$emundusUser = $emundusUserRepository->getItemByField('user_id', $user_id, true);
			assert($emundusUser instanceof EmundusUserEntity);
			if (!empty($emundusUser->getEmailCc())) {
				if (preg_match('/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-z\-0-9]+\.)+[a-z]{2,}))$/', $emundusUser->getEmailCc()) === 1) {
					$emails_cc[] = $emundusUser->getEmailCc();
				}
			}

			$password = !empty($post['PASSWORD']) ? $post['PASSWORD'] : "";
			$post_tags = $this->mEmails->setTags($user_id, $post, $fnum, $password, $mail_from_name.$reply_to.$subject.$body);

			if(!empty($post_tags))
			{
				// TODO: override $post_tags replacements by $post tags if a value is set for the pattern

				$mail_from_name = preg_replace($post_tags['patterns'], $post_tags['replacements'], $mail_from_name);
				$reply_to      = preg_replace($post_tags['patterns'], $post_tags['replacements'], $reply_to);
				$subject        = preg_replace($post_tags['patterns'], $post_tags['replacements'], $subject);
				$body           = preg_replace($post_tags['patterns'], $post_tags['replacements'], $body);
			}
		}

		$keys = [];
		foreach (array_keys($post) as $key) {
			$keys[] = '/\['.$key.'\]/';
		}
		$subject = preg_replace($keys, $post, $subject);
		$body = preg_replace($keys, $post, $body);

		if($fnum != null) {
			$body = $this->mEmails->setTagsFabrik($body, array($fnum));
		}

		$baseTemplate = $this->getBaseTemplate();
		if(!empty($baseTemplate))
		{
			$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/"], [$subject, $body], $baseTemplate->Template);

			if($user_id != null && !empty($post_tags)) {
				$body = preg_replace($post_tags['patterns'], $post_tags['replacements'], $body);
			}
			$body = preg_replace($keys, $post, $body);
		}

		if(!$this->sendEmail($to, $subject, $body, $mail_from, $mail_from_name, $attachments, $emails_cc, [], $reply_to, $reply_to_name))
		{
			throw new \RuntimeException('Failed to send email to ' . $to);
		}

		if (!empty($user_id_to)) {
			$log = [
				'user_id_from'  => $user_id_from,
				'user_id_to'    => $user_id_to,
				'subject'       => $subject,
				'message'       => $body,
				'type'          => 1,
				'email_to'      => $to
			];
			$this->mEmails->logEmail($log);
		}
	}

	public function sendEmail(string $to, string $subject, string $body, string $from, string $fromName, array $attachments = [], array $cc= [], array $bcc = [], string $replyTo = '', string $replyToName = '', bool $isHtml = true, string $encoding = 'base64'): bool
	{
		if(empty($to) || empty($body))
		{
			throw new \InvalidArgumentException('To and Body fields are required.');
		}

		if(!$this->correctEmail($to))
		{
			throw new \InvalidArgumentException('Invalid email address: ' . $to);
		}

		$toAttach = array();
		if (!empty($attachments) && is_array($attachments)) {
			$toAttach = $attachments;
		}

		$body_raw = strip_tags($body);

		$this->mailer->setSender([$from, $fromName]);
		if(!empty($replyTo) && $replyTo != $from)
		{
			$this->mailer->addReplyTo($replyTo, $replyToName);
		}
		$this->mailer->addRecipient($to);
		$this->mailer->setSubject($subject);
		$this->mailer->isHTML($isHtml);
		$this->mailer->Encoding = $encoding;
		$this->mailer->setBody($body);
		$this->mailer->AltBody = $body_raw;

		if (!empty($cc)) {
			$this->mailer->addCC($cc);
		}
		if (!empty($toAttach)) {
			$this->mailer->addAttachment($toAttach);
		}

		$custom_email_tag = $this->getCustomHeader();
		if (!empty($custom_email_tag))
		{
			$this->mailer->addCustomHeader($custom_email_tag);
		}

		$send = $this->mailer->send();
		if(!$send)
		{
			throw new \RuntimeException('Failed to send email to ' . $to . '. Error: ' . $this->mailer->ErrorInfo);
		}

		return $send;
	}
}