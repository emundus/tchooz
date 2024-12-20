<?php
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;


//JHTML::stylesheet('media/com_emundus/css/emundus.css' );
$app = Factory::getApplication();
$document = Factory::getDocument();
$document->addStyleSheet("media/com_emundus/css/emundus_export_select_columns.css");
$eMConfig     = JComponentHelper::getParams('com_emundus');
$current_user = $app->getIdentity();
$view         = $app->input->get('v', null, 'GET', 'none', 0);
//$view = Factory::getApplication()->input->get('view', null, 'GET', 'none',0);
$comments = $app->input->get('comments', null, 'POST', 'none', 0);
$itemid   = $app->input->get('Itemid', null, 'GET', 'none', 0);
// Starting a session.
$session    = $app->getSession();
$s_elements = $session->get('s_elements');
$comments   = $session->get('comments');

$allowed_groups = EmundusHelperAccess::getUserFabrikGroups($current_user->id);

$fabrik_elements = array('jos_emundus_users___email', 'jos_emundus_users___firstname', 'jos_emundus_users___lastname');

if (!empty($s_elements)) {
	foreach ($s_elements as $s) {
		$t              = explode('.', $s);
		$table_name[]   = $t[0];
		$element_name[] = $t[1];
	}
}
?>

<?php if (count($this->elements) > 0) : ?>
    <!--        <div class="em-program-title em-mb-16">
            <h1><?= $this->program; ?></h1>
        </div>-->
    <div id="emundus_elements">
	<?php
	$tbl_tmp = '';
	$grp_tmp = '';
	?>

	<?php foreach ($this->elements as $t) : ?>

		<?php
		if (!empty($t->table_join)) {
			$fabrik_elements[] = $t->table_join . '___' . $t->element_name;
		}
		else {
			$fabrik_elements[] = $t->fabrik_element;
		}

		if ($tbl_tmp == '') :?>
            <div class="panel panel-primary excel" id="emundus_table_<?= $t->table_id; ?>">
            <div class="panel-heading">
                <legend>
                    <label for="emundus_checkall_tbl_<?= $t->table_id; ?>"><?= Text::_($t->form_label); ?></label>
                </legend>
            </div>

            <div class="panel-body">
            <div class="panel panel-info excel" id="emundus_grp_<?= $t->group_id; ?>">
            <div class="panel-heading">
                <legend>
                    <label for="emundus_checkall_grp_'<?= $t->group_id; ?>"><?= Text::_($t->group_label); ?></label>
                </legend>
            </div>

            <div class="panel-body">
            <div class="em-element-title em-element-main-title em-mb-16">
                <div class="em-element-title-id em-element-main-title-id">
                    <b><?= Text::_('ID'); ?></b>
                </div>
                <div class="em-element-title-label em-element-main-title-label">
                    <b><?= Text::_('LABEL'); ?></b>
                </div>
            </div>
		<?php elseif ($t->table_id != $tbl_tmp && $tbl_tmp != '') : ?>
            </div>
            </div>
            </div>
            </div>
            </div>
            <div class="panel panel-primary excel" id="emundus_table_<?= $t->table_id; ?>">
            <div class="panel-heading">
                <legend>
                    <label for="emundus_checkall_tbl_<?= $t->table_id; ?>"><?= Text::_($t->form_label); ?></label>
                </legend>
            </div>

            <div class="panel-body">
            <div class="panel panel-info excel" id="emundus_grp_<?= $t->group_id; ?>'">
            <div class="panel-heading">
                <legend>
                    <label for="emundus_checkall_grp_<?= $t->group_id; ?>"><?= Text::_($t->group_label); ?></label>
                </legend>
            </div>
            <div class="panel-body">
            <div class="em-element-title">
                <div class="em-element-title-id" onclick="copyid();" data-toggle="tooltip" data-placement="left"
                     title="<?= Text::_('COM_EMUNDUS_EMTAGS_SELECT_TO_COPY'); ?>">
                    <p></p>
                </div>
                <div class="em-element-title-label">
                    <p></p>
                </div>
            </div>

		<?php elseif ($t->group_id != $grp_tmp && $grp_tmp != '') : ?>
            </div>
            </div>

            <div class="panel panel-info excel" id="emundus_grp_<?= $t->group_id; ?>">
            <div class="panel-heading">
                <legend>
                    <label for="emundus_checkall_grp_<?= $t->group_id; ?>"><?= Text::_($t->group_label); ?></label>
                </legend>
            </div>
            <div class="panel-body">
            <div class="em-element-title">
                <div class="em-element-title-id" onclick="copyid();" data-toggle="tooltip" data-placement="left"
                     title="<?= Text::_('COM_EMUNDUS_EMTAGS_SELECT_TO_COPY'); ?>">
                    <p></p>
                </div>
                <div class="em-element-title-label">
                    <p></p>
                </div>
            </div>
		<?php endif; ?>

        <div class="em-element">
            <div class="em-element-id" onclick="copyid('<?= '${' . $t->id . '}'; ?>');" data-toggle="tooltip"
                 data-placement="left" title="<?= Text::_('COM_EMUNDUS_EMTAGS_SELECT_TO_COPY'); ?>">
				<?= '${' . $t->id . '}'; ?>
            </div>
            <div class="em-element-label">
				<?= Text::_($t->element_label); ?>
            </div>
        </div>
        <br>
		<?php
		$tbl_tmp = $t->table_id;
		$grp_tmp = $t->group_id;
		?>
	<?php endforeach; ?>
    </div>
    </div>
    </div>
    </div>
    </div>
<?php else: ?>
    <div class="em-mb-16"><?= Text::_('COM_EMUNDUS_FORM_NO_FORM_DEFINED'); ?></div>
<?php endif; ?>