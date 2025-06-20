function export_excel(fnums, letter) {
    var eltJson = {};
    var i = 0;
    var objclass = [];

    var code = $('#em-export-prg').val().replace(/\s/g, '');
    var year = '';

    let campaign = document.getElementById('em-export-camp');
    let selectedOption = campaign.options[campaign.selectedIndex];

    if (selectedOption.value != '0') {
        year = selectedOption.getAttribute('data-year');
    }
    const excel_file_name = code + '_' + year;

    $('[class^="emundusitem"]:checkbox:checked').each(function () {
        if ($(this).attr('class') == 'emundusitem_evaluation otherForm') {
            objclass.push($(this).attr('class'));
        }
    });
    objclass = $.unique(objclass);

    let defaultElts = [2540, 2754, 1906, 7056, 7057, 7068];
    let stepElements = {};

    $('.em-export-item').each(function () {
        let id = $(this).attr('id').split('-')[0];
        if (!defaultElts.includes(parseInt(id))) {
            if ($(this).attr('data-step-id') && $(this).attr('data-step-id') != 0) {
                stepElements[$(this).attr('data-step-id')] = stepElements[$(this).attr('data-step-id')] || [];
                stepElements[$(this).attr('data-step-id')].push(id);
            } else {
                eltJson[i] = $(this).attr('id').split('-')[0];
            }
            i++;
        }
    });

    eltJson = JSON.stringify(eltJson);
    var objJson = {};

    i = 0;
    $('.em-ex-check:checked').each(function () {
        objJson[i] = $(this).attr('value');
        i++;
    });
    objJson = JSON.stringify(objJson);

    var methode = $('input[name=em-export-methode]:checked').val();

    var options = {};
    i = 0;
    $('.em-ex-check0:checked').each(function () {
        options[i] = $(this).attr('value');
        i++;
    });
    options = JSON.stringify(options);

    $('#data').hide();

    $('div').remove('#chargement');

    var swal_container_class = '';
    var swal_popup_class = '';
    var swal_actions_class = '';

    var html = '<div id="chargement" style="text-align: center">' +
        '<div id="extractstep"><p>' + Joomla.Text._('COM_EMUNDUS_CREATE_CSV') + '</p></div>' +
        '</div>';

    Swal.fire({
        title: Joomla.Text._('COM_EMUNDUS_EXCEL_GENERATION'),
        html: html,
        showCancelButton: false,
        showCloseButton: false,
        reverseButtons: true,
        confirmButtonText: Joomla.Text._('COM_EMUNDUS_ONBOARD_OK'),
        cancelButtonText: Joomla.Text._('COM_EMUNDUS_ONBOARD_CANCEL'),
        customClass: {
            container: 'em-modal-actions ' + swal_container_class,
            popup: swal_popup_class,
            title: 'em-swal-title',
            cancelButton: 'em-swal-cancel-button',
            confirmButton: 'em-swal-confirm-button btn btn-success',
            actions: swal_actions_class
        },
    });
    document.querySelector('.em-swal-confirm-button').style.opacity = '0';

    $.ajax({
        type: 'post',
        url: 'index.php?option=com_emundus&controller=files&task=getfnums_csv&Itemid='+itemId,
        dataType: 'JSON',
        data: {fnums: fnums},
        success: function (result) {
            var totalfile = result.totalfile;

            if (result.status && totalfile > 0) {
                $.ajax({
                    type: 'post',
                    url: 'index.php?option=com_emundus&controller=files&task=create_file_csv',
                    dataType: 'JSON',
                    success: function (result) {
                        if (result.status) {
                            $('#extractstep').replaceWith('<div id="extractstep"><div id="addatatext"><p>' + Joomla.Text._('COM_EMUNDUS_ADD_DATA_TO_CSV') + '</p></div><div id="datasbs"</div>');
                            const json = {
                                start: 0,
                                limit: 100,
                                totalfile: totalfile,
                                nbcol: 0,
                                methode: methode,
                                file: result.file,
                                excelfilename: excel_file_name
                            };

                            if (Number(methode) === 0 && $('#view').val() !== 'evaluation') {
                                $('#datasbs').replaceWith('<div id="datasbs" data-start="0"><p>0 / ' + totalfile + '</p></div>');
                            } else {
                                $('#datasbs').replaceWith('<div id="datasbs" data-start="0"><p>0</p></div>');
                            }
                            generate_csv(json, eltJson, objJson, options, objclass, letter, stepElements);
                        } else {
                            $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">' + Joomla.Text._(result.msg) + '</div>');
                        }
                    },
                    error: function (jqXHR) {
                        $('#loadingimg').empty();
                        $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">' + jqXHR.responseText + '</div>');
                    }
                });
            } else {
                $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">' + Joomla.Text._('COM_EMUNDUS_NO_DATA') + '</div>');
            }
        },
        error: function (jqXHR) {
            $('#loadingimg').empty();
            $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">' + jqXHR.responseText + '</div>');
        }
    });
}

