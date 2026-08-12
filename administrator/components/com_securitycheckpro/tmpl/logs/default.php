<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU GPL v3 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Filesystem\Path;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Logs\HtmlView $this */

HTMLHelper::_('bootstrap.tooltip', '[data-bs-toggle="tooltip"]');

// Cargar idioma del plugin en el backend (evita null en Joomla 5/6)
$lang = Factory::getApplication()->getLanguage();
$lang->load('plg_system_securitycheckpro', JPATH_ADMINISTRATOR);

// Ordenación actual
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));


// Mapeo de tipo → [clase badge, etiqueta corta]
$badgeMap = [
    'XSS'                     => ['bg-danger',    'XSS'],
    'XSS_BASE64'              => ['bg-danger',    'XSS (b64)'],
    'SQL_INJECTION'           => ['bg-danger',    'SQL Injection'],
    'SQL_INJECTION_BASE64'    => ['bg-danger',    'SQL Inj. (b64)'],
    'CMD_INJECTION'           => ['bg-danger',    'CMD Injection'],
    'CRLF_INJECTION'          => ['bg-danger',    'CRLF Injection'],
    'LFI'                     => ['bg-warning',   'LFI'],
    'LFI_BASE64'              => ['bg-warning',   'LFI (b64)'],
    'MULTIPLE_EXTENSIONS'     => ['bg-warning',   'Upload'],
    'FORBIDDEN_EXTENSION'     => ['bg-warning',   'Upload'],
    'SECOND_LEVEL'            => ['bg-warning',   '2nd Level'],
    'IP_BLOCKED'              => ['bg-dark',      'IP Blocked'],
    'IP_BLOCKED_DINAMIC'      => ['bg-dark',      'IP Blocked (dyn)'],
    'SESSION_PROTECTION'      => ['bg-dark',      'Session'],
    'SESSION_HIJACK_ATTEMPT'  => ['bg-dark',      'Session Hijack'],
    'USER_AGENT_MODIFICATION' => ['bg-secondary', 'HTTP'],
    'REFERER_MODIFICATION'    => ['bg-secondary', 'HTTP'],
    'SPAM_PROTECTION'         => ['bg-secondary', 'Spam'],
    'URL_INSPECTOR'           => ['bg-secondary', 'URL'],
    'IP_PERMITTED'            => ['bg-success',   'IP Permitted'],
];

// Claves permitidas para tag_description (evita concatenaciones peligrosas)
$allowedTagKeys = [
    'TAGS_STRIPPED',
    'DUPLICATE_BACKSLASHES',
    'LINE_COMMENTS',
    'SQL_PATTERN',
    'IF_STATEMENT',
    'INTEGERS',
    'BACKSLASHES_ADDED',
    'LFI',
    'IP_BLOCKED',
    'IP_BLOCKED_DINAMIC',
    'IP_PERMITTED',
    'FORBIDDEN_WORDS',
    'SESSION_PROTECTION',
    'UPLOAD_SCANNER',
    'FAILED_LOGIN_ATTEMPT_LABEL',
	'URL_FORBIDDEN_WORDS',
	'HEURISTIC_SQL',
	'SPAM_PROTECTION',
	'URL_FORBIDDEN_WORDS',
	'CMD_INJECTION',
	'CRLF_INJECTION'
];

// Función utilitaria: sanitiza texto plano con sustitución
$esc = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

