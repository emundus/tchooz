 /**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

	document.addEventListener('DOMContentLoaded', function () {
		// Botones de "Más info"
		document.querySelectorAll('.js-info').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var title = this.dataset.title || '';
				var body  = this.dataset.body  || '';
				if (typeof configure_toast === 'function') {
					configure_toast(title, body);
				} else {
					console.error('configure_toast not defined');
				}
			});
		});
	});

    var Password = {
 
      _pattern : /[a-zA-Z0-9]/, 
      
      _getRandomByte : function()
      {
        // http://caniuse.com/#feat=getrandomvalues
        if(window.crypto && window.crypto.getRandomValues) 
        {
          var result = new Uint8Array(1);
          window.crypto.getRandomValues(result);
          return result[0];
        }
        else if(window.msCrypto && window.msCrypto.getRandomValues) 
        {
          var result = new Uint8Array(1);
          window.msCrypto.getRandomValues(result);
          return result[0];
        }
        else
        {
          return Math.floor(Math.random() * 256);
        }
      },
      
      generate : function(length)
      {
        return Array.apply(null, {'length': length})
          .map(function()
          {
            var result;
            while(true) 
            {
              result = String.fromCharCode(this._getRandomByte());
              if(this._pattern.test(result))
              {
                return result;
              }
            }        
          }, this)
          .join('');  
      }    
        
    };

	// Add element to a form
	function add_element_to_form(name,value) {
		var input = document.createElement("input");
		input.setAttribute("type", "hidden");
		input.setAttribute("name", name);
		input.setAttribute("value", value);

		//append to form element that you want .
		document.getElementById("adminForm").appendChild(input);
	}