function generate_csv(json, eltJson, objJson, options, objclass, letter, stepElements) {
    const maxcsv = 65000;
    const maxxls = 65000;
    var start = json.start;
    var limit = json.limit;
    var totalfile = json.totalfile;
    var file = json.file;
    var nbcol = json.nbcol;
    var methode = json.methode;

    $.ajaxQ.abortAll();

    if (start+limit <= maxcsv) {
        $.ajax({
            type: 'post',
            url: 'index.php?option=com_emundus&controller=files&task=generate_array',
            dataType: 'JSON',
            data: {
                file: file,
                totalfile: totalfile,
                start: start,
                limit: limit,
                nbcol: nbcol,
                methode: methode,
                elts: eltJson,
                step_elts: stepElements,
                objs: objJson,
                opts: options,
                objclass: objclass,
                excelfilename:json.excelfilename
            },
            success: function(result) {
                var json = result.json;
                if (result.status) {
                    if ((methode == 0) && ($('#view').val() != "evaluation")) {
                        $('#datasbs').replaceWith('<div id="datasbs" data-start="' + result.json.start + '"><p>' + result.json.start + ' / ' + result.json.totalfile + '</p></div>');
                    } else {
                        $('#datasbs').replaceWith('<div id="datasbs" data-start="' + result.json.start + '"><p>' + result.json.start + '</p></div>');

                    }
                    if (start != json.start && totalfile > json.start) {
                        generate_csv(json, eltJson, objJson, options, objclass, letter);
                    } else {
                        $('#extractstep').replaceWith('<div id="extractstep"><p>' + Joomla.Text._('COM_EMUNDUS_XLS_GENERATION') + '</p></div>');
                        let csv_only = false;
                        if(options) {
                            const options_array = JSON.parse(options);
                            if(options_array && Object.values(options_array).includes("form-csv-only")) {
                                csv_only = true;
                            }
                        }

                        if(!csv_only) {
                            $.ajax(
                                {
                                    type: 'post',
                                    url: 'index.php?option=com_emundus&controller=files&task=export_xls_from_csv',
                                    dataType: 'JSON',
                                    data: {
                                        csv: file,
                                        nbcol: nbcol,
                                        start: json.start,
                                        excelfilename: result.json.excelfilename
                                    },
                                    success: function(result) {
                                        if (result.status) {
                                            //// right here --> I will
                                            let source = result.link;

                                            if (typeof letter !== 'undefined' && letter != 0) {
                                                $.ajax({
                                                    type: 'post',
                                                    url: 'index.php?option=com_emundus&controller=files&task=getexcelletter',
                                                    dataType: 'JSON',
                                                    data: { letter: letter },
                                                    success: function(data) {
                                                        if (data.status) {
                                                            let letter = data.letter.file; // get the destination of letters
                                                            // call ajax to migrate all csv to letter
                                                            $.ajax({
                                                                type: 'post',
                                                                url: 'index.php?option=com_emundus&controller=files&task=export_letter',
                                                                dataType: 'JSON',
                                                                data: {
                                                                    source: source,
                                                                    letter: letter,
                                                                },
                                                                success: function(reply) {
                                                                    let tmp = reply.link.split('/');
                                                                    let filename = tmp[tmp.length - 1];
                                                                    $('#loadingimg').empty();
                                                                    $('#extractstep').replaceWith('<div><p class="em-main-500-color">' + Joomla.Text._('COM_EMUNDUS_EXPORT_FINISHED') + '</p></div>');
                                                                    $('.swal2-confirm').replaceWith('<a class="tw-btn-primary em-w-auto" title="' + Joomla.Text._('COM_EMUNDUS_DOWNLOAD_EXTRACTION') + '" href="index.php?option=com_emundus&controller=' + $('#view').val() + '&task=download&format=xls&name=' + filename + '"><span>' + Joomla.Text._('COM_EMUNDUS_DOWNLOAD_EXTRACTION') + '</span></a>');
                                                                    const confirmBtn = document.querySelector('.em-swal-confirm-button');
                                                                    if (confirmBtn) {
                                                                        confirmBtn.style.opacity = '1';
                                                                    }
                                                                }, error: function(jqXHR) {
                                                                    console.log(jqXHR.responseText);
                                                                }
                                                            })
                                                        }
                                                    }, error: function(jqXHR) {
                                                        console.log(jqXHR.responseText);
                                                    }
                                                });
                                            } else {
                                                $('#loadingimg').empty();
                                                $('#extractstep').replaceWith('<div><p class="em-main-500-color">' + Joomla.Text._('COM_EMUNDUS_EXPORT_FINISHED') + '</p></div>');
                                                $('.swal2-confirm').replaceWith('<a class="tw-btn-primary em-w-auto" title="' + Joomla.Text._('COM_EMUNDUS_DOWNLOAD_EXTRACTION') + '" href="index.php?option=com_emundus&controller=' + $('#view').val() + '&task=download&format=xls&name=' + result.link + '"><span>' + Joomla.Text._('COM_EMUNDUS_DOWNLOAD_EXTRACTION') + '</span></a>');
                                            }
                                        }
                                        else {
                                            $('#loadingimg').empty();
                                            $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">' + Joomla.Text._('COM_EMUNDUS_ERROR_XLS') + '</div>');
                                            $('.swal2-confirm').replaceWith('<a class="tw-btn-primary em-w-auto" title="' + Joomla.Text._('COM_EMUNDUS_DOWNLOAD_EXTRACTION_CSV') + '" href="index.php?option=com_emundus&controller=' + $('#view').val() + '&task=download&format=csv&name=' + file + '"><span>' + Joomla.Text._('COM_EMUNDUS_DOWNLOAD_EXTRACTION_CSV') + '</span></a>');
                                        }
                                    },
                                    error: function(jqXHR, textStatus, errorThrown) {
                                        $('#loadingimg').empty();
                                        $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">' + Joomla.Text._('COM_EMUNDUS_ERROR_XLS') + '</div>');
                                        $('.swal2-confirm').replaceWith('<a class="tw-btn-primary em-w-auto" title="' + Joomla.Text._('COM_EMUNDUS_DOWNLOAD_EXTRACTION_CSV') + '" href="index.php?option=com_emundus&controller=' + $('#view').val() + '&task=download&format=csv&name=' + file + '"><span>' + Joomla.Text._('COM_EMUNDUS_DOWNLOAD_EXTRACTION_CSV') + '</span></a>');
                                        console.log(jqXHR.responseText);
                                    }
                                });
                        }
                        else {
                            $('#loadingimg').empty();
                            $('#extractstep').replaceWith('<div><p class="em-main-500-color">' + Joomla.Text._('COM_EMUNDUS_EXPORT_FINISHED') + '</p></div>');
                            $('.swal2-confirm').replaceWith('<a class="tw-btn-primary em-w-auto" title="' + Joomla.Text._('COM_EMUNDUS_DOWNLOAD_EXTRACTION_CSV') + '" href="index.php?option=com_emundus&controller=' + $('#view').val() + '&task=download&format=csv&name=' + file + '"><span>' + Joomla.Text._('COM_EMUNDUS_DOWNLOAD_EXTRACTION_CSV') + '</span></a>');
                        }
                    }
                } else {
                    document.querySelector('#extractstep').replaceWith(result.msg);
                }
            },
            error: function (jqXHR) {
                $('#loadingimg').empty();
                $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">' + jqXHR.responseText + '</div>');
            }
        });
    } else if (start+limit > maxcsv) {
        $('#loadingimg').empty();
        $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">'+Joomla.Text._('COM_EMUNDUS_ERROR_CSV_CAPACITY')+'</div>');
        exit();
    } else if ((start < maxxls) && (start+limit < maxcsv)) {
        $('#extractstep').replaceWith('<div id="extractstep"><p>'+Joomla.Text._('COM_EMUNDUS_XLS_GENERATION')+'</p></div>');
        $.ajax({
            type: 'post',
            url: 'index.php?option=com_emundus&controller=files&task=export_xls_from_csv',
            dataType: 'JSON',
            data: {
                csv: file,
                nbcol: nbcol,
                start: start
            },
            success: function(result) {
                if (result.status) {
                    $('#loadingimg').empty();
                    $('#extractstep').replaceWith('<div class="alert alert-success" role="alert">'+Joomla.Text._('COM_EMUNDUS_EXPORT_FINISHED')+'</div>' );
                    $('.modal-body').append('<a class="btn btn-link" title="' + Joomla.Text._('COM_EMUNDUS_DOWNLOAD_EXTRACTION') + '" href="index.php?option=com_emundus&controller=' + $('#view').val() + '&task=download&format=xls&name=' + result.link + '"><span class="glyphicon glyphicon-download-alt"></span>  <span>' + Joomla.Text._('COM_EMUNDUS_DOWNLOAD_EXTRACTION') + '</span></a>');
                }
            },
            error: function(jqXHR) {
                $('#loadingimg').empty();
                $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">'+Joomla.Text._('COM_EMUNDUS_ERROR_XLS')+'</div>');
                console.log(jqXHR.responseText);
            }
        });

    } else {
        $('#loadingimg').empty();
        $('#extractstep').replaceWith('<div class="alert alert-info" role="alert">'+Joomla.Text._('COM_EMUNDUS_ERROR_CAPACITY_XLS')+'</div><a class="btn btn-link" title="'+Joomla.Text._('COM_EMUNDUS_DOWNLOAD_EXTRACTION')+'" href="index.php?option=com_emundus&controller='+$('#view').val()+'&task=download&format=xls&name='+file+'"><span class="glyphicon glyphicon-download-alt"></span>  <span>'+Joomla.Text._('COM_EMUNDUS_DOWNLOAD_EXTRACTION')+'</span></a>');
    }
}

