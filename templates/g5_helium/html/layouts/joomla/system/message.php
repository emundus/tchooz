<?php

/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @copyright Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/**
 * Joomla 3 version of the system messages.
 */

$msgList = $displayData['msgList'];

?>
<div id="system-message-container">
	<?php if (is_array($msgList) && !empty($msgList)) : ?>
        <div id="system-message" class="tw-flex tw-flex-col tw-mt-4 tw-gap-2">
			<?php foreach ($msgList as $type => $msgs) : ?>
				<?php
				switch ($type) {
					case 'error':
						$icon = 'cancel';
						break;
					case 'warning':
						$icon = 'report_problem';
						break;
					case 'success':
						$icon = 'check_circle';
						break;
					default:
						$type = 'info';
						$icon = 'info';
						break;
				}
				?>
                <div class="tw-shadow alert alert-<?php echo $type; ?>">
					<?php if (!empty($msgs)) : ?>
                        <span class="material-symbols-outlined tw-mr-3"><?php echo $icon ?></span>
                        <div>
							<?php foreach ($msgs as $msg) : ?>
                                <p id="alert-message-text"><?php echo $msg; ?></p>
							<?php endforeach; ?>
                        </div>
                        <span class="material-symbols-outlined tw-absolute tw-top-[3px] tw-right-[5px] !tw-text-base tw-cursor-pointer" onclick="closeAlert('<?php echo $type; ?>')">close</span>
					<?php endif; ?>
                </div>
			<?php endforeach; ?>
        </div>
	<?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var messages = document.querySelectorAll('#system-message .alert');
        setTimeout(() => {
            messages.forEach(function(message) {
                message.style.opacity = 1;
                message.style.bottom = '10px'
            });
        },450)

        setTimeout(function() {
            messages.forEach(function(message) {
                message.style.opacity = 0;
                message.style.bottom = '-100px'
            });
        }, 50000000);
    });

    closeAlert = function(type) {
        var messages = document.querySelectorAll('#system-message .alert');
        messages.forEach(function(message) {
            if (message.classList.contains('alert-' + type)) {
                message.style.opacity = 0;
                message.style.bottom = '-100px'
                setTimeout(() => {
                    message.remove();
                }, 300)
            }
        });
    }
</script>
