/*! Fabrik */
/**
 * Admin Visualization Editor
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(["jquery","admin/pluginmanager"],function(n,t){return new Class({Extends:t,Implements:[Options,Events],options:{},initialize:function(n,t){this.setOptions(n),this.watchSelector()},watchSelector:function(){void 0!==n?n("#jform_plugin").bind("change",function(n){this.changePlugin(n)}.bind(this)):document.id("jform_plugin").addEvent("change",function(n){n.stop(),this.changePlugin(n)}.bind(this))},changePlugin:function(n){var t={option:"com_fabrik",task:"visualization.getPluginHTML",format:"raw",plugin:n.target.get("value")};t[Joomla.getOptions("csrf.token")]=1;new Request({url:"index.php",evalResponse:!1,evalScripts:function(n,t){this.script=n}.bind(this),data:t,update:document.id("plugin-container"),onComplete:function(n){document.id("plugin-container").set("html",n),Browser.exec(this.script),this.updateBootStrap()}.bind(this)}).send()}})});