function export_pdf(fnums, ids, default_export = '', default_form_ids = '', pdf_elements = {profiles: [], tables: [], groups: [], elements: []}) {
    var start = 0;
    var limit = 2;
    var forms = 0;
    var attachment  = 0;

    var form_checked = [];
    var attach_checked = [];
    var options = [];
    var params = {};

    var elements = null;

    if (default_export === '') {
        /// if at least one is checked --> forms = 1
        forms = $('[id^=felts] input:checked').length > 0 ?  1 : 0;

        /// save all profiles
        let profiles = [];
        $('[id^=felts]').each(function (flt) {
            if($(this).find($('[id^=emundus_elm_]')).is(':checked') == true) {
                let id = $(this).attr('id').split('felts')[1];
                pdf_elements['profiles'].push(id);
            }
        });

        /// save all tables
        let tables = [];
        $('[id^=emundus_table_]').each(function (flt) {
            if($(this).find($('[id^=emundus_elm_]')).is(':checked') == true) {
                let id = $(this).attr('id').split('emundus_table_')[1].replace(/\D/g, '');
                if (form_checked.length > 0) {
                    form_checked = form_checked.concat(',', id);
                } else {
                    form_checked = id;
                }
                pdf_elements['tables'].push(id);
            }
        });

        /// save all groups
        let groups = [];
        $('[id^=emundus_grp_]').each(function (flt) {
            const group_id = $(this).attr('id').split('emundus_grp_')[1].replace(/\D/g, '');

            if ($(this).find($('.emundusitem_' + group_id + '[id^=emundus_elm_]')).is(':checked') == true) {
                pdf_elements['groups'].push(group_id);
            }
        });

        let eltsObject = $('[id^=emundus_elm_]');
        let eltsArray = Array.prototype.slice.call(eltsObject);
        eltsArray.forEach(elt => {
            if (elt.checked == true) {
                pdf_elements['elements'].push(elt.value);
            }
        });

        /**
         * Steps elements
         *
         * all inputs that contains emundus_checkall_tbl_[id] are checked
         */
        document.querySelectorAll('#evaluation-steps-elts input[id^=emundus_checkall_tbl_]').forEach(elt => {
            if (elt.checked == true) {
                forms = 1;
                let id = elt.id.split('emundus_checkall_tbl_evaluation_steps_')[1];

                if (form_checked.length > 0) {
                    form_checked = form_checked.concat(',', id);
                } else {
                    form_checked = id;
                }
                pdf_elements['tables'].push(id);

                if (!options.includes('eval_steps')) {
                    options.push('eval_steps');
                }
            }
        });

        document.querySelectorAll('#evaluation-steps-elts input[id^=emundus_checkall_grp_]').forEach(elt => {
            if (elt.checked == true) {
                forms = 1;
                pdf_elements['groups'].push(elt.id.split('emundus_checkall_grp_')[1]);
                if (!options.includes('eval_steps')) {
                    options.push('eval_steps');
                }
            }
        });

        document.querySelectorAll('#evaluation-steps-elts input[id^=emundus_checkall_elm_]').forEach(elt => {
            if (elt.checked == true) {
                forms = 1;
                pdf_elements['elements'].push(elt.id.split('emundus_checkall_elm_')[1]);
                if (!options.includes('eval_steps')) {
                    options.push('eval_steps');
                }
            }
        });

        $('#aelts input:checked').each(function() {
            attach_checked.push($(this).val());
            attachment = 0;
        });

        if ($('#em-ex-forms').is(":checked"))
            forms = 1;
        if ($('#em-ex-attachment').is(":checked"))
            attachment = 1;
        if ($('#em-add-header').is(":checked")) {
            $('#em-export-opt option:selected').each(function() {
                options.push($(this).val());
            });
        } else {
            options.push("0");
        }

        if ($('#concat_attachments_with_form').is(":checked")) {
            params.concat_attachments_with_form = 1;
        } else {
            params.concat_attachments_with_form = 0;
        }

        if ($('#convert_docx_to_pdf').is(":checked")) {
            params.convert_docx_to_pdf = 1;
        } else {
            params.convert_docx_to_pdf = 0;
        }

        $('#data').hide();

        $('div').remove('#chargement');
    } else if (default_export === 'forms') {
        forms = 1;
        options = [
            "aid",
            "afnum",
            "aemail",
            "aapp-sent",
            "adoc-print",
            "tags",
            "status",
            "upload"
        ];
    } else if (default_export === 'evaluation_step') {
        forms = 1;
        form_checked = default_form_ids;
        options = ["eval_steps", "aid", "afnum", "aemail", "aapp-sent", "adoc-print", "tags", "status", "upload"];
    }

    var swal_container_class = '';
    var swal_popup_class = '';
    var swal_actions_class = '';

    var html = '<div id="chargement" style="text-align: center">' +
        '<div id="extractstep" class="em-flex-column"><p>' + Joomla.Text._('COM_EMUNDUS_EXPORTS_CREATE_PDF') + '</p><div class="em-loader em-mt-8"></div></div>' +
        '</div>';

    Swal.fire({
        title: Joomla.Text._('COM_EMUNDUS_EXPORTS_PDF_GENERATION'),
        html: html,
        showCancelButton: false,
        showCloseButton: false,
        reverseButtons: true,
        confirmButtonText: Joomla.Text._('COM_EMUNDUS_ONBOARD_OK'),
        cancelButtonText: Joomla.Text._('COM_EMUNDUS_ONBOARD_CANCEL'),
        customClass: {
            container: 'em-modal-actions ' + swal_container_class,
            popup: swal_popup_class,
            title: 'em-swal-title',
            cancelButton: 'em-swal-cancel-button',
            confirmButton: 'em-swal-confirm-button btn btn-success',
            actions: swal_actions_class
        },
    });
    document.querySelector('.em-swal-confirm-button').style.opacity = '0';

    $.ajax({
        type: 'post',
        url: 'index.php?option=com_emundus&controller=files&task=getfnums',
        dataType: 'JSON',
        data: {fnums: fnums, ids: ids, action_id:8, crud:'c'},

        success: function(result) {
            var totalfile = result.totalfile;
            ids = result.ids;

            if (result.status) {
                $.ajax({
                    type: 'post',
                    url: '/index.php?option=com_emundus&controller=files&task=create_file_pdf&format=raw',
                    dataType: 'JSON',
                    success: function (result) {
                        if (result.status) {
                            $('#extractstep').replaceWith('<div id="extractstep"><div id="addatatext"><p>' +
                                Joomla.Text._('COM_EMUNDUS_EXPORTS_ADD_FILES_TO_PDF') +
                                '</p></div><div id="datasbs"</div>');

                            var json = {
                                start: start,
                                limit: limit,
                                totalfile: totalfile,
                                forms: forms,
                                attachment: attachment,
                                attachids: attach_checked,
                                options: options,
                                file: result.file,
                                ids: ids
                            };

                            if (forms != 0) {
                                json.formids = form_checked;
                                elements = pdf_elements;
                            }
                            $('#datasbs').replaceWith('<div id="datasbs" data-start="0"><p>...</p></div>');

                            generate_pdf(json, elements);

                        } else {

                            $('#loadingimg').empty();
                            $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">' +
                                result.msg + '</div>');

                        }
                    },
                    error: function (jqXHR) {
                        $('#loadingimg').empty();
                        $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">' +
                            jqXHR.responseText + '</div>');
                    }
                });
            }
        },
        error: function (jqXHR) {
            $('#loadingimg').empty();
            $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">' + jqXHR.responseText +
                '</div>');
        }
    });
}