// Decodifica base64 de forma segura y limita longitud
$safeBase64 = static function (?string $b64, int $max = 4000) use ($esc): string {
    if ($b64 === null || $b64 === '') {
        return '';
    }
    $decoded = base64_decode($b64, true);
    if ($decoded === false) {
        return '';
    }
    if (mb_strlen($decoded, 'UTF-8') > $max) {
        $decoded = mb_substr($decoded, 0, $max, 'UTF-8') . '…';
    }
    return $esc($decoded);
};
?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&view=logs'); ?>"
      method="post" name="adminForm" id="adminForm" class="margin-left-10 margin-right-10">

    <?php 
    // Navegación (include robusto)
    $navFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php');
    if (is_file($navFile)) {
        require $navFile;
    }
    ?>

    <?php if (empty($this->logs_attacks)) : ?>
        <div class="alert alert-danger text-center margen_inferior" role="alert">
            <h2 class="h4 m-0"><?php echo Text::_('COM_SECURITYCHECKPRO_LOGS_RECORD_DISABLED'); ?></h2>
            <div id="top" class="mt-2"><?php echo Text::_('COM_SECURITYCHECKPRO_LOGS_RECORD_DISABLED_TEXT'); ?></div>
        </div>
    <?php endif; ?>

    <!-- Action bar -->
    <div class="scp-actionbar">
        <div>
            <p class="scp-actionbar__title">
                <i class="fa fa-shield-alt" aria-hidden="true"></i>
                <?php echo Text::_('COM_SECURITYCHECKPRO_CPANEL_VIEW_FIREWALL_LOGS_TEXT'); ?>
            </p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
        </div>

        <div class="logs-style">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th class="text-center">
                            <?php echo HTMLHelper::_('grid.sort', 'Ip', 'a.ip', $listDirn, $listOrder); ?>
                        </th>
                        <th class="text-center">
                            <?php echo HTMLHelper::_('grid.sort', 'COM_SECURITYCHECKPRO_LOG_TIME', 'a.time', $listDirn, $listOrder); ?>
                        </th>
                        <th class="text-center">
                            <?php echo Text::_('COM_SECURITYCHECKPRO_USER'); ?>
                        </th>
                        <th class="text-center" style="min-width:220px;">
                            <?php echo HTMLHelper::_('grid.sort', 'COM_SECURITYCHECKPRO_LOG_DESCRIPTION', 'a.description', $listDirn, $listOrder); ?>
                        </th>
                        <th class="text-center" style="width:20%;">
                            <?php echo Text::_('COM_SECURITYCHECKPRO_LOG_URI'); ?>
                        </th>
                        <th class="text-center">
                            <?php echo HTMLHelper::_('grid.sort', 'COM_SECURITYCHECKPRO_TYPE_COMPONENT', 'a.component', $listDirn, $listOrder); ?>
                        </th>
                        <th class="text-center">
                            <?php echo HTMLHelper::_('grid.sort', 'COM_SECURITYCHECKPRO_LOG_TYPE', 'a.type', $listDirn, $listOrder); ?>
                        </th>
                        <th class="text-center">
                            <?php echo HTMLHelper::_('grid.sort', 'COM_SECURITYCHECKPRO_LOG_READ', 'a.marked', $listDirn, $listOrder); ?>
                        </th>
                        <th class="text-center">
                            <input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" aria-label="<?php echo $esc(Text::_('JGLOBAL_CHECK_ALL')); ?>">
                        </th>
                    </tr>
                </thead>

                <tbody>
                <?php if (!empty($this->items)) :  
					$i = 0;
                    foreach ($this->items as $row) :
                        // Campos sanitizados
                        $ip        = $esc($row->ip ?? '');
                        $username  = $esc($row->username ?? '');
                        $desc      = $esc($row->description ?? '');
                        $uri       = $esc($row->uri ?? '');
                        $component = $esc($row->component ?? '');
                        $type      = $esc($row->type ?? '');
                        $marked    = (int) ($row->marked ?? 0);

                        // whois seguro (sólo si IP válida)
                        $whoisHref = null;
                        if (filter_var($row->ip ?? '', FILTER_VALIDATE_IP)) {
                            $whoisHref = 'https://www.whois.com/whois/' . rawurlencode((string) $row->ip);
                        }

                        // Fecha/hora (si viene en formato timestamp o string compatible)
                        $timeVal = $row->time ?? '';
                        $timeOut = $timeVal !== '' ? HTMLHelper::_('date', $timeVal, Text::_('DATE_FORMAT_LC2')) : '';

                        // tag_description seguro con whitelist
                        $tagKeyRaw = (string) ($row->tag_description ?? '');
						$tagKey    = strtoupper(trim($tagKeyRaw));
						$tagKey = in_array($tagKey, $allowedTagKeys, true) ? $tagKey : '';
						
						$tagTxt = $tagKey !== ''
							? Text::_('COM_SECURITYCHECKPRO_' . $tagKey)
							: Text::_('COM_SECURITYCHECKPRO_UNKNOWN_EVENT');

                        // Payload del ataque (decodificado de base64)
                        $decodedOriginal = $safeBase64($row->original_string ?? '', 4000);

                        // Badge por tipo
                        $badgeEntry = $badgeMap[$type] ?? null;
                        $badgeHtml  = $badgeEntry
                            ? '<span class="badge ' . $badgeEntry[0] . '" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-theme="dark" title="' . $esc(Text::_('COM_SECURITYCHECKPRO_TITLE_' . $type)) . '">' . $badgeEntry[1] . '</span>'
                            : '<span class="badge bg-secondary">' . $esc($type) . '</span>';
                    ?>
                    <tr>
                        <td class="text-center">
                            <?php if ($whoisHref) : ?>
                                <a href="<?php echo $whoisHref; ?>" class="whois-link"
                                   target="_blank" rel="noopener noreferrer"
                                   data-bs-toggle="tooltip" data-bs-placement="top" data-bs-theme="dark"
                                   title="<?php echo $esc(Text::_('COM_SECURITYCHECKPRO_WHOIS')); ?>">
                                    <?php echo $ip; ?>
                                </a>
                            <?php else : ?>
                                <?php echo $ip !== '' ? $ip : '—'; ?>
                            <?php endif; ?>
                        </td>

                        <td class="text-center">
                            <?php echo $timeOut !== '' ? $timeOut : '—'; ?>
                        </td>

                        <td class="text-center">
                            <?php echo $username !== '' ? $username : '—'; ?>
                        </td>

                        <td style="max-width:25rem;">
                            <div class="fw-semibold"><?php echo $tagTxt; ?></div>
                            <?php if ($desc !== '') : ?>
                                <div class="text-muted small"><?php echo $desc; ?></div>
                            <?php endif; ?>
                            <?php if ($decodedOriginal !== '') : ?>
                                <details class="mt-1">
                                    <summary class="text-muted small" style="cursor:pointer;"><?php echo Text::_('COM_SECURITYCHECKPRO_ORIGINAL_STRING_CSV'); ?></summary>
                                    <div class="text-muted small font-monospace mt-1" style="word-break:break-all;max-height:6rem;overflow-x:hidden;overflow-y:auto;"><?php echo $decodedOriginal; ?></div>
                                </details>
                            <?php endif; ?>
                        </td>

                        <?php
                        $uriShort = mb_strlen($row->uri ?? '', 'UTF-8') > 70
                            ? mb_substr($row->uri ?? '', 0, 70, 'UTF-8') . '…'
                            : ($row->uri ?? '');
                        ?>
                        <td class="text-center" style="word-break:break-all;"
                            <?php if ($uri !== '' && mb_strlen($row->uri ?? '', 'UTF-8') > 70) : ?>
                                title="<?php echo $uri; ?>"
                            <?php endif; ?>>
                            <?php echo $uri !== '' ? $esc($uriShort) : '—'; ?>
                        </td>

                        <td class="text-center">
                            <?php echo $component !== '' ? mb_substr($component, 0, 40, 'UTF-8') : '—'; ?>
                        </td>

                        <td class="text-center">
                            <?php echo $badgeHtml; ?>
                        </td>

                        <td class="text-center">
                            <?php if ($marked) : ?>
                                <span class="badge bg-success"><?php echo Text::_('COM_SECURITYCHECKPRO_LOG_READ'); ?></span>
                            <?php else : ?>
                                <span class="badge bg-warning"><?php echo Text::_('COM_SECURITYCHECKPRO_LOG_UNREAD'); ?></span>
                            <?php endif; ?>
                        </td>

                        <td class="text-center">
                            <?php echo HTMLHelper::_('grid.id', $i, (int) ($row->id ?? 0)); ?>
                        </td>
                    </tr>
                    <?php
                    $i++;
                    endforeach;
                else : ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($this->items)) : ?>
            <div class="margin-left-10">
                <?php echo $this->pagination->getListFooter(); ?>
            </div>
        <?php endif; ?>

    </div>

    <input type="hidden" name="option" value="com_securitycheckpro">
    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <input type="hidden" name="controller" value="securitycheckpro">	
	<input type="hidden" name="filter_order"     value="<?= $esc($listOrder); ?>">
	<input type="hidden" name="filter_order_Dir" value="<?= $esc($listDirn); ?>">  
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
