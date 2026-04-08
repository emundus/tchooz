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
use Joomla\CMS\Uri\Uri;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Logs\HtmlView $this */

// Cargar idioma del plugin en el backend (evita null en Joomla 5/6)
$lang = Factory::getApplication()->getLanguage();
$lang->load('plg_system_securitycheckpro', JPATH_ADMINISTRATOR);

// Ordenación actual
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));


// Prefijos/paths
$mediaBase = rtrim(Uri::root(), '/') . '/media/com_securitycheckpro/images';

// Mapeo de tipo->icono
$iconMap = [
    'XSS'                    => 'xss.png',
    'XSS_BASE64'             => 'xss_base64.png',
    'SQL_INJECTION'          => 'sql_injection.png',
    'SQL_INJECTION_BASE64'   => 'sql_injection_base64.png',
    'LFI'                    => 'local_file_inclusion.png',
    'LFI_BASE64'             => 'local_file_inclusion_base64.png',
    'IP_PERMITTED'           => 'permitted.png',
    'IP_BLOCKED'             => 'blocked.png',
    'IP_BLOCKED_DINAMIC'     => 'dinamically_blocked.png',
    'SECOND_LEVEL'           => 'second_level.png',
    'USER_AGENT_MODIFICATION'=> 'http.png',
    'REFERER_MODIFICATION'   => 'http.png',
    'SESSION_PROTECTION'     => 'session_protection.png',
    'SESSION_HIJACK_ATTEMPT' => 'session_hijack.png',
    'MULTIPLE_EXTENSIONS'    => 'upload_scanner.png',
    'FORBIDDEN_EXTENSION'    => 'upload_scanner.png',
    'SPAM_PROTECTION'        => 'spam_protection.png',
    'URL_INSPECTOR'          => 'url_inspector.png',
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
	'URL_FORBIDDEN_WORDS'
];

// Función utilitaria: sanitiza texto plano con sustitución
$esc = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

// Función utilitaria: decodifica base64 de forma segura y limita longitud
$safeBase64 = static function (?string $b64, int $max = 4000) use ($esc): string {
    if ($b64 === null || $b64 === '') {
        return '';
    }
    $decoded = base64_decode($b64, true); // strict
    if ($decoded === false) {
        return Text::_('COM_SECURITYCHECKPRO_INVALID_BASE64');
    }
    // Limitar tamaño para evitar payloads enormes en el DOM
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
                        <th class="text-center">
                            <?php echo HTMLHelper::_('grid.sort', 'COM_SECURITYCHECKPRO_LOG_DESCRIPTION', 'a.description', $listDirn, $listOrder); ?>
                        </th>
                        <th class="text-center width-35">
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

                        // original_string (base64) a texto seguro, acotado
                        $decodedOriginal = $safeBase64($row->original_string ?? '', 4000);

                        // Icono por tipo
                        $iconFile = $iconMap[$type] ?? null;
                        $iconHtml = $iconFile
                            ? HTMLHelper::_(
                                'image',
                                $mediaBase . '/' . $iconFile,
                                $esc(Text::_('COM_SECURITYCHECKPRO_TITLE_' . $type)),
                                [
                                    'title' => $esc(Text::_('COM_SECURITYCHECKPRO_TITLE_' . $type)),
                                    'loading' => 'lazy',
                                    'decoding' => 'async',
                                    'class' => 'img-fluid',
                                ]
                              )
                            : $esc($type); // fallback textual si no hay icono
                    ?>
                    <tr>
                        <td class="text-center">
                            <?php if ($whoisHref) : ?>
                                <a href="<?php echo $whoisHref; ?>" class="whois-link"
                                   target="_blank" rel="noopener noreferrer"
                                   data-bs-toggle="tooltip"
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

                        <td class="text-center">
                            <div>
                                <strong><?php echo $tagTxt; ?></strong>
                                <?php if ($desc !== '') : ?>
                                    <?php echo ': ' . $desc; ?>
                                <?php endif; ?>
                            </div>
                            <div class="mt-2">
                                <label class="visually-hidden" for="orig_<?php echo (int) ($row->id ?? $i); ?>">
                                    <?php echo Text::_('COM_SECURITYCHECK_ORIGINAL_STRING'); ?>
                                </label>
                                <textarea id="orig_<?php echo (int) ($row->id ?? $i); ?>"
                                          cols="30" rows="2" readonly
                                          class="form-control form-control-sm"><?php echo $decodedOriginal; ?></textarea>
                            </div>
                        </td>

                        <td class="text-center" style="word-break: break-all;">
                            <?php echo $uri !== '' ? $uri : '—'; ?>
                        </td>

                        <td class="text-center">
                            <?php echo $component !== '' ? mb_substr($component, 0, 40, 'UTF-8') : '—'; ?>
                        </td>

                        <td class="text-center">
                            <?php echo $iconHtml; ?>
                        </td>

                        <td class="text-center">
                            <?php
                            echo HTMLHelper::_(
                                'image',
                                $mediaBase . '/' . ($marked ? 'read.png' : 'no_read.png'),
                                $marked ? Text::_('COM_SECURITYCHECKPRO_LOG_READ') : Text::_('COM_SECURITYCHECKPRO_LOG_UNREAD'),
                                [
                                    'title' => $marked ? Text::_('COM_SECURITYCHECKPRO_LOG_READ') : Text::_('COM_SECURITYCHECKPRO_LOG_UNREAD'),
                                    'loading' => 'lazy',
                                    'decoding' => 'async',
                                ]
                            );
                            ?>
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

        <div class="col-md-6 col-lg-8 mb-md-0 mb-4 margin-top-10">
            <p class="mb-0">
                <?php echo Text::_('COM_SECURITYCHECKPRO_COPYRIGHT'); ?>
                | <?php echo Text::_('COM_SECURITYCHECKPRO_ICONS_ATTRIBUTION'); ?>
            </p>
        </div>
    </div>

    <input type="hidden" name="option" value="com_securitycheckpro">
    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <input type="hidden" name="controller" value="securitycheckpro">	
	<input type="hidden" name="filter_order"     value="<?= $esc($listOrder); ?>">
	<input type="hidden" name="filter_order_Dir" value="<?= $esc($listDirn); ?>">  
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
