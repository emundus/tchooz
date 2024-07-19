<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

$data = $displayData;
$wa   = Factory::getDocument()->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('plg_editors_tiptap');
$wa->registerAndUseStyle('plg_editors_tiptap', 'media/plg_editors_tiptap/app.css');
?>
<textarea
    name="<?php echo $data->name; ?>"
    id="<?php echo $data->id; ?>"
    style="display: none"
    class="<?php echo empty($data->class) ? 'mce_editable' : $data->class; ?>"
    <?php echo $data->readonly ? ' readonly disabled' : ''; ?>
>
    <?php echo $data->content; ?>
</textarea>

<div id="<?php echo $data->id; ?>-editor"
     class="tiptap-editor"
     textareaId="<?php echo $data->id; ?>"
     enableSuggestions="<?php echo $data->enable_suggestions ? 'true' : 'false'; ?>"
     suggestions="<?php echo htmlspecialchars(json_encode($data->suggestions)); ?>"
     plugins="<?php echo htmlspecialchars(json_encode($data->plugins)); ?>"
>

</div>

<script type="module" src="media/plg_editors_tiptap/app.js?<?php echo rand() ?>"></script>