function generate_pdf(json, pdf_elements= null) {
    const maxfiles = 5000;
    var start       = json.start;
    var limit       = json.limit;
    var totalfile   = json.totalfile;
    var file        = json.file;
    var forms       = json.forms;
    var attachment  = json.attachment;
    var ids         = json.ids;
    var formids     = json.formids;
    var attachids   = json.attachids;
    var options     = json.options;

    $.ajaxQ.abortAll();

    $('#extractstep').replaceWith('<div id="extractstep" class="em-flex-column"><p>' + Joomla.Text._('COM_EMUNDUS_EXPORTS_PDF_GENERATION') + '</p><div class="em-loader em-mt-8"></div></div>');

    if (start+limit < maxfiles) {
        /// call to ajax
        if(pdf_elements !== null && pdf_elements !== undefined) {
            var profiles = pdf_elements['profiles'];
            var tables = pdf_elements['tables'];
            var groups = pdf_elements['groups'];
            var elements = pdf_elements['elements'];

            $.ajax({
                type: 'post',
                url: '/index.php?option=com_emundus&controller=files&task=generate_pdf&format=raw',
                dataType: 'JSON',
                data: {
                    file: file,
                    totalfile: totalfile,
                    start: start,
                    limit: limit,
                    forms: forms,
                    attachment: attachment,
                    ids: ids,
                    formids: formids,
                    attachids: attachids,
                    options: options,
                    profiles: profiles,         /// default is UNDEFINED
                    tables: tables,             /// default is UNDEFINED
                    groups: groups,             /// default is UNDEFINED
                    elements: elements,         /// default is UNDEFINED
                },
                success: function (result) {
                    if (result.status) {
                        $('#extractstep').replaceWith('<div><p class="em-main-500-color">' + Joomla.Text._('COM_EMUNDUS_EXPORT_FINISHED') + '</p></div>');
                        $('.swal2-confirm').replaceWith('<a class="tw-btn-primary em-w-auto" title="' + Joomla.Text._('COM_EMUNDUS_EXPORTS_DOWNLOAD_PDF') + '" href="' + result.json.path + 'tmp/' + result.json.file + '" target="_blank"><span>' + Joomla.Text._('COM_EMUNDUS_EXPORTS_DOWNLOAD_PDF') + '</span></a>');
                        const confirmBtn = document.querySelector('.em-swal-confirm-button');
                        if (confirmBtn) {
                            confirmBtn.style.opacity = '1';
                        }
                    } else {
                        $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">' + result.json.msg + '</div>');
                    }
                },
                error: function (jqXHR) {
                    $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">' + jqXHR.responseText + '</div>');
                    $('#chargement').append('<button type="button" class="btn btn-default" id="back" onclick="back();"><span class="glyphicon glyphicon-arrow-left"></span>&nbsp;&nbsp;' + Joomla.Text._('BACK') + '</button>&nbsp;&nbsp;&nbsp;');
                }
            })
        } else {
            $.ajax({
                type: 'post',
                url: '/index.php?option=com_emundus&controller=files&task=generate_pdf&format=raw',
                dataType: 'JSON',
                data: {
                    file: file,
                    totalfile: totalfile,
                    start: start,
                    limit: limit,
                    forms: forms,
                    attachment: attachment,
                    ids: ids,
                    formids: formids,
                    attachids: attachids,
                    options: options,
                },
                success: function (result) {
                    if (result.status) {
                        $('#extractstep').replaceWith('<div><p class="em-main-500-color">'+Joomla.Text._('COM_EMUNDUS_EXPORT_FINISHED')+'</p></div>');
                        $('.swal2-confirm').replaceWith('<a class="tw-btn-primary em-w-auto" title="' + Joomla.Text._('COM_EMUNDUS_EXPORTS_DOWNLOAD_PDF') + '" href="' +result.json.path+ 'tmp/' + result.json.file + '" target="_blank"><span>' + Joomla.Text._('COM_EMUNDUS_EXPORTS_DOWNLOAD_PDF') + '</span></a>');
                        const confirmBtn = document.querySelector('.em-swal-confirm-button');
                        if (confirmBtn) {
                            confirmBtn.style.opacity = '1';
                        }
                    } else {
                        $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">' + result.json.msg + '</div>');
                    }
                },
                error: function (jqXHR) {
                    $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">!!' + jqXHR.responseText + '</div>');
                }
            })
        }

    } else if (start+limit> maxfiles) {
        $('#extractstep').replaceWith('<div class="alert alert-danger" role="alert">'+Joomla.Text._('COM_EMUNDUS_ERROR_NBFILES_CAPACITY')+'</div>');
        $('#chargement').append('<button type="button" class="btn btn-default" id="back" onclick="back();"><span class="glyphicon glyphicon-arrow-left"></span>&nbsp;&nbsp;'+Joomla.Text._('BACK')+'</button>&nbsp;&nbsp;&nbsp;');

    } else if (start+limit <= maxfiles) {
        $('#extractstep').replaceWith('<div><p class="em-main-500-color">'+Joomla.Text._('COM_EMUNDUS_EXPORT_FINISHED')+'</p></div>');
        $('.swal2-confirm').replaceWith('<a class="tw-btn-primary em-w-auto" title="' + Joomla.Text._('COM_EMUNDUS_EXPORTS_DOWNLOAD_PDF') + '" href="' +result.json.path+ 'tmp/' + file + '" target="_blank"><span>' + Joomla.Text._('COM_EMUNDUS_EXPORTS_DOWNLOAD_PDF') + '</span></a>');

    } else {
        $('#extractstep').replaceWith('<div class="alert alert-info" role="alert">'+Joomla.Text._('COM_EMUNDUS_ERROR_CAPACITY_PDF')+'</div><a class="btn btn-link" title="'+Joomla.Text._('COM_EMUNDUS_EXPORTS_DOWNLOAD_PDF')+'" href="' +result.json.path+ '/tmp/'+file+'" target="_blank"><span class="glyphicon glyphicon-download-alt"></span>  <span>'+Joomla.Text._('COM_EMUNDUS_EXPORTS_DOWNLOAD_PDF')+'</span></a>');
        $('#chargement').append('<button type="button" class="btn btn-default" id="back" onclick="back();"><span class="glyphicon glyphicon-arrow-left"></span>&nbsp;&nbsp;'+Joomla.Text._('BACK')+'</button>&nbsp;&nbsp;&nbsp;');
    }
}

