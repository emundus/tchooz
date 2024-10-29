/**
 * Dropfiles
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and to customize.
 * Otherwise, please feel free to contact us at contact@joomunited.com *
 * @package Dropfiles
 * @copyright Copyright (C) 2013 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @copyright Copyright (C) 2013 Damien Barrère (http://www.crac-design.com). All rights reserved.
 * @license GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 *
 */
jQuery(document).ready(function ($) {
    if (typeof(Dropfiles) == 'undefined') {
        Dropfiles = {};
        Dropfiles.can = {};
        Dropfiles.can.create = false;
        Dropfiles.can.edit = false;
        Dropfiles.can.delete = false;
        Dropfiles.maxfilesize = 10;
        Dropfiles.selection = {};
        Dropfiles.selected = {};
        Dropfiles.selected.access = false;
        Dropfiles.selected.ordering = false;
        Dropfiles.selected.orderingdir = false;
        Dropfiles.selected.usergroup = false;
        Dropfiles.catRefTofileId = false;
    }

    if (typeof (Joomla) === "undefined") {
        return;
    }

    if (typeof(dropfilesRootUrl)=== 'string') {
        dropfilesRootUrl = dropfilesRootUrl.endsWith('/') ? dropfilesRootUrl.slice(0, -1) : dropfilesRootUrl;
    } else {
        dropfilesRootUrl = '';
    }

    var categoryAjax = null;
    Dropfiles.checkAndUpdatePreview = checkAndUpdatePreview = function () {
        var catsmanage = getUrlParameter('site_catid');
        var tasksmanage = getUrlParameter('task');
        if (!tasksmanage && tasksmanage !== 'site_manage') {
            updatepreview();
        } else {
            updatepreview(catsmanage);
        }
    }

    /**
     * Init sortable files
     * Save order after each sort
     */
    function initSortableFiles() {
        $('#preview').sortable({
            placeholder: 'highlight file',
            revert: 300,
            distance: 5,
            tolerance: "pointer",
            items: ".file",
            appendTo: "body",
            cursorAt: {top: 0, left: 0},
            helper: function (e, item) {
                filename = $(item).find('.title').text() + "." + $(item).find('.type').text();
                fileext = $(item).find('.type').text();
                count = $('#preview').find('.file.selected').length;
                if (count > 1) {
                    return $("<div id='file-handle' class='dropfiles_draged_file ui-widget-header' ><div class='ext "+fileext+"'><span class='txt'>"+fileext+"</span></div><div class='filename'>" + filename + "</div><span class='fCount'>" + count + "</span></div>");
                } else {
                    return $("<div id='file-handle' class='dropfiles_draged_file ui-widget-header' ><div class='ext "+fileext+"'><span class='txt'>"+fileext+"</span></div><div class='filename'>" + filename + "</div></div>");
                }
            },
            update: function () {
                var json = '';
                id_category = jQuery('input[name=id_category]').val();
                $.each($('#preview .file'), function (i, val) {
                    if (json !== '') {
                        json += ',';
                    }
                    json += '"' + i + '":"' + $(val).data('id-file') + '"';
                });
                json = '{' + json + '}';
                $.ajax({
                    url: dropfilesRootUrl + "/index.php?option=com_dropfiles&task=files.reorder&idcat=" + id_category,
                    type: "POST",
                    data: {order: json}
                }).done(function (data) {
                    var ismovefile = $('#dropfiles-movefile-container');
                    if (ismovefile.length > 0) {
                        ismovefile.remove();
                    } else {
                        $.gritter.add({text: Joomla.JText._('COM_DROPFILES_JS_CATEGORY_ORDER', 'File(s) removed with success!')});
                    }
                });
            },
            /** Prevent firefox bug positionnement **/
            start: function (event, ui) {
                $(ui.helper).css('width', 'auto');
//                $(ui.helper).find('td').each(function(i,e){
//                    $(e).css('width',$('#preview .restable thead th:nth-child('+(i+1)+')').width());
//                });

                var userAgent = navigator.userAgent.toLowerCase();
                if (ui.helper !== "undefined" && userAgent.match(/firefox/)) {
                    ui.helper.css('position', 'absolute');
                }

                ui.placeholder.html("<td colspan='8'></td>");
                //ui.placeholder.height(ui.helper.height());
            },
            beforeStop: function (event, ui) {
                var userAgent = navigator.userAgent.toLowerCase();
                if (ui.offset !== "undefined" && userAgent.match(/firefox/)) {
                    ui.helper.css('margin-top', 0);
                }
            }
        });
    }

    $('#preview').disableSelection();

    if (typeof(gcaninsert) !== 'undefined' && gcaninsert === true) {
        if (typeof(window.parent.tinyMCE) !== 'undefined' && window.parent.tinyMCE.activeEditor !== null) {
            content = window.parent.tinyMCE.get(e_name).selection.getContent();
            imgparent = window.parent.tinyMCE.get(e_name).selection.getNode().parentNode;
            exp = '<img.*data\-dropfilesfile="([0-9a-zA-Z_]+)".*?>';
            file = content.match(exp);
            exp = '<img.*data\-dropfilescategory="([0-9]+)".*?>';
            category = content.match(exp);
            exp = '<img.*data\-dropfilesfilecategory="([0-9]+)".*?>';
            filecategory = content.match(exp);
            Dropfiles.selection = new Array();
            Dropfiles.selection.content = content;

            if (file !== null && filecategory !== null) {
                if (file !== null) {
                    elem = $(content).filter('img[data-dropfilesfile=' + file[1] + ']');
                    Dropfiles.selection.selection = elem;
                    Dropfiles.selection.file = file[1];
                }
                if (filecategory !== null) {
                    Dropfiles.selection.category = filecategory[1];
                    $('input[name=id_category]').val(filecategory[1]);
                    updatepreview(filecategory[1], file[1]);
                }
            } else if (category !== null) {
                Dropfiles.selection.category = category[1];
                $('input[name=id_category]').val(category[1]);
                updatepreview(category[1]);
            } else {
                updatepreview();
            }
        } else if (typeof window.parent.CKEDITOR != 'undefined') {
            var ckEditor = window.parent.CKEDITOR.instances[e_name];
            imgElement = ckEditor.getSelection().getSelectedElement();
            if (typeof imgElement != "undefined" && imgElement != null) {
                file = imgElement.getAttribute('data-dropfilesfile');
                category = imgElement.getAttribute('data-dropfilescategory');
                filecategory = imgElement.getAttribute('data-dropfilesfilecategory');
                Dropfiles.selection = new Array();

                if (file !== null && filecategory !== null) {
                    if (file !== null) {
                        Dropfiles.selection.selection = imgElement;
                        Dropfiles.selection.file = file;
                    }
                    if (filecategory !== null) {
                        Dropfiles.selection.category = filecategory;
                        $('input[name=id_category]').val(filecategory);
                        updatepreview(filecategory, file);
                    }
                } else if (category !== null) {
                    Dropfiles.selection.category = category;
                    $('input[name=id_category]').val(category);
                    updatepreview(category);
                } else {
                    updatepreview();
                }
            } else {
                updatepreview();
            }
        } else {
            updatepreview();
        }
    } else {
        /* Load gallery */
        checkAndUpdatePreview();
    }

    function checkCateActive(id_category) {
        id_category_ck = null;
        id_category = $('#dropfiles_upload_target_category').data('id-category');
        $('input[name=id_category]').val(id_category);

        return id_category;
    }

    /* Load nestable */
    checkCateActive(null);
    if (Dropfiles.can.edit || (Dropfiles.can.editown && Dropfiles.author === $('#dropfiles_upload_target_category').data('author'))
     || Dropfiles.can.upload) {
        $('.nested').nestable({
            maxDepth: 16,
            effect: {animation: 'fade', time: 'slow'},
            onClick: function(l, e, p) {
                id_category = $(e).data('id-category');
                $('input[name=id_category]').val(id_category);
                if (Dropfiles.catRefTofileId) {
                    updatepreview(id_category, Dropfiles.catRefTofileId);
                    Dropfiles.catRefTofileId = false;
                } else {
                    updatepreview(id_category);
                    Dropfiles.catRefTofileId = false;
                }
                $(e).addClass('active');
                if ($(e).find('.google-drive-icon').length > 0) {
                    $('#rightcol .fileblock').addClass('googleblock');
                    $('#rightcol .categoryblock').addClass('catgoogleblock');
                } else {
                    $('#rightcol .fileblock').removeClass('googleblock');
                    $('#rightcol .categoryblock').removeClass('catgoogleblock');
                }
                updatepreview(id_category);
                return false;
            },
            callback: function (event, e) {
                var isCloudItem = $(e).find('div.dd3-handle i.google-drive-icon-white').length;
                var isDropboxItem = $(e).find('div.dd3-handle i.dropbox-icon-white').length;
                var isOnedriveItem = $(e).find('div.dd3-handle i.onedrive-icon-white').length;
                var isOnedriveBusinessItem = $(e).find('div.dd3-handle i.onedrive-business-icon-white').length;
                var itemChangeType = 'default';
                if (isCloudItem > 0) {
                    itemChangeType = 'googledrive';
                } else if (isDropboxItem > 0) {
                    itemChangeType = 'dropbox';
                } else if (isOnedriveItem > 0) {
                    itemChangeType = 'onedrive';
                } else if (isOnedriveBusinessItem > 0) {
                    itemChangeType = 'onedrivebusiness';
                }
                pk = $(e).data('id-category');
                if ($(e).prev('li').length === 0) {
                    position = 'first-child';
                    if ($(e).parents('li').length === 0) {
                        //root
                        ref = 0;
                    } else {
                        ref = $(e).parents('li').data('id-category');
                    }
                } else {
                    position = 'after';
                    ref = $(e).prev('li').data('id-category');
                }
                $.ajax({
                    url: dropfilesRootUrl + "/index.php?option=com_dropfiles&task=categories.order&pk=" + pk + "&position=" + position + "&ref=" + ref + "&dragType=" + itemChangeType,
                    type: "POST"
                }).done(function (data) {
                    result = jQuery.parseJSON(data);
                    if (result.response === true) {
                        $.gritter.add({text: Joomla.JText._('COM_DROPFILES_JS_CATEGORY_ORDER', 'New category order saved!')});
                    } else {
                        bootbox.alert(result.response);
                    }
                });

            }
        });
        if (Dropfiles.collapse === true) {
            $('.nested').nestable('collapseAll');
        }
    }

    var ctrlDown = false;
    $(window).on("keydown", function (event) {
        if (event.which === 17 || event.ctrlKey || event.metaKey) {
            ctrlDown = true;
        }
    }).on("keyup", function (event) {
        ctrlDown = false;
    });

    //override Joomla.submitbutton
    var oldJoomlaSubmition = Joomla.submitbutton;
    var selectedFiles = [];
    var lastAction = '';
    var sourceCat = 0;
    Joomla.submitbutton = function ($task) {
        if ($task == 'files.copyfile' || $task == 'files.movefile') {

            if ($('#preview .file.selected').length == 0) {
                bootbox.alert(Joomla.JText._('COM_DROPFILES_JS_NO_FILES_SELETED', 'Please select file(s)'));
                return;
            }
            lastAction = $task;
            sourceCat = $('#dropfiles_upload_target_category').data('id-category');
            selectedFiles = [];
            $('#preview .file.selected').each(function (index) {
                selectedFiles.push($(this).data('id-file'));
            });
            if (lastAction == 'files.copyfile') {
                //do nothing
            } else {
                $('#preview .file.selected').css('opacity', '0.7');
            }

            var numberfiles = '<span class="dropfiles-number-files">' + $('#preview .file.selected').length + '</span>';
            var type = 'cut';
            if ($task == 'files.copyfile') {
                type = 'copy';
            } else if ($task == 'files.movefile') {
                type = 'cut';
            }
            $('.dropfiles-number-files').remove();

            $('#dropfiles-' + type).prepend(numberfiles);
        }
        else if ($task == 'files.paste') {
            if (selectedFiles.length == 0) {
                bootbox.alert(Joomla.JText._('COM_DROPFILES_JS_NO_FILES_COPIED_CUT', 'There is no copied/cut files yet'));
            }
            cat_target = $('#dropfiles_upload_target_category').data('id-category');
            if (cat_target != sourceCat) {
                countFiles = selectedFiles.length;
                iFile = 0;
                while (selectedFiles.length > 0) {
                    id_file = selectedFiles.pop();
                    $.ajax({
                        url: dropfilesRootUrl + "/index.php?option=com_dropfiles&task=" + lastAction + "&id_category=" + cat_target + '&active_category=' + sourceCat + '&id_file=' + id_file,
                        type: "POST"
                    }).done(function (data) {
                        iFile++;
                        result = jQuery.parseJSON(data);
                        if (result.response.onedrive_catkey !== undefined) {
                            checkCopyOnedrive(result.response.onedrive_catkey, result.response.location, cat_target);
                        } else {
                            if (iFile == countFiles) {
                                if (lastAction == 'files.copyfile') {
                                    $.gritter.add({text: Joomla.JText._('COM_DROPFILES_JS_FILES_COPIED', 'File(s) copied with success!')});
                                } else {
                                    $.gritter.add({text: Joomla.JText._('COM_DROPFILES_JS_FILES_MOVED', 'File(s) moved with success!')});
                                }

                                updatepreview(cat_target);
                            }
                        }
                    });
                }
            }
            $('.dropfiles-number-files').remove();
        } else if ($task == 'files.uncheck') {
            selectedFiles = [];
            $('.file').removeClass('selected');
            $('.dropfiles-btn-toolbar').find('#dropfiles-cut, #dropfiles-copy, #dropfiles-paste, #dropfiles-delete, #dropfiles-download, #dropfiles-uncheck').hide();
            $('.dropfiles-number-files').remove();
            showCategory();
        } else if ($task == 'files.delete') {
            bootbox.confirm(Joomla.JText._('COM_DROPFILES_JS_ARE_YOU_SURE_DELETE', 'Are you sure you want to delete the files you have selected') + '?', function (result) {
                if (result === true) {
                    sourceCat = $('#dropfiles_upload_target_category').data('id-category');
                    selectedFiles = [];
                    $('#preview .file.selected').each(function (index) {
                        selectedFiles.push({
                            'id_File': $(this).data('id-file'),
                            'id_CateRef': $(this).data('id-category'),
                        });
                    })
                    cat_target = $('#dropfiles_upload_target_category').data('id-category');
                    if (cat_target == sourceCat) {
                        while (selectedFiles.length > 0) {
                            selectedFile = selectedFiles.pop();
                            id_file = selectedFile.id_File;
                            id_cateRef = selectedFile.id_CateRef;
                            $.ajax({
                                url: dropfilesRootUrl + "/index.php?option=com_dropfiles&task=files.delete&id_file=" + id_file + "&id_cat=" + sourceCat + "&id_cate_ref=" + id_cateRef,
                                type: "POST"
                            }).done(function (data) {
                                result = jQuery.parseJSON(data);
                                if (result === true) {
                                    $('tr[data-id-file="' + id_file + '"]').fadeOut(500, function () {
                                        $(this).remove();
                                        $('.fileblock #fileparams').empty();
                                        updatepreview(cat_target);
                                        $.gritter.add({text: Joomla.JText._('COM_DROPFILES_JS_FILES_REMOVED', 'File(s) removed with success!')});
                                    });
                                } else {
                                    bootbox.alert(result.response);
                                }
                            });
                        }
                    }
                }
            });
            return false;
        } else if ($task == 'files.download') {
            $('#preview .file.selected').each(function (index) {
                var link = document.createElement("a");
                link.download = '';
                link.href = $(this).data('linkdownload');
                $('body').append(link);
                link.click();
                $(link).remove();
            });
        } else if($task == 'files.checkall') {
            $('.file').addClass('selected');
            $('.dropfiles-btn-toolbar').find('#dropfiles-cut, #dropfiles-copy, #dropfiles-paste, #dropfiles-delete, #dropfiles-download, #dropfiles-uncheck').show();
        }
        else {
            oldJoomlaSubmition($task);
        }

    }

    function showCategory() {
        $('.fileblock').fadeOut(function () {
            $('.categoryblock').fadeIn();
        });
        $('#insertfile').fadeOut(function () {
            $('#insertcategory').fadeIn();
        });
    }

    function showFile(e) {
        $('#singleimage').attr('src',$(e).attr('src'));
        $('.categoryblock').fadeOut(function () {
            $('.fileblock').fadeIn();
        });
        $('#insertcategory').fadeOut(function () {
            $('#insertfile').fadeIn();
        });
    }

    /**
     * Reload a category preview
     * @param id_category
     * @param id_file
     */
    function updatepreview(id_category, id_file, order, order_dir) {
        if (typeof(id_category) === "undefined" || id_category === null) {
            id_category = checkCateActive(id_category);
            if (typeof(id_category) === 'undefined') {
                $('#insertcategory').hide();
                return;
            }
            $('input[name=id_category]').val(id_category);
        } else {
            // $('#preview')
            id_category = checkCateActive(id_category);
        }
        loading('#wpreview');
        url = "/index.php?option=com_dropfiles&view=files&format=raw&id_category=" + id_category;
        if (typeof(order) === 'string') {
            url = url + '&orderCol=' + order;
        }
        if (order_dir === 'asc') {
            url = url + '&orderDir=desc';
        } else if (order_dir === 'desc') {
            url = url + '&orderDir=asc';
        }

        var oldCategoryAjax = categoryAjax;
        if (oldCategoryAjax !== null) {
            oldCategoryAjax.abort();
        }
        categoryAjax = $.ajax({
            url: dropfilesRootUrl + url,
            type: "POST"
        }).done(function (data) {
            $('#preview').contents().remove();
            $(data).hide().appendTo('#preview').fadeIn(200);
            rloading('#wpreview');
            if (selectedFiles.length == 0) {
                $('.dropfiles-btn-toolbar #dropfiles-cut').hide();
                $('.dropfiles-btn-toolbar #dropfiles-copy').hide();
                $('.dropfiles-btn-toolbar #dropfiles-paste').hide();
                $('.dropfiles-btn-toolbar #dropfiles-delete').hide();
                $('.dropfiles-btn-toolbar #dropfiles-download').hide();
                $('.dropfiles-btn-toolbar #dropfiles-uncheck').hide();
            }
            if (Dropfiles.can.edit || (Dropfiles.can.editown && Dropfiles.author === $('#dropfiles_upload_target_category').data('author'))
             || Dropfiles.can.upload) {
                var remote_file = (Dropfiles.addRemoteFile == 1) ? '<a href="" id="add_remote_file" class="btn btn-large btn-primary">' + Joomla.JText._('COM_DROPFILES_JS_ADD_REMOTE_FILE', 'Add remote file') + '</a> ' : '';
                $('<div id="dropbox" class="dropbox-upload"><span class="message">' + Joomla.JText._('COM_DROPFILES_JS_DROP_FILES_HERE', 'Drop files here to upload') + '</span><input class="hide" type="file" id="upload_input" multiple="">' + remote_file + '<span id="upload_button" class="btn btn-large btn-primary">' + Joomla.JText._('COM_DROPFILES_JS_SELECT_FILES', 'Select files') + '</span></div><div class="clr"></div>').appendTo('#preview');

                $('#add_remote_file').on('click', function (e) {
                    e.preventDefault();
                    var allowed = Dropfiles.allowedext;
                    allowed = allowed.split(',');
                    allowed.sort();
                    var allowed_select = '<select id="dropfiles-remote-type">';
                    $.each(allowed, function (i, v) {
                        allowed_select += '<option value="' + v + '">' + v + '</option>';
                    });
                    allowed_select += '</select>';

                    bootbox.dialog({
                        message: '<div class="form-horizontal dropfiles-remote-form"> ' +
                            '<div class="control-group"> ' +
                            '<label class=" control-label" for="dropfiles-remote-title">' + Joomla.JText._('COM_DROPFILES_JS_REMOTE_FILE_TITLE', 'title') + '</label> ' +
                            '<div class="controls"> ' +
                            '<input id="dropfiles-remote-title" name="dropfiles-remote-title" type="text" placeholder="' + Joomla.JText._('COM_DROPFILES_JS_REMOTE_FILE_TITLE', 'title') + '" class=""> ' +
                            '</div> ' +
                            '</div> ' +
                            '<div class="control-group"> ' +
                            '<label class="control-label" for="dropfiles-remote-url">' + Joomla.JText._('COM_DROPFILES_JS_REMOTE_FILE_REMOTE_URL', 'Remote URL') + '</label> ' +
                            '<div class="controls">' +
                            '<input id="dropfiles-remote-url" name="dropfiles-remote-url" type="text" placeholder="' + Joomla.JText._('COM_DROPFILES_JS_REMOTE_FILE_URL', 'URL') + '" class=""> ' +
                            '</div> </div>' +
                            '<div class="control-group"> ' +
                            '<label class="control-label" for="dropfiles-remote-type">' + Joomla.JText._('COM_DROPFILES_JS_REMOTE_FILE_TYPE', 'File Type') + '</label> ' +
                            '<div class="controls">' +
                            allowed_select +
                            '</div> </div>' +
                            '</div>',
                        buttons: {
                            save: {
                                "label": Joomla.JText._('COM_DROPFILES_JS_SAVE', 'Save'),
                                "className": "btn-primary",
                                "callback": function () {
                                    var category_id = $('input[name=id_category]').val();
                                    var remote_title = $('#dropfiles-remote-title');
                                    var remote_url = $('#dropfiles-remote-url');
                                    var remote_type = $('#dropfiles-remote-type');
                                    var ajax_url = "/index.php?option=com_dropfiles&task=files.addremoteurl&id_category=" + category_id + '&remote_title=' + remote_title.val() + '&remote_url=' + remote_url.val() + '&remote_type=' + remote_type.val();

                                    $.ajax({
                                        url: dropfilesRootUrl + ajax_url,
                                        type: "POST"
                                    }).done(function (data) {

                                        result = $.parseJSON(data);
                                        if (result.response === true) {
                                            updatepreview();
                                        } else {
                                            bootbox.alert(result.response);
                                        }
                                        $('.remote-dialog').remove();

                                    });
                                }
                            },
                            cancel: {
                                "label": Joomla.JText._('COM_DROPFILES_JS_CANCEL', 'Cancel'),
                                "className": "s",
                                "callback": function () {
                                    $('.remote-dialog').remove();
                                    $('.modal-backdrop').remove();
                                }
                            }
                        },
                        className: 'remote-dialog'
                    });

                    $('.dropfiles-remote-form').parents('.bootbox').addClass('dropfiles-upload-remote');

                    return false;
                });
            }
            $('#preview .restable').restable({
                type: 'hideCols',
                priority: {0: 'persistent', 1: 3, 2: 'persistent'},
                hideColsDefault: [4, 5]
            });

            var filehidecolumns = $.Event('dropfiles_file_hide_column_status');
            $(document).trigger(filehidecolumns);
            showhidecolumns();

            if (Dropfiles.can.edit || (Dropfiles.can.editown && Dropfiles.author === $('#dropfiles_upload_target_category').data('author'))
             || Dropfiles.can.upload) {
                initSortableFiles();
                $('#preview').sortable('enable');
                $('#preview').sortable('refresh');

            }
            initDeleteBtn();

            /** Init ordering **/
            $('#preview .restable thead a').click(function (e) {
                e.preventDefault();
                updatepreview(null, null, $(this).data('ordering'), $(this).data('direction'));
                if ($(this).data('direction') === 'asc') {
                    direction = 'desc';
                } else {
                    direction = 'asc';
                }
                $('#jform_params_ordering option[value="' + $(this).data('ordering') + '"]').attr('selected', 'selected').parent().animate({
                    'background-color': '#2196f3',
                    'color': '#fff',
                    'border': 'none',
                    'box-shadow': '1px 1px 12px #ccc'
                });
                $('#jform_params_orderingdir option[value="' + direction + '"]').attr('selected', 'selected').parent().animate({
                    'background-color': '#2196f3',
                    'color': '#fff',
                    'border': 'none',
                    'box-shadow': '1px 1px 12px #ccc'
                });
            });
            initFiles();


            $('#wpreview').unbind();
            Dropfiles.uploader.assignBrowse($('#upload_button'));
            Dropfiles.uploader.assignDrop($('#wpreview'));

            theme = $('input[name=theme]').val();
            $('#themeselect .themebtn').removeClass('selected');
            $('#themeselect a[data-theme=' + theme + ']').addClass('selected');

            if (typeof(id_file) !== "undefined" && id_file !== null) {
                $('#preview .file[data-id-file=' + id_file + ']').trigger('click');
            } else {
                showCategory();
                if (typeof(order) === 'undefined') {
                    $('.fileblock #fileparams').empty();
                }
            }

            if ($('.file.dropfiles-unpublished').length) {
                $('.file.dropfiles-unpublished').hide();
            }

            if ($('#dropbox').length) {
                var contents = '<div class="dropfiles-upload-message-section" ' +
                    'style="margin: 10px 0; max-width: 100%; padding: 0 25px; box-sizing: border-box; height: 50px;"></div>';
                var messages = $('#dropfiles_upload_messages').val();
                $('.dropfiles-upload-message-section').remove();
                $(contents).insertBefore($('#dropbox'));
                if (messages !== '') {
                    var code = '<div class="gritter-item"><a class="gritter-close" href="#" tabindex="1" style="display: none;">Close Notification</a><div class="gritter-without-image"><p>' + messages + '</p></div><div style="clear:both"></div></div>';
                    $('.dropfiles-upload-message-section').html(code);
                    $('.dropfiles-upload-message-section .gritter-item').fadeOut(5000);
                    $('#dropfiles_upload_messages').val('');
                    setTimeout(function () {
                        $('.dropfiles-upload-message-section').empty();
                    }, 5000);
                }
            }

            rloading('#wpreview');
            $('#mybootstrap #preview').trigger('dropfiles_preview_updated');
        });

        initDeleteBtn();
    }

    /** Init files **/
    function initFiles() {
        $(document).unbind('click.window').bind('click.window', function (e) {

            if ($(e.target).is('#rightcol') ||
                $(e.target).parents('#rightcol').length > 0 ||
                $(e.target).parents('#rightcol').length > 0 ||
                $(e.target).is('.modal-backdrop') ||
                $(e.target).parents('.bootbox.modal').length > 0 ||
                $(e.target).parents('.mce-container').length > 0 ||
                $(e.target).parents('.tagit-autocomplete').length > 0 ||
                $(e.target).parents('#toolbar-copy').length > 0 ||
                $(e.target).parents('#toolbar-scissors').length > 0 ||
                $(e.target).parents('.ui-datepicker-header').length > 0 ||
                $(e.target).parents('.calendar').length > 0 ||
                $(e.target).parents('.dropfiles-btn-toolbar').length > 0
            ) {
                return;
            }
            $('.fileblock #fileparams').empty();
            $('#preview .file').removeClass('selected');
            $('#preview .file').removeClass('first');
            $('#preview .file').removeClass('second');
            $('.dropfiles-btn-toolbar #dropfiles-cut').hide();
            $('.dropfiles-btn-toolbar #dropfiles-copy').hide();
            $('.dropfiles-btn-toolbar #dropfiles-paste').hide();
            $('.dropfiles-btn-toolbar #dropfiles-delete').hide();
            $('.dropfiles-btn-toolbar #dropfiles-download').hide();
            $('.dropfiles-btn-toolbar #dropfiles-uncheck').hide();
            showCategory();
        });

        $('#preview .file').unbind('click').click(function (e) {
            iselected = $(this).find('tr.selected').length;

            //Allow multiselect
            if (!e.ctrlKey && !ctrlDown && !e.shiftKey) {
                $('#preview .file.first').removeClass('first');
                $('#preview .file.second').removeClass('second');
                $(this).addClass('first');
                $('#preview .file.selected').removeClass('selected');
            } else if(e.shiftKey) {
                if($('#preview .file.first').length == 0) {
                    $(this).addClass('first');
                } else {
                    $('#preview .file.second').removeClass('second');
                    $(this).addClass('second');
                    var index1, index2;
                    $('#preview .file').each(function(index, elm) {
                        if ($(elm).hasClass('first')) {
                            index1 = index;
                        }
                        if ($(elm).hasClass('second')) {
                            index2 = index;
                        }
                    });
                    if (index1 < index2) {
                        $('#preview .file').each(function(index, elm) {
                            if (index >= index1 && index <= index2) {
                                $(elm).addClass('selected');
                            }
                        });
                    } else {
                        $('#preview .file').each(function(index, elm) {
                            if (index >= index2 && index <= index1) {
                                $(elm).addClass('selected');
                            }
                        });
                    }
                }
            }
            if (e.ctrlKey) {
                $('#preview .file.ctrl').removeClass('ctrl');
                $(this).addClass('ctrl');
                var indexctrl, indexcurrent;
                if($('#preview .file.first').length == 0) {
                    $(this).addClass('first');
                }
                $('#preview .file').each(function(index, elm) {
                    if ($(elm).hasClass('first')) {
                        indexctrl = index;
                    }
                    if ($(elm).hasClass('ctrl')) {
                        indexcurrent = index;
                    }
                });
                $('#preview .file').each(function(index, elm) {
                    if (index == indexcurrent && indexcurrent < indexctrl) {
                        $('#preview .file.first').removeClass('first');
                        $(elm).addClass('first');
                    }
                });

            }
            if (iselected === 0) {
                $(this).addClass('selected');
            }

            if ($('#preview .file.selected').length == 1) {
                showFile(this);
                $('.dropfiles-btn-toolbar #dropfiles-cut').show();
                $('.dropfiles-btn-toolbar #dropfiles-copy').show();
                $('.dropfiles-btn-toolbar #dropfiles-paste').show();
                $('.dropfiles-btn-toolbar #dropfiles-delete').show();
                $('.dropfiles-btn-toolbar #dropfiles-download').show();
                $('.dropfiles-btn-toolbar #dropfiles-uncheck').show();
            } else {
                showCategory();
            }
            e.stopPropagation();
        });
    }

    $(window).resize(function () {
        hideColumns();
    });

    //hide columns base on window size
    function hideColumns() {

        var w = $(window).width();
        if (w <= 1600 && w > 1440) {
            $('input[name="restable-toggle-cols"]').prop('checked', true);
            $('#restable-toggle-col-6-0,#restable-toggle-col-5-0').prop('checked', false);
        } else if (w <= 1440 && w > 1200) {
            $('input[name="restable-toggle-cols"]').prop('checked', true);
            $('#restable-toggle-col-6-0,#restable-toggle-col-5-0,#restable-toggle-col-4-0').prop('checked', false);
        } else if (w <= 1200 && w > 1024) {
            $('input[name="restable-toggle-cols"]').prop('checked', true);
            $('#restable-toggle-col-6-0,#restable-toggle-col-5-0,#restable-toggle-col-4-0,#restable-toggle-col-3-0').prop('checked', false);
        } else if (w <= 1024) {
            $('input[name="restable-toggle-cols"]').prop('checked', true);
            $('#restable-toggle-col-6-0,#restable-toggle-col-5-0,#restable-toggle-col-4-0,#restable-toggle-col-3-0,#restable-toggle-col-2-0').prop('checked', false);
        }
    }

    //show/hide columns base on cookie
    function showhidecolumns() {
        if (!localStorage.getItem('dropfilesFileColumnState')) {
            hideColumns();
            return;
        } else {
            $('.restable thead th').hide();
            $('.restable tbody td').hide();
            var colList = JSON.parse(localStorage.getItem('dropfilesFileColumnState'));
            $.each($('input[name="restable-toggle-cols"]'), function () {
                $(this).prop('checked', false);
            });
            $.each(colList, function (index, fieldset) {
                if (parseInt(fieldset.state) == 1) {
                    $('#' + fieldset.id).prop('checked', true);
                }
            });
            $.each($('input[name="restable-toggle-cols"]'), function () {
                if($(this).is(':checked')) {
                    var col = parseInt($(this).data('col')) + 1;
                }
                if (col) {
                    $('.restable thead th:nth-child(' + col + ')').show();
                    $('.restable tbody td:nth-child(' + col + ')').show();
                }
            });
        }
    }

    function initDeleteBtn() {
        $('.actions .trash').unbind('click').click(function (e) {
            that = this;
            bootbox.confirm(Joomla.JText._('COM_DROPFILES_JS_ARE_YOU_SURE', 'Are you sure') + '?', function (result) {
                if (result === true) {
                    //Delete file
                    id_file = $(that).parents('.file').data('id-file');
                    id_category = $('input[name=id_category]').val();
                    $.ajax({
                        url: dropfilesRootUrl + "/index.php?option=com_dropfiles&task=files.delete&id_file=" + id_file + "&id_cat=" + id_category,
                        type: "POST"
                    }).done(function (data) {
                        result = jQuery.parseJSON(data);
                        if (result === true) {
                            $(that).parents('.file').fadeOut(500, function () {
                                $(this).remove();
                            });
                        } else {
                            bootbox.alert(result.response);
                        }
                    });
                }
            });
            return false;
        });
    }

    function toMB(mb) {
        return mb * 1024 * 1024;
    }

    var allowedExt = Dropfiles.allowedext;
    if (typeof(allowedExt) === 'undefined') {
        allowedExt = '';
    }
    allowedExt = allowedExt.split(',');
    allowedExt.sort();
    // Init status functions
    Dropfiles.progressAdd = function (prgId, fileName, fileCatId) {
        var progressBar = '<div class="dropfiles_progress_block" data-id="' + prgId + '" data-cat-id="' + fileCatId + '">'
            + '<div class="dropfiles_progress_fileinfo">'
            + '<span class="dropfiles_progress_filename">' + fileName + '</span>'
            + '<span class="dropfiles_progress_cancel"></span>'
            + '<span class="dropfiles_progress_pause"></span>'
            + '</div>'
            + '<div class="dropfiles_process_full" style="display: block;">'
            + '<div class="dropfiles_process_run" id="' + prgId + '" data-w="0" style="width: 0%;"></div>'
            + '</div>'
            + '</div>';
        $('#preview table.restable').after(progressBar);
        $('#preview').find('.dropfiles_progress_block[data-id="' + prgId + '"] .dropfiles_progress_cancel').on('click', Dropfiles.progressInitCancel);
        $('#preview').find('.dropfiles_progress_block[data-id="' + prgId + '"] .dropfiles_progress_pause').on('click', Dropfiles.progressInitPause);

        var file = Dropfiles.uploader.getFromUniqueIdentifier(prgId);
        Dropfiles.uploader.updateQuery({
            id_category: fileCatId,
        });

        for (var num = 1; num <= Dropfiles.uploader.getOpt('simultaneousUploads'); num++) {
            if (typeof(file.chunks[num - 1]) !== 'undefined') {
                if (file.chunks[num - 1].status() === 'pending' && file.chunks[num - 1].preprocessState === 0) {
                    file.chunks[num - 1].send();
                }
            }
        }
    }
    Dropfiles.progressInitCancel = function (e) {
        e.stopPropagation();
        var $this = $(e.target);
        var progress = $this.parent().parent();
        var fileId = progress.data('id');
        var fileCatId = progress.data('cat-id');
        if (typeof(fileId) !== 'undefined') {
            // Bind
            var file = Dropfiles.uploader.getFromUniqueIdentifier(fileId);
            if (file !== false) {
                file.cancel();
                Dropfiles.progressUpdate(fileId, '0%');
            }
            progress.fadeOut('normal', function () {
                $(this).remove();
                // wpfd_status.close();
            });

            // todo: modify this to pause all uploading files
            if (Dropfiles.uploader.files.length === 0) {
                $('.dropfiles_progress_pause.all').fadeOut('normal', function () {
                    $(this).remove();
                });
            }

            $.ajax({
                url: dropfilesRootUrl + '/index.php?option=com_dropfiles&task=files.upload',
                method: 'POST',
                data: {
                    id_category: fileCatId,
                    deleteChunks: fileId
                },
                success: function (res, stt) {
                    if (res.response === true) {

                    }
                }
            });
        }
    }
    Dropfiles.progressInitPause = function (e) {
        e.stopPropagation();
        var $this = $(e.target);
        var progress = $this.parent().parent();
        var fileId = progress.data('id');
        if (fileId !== undefined) {
            // Bind
            var file = Dropfiles.uploader.getFromUniqueIdentifier(fileId);
            if (file !== false && file.isUploading()) {
                file.abort();
                file.pause(true); // This is very important or paused file will upload after this done
                // Init play button
                $this.addClass('paused');
                $this.text('');
                $this.css('color', 'green');
                Dropfiles.progressUpdate(fileId, Math.floor(file.progress() * 100) + '%');
                $this.unbind('click').on('click', Dropfiles.progressInitContinue);
            }

        }
    }
    Dropfiles.progressInitContinue = function (e) {
        e.stopPropagation();
        var $this = $(e.target);
        var progress = $this.parent().parent();
        var fileId = progress.data('id');
        if (fileId !== undefined) {
            // Bind
            var file = Dropfiles.uploader.getFromUniqueIdentifier(fileId);
            if (file !== false && !file.isUploading()) {
                for (var num = 1; num <= Dropfiles.uploader.getOpt('simultaneousUploads'); num++) {
                    for (var i = 0; i < file.chunks.length; i++) {
                        if (file.chunks[i].status() === 'pending' && file.chunks[i].preprocessState === 0) {
                            file.chunks[i].send();
                            file.pause(false); // This is very important or file will not start after paused!
                            break;
                        }
                    }
                }

                // Init pause button
                $this.removeClass('paused');
                $this.text('');
                $this.css('color', '#ff8000');
                $this.unbind('click').on('click', Dropfiles.progressInitPause);
            }
        }
    }
    Dropfiles.progressUpdate = function (prgId, value) {
        $('#preview').find('#' + prgId).css('width', value);
    }
    Dropfiles.progressDone = function (prgId) {
        var progress = jQuery('.dropfiles_progress_block[data-id="' + prgId + '"]');
        progress.find('.dropfiles_progress_cancel').addClass('uploadDone').unbind('click');
        progress.find('.dropfiles_progress_pause').css('visibility', 'hidden');
        progress.find('.dropfiles_progress_full').remove();
        setTimeout(function () {
            jQuery('.dropfiles_progress_block[data-id="' + prgId + '"]').fadeIn(300).hide(300, function () {
                jQuery(this).remove();
            });
        }, 1000);
    }
    // Init the uploader
    Dropfiles.uploader = new Resumable({
        target: dropfilesRootUrl + '/index.php?option=com_dropfiles&task=files.upload',
        query: {
            id_category: $('input[name=id_category]').val()
        },
        fileParameterName: 'file_upload',
        simultaneousUploads: 2,
        maxChunkRetries: 1,
        maxFileSize: toMB(Dropfiles.maxfilesize),
        maxFileSizeErrorCallback: function (file) {
            bootbox.alert(file.name + ' ' + Joomla.JText._('COM_DROPFILES_JS_FILE_TOO_LARGE', 'is too large') + '!');
        },
        chunkSize: Dropfiles.chunkSize,
        forceChunkSize: true,
        fileType: allowedExt,
        fileTypeErrorCallback: function (file) {
            bootbox.alert(file.name + ' cannot upload!<br/><br/>' + Joomla.JText._('COM_DROPFILES_CTRL_FILES_WRONG_FILE_EXTENSION'));
        },
        generateUniqueIdentifier: function (file, event) {
            var relativePath = file.webkitRelativePath || file.fileName || file.name;
            var size = file.size;
            var prefix = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
            return (prefix + size + '-' + relativePath.replace(/[^0-9a-zA-Z_-]/img, ''));
        }
    });

    if (!Dropfiles.uploader.support) {
        bootbox.alert(Joomla.JText._('COM_DROPFILES_JS_BROWSER_NOT_SUPPORT_HTML5', 'Your browser does not support HTML5 file uploads!'));
    }

    Dropfiles.uploader.on('filesAdded', function (files) {
        var categoryId = $('input[name=id_category]').val();
        files.forEach(function (file) {
            Dropfiles.progressAdd(file.uniqueIdentifier, file.fileName, categoryId);
        });
    });

    Dropfiles.uploader.on('fileProgress', function (file) {
        // $('.dropfiles_process_run#' + file.uniqueIdentifier).width(Math.floor(file.progress() * 100) + '%');
        Dropfiles.progressUpdate(file.uniqueIdentifier, Math.floor(file.progress() * 100) + '%');
    });

    Dropfiles.uploader.on('fileSuccess', function (file, res) {
        // $('.dropfiles_process_run#' + file.uniqueIdentifier).parent('.dropfiles_process_full').remove();
        Dropfiles.progressDone(file.uniqueIdentifier);
        var response = JSON.parse(res);
        if (typeof response.datas.id !== 'undefined') {
            $.ajax({
                url: dropfilesRootUrl + '/index.php?option=com_dropfiles&task=files.ftsIndex',
                method: 'POST',
                data: {id: response.datas.id},
            });
        }

        if (typeof(response) === 'string') {
            $('#dropfiles_upload_messages').val(response);
            return false;
        }

        if (response.response !== true) {
            $('#dropfiles_upload_messages').val(response.response);
            return false;
        }
    });

    Dropfiles.uploader.on('fileError', function (file, msg) {
        $.gritter.add({
            text: file.fileName + ' error while uploading!',
            class_name: 'error-msg'
        });
    });

    Dropfiles.uploader.on('complete', function () {
        $('#preview .progress').delay(300).fadeIn(300).hide(300, function () {
            $(this).remove();
        });
        $('#preview .uploaded').delay(300).fadeIn(300).hide(300, function () {
            $(this).remove();
        });
        $('#preview .file').delay(1200).show(1200, function () {
            $(this).removeClass('done placeholder');
        });

        updatepreview();
    });

    (function () {
        $('#patchHtaccess').click(function () {
            $.ajax({
                url: dropfilesRootUrl + '/index.php?option=com_dropfiles&view=patch&tmpl=component&format=raw'
            }).done(function (data) {
                bootbox.alert(data);
            });
        });
    })();

    function loading(e) {
        $(e).addClass('dploadingcontainer');
        $(e).append('<div class="dploading"></div>');
    }

    function rloading(e) {
        $(e).removeClass('dploadingcontainer');
        $(e).find('div.dploading').remove();
    }
});

//https://gist.github.com/ncr/399624
jQuery.fn.single_double_click = function (single_click_callback, double_click_callback, timeout) {
    return this.each(function () {
        var clicks = 0, self = this;
        jQuery(this).click(function (event) {
            clicks++;
            if (clicks == 1) {
                setTimeout(function () {
                    if (clicks == 1) {
                        single_click_callback.call(self, event);
                    } else {
                        double_click_callback.call(self, event);
                    }
                    clicks = 0;
                }, timeout || 300);
            }
        });
    });
}

var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

window.multipleUser = {};

window.multipleUser.setValue = function(value, name) {
    var $ = jQuery.noConflict();
    var $input = $("#jform_canview_id");
    var $inputName = $("#jform_canview");
    var oldValue = $input.val();
    var oldName = $inputName.val();
    if (oldValue === '0' || oldValue === '') {
        $input.val(value).trigger('change');
        $inputName.val(name || value).trigger('change');
    } else {
        var newValue = oldValue.split(',');
        var newName = oldName.split(',');
        newValue.push(value);
        newName.push(name);
        $input.val(newValue.unique().join(',')).trigger('change');
        $inputName.val(newName.unique().join(',')).trigger('change');
    }
};

window.multipleUser.unsetValue = function(value, name) {
    var $ = jQuery.noConflict();
    var $input = $("#jform_canview_id");
    var $inputName = $("#jform_canview");
    var oldValue = $input.val().split(',');
    var oldName = $inputName.val().split(',');

    if (oldValue.length === 0) {
        $input.val(0).trigger('change');
        $inputName.val('').trigger('change');
    } else {
        var newValue = $.grep(oldValue, function(item, index) {
            return item.toString() !== value.toString();
        });
        var newName = $.grep(oldName, function(item, index) {
            return item.toString() !== name.toString();
        });

        $input.val(newValue.unique().join(',')).trigger('change');
        $inputName.val(newName.unique().join(',')).trigger('change');
    }
};

/**
 * Array unique function from
 * Thanks to ShAkKiR from https://stackoverflow.com/a/44376705
 * @returns {Array}
 */
Array.prototype.unique = function() {
    var a = [];
    for (i = 0; i < this.length; i++) {
        var current = this[i];
        if (a.indexOf(current) < 0) a.push(current);
    }
    return a;
}