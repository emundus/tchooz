/**
 * Admin subGroup Editor
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */


class FbSubForm {
	/* Add our event handlers for new rows and removed rows */
    constructor() {
    	document.addEventListener('subform-row-add', (event) => this.handleAdd(event));
    	document.addEventListener('subform-row-remove', (event) => this.handleRemove(event));
	}

	/* To handle a remove, we get all the inputs, and remove them from the FabrikAdmin.model.fields object */
	handleRemove(event) {
		this.remGroup = event.detail.row;
		this.remInputs = this.remGroup.querySelectorAll('input, select');
        this.remInputs.forEach((input) => { 
            for (const [type, plugins] of Object.entries(FabrikAdmin.model.fields)) {
				/* Purge the plugin */
				if (typeof (FabrikAdmin.model.fields[type][input.id]) !== 'undefined') {
					delete FabrikAdmin.model.fields[type][input.id];
				}
			}
		});
        this.remInputs = null, this.remGroup = null;
	}

	/* An add is much more complex, first we need to wait for the new row to be added to the dop */
	handleAdd(event) {
		this.addGroup = event.detail.row;
		this.addInputs = this.addGroup.querySelectorAll('input, select');
		if (this.addInputs.length == 0) {
			this.addInputsPeriodical = this.getAddInputs.periodical(500, this);
		} else {
			this.addSetUp();
		}
	}

	/* The new row has now been added to the dom */
	addSetUp() { 
		/* Loop through the inputs and process those we need to keep track of, 
		 * there will be a template for the field in the FabrikAdmin.model.fields object
		 */
        this.addInputs.forEach((input) => { 
        	/* We need to locate the field template in the FabrikAdmin.model.fields object to determine if it is a table or element */
            for (const [type, plugins] of Object.entries(FabrikAdmin.model.fields)) {
				/* Figure out the template element name and determine if we have a plugin set up for it */
				let newPlugin = false;
				const newElementID = input.id;
				const elementTemplateName = newElementID.replace(this.addGroup.dataset.group, this.addGroup.dataset.baseName + 'X');
				if (typeof (FabrikAdmin.model.fields[type][elementTemplateName]) !== 'undefined') {
					/* OK, we found the template in table or element */
					/* Build the new plugin and instantiate it */
					const sourceid 			= newElementID.match(/__datasources(\d+)__/)[1];
					const o 				= {...FabrikAdmin.model.fields[type][elementTemplateName].options};
					o.isTemplate			= false;
					o.templateName 			= elementTemplateName;
					o.conn 					= o.conn.replace('__datasourcesX__', `__datasources${sourceid}__`);
					if (type == 'element') {
						o.table 				= o.table.replace('__datasourcesX__', `__datasources${sourceid}__`);
					}
					const newClass			= type == "element" ? 'elementElement' : 'fabriktablesElement';
					var p					= new window[newClass](newElementID, o);
					p.el 					= document.id(newElementID);
					newPlugin = true;
				}
				if (newPlugin !== false) {
					/* The field was found so insert the instantiated plugin into the FabrikAdmin.model.fields object */
					FabrikAdmin.model.fields[type][newElementID] = p;
				}
			}
		});
        this.addInputs = null, this.addGroup = null;
	}

	/* Function to get all the inputs for the new row, runs via the periodical */
	getAddInputs() {
		if (!(this.addInputs = this.addGroup.querySelectorAll('input, select'))) {
			return;
		}
		this.addSetUp();
		clearInterval(this.addInputsPeriodical);
	}

}
