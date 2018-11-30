/*
 * app/Resources/public/js/main.js
 *
 * initialisation des composants
 *
 */

variable_class_to_remove = 'label-danger label-warning label-success label-light badge-danger badge-warning badge-success';

update_light_class = function () {
    var light_adr = '#' + $(this).attr('id') + '_light';
    $(light_adr).removeClass(variable_class_to_remove);
    $(light_adr).addClass($(this).find(':selected').data('lightclass'));
};

function countSms(src,dest, max){
    var txtVal = src.val();
    var chars = txtVal.length;
    dest.html(chars +' caractères');
    if(chars>max) {
        dest.addClass('text-danger');
        dest.removeClass('text-primary');
    } else {
        dest.removeClass('text-danger');
        dest.addClass('text-primary');
    }
}

function applyGritter() {
    var grit = $.gritter.add({
        title     : $(this).data('title'),
        text      : $(this).html(),
        class_name: $(this).data('class'),
        image     : $(this).data('image'),
        sticky    : true
    });
}

function calculateProductAmount() {
    var mt_produits = 0;
    $('input.produits_montant_calc').each(function (e) {
        mt_produits = mt_produits + (parseInt($(this).val(), 10) || 0);
    });
    $('#kgc_RdvBundle_rdv_tarification_montant_produits').val(mt_produits).keyup();
};

