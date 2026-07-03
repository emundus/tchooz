<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\Path;
use Joomla\Plugin\Actionlog\Trackactions\Model\TrackActionsHelperModel;
use Joomla\Event\Event as JoomlaEvent;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Trackactions_logs\HtmlView $this */

HTMLHelper::_('bootstrap.tooltip', '[data-bs-toggle="tooltip"]');

$app       = Factory::getApplication();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

// El plugin Track Actions instalado puede ser una version antigua que no tenga esta clase todavia
$trackActionsHelperAvailable = class_exists(TrackActionsHelperModel::class);
if (!$trackActionsHelperAvailable) {
    $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_TRACKACTIONS_NEEDS_UPDATE'), 'warning');
}

// ---------------------------------------------------------------------
// Prefetch de usuarios (nombre + grupos) para evitar N+1
// ---------------------------------------------------------------------
/** @var array<int, array{name: string, groups: list<int>}> $userMap */
$userMap = [];

if (!empty($this->items)) {
    $userIds = [];

    foreach ($this->items as $it) {
        if (!empty($it->user_id)) {
            $userIds[(int) $it->user_id] = true;
        }
    }

    if ($userIds) {
        $userIds = array_keys($userIds);
        /** @var \Joomla\Database\DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $qb = $db->getQuery(true);

        $qb->clear()
           ->select($db->quoteName(['id', 'name']))
           ->from($db->quoteName('#__users'))
           ->where($db->quoteName('id') . ' IN (' . implode(',', array_map('intval', $userIds)) . ')');
        $db->setQuery($qb);
        /** @var array<string, array{name: string}> $rows */
        $rows = (array) $db->loadAssocList('id');

        foreach ($rows as $id => $row) {
            $userMap[(int) $id] = ['name' => (string) $row['name'], 'groups' => []];
        }

        $qb->clear()
           ->select($db->quoteName(['user_id', 'group_id']))
           ->from($db->quoteName('#__user_usergroup_map'))
           ->where($db->quoteName('user_id') . ' IN (' . implode(',', array_map('intval', $userIds)) . ')');
        $db->setQuery($qb);
        /** @var list<array{user_id: string, group_id: string}> $grpRows */
        $grpRows = (array) $db->loadAssocList();

        foreach ($grpRows as $r) {
            $uid = (int) $r['user_id'];
            if (isset($userMap[$uid])) {
                $userMap[$uid]['groups'][] = (int) $r['group_id'];
            }
        }
    }
}

