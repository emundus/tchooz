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

namespace Web357\Plugin\System\Microsoftoutlook365mailconnect\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Web357\Plugin\System\Microsoftoutlook365mailconnect\Helper\MicrosoftOutlookApplicationHelper;

class Msoutlook365authorizationurlField extends FormField
{
    protected $type = 'msoutlook365authorizationurl';

    protected function getInput()
    {
        // Generate the redirect URL
        $redirectUrl = MicrosoftOutlookApplicationHelper::getInstance()->getRedirectUrl();

        // Generate unique ID for the input
        $fieldId = $this->id . '_redirect_url';

        $html = [];
        $html[] = '<div class="input-group">';
        $html[] = '    <input type="text" class="form-control" id="' . $fieldId . '" value="' . $redirectUrl . '" readonly>';
        $html[] = '    <button class="btn btn-secondary" type="button" onclick="web357MicrosoftOutlookCopyToClipboard(\'' . $fieldId . '\', this)">';
        $html[] = '        <span class="icon-copy" aria-hidden="true"></span>';
        $html[] = '        <span class="visually-hidden">' . Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_COPY_URL') . '</span>';
        $html[] = '    </button>';
        $html[] = '</div>';

        return implode("\n", $html);
    }
}