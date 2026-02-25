<?php

/* ======================================================
 # Microsoft/Outlook 365 Mail Connect for Joomla! - v1.0.8 (pro version)
 # -------------------------------------------------------
 # For Joomla! CMS (v4.x)
 # Author: Web357 (Yiannis Christodoulou)
 # Copyright: (©) 2014-2024 Web357. All rights reserved.
 # License: GNU/GPLv3, https://www.gnu.org/licenses/gpl-3.0.html   
 # Website: https://www.web357.com
 # Demo: 
 # Support: support@web357.com
 # Last modified: Tuesday 03 February 2026, 10:20:16 AM
 ========================================================= */
declare(strict_types=1);

namespace Web357\Plugin\System\Microsoftoutlook365mailconnect\Service\MicrosoftOutlook365MailConnectMailer;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailerInterface;
use Throwable;
use Web357\Plugin\System\Microsoftoutlook365mailconnect\Helper\MicrosoftOutlookApplicationHelper;

if (class_exists('\CustomMailer')) {
    class_alias('\CustomMailer', '\Web357\Plugin\System\Microsoftoutlook365mailconnect\Service\MicrosoftOutlook365MailConnectMailer\Web357Mailer');
} else {
    class_alias('\Joomla\CMS\Mail\Mail', '\Web357\Plugin\System\Microsoftoutlook365mailconnect\Service\MicrosoftOutlook365MailConnectMailer\Web357Mailer');
}

class MicrosoftOutlook365MailConnectMailer extends Web357Mailer
{

    const MICROSOFT_FALLBACK_MAILER_SERVICE = 'web357MicrosoftOutlook365FallbackMailer';

    public $Mailer = 'web357MicrosoftOutlook365MailConnect';

    protected function toMicrosoftGraphMessageArray(): array
    {
        if ($this->ContentType === static::CONTENT_TYPE_PLAINTEXT) {
            $content = $this->AltBody ?: $this->Body;
            $contentType = 'Text';
        } else {
            $content = $this->Body;
            $contentType = 'HTML';
        }

        $message = [
            'subject' => $this->Subject,
            'body' => [
                'contentType' => $contentType,
                'content' => $content,
            ],
            'toRecipients' => array_values(array_map(function ($addr) {
                $emailAddress = ['address' => $addr[0]];
                if (!empty($addr[1])) {
                    $emailAddress['name'] = $addr[1];
                }
                return ['emailAddress' => $emailAddress];
            }, array_filter($this->getToAddresses(), function ($addr) {
                return !empty($addr[0]) && filter_var($addr[0], FILTER_VALIDATE_EMAIL);
            }))),
        ];

        // Only include ccRecipients if there are actual CC addresses
        $ccAddresses = array_filter($this->getCcAddresses(), function ($addr) {
            return !empty($addr[0]) && filter_var($addr[0], FILTER_VALIDATE_EMAIL);
        });
        if (!empty($ccAddresses)) {
            $message['ccRecipients'] = array_values(array_map(function ($addr) {
                $emailAddress = ['address' => $addr[0]];
                if (!empty($addr[1])) {
                    $emailAddress['name'] = $addr[1];
                }
                return ['emailAddress' => $emailAddress];
            }, $ccAddresses));
        }

        // Only include bccRecipients if there are actual BCC addresses  
        $bccAddresses = array_filter($this->getBccAddresses(), function ($addr) {
            return !empty($addr[0]) && filter_var($addr[0], FILTER_VALIDATE_EMAIL);
        });
        if (!empty($bccAddresses)) {
            $message['bccRecipients'] = array_values(array_map(function ($addr) {
                $emailAddress = ['address' => $addr[0]];
                if (!empty($addr[1])) {
                    $emailAddress['name'] = $addr[1];
                }
                return ['emailAddress' => $emailAddress];
            }, $bccAddresses));
        }

        // Only include replyTo if there are actual reply-to addresses
        $replyToAddresses = array_filter($this->getReplyToAddresses(), function ($addr) {
            return !empty($addr[0]) && filter_var($addr[0], FILTER_VALIDATE_EMAIL);
        });
        if (!empty($replyToAddresses)) {
            $message['replyTo'] = array_values(array_map(function ($addr) {
                $emailAddress = ['address' => $addr[0]];
                if (!empty($addr[1])) {
                    $emailAddress['name'] = $addr[1];
                }
                return ['emailAddress' => $emailAddress];
            }, $replyToAddresses));
        }

        // Optional "from" override (only needed for shared mailbox or delegation)
        $oauthFromEmail = MicrosoftOutlookApplicationHelper::getInstance()->getOauthFromEmail();
        if (!empty($oauthFromEmail)) {
            $message['from'] = [
                'emailAddress' => [
                    'address' => $oauthFromEmail,
                ],
            ];
        }

        // Add attachments - only include attachments array if there are actual attachments
        $attachments = [];
        foreach ($this->getAttachments() as $att) {
            $filePathOrContent = $att[0];
            $filename = $att[2];
            $contentType = $att[4] ?? 'application/octet-stream';
            $isInline = $att[6] === 'inline';
            $contentId = $att[7] ?? null;
            $isStringAttachment = $att[5];

            // Get content
            if ($isStringAttachment) {
                $content = base64_encode($filePathOrContent);
            } elseif (file_exists($filePathOrContent)) {
                $content = base64_encode(file_get_contents($filePathOrContent));
            } else {
                continue; // Skip invalid attachment
            }

            $attachment = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $filename,
                'contentType' => $contentType,
                'contentBytes' => $content,
            ];

            if ($isInline) {
                $attachment['isInline'] = true;
                if ($contentId) {
                    $attachment['contentId'] = $contentId;
                }
            }

            $attachments[] = $attachment;
        }

