<?php
/**
 * @package     ${NAMESPACE}
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */
use Joomla\CMS\Language\Text;

Text::script('COM_EMUNDUS_APPLICATION_SHARE_EMAILS');

?>

<div>
    <div>
        <label for="collab_emails" class="font-bold"><?php echo Text::_('COM_EMUNDUS_APPLICATION_SHARE_EMAILS') ?></label>
        <input type="text" name="collab_emails" id="collab_emails" class="tw-mt-2" />
    </div>

    <div>
        <label class="font-bold">Droits</label>
        <div class="tw-mt-2">
            <input type="checkbox" name="rights" id="read" value="read" checked />
            <label for="read">Lire</label>
        </div>

        <div>
            <input type="checkbox" name="rights" id="update" value="update" />
            <label for="update">Modifier</label>
        </div>

        <div>
            <input type="checkbox" name="rights" id="view_history" value="view_history" />
            <label for="view_history">Visualiser l'historique</label>
        </div>

        <div>
            <input type="checkbox" name="rights" id="view_others" value="view_others" />
            <label for="view_others">Visualiser les autres collaborateurs</label>
        </div>
    </div>

    <div>
        <h3>Demandes</h3>
    </div>
</div>
