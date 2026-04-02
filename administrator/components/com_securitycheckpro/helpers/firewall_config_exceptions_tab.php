<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */

// Asegura que tenemos variables locales con el valor (bool)
$exclude_exceptions_if_vulnerable_local = isset($this->exclude_exceptions_if_vulnerable)
    ? (bool)$this->exclude_exceptions_if_vulnerable
    : false;
$check_header_referer_local = isset($this->check_header_referer)
    ? (bool)$this->check_header_referer
    : false;
$check_base_64_local = isset($this->check_base_64)
    ? (bool)$this->check_base_64
    : false;
$strip_all_tags_local = isset($this->strip_all_tags)
    ? (bool)$this->strip_all_tags
    : false;	


?>

<!-- Exceptions -->
<div class="card mb-12">
    <div class="card-body">
        <div class="row">
            <div class="col-xl-12 mb-12">
                <div class="card-header text-white bg-primary">
                    <?php echo Text::_('PLG_SECURITYCHECKPRO_EXCEPTIONS_LABEL'); ?>
                </div>
                <div class="card-body">
                    <h4 class="card-title">
                        <?php echo Text::_('COM_SECURITYCHECKPRO_EXCLUDE_EXCEPTIONS_IF_VULNERABLE_LABEL'); ?>
                    </h4>
                    <div class="controls">
                        <?php echo $basemodel->renderSelect(
                            'exclude_exceptions_if_vulnerable',
                            'boolean',
                            ['class' => 'form-select'],
                            (int) $exclude_exceptions_if_vulnerable_local,
                            false
                        ); ?>
                    </div>
                    <blockquote>
                        <p class="small text-body-secondary">
                            <small><?php echo Text::_('COM_SECURITYCHECKPRO_EXCLUDE_EXCEPTIONS_IF_VULNERABLE_DESCRIPTION'); ?></small>
                        </p>
                    </blockquote>

                    <?php
                    echo HTMLHelper::_('uitab.startTabSet', 'ExceptionsTabs', [
						'active'    => $this->activeExceptions, // id						
						'breakpoint'=> 768              // Controla en qué ancho de pantalla los tabs se convierten en acordeón
					]);	
					
                    ?>

                    <?php echo HTMLHelper::_('uitab.addTab', 'ExceptionsTabs', 'li_header_referer_tab', Text::_('PLG_SECURITYCHECKPRO_CHECK_HEADER_REFERER_LABEL')); ?>
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_CHECK_HEADER_REFERER_LABEL'); ?></h4>
                        <div class="controls">
                            <?php echo $basemodel->renderSelect(
                                'check_header_referer',
                                'boolean',
                                ['class' => 'form-select'],
                                (int) $check_header_referer_local,
                                false
                            ); ?>
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_CHECK_HEADER_REFERER_DESCRIPTION'); ?></small></p></blockquote>
                    <?php echo HTMLHelper::_('uitab.endTab'); ?>

                    <?php echo HTMLHelper::_('uitab.addTab', 'ExceptionsTabs', 'li_base64_tab', Text::_('PLG_SECURITYCHECKPRO_CHECK_BASE64_LABEL')); ?>
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_CHECK_BASE64_LABEL'); ?></h4>
                        <div class="controls">
                            <?php echo $basemodel->renderSelect(
                                'check_base_64',
                                'boolean',
                                ['class' => 'form-select'],
                                (int) $check_base_64_local,
                                false
                            ); ?>
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_CHECK_BASE64_DESCRIPTION'); ?></small></p></blockquote>

                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_BASE64_EXCEPTIONS_LABEL'); ?></h4>
                        <div class="controls">
                            <textarea name="base64_exceptions" class="form-control firewall-config-style"><?php echo htmlspecialchars((string) $this->base64_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_BASE64_EXCEPTIONS_DESCRIPTION'); ?></small></p></blockquote>
                    <?php echo HTMLHelper::_('uitab.endTab'); ?>

                    <?php echo HTMLHelper::_('uitab.addTab', 'ExceptionsTabs', 'li_xss_tab', Text::_('XSS')); ?>
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_STRIP_ALL_TAGS_LABEL'); ?></h4>
                        <div class="controls" id="strip_all_tags">
                            <?php echo $basemodel->renderSelect(
                                'strip_all_tags',
                                'boolean',
                                ['class' => 'form-select', 'onchange' => 'Disable()'],
                                (int) $strip_all_tags_local,
                                false
                            ); ?>
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_STRIP_ALL_TAGS_DESCRIPTION'); ?></small></p></blockquote>

                        <div id="tags_to_filter_div">
                            <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_TAGS_TO_FILTER_LABEL'); ?></h4>
                            <div class="controls">
                                <textarea name="tags_to_filter" class="form-control firewall-config-style"><?php echo htmlspecialchars((string) $this->tags_to_filter, ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_TAGS_TO_FILTER_DESCRIPTION'); ?></small></p></blockquote>
                        </div>

                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_STRIP_TAGS_EXCEPTIONS_LABEL'); ?></h4>
                        <div class="controls">
                            <textarea name="strip_tags_exceptions" class="form-control firewall-config-style"><?php echo htmlspecialchars((string) $this->strip_tags_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_STRIP_TAGS_EXCEPTIONS_DESCRIPTION'); ?></small></p></blockquote>
                    <?php echo HTMLHelper::_('uitab.endTab'); ?>

                    <?php echo HTMLHelper::_('uitab.addTab', 'ExceptionsTabs', 'li_sql_tab', Text::_('SQL Injection')); ?>
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_DUPLICATE_BACKSLASHES_EXCEPTIONS_LABEL'); ?></h4>
                        <div class="controls">
                            <textarea cols="35" rows="3" name="duplicate_backslashes_exceptions" class="firewall-config-style"><?php echo htmlspecialchars((string) $this->duplicate_backslashes_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_DUPLICATE_BACKSLASHES_EXCEPTIONS_DESCRIPTION'); ?></small></p></blockquote>

                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_LINE_COMMENTS_EXCEPTIONS_LABEL'); ?></h4>
                        <div class="controls">
                            <textarea name="line_comments_exceptions" class="form-control firewall-config-style"><?php echo htmlspecialchars((string) $this->line_comments_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_LINE_COMMENTS_EXCEPTIONS_DESCRIPTION'); ?></small></p></blockquote>

                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SQL_PATTERN_EXCEPTIONS_LABEL'); ?></h4>
                        <div class="controls">
                            <textarea name="sql_pattern_exceptions" class="form-control firewall-config-style"><?php echo htmlspecialchars((string) $this->sql_pattern_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SQL_PATTERN_EXCEPTIONS_DESCRIPTION'); ?></small></p></blockquote>

                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_IF_STATEMENT_EXCEPTIONS_LABEL'); ?></h4>
                        <div class="controls">
                            <textarea name="if_statement_exceptions" class="form-control firewall-config-style"><?php echo htmlspecialchars((string) $this->if_statement_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_IF_STATEMENT_EXCEPTIONS_DESCRIPTION'); ?></small></p></blockquote>

                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_USING_INTEGERS_EXCEPTIONS_LABEL'); ?></h4>
                        <div class="controls">
                            <textarea name="using_integers_exceptions" class="form-control firewall-config-style"><?php echo htmlspecialchars((string) $this->using_integers_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_USING_INTEGERS_EXCEPTIONS_DESCRIPTION'); ?></small></p></blockquote>

                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_ESCAPE_STRINGS_EXCEPTIONS_LABEL'); ?></h4>
                        <div class="controls">
                            <textarea name="escape_strings_exceptions" class="form-control firewall-config-style"><?php echo htmlspecialchars((string) $this->escape_strings_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_ESCAPE_STRINGS_EXCEPTIONS_DESCRIPTION'); ?></small></p></blockquote>
                    <?php echo HTMLHelper::_('uitab.endTab'); ?>

                    <?php echo HTMLHelper::_('uitab.addTab', 'ExceptionsTabs', 'li_lfi_tab', Text::_('PLG_SECURITYCHECKPRO_LFI_EXCEPTIONS_LABEL')); ?>
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_LFI_EXCEPTIONS_LABEL'); ?></h4>
                        <div class="controls">
                            <textarea name="lfi_exceptions" class="form-control firewall-config-style"><?php echo htmlspecialchars((string) $this->lfi_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_LFI_EXCEPTIONS_DESCRIPTION'); ?></small></p></blockquote>
                    <?php echo HTMLHelper::_('uitab.endTab'); ?>

                    <?php echo HTMLHelper::_('uitab.addTab', 'ExceptionsTabs', 'li_secondlevel_tab', Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_EXCEPTIONS_LABEL')); ?>
                        <h4 class="card-title"><?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_EXCEPTIONS_LABEL'); ?></h4>
                        <div class="controls">
                            <textarea name="second_level_exceptions" class="form-control firewall-config-style"><?php echo htmlspecialchars((string) $this->second_level_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_EXCEPTIONS_DESCRIPTION'); ?></small></p></blockquote>
                    <?php echo HTMLHelper::_('uitab.endTab'); ?>

                    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