function export_zip(fnums){
    var forms = 0;
    var attachment  = 0;
    var form_checked = [];
    var attach_checked = [];
    var options = [];
    let params = {};
    let eval_steps = getCheckedEvalSteps();


    $('#felts input:checked').each(function() {
        form_checked.push($(this).val());
        forms = 0;
    });

    if ($('#em-ex-forms').is(":checked"))
        forms = 1;
    if ($('#em-ex-attachment').is(":checked"))
        attachment = 1;

    if ($('#em-add-header').is(":checked")) {
        $('#em-export-opt option:selected').each(function() {
            options.push($(this).val());
        });
    } else {
        options.push('0');
    }

    if ($('#concat_attachments_with_form').is(":checked")) {
        params.concat_attachments_with_form = 1;
    } else {
        params.concat_attachments_with_form = 0;
    }

    $('#data').hide();

    $('div').remove('#chargement');

    var swal_container_class = '';
    var swal_popup_class = '';
    var swal_actions_class = '';

    var html = '<div id="chargement" style="text-align: center">' +
        '<div id="extractstep" class="em-flex-column"><p>' + Joomla.Text._('COM_EMUNDUS_EXPORTS_CREATE_ZIP') + '</p><div class="em-loader em-mt-8"></div></div>' +
        '</div>';

    Swal.fire({
        title: Joomla.Text._('COM_EMUNDUS_EXPORTS_ZIP_GENERATION'),
        html: html,
        showCancelButton: false,
        showCloseButton: false,
        reverseButtons: true,
        confirmButtonText: Joomla.Text._('COM_EMUNDUS_ONBOARD_OK'),
        cancelButtonText: Joomla.Text._('COM_EMUNDUS_ONBOARD_CANCEL'),
        customClass: {
            container: 'em-modal-actions ' + swal_container_class,
            popup: swal_popup_class,
            title: 'em-swal-title',
            cancelButton: 'em-swal-cancel-button',
            confirmButton: 'em-swal-confirm-button btn btn-success',
            actions: swal_actions_class
        },
    });
    document.querySelector('.em-swal-confirm-button').style.opacity = '0';

    var url = 'index.php?option=com_emundus&controller=files&task=zip&Itemid='+itemId;
    $.ajax({
        type:'get',
        url:url,
        data: {
            fnums: fnums,
            forms: forms,
            attachment: attachment,
            formids: form_checked,
            attachids:attach_checked,
            eval_steps: JSON.stringify(eval_steps),
            options: options,
            params: JSON.stringify(params)
        },
        dataType:'json',
        success: function(result) {
            if (result.status && result.name != 0) {
                $('#extractstep').replaceWith('<div><p class="em-main-500-color">'+Joomla.Text._('COM_EMUNDUS_EXPORT_FINISHED')+'</p></div>');
                $('.swal2-confirm').replaceWith('<a class="tw-btn-primary em-w-auto" title="' + Joomla.Text._('COM_EMUNDUS_DOWNLOAD_ZIP') + '" href="index.php?option=com_emundus&controller='+$('#view').val()+'&task=download&format=zip&name='+result.name+'"><span>' + Joomla.Text._('COM_EMUNDUS_DOWNLOAD_ZIP') + '</span></a>');
            }
        },
        error: function (jqXHR) {
            console.log(jqXHR.responseText);
        }
    });
}