        // Only include attachments if there are actual attachments
        if (!empty($attachments)) {
            $message['attachments'] = $attachments;
        }

        $payload = [
            'message' => $message,
            'saveToSentItems' => true,
        ];

        Log::add('Microsoft Graph API Payload: ' . json_encode($payload, JSON_PRETTY_PRINT), Log::DEBUG, 'microsoftoutlook365mailconnect');
        return $payload;
    }

    public function Send()
    {
        // Ensure the From address is set correctly (JMail Log compatibility)
        $microsoftOutlookApplicationHelper = MicrosoftOutlookApplicationHelper::getInstance();
        $this->From = $microsoftOutlookApplicationHelper->getOauthFromEmail() ?: $microsoftOutlookApplicationHelper->getConfiguredEmail();
        return parent::Send();
    }

    public function web357MicrosoftOutlook365MailConnectSend($header, $body)
    {
        try {
            $microsoftOutlookApplicationHelper = MicrosoftOutlookApplicationHelper::getInstance();
            return $microsoftOutlookApplicationHelper->sendEmail($this->toMicrosoftGraphMessageArray());
        } catch (Throwable $e) {
            if ($e->getCode() === 400) {
                $this->notifyAdministrator($e);
                return $this->sendWithFallback();;
            }
            Log::add($e->getMessage(), Log::ERROR, 'microsoftoutlook365mailconnect');
            $this->ErrorInfo = $e->getMessage();
            return false;
        }
    }

    /**
     * Notifies the administrator with an alert in case of email sending failure.
     * The notification is sent via backend message and email, utilizing Joomla's mailer.
     *
     * @param Throwable $e An instance of the exception that includes details about the failure.
     * @return void
     */
    protected function notifyAdministrator(Throwable $e)
    {
        // Notify administrator via backend message
        try {
            $app = Factory::getApplication();
            $app->enqueueMessage(
                'Microsoft/Outlook 365 Mail Connect: Failed to send email (HTTP 400). Please verify plugin configuration and re-authorize the app if needed. Error: ' . $e->getMessage(),
                'error'
            );
        } catch (Throwable $t) {
            // Ignore enqueue errors
        }

        try {
            $config = Factory::getApplication()->getConfig();
            $adminEmail = (string)$config->get('mailfrom');

            if ($adminEmail && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                $subject = '[ALERT] Microsoft/Outlook 365 Mail Connect: Email sending failed (HTTP 400)';
                $body = "Hello Administrator,\n\n" .
                    "An attempt to send an email via Microsoft Graph API failed with HTTP 400.\n\n" .
                    "Subject: " . (string)$this->Subject . "\n" .
                    "To: " . implode(', ', array_map(function ($addr) {
                        return $addr[0];
                    }, (array)$this->getToAddresses())) . "\n" .
                    "Date: " . date('Y-m-d H:i:s') . "\n\n" .
                    "Error message: " . $e->getMessage() . "\n\n" .
                    "Please review the plugin configuration and re-authorize the connection if necessary.";
                /** @var MailerInterface $mailer */
                $mailer = Factory::getContainer()->get(static::MICROSOFT_FALLBACK_MAILER_SERVICE)->createMailer();
                $mailer->addRecipient($adminEmail);
                $mailer->setSubject($subject);
                $mailer->isHtml(false);
                $mailer->setBody($body);
                $mailer->Send();
            }
        } catch (Throwable $t) {
        }
    }

    /**
     * Attempts to send an email using a fallback mailer service. Copies all email data including recipients,
     * subject, body, and attachments from the primary email details. If the fallback mailer fails, logs the error.
     *
     * @return bool Returns true if the email was successfully sent using the fallback mailer, otherwise false.
     */
    protected function sendWithFallback()
    {
        try {
            /** @var \Joomla\CMS\Mail\Mail $fallbackMailer */
            $fallbackMailer = Factory::getContainer()->get(static::MICROSOFT_FALLBACK_MAILER_SERVICE)->createMailer();

            // Copy recipients
            foreach ($this->getToAddresses() as $addr) {
                $fallbackMailer->addRecipient($addr[0], $addr[1] ?? '');
            }
            foreach ($this->getCcAddresses() as $addr) {
                $fallbackMailer->addCC($addr[0], $addr[1] ?? '');
            }
            foreach ($this->getBccAddresses() as $addr) {
                $fallbackMailer->addBCC($addr[0], $addr[1] ?? '');
            }
            foreach ($this->getReplyToAddresses() as $addr) {
                $fallbackMailer->addReplyTo($addr[0], $addr[1] ?? '');
            }

            // Copy subject and body
            $fallbackMailer->setSubject($this->Subject);
            $fallbackMailer->setBody($this->Body);
            $fallbackMailer->isHtml($this->ContentType !== static::CONTENT_TYPE_PLAINTEXT);

            // Copy attachments
            foreach ($this->getAttachments() as $att) {
                if ($att[5] === true) {
                    $fallbackMailer->addStringAttachment($att[0], $att[2], 'base64', $att[4] ?? 'application/octet-stream');
                } else {
                    $fallbackMailer->addAttachment($att[0], $att[2], 'base64', $att[4] ?? 'application/octet-stream');
                }
            }

            // Send fallback mail
            return $fallbackMailer->Send();
        } catch (Throwable $fallbackException) {
            Log::add('Fallback mailer failed: ' . $fallbackException->getMessage(), Log::ERROR, 'microsoftoutlook365mailconnect');
            return false;
        }
    }

}
