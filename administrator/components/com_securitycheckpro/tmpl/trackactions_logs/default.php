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

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Trackactions_logs\HtmlView $this */

HTMLHelper::_('bootstrap.tooltip');

$app       = Factory::getApplication();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

// ---------------------------------------------------------------------
// Prefetch de usuarios (nombre + grupos) para evitar N+1
// ---------------------------------------------------------------------
$userMap = []; // id => ['name' => string, 'groups' => int[]]

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

        // Nombres
        $qb->clear()
           ->select($db->quoteName(['id', 'name']))
           ->from($db->quoteName('#__users'))
           ->where($db->quoteName('id') . ' IN (' . implode(',', array_map('intval', $userIds)) . ')');
        $db->setQuery($qb);
        $rows = (array) $db->loadAssocList('id');

        foreach ($rows as $id => $row) {
            $userMap[(int) $id] = ['name' => (string) $row['name'], 'groups' => []];
        }

        // Grupos
        $qb->clear()
           ->select($db->quoteName(['user_id', 'group_id']))
           ->from($db->quoteName('#__user_usergroup_map'))
           ->where($db->quoteName('user_id') . ' IN (' . implode(',', array_map('intval', $userIds)) . ')');
        $db->setQuery($qb);
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

    $groups = $userMap[$userId]['groups'] ?? [];
    $name   = htmlspecialchars($userMap[$userId]['name'] ?? (string) $userId, ENT_QUOTES, 'UTF-8');

    $cls = 'bg-info';
    if (in_array(8, $groups, true)) {
        $cls = 'bg-danger';
    } elseif (in_array(7, $groups, true)) {
        $cls = 'bg-warning';
    }

    return '<span class="badge ' . $cls . '" data-bs-toggle="tooltip" title="ID: ' . (int) $userId . '">' . $name . '</span>';
};
?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=trackactions_logs&view=trackactions_logs'); ?>"
      method="post"
      name="adminForm"
      id="adminForm"
      class="container-fluid px-3">

    <?php
    // Navegación (include robusto)
    $navFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php');
    if (is_file($navFile)) {
        require $navFile;
    }
    ?>

    <div class="card mb-3">
        <div class="card-body">
            <div id="j-main-container">

                <!-- LEYENDA -->
                <div class="card mb-3">
                    <div class="card-header text-center fw-bold">
                        <?php echo Text::_('COM_SECURITYCHECKPRO_COLOR_CODE'); ?>
                    </div>
                    <div class="card-body py-2">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="pe-2"><span class="badge bg-warning">&nbsp;</span></td>
                                    <td class="w-25"><?php echo Text::_('COM_SECURITYCHECKPRO_ADMINISTRATOR_GROUP'); ?></td>

                                    <td class="pe-2"><span class="badge bg-danger">&nbsp;</span></td>
                                    <td class="w-25"><?php echo Text::_('COM_SECURITYCHECKPRO_SUPER_USERS_GROUP'); ?></td>

                                    <td class="pe-2"><span class="badge bg-info">&nbsp;</span></td>
                                    <td class="w-25"><?php echo Text::_('COM_SECURITYCHECKPRO_OTHER_GROUPS'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- FIN LEYENDA -->

                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info d-flex align-items-center" role="status">
                        <span class="icon-info-circle me-2" aria-hidden="true"></span>
                        <div><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle" id="logsList">
                            <thead>
                                <tr>
                                    <th scope="col" width="2%">
                                        <?php echo HTMLHelper::_('searchtools.sort', '', 'a.id', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
                                    </th>
                                    <th scope="col" width="1%" class="text-center">
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

                            <tfoot>
                                <tr>
                                    <td colspan="7">
                                        <?php echo $this->pagination->getListFooter(); ?>
                                    </td>
                                </tr>
                            </tfoot>

                            <tbody>
                                <?php foreach ($this->items as $i => $item) : ?>
                                    <tr class="row<?php echo (int) $i % 2; ?>">
                                        <td class="text-muted">
                                            <span class="sortable-handler inactive" aria-hidden="true">
                                                <span class="icon-menu" aria-hidden="true"></span>
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?>
                                        </td>

                                        <td>
                                            <?php
                                            $message = (string) $item->message;
											$extension = (string) $item->extension;

											// 1) Asegura que el idioma del plugin system trackactions está cargado											
											$app->getLanguage()->load('plg_system_trackactions', JPATH_ADMINISTRATOR, null, false, true);
											$app->getLanguage()->load('plg_system_trackactions', JPATH_SITE, null, false, true);

											// 2) Si el mensaje "parece" una clave, tradúcelo
											if ($message !== '' && preg_match('/^[A-Z0-9_]+$/', $message) === 1) {
												$message = Text::_($message);
											}

											// 3) Deja que el plugin añada icono y/o retoque el texto ya traducido
											$icono = null;
											try {
												$app->triggerEvent('onLogMessagePrepare', [ &$message, $extension, &$icono ]);
											} catch (\Throwable $e) {
											}

											if (!empty($icono)) {
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
                                            $extTxt  = \Joomla\Plugin\System\Trackactions\Model\TrackActionsHelperModel::translateExtensionName($extBase);
                                            echo htmlspecialchars((string) $extTxt, ENT_QUOTES, 'UTF-8');
                                            ?>
                                        </td>

                                        <td>
                                            <?php echo $renderUserBadge(isset($item->user_id) ? (int) $item->user_id : null); ?>
                                        </td>

                                        <td>
                                            <?php
                                            $ip = trim((string) $item->ip_address);
                                            echo $ip !== '' ? '<code>' . htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') . '</code>' : '<span class="text-body-secondary">—</span>';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <input type="hidden" name="option" value="com_securitycheckpro">
    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <input type="hidden" name="view" value="trackactions_logs">
    <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>">
    <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

