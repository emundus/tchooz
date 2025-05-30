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
?>
<div class="container-module-cas">
    <div class="sous-container-module-cas">
		<?php if ($mod_emundus_cas_tab2_display == 1): ?>
            <ul>
                <li id="onglet-connexion" onclick="Connexion()"><?= JText::_('MOD_EM_CAS_SUBMENU1') ?></li>
                <li id="onglet-inscription" class="couleurFoncee"
                    onclick="Inscription()"><?= JText::_('MOD_EM_CAS_SUBMENU2') ?></li>
            </ul>
		<?php endif; ?>


        <div id="connexion">
            <div class="container">
                <p><?= $mod_emundus_cas_url1_desc; ?></p>
                <br/>

				<?php if (empty($mod_emundus_cas_logo)) : ?>
                    <div class="btn-cas">

                        <a href="<?= $mod_emundus_cas_url1; ?>" class="btn btn-primary rounded">
							<?= $mod_emundus_cas_btn1; ?>
                        </a>
                    </div>
				<?php else: ?>
                    <div class="btn-cas">
                        <a href="<?= $mod_emundus_cas_url1; ?>" class="btn btn-primary logo">

                            <img src="<?= $mod_emundus_cas_logo; ?>" alt="Icône du système de connexion"/>
                        </a>
                        <a href="<?= $mod_emundus_cas_url1; ?>" class="btn btn-primary">
							<?= $mod_emundus_cas_btn1; ?>
                        </a>
                    </div>
				<?php endif; ?>

            </div>
        </div>

        <div id="inscription" class="invisible">
			<?php if (!empty($mod_emundus_cas_url2_desc) || !empty($mod_emundus_cas_url2)) : ?>
                <div class="container em-grid-2">
                    <p><?= $mod_emundus_cas_url2_desc; ?></p>
                    <p><a href="<?= $mod_emundus_cas_url2; ?>" class="btn btn-primary"><?= $mod_emundus_cas_btn2; ?></a>
                    </p>
                </div>
			<?php endif; ?>

            <div class="formulaire">
                <form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure', 0)); ?>" method="post"
                      id="login-form" class="form-inline">
					<?php if ($params->get('pretext')) : ?>
                        <div class="pretext">
                            <p><?php echo $params->get('pretext'); ?></p>
                        </div>
					<?php endif; ?>
                    <div class="userdata">
                        <div id="form-login-username" class="control-group">
                            <div class="controls">
								<?php if (!$params->get('usetext', 0)) : ?>
                                    <div class="input-prepend">
						<span class="add-on">
							<span class="icon-user hasTooltip"
                                  title="<?php echo JText::_('MOD_LOGIN_VALUE_USERNAME'); ?>"></span>
							<label for="modlgn-username"
                                   class="element-invisible"><?php echo JText::_('MOD_LOGIN_VALUE_USERNAME'); ?>
                                  <i data-isicon="true" class="icon-star small "></i>
                            </label>
						</span>
                                        <input id="modlgn-username" type="text" name="username" class="input-small"
                                               tabindex="0"
                                               size="18"
                                               placeholder="<?php echo JText::_('MOD_LOGIN_VALUE_USERNAME'); ?>"/>
                                    </div>
								<?php else : ?>
                                    <label for="modlgn-username"><?php echo JText::_('MOD_LOGIN_VALUE_USERNAME'); ?>
                                        <i data-isicon="true" class="icon-star small "></i>
                                    </label>
                                    <input id="modlgn-username" type="text" name="username" class="input-small"
                                           tabindex="0"
                                           size="18" placeholder="<?php echo JText::_('MOD_LOGIN_VALUE_USERNAME'); ?>"/>
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
								<label for="modlgn-passwd"
                                       class="element-invisible"><?php echo JText::_('JGLOBAL_PASSWORD'); ?>
                                       <i data-isicon="true" class="icon-star small "></i>
							</label>
						</span>
                                        <input id="modlgn-passwd" type="password" name="password" class="input-small"
                                               tabindex="0" size="18"
                                               placeholder="<?php echo JText::_('JGLOBAL_PASSWORD'); ?>"/>
                                    </div>
								<?php else : ?>
                                    <label for="modlgn-passwd"><?php echo JText::_('JGLOBAL_PASSWORD'); ?>
                                        <i data-isicon="true" class="icon-star small "></i>
                                    </label>
                                    <input id="modlgn-passwd" type="password" name="password" class="input-small"
                                           tabindex="0"
                                           size="18" placeholder="<?php echo JText::_('JGLOBAL_PASSWORD'); ?>"/>
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
								<label for="modlgn-secretkey"
                                       class="element-invisible"><?php echo JText::_('JGLOBAL_SECRETKEY'); ?>
							</label>
						</span>
                                            <input id="modlgn-secretkey" autocomplete="one-time-code" type="text"
                                                   name="secretkey" class="input-small" tabindex="0" size="18"
                                                   placeholder="<?php echo JText::_('JGLOBAL_SECRETKEY'); ?>"/>
                                            <span class="btn width-auto hasTooltip"
                                                  title="<?php echo JText::_('JGLOBAL_SECRETKEY_HELP'); ?>">
							<span class="icon-help"></span>
						</span>
                                        </div>
									<?php else : ?>
                                        <label for="modlgn-secretkey"><?php echo JText::_('JGLOBAL_SECRETKEY'); ?></label>
                                        <input id="modlgn-secretkey" autocomplete="one-time-code" type="text"
                                               name="secretkey"
                                               class="input-small" tabindex="0" size="18"
                                               placeholder="<?php echo JText::_('JGLOBAL_SECRETKEY'); ?>"/>
                                        <span class="btn width-auto hasTooltip"
                                              title="<?php echo JText::_('JGLOBAL_SECRETKEY_HELP'); ?>">
						<span class="icon-help"></span>
					</span>
									<?php endif; ?>

                                </div>
                            </div>
						<?php endif; ?>
						<?php if (JPluginHelper::isEnabled('system', 'remember')) : ?>
                            <div id="form-login-remember" class="control-group checkbox">
                                <label for="modlgn-remember"
                                       class="control-label"><?php echo JText::_('MOD_LOGIN_REMEMBER_ME'); ?></label>
                                <input
                                        id="modlgn-remember" type="checkbox" name="remember" class="inputbox"
                                        value="yes"/>
                            </div>
						<?php endif; ?>
                        <div id="form-login-submit" class="control-group">
                            <div class="controls">
                                <button type="submit" tabindex="0" name="Submit"
                                        class="btn btn-primary login-button"><?php echo JText::_('JLOGIN'); ?></button>
                            </div>
                        </div>
                        <div class="control-group em-float-right">
                            <div class="control-label">
                                <a class="hover:tw-underline" href="<?php echo JRoute::_($forgottenLink); ?>">
				                    <?php echo JText::_('COM_USERS_LOGIN_RESET'); ?>
                                </a>
                            </div>
                        </div>
						<?php
						$usersConfig = JComponentHelper::getParams('com_users'); ?>

                        <input type="hidden" name="option" value="com_users"/>
                        <input type="hidden" name="task" value="user.login"/>
                        <input type="hidden" name="return" value="<?php echo $return; ?>"/>
						<?php echo JHtml::_('form.token'); ?>
                    </div>
					<?php if ($params->get('posttext')) : ?>
                        <div class="posttext">
                            <p><?php echo $params->get('posttext'); ?></p>
                        </div>
					<?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function Connexion() {
        var connexion = document.getElementById("connexion");
        connexion.classList.remove("invisible");

        var inscription = document.getElementById("inscription");
        inscription.classList.add("invisible");

        var ongletConnexion = document.getElementById("onglet-inscription");
        ongletConnexion.classList.add("couleurFoncee");

        var ongletInscription = document.getElementById("onglet-connexion");
        ongletInscription.classList.remove("couleurFoncee");

    }

    function Inscription() {
        var connexion = document.getElementById("connexion");
        connexion.classList.add("invisible");

        var inscription = document.getElementById("inscription");
        inscription.classList.remove("invisible");

        var ongletConnexion = document.getElementById("onglet-inscription");
        ongletConnexion.classList.remove("couleurFoncee");

        var ongletInscription = document.getElementById("onglet-connexion");
        ongletInscription.classList.add("couleurFoncee");

    }
</script>