// Badge por grupo (SU=8, Admin=7)
$renderUserBadge = static function (?int $userId) use ($userMap): string {
    if (!$userId || !isset($userMap[$userId])) {
        $title = Text::_('COM_SECURITYCHECKPRO_USER_DONT_EXISTS');
        return '<span class="badge bg-secondary-subtle text-body-secondary" title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">---</span>';
    }

    $groups  = $userMap[$userId]['groups'];
    $nameRaw = $userMap[$userId]['name'];
    $name    = htmlspecialchars($nameRaw, ENT_QUOTES, 'UTF-8');

    $cls = 'bg-info';
    if (in_array(8, $groups, true)) {
        $cls = 'bg-danger';
    } elseif (in_array(7, $groups, true)) {
        $cls = 'bg-warning text-dark';
    }

    return '<span class="badge ' . $cls . '" data-bs-toggle="tooltip" title="ID: ' . (int) $userId . '">' . $name . '</span>';
};
?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=trackactions_logs&view=trackactions_logs'); ?>"
      method="post"
      name="adminForm"
      id="adminForm">

    <?php
    $navFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php');
    if (is_file($navFile)) {
        require $navFile;
    }
    ?>

    <?php
    // ------------------------------------------------------------------
    // Info panel data: loggable extensions + integration plugin states
    // ------------------------------------------------------------------
    $dbInfo = Factory::getContainer()->get(DatabaseInterface::class);

    // Loggable extensions from SCP settings
    $qInfo = $dbInfo->getQuery(true)
        ->select($dbInfo->quoteName('storage_value'))
        ->from($dbInfo->quoteName('#__securitycheckpro_storage'))
        ->where($dbInfo->quoteName('storage_key') . ' = ' . $dbInfo->quote('pro_plugin'));
    $dbInfo->setQuery($qInfo);
    $proPluginJson    = $dbInfo->loadResult();
    $proPluginConfig  = $proPluginJson ? json_decode($proPluginJson, true) : null;
    $loggableExts     = [];
    if (is_array($proPluginConfig) && !empty($proPluginConfig['loggable_extensions'])) {
        // array_unique por si la BBDD ya tiene la extension duplicada (ver migracion 5.1.0.sql)
        $loggableExts = array_values(array_unique((array) $proPluginConfig['loggable_extensions']));
    }
    $isDefaultList = empty($loggableExts);
    if ($isDefaultList) {
        $loggableExts = explode(',', 'com_banners,com_cache,com_categories,com_config,com_contact,com_content,com_installer,com_media,com_menus,com_messages,com_modules,com_newsfeeds,com_plugins,com_redirect,com_tags,com_templates,com_users');
    }

    // Integration plugin states
    $integrationPlugins = [
        ['folder' => 'actionlog', 'element' => 'trackactions',                  'label' => 'Track Actions'],
        ['folder' => 'system',    'element' => 'trackactions_securitycheckpro',  'label' => 'SecurityCheck Pro'],
        ['folder' => 'system',    'element' => 'trackactions_controlcenter',     'label' => 'Control Center'],
        ['folder' => 'system',    'element' => 'trackactions_akeeba_backup',     'label' => 'Akeeba Backup'],
        ['folder' => 'system',    'element' => 'trackactions_acymailing',        'label' => 'AcyMailing'],
    ];
    $qPlg = $dbInfo->getQuery(true)
        ->select([$dbInfo->quoteName('element'), $dbInfo->quoteName('enabled')])
        ->from($dbInfo->quoteName('#__extensions'))
        ->where($dbInfo->quoteName('type') . ' = ' . $dbInfo->quote('plugin'))
        ->where($dbInfo->quoteName('element') . ' IN (' . implode(',', array_map(static fn($p) => $dbInfo->quote($p['element']), $integrationPlugins)) . ')');
    $dbInfo->setQuery($qPlg);
    $pluginRows = $dbInfo->loadAssocList('element') ?: [];
    ?>

    <!-- Action bar: título + leyenda de colores -->
    <div class="scp-actionbar">
        <div>
            <p class="scp-actionbar__title">
                <i class="fa fa-binoculars" aria-hidden="true"></i>
                <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_VIEW_TRACKACTIONS_LOGS_TEXT'); ?>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center small">
            <span class="text-muted"><?php echo Text::_('COM_SECURITYCHECKPRO_COLOR_CODE'); ?> — <?php echo Text::_('COM_SECURITYCHECKPRO_USER_BELONGS_TO'); ?>:</span>
            <span class="badge bg-warning text-dark"><?php echo Text::_('COM_SECURITYCHECKPRO_GROUP_ADMINISTRATORS'); ?></span>
            <span class="badge bg-danger"><?php echo Text::_('COM_SECURITYCHECKPRO_GROUP_SUPER_USERS'); ?></span>
            <span class="badge bg-info"><?php echo Text::_('COM_SECURITYCHECKPRO_GROUP_OTHERS'); ?></span>
        </div>
    </div>

    <!-- Info panel: loggable extensions + integration plugins -->
    <div class="card mb-2 border-0 bg-body-secondary small">
        <div class="card-body py-2">
            <div class="row g-2">
                <div class="col-12 col-xl-8">
                    <span class="fw-semibold text-muted me-1">
                        <i class="fa fa-list-ul fa-fw" aria-hidden="true"></i>
                        <?php echo Text::_('COM_SECURITYCHECKPRO_TRACKACTIONS_TRACKED'); ?>:
                    </span>
                    <?php if ($isDefaultList) : ?>
                        <span class="badge text-bg-secondary me-1"><?php echo Text::_('COM_SECURITYCHECKPRO_TRACKACTIONS_DEFAULT_LIST'); ?></span>
                    <?php endif; ?>
                    <?php foreach ($loggableExts as $ext) : ?>
                        <span class="badge text-bg-primary me-1"><?php echo htmlspecialchars(trim((string) $ext), ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="col-12 col-xl-4">
                    <span class="fw-semibold text-muted me-1">
                        <i class="fa fa-plug fa-fw" aria-hidden="true"></i>
                        <?php echo Text::_('COM_SECURITYCHECKPRO_TRACKACTIONS_INTEGRATIONS'); ?>:
                    </span>
                    <?php foreach ($integrationPlugins as $plg) : ?>
                        <?php
                        $row = $pluginRows[$plg['element']] ?? null;
                        $label = htmlspecialchars($plg['label'], ENT_QUOTES, 'UTF-8');
                        if ($row === null) :
                            $title = htmlspecialchars(Text::_('COM_SECURITYCHECKPRO_TRACKACTIONS_PLUGIN_NOT_INSTALLED'), ENT_QUOTES, 'UTF-8');
                        ?>
                            <span class="badge text-bg-secondary me-1" data-bs-toggle="tooltip" title="<?php echo $title; ?>"><?php echo $label; ?></span>
                        <?php elseif ((int) $row['enabled'] === 1) : ?>
                            <span class="badge text-bg-success me-1"><?php echo $label; ?></span>
                        <?php else : ?>
                            <span class="badge text-bg-danger me-1" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars(Text::_('COM_SECURITYCHECKPRO_TRACKACTIONS_PLUGIN_DISABLED'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $label; ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
        </div>

        <?php if (empty($this->items)) : ?>
            <div class="card-body pt-0">
                <div class="alert alert-info d-flex align-items-center mb-0" role="status">
                    <span class="icon-info-circle me-2" aria-hidden="true"></span>
                    <div><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
                </div>
            </div>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="logsList">
                    <thead>
                        <tr>
                            <th scope="col" class="text-center" style="width:1%">
                                <span class="visually-hidden"><?php echo Text::_('JGLOBAL_CHECK_ALL'); ?></span>
                                <?php echo HTMLHelper::_('grid.checkall'); ?>
                            </th>
                            <th scope="col">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_SECURITYCHECKPRO_MESSAGE', 'a.message', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" style="min-width:180px">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_SECURITYCHECKPRO_DATE', 'a.log_date', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_SECURITYCHECKPRO_EXTENSION', 'a.extension', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_SECURITYCHECKPRO_USER', 'a.user_id', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_SECURITYCHECKPRO_IP_ADDRESS', 'a.ip_address', $listDirn, $listOrder); ?>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($this->items as $i => $item) : ?>
                            <tr>
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?>
                                </td>

                                <td>
                                    <?php
                                    $message   = (string) $item->message;
                                    $extension = (string) $item->extension;

                                    $app->getLanguage()->load('plg_system_trackactions', JPATH_ADMINISTRATOR, null, false, true);
                                    $app->getLanguage()->load('plg_system_trackactions', JPATH_SITE, null, false, true);

                                    if ($message !== '' && preg_match('/^[A-Z0-9_]+$/', $message) === 1) {
                                        $message = Text::_($message);
                                    }

                                    $icono = '';

                                    try {
                                        $prepareEvent = new JoomlaEvent('onLogMessagePrepare', [
                                            'message'   => $message,
                                            'extension' => $extension,
                                            'icono'     => null,
                                        ]);
                                        $app->getDispatcher()->dispatch('onLogMessagePrepare', $prepareEvent);
                                        $message = (string) $prepareEvent->getArgument('message', $message);
                                        $ico     = $prepareEvent->getArgument('icono', null);
                                        if (is_string($ico) && $ico !== '') {
                                            $icono = $ico;
                                        }
                                    } catch (\Throwable $e) {
                                    }

                                    if ($icono !== '') {
                                        echo $icono . ' ';
                                    }

                                    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    $rawDate = (string) ($item->log_date ?? '');
                                    echo $rawDate !== ''
                                        ? HTMLHelper::_('date', $rawDate, Text::_('DATE_FORMAT_LC2'))
                                        : '<span class="text-body-secondary">—</span>';
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    $extBase = strtoupper(strtok((string) $item->extension, '.'));
                                    // @phpstan-ignore-next-line
                                    $extTxt = $trackActionsHelperAvailable ? TrackActionsHelperModel::translateExtensionName($extBase) : $extBase;
                                    echo htmlspecialchars((string) $extTxt, ENT_QUOTES, 'UTF-8');
                                    ?>
                                </td>

                                <td>
                                    <?php echo $renderUserBadge(isset($item->user_id) ? (int) $item->user_id : null); ?>
                                </td>

                                <td>
                                    <?php
                                    $ip = trim((string) $item->ip_address);
                                    echo $ip !== ''
                                        ? '<code>' . htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') . '</code>'
                                        : '<span class="text-body-secondary">—</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-transparent">
                <?php echo $this->pagination->getListFooter(); ?>
            </div>
        <?php endif; ?>
    </div>

    <input type="hidden" name="option"           value="com_securitycheckpro">
    <input type="hidden" name="task"             value="">
    <input type="hidden" name="boxchecked"       value="0">
    <input type="hidden" name="view"             value="trackactions_logs">
    <input type="hidden" name="filter_order"     value="<?php echo $listOrder; ?>">
    <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