function generate_letter() {
    var fnums = $('input:hidden[name="em-doc-fnums"]').val();
    var idsTmpl = $('#em-doc-tmpl').val();

    var cansee = 0;
    if($('#em-doc-cansee').is(':checked')) { cansee = 1; }

    // show by applicants (0) or show by document type (1)
    var showMode = $('#em-doc-export-mode').val();

    var mergeMode = 0;
    if($('#em-doc-pdf-merge').is(':checked')) { mergeMode = 1; }

    let force_replace_document = 0;
    const forceReplaceAttachmentInput = document.getElementById('em-force-replace-attachment');
    if (forceReplaceAttachmentInput) {
        force_replace_document = forceReplaceAttachmentInput.checked ? 1 : 0;
    }

    if (fnums && fnums.length > 0 ) {
        // do that to remove the check-all option
        fnums = fnums.replace(/([a-z-]+,)/g, '');
    }

    var swal_container_class = '';
    var swal_popup_class = 'em-w-auto';
    var swal_actions_class = '';

    var html = '<div id="chargement">' +
        '<div id="extractstep" class="em-flex-column"><p>' + Joomla.Text._('COM_EMUNDUS_LETTERS_PROGRESSING') + '</p><div class="em-loader em-mt-8"></div></div>' +
        '</div>';

    Swal.fire({
        title: Joomla.Text._('COM_EMUNDUS_ACCESS_LETTERS'),
        html: html,
        showCancelButton: false,
        showCloseButton: false,
        reverseButtons: true,
        confirmButtonText: Joomla.Text._('COM_EMUNDUS_ONBOARD_OK'),
        cancelButtonText: Joomla.Text._('COM_EMUNDUS_ONBOARD_CANCEL'),
        customClass: {
            container: 'em-modal-actions ' + swal_container_class,
            popup: swal_popup_class,
            title: 'em-swal-title',
            cancelButton: 'em-swal-cancel-button',
            confirmButton: 'em-swal-confirm-button btn btn-success',
            actions: swal_actions_class
        },
    });

    $.ajax({
        type:'post',
        url:'index.php?option=com_emundus&controller=files&task=generateletter',
        dataType:'json',
        data:{fnums: fnums, ids_tmpl: idsTmpl, cansee: cansee, showMode: showMode, mergeMode: mergeMode, force_replace_document: force_replace_document},
        success: function(result) {
            if (result.status) {
                removeLoader();

                $('.swal2-confirm').replaceWith('<a class="tw-btn-primary em-w-auto" id="em-download-all" title="' + Joomla.Text._('DOWNLOAD_DOCUMENT') + '" href=""><span>' + Joomla.Text._('DOWNLOAD_DOCUMENT') + '</span></a>');

                /// render recapitulatif
                var recal = result.data.recapitulatif_count;
                var table =
                    "<p class='em-h4'>" +
                    Joomla.Text._('AFFECTED_CANDIDATS') + result.data.affected_users +
                    "</p>" +
                    "<table class='table table-striped em-mt-12' id='em-generated-docs' style='border: 1px solid #c1c7d0'>" +
                    "<thead>" +
                    "<th>" + Joomla.Text._('GENERATED_DOCUMENTS_LABEL') + "</th>" +
                    "<th>" + Joomla.Text._('GENERATED_DOCUMENTS_COUNT') + "</th>" +
                    "</thead>" +
                    "<tbody>";

                recal.forEach(data => {
                    table +=
                        "<tr style='background: #c1c7d0'>" +
                        "<td>" + data.document + "</td>" +
                        "<td>" + data.count + "</td>" +
                        "</tr>"
                })

                table += "</tbody></table>";

                if (showMode == 0) {
                    var zip = result.data.zip_data_by_candidat;

                    table += "<p class='em-h4'>" +
                        Joomla.Text._('CANDIDAT_GENERATED') +
                        "</p>" +
                        "<table class='table table-striped em-mt-12' id='em-generated-docs' style='border: 1px solid #c1c7d0'>" +
                        "<thead>" +
                        "<tr>" +
                        "<th>" + Joomla.Text._('CANDIDATE') + "</th>" +
                        "</tr>" +
                        "</thead>" +
                        "<tbody>";

                    if (mergeMode == 0) {
                        zip.forEach(file => {
                            table += "<tr>" +
                                "<td>" + file.applicant_name +
                                "<a id='em_zip_download' target='_blank' class='em-float-right' href='" + file.zip_url + "'>" +
                                "<span class='material-icons'>file_download</span>" +
                                "</a>" +
                                "</td>" +
                                "</tr>";
                        })

                    } else {
                        zip.forEach(file => {
                            table += "<tr>" +
                                "<td>" + file.applicant_name +
                                "<a id='em_zip_download' target='_blank' class='em-float-right' href='" + file.merge_zip_url + "'>" +
                                "<span class='material-icons'>file_download</span>" +
                                "</a>" +
                                "</td>" +
                                "</tr>";
                        })
                    }

                    table += "</tbody></table>";

                    $('#em-download-all').attr('href', result.data.zip_all_data_by_candidat);
                } else if (showMode == 1) {
                    var letters = result.data.letter_dir;

                    table +=
                        "<p class='em-h4'>" +
                        Joomla.Text._('DOCUMENT_GENERATED') +
                        "</p>" +
                        "<table class='table table-striped em-mt-12' id='em-generated-docs' style='border: 1px solid #c1c7d0'>" +
                        "<thead>" +
                        "<tr>" +
                        "<th>" + Joomla.Text._('DOCUMENT_NAME') + "</th>" +
                        "</tr>" +
                        "</thead>" +
                        "<tbody>";

                    if (mergeMode == 0) {
                        letters.forEach(letter => {
                            table +=
                                "<tr>" +
                                "<td>" + letter.letter_name +
                                "<a id='em_zip_download' target='_blank' class='em-float-right' href='" + letter.zip_dir + "'>" +
                                "<span class='material-icons'>file_download</span>" +
                                "</a>" +
                                "</td>" +
                                "</tr>";
                        })
                    } else {
                        letters.forEach(letter => {
                            table +=
                                "<tr>" +
                                "<td>" + letter.letter_name +
                                "<a id='em_zip_download' target='_blank' class='em-float-right' href='" + letter.zip_merge_dir + "'>" +
                                "<span class='material-icons'>file_download</span>" +
                                "</a>" +
                                "</td>" +
                                "</tr>";
                        })
                    }

                    table += "</tbody></table>";
                    $('#em-download-all').attr('href', result.data.zip_all_data_by_document);
                } else {
                    /// showMode == 2 (classic way)
                    var files = result.data.files;
                    if (files && files.length > 0) {
                        var zipUrl = 'index.php?option=com_emundus&controller=files&task=exportzipdoc&ids=';
                        table += '<p class="em-h4">' + Joomla.Text._('COM_EMUNDUS_LETTERS_FILES_GENERATED') + '</p>' +
                            '<table class="table table-striped em-mt-12" id="em-generated-docs" style="border: 1px solid #c1c7d0">' +
                            '<thead>' +
                            '<tr>' +
                            '<th>' + Joomla.Text._('COM_EMUNDUS_ATTACHMENTS_FILE_NAME') + '</th>' +
                            '</tr>' +
                            '</thead>' +
                            '<tbody>';

                        files.forEach(file => {
                            const regex = /images\/emundus\/files\//g;
                            const subst = `index.php?option=com_emundus&task=getfile&u=images/emundus/files/`;

                            const getfile_url = file.url.replace(regex, subst);

                            table += '<tr id="' + file.upload + '">' +
                                '<td>' + file.filename +
                                '<a id="em_download_doc_' + file.upload + '" target="_blank" class="em-p-8" href="' + getfile_url + file.filename + '">' +
                                '<span class="material-symbols-outlined">file_download</span>' +
                                '</a>' +
                                '</td>' +
                                '</tr>';
                        })

                        table += "</tbody></table>";

                        var urls = [];
                        files.forEach(file => {
                            urls.push(file.upload);
                        })
                        console.log(urls);

                        $('#em-download-all').attr('href', zipUrl + urls.toString());
                    }
                }

                $('#extractstep').replaceWith(table);
            } else {
                removeLoader();
            }
        },
        error: function (jqXHR) {
            console.log(jqXHR.responseText);
        }
    });
}