function init_plugins(conteneur) {
    conteneur = typeof conteneur !== 'undefined' ? conteneur : 'body';

    /* --- read_only --- */
    $(conteneur + ' input[disabled][type=text]').each(function () {
        if (!$(this).hasClass('date-picker')) {
            $(this).removeAttr('disabled');
            $(this).attr('readonly', 'readonly');
        }
    });

    /* --- custom file input --- */
    $(conteneur + ' .ace_file_input').ace_file_input({
        no_file   : 'Aucun fichier sélectionné...',
        btn_choose: 'Parcourir',
        btn_change: 'Modifier',
        droppable : false,
        onchange  : null,
        thumbnail : false
    });

    /* --- Color picker --- */
    $(conteneur + ' .colorpicker').ace_colorpicker();

    /* --- Datepicker --- */
    $(conteneur + ' .date-picker').each(function () {
        $(this).datepicker({
            format            : "dd/mm/yyyy",
            language          : "fr",
            todayHighlight    : true,
            startDate         : $(this).data('startdate'),
            daysOfWeekDisabled: $(this).data('daysofweekdisabled')
        });
    });

    $.fn.datetimepicker.dates['fr'] = {
        days       : ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche"],
        daysShort  : ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam", "Dim"],
        daysMin    : ["D", "L", "Ma", "Me", "J", "V", "S", "D"],
        months     : ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"],
        monthsShort: ["Jan", "Fev", "Mar", "Avr", "Mai", "Jui", "Jul", "Aou", "Sep", "Oct", "Nov", "Dec"],
        today      : "Aujourd'hui",
        suffix     : [],
        meridiem   : ["am", "pm"],
        weekStart  : 1,
        format     : "dd/mm/yyyy hh:ii"
    };
    $(conteneur + ' .date-picker-hours').each(function () {
        $(this).datetimepicker({
            format  : "dd/mm/yyyy hh:ii",
            language: "fr",
        });
    });

    $(conteneur + ' .date-picker-month').each(function () {
        $(this).datepicker({
            changeMonth    : true,
            changeYear     : true,
            showButtonPanel: true,
            language       : "fr",
            dateFormat     : 'mm/yyyy',
            viewMode       : "months",
            minViewMode    : "months",
            autoclose      : true,
            onClose        : function (dateText, inst) {
                $(this).datepicker('setDate', new Date(inst.selectedYear, inst.selectedMonth, 1));
            }
        });
    });
    /* --- Accordions --- */
//    $( conteneur + ' .accordion' ).accordion({ collapsible: true });

    /* -- Tooltips & Popover --- */
    $(conteneur + ' [data-rel=tooltip]').tooltip();
    $(conteneur + ' .html-popover').each(function () {
        contentid = $(this).data('idcontent');
        content = $(contentid).html();
        $(contentid).remove();
        $(this).popover({
            html   : true,
            content: content
        });
    });

    /* --- Alertes --- */
    function code_alert(type, msg) {
        type = typeof type !== 'undefined' ? type : 'default';
        var intros = new Array();
        intros['default'] = '';
        intros['danger'] = '<i class="icon-warning-sign"></i> Erreur&nbsp;: ';
        intros['success'] = '<i class="icon-ok"></i> Effectué&nbsp;! ';
        intros['warning'] = '<i class="icon-warning-sign"></i> Attention&nbsp;! ';
        intros['info'] = '<i class="icon-exclamation-sign"></i> Info&nbsp;: ';
        var code = '\
            <button type="button" class="close" data-dismiss="alert"><i class="icon-remove"></i></button>\
            <strong>' + intros[type] + '</strong>\
            ' + msg + '<br />\
        ';
        return code;
    }

    $(conteneur + ' .alert').each(function () {
        var regex = /alert-(?!block)(.*[^(])/;
        var type = $(this).attr('class').match(regex);
        var msg = $(this).html();
        $(this).html(code_alert(type[1], msg));
    });

    /* --- Gritters --- */

    $(conteneur + ' .gritter').each(applyGritter);

    /* --- Masques de champs --- */
    $(conteneur + ' .input-mask').each(function(){
        var mask = $(this).data('mask');
        $(this).mask(mask);
    });
    $(conteneur + ' .input-mask-date').each(function(){
        $(this).mask('99/99/9999');
    });
    $(conteneur + ' .input-mask-datetime').each(function () {
        //ce masque limite la dizaine de minutes à soit 0 soit 3 plus unité à 0 => force l'heure ou la demi heure pile
        $.mask.definitions['m'] = '[03]';
        $(this).mask('99/99/9999 99:m0');
    });
    $(conteneur + ' .input-mask-expiration').each(function () {
        $(this).mask('99/99');
    });

    /* --- Chosen select --- */
    $(conteneur + ' .chosen-select').each(function () {
        var width = $(this).data('width');
        if (typeof width === "undefined") {
            width = '50%';
        }
        $(this).chosen({'width': width});
    });

    /* --- DataTable --- */
    $.fn.dataTable.moment('DD/MM/YYYY');
    $.fn.dataTable.moment('DD/MM/YYYY HH:mm');
    $(conteneur + ' .dataTable').dataTable();
    $(conteneur + ' #datatable-impaye').dataTable({
        "paging" : false,
        "order"  : [[1, "desc"]],
        "columns": [
            {"width": "160px", "targets": [0]}, // client
            {"width": "102px", "targets": [1, 2, 3]}, // date consultation, dernier, prochain encaissement
            {"type": "moment-DD/MM/YYYY", "targets": [2, 3]}, // date dernier, prochain encaissement
            {"orderable": false, "targets": [4, 5, 7, 8]}, // stateline + etiquettes + commentaire dernier historique + ouvrir la fiche
            {"width": "118px", "targets": [4]}, // stateline
            {"width": "140px", "targets": [5]}, // etiquettes
            {"width": "70px", "targets": [6]}, // date dernier historique
            {"width": "28px", "targets": [8]} // ouvrir la fiche
        ]
    });

    /* --- Form wizard --- */
    $(conteneur + ' .wizard').ace_wizard().on('finished', function (e) {
        $(this).parents('form').submit();
    });

    /* --- compactable -- */
//    $('[data-action="switch-compact"]').click(function(){
//        alert('detect');
//        $(this).parent(".compactable").find(".compactit").each().toggle();
//    });

    /* --- Planning de selection --- */
    $('#planning_selection input[type=radio]').click(function () {
        var value = $(this).val();
        $('#kgc_RdvBundle_rdv_dateConsultation').val(value);
    });

    $('.valid-campagne-send').click(function () {
        var $this = $(this), $td = $this.closest('td');
        if (confirm("Confirmez-vous l'envoi de la campagne?")) {
            $td.find('.valid-campagne-send-click').click();
        }
    });

    $('#planning_selection #form_jour, #planning_selection #form_periode').change(function () {
        $('#planning_selection #planning_maj').click();
    });

    $('#planning_selection #planning_maj').click(function () {
        var e = $('#planning_selection');
        $.ajax({
            context   : e,
            url       : $(this).data('href'),
            type      : 'post',
            data      : $('#planning_selection input, #planning_selection select').serialize(),
            beforeSend: function () {
                $(e).html($('#loading').html());
            },
            success   : function (data) {
                if (data && data.redirect_uri) {
                    window.location.replace(data.redirect_uri);
                    return false;
                }
                $(e).html(data);
                init_plugins("#" + $(e).attr('id'));
            },
            error     : function (xhr) {
                erreur_generale(xhr);
            }
        });
        return false;
    });

    $(conteneur).on('keyup', '.count-sms', function () {
        var $this = $(this), iNbMax = $this.attr('data-max');
        countSms($(this), $("#showNbCount"), iNbMax);
    });

    /* -----------------------
     * -- Champs collection --
     * ----------------------- */
    $(conteneur + ' .collection_field').each(function () {
        // var prototype = $($(this).data('adrprototype')).data('prototype');
        var container = $(this).find('.collection_fields_container');
        var index = container.find(container.data('collection-childs')).length;

        var lastClass = container.find(container.data('collection-childs')).last().attr('class'), encRegExp = /.*_encaissements_(\d+)([ _].*)?/;
        if (lastClass && lastClass.match(encRegExp)) {
            index = parseInt(lastClass.replace(encRegExp, '$1')) + 1;
        }

        var tab = $(this).hasClass('tabbable');
        $(this).find('.collection_add').click(function () {
            var prototype = $($(this).closest('.collection_field').data('adrprototype')).data('prototype');
            if (!$(this).attr('disabled')) {
                thisprototype = prototype
                    .replace(/__name__label__/g, '')
                    .replace(/__name__/g, index);

                if (tab) {
                    bouton_suppr = '<span id="del-tab-' + index + '" class="collection_del text-danger" style="cursor:pointer"><i class="icon-trash"></i></span>';
                    lien_tab = '<a href="#tab-' + index + '" data-toggle="tab" data-selected-index="' + index + '">Nouveau ' + bouton_suppr + '</a>';
                    $(this).parent().before('<li id="lien-tab-' + index + '" class="edit del-tab-' + index + '">' + lien_tab + '</li>');
                    container.append('<div id="tab-' + index + '" class="tab-pane no-padding del-tab-' + index + '">' + thisprototype + '</div>');
                    $('#lien-tab-' + index + ' a').tab('show');
                    id = '#tab-' + index;
                } else {
                    container.append(thisprototype);
                    id = '#' + container.attr('id');
                }
                var $container = $(id);
                $container.find('#numeroMaskedCb').removeClass('class').addClass('hide');
                $container.find('#numeroCb').removeClass('hide');
                init_plugins(id);
                index++;
            }
            return false;
        });

        $(this).find('.decrypt_event').click(function () {
            var $this = $(this), $table = $this.closest('table'), $numero = $table.find('#numeroCb'), $numeroMasked = $table.find('#numeroMaskedCb');
            $numeroMasked.removeClass('class').addClass('hide');
            $numero.removeClass('hide');
        });

        $(document).on('click', '.collection_del', function () {
            var $this = $(this);
            var id = $this.attr('id');
            $('.' + id).remove();

            if ($this.hasClass('del_product')) {
                calculateProductAmount();
            }

            return false;
        });
    });

    /* --- Easy Pie Chart --- */
    $(conteneur + ' .easy-pie-chart.percentage').each(function () {
        var $box = $(this).closest('.infobox');
        var barColor = $(this).data('color') || (!$box.hasClass('infobox-dark') ? $box.css('color') : 'rgba(255,255,255,0.95)');
        var trackColor = barColor == 'rgba(255,255,255,0.95)' ? 'rgba(255,255,255,0.25)' : '#E2E2E2';
        var size = parseInt($(this).data('size')) || 50;
        $(this).easyPieChart({
            barColor  : barColor,
            trackColor: trackColor,
            scaleColor: false,
            lineCap   : 'butt',
            lineWidth : parseInt(size / 10),
            animate   : /msie\s*(8|7|6)/.test(navigator.userAgent.toLowerCase()) ? false : 1000,
            size      : size
        });
    });

    /* --- select-show-field --- */
    $(conteneur + ' .select-show-field').change(function (e) {
        option = $(this).find('option:selected');
        fieldid = $(option).data('show-field');
        $('#' + fieldid).show();
    });

    /* --- light by value --- */
    $(conteneur + ' select.light-on-value').each(update_light_class);

    /* --- enable-fields --- */
    $(conteneur + ' .enable-fields').each(function () {
        enable_fields(true, $(this));
    });
    $(conteneur + ' .disable-fields').each(function () {
        enable_fields(false, $(this));
    });

    /* --- dependant select --- */
    $(conteneur + ' .js-dependant-select').each(function () {
        var $dependantSelect = $(this);
        var $changedFields = $(this).data('depends-on').split(';');
        for (var key = 0; key < $changedFields.length; ++key) {
            var $changedField = $($changedFields[key]);
            DependantFilteredSelect($changedFields, $dependantSelect);
            $changedField.change(function () {
                DependantFilteredSelect($changedFields, $dependantSelect);
            });
        }
    });

    /* --- ajax auto fill --- */
    $(conteneur + ' .js-dependant-ajax-autofill').each(function () {
        var $dependantField = $(this);
        var $changedField = $($(this).data('depends-on'));

        DependantAjaxAutoFill($changedField, $dependantField);
        $changedField.change(function () {
            DependantAjaxAutoFill($(this), $dependantField);
        });
    });

    /* -----------------------------
     * -- Chargements asynchrones --
     * ----------------------------- */
    $(conteneur + " .ajax_load").each(function () {
        ajax_load(this);
    });

    $(conteneur + ' .data-form-ajax').submit(function () {
        $('form[data-ajax-form-from="' + this.id + '"]').each(function () {
            ajax_load(this);
        });

        return false;
    });

    loadWysiwyg();
    copyWysiwygContent();

    var submitButton = $(conteneur + ' .form-footer button[type="submit"]');

    $(conteneur + ' select.securisation').each(function () {
        var securisationTpeSelect = $(this),
            securisationButton = $(conteneur + ' button.securisation-payment'),
            securisationPreAuthSelect = $(conteneur + ' select.preauthorize-amount'),
            securisationCheckbox = $(conteneur + ' input.securisation-checkbox'),
            securisationLastChecked = securisationCheckbox.prop('checked'),
            securisationTpeRegExp = new RegExp(securisationButton.data('secu-allowed')),
            preAuthTpeRegExp = new RegExp(securisationButton.data('preauth-allowed'));

        securisationTpeSelect.change(function () {
            var isSecuAllowed = securisationTpeSelect.val().match(securisationTpeRegExp);
            var isPreAuthAllowed = securisationTpeSelect.val().match(preAuthTpeRegExp);

            if (isSecuAllowed) {
                securisationLastChecked = securisationCheckbox.prop('checked');
                securisationTpeSelect.addClass('allowed');
                securisationButton.prop('disabled', false);
                securisationPreAuthSelect.closest('tr').show();
                if (isPreAuthAllowed) {
                    securisationPreAuthSelect.val('').prop('required', true).prop('disabled', false);
                } else {
                    securisationPreAuthSelect.val(1).prop('required', false).prop('disabled', true);
                }
                securisationCheckbox.prop('checked', true).prop('disabled', true);
                submitButton.prop('disabled', true);
            } else {
                securisationTpeSelect.removeClass('allowed');
                securisationButton.prop('disabled', true);
                securisationPreAuthSelect.prop('required', false).closest('tr').hide();
                securisationCheckbox.prop('checked', securisationLastChecked).prop('disabled', false);
                submitButton.prop('disabled', false);
            }
        });
    }).change();

    // js to get currently selected tab
    $(conteneur + ' ul[data-selection-input-id]').each(function () {
        var item = $(this), selectionInput = $('#' + item.data('selectionInputId'));

        item.on('show.bs.tab', 'a[data-selected-index]', function () {
            selectionInput.val($(this).data('selectedIndex'));
        });
    });

    $(conteneur + ' .validEncaissement select.encaissement-tpe').each(function () {
        var cbTd = $(conteneur + ' .validEncaissement td.cartebancaire'),
            etatSelect = $(conteneur + ' .validEncaissement select.encaissement-status'),
            tpeSelect = $(this),
            paymentButton = $(conteneur + ' .validEncaissement button.encaissement-payment'),
            tpeAllowedRegExp = new RegExp(paymentButton.data('allowed'));

        tpeSelect.change(function () {
            var tpeVal = tpeSelect.val();

            if (tpeVal.match(tpeAllowedRegExp) || tpeVal == 'preauth') {
                if (tpeVal == 'preauth') {
                    cbTd.hide();
                } else {
                    cbTd.show();
                }
                etatSelect.hide().val('STATE_DONE');
                paymentButton.show();
                submitButton.prop('disabled', true);
            } else {
                cbTd.hide();
                etatSelect.show().val('');
                paymentButton.hide();
                submitButton.prop('disabled', false);
            }
        });
    }).change();

    var jNewSendLinkInputs = $('.card_new_send_div .checkbox input'), isCbDisabled = false;

    jNewSendLinkInputs.change(function () {
        var shouldBeDisabled = jNewSendLinkInputs.filter(':checked').size() > 0;

        if (shouldBeDisabled != isCbDisabled) {
            $(this).closest('.widget-cb').find('input[type="text"]').val('').attr('disabled', shouldBeDisabled);

            isCbDisabled = shouldBeDisabled;
        }
    }).change();

    var jSendLinkCheckbox = $('.card_send_div :checkbox');
    jSendLinkCheckbox.change(function () {
        if (jSendLinkCheckbox.is(':checked')) {
            $('.card_send_div .buttons').show();
        } else {
            $('.card_send_div .buttons').hide();
        }
    }).change();

    $('.card_send_div button').click(function () {
        var button = $(this).prop('disabled', true);

        $.post(button.data('url'), function (response) {
            button.prop('disabled', false);

            $(response).find('.gritter').each(applyGritter);
        });
    });

    $(conteneur).find('select.paymentMethodSelect').change(function(){
        var $items = $(conteneur + ' .show-only-if-cb');
        if ($(this).val() == '1') {
            $items.show().filter('.required-if-cb').prop('required', true);
        } else {
            $items.hide().val(null).filter('.required-if-cb').prop('required', false);
        }
    }).change();

    $(conteneur+' .paymentMethod-edit-field').change(function(){
        var $field = $(this), $tr = $field.closest('tr[data-payment]'), paymentId = $tr.data('payment');

        if ($field.val() == 1) {
            $tr.find('#tpe-field-'+paymentId).show();
        } else {
            $tr.find('#tpe-field-'+paymentId).hide();
        }
    }).change();

    var $selectUnitType = $(conteneur+' select.unit-type-choice');

    if ($selectUnitType.size()) {
        // code to hide or display money and default integer field when changing promo unit type, and update label
        var $unitFieldDefault = $(conteneur+' :input[data-unit-field="default"]'), $unitFieldMoney = $(conteneur+' :input[data-unit-field="money"]');
        var $labelDefault = $('label[for="'+$unitFieldDefault.prop('id')+'"]'), $labelMoney = $('label[for="'+$unitFieldMoney.prop('id')+'"]');
        var typeMoney = $selectUnitType.data('unitMoney'), typeBonus = $selectUnitType.data('unitBonus'), typeDefault = $selectUnitType.data('unitDefault');
        var formulaFilterWithoutReduction = $(conteneur+' .allowed-formulas').data('choiceReductionNotAllowed');
        var $websiteSelect = $(conteneur+' .promotion-website'), chatTypesByWebsite = $websiteSelect.data('websiteTypes');

        var cbWoReductionList = [];
        for (var i in formulaFilterWithoutReduction) {
            cbWoReductionList.push(conteneur+' .allowed-formulas :checkbox[value="'+formulaFilterWithoutReduction[i]+'"]');
        }
        var $checkboxFormulaWithoutReduction = $(cbWoReductionList.join(','));

        function updateBonusLabel() {
            if ($selectUnitType.val() == typeBonus) {
                var chatType = chatTypesByWebsite[$websiteSelect.val()];

                $labelDefault.html($selectUnitType.data('labelBonus')[chatType == 1 ? 1 : 0]);
            }
        }

        function enableFormulaWithoutReductionChoice(enabled) {
            if (!enabled) {
                $checkboxFormulaWithoutReduction.prop('checked', false);
            }
            $checkboxFormulaWithoutReduction.prop('disabled', !enabled);
        }

        $websiteSelect.change(updateBonusLabel).change();

        $selectUnitType.change(function() {
            var val = $selectUnitType.val();

            if (val == typeMoney) {
                $unitFieldDefault.hide().prop('required', false); $labelDefault.hide();
                $unitFieldMoney.show().prop('required', true).next('.input-group-addon').show(); $labelMoney.show();
            } else {
                $unitFieldDefault.show().prop('required', true); $labelDefault.show();
                $unitFieldMoney.hide().prop('required', false).next('.input-group-addon').hide(); $labelMoney.hide();

                if (val == typeBonus) {
                    updateBonusLabel();
                } else {
                    $labelDefault.html($selectUnitType.data('labelDefault'));
                }
            }

            enableFormulaWithoutReductionChoice(val == typeBonus);
        }).change();
    }
}

$.ajaxSetup({
    error: erreur_generale
});

function erreur_generale(jqXHR, target) {
    if (jqXHR.status == 403) {
        var grit = $.gritter.add({
            title     : 'Erreur',
            text      : "Vous nʼêtes pas autorisé à effectuer cette action.",
            class_name: 'gritter-error',
            image     : ImagePath + 'ban-circle.png',
            sticky    : true
        });
    } else {
        var grit = $.gritter.add({
            title     : 'Erreur',
            text      : 'Une erreur est survenue.',
            class_name: 'gritter-error',
            image     : ImagePath + 'warning-sign.png',
            sticky    : true
        });
    }
    if (AppEnvironment === 'dev') {
        if (typeof target === 'undefined' || target == 'error') {
            target = '#modal-dialog';
        }
        if (target === '#modal-dialog') {
            $('#modal-dialog').modal({backdrop: 'static'}).modal('show');
        }
        $(target).html(jqXHR.responseText);
    }
}

function ajax_load(e) {
    var beforeloadConf = $(e).data('ajax-beforeload');
    var beforeload = typeof beforeloadConf === 'undefined'
        ? beforeload = true
        : beforeloadConf === 1 ? true : false;
    // conteneur cible
    var target = typeof $(e).data('target') === 'undefined'
        ? target = e
        : $('#' + $(e).data('target'));
    // action
    var action = typeof $(e).attr('action') === 'undefined'
        ? $(e).attr('href')
        : $(e).attr('action');

    var ajaxFormId = $(e).data('ajax-form-from');
    if (ajaxFormId) {
        var data = $('#' + ajaxFormId).serialize();
    } else {
        var data = undefined;
    }

    // method
    var method = $(e).prop('tagName') === 'FORM' ? 'post' : 'get';
    // Requête AJAX
    $.ajax({
        context   : { 'target': target, 'beforeload': beforeload},
        url       : action,
        data      : data,
        type      : method,
        beforeSend: function () {
            if(this.beforeload){
                $(this.target).html($('#loading').html());
            }
        },
        success   : function (data) {
            var target = this.target;
            if (data && data.redirect_uri) {
                window.location.replace(data.redirect_uri);
                return false;
            }
            $(target).html(data);
            init_plugins("#" + $(target).attr('id'));
            dealWithCodePromoWebsiteContext();
        },
        error     : function (xhr) {
            var target = this.target;
            erreur_generale(xhr, '#' + $(target).attr('id'));
            conteneur = $(target).attr('id');
            $('#' + conteneur + ' .loading-spinner').replaceWith('<i class="icon-warning-sign red bigger-300"></i>');
        }
    });
}

var DependantFilteredSelect = function ($changedFields, $dependantSelect) {
    var patternValue = [];
    // Liste des pattern de filtrage
    for (var i = 0; i < $changedFields.length; ++i) {
        var fieldValue = $($changedFields[i]).val();
        if(patternValue.length === 0){
            patternValue.push('-' + fieldValue);
            patternValue.push('-0');
        } else {
            var tempPatternValue = [];
            for (var j = 0; j < patternValue.length; ++j) {
                var pattern = patternValue[j];
                tempPatternValue.push(pattern + '-' + fieldValue);
                tempPatternValue.push(pattern + '-0');
            }
            patternValue = tempPatternValue;
        }
    }
    if ($dependantSelect) {
        var $toHide = $dependantSelect.find('option[value!=""]');
        $toHide.hide();
        $toHide.each(function () {
            if ($(this).parent().is("span")) {
                $(this).unwrap();
            }
            $(this).wrap("<span>");
        });
        $toHide.promise().done(function () {
            var $toShow = $dependantSelect.find('option').filter(function (index) {
                var found = false;
                for (var i = 0; i < patternValue.length; ++i) {
                    var pattern = patternValue[i];
                    var dependant_value = $(this).data('dependant-value');
                    if(dependant_value !== undefined){
                        for (var j = 0; j < dependant_value.length; ++j) {
                            if(dependant_value[j] === pattern){
                                found = true;
                            }
                        }
                    }
                }
                return found;
            });
            $toShow.show();
            $toShow.unwrap();
            $toShow.promise().done(function () {
                $dependantSelect.trigger("chosen:updated");
            });
        });
    }
};

var DependantAjaxAutoFill = function ($changedField, $dependantField) {
    if ($dependantField) {
        var value = $changedField.val();
        var url = $dependantField.data('ajax-autofill-url');
        var $is_form = $.inArray($dependantField.prop('tagName'), ['input', 'select', 'textarea']) !== -1;

        $.ajax({
            context   : [$dependantField, $is_form],
            url       : url,
            type      : 'get',
            data      : {'identifier': value},
            dataType  : 'json',
            beforeSend: function () {
                if ($is_form) {
                    $dependantField.append('<i class="icon-spinner icon-spin" id="' + $dependantField.attr('id') + '_spinner"></i>');
                } else {
                    $dependantField.html($('#loading').html());
                }
            },
            success   : function (data) {
                if ($is_form) {
                    $.remove('#' + $dependantField.attr('id') + '_spinner');
                    $dependantField.val(data.fillWith);
                } else {
                    $dependantField.html(data.fillWith);
                }
            }
        });
    }
};

var codePromoWebSiteContext = function ($select) {
    var selectWebsiteId = $select.val();
    var $form = $select.parents('form');
    var $codePromoSelect = $form.find('#kgc_RdvBundle_rdv_codepromo');

    if ($codePromoSelect) {
        var $toHide = $codePromoSelect.find("option");
        $toHide.hide();
        $toHide.promise().done(function () {
            var $toShow = $codePromoSelect.find("option[data-website-id='" + selectWebsiteId + "']");
            $toShow.show();
            $toShow.promise().done(function () {
                $codePromoSelect.trigger("chosen:updated");
            });
        });
    }
};
var stateContext = function ($select) {
    var hasState = $select
        .find('option[data-has-calendar="1"]')
        .filter(':selected')
        .get(0)
        ;
    var $form = $select.parents('form');
    var $StateSelected = $form.find('#kgc_userbundle_prospect_state');
    var $statedateInput = $form.find('#kgc_userbundle_prospect_dateState');

    var $stateSelectTr = $StateSelected.parents('tr');
    $stateSelectTr = $stateSelectTr.get(0) ? $stateSelectTr : $StateSelected.parents('.form-group');

    var $statedateInputTr = $statedateInput.parents('tr');
    $statedateInputTr = $statedateInputTr.get(0) ? $statedateInputTr : $statedateInput.parents('.form-group');

    $statedateInputTr.hide();
    if (hasState && $(hasState).val() > 0) {
        $statedateInputTr.show();
    }

};

var stateContextRdv = function ($select) {
    var hasState = $select
        .find('option[data-has-calendar="1"]')
        .filter(':selected')
        .get(0)
        ;
    var $dateSpan = $select.closest('td').find('.dateSpan'), idRdv = $dateSpan.attr('data-id');
    $dateSpan.html('');
    if (hasState && $(hasState).val() > 0) {
        $dateSpan.html('<input type="text" id="dateStateOf'+idRdv+'" value="" name="rdv['+idRdv+'][dateState]" class="date-picker-hours form-control">');
        $dateSpan.find('.date-picker-hours').datetimepicker({
            format  : "dd/mm/yyyy hh:ii",
            language: "fr",
        });
    }

};

var sourceWebsiteContext = function ($select) {
    var hasSource = $select
        .find('option[data-has-source="1"]')
        .filter(':selected')
        .get(0)
        ;
    var $form = $select.parents('form');
    var $sourceSelect = $form.find('#kgc_RdvBundle_rdv_source');
    if (!$sourceSelect.length) {
        $sourceSelect = $form.find('#kgc_userbundle_prospect_source');
    }
    var $gclidInput = $form.find('#kgc_RdvBundle_rdv_gclid');
    if (!$gclidInput.length) {
        $gclidInput = $form.find('#kgc_userbundle_prospect_gclid');
    }
    var $formUrlInput = $form.find('#kgc_RdvBundle_rdv_formurl');
    if (!$formUrlInput.length) {
        $formUrlInput = $form.find('#kgc_userbundle_prospect_formurl');
    }

    var $sourceSelectTr = $sourceSelect.parents('tr');
    $sourceSelectTr = $sourceSelectTr.get(0) ? $sourceSelectTr : $sourceSelect.parents('.form-group');

    var $gclidInputTr = $gclidInput.parents('tr');
    $gclidInputTr = $gclidInputTr.get(0) ? $gclidInputTr : $gclidInput.parents('.form-group');

    var $formUrlInputTr = $formUrlInput.parents('tr');
    $formUrlInputTr = $gclidInputTr.get(0) ? $formUrlInputTr : $formUrlInput.parents('.form-group');

    $sourceSelectTr.hide();
    $gclidInputTr.hide();
    $formUrlInputTr.hide();
    if (hasSource) {
        $sourceSelectTr.show(function () {
            gclidWebsiteContext($sourceSelect);
        });
        $formUrlInputTr.show();
    }

    checkSourceOrMessage(hasSource, $sourceSelect.val());
};

var gclidWebsiteContext = function ($select) {
    var hasGclid = $select
        .find('option[data-has-gclid="1"]')
        .filter(':selected')
        .get(0)
        ;
    var $form = $select.parents('form');
    var $gclidInput = $form.find('#kgc_RdvBundle_rdv_gclid');
    if (!$gclidInput.length) {
        $gclidInput = $form.find('#kgc_userbundle_prospect_myastroGclid');
    }
    var $gclidInputTr = $gclidInput.parents('tr');
    $gclidInputTr = $gclidInputTr.get(0) ? $gclidInputTr : $gclidInput.parents('.form-group');
    $gclidInputTr.hide();
    if (hasGclid && $select.is(':visible')) {
        $gclidInputTr.show();
    }
    checkGClidOrMessage(hasGclid, $gclidInput.val());
};

var checkSourceOrMessage = function (hasSource, sourceVal) {
    var divId = "error-source";
    $('#' + divId).remove();

    if (hasSource) {
        if (sourceVal.length == 0) {
            $('.modal-footer').prepend('' +
                '<div class="pull-right blink_me" id="' + divId + '">' +
                '<i class="icon-exclamation-sign bigger-120"></i> Aucune source n\'a été saisie !</div>');
        }
    }
    checkGClidOrMessage($('#kgc_RdvBundle_rdv_gclid').is(':visible'), $('#kgc_RdvBundle_rdv_gclid').val());
};

var checkGClidOrMessage = function (hasGclid, gclidVal) {
    var divId = "error-gclid";
    $('#' + divId).remove();
    if (hasGclid) {
        if (gclidVal.length == 0) {
            $('.modal-footer').prepend('' +
                '<div class="pull-right blink_me" id="' + divId + '">' +
                '<i class="icon-exclamation-sign bigger-120"></i> Aucun GCLID n\'a été saisi !</div>');
        }
    }
};

var copyWysiwygContent = function () {
    var $target = $('.wysiwyg-editor');
    var $content = $('.js-wysiwyg-content');
    if ($target && $content) {
        $target.html($content.text());
    }
};

var loadWysiwyg = function () {
    $('#wysiwyg-editor-1').ace_wysiwyg({
        toolbar      : [
            {
                name  : 'font',
                title : 'Custom tooltip',
                values: ['Arial', 'Verdana', 'Comic Sans MS']
            },
            null,
            {
                name  : 'fontSize',
                title : 'Custom tooltip',
                values: {1: 'Taille 1', 2: 'Taille 2', 3: 'Taille 3', 4: 'Taille 4', 5: 'Taille 5'}
            },
            null,
            {name: 'bold', title: 'Custom tooltip'},
            {name: 'italic', title: 'Custom tooltip'},
            {name: 'strikethrough', title: 'Custom tooltip'},
            {name: 'underline', title: 'Custom tooltip'},
            null,
            'insertunorderedlist',
            'insertorderedlist',
            'outdent',
            'indent',
            null,
            {name: 'justifyleft'},
            {name: 'justifycenter'},
            {name: 'justifyright'},
            {name: 'justifyfull'},
            null,
            {
                name        : 'createLink',
                placeholder : 'Custom PlaceHolder Text',
                button_class: 'btn-purple',
                button_text : 'Custom TEXT'
            },
            {name: 'unlink'},
            null,
            {
                name               : 'insertImage',
                placeholder        : 'Custom PlaceHolder Text',
                button_class       : 'btn-inverse',
                choose_file        : false,//hide choose file button
                button_text        : 'Set choose_file:false to hide this',
                button_insert_class: 'btn-pink',
                button_insert      : 'Insert Image'
            },
            null,
            {
                name  : 'foreColor',
                title : 'Custom Colors',
                values: ['red', 'green', 'blue', 'navy', 'orange'],
                /**
                 You change colors as well
                 */
            },
            /**null,
             {
                 name:'backColor'
             },*/
            null,
            {name: 'undo'},
            {name: 'redo'},
            null,
            'viewSource'
        ],
        speech_button: false,//hide speech button on chrome

        'wysiwyg': {
            hotKeys: {} //disable hotkeys
        }

    }).prev().addClass('wysiwyg-style2');
};

function select_field(key) {
    return $("*[id*='" + key + "']");
}

function enable_fields($enable, $element) {
    fields = $element.data('enable').split(';');
    var edit = (typeof $enable !== 'undefined' ? $enable : true) === $element.is(':checked');
    for (var key = 0; key < fields.length; ++key) {
        select_field(fields[key]).each(function (index) {
            var $this = $(this);

            if ($this.prop('tagName') === 'SELECT') {
                value = $this.val();
                $this.attr('value', value);
            }
            if (edit) {
                enableable = $this.data('enableable');
                if ((typeof enableable === 'undefined' || enableable === 1) && $enable) {
                    $this.removeAttr('readonly');
                    if ($this.attr('disabled') !== 'undefined') {
                        $this.removeAttr('disabled');
                        $this.attr('originally-disabled', 1);
                    }
                    if ($this.hasClass('chosen-select')) {
                        $this.trigger('chosen:updated');
                    }
                    if (index === 1) {
                        $this.focus();
                    }
                } else {
                    if (!$enable) {
                        if ($this.attr('disabled') !== 'undefined') {
                            $this.removeAttr('disabled');
                        }
                    }
                }
                $("*[id*='" + fields[key] + "']:not(select):not(.chosen-container)").addClass('text-orange');
            } else {
                if (!$enable) {
                    $this.attr('disabled', 'disabled');
                } else {
                    $this.attr('readonly', 'readonly');
                    if ($this.attr('originally-disabled') == 1) {
                        $this.attr('disabled', 'disabled');
                    }
                    if ($this.attr('originally-checked') == 1) {
                        $this.attr('checked', 'checked');
                    }
                    if ($this.prop('tagName') === 'SELECT') {
                        $this.val($this.attr('value')).change();
                    }
                    if ($this.hasClass('chosen-select')) {
                        $this.trigger('chosen:updated');
                    }
                    $this.val($this.attr('value'));
                }

                $("*[id*='" + fields[key] + "']").removeClass('text-orange');
            }
        });
    }
};

var dealWithStateContext = function () {
    $('.js-state-select').each(function () {
        stateContext($(this));
    });
};

var dealWithCodePromoWebsiteContext = function () {
    $('.js-website-select').each(function () {
        codePromoWebSiteContext($(this));
        sourceWebsiteContext($(this));
    });
    if ($('#kgc_RdvBundle_rdv_source').length) {
        $('#kgc_RdvBundle_rdv_source').each(function () {
            gclidWebsiteContext($(this));
        });
        var timerGclid = null;

        $('#kgc_RdvBundle_rdv_gclid').on('keyup, change, paste, click', function () {
            if (null !== timerGclid) {
                clearTimeout(timerGclid);
            }

            timerGclid = setTimeout(function () {
                checkGClidOrMessage(true, $(this).val());
            }.bind(this), 500);
        });
        $('#kgc_RdvBundle_rdv_source').on('change', function () {
            checkSourceOrMessage(true, $(this).val());
        });

        $('#kgc_RdvBundle_rdv_client_prenom, #kgc_RdvBundle_rdv_client_mail, #kgc_RdvBundle_rdv_numtel1, #kgc_RdvBundle_rdv_numtel2, #kgc_RdvBundle_rdv_idProspect').on('blur', function (ev) {
            similarityCheck($('.js-search-similarity'));
        });
    }
    else {
        $('#kgc_userbundle_prospect_source').each(function () {
            gclidWebsiteContext($(this));
        });
        var timerGclid = null;

        $('#kgc_userbundle_prospect_gclid').on('keyup, change, paste, click', function () {
            if (null !== timerGclid) {
                clearTimeout(timerGclid);
            }

            timerGclid = setTimeout(function () {
                checkGClidOrMessage(true, $(this).val());
            }.bind(this), 500);
        });
        $('#kgc_userbundle_prospect_source').on('change', function () {
            checkSourceOrMessage(true, $(this).val());
        });

        $('#kgc_userbundle_prospect_client_nom, #kgc_userbundle_prospect_client_mail').on('blur', function (ev) {
            similarityCheck($('.js-search-similarity'));
        });
    }

    similarityCheck($('.js-search-similarity'));
};

function modal_load(link) {
    var $modal = $('#modal-dialog');

    $modal.modal({backdrop: 'static'}).modal('show');
    $modal.html($('#default-modal-dialog').html());
    $modal.load($(link).attr('href'), function (a) {
        init_plugins('#modal-dialog');
        dealWithCodePromoWebsiteContext();
        dealWithStateContext();
    });
}

function refresh(ids) {
    a = ids.split(',');
    for (var key = 0; key < a.length; ++key) {
        ajax_load($(a[key]));
    }
}

var luhnChk = (function (arr) {
    return function (ccNum) {
        var
            len = ccNum.length,
            bit = 1,
            sum = 0,
            val;

        while (len) {
            val = parseInt(ccNum.charAt(--len), 10);
            sum += (bit ^= 1) ? arr[val] : val;
        }

        return sum && sum % 10 === 0;
    };
}([0, 2, 4, 6, 8, 1, 3, 5, 7, 9]));

var prePaidChk = function (ccNum) {
    if (!ccNum.length) {
        return false;
    }
    var prePaid = [
        "457737",
        "554951",
        "475742",
        "530446",
        "497010",
        "538667",
        "541275",
        "531306",
        "533935",
        "531422",
        "529565",
        "533840",
        "530558",
        "554700",
        "533900"
    ];

    return -1 !== $.inArray(ccNum.substr(0, 6), prePaid);
};

var luhnClean = function (string) {
    return string.replace(/\D/g, '');
};

var similarityCheck = function ($link) {
    var url = $link.attr('href');
    var $form = $link.parents('form');
    var $result_target = '.js-results-similarity';
    var $result = $($result_target);

    $.ajax({
        type      : "POST",
        url       : url,
        data      : $form.find('input#kgc_RdvBundle_rdv_client_nom,' +
            'input#kgc_RdvBundle_rdv_idProspect,' +
            'input#kgc_RdvBundle_rdv_client_prenom,' +
            'input#kgc_RdvBundle_rdv_client_mail,' +
            'input#kgc_RdvBundle_rdv_numtel1,' +
            'input#kgc_RdvBundle_rdv_numtel2').serialize(),
        beforeSend: function () {
            $result.html($('#loading').html());
        },
        success   : function (data) {
            $result.html(data);
        },
        error     : function (xhr) {
            erreur_generale(xhr, $result_target);
        }
    });
};

jQuery(function ($) {
    var $document = $(document);

    var luhnTimer = (function () {
        var timer = null;
        return function tmp($obj) {

            if (null !== timer) {
                clearTimeout(timer);
                timer = null;
            }
            timer = setTimeout(function () {
                var value = luhnClean($obj.val());
                var $parent = $obj.parent();
                var luhnResult = luhnChk(value);
                var prePaidResult = prePaidChk(value);
                var resultClass = luhnResult ? 'js-luhn-input-ok' : (prePaidResult ? 'js-luhn-input-fatal' : 'js-luhn-input-error');
                $obj.parent().find('.js-luhn-text-error').remove();
                $obj.parent().find('.js-luhn-text-fatal').remove();
                $obj.removeClass('js-luhn-input-ok').removeClass('js-luhn-input-error').removeClass('js-luhn-input-fatal');
                if (value.length) {
                    if (!luhnResult) {
                        $obj.parent().append('<div class="js-luhn-text-error">CB invalide !</div>');
                    }
                    if (prePaidResult) {
                        $obj.parent().append('<div class="js-luhn-text-fatal">CB prépayée !!!</div>');
                    }
                    $obj.addClass(resultClass);
                }
            }, 500);
        };
    })();

    $document.on('keyup', '.js-check-luhn', function (ev) {
        luhnTimer($(this));
    });

    $document.ajaxStop(function () {
        var $elements = $('.js-check-luhn');
        if ($elements.length) {
            for (var i = 0, tot = $elements.length; i < tot; i++) {
                luhnTimer($($elements[i]));
            }
        }
    });

    /* DataTable */
    $.extend(true, $.fn.dataTable.defaults, {
        "pageLength": 25,
        "language"  : {
            "processing"  : "Traitement en cours...",
            "lengthMenu"  : "Afficher _MENU_ éléments",
            "zeroRecords" : "Aucun élément à afficher",
            "info"        : "Affichage de l'élément _START_ à _END_ sur _TOTAL_ éléments",
            "infoEmpty"   : "Affichage de l'élément 0 à 0 sur 0 éléments",
            "infoFiltered": "(filtré de _MAX_ éléments au total)",
            "infoPostFix" : "",
            "search"      : "Rechercher : ",
            "url"         : "",
            "paginate"    : {
                "first"   : "Premier",
                "previous": "Précédent",
                "next"    : "Suivant",
                "last"    : "Dernier"
            }
        }
    });

    /* Initialisation plugins */
    init_plugins();

    /* -------------------
     * --   Gritters    --
     * -------------------- */

    $document.on('click', '.gritter-remove', function () {
        $.gritter.removeAll();
        return false;
    });

    $document.on('click', '.btn-hide-show', function () {
        var $this = $(this), $rows = $('.' + $this.data("hide-show"));
        if ($rows.first().hasClass('hide')) {
            $rows.removeClass('hide');
            $this.html("-");
        } else {
            $rows.addClass('hide');
            $this.html("+");
        }
        return false;
    });

    /* -----------------------------
     * -- Chargements asynchrones --
     * ----------------------------- */

    $document.on('click', '.ajax_load_link', function () {
        ajax_load(this);
        return false;
    });

    $document.on('click', '.ajax_reload', function () {
        refresh($(this).attr('href'));
        return false;
    });

    $document.on('click', 'a.gritter-load', function () {
        var e = this;
        $.ajax({
            context : e,
            url     : $(this).attr('href'),
            type    : 'get',
            dataType: 'json',
            success : function (data) {

                if (data && data.redirect_uri) {
                    window.location.replace(data.redirect_uri);
                    return false;
                }

                refreshids = $(e).data('refresh');
                if (refreshids) {
                    refresh(refreshids);
                }

                if (data.gritter !== undefined) {
                    var grit = $.gritter.add({
                        title     : data.title,
                        text      : data.msg,
                        class_name: 'gritter-' + data.class,
                        image     : 'img/' + data.img,
                        sticky    : true
                    });
                }
            }
        });
        return false;
    });

    $document.on('click', "button.js-card-pause", function (e) {
        var $form = $(this).closest('form');
        $form.attr("data-href-alt", $(this).attr("data-href"));
        $form.attr("data-action-alt", $(this).attr("data-main-action"));
    });

    /* -------------------------
     * -- Formulaires en Ajax --
     * ------------------------- */
    $document.on('submit', 'form.ajax_load', function (ev) {
        var e = this;

        // Dealing with PAUSE
        var href = $(this).attr('data-href-alt');
        $(this).attr('data-href-alt', null);
        href = href ? href : $(this).attr('action');

        var action = $(this).attr('data-action-alt');
        $(this).attr('data-action-alt', null);
        var $actionField = $(this).find("#kgc_RdvBundle_rdv_mainaction");
        var previousAction = $actionField.val();
        if (action) {
            $actionField.val(action);
        }

        $.ajax({
            context   : e,
            url       : href,
            type      : $(this).attr('method'),
            data      : $(this).serialize(),
            beforeSend: function () {
                $(e).html($('#loading').html());
            },
            success   : function (data) {
                if (data && data.redirect_uri) {
                    window.location.replace(data.redirect_uri);
                    return false;
                }
                $(e).html(data);
                init_plugins("#" + $(e).attr('id'));
                var form = $('#' + $(e).attr('id'));
                var refreshids = $(form).data('refresh');
                if (refreshids) {
                    refresh(refreshids);
                }
            },
            error     : function (xhr) {
                erreur_generale(xhr, '#' + $(e).attr('id'));
                ajax_load(e);
            },
            complete  : function () {
                $(this).find("#kgc_RdvBundle_rdv_mainaction").val(previousAction);
            }
        });
        return false;
    });

    var manageFilePost = function ($elem, ev) {
        var $frame = $('<iframe id="uploadTrg" name="uploadTrg" height="0" width="0" frameborder="0" scrolling="yes"></iframe>');
        var $form = $elem.parents('form');
        $('body').append($frame);
        $("#uploadTrg").load(function () {
            var iframeContents = this.contentWindow.document.body.innerHTML;
            var modal$ = $('#modal-dialog');
            modal$.html(iframeContents);
            init_plugins('#modal-dialog');
            modal$.modal('show');
            $frame.remove();
        });

        $form.attr('target', 'uploadTrg');
        $form.submit();

        $('#modal-dialog').html($('#default-modal-dialog').html());

        return false;
    };

    $document.on('submit', '.ajax_modal_form', function (ev) {
        var e = this;
        var $data = $(this).serialize();
        var $fileInput = $(this).find('input.js-file');
        if ($fileInput.length) {
            var files = $fileInput[0].files;
            $.each(files, function (key, value) {
                $data.append(key, value);
            });
        }

        $.ajax({
            context   : e,
            url       : $(this).attr('action'),
            type      : $(this).attr('method'),
            data      : $data,
            beforeSend: function () {
                $('#modal-dialog').html($('#default-modal-dialog').html());
            },
            success   : function (data) {
                if (data && data.redirect_uri) {
                    window.location.replace(data.redirect_uri);
                    return false;
                }
                var modal$ = $('#modal-dialog');
                modal$.html(data);
                init_plugins('#modal-dialog');
                modal$.modal('show');
                var form = $('#' + $(e).attr('id'));
                if ($(form).data('close')) {
                    $('#modal-dialog').modal('hide');
                }
                refreshids = $(form).data('refresh');
                if (refreshids) {
                    refresh(refreshids);
                }
            }
        });
        return false;
    });

    /* ----------------------
     * -- Fenêtres modales --
     * ---------------------- */

    $('#modal-dialog').html($('#default-modal-dialog').html());

    $('a.modal-auto-load').each(function () {
        modal_load($(this));
    });

    $document.on('click', 'a.modal-load:not([disabled])', function () {
        modal_load($(this));
        return false;
    });

    // à la fermeture de la fenêtre modale
    $document.on('hidden.bs.modal', function (e) {
        // il faut en supprimer les données
        // et remettre le code par défaut (chargement)
        $(e.target).removeData("bs.modal").html($('#default-modal-dialog').html());
    });
    // + http://stackoverflow.com/questions/12286332/twitter-bootstrap-remote-modal-shows-same-content-everytime

    $document.on('click', 'button[data-ajax-url]', function(){
        $('#modal-dialog').html($('#default-modal-dialog').html());

        $.get($(this).data('ajaxUrl'), function(response){
            $(response).find('.gritter').each(applyGritter);
            $('#modal-dialog').modal('hide');
        });
    });

    /* ---------------------
     * --  Display Switch --
     * --------------------- */
    $document.on('change', '.display-switch', function () {
        target = $(this).data('displaytarget');
        if ($(this).is(':checked')) {
            $('#' + target + '_0').removeClass('active');
            $('#' + target + '_1').addClass('active');
        } else {
            $('#' + target + '_1').removeClass('active');
            $('#' + target + '_0').addClass('active');
        }
    });

    /* ---------------------
     * -- Switchs Edition --
     * --------------------- */
    $document.on('change', '.enable-fields', function () {
        enable_fields(true, $(this));
    });
    $document.on('change', '.disable-fields', function () {
        enable_fields(false, $(this));
    });

    /*---------------------------
     * -- Date search shortcuts --
     * --------------------------- */

    $document.on('click', '.js-wysiwyg-btn', function () {
        $('.js-wysiwyg-content').val(
            $('#wysiwyg-editor-1').html()
        );
    });

    $document.on('click', '#prepare_mail_rdv_modal .mail-list tr', function (ev) {
        var content = $('#wysiwyg-editor-1').html();
        var isEmpty = $.trim(content) === "";
        if (isEmpty || !isEmpty && confirm('Attention, le contenu du message va etre écrasé !')) {
            var $element = $(this);
            var id = $element.find('.mail-id');
            var attach = $element.find('.mail-attachment');
            var subject = $element.find('.mail-subject');
            var html = $element.find('.hidden.mail-html');
            var $container = $('#prepare_mail_rdv_modal');

            $container.find('.attachment-target').html(attach.html());
            $container.find('#kgc_RdvBundle_rdv_mail_sent_mail').val(parseInt(id.html(), 10));
            $container.find('#kgc_RdvBundle_rdv_mail_sent_subject').val($.trim(subject.html()));
            $('#wysiwyg-editor-1').html($.trim(html.html()));
        }

        ev.preventDefault();
        ev.stopPropagation();

    });

    $document.on('click', '#prepare_mail_chat_modal .mail-list tr', function (ev) {
        var content = $('#wysiwyg-editor-1').html();
        var isEmpty = $.trim(content) === "";
        if (isEmpty || !isEmpty && confirm('Attention, le contenu du message va etre écrasé !')) {
            var $element = $(this);
            var id = $element.find('.mail-id');
            var attach = $element.find('.mail-attachment');
            var subject = $element.find('.mail-subject');
            var html = $element.find('.hidden.mail-html');
            var $container = $('#prepare_mail_chat_modal');

            $container.find('.attachment-target').html(attach.html());
            $container.find('#kgc_ClientBundle_mail_mail_sent_mail').val(parseInt(id.html(), 10));
            $container.find('#kgc_ClientBundle_mail_mail_sent_subject').val($.trim(subject.html()));
            $('#wysiwyg-editor-1').html($.trim(html.html()));
        }

        ev.preventDefault();
        ev.stopPropagation();

    });

    $document.on('click', '#prepare_mail_rdv_modal button[type="submit"]', function (ev) {
        if (confirm("Voulez-vous vraiment envoyer ce mail ?")) {
            $('#prepare_mail_rdv_modal').find('#kgc_RdvBundle_rdv_mail_sent_html').val(
                $('#wysiwyg-editor-1').html()
            );
            manageFilePost($(this), ev);
        } else {
            ev.preventDefault();
            ev.stopPropagation();
            return false;
        }
    });

    $document.on('change', 'select#kgc_RdvBundle_rdv_mail_sent_mail', function () {

        var content = $('#wysiwyg-editor-1').html();
        var isEmpty = $.trim(content) === "";

        if (isEmpty || !isEmpty && confirm('Attention, le contenu du message va être écrasé !')) {
            var $select = $(this);
            var value = $select.val();
            var $container = $('#prepare_mail_rdv_modal');

            $.ajax({
                url    : BuildMailBaseUrl,
                type   : 'POST',
                data   : {id: value, rdv: $select.attr('data-rdv')},
                success: function (data) {
                    if (data && data.subject && data.html) {

                        var billCode = $('.billing-mail-code').html();
                        var $none = $(".attachment-target .none");
                        var $link = $(".attachment-target a");
                        var option = $select.find('option:selected').html();

                        if (option.indexOf(billCode) >= 0) {
                            $link.removeClass('hidden');
                            $none.addClass('hidden');
                        } else {
                            $none.removeClass('hidden');
                            $link.addClass('hidden');
                        }

                        $container.find('#kgc_RdvBundle_rdv_mail_sent_subject').val($.trim(data.subject));
                        //$container.find('#kgc_RdvBundle_rdv_mail_sent_html').val($.trim(data.html));
                        $('#wysiwyg-editor-1').html($.trim(data.html));
                    }
                },
                error  : function (xhr) {
                    erreur_generale(xhr);
                }
            });
        }
    });

    $document.on('click', '#prepare_mail_chat_modal button[type="submit"]', function (ev) {
        if (confirm("Voulez-vous vraiment envoyer ce mail ?")) {
            $('#prepare_mail_rdv_modal').find('#kgc_ClientBundle_mail_mail_sent_html').val(
                $('#wysiwyg-editor-1').html()
            );
            manageFilePost($(this), ev);
        } else {
            ev.preventDefault();
            ev.stopPropagation();
            return false;
        }
    });
    $document.on('change', 'select#kgc_ClientBundle_mail_mail_sent_mail', function () {

        var content = $('#wysiwyg-editor-1').html();
        var isEmpty = $.trim(content) === "";

        if (isEmpty || !isEmpty && confirm('Attention, le contenu du message va être écrasé !')) {
            var $select = $(this);
            var value = $select.val();
            var $container = $('#prepare_mail_chat_modal');

            $.ajax({
                url    : BuildMailChatBaseUrl,
                type   : 'POST',
                data   : {id: value, client: $select.attr('data-client')},
                success: function (data) {
                    if (data && data.subject && data.html) {

                        var billCode = $('.billing-mail-code').html();
                        var $none = $(".attachment-target .none");
                        var $link = $(".attachment-target a");
                        var option = $select.find('option:selected').html();

                        if (option.indexOf(billCode) >= 0) {
                            $link.removeClass('hidden');
                            $none.addClass('hidden');
                        } else {
                            $none.removeClass('hidden');
                            $link.addClass('hidden');
                        }

                        $container.find('#kgc_ClientBundle_mail_mail_sent_subject').val($.trim(data.subject));
                        $container.find('#kgc_ClientBundle_mail_mail_sent_html').val($.trim(data.html));
                        $('#wysiwyg-editor-1').html($.trim(data.html));
                    }
                },
                error  : function (xhr) {
                    erreur_generale(xhr);
                }
            });
        }
    });

    $document.on('click', '#prepare_sms_rdv_modal .sms-list tr', function (ev) {
        var content = $('#wysiwyg-editor-1').html();
        var isEmpty = $.trim(content) === "";
        if (isEmpty || !isEmpty && confirm('Attention, le contenu du message va etre écrasé !')) {
            var $element = $(this);
            var id = $element.find('.sms-id');
            var text = $element.find('.hidden.sms-text');
            var $container = $('#prepare_sms_rdv_modal');

            $container.find('#kgc_RdvBundle_rdv_sms_sent_mail').val(parseInt(id.html(), 10));
            $('#wysiwyg-editor-1').html($.trim(html.html()));
        }

        ev.preventDefault();
        ev.stopPropagation();

    });

    $document.on('click', '#prepare_sms_rdv_modal button[type="submit"]', function (ev) {
        if (confirm("Voulez-vous vraiment envoyer ce sms ?")) {
            // $('#prepare_sms_rdv_modal').find('#kgc_RdvBundle_rdv_sms_sent_html').val(
            //     $('#wysiwyg-editor-1').html()
            // );
        } else {
            ev.preventDefault();
            ev.stopPropagation();
            return false;
        }
    });

    $document.on('change', 'select#kgc_RdvBundle_rdv_sms_sent_sms', function () {

        var content = $('#wysiwyg-editor-1').html();
        var isEmpty = $.trim(content) === "";

        if (isEmpty || !isEmpty && confirm('Attention, le contenu du message va être écrasé !')) {
            var $select = $(this);
            var value = $select.val();
            var $container = $('#prepare_sms_rdv_modal');

            $.ajax({
                url    : BuildSmsBaseUrl,
                type   : 'POST',
                data   : {id: value, rdv: $select.attr('data-rdv')},
                success: function (data) {
                    if (data && data.text) {
                        var $textarea = $container.find('#kgc_RdvBundle_rdv_sms_sent_text');
                        $textarea.val($.trim(data.text));
                        $('#wysiwyg-editor-1').html($.trim(data.text));
                        countSms($textarea, $("#showNbCount"), $textarea.attr('data-max'));
                    }
                },
                error  : function (xhr) {
                    erreur_generale(xhr);
                }
            });
        }
    });

    $document.on('click', '#prepare_sms_chat_modal .sms-list tr', function (ev) {
        var content = $('#wysiwyg-editor-1').html();
        var isEmpty = $.trim(content) === "";
        if (isEmpty || !isEmpty && confirm('Attention, le contenu du message va etre écrasé !')) {
            var $element = $(this);
            var id = $element.find('.sms-id');
            var text = $element.find('.hidden.sms-text');
            var $container = $('#prepare_sms_chat_modal');

            $container.find('#kgc_ClientBundle_client_sms_sent_mail').val(parseInt(id.html(), 10));
            $('#wysiwyg-editor-1').html($.trim(html.html()));
        }

        ev.preventDefault();
        ev.stopPropagation();

    });

    $document.on('click', '#prepare_sms_chat_modal button[type="submit"]', function (ev) {
        if (confirm("Voulez-vous vraiment envoyer ce sms ?")) {
            // $('#prepare_sms_chat_modal').find('#kgc_ClientBundle_client_sms_sent_html').val(
            //     $('#wysiwyg-editor-1').html()
            // );
        } else {
            ev.preventDefault();
            ev.stopPropagation();
            return false;
        }
    });

    $document.on('change', 'select#kgc_ClientBundle_client_sms_sent_sms', function () {

        var content = $('#wysiwyg-editor-1').html();
        var isEmpty = $.trim(content) === "";

        if (isEmpty || !isEmpty && confirm('Attention, le contenu du message va être écrasé !')) {
            var $select = $(this);
            var value = $select.val();
            var $container = $('#prepare_sms_chat_modal');

            $.ajax({
                url    : BuildSmsChatBaseUrl,
                type   : 'POST',
                data   : {id: value, client: $select.attr('data-client')},
                success: function (data) {
                    if (data && data.text) {
                        var $textarea = $container.find('#kgc_ClientBundle_client_sms_sent_text');
                        $textarea.val($.trim(data.text));
                        $('#wysiwyg-editor-1').html($.trim(data.text));
                        countSms($textarea, $("#showNbCount"), $textarea.attr('data-max'));
                    }
                },
                error  : function (xhr) {
                    erreur_generale(xhr);
                }
            });
        }
    });

    $document.on('click', ".js-search-date-intervalle input[type='radio']", function () {

        $.ajax({
            url    : $(this).parents('.js-search-date-intervalle').attr('action-ajax'),
            type   : 'POST',
            data   : $(this).serialize(),
            success: function (data) {
                $('.js-search-date-begin').val(data.begin);
                $('.js-search-date-end').val(data.end);
                $(this).parents('form').submit();
            }.bind(this),
            error  : function (xhr) {
                erreur_generale(xhr);
            }
        });
    });

    /* ---------------------
     * --   label select  --
     * --------------------- */

    $document.on('change', 'select.label', function (e) {
        $(this).removeClass(variable_class_to_remove);
        $(this).addClass($(this).find(':selected').attr('class'));
    });

    /* -----------------------
     * --  light on value   --
     * ----------------------- */

    $document.on('change', 'select.light-on-value', update_light_class);

    /* ---------------------
     * --  Compact Widget --
     * --------------------- */

    $document.on('click.ace.widget', '[data-action="compact"]', function (ev) {
        ev.preventDefault();

        var $this = $(this);
        var $box = $this.closest('.widget-box');

        if ($box.hasClass('ui-sortable-helper')) return;

        var event_name = $box.hasClass('compacted') ? 'show' : 'hide';

        var $body = $box.find('.widget-body');

        var $icon = $this.find('[class*=icon-]').eq(0);
        var $icon_class_actual = $icon.attr('class');
        var $icon_class_switch = $icon.data('switch-icon');
        $icon.removeClass($icon_class_actual);
        $icon.addClass($icon_class_switch);
        $icon.data('switch-icon', $icon_class_actual);

        var expandSpeed = 300;
        var collapseSpeed = 200;

        if (event_name === 'show') {
            $box.removeClass('compacted');
            $body.find('.compactit').each(function () {
                $(this).slideUp(0, function () {
                    $(this).slideDown(expandSpeed)
                })
            });
        } else {
            $body.find('.compactit').slideUp(collapseSpeed, function () {
                $box.addClass('compacted');
            });
        }
    });

    /* -----------------------------
     * --  Form submmit on change --
     * ----------------------------- */

    $document.on('change', '.submit-onchange', function (ev) {
        $(this).parents('form').submit();
    });

    var addPendulumTemplate = function (type) {
        var $template = $('#pendulum_question_template');
        var $wrapper = $('.historique .pendulum-wrapper');
        if ($wrapper && $template) {
            var $templateHtml = $template.html();
            var $count = $wrapper.find('tr').last().attr('id');
            if ($count !== undefined) {
                $count = parseInt($count.replace(/[^\d.]/g, ''), 10);
            } else {
                $count = 0;
            }
            var $i = $count + 1;

            $($templateHtml.replace(/__name__/g, $i)).appendTo($wrapper);
            $('#pendulum-' + $i).addClass('type-' + type);
            $('#pendulum-' + $i + ' .pendulum-type-field').val(type);
        }
    };

    $document.on('click', '.historique .js-add-pendulum-defined', function (ev) {
        ev.preventDefault();
        ev.stopPropagation();
        addPendulumTemplate('defined');
    });
    $document.on('click', '.historique .js-add-pendulum-custom', function (ev) {
        ev.preventDefault();
        ev.stopPropagation();
        addPendulumTemplate('custom');
    });

    $document.on('click', '.historique .js-remove-pendulum', function (ev) {
        ev.preventDefault();
        ev.stopPropagation();
        $(this).parents('.pendulum-line').remove();
    });

    $document.on('change', '.js-tarification, .js-forfait', function (ev) {
        ev.preventDefault();
        ev.stopPropagation();

        var $elem = $(this);
        var tarId = $(this).val();
        var forfaitId = tarId;

        if ($elem.hasClass('js-tarification')) {
            forfaitId = $('.js-forfait').val();
        }

        if ($elem.hasClass('js-forfait')) {
            tarId = $('.js-tarification').val();
        }

        var $targetForfait = $("option[data-forfait='" + forfaitId + "']");
        var $targetPrice = $targetForfait.attr('data-tar-' + tarId);

        if ($targetPrice) {
            $('.js-tar-amount').val(parseInt($targetPrice, 10)).keyup();
        } else {
            $('.js-tar-amount').val('').keyup();
        }

    });

    $document.on('change', '.price-calc-product, .price-calc-qt', function (ev) {
        ev.preventDefault();
        ev.stopPropagation();

        var $element = $(this);
        var $container = $element.parents('.price-calc-container');

        var $product = $container.find('.price-calc-product');
        var $product_price = $product.find(':selected').data('price');
        var $qt = $container.find('.price-calc-qt').val();

        var $price = $product_price * $qt;

        var $price_field = $container.find('.price-calc-field');
        $price_field.val($price);
        $price_field.trigger('keyup');

    });

    $document.on('change', '.historique .js-reminder-day', function (ev) {
        var day = $(this).val();
        var $target = $('.historique .js-original-date');
        if (day) {
            var val = day + ' ' + $('.historique .js-reminder-hour').val();
            $target.val(val);
        } else {
            $target.val('');
        }
    });

    $document.on('change', '.historique .js-reminder-hour', function (ev) {
        var hour = $(this).val();
        var $target = $('.historique .js-original-date');
        var day = $('.historique .js-reminder-day').val();
        if (hour && day) {
            var val = day + ' ' + hour;
            $target.val(val);
        } else {
            $target.val('');
        }
    });

    $document.on('click', '.js-reset-empty', function (ev) {
        ev.preventDefault();
        var $elem = $(this);
        var $form = $elem.parents('form');
        var $targets = $elem.data('reset-targets');
        if ($targets !== undefined) {
            var $elements = $targets.split(',');
        } else {
            var $elements = $form.find(':input');
        }
        for (var i = 0; i < $elements.length; i++) {
            var $e = $($elements[i]);
            if (!$e.hasClass('no-reset')) {
                $e.val('');
            }
            var resetVal = $e.data('reset-value');
            if (resetVal) {
                $e.val(resetVal)
            }
        }
        $form.submit();
        return false;
    });

    $document.on('click', '.js-search-similarity', function (ev) {
        ev.preventDefault();
        ev.stopPropagation();
        similarityCheck($(this));
    });

    $document.on('click', '.js-similarity-use', function (ev) {
        ev.preventDefault();
        ev.stopPropagation();
        var values = $(this).parents('tr').find('.js-similarity-field');

        for (var i = 0; i < values.length; i++) {
            $("#kgc_RdvBundle_rdv_" + $(values[i]).attr('data-field')).val($(values[i]).html());
            if ($(values[i]).attr('data-field') == 'formurl' || $(values[i]).attr('data-field') == 'support' || $(values[i]).attr('data-field') == 'codepromo') {
                $("#kgc_RdvBundle_rdv_" + $(values[i]).attr('data-field')).trigger('chosen:updated');
            } else {
                $("#kgc_RdvBundle_rdv_" + $(values[i]).attr('data-field')).trigger('change');
            }
        }

    });

    $document.on('click', '.js-page-target', function (ev) {
        ev.preventDefault();

        var $link = $(this);
        var $form = $link.parents('form');
        var $pageField = $form.find('.js-page-target');
        var pageValue = $link.data('page-target');

        $pageField.val(pageValue);
        $form.submit();
    });

    $document.on('keyup change', '.trigger_amount_calc', function (e) {
        var amount = 0;
        var $element = $(this);
        var $container = $element.parents('.auto_amount_calc');
        var ref, refItem;

        $container.find('.add_amount:enabled').each(function (e) {
            ref = $(this).data('ignoreIfRefNotEmpty');
            if (ref) {
                refItem = $(':input[data-ignore-ref="'+ref+'"]');
            }
            if (!ref || !refItem.val() || refItem.prop('disabled')) {
                amount += parseFloat($(this).val().replace(',', '.'), 10) || 0;
            }
        });

        $container.find('.sub_amount:enabled').each(function (e) {
            if (ref) {
                refItem = $(':input[data-ignore-ref="'+ref+'"]');
            }
            if (!ref || !refItem.val() || refItem.prop('disabled')) {
                amount -= parseFloat($(this).val().replace(',', '.'), 10) || 0;
            }
        });

        $container.find('.auto_amount_result').val(amount);
    });

    $document.on('keyup change', '.produits_montant_calc', calculateProductAmount);

    function calc_mt_min() {
        var idCodeTarif = $('#kgc_RdvBundle_rdv_tarification_code').val();
        var nbMin = parseInt($('#kgc_RdvBundle_rdv_tarification_temps').val());
        var decount10Min = $('#kgc_RdvBundle_rdv_tarification_decount10min').is(':checked');
        if (decount10Min) {
            nbMin -= 10;
        }
        if (idCodeTarif !== '' && nbMin > 0) {
            var action = MinutesAmountCalcBaseUrl + '/' + idCodeTarif + '/' + nbMin;
            $('#label-kgc_RdvBundle_rdv_tarification_montant_minutes').append('<i class="icon-refresh icon-spin bigger-130 pull-right"></i>');
            $.ajax({
                url    : action,
                type   : 'get',
                success: function (data) {
                    $('#label-kgc_RdvBundle_rdv_tarification_montant_minutes .icon-refresh').remove();
                    $('#kgc_RdvBundle_rdv_tarification_montant_minutes').val(data.mt).keyup();
                }
            });
        } else {
            $('#kgc_RdvBundle_rdv_tarification_montant_minutes').val(0).keyup();
        }
    }

    $document.on('keyup', '#kgc_RdvBundle_rdv_tarification_temps:not(:disabled)', calc_mt_min);
    $document.on('change', '#kgc_RdvBundle_rdv_tarification_decount10min:not(:disabled)', calc_mt_min);
    $document.on('change', '#kgc_RdvBundle_rdv_tarification_code:not(:disabled)', calc_mt_min);

    $document.on('change', '.js-website-select', function (e) {
        codePromoWebSiteContext($(this));
        sourceWebsiteContext($(this));
    });
    $document.on('change', '.js-state-select', function (e) {
        stateContext($(this));
    });
    $document.on('change', '.js-state-rdv-select', function (e) {
        stateContextRdv($(this));
    });

    $document.on('change', '.js-source-select', function (e) {
        gclidWebsiteContext($(this))
    });

    $(function () {
        var stayAlive = function () {
            $.ajax({
                type : "POST",
                url  : alivePath,
                async: true,
                cache: false
            });
            setTimeout(stayAlive, 1 * 60 * 1000);
        };
        stayAlive();
    });

    $document.on('click', '.ajax-load-history', function (ev) {
        ev.preventDefault();
        ev.stopPropagation();
        var path = $(this).attr('href');
        var $viewMore = $('.historique-content .js-view-more');
        $.ajax({
            url     : path,
            type    : 'GET',
            success : function (data) {
                $viewMore.replaceWith($(data).html());
            }.bind(this),
            error   : function () {
                erreur_generale();
            },
            complete: function () {

            }
        });
    });

    $document.on('change', '#form_statScope_statScope', function (ev) {
        var followElem, elems = $('#form_supports_supports option');

        for (var i = 0; i < elems.length; i++) {
            if (elems[i].text == 'Suivi Client') {
                followElem = $('#form_supports_supports option:eq(' + i + ')');
            }
        }

        var selectedScope = $('#form_statScope_statScope :checked')[0].value;
        var follow = selectedScope == 'follow', consult = selectedScope == 'consult';

        if (follow) {
            $("#form_supports_supports option").attr('selected', false).trigger("chosen:updated");
            followElem.attr('selected', true).trigger("chosen:updated");
        }
        else if (consult) {
            followElem.attr('selected', false).trigger("chosen:updated");
        }

        $("#form_supports_supports option").attr('disabled', follow).trigger("chosen:updated");

        followElem.attr('disabled', consult).trigger("chosen:updated");
    });

    $document.on('click', '.unsubscribe-buttons button[data-url][data-confirm]', function(e) {
        e.preventDefault();
        var $button = $(this);
        if (confirm($button.data('confirm'))) {
            $.ajax({
                url: $button.data('url'),
                type: 'POST',
                success: function(data){
                    location.reload();
                }
            });
        }
    });
});

/* -----------------------------------------------
 * --  Correction affichage datepicker in modal -- */
// Since confModal is essentially a nested modal it's enforceFocus method
// must be no-op'd or the following error results
// "Uncaught RangeError: Maximum call stack size exceeded"
// But then when the nested modal is hidden we reset modal.enforceFocus
var enforceModalFocusFn = $.fn.modal.Constructor.prototype.enforceFocus;

$.fn.modal.Constructor.prototype.enforceFocus = function () {
};

//$confModal.on('hidden', function() {
//    $.fn.modal.Constructor.prototype.enforceFocus = enforceModalFocusFn;
//});
//
//$confModal.modal({ backdrop : false });
jQuery(function($) {
  $('#envoie-de-produit').DataTable({
      paging: false,
      searching: false,
        "columnDefs": [
          {
              "targets": [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 11 ,12, 13 ],
              "orderable": false
          },
        ]
    });
  });
