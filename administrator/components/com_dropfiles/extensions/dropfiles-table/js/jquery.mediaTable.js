/**
Copyright (c) 2012 Marco Pegoraro, http://movableapp.com/

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


WHERE TO FIND MEDIA TABLE:
https://github.com/thepeg/MediaTable
http://consulenza-web.com/jquery/MediaTable/
http://www.consulenza-web.com/2012/01/mediatable-jquery-plugin/

**/


/**
 * jQuery Bacheca
 * Plugin Dimostrativo
 */

;(function($){
	
	
	/**
	 * DOM Initialization Logic
	 */
	
	var __loop = function( cfg, i ) {
		
		var $this 	= $(this),
			wdg 	= $this.data( 'MediaTable' );
		
		// Prevent re-initialization of the widget!
		if ( !$.isEmptyObject(wdg) ) return;
		
		// Build the widget context.
		wdg = {
			$wrap:		$('<div>'),		// Refer to the main content of the widget
			$table:		$this,			// Refer to the MediaTable DOM (TABLE TAG)
			$menu:		false,			// Refer to the column's toggler menu container
			cfg:		cfg,			// widget local configuration object
			id:			$this.attr('id')
		};
		
		// Setup Widget ID if not specified into DOM Table.
		if ( !wdg.id ) {
			wdg.id = 'MediaTable-' + i;
			wdg.$table.attr( 'id', wdg.id );
		}
		
		
		
		// Activate the MediaTable.
		wdg.$table.addClass('activeMediaTable');
		
		// Create the wrapper.
		wdg.$wrap.addClass('mediaTableWrapper');
		
		// Place the wrapper near the table and fill with MediaTable.
		wdg.$table.before(wdg.$wrap).appendTo(wdg.$wrap);
		
		
		// Menu initialization logic.
		if ( wdg.cfg.menu ) __initMenu( wdg );
		
		// Columns Initialization Loop.
		wdg.$table.find('thead th').each(function(i){	__thInit.call( this, i, wdg );	});
		
		
		
		
		// Save widget context into table DOM.
		wdg.$table.data( 'MediaTable', wdg );
		
	}; // EndOf: "__loop()" ###
	
		
		var __initMenu = function( wdg ) {
			
			// Buid menu objects
			wdg.$menu 			= $('<div />');
			wdg.$menu.$header 	= $('<a title="'+Joomla.JText._('COM_DROPFILES_DEFAULT_FRONT_COLUMNS','Columns')+'" ><i class="zmdi zmdi-settings"></i></a>');
			wdg.$menu.$list		= $('<ul />');
			
			// Setup menu general properties and append to DOM.
			wdg.$menu
				.addClass('mediaTableMenu')
				.addClass('mediaTableMenuClosed')
				.append(wdg.$menu.$header)
				.append(wdg.$menu.$list);
			
			// Add a class to the wrapper to inform about menu presence.
			wdg.$wrap.addClass('mediaTableWrapperWithMenu');
			
			// Setup menu title (handler)
			wdg.$menu.$header.text(wdg.cfg.menuTitle);
			wdg.$table.before(wdg.$menu);
			
			// Bind screen change events to update checkbox status of displayed fields.
			$(window).bind('orientationchange resize',function(){
				wdg.$menu.find('input').trigger('updateCheck');
			});
			
			// Toggle list visibility when clicking the menu title.
			wdg.$menu.$header.bind('click',function(){
				wdg.$menu.toggleClass('mediaTableMenuClosed');
			});
			
			wdg.$table.click(function() {
                wdg.$menu.addClass('mediaTableMenuClosed');
            });
			
			// Toggle list visibilty when mouse go outside the list itself.
			wdg.$menu.$list.bind('mouseleave',function(e){
				wdg.$menu.toggleClass('mediaTableMenuClosed');
				e.stopPropagation();
			});
			
			
			
		}; // EndOf: "__initMenu()" ###
		
		var __thInit = function( i, wdg ) {
			
			var $th 	= $(this),
				id		= $th.attr('id'),
				classes = $th.attr('class');
			
			// Set up an auto-generated ID for the column.
			// the ID is based upon widget's ID to allow multiple tables into one page.
			if ( !id ) {
				id = wdg.id + '-mediaTableCol-' + i;
				$th.attr( 'id', id );
			}
			
			// Add toggle link to the menu.
			if ( wdg.cfg.menu && !$th.is('.persist') ) {
				
				var $li = $('<li><input type="checkbox" name="toggle-cols" id="toggle-col-'+wdg.id+'-'+i+'" value="'+id+'" /> <label for="toggle-col-'+wdg.id+'-'+i+'">'+$th.text()+'</label></li>');
				wdg.$menu.$list.append($li);
				
				__liInitActions( $th, $li.find('input'), wdg );
				
			}
			
			// Propagate column's properties to each cell.
			$('tbody tr:not(.nohide)',wdg.$table).each(function(){	__trInit.call( this, i, id, classes ); });
			$('tbody',wdg.$table).on('change',function(){	
                            $('tbody tr:not(.nohide)',wdg.$table).each(function(){	__trInit.call( this, i, id, classes ); }); 
                        });
			
		}; // EndOf: "__thInit()" ###
		
		
		var __trInit = function( i, id, classes ) {
			
			var $cell	= $(this).find('td,th').eq(i);
			
			$cell.attr( 'headers', id );
			
			if ( classes ) $cell.addClass(classes);
			
		}; // EndOf: "__trInit()" ###
		
		
		var __liInitActions = function( $th, $checkbox, wdg ) {
			
			var change = function() {
				
				var	val 	= $checkbox.val(),  // this equals the header's ID, i.e. "company"
					cols 	= wdg.$table.find("#" + val + ", [headers="+ val +"]"); // so we can easily find the matching header (id="company") and cells (headers="company")
				
				
				if ( $checkbox.is(":checked")) { 
					cols.show();
					cols.removeClass('drop_col_hidden');
				} else { 
					cols.addClass('drop_col_hidden');
					cols.hide();
				};

				var visibleRow = $th.closest('tr');
				var visibleColumn = visibleRow.find('th:not(.drop_col_hidden)');
                visibleColumn.each(function() {
                    if ($(this).is(visibleColumn.last()) && !$(this).hasClass('file_download')) {
                    	$(this).addClass('drop-col-pd-right');
                    } else {
                    	$(this).removeClass('drop-col-pd-right');
                    }
                })
			};
			
			var updateCheck = function() {
				
				//if ( $th.css("display") ==  "table-cell") {
				if ( $th.is(':visible') ) {
					$checkbox.attr("checked", true);
				}
				else {
					$checkbox.attr("checked", false);
				};
			
			};
			
			$checkbox
				.bind('change',	change )
				.bind('updateCheck', updateCheck )
				.trigger( 'updateCheck' );
		
		} // EndOf: "__liInitActions()" ###
	
	
	
	
	
	
	
	/**
	 * Widget Destroy Logic
	 */
	
	var __destroy = function() {
		
		// Get the widget context.
		var wdg = $(this).data( 'MediaTable' );
		if ( !wdg ) return;
		
		
		// Remove the wrapper from the MediaTable.
		wdg.$wrap.after(wdg.$table).remove();
		
		// Remove MediaTable active class so media-query will not work.
		wdg.$table.removeClass('activeMediaTable');
		
		
		// Remove DOM reference to the widget context.
		wdg.$table.data( 'MediaTable', null );
		
	}; // EndOf: "__destroy()" ###
	
	
	
	
	
	
	
	/**
	 * jQuery Extension
	 */
	$.fn.mediaTable = function() {
		
		var cfg = false;

		// Default configuration block
		if ( !arguments.length || $.isPlainObject(arguments[0]) ) cfg = $.extend({},{
			
			// Teach the widget to create a toggle menu to declare column's visibility
			menu:		true,
			
		t:'e'},arguments[0]);
		// -- default configuration block --
		
		
		
		// Items initialization loop:	
		if ( cfg !== false ) {
			$(this).each(function( i ){ __loop.call( this, cfg, i ); });
			
			
			
 			
		// Item actions loop - switch throught actions
		} else if ( arguments.length ) switch ( arguments[0] ) {
		
			case 'destroy':
			$(this).each(function(){ __destroy.call( this ); });
			break;
		
		}
		
		
		// Mantengo la possibilit� di concatenare plugins.
		return this;
		
	}; // EndOf: "$.fn.mediaTable()" ###
	
	
})( jQuery );