function generate_trombinoscope(fnums, data)
{
    tinyMCE.execCommand('mceToggleEditor', false, 'trombi_head');
    tinyMCE.execCommand('mceToggleEditor', false, 'trombi_foot');

    let string_fnums;
    if (fnums === 'all') {
        string_fnums = '["all"]';
    } else {
        string_fnums = fnums;
    }

    $(this).prop('disabled', true);

    $.ajax({
        type: 'POST',
        url: 'index.php?option=com_emundus&controller=trombinoscope&task=generate_pdf',
        async: false,
        dataType: 'json',
        data : {
            string_fnums: string_fnums,
            gridL: data.selected_grid_width,
            gridH: data.selected_grid_height,
            margin: data.selected_margin,
            template: data.selected_tmpl,
            header: data.header,
            footer: data.footer,
            format: data.format,
            generate: data.string_generate,
            checkHeader: data.selected_check,
            border: data.selected_border,
            headerHeight : data.header_height
        },
        success: function (data) {
            if (data.status && data.pdf_url !== undefined && data.pdf_url !== null) {
                Swal.fire({
                    title: Joomla.Text._('COM_EMUNDUS_TROMBI_DOWNLOAD'),
                    html: Joomla.Text._('COM_EMUNDUS_EXPORT_FINISHED'),
                    customClass: {
                        title: 'em-swal-title',
                        actions: 'em-swal-single-action'
                    },
                });
                $('.swal2-confirm').replaceWith('<a class="tw-btn-primary em-w-auto" href="' +data.pdf_url + '" target="_blank">Télécharger le fichier</a>')
            } else {
                Swal.fire({
                    title: Joomla.Text._('COM_EMUNDUS_ERROR'),
                    text: Joomla.Text._('COM_EMUNDUS_TROMBINOSCOPE_GENERATE_FAILED'),
                    icon: 'error',
                    customClass: {
                        title: 'em-swal-title',
                        confirmButton: 'em-swal-confirm-button',
                        actions: 'em-swal-single-action'
                    },
                });
            }
            removeLoader();
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            removeLoader();
        }
    });
}


