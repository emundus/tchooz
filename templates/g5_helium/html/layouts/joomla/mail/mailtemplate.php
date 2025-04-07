<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

// Check if we have all the data
if (!array_key_exists('mail', $displayData)) {
    return;
}

// Setting up for display
$mailBody = $displayData['mail'];

if (!$mailBody) {
    return;
}

$extraData = [];

if (array_key_exists('extra', $displayData)) {
    $extraData = $displayData['extra'];
}

require_once JPATH_SITE.'/components/com_emundus/helpers/emails.php';
$logo_path = \EmundusHelperEmails::getLogo();

if(!empty($logo_path)) {
	$extraData['logo'] = $logo_path;
}

$siteUrl = Uri::root(false);

?>
<!DOCTYPE html>
<html lang="<?php echo (isset($extraData['lang'])) ?  $extraData['lang'] : 'en' ?>" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="x-apple-disable-message-reformatting">
        <!--[if !mso]><!-->
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <!--<![endif]-->
        <title></title>
        <!--[if mso]>
            <style>
                table {border-collapse:collapse;border-spacing:0;border:none;margin:0;}
                div, td {padding:0;}
                div {margin:0 !important;}
            </style>
            <noscript>
                <xml>
                <o:OfficeDocumentSettings>
                    <o:PixelsPerInch>96</o:PixelsPerInch>
                </o:OfficeDocumentSettings>
                </xml>
            </noscript>
            <![endif]-->
        <style>
            html {height: 100%;}
            table, td, div, h1, p { font-family: Arial, sans-serif; }
            table {
                border-spacing: 0 !important;
                border-collapse: collapse !important;
                table-layout: fixed !important;
            }

            table table table {
                table-layout: auto;
            }

            img {
                -ms-interpolation-mode: bicubic;
            }

            *[x-apple-data-detectors] {
                color: inherit !important;
                text-decoration: none !important;
            }

            .x-gmail-data-detectors,
            .x-gmail-data-detectors *,
            .aBn {
                border-bottom: 0 !important;
                cursor: default !important;
            }

            .a6S {
                display: none !important;
                opacity: 0.01 !important;
            }

            img.g-img+div {
                display: none !important;
            }

            .button-link {
                text-decoration: none !important;
            }

            .bas-footer {
                display: flex;
                flex-direction: row;
                justify-content: space-between !important;
                align-items: center;
                margin-top: 2px !important;
                background: #e3e3e3;
                border-top: 1px solid #bbb;
                color: #2f4486 !important;
                left: 0px;
            }

            @media only screen and (min-device-width: 375px) and (max-device-width: 413px) {
                .email-container {
                    min-width: 375px !important;
                }
            }
            .button-td,
            .button-a {
                transition: all 100ms ease-in;
            }

            .button-td:hover,
            .button-a:hover {
                background: #EA4503 !important;
                border-color: #EA4503 !important;
                color: #FFFFFF;
            }

            @media screen and (max-width: 600px) {
                .email-container p {
                    font-size: 17px !important;
                    line-height: 22px !important;
                }
            }
        </style>
    </head>
    <body style="margin:0;padding:0;word-spacing:normal;background-color:#ffffff;height:100%;width:100%">
        <div role="article" aria-roledescription="email" style="text-size-adjust:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;background-color:#ffffff;height:100%;">
            <table role="presentation" style="max-width: 600px; margin: auto;" class="email-container">
                <tr>
                    <td align="center" style="vertical-align:baseline; padding:30px 0">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="100%">
                            <tr>
                                <td style="padding:40px 30px 0 30px;text-align:center;font-size:24px;font-weight:bold;background-color:#ffffff;">
                                <?php if (isset($extraData['logo']) || isset($extraData['siteName'])) : ?>
                                    <?php if (isset($extraData['logo'])) : ?>
                                        <a href="<?php echo $siteUrl; ?>"><img width="180" src="<?php echo htmlspecialchars($extraData['logo'], ENT_QUOTES);?>" alt="<?php echo (isset($extraData['siteName']) ? $extraData['siteName'] . ' ' : '');?>Logo" style="max-width:80%;height:auto;border:none;text-decoration:none;color:#ffffff;"></a>
                                    <?php else : ?>
                                    <h1 style="margin-top:0;margin-bottom:0;font-size:26px;line-height:32px;font-weight:bold;letter-spacing:-0.02em;color:#112855;">
                                        <?php echo $extraData['siteName']; ?>
                                    </h1>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>

                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="max-width: 600px;">
                            <tr>
                                <td style="padding: 40px 0; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;">
                                <?php endif; ?>
                                    <?php echo $mailBody; ?>
                                </td>
                            </tr>
                        </table>
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="100%">
                            <tr>
                                <td style="padding:0; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;"><br>
                                    <div class="bas-footer" style="background-color: white; padding-top:24px;">
                                        <div class="adresse" style="width: 50%;">
                                            <a class="university" style="margin-top: 0px;" href="<?php echo $siteUrl; ?>"><?php echo isset($extraData['siteName']) ? $extraData['siteName'] . ' ' : ''; ?></a>
                                        </div>
	                                    <?php if (isset($extraData['logo'])) : ?>
                                        <div class="logo" style="width:50%; text-align: end;">
                                            <a href="<?php echo $siteUrl; ?>">
                                                <img class="logo" src="<?php echo htmlspecialchars($extraData['logo'], ENT_QUOTES);?>" alt="<?php echo (isset($extraData['siteName']) ? $extraData['siteName'] . ' ' : '');?>" width="100">
                                                </a></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
