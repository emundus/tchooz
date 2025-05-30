<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_login
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JLoader::register('UsersHelperRoute', JPATH_SITE . '/components/com_users/helpers/route.php');

JHtml::_('behavior.keepalive');
JHtml::_('bootstrap.tooltip');

$doc = JFactory::getDocument();
$doc->addStyleSheet('templates/g5_helium/html/mod_login/style/mod_login.css');

?>

<div class="page-header">
    <h1 class="em-titre-connectez-vous-apply">
        <?php echo JText::_('MOD_LOGIN_TITLE'); ?>
    </h1>
</div>
<form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure', 0)); ?>" method="post" id="login-form" class="form-inline">
    <?php if ($params->get('pretext')) : ?>
        <div class="pretext">
            <p><?php echo $params->get('pretext'); ?></p>
        </div>
    <?php endif; ?>
    <div class="userdata test">
        <div id="form-login-username" class="control-group">
            <div class="controls">
                <?php if (!$params->get('usetext', 0)) : ?>
                    <div class="input-prepend">
						<span class="add-on">
							<span class="icon-user hasTooltip" title="<?php echo JText::_('MOD_LOGIN_VALUE_USERNAME'); ?>"></span>
							<label for="modlgn-username" class="element-invisible"><?php echo JText::_('MOD_LOGIN_VALUE_USERNAME'); ?></label>
						</span>
                        <input id="modlgn-username" type="text" name="username" class="input-small" tabindex="0" size="18" placeholder="<?php echo JText::_('MOD_LOGIN_VALUE_USERNAME'); ?>" />
                    </div>
                <?php else : ?>
                    <label for="modlgn-username" class="em_label-user"><?php echo JText::_('MOD_LOGIN_VALUE_USERNAME'); ?></label>
                    <input id="modlgn-username" class="em_label-userInput" type="text" name="username" class="input-small" tabindex="0" size="18"/>
                <?php endif; ?>
            </div>
        </div>
        <div id="form-login-password" class="control-group">
            <div class="controls">
                <?php if (!$params->get('usetext', 0)) : ?>
                    <div class="input-prepend">
						<span class="add-on">
							<span class="icon-lock hasTooltip" title="<?php echo JText::_('JGLOBAL_PASSWORD'); ?>">
							</span>
								<label for="modlgn-passwd" class="element-invisible"><?php echo JText::_('JGLOBAL_PASSWORD'); ?>
							</label>
						</span>
                        <input id="modlgn-passwd" type="password" name="password" class="input-small" tabindex="0" size="18" placeholder="<?php echo JText::_('JGLOBAL_PASSWORD'); ?>" />
                    </div>
                <?php else : ?>
                    <label for="modlgn-passwd" class="em_label-passwd"><?php echo JText::_('JGLOBAL_PASSWORD'); ?></label>
                    <input id="modlgn-passwd" class="em_label-passwdInput" type="password" name="password" class="input-small" tabindex="0" size="18" />
                <?php endif; ?>
            </div>
        </div>
        <?php if (count($twofactormethods) > 1) : ?>
            <div id="form-login-secretkey" class="control-group">
                <div class="controls">
                    <?php if (!$params->get('usetext', 0)) : ?>
                        <div class="input-prepend input-append">
						<span class="add-on">
							<span class="icon-star hasTooltip" title="<?php echo JText::_('JGLOBAL_SECRETKEY'); ?>">
							</span>
								<label for="modlgn-secretkey" class="element-invisible"><?php echo JText::_('JGLOBAL_SECRETKEY'); ?>
							</label>
						</span>
                            <input id="modlgn-secretkey" autocomplete="off" type="text" name="secretkey" class="input-small" tabindex="0" size="18" placeholder="<?php echo JText::_('JGLOBAL_SECRETKEY'); ?>" />
                            <span class="btn width-auto hasTooltip" title="<?php echo JText::_('JGLOBAL_SECRETKEY_HELP'); ?>">
							<span class="icon-help"></span>
						</span>
                        </div>
                    <?php else : ?>
                        <label for="modlgn-secretkey"><?php echo JText::_('JGLOBAL_SECRETKEY'); ?></label>
                        <input id="modlgn-secretkey" autocomplete="off" type="text" name="secretkey" class="input-small" tabindex="0" size="18" placeholder="<?php echo JText::_('JGLOBAL_SECRETKEY'); ?>" />
                        <span class="btn width-auto hasTooltip" title="<?php echo JText::_('JGLOBAL_SECRETKEY_HELP'); ?>">
						<span class="icon-help"></span>
					</span>
                    <?php endif; ?>

                </div>
            </div>
        <?php endif; ?>
        <?php if (JPluginHelper::isEnabled('system', 'remember')) : ?>
            <div id="form-login-remember" class="control-group checkbox">
                <label for="modlgn-remember" class="control-label"><?php echo JText::_('MOD_LOGIN_REMEMBER_ME'); ?></label> <input id="modlgn-remember" type="checkbox" name="remember" class="inputbox" value="yes"/>
            </div>
        <?php endif; ?>
        <div id="form-login-submit" class="control-group">
            <div class="controls">
                <button type="submit" tabindex="0" name="Submit" class="btn btn-primary login-button"><?php echo JText::_('JLOGIN'); ?></button>
            </div>
        </div>
        <?php
        $usersConfig = JComponentHelper::getParams('com_users'); ?>
        <ul class="unstyled em-linkForgot">
            <?php if ($usersConfig->get('allowUserRegistration')) : ?>
                <li>
                    <a href="<?php echo JRoute::_('index.php?option=com_users&view=registration'); ?>" class="em-register">
                        <?php echo JText::_('MOD_LOGIN_REGISTER'); ?> <span class="icon-arrow-right"></span></a>
                </li>
            <?php endif; ?>

            <li>
                <a href="<?php echo JRoute::_('index.php?option=com_users&view=reset'); ?>" class="em-forgotPassword">
                    <?php echo JText::_('MOD_LOGIN_FORGOT_YOUR_PASSWORD'); ?></a>
            </li>
        </ul>
        <input type="hidden" name="option" value="com_users" />
        <input type="hidden" name="task" value="user.login" />
        <input type="hidden" name="return" value="<?php echo $return; ?>" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
    <?php if ($params->get('posttext')) : ?>
        <div class="posttext">
            <p><?php echo $params->get('posttext'); ?></p>
        </div>
    <?php endif; ?>
</form>