async function getEvaluationStepsElements(code)
{
    let elements = '';

    if (code) {
        return await fetch('/index.php?option=com_emundus&view=export_select_columns&format=raw&form=evaluation_steps&code='+code).then((response) => {
            return response.text();
        }).then((data) => {
            return data;
        });
    } else {
        return elements;
    }
}


function getCheckedEvalSteps()
{
    let json = {
        tables: [],
        groups: [],
        elements: []
    }

    document.querySelectorAll('#eval-steps-container input[id^=emundus_checkall_tbl_]').forEach(elt => {
        if (elt.checked == true) {
            let table_id = elt.id.split('emundus_checkall_tbl_')[1];

            if (!json.tables.includes(table_id)) {
                json.tables.push(table_id);
            }
        }
    });

    document.querySelectorAll('#eval-steps-container input[id^=emundus_checkall_grp_]').forEach(elt => {
        if (elt.checked == true) {
            let group_id = elt.id.split('emundus_checkall_grp_evaluation_steps_')[1];

            if (!json.groups.includes(group_id)) {
                json.groups.push(group_id);
            }
        }
    });

    document.querySelectorAll('#eval-steps-container input[id^=emundus_elm_]').forEach(elt => {
        if (elt.checked == true) {
            let element_id = elt.id.split('emundus_elm_')[1];

            if (!json.elements.includes(element_id)) {
                json.elements.push(element_id);
            }
        }
    });

    return json;
}