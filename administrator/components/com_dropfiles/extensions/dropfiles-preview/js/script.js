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
 */

jQuery(document).ready(function ($) {

    var preview_hash = window.location.hash;
    var preview_cParents = {};
    var preview_tree = $('.dropfiles-foldertree-preview');
    var sourcefile = $("#dropfiles-template-preview-box").html();
    sourcefile = fixJoomlaSef(sourcefile);

    var preview_root_cat = $('.dropfiles-content-preview').data('category');

    $(".dropfiles-content-preview").each(function (index) {
        var preview_topCat = $(this).data('category');
        preview_cParents[preview_topCat] = {};
        preview_cParents[preview_topCat][preview_topCat] = {parent_id: 0, id: preview_topCat, title: $(this).data("category-name")};
        $(this).find(".dropfilescategory.catlink").each(function (index) {
            var tempidCat = $(this).data('idcat');
            preview_cParents[preview_topCat][tempidCat] = {parent_id: preview_topCat, id: tempidCat, title: $(this).text()};
        });
        initInputSelected(preview_topCat);
        initDownloadSelected(preview_topCat);
    });

    Handlebars.registerHelper('bytesToSize', function (bytes) {
        return bytesToSize(bytes);
    });

    Handlebars.registerHelper('isGGExt', function (ext, options) {
        if (dropfilesGVExt.indexOf(ext) >= 0) {
            return options.fn(this);
        }
    });

    Handlebars.registerHelper('encodeURI', function (uri) {
        res = encodeURIComponent(uri);
        return res;
    });

    function initInputSelected(sc) {
        $(document).on('change', ".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sc + "] input.cbox_file_download", function () {
            var rootCat = ".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sc + "]";
            var selectedFiles = $(rootCat + " input.cbox_file_download:checked");
            var filesId = [];
            if (selectedFiles.length) {
                selectedFiles.each(function (index, file) {
                    filesId.push($(file).data('id'));
                });
            }
            if (filesId.length > 0) {
                $(rootCat + " .dropfilesSelectedFiles").remove();
                $('<input type="hidden" class="dropfilesSelectedFiles" value="' + filesId.join(',') + '" />')
                    .insertAfter($(rootCat).find(" #current_category_slug"));
                hideDownloadAllBtn(sc, true);
                $(rootCat + " .preview-download-selected").remove();
                if ($(rootCat).find('.breadcrumbs').length) {
                    var downloadSelectedBtn = $('<a href="javascript:void(0);" class="preview-download-selected download-selected" style="display: block;"><span class="btn-title">' + Joomla.JText._('COM_DROPFILES_DOWNLOAD_SELECTED', 'Download selected') + '</span><i class="zmdi zmdi-check-all dropfiles-download-category"></i></a>');
                    downloadSelectedBtn.appendTo($(rootCat).find(".breadcrumbs.dropfiles-breadcrumbs-preview"));
                } else {
                    var downloadSelectedBtn = $('<a href="javascript:void(0);" class="preview-download-selected download-selected" style="display: block;"><span class="btn-title">' + Joomla.JText._('COM_DROPFILES_DOWNLOAD_SELECTED', 'Download selected') + '</span><i class="zmdi zmdi-check-all dropfiles-download-category"></i></a>');
                    downloadSelectedBtn.insertAfter($(rootCat).find(" #current_category_slug"));
                }
            } else {
                $(rootCat + " .dropfilesSelectedFiles").remove();
                $(rootCat + " .preview-download-selected").remove();
                hideDownloadAllBtn(sc, false);
            }
        });
    }

    function hideDownloadAllBtn(sc, hide) {
        var rootCat = ".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sc + "]";
        var downloadCatButton = $(rootCat + " .preview-download-category");
        var selectFileInputs = $(rootCat + " input.cbox_file_download");

        if (downloadCatButton.length === 0) {
            if (selectFileInputs.length > 0) {
                if ($(rootCat).find('.breadcrumbs').length) {
                    var downloadAllBtn = $('<a href="javascript:void(0);" class="preview-download-category download-all" style="display: block;"><span class="btn-title">' + Joomla.JText._('COM_DROPFILES_DOWNLOAD_ALL', 'Download all') + '</span><i class="zmdi zmdi-check-all"></i></a>');
                    downloadAllBtn.prependTo($(rootCat).find(".breadcrumbs.dropfiles-breadcrumbs-preview"));
                } else {
                    var downloadAllBtn = $('<a href="javascript:void(0);" class="preview-download-category download-all" style="display: block;"><span class="btn-title">' + Joomla.JText._('COM_DROPFILES_DOWNLOAD_ALL', 'Download all') + '</span><i class="zmdi zmdi-check-all"></i></a>');
                    downloadAllBtn.insertAfter($(rootCat).find(" #current_category_slug"));
                }
            } else {
                return;
            }
        } else {
            if (selectFileInputs.length === 0) {
                downloadCatButton.remove();
                return;
            }
        }

        if (hide) {
            $(rootCat + " .preview-download-category").hide();
        } else {
            $(rootCat + " .preview-download-category").show();
        }
    }

    function initDownloadSelected(sc) {
        var rootCat = ".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sc + "]";
        $(document).on('click', rootCat + ' .preview-download-selected', function () {
            if ($(rootCat).find('.dropfilesSelectedFiles').length > 0) {
                var current_category = $(rootCat).find('#current_category').val();
                var category_name = $(rootCat).find('#current_category_slug').val();
                var selectedFilesId = $(rootCat).find('.dropfilesSelectedFiles').val();
                $.ajax({
                    url: dropfilesBaseUrl + "index.php?option=com_dropfiles&task=frontfile.zipSeletedFiles&filesId=" + selectedFilesId + "&dropfiles_category_id=" + current_category,
                    dataType: "json",
                }).done(function (results) {
                    if (results.status === 'success') {
                        var hash = results.hash;
                        window.location.href = dropfilesBaseUrl + "index.php?option=com_dropfiles&task=frontfile.downloadZipedFile&hash=" + hash + "&dropfiles_category_id=" + current_category + "&dropfiles_category_name=" + category_name;
                    } else {
                        alert(results.message);
                    }
                })
            }
        });
    }

    initClickFile();

    preview_hash = preview_hash.replace('#', '');
    if (preview_hash != '') {
        var hasha = preview_hash.split('-');
        var re = new RegExp("^(p[0-9]+)$");
        var page = null;
        var stringpage = hasha.pop();

        if (re.test(stringpage)) {
            page = stringpage.replace('p', '');
        }

        var hash_category_id = hasha[1];
        var hash_sourcecat = hasha[0];

        if (parseInt(hash_category_id) > 0 || hash_category_id === 'all_0') {
            if (hash_category_id == 'all_0') {
                hash_category_id = 0;
            }
            setTimeout(function () {
                load(hash_sourcecat, hash_category_id, page, true);
            }, 100);
        }
    }

    function preview_initClick() {
        $('.dropfiles-content-preview.dropfiles-content-multi .catlink').click(function (e) {
            e.preventDefault();
            load($(this).parents('.dropfiles-content-preview.dropfiles-content-multi').data('category'), $(this).data('idcat'), null);
        });
    }

    preview_initClick();
    initManageFile($('.dropfiles-content-preview.dropfiles-content-multi .catlink').parents('.dropfiles-content-preview.dropfiles-content-multi').data('category'));

    function initClickFile() {
        $('.dropfiles-content-preview.dropfiles-content .dropfiles-file-link').unbind('click').click(function (e) {
            var href = $(this).attr('href');
            if (href !== '#') {
                return;
            }
            e.preventDefault();
            fileid = $(this).data('id');
            catid = $(this).parents(".dropfiles-content-preview").data('current');
            $.ajax({
                url: dropfilesBaseUrl + "index.php?option=com_dropfiles&view=frontfile&format=json&id=" + fileid + "&catid=" + catid,
                dataType: "json",
                beforeSend: function() {
                    // setting a timeout
                    if($('body').has('dropfiles-preview-box-loader') !== true) {
                        $('body').append('<div class="dropfiles-preview-box-loader"></div>');
                    }
                }
            }).done(function (file) {
                var template = Handlebars.compile(sourcefile);
                var html = template(file);
                box = $("#dropfiles-box-preview");
                $('.dropfiles-preview-box-loader').each(function () {
                    $(this).remove();
                });
                if (box.length === 0) {
                    $('body').append('<div id="dropfiles-box-preview" style="display: none;"></div>');
                    box = $("#dropfiles-box-preview");
                }
                box.empty();
                box.prepend(html);
                box.click(function (e) {
                    if ($(e.target).is('#dropfiles-box-preview')) {
                        box.hide();
                    }
                    $('#dropfiles-box-preview').unbind('click.box').bind('click.box', function (e) {
                        if ($(e.target).is('#dropfiles-box-preview')) {
                            box.hide();
                        }
                    });
                });
                $('#dropfiles-box-preview .dropfiles-close').click(function (e) {
                    e.preventDefault();
                    box.hide();
                });
                if (typeof(dropfilesColorboxInit) !== 'undefined') {
                    dropfilesColorboxInit();
                }
                box.show();

                dropblock = box.find('.dropblock');
                if ($(window).width() < 400) {
                    dropblock.css('margin-top', '0');
                    dropblock.css('margin-left', '0');
                    dropblock.css('top', '0');
                    dropblock.css('left', '0');
                    dropblock.height($(window).height() - parseInt(dropblock.css('padding-top'), 10) - parseInt(dropblock.css('padding-bottom'), 10));
                    dropblock.width($(window).width() - parseInt(dropblock.css('padding-left'), 10) - parseInt(dropblock.css('padding-right'), 10));
                } else {
                    dropblock.css('margin-top', (-(dropblock.height() / 2) - 20) + 'px');
                    dropblock.css('margin-left', (-(dropblock.width() / 2) - 20) + 'px');
                    dropblock.css('height', '');
                    dropblock.css('width', '');
                    dropblock.css('top', '');
                    dropblock.css('left', '');
                }
            });
        });
    }

    function load(sourcecat, category, page, reload = false) {
        var pathname = window.location.pathname;
        var container = $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "]");
        if (container.length == 0) {
            return;
        }
        $(document).trigger('dropfiles:category-loading');
        $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "]").find('#current_category').val(category);
        $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] .dropfiles-container-preview").empty();
        $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] .dropfiles-container-preview").html($('#dropfiles-loading-wrap').html());

        //Get categories
        $.ajax({
            url: dropfilesBaseUrl + "index.php?option=com_dropfiles&view=frontcategories&format=json&id=" + category + "&top=" + sourcecat,
            dataType: "json"
        }).done(function (categories) {
            if (page != null) {
                window.history.pushState('', document.title, pathname + '#' + sourcecat + '-' + category + '-' + categories.category.alias + '-p' + page);
            } else {
                window.history.pushState('', document.title, pathname + '#' + sourcecat + '-' + category + '-' + categories.category.alias);
            }
            $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "]").find('#current_category_slug').val(categories.category.alias);

            var sourcecategories = $("#dropfiles-template-preview-categories-"+sourcecat).html();
            var template = Handlebars.compile(sourcecategories);
            var html = template(categories);
            $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "]").data('current', category);
            if ($(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] .dropfiles-container-preview .dropfiles-categories").length) {
                $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] .dropfiles-container-preview .dropfiles-categories").remove();
            }
            $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] .dropfiles-container-preview").prepend(html);

            for (i = 0; i < categories.categories.length; i++) {
                preview_cParents[sourcecat][categories.categories[i].id] = categories.categories[i];
            }

            if (reload) {
                preview_breadcrum(sourcecat, category, true);
            } else {
                // Store cParents
                localStorage.setItem('dropfiles_preview_cParents_' + sourcecat, JSON.stringify(preview_cParents));
                preview_breadcrum(sourcecat, category);
            }

            preview_initClick();
            initManageFile(sourcecat);
            if (preview_tree.length) {
                var currentTree = container.find('.dropfiles-foldertree-preview');
                currentTree.find('li').removeClass('selected');
                currentTree.find('i.zmdi').removeClass('zmdi-folder').addClass("zmdi-folder");

                currentTree.jaofoldertree('open', category, currentTree);

                var el = currentTree.find('a[data-file="' + category + '"]').parent();
                el.find(' > i.zmdi').removeClass("zmdi-folder").addClass("zmdi-folder");

                if (!el.hasClass('selected')) {
                    el.addClass('selected');
                }

            }

        });

        //Get files
        var getFilesAjaxUrl = dropfilesBaseUrl + "index.php?option=com_dropfiles&view=frontfiles&format=json&id=" + category;
        if (page != null) {
            getFilesAjaxUrl += "&page=" + page;
        }
        $.ajax({
            url: getFilesAjaxUrl,
            dataType: "json"
        }).done(function (content) {
            var sourcefiles = $("#dropfiles-template-preview-files-" + sourcecat).html();
            sourcefiles = fixJoomlaSef(sourcefiles);
            var template = Handlebars.compile(sourcefiles);
            var html = template(content);

            if ($(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] .dropfiles-container-preview .dropfiles_list").length) {
                $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] .dropfiles-container-preview .dropfiles_list").remove();
            }

            $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] .dropfiles-container-preview").append(html);

            // View files
            if (typeof (content.fileview) !== 'undefined' && content.fileview.length) {
                content.fileview.forEach(function (viewFile) {
                    var preview_dropblock = $(".dropfiles-content-preview[data-category=" + sourcecat + "] .dropfiles-container-preview .dropfiles-file-link[data-id='"+ viewFile.id +"'] .dropblock");
                    if (viewFile.view === true) {
                        preview_dropblock.css({'background-image': 'url('+ viewFile.link +')'});
                        preview_dropblock.addClass(viewFile.view_class);
                    }
                });
            }

            initClickFile();
            if (typeof(dropfilesColorboxInit) !== 'undefined') {
                dropfilesColorboxInit();
            }
            dropfiles_remove_loading($(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] .dropfiles-container-preview"));
            $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] .dropfilesSelectedFiles").remove();
            $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] .preview-download-selected").remove();
            $.ajax({
                url: dropfilesBaseUrl + "index.php?option=com_dropfiles&task=category.isCloudCategory&id_category=" + category,
                dataType: "json"
            }).done(function (result) {
                if (result.status === 'true') {
                    hideDownloadAllBtn(sourcecat, true);
                } else {
                    hideDownloadAllBtn(sourcecat, false);
                }

                if ($(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] #current-category-link").length) {
                    var current_download_link = $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] #current-category-link").val().toLowerCase();
                    if ($(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] .preview-download-category").length) {
                        var root_download_link = $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] .preview-download-category").attr('href').toLowerCase();
                        if (current_download_link !== root_download_link) {
                            $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "] .preview-download-category").attr('href', current_download_link);
                        }
                    }
                }
            });

            // Pagination initial
            $('.dropfiles-content-preview.dropfiles-content-multi[data-category=' + sourcecat + '] + .dropfiles-pagination').remove();
            if (typeof (content.pagination) !== 'undefined') {
                $('.dropfiles-content-preview.dropfiles-content-multi[data-category=' + sourcecat + ']').after(content.pagination);
                delete content.pagination;
            }
            preview_init_pagination($('.dropfiles-content-preview.dropfiles-content-multi[data-category=' + sourcecat + '] + .dropfiles-pagination'));
        });
        $(document).trigger('dropfiles:category-loaded');
    }

    function preview_breadcrum(sourcecat, catid, reload = false) {
        links = [];

        if (reload) {
            var cParentList = localStorage.getItem('dropfiles_preview_cParents_' + sourcecat);
            cParentList = cParentList ? JSON.parse(localStorage.getItem('dropfiles_preview_cParents_' + sourcecat)) : null;
            if (cParentList !== null) {
                preview_cParents = cParentList;
            }
        }

        current_Cat = preview_cParents[sourcecat][catid];

        if (typeof (current_Cat) == 'undefined') {
            // todo: made breadcrumb working when reload page with hash
            return;
        }

        links.unshift(current_Cat);
        if (current_Cat.parent_id != 0) {
            while (preview_cParents[sourcecat][current_Cat.parent_id]) {
                current_Cat = preview_cParents[sourcecat][current_Cat.parent_id];
                links.unshift(current_Cat);
            }
        }

        let html = '';
        for (i = 0; i < links.length; i++) {
            if (i < links.length - 1) {
                html += '<li><a class="catlink" data-idcat="' + links[i].id + '" href="javascript:void(0)">' + links[i].title + '</a><span class="divider"> &gt; </span></li>';
            } else {
                html += '<li><span>' + links[i].title + '</span></li>';
            }
        }
        $(".dropfiles-content-preview[data-category=" + sourcecat + "] .dropfiles-breadcrumbs-preview li").remove();
        $(".dropfiles-content-preview[data-category=" + sourcecat + "] .dropfiles-breadcrumbs-preview").append(html);

    }

    // Pagination actions on ready
    $('.dropfiles-content-preview + .dropfiles-pagination').each(function (index, elm) {
        var $this = $(elm);
        preview_init_pagination($this);
    });

    function preview_init_pagination($this) {
        var number = $this.find('a:not(.current)');
        var wrap = $this.prev('.dropfiles-content-preview');
        var sourcecat = wrap.data('category');
        var current_category = wrap.find('#current_category').val();

        number.unbind('click').bind('click', function () {
            var page_number = $(this).attr('data-page');
            var current_sourcecat = $(this).parent().prev('.dropfiles-content').attr('data-category');
            var wrap = $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + current_sourcecat + "]");
            var current_category = $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + current_sourcecat + "]").find('#current_category').val();
            if (typeof page_number !== 'undefined') {
                var pathname = window.location.href.replace(window.location.hash, '');
                var category = $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + current_sourcecat + "]").find('#current_category').val();
                var category_slug = $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + current_sourcecat + "]").find('#current_category_slug').val();
                var ordering = $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + current_sourcecat + "]").find('#current_ordering_' + current_sourcecat).val();
                var orderingDirection = $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + current_sourcecat + "]").find('#current_ordering_direction_' + current_sourcecat).val();
                var page_limit = $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + current_sourcecat + "]").find('#page_limit_' + current_sourcecat).val();

                window.history.pushState('', document.title, pathname + '#' + current_sourcecat + '-' + category + '-dropfiles-' + category_slug + '-p' + page_number);

                $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + current_sourcecat + "] .dropfiles-container-preview .dropfiles_list").remove();
                $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + current_sourcecat + "] .dropfiles-container-preview").append($('#dropfiles-loading-wrap').html());

                var params = $.param({
                    id: current_category,
                    rootcat: current_sourcecat,
                    page: page_number,
                    orderCol: ordering,
                    orderDir: orderingDirection,
                    page_limit: page_limit
                });

                // Get files
                $.ajax({
                    url: dropfilesBaseUrl + 'index.php?option=com_dropfiles&view=frontfiles&format=json&' + params,
                    dataType: "json",
                    beforeSend: function () {
                        $('html, body').animate({scrollTop: $(".dropfiles-content[data-category=" + current_sourcecat + "]").offset().top}, 'fast');
                    }
                }).done(function (content) {
                    delete content.category;
                    wrap.next('.dropfiles-pagination').remove();
                    wrap.after(content.pagination);
                    delete content.pagination;
                    var sourcefiles = $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + current_sourcecat + "]").parent().find("#dropfiles-template-preview-files-"+current_sourcecat).html();
                    var template = Handlebars.compile(sourcefiles);
                    var html = template(content);
                    $(".dropfiles-content-preview[data-category=" + current_sourcecat + "] .dropfiles-container-preview").append(html);

                    initClickFile();

                    if (typeof dropfilesColorboxInit !== 'undefined') {
                        dropfilesColorboxInit();
                    }

                    preview_init_pagination(wrap.next('.dropfiles-pagination'));
                    dropfiles_remove_loading($(".dropfiles-content-preview[data-category=" + current_sourcecat + "] .dropfiles-container-preview"));
                });
            }
        });
    }

    if (preview_tree.length) {
        preview_tree.each(function () {
            var preview_topCat = $(this).parents('.dropfiles-content-preview.dropfiles-content-multi').data('category');
            var rootCatName = $(this).parents('.dropfiles-content-preview.dropfiles-content-multi').data('category-name');

            $(this).jaofoldertree({
                script: dropfilesBaseUrl + 'index.php?option=com_dropfiles&task=frontfile.getSubs&tmpl=component',
                usecheckboxes: false,
                root: preview_topCat,
                showroot: rootCatName,
                onclick: function (elem, file) {
                    preview_topCat = $(elem).parents('.dropfiles-content-preview.dropfiles-content-multi').data('category');
                    if (preview_topCat != file) {

                        $('.directory', $(elem).parents('.dropfiles-content-preview.dropfiles-content-multi')).each(function() {
                            if (!$(this).hasClass('selected') && $(this).find('> ul > li').length === 0) {
                                $(this).removeClass('expanded').addClass('collapsed');
                            }
                        });

                        $(elem).parents('.directory').each(function () {
                            var $this = $(this);
                            var category = $this.find(' > a');
                            var parent = $this.find('.icon-open-close');
                            if (parent.length > 0) {
                                if (typeof preview_cParents[category.data('file')] == 'undefined') {
                                    preview_cParents[category.data('file')] = {
                                        parent_id: parent.data('parent_id'),
                                        id: category.data('file'),
                                        title: category.text()
                                    };
                                }
                            }
                        });

                    }
                    load(preview_topCat, file, null);
                },
                onChanges: function (folderTree, newFolders) {
                    var sourcecat = $(folderTree).parents('.dropfiles-content-preview').attr('data-category');
                    if (newFolders.length !== 0) {
                        $.ajax({
                            url: dropfilesBaseUrl + "index.php?option=com_dropfiles&view=frontcategories&format=json&id=" + newFolders[0].parent_id + "&top=" + sourcecat,
                            dataType: "json"
                        }).done(function (categories) {
                            for (i = 0; i < categories.categories.length; i++) {
                                preview_cParents[sourcecat][categories.categories[i].id] = categories.categories[i];
                            }
                        });
                    }
                }
            });
        })
    }

    function initManageFile(sourcecat) {
        if (typeof sourcecat == 'undefined') {
            sourcecat = $('.dropfiles-content-preview.dropfiles-content-multi').data('category');
        }
        var current_category = $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "]").find('#current_category').val();
        var link_manager = $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "]").find('.openlink-manage-files').data('urlmanage');
        link_manager = link_manager + '&task=site_manage&site_catid=' + current_category + '&tmpl=dropfilesfrontend';
        $(".dropfiles-content-preview.dropfiles-content-multi[data-category=" + sourcecat + "]").find('.openlink-manage-files').attr('href', link_manager);
    }

    // Remove the root url in case it's added by Joomla Sef plugin
    function fixJoomlaSef(template) {
        if (typeof template != 'undefined' && template != null) {
            var reg = new RegExp(dropfilesRootUrl + "{{", 'g');
            template = template.replace(reg, "{{");
        }

        return template;
    }

});
