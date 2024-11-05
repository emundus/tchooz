<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_version
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$ressources = $params->get('ressources');

?>
<style>
    .emundus_link[target=_blank]:before {
        content: unset;
    }
</style>
<div style="padding: 16px;display: flex;flex-direction: column;gap: 6px">
    <?php foreach ($ressources as $ressource) : ?>
        <a style="display: flex;gap: 4px;align-items: center; font-size: 16px" class="emundus_link" href="<?php echo $ressource->link; ?>" target="_blank">
            <img src="modules/mod_emundus_notes/assets/<?php echo $ressource->type; ?>.webp" alt="<?php echo $ressource->name; ?>" style="width: 16px; height: 16px;" />
            <p><?php echo $ressource->name; ?></p>
        </a>
    <?php endforeach; ?>
    <hr />
    <div>
        <?php echo $params->get('note'); ?>
    </div>
</div>
