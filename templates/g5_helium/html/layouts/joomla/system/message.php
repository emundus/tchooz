<?php

/**
 * @package         Joomla.Site
 * @subpackage      Layout
 *
 * @copyright   (C) 2014 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/* @var $displayData array */
$msgList   = $displayData['msgList'];
$document  = Factory::getDocument();
$msgOutput = '';
$alert     = [
	CMSApplication::MSG_EMERGENCY => 'danger',
	CMSApplication::MSG_ALERT     => 'danger',
	CMSApplication::MSG_CRITICAL  => 'danger',
	CMSApplication::MSG_ERROR     => 'danger',
	CMSApplication::MSG_WARNING   => 'warning',
	CMSApplication::MSG_NOTICE    => 'info',
	CMSApplication::MSG_INFO      => 'info',
	CMSApplication::MSG_DEBUG     => 'info',
	'message'                     => 'success'
];

// Load JavaScript message titles
Text::script('ERROR');
Text::script('MESSAGE');
Text::script('NOTICE');
Text::script('WARNING');

// Load other Javascript message strings
Text::script('JCLOSE');
Text::script('JOK');
Text::script('JOPEN');

// Alerts progressive enhancement
$document->getWebAssetManager()
	->useStyle('webcomponent.joomla-alert')
	->useScript('messages');

if (is_array($msgList) && !empty($msgList)) { ?>
    <div id="system-message-container" aria-live="polite">
        <div id="system-message">
			<?php
			$messages = [];

			foreach ($msgList as $type => $msgs) {
				switch ($type) {
					case 'error':
						$icon = 'cancel';
						break;
					case 'warning':
						$icon = 'error';
						break;
					case 'success':
						$icon = 'check_circle';
						break;
					default:
						$icon = 'info';
						break;
				}
				?>
                <div class="alert alert-<?php echo $type; ?>">

					<?php if (!empty($msgs)) : ?>
                        <span class="material-icons"><?php echo $icon ?></span>
                        <div>
							<?php foreach ($msgs as $msg) : ?>
                                <p><?php echo $msg; ?></p>
							<?php endforeach; ?>
                        </div>
					<?php endif; ?>
                </div>

				<?php
				// JS loaded messages
				$messages[] = [$alert[$type] ?? $type => $msgs];
				// Noscript fallback
				if (!empty($msgs)) {
					$msgOutput .= '<div class="alert alert-' . ($alert[$type] ?? $type) . '">';
					foreach ($msgs as $msg) :
						$msgOutput .= $msg;
					endforeach;
					$msgOutput .= '</div>';
				}
			}

			if ($msgOutput !== '') {
				$msgOutput = '<noscript>' . $msgOutput . '</noscript>';
			}
			?>
        </div>
    </div>
	<?php
}
?>
