/**
 * Block Until Fabrik is Ready
 * 
 * This code blocks all user input until the Fabrik susbsystem is ready.
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var onFabrikReadyBody = false;
var blockDiv        = '<div id="blockDiv" style=" z-index:9999999;"></div>';
var blockDivTabs    = '<div id="blockDivTabs" style=" z-index:9999999;"></div>';
var actionsSelector = '.fabrikActions';
var tabsSelector    = '.fabrikForm ul.nav.nav-tabs';

function onFabrikReadyBlock(e) {
    if (blockDiv.length == 0 && blockDivTabs.length == 0) return false;
    e.stopPropagation();
    e.preventDefault();
    alert(Joomla.JText._("COM_FABRIK_STILL_LOADING"));
    blockDiv = '';
	blockDivTabs = '';
    return false;
}

function onFabrikReady() {
    if (window.jQuery) {
        if (typeof Fabrik === "undefined") {
			if (onFabrikReadyBody === false && jQuery(actionsSelector).length && jQuery("#blockDiv").length === 0) {
                jQuery(actionsSelector).wrap(blockDiv);
                
                jQuery("#blockDiv").click(function(e) {
                    return onFabrikReadyBlock(e);
                });
                jQuery("#blockDiv").mousedown(function(e) {
                    return onFabrikReadyBlock(e);
                });
            }
            if (onFabrikReadyBody === false && jQuery(tabsSelector).length && jQuery("#blockDivTabs").length === 0) {
                jQuery(tabsSelector).wrap(blockDivTabs);
                
                jQuery("#blockDivTabs").click(function(e) {
                    return onFabrikReadyBlock(e);
                });
                jQuery("#blockDivTabs").mousedown(function(e) {
                    return onFabrikReadyBlock(e);
                });
            }    
            setTimeout(onFabrikReady, 50);
       } else {

			if (jQuery(actionsSelector).parent().attr('id') === 'blockDiv') {
				jQuery(actionsSelector).unwrap();
			}
			if (jQuery(tabsSelector).parent().attr('id') === 'blockDivTabs') {
				jQuery(tabsSelector).unwrap();
			}
			
        }
    }
}


if (document.readyState !== 'loading') {
    onFabrikReady();
} else {
    document.addEventListener('DOMContentLoaded', onFabrikReady()); 
}

