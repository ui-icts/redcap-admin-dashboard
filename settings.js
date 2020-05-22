(function($, window, document) {
    $(document).ready(function () {
        jQuery.validator.addMethod("username", function(value, element) {
            var regEx = new RegExp('^[A-Za-z0-9\-\_\.]*$');

            return this.optional(element) || regEx.test(value);
        }, "User names can only contain letters, numbers, underscores, hyphens, and periods.");

        jQuery.validator.addMethod("notInArray", function(value, element, param) {
            var index = -1;
            var optionalCondition = false;

            if (value != '') {
                index = $.inArray(value, param[0]);
            }

            if (param[1] == 'report') {
                optionalCondition = index == UIOWA_AdminDash.currentReportConfigIndex;
            }

            return index == -1 || optionalCondition;
        }, 'Value must be unique.');

        jQuery.validator.addMethod("lettersonly", function(value, element) {
            return this.optional(element) || /^[a-z]+$/i.test(value);
        }, "Alphabetical characters only.");

        $('#newExecutiveUserForm').submit(false);

        $('#new-executive-username').autocomplete({
            source: app_path_webroot + "UserRights/search_user.php",
            minLength: 2,
            delay: 150,
            html: true,
            select: function (event, ui) {
                $(this).val(ui.item.value);
                return false;
            }
        });

        $.ajax({
            method: 'POST',
            url: UIOWA_AdminDash.requestHandlerUrl + '&type=getProjectList'
        })
        .done(function (data) {
            var projects = JSON.parse(data);
            var $projectSelects = $('.pid-select');

            $.each(projects, function (index, item) {
                $projectSelects.append($('<option>', {
                    value: item.value,
                    text : item.label
                }));
            });

            $projectSelects.append($('<option>', {
                value: 'other',
                text : 'Other'
            }));

            $('.report-list-loading-icon').hide();
            $('#reportTable').show();
        });

        function delay(fn, ms) {
            let timer = 0;
            return function(...args) {
                clearTimeout(timer);
                timer = setTimeout(fn.bind(this, ...args), ms || 0)
            }
        }

        $('.pid-other').keyup(delay(function(e) {
            $(".pid-select[data-project=" + $(this).data('project') + "]").trigger('change');
        }, 500));

        $('.pid-select').change(function () {
            var $this = $(this);
            var pid = $this.val();
            var configId = $(this).data('project');
            var $otherPidDiv = $(".otherPidDiv[data-project=" + configId + "]");
            var $projectCheckboxes = $('.project-checkboxes-' + configId + ' > ul > li');

            if (pid === 'other') {
                var otherPid = $otherPidDiv.find('input').val();

                $otherPidDiv.show();

                if (otherPid !== '') {
                    pid = otherPid;
                }
                else {
                    return;
                }
            }
            else {
                $otherPidDiv.hide();
            }

            if (pid === '') {
                $projectCheckboxes.empty('.project-checkboxes-' + configId);
                $('#p' + configId + 'Title').text('');
                $('#joinOptionsTab').addClass('disabled');

                return;
            }

            $this.prop('disabled', true);
            $projectCheckboxes.empty('.project-checkboxes-' + configId);
            $('.join-field-list[data-project=' + configId + ']').hide();
            $('.loading-icon[data-project=' + configId + ']').show();

            $.ajax({
                method: 'POST',
                data: {'pid': pid},
                url: UIOWA_AdminDash.requestHandlerUrl + '&type=getProjectFields',
                success: function(data) {
                    data = JSON.parse(data);

                    if (data['fieldLookup'].length === 0) {
                        $('.loading-icon[data-project=' + configId + ']').hide();
                        alert('Failed to load fields. PID may be invalid.');
                    }

                    loadJoinFields(data);
                }
            });

            function loadJoinFields(data) {
                $.each(data['fieldLookup'], function (form, fields) {
                    $projectCheckboxes.append($('<input>', {
                        type: 'checkbox',
                        class: 'option',
                        name: form + '_p' + configId,
                        id: form + '_p' + configId
                    }));

                    $projectCheckboxes.append($('<label>', {
                        html: '&nbsp;' + form,
                        for: form + '_p' + configId
                    }));

                    $projectCheckboxes.append();
                    var $subCheckboxes = $('<ul>');

                    $.each(fields, function(index, field_name) {
                        var $li = $('<li>');

                        $li.append($('<input>', {
                            type: 'radio',
                            name: 'join_project_' + configId,
                            value: field_name
                        }));

                        $li.append('<span> </span>');

                        $li.append($('<input>', {
                            type: 'checkbox',
                            id: field_name + '_p' + configId,
                            name: field_name,
                            class: 'subOption',
                            'data-form': form + '_p' + configId,
                            'data-project': configId
                        }));

                        $li.append($('<label>', {
                            html: '&nbsp;' + field_name,
                            for: field_name + '_p' + configId
                        }));

                        $subCheckboxes.append($li);
                    });

                    $projectCheckboxes.append($subCheckboxes);

                    var checkboxes = document.querySelectorAll("input.subOption[data-form='" + form + '_p' + configId + "']"),
                        checkall = document.querySelectorAll("input[name='" + form + '_p' + configId + "']")[0];

                    for(var i=0; i<checkboxes.length; i++) {
                        checkboxes[i].onclick = function() {
                            var checkedCount = document.querySelectorAll("input.subOption:checked[data-form='" + form + '_p' + configId + "']").length;

                            checkall.checked = checkedCount > 0;
                            checkall.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
                        }
                    }

                    checkall.onclick = function() {
                        for(var i=0; i<checkboxes.length; i++) {
                            checkboxes[i].checked = this.checked;
                        }
                    };

                    var reportInfo = UIOWA_AdminDash.reportReference[UIOWA_AdminDash.currentReportConfigIndex];

                    if (reportInfo) {
                        $.each(reportInfo['p' + configId + 'Fields'], function(index, field_name) {
                            $("#" + field_name + '_' + 'p' + configId).prop('checked', true).trigger('onclick');
                        });

                        $("input[name='join_project_" + configId + "'][value='" + reportInfo['p' + configId + 'JoinField'] + "']").prop('checked', true);
                        $("input[name='allowProjectUsersP" + configId + "'][value='" + reportInfo["allowProjectUsersP" + configId] + "']").prop('checked', true);

                        $('#showChoiceLabels').prop('checked', reportInfo['showChoiceLabels']);
                        $('#matchesOnly').prop('checked', reportInfo['matchesOnly']);

                    }
                    else {
                        $('.project-checkboxes-' + configId + ' input[type=radio]').first().prop('checked', true);
                    }

                    $('.p' + configId + 'Title').text(pid + ' - ' + data['projectTitle']);

                    if ($(".pid-select[data-project=1]").val() !== '' && $(".pid-select[data-project=2]").val() !== '') {
                        $('#joinOptionsTab').removeClass('disabled');
                    }
                    $this.prop('disabled', false);
                    $('.loading-icon[data-project=' + configId + ']').hide();
                    $('.join-field-list[data-project=' + configId + ']').show();
                });
            }
        });

        $[ "ui" ][ "autocomplete" ].prototype["_renderItem"] = function( ul, item) {
            return $( "<li></li>" )
                .data( "item.autocomplete", item )
                .append( $( "<a></a>" ).html( item.label ) )
                .appendTo( ul );
        };

        // Enable tooltips
        $('[data-toggle="tooltip"]').tooltip();

        //if (sessionStorage.getItem("selectedUser") && UIOWA_AdminDash.superuser) {
        //    $('.executiveUser').val( sessionStorage.getItem("selectedUser") );
        //    UIOWA_AdminDash.userID = $('.executiveUser')[0].value;
        //}

        var reportTable = $('.report-visibility-table');
        var addButtonRow = $('.add-report-button').closest('tr');

        for (var i in UIOWA_AdminDash.reportReference) {
            var reportName = UIOWA_AdminDash.reportReference[i]['reportName'];
            var readOnly = UIOWA_AdminDash.reportReference[i]['readOnly'];
            var reportRow = UIOWA_AdminDash.createReportRow(UIOWA_AdminDash.reportReference[i]).insertBefore(addButtonRow);

            if (readOnly) {
                $('.custom-report-only', reportRow).hide();
            }
        }

        $('.executiveUser').change(function() {
            $('.executiveUser').not(this).val( this.value );
            //sessionStorage.setItem("selectedUser", this.value);
            UIOWA_AdminDash.updateSettingsModal(this.value);
        });

        reportTable.on('click', '.open-report-setup', function() {
            var reportTr = $(this).closest('tr');
            UIOWA_AdminDash.newReport = $('.add-report-button', reportTr).length !== 0;

            UIOWA_AdminDash.currentReportConfigIndex = reportTr.index();
            UIOWA_AdminDash.updateReportSetupModal();
        });

        $('.save-report-setup').click(function() {

            var form = $('#reportConfiguration');
            var reportName = $('#reportName');
            var reportCustomId = $('#reportCustomId');
            var existingReports = Object.keys(UIOWA_AdminDash.adminVisibility);

            form.validate({
                errorPlacement: function(error, element) {
                    if (element.hasClass('custom-error-report-id')) {
                        $('.custom-report-id-group').after(error);
                    }
                    else if (element.hasClass('custom-error')) {
                        $('#idHelpBlock').before(error);
                    }
                    else {
                        element.after(error); // default error placement
                    }
                }
            });

            reportName.rules("add", {
                notInArray: [existingReports, 'report'],
                messages: {
                    notInArray: 'Report title must be unique.'
                }
            });

            reportCustomId.rules("add", {
                notInArray: [UIOWA_AdminDash.reportIDs, 'report'],
                lettersonly: 'opt',
                messages: {
                    notInArray: 'Custom ID must be unique.',
                    lettersonly: 'Alphabetical characters only.'
                }

            });

            if (!form.valid()) {
                return;
            }

            UIOWA_AdminDash.lastSavedReportUrl = UIOWA_AdminDash.reportUrlTemplate +
                ($('#reportCustomId').val() != '' ?
                '&report=' + $('#reportCustomId').val() : '&id=' + UIOWA_AdminDash.currentReportConfigIndex);

            if (UIOWA_AdminDash.newReport) {
                var addButtonRow = $('.add-report-button').closest('tr');
                var navBar = $('.report-tabs');
                var reportRow = $(UIOWA_AdminDash.createReportRow('Untitled')).insertBefore(addButtonRow);

                $(reportRow).find('.table-report-title').attr('href', UIOWA_AdminDash.lastSavedReportUrl);
                $('input', reportRow).bootstrapToggle();

                if (!$('#executiveUser').val()) {
                    $('.table-executive-visible input', reportRow).prop('disabled', true);
                    $('.table-executive-visible .toggle-off', reportRow).addClass('disabled');
                }

                $(
                    '<li class="nav-item" style="display:none">' +
                    '<a class="nav-link" href="' + UIOWA_AdminDash.lastSavedReportUrl + '">' +
                    '<span class="report-icon fas fa-' + $('#reportIcon').val() + '">' +
                    '</span>&nbsp; <span class="report-title">' + $('#reportName').val() + '</span>' +
                    '</a>'
                ).appendTo(navBar);
            }

            if ($(this).text() == 'Save & View Report') {
                UIOWA_AdminDash.loadReportAfterSave = true;
            }

            UIOWA_AdminDash.saveReportConfiguration();

            $('#reportSetupModal').modal('toggle');
        });

        $('#new-executive-username').keypress(function(event){
            var keycode = (event.keyCode ? event.keyCode : event.which);
            if(keycode == '13' && $(this).val() !== ''){
                $(this).parent().find('button').click();
            }
        });

        $('#new-executive-username').on('keyup', function () {
            if ($(this).val() !== '') {
                $('#add-executive-user-button').prop('disabled', false);
            }
            else {
                $('#add-executive-user-button').prop('disabled', true);
            }
        });

        $('.executive-table').on('click', '.email-executive-user', function () {
            var username = $.trim($(this).closest('tr').find('.executive-user').text());

            $.ajax({
                method: 'POST',
                url: UIOWA_AdminDash.requestHandlerUrl + '&type=sqlQuery',
                data: 'select user_email from redcap_user_information where username = "' + username + '" limit 1'
            })
            .done(function (data) {
                data = JSON.parse(data);
                var userEmail = '';

                if (data.length > 0) {
                    userEmail = data[0]['user_email'];
                }

                window.location = 'mailto:' + userEmail +
                    '?subject=Executive Dashboard Access' +
                    '&body=Hello,%0D%0A%0D%0AYou have been granted access to the REDCap Executive Dashboard: ' + encodeURIComponent(UIOWA_AdminDash.executiveUrl);
            });
        });

        $('.executive-table').on('click', '.remove-executive-user', function () {
            UIOWA_AdminDash.executiveRowToRemove = $(this).closest('tr');
            $('.display-executive-username').text(UIOWA_AdminDash.executiveRowToRemove.find('.executive-user').text());
            $('#executiveUserRemove').modal('show');
        });

        $('.confirm-remove-user').click(function () {
            var removeUserRow = UIOWA_AdminDash.executiveRowToRemove;
            var removeUser = $.trim(removeUserRow.find('.executive-user').text());
            var executiveUserSelect = $('#executiveUser');
            var noExecutiveUsers = $('#noExecutiveUsers');

            if (executiveUserSelect.val() == removeUser) {
                $('#executiveUser').val('');
            }

            $("#executiveUser option[value='" + removeUser + "']").remove();
            removeUserRow.remove();
            UIOWA_AdminDash.executiveUsers = $.grep(UIOWA_AdminDash.executiveUsers, function (username) {
                return username != removeUser;
            });
            UIOWA_AdminDash.executiveExportLookup = $.grep(UIOWA_AdminDash.executiveExportLookup, function (username) {
                return username != removeUser;
            });

            UIOWA_AdminDash.saveConfigSettingToDb('executive-users', UIOWA_AdminDash.executiveUsers);
            UIOWA_AdminDash.saveConfigSettingToDb('executive-user-export', UIOWA_AdminDash.executiveExportLookup);

            if ($('.executive-table').find('tr').length <= 2) {
                noExecutiveUsers.show();
            }

            $('.executiveUser').change();
        });

        $('.edit-executive-visibility').click(function () {
            $('#executiveUser').change();
            $('#collapseOne').collapse('show');
        });

        $('.executive-link').attr('href', UIOWA_AdminDash.executiveUrl);

        $('#reportSetupModal').on('shown.bs.modal', function() {
            var reportReference = UIOWA_AdminDash.reportReference[UIOWA_AdminDash.currentReportConfigIndex];

            if (!UIOWA_AdminDash.newReport && reportReference['checked']) {
                var savedFormattingConfig = reportReference['formatting'];

                if (savedFormattingConfig) {
                    UIOWA_AdminDash.loadReportFormatting('existing', savedFormattingConfig);
                }
            }
        });

        $('#reportSetupModal').on('hidden.bs.modal', function() {
            var $alertas = $('#reportConfiguration');
            $alertas.validate().resetForm();
            $alertas.find('.error').removeClass('error');

            $('#reportDisplayType').val('sql').trigger('change');
            $('#testQueryResult').html('');
            $('#formattingTab').addClass('disabled').tooltip('enable');
            $('#chartConfigTab').addClass('disabled').tooltip('enable');
            $('.test-query').html('Test Query').prop('disabled', false).removeClass('btn-danger btn-success').addClass('btn-info');

            $('.pid-select').val('').trigger('change');
            $('.pid-other').val('');
            $('.join-field-list').hide();
            $('#showChoiceLabels').prop('checked', false);
            $("input[name='allowProjectUsersP1'][value='0']").prop('checked', true);
            $("input[name='allowProjectUsersP2'][value='0']").prop('checked', true);
            $('#matchesOnly').prop('checked', false);
        });

        $('#reportIcon').on('input', function() {
            var input = $('#reportIcon');
            var icon = $('#reportIconPreview');

            icon.removeClass();
            icon.addClass('fas fa-' + input.val());
        });

        // initialize ace editor
        editor = ace.edit("reportQuery", {
            theme: "ace/theme/monokai",
            mode: "ace/mode/sql",
            minLines: 10
        });

        // enable bootstrap toggles for config options
        $('.module-config').each(function() {
            $(this).bootstrapToggle($(this).val() == '1' ? 'on' : 'off');
        });

        $('#viewReadme').click(function() {
            window.open(UIOWA_AdminDash.readmeUrl, '_blank');
        });

        $('#showChangelog').click(function() {
            $('#changelogModal').modal('show');
        });

        // download module settings dump
        $('#diagnostic').click(function() {
            window.location.href = UIOWA_AdminDash.requestHandlerUrl + '&type=exportDiagnosticFile';
        });

        var username = null;

        if (UIOWA_AdminDash.executiveAccess) {
            username = $('#primaryUserSelect')[0].value;

            if ($('#primaryUserSelect')[0].value) {
                $('#executiveUser')[0].value = $('#primaryUserSelect')[0].value;
            }
        }
        else {
            username = $('#executiveUser')[0].value;
        }

        UIOWA_AdminDash.updateSettingsModal(username);

        $('.confirmDelete').on('click', function() {
            UIOWA_AdminDash.reportReference = $.grep(UIOWA_AdminDash.reportReference, function (report) {
                return report['reportName'] != UIOWA_AdminDash.reportNameToDelete;
            });

            UIOWA_AdminDash.reportIDs.splice(UIOWA_AdminDash.reportRowToDelete.index());
            delete UIOWA_AdminDash.adminVisibility[UIOWA_AdminDash.reportNameToDelete];
            delete UIOWA_AdminDash.executiveVisibility[UIOWA_AdminDash.reportNameToDelete];

            var reportNav = $('.report-title').filter(function () {
                return $(this).html() == UIOWA_AdminDash.reportNameToDelete;
            }).closest('li').first();

            UIOWA_AdminDash.reportRowToDelete.remove();
            reportNav.remove();
            UIOWA_AdminDash.saveReportSettingsToDb('all');
        });

        $('.report-visibility-table').on('change', '.report-visibility-toggle', function() {
            if ($(this).hasClass('save-enabled')) {
                UIOWA_AdminDash.adminVisibility = {};

                var selectedUser = $('#executiveUser')[0].value;

                $('.report-visibility-table tr').each(function () {
                    var reportTitle = $(this).find('.table-report-title').html();
                    var adminVisible = !$(this).find('.table-admin-visible div').hasClass('off');
                    var executiveVisible = !$(this).find('.table-executive-visible div').hasClass('off');
                    var prevVisible = $.inArray(selectedUser, UIOWA_AdminDash.executiveVisibility[reportTitle]) != -1;

                    if (reportTitle == undefined) {return;}

                    UIOWA_AdminDash.adminVisibility[reportTitle] = adminVisible;

                    if (!UIOWA_AdminDash.executiveVisibility[reportTitle]) {
                        UIOWA_AdminDash.executiveVisibility[reportTitle] = [];
                    }

                    if (executiveVisible) {
                        if (prevVisible) {return;}

                        UIOWA_AdminDash.executiveVisibility[reportTitle].push(selectedUser);
                    }
                    else {
                        UIOWA_AdminDash.executiveVisibility[reportTitle] = $.grep(UIOWA_AdminDash.executiveVisibility[reportTitle], function(e){
                            return e != selectedUser;
                        });
                    }
                });

                UIOWA_AdminDash.saveReportSettingsToDb('visibility');
                UIOWA_AdminDash.updateReportTabs(selectedUser, true);
            }
        });

        $('.config-settings-table').on('change', '.toggle', function() {
            var configKey = $(this).find('input').attr('name');
            var configEnabled = !$(this).hasClass('off');

            UIOWA_AdminDash.saveConfigSettingToDb(configKey, configEnabled)
        });

        $('.executive-table').on('change', '.toggle', function() {
            var username = $.trim($(this).find('input').data('username'));
            var exportIndex = UIOWA_AdminDash.executiveExportLookup.indexOf(username);

            if (exportIndex === -1) {
                UIOWA_AdminDash.executiveExportLookup.push(username);
            }
            else {
                UIOWA_AdminDash.executiveExportLookup.splice(exportIndex, 1);
            }

            UIOWA_AdminDash.saveConfigSettingToDb('executive-user-export', UIOWA_AdminDash.executiveExportLookup)
        });

        $('#reportDisplayType').change(function () {
            if ($(this).val() === 'projects') {
                $('#sqlTab').hide();
                $('#formattingTab').hide();
                $('#projectJoinTab').show();
                $('#joinOptionsTab').show();

                $('a[href="#projectJoin"]').tab('show');
            }
            else {
                $('#projectJoinTab').hide();
                $('#joinOptionsTab').hide();
                $('#sqlTab').show();
                $('#formattingTab').show();

                $('a[href="#sql"]').tab('show');
            }
        });

        $('.test-query').click(function() {
            var testQueryButton = $(this);

            testQueryButton.prop('disabled', true);
            testQueryButton.html('<i class="fas fa-spinner fa-spin test-progress"></i>');

            var startTime = performance.now();

            $.ajax({
                    method: 'POST',
                    url: UIOWA_AdminDash.requestHandlerUrl + '&type=sqlQuery',
                    data: editor.getValue(),
                    timeout: UIOWA_AdminDash.testQueryTimeout
                })
                .done(function(data) {
                    var endTime = performance.now();
                    data = JSON.parse(data);


                    if (data['error']) {
                        testQueryButton.html('<i class="fas fa-times"></i> Error').removeClass('btn-info').addClass('btn-danger');
                        $('#testQueryResult').html('<span style="color:red;">Query failed: ' + data['error'] + '</span>');
                    }
                    else {
                        testQueryButton.html('<i class="fas fa-check"></i> Success').removeClass('btn-info').addClass('btn-success');

                        var isFirstRow = true;
                        UIOWA_AdminDash.lastTestQuery['report'] = UIOWA_AdminDash.currentReportConfigIndex;
                        UIOWA_AdminDash.lastTestQuery['rowCount'] = 0;

                        $.each(data, function (index, row) {
                            if (isFirstRow) {
                                UIOWA_AdminDash.lastTestQuery['columns'] = Object.keys(row);
                                isFirstRow = false;
                            }

                            UIOWA_AdminDash.lastTestQuery['rowCount']++
                        });

                        // Enable Special Formatting tab if there are columns to work with
                        if (UIOWA_AdminDash.lastTestQuery['columns'].length > 0) {
                            UIOWA_AdminDash.loadReportFormatting('new', UIOWA_AdminDash.lastTestQuery['columns']);
                        }

                        $('#testQueryResult').html('<span style="color:green;">Query returned ' + UIOWA_AdminDash.lastTestQuery['rowCount'] + ' row(s) in ' + Math.floor(endTime - startTime) + 'ms</span>');
                    }
                })
                .fail(function() {
                    testQueryButton.html('<i class="fas fa-times"></i> Error').removeClass('btn-info').addClass('btn-danger');
                    $('#testQueryResult').html('<span style="color:red;">Query timed out! You may want to optimize your query before running again or increase/disable the timeout via this module\'s settings.</span>');
                })
        });

        editor.on('change', function() {
            UIOWA_AdminDash.lastTestQuery['checked'] = false;
            $('#formattingTab').addClass('disabled').tooltip('enable');
            $('#chartConfigTab').addClass('disabled').tooltip('enable');
            $('.test-query').html('Test Query').prop('disabled', false).removeClass('btn-danger btn-success').addClass('btn-info');
        });
    });

}(window.jQuery, window, document));

UIOWA_AdminDash.updateSettingsModal = function(selectedUser) {
    // disable save trigger while updating
    var allToggles = $('.report-visibility-table input').removeClass('save-enabled');

    $('.report-visibility-table tr').each(function () {
        var reportTitle = $(this).find('.table-report-title').html();

        if (reportTitle == undefined) {return;}

        var adminVisible = UIOWA_AdminDash.adminVisibility[reportTitle];
        var executiveVisible = $.inArray(selectedUser, UIOWA_AdminDash.executiveVisibility[reportTitle]) != -1;
        var adminToggle = $(this).find('.table-admin-visible input');
        var executiveToggle = $(this).find('.table-executive-visible input');
        executiveToggle.bootstrapToggle();
        var executiveToggleGroup = $(this).find('.table-executive-visible .toggle-off');

        if (selectedUser == '' || selectedUser == null) {
            executiveToggle.bootstrapToggle('off');

            executiveToggle.prop('disabled', true);
            executiveToggleGroup.addClass('disabled');
        }
        else {
            executiveToggle.prop('disabled', false);
            executiveToggleGroup.removeClass('disabled');

            executiveVisible ? executiveToggle.bootstrapToggle('on') : executiveToggle.bootstrapToggle('off');
        }

        adminVisible ? adminToggle.bootstrapToggle('on') : adminToggle.bootstrapToggle('off');
    });

    allToggles.addClass('save-enabled');

    UIOWA_AdminDash.updateReportTabs(selectedUser);
};

UIOWA_AdminDash.updateReportSetupModal = function() {
    var index = UIOWA_AdminDash.currentReportConfigIndex;
    var reportInfo = UIOWA_AdminDash.reportReference[index];
    var nameInput = $('#reportName');
    var descInput = $('#reportDescription');
    var iconInput = $('#reportIcon');
    var idInput = $('#reportId');
    var customIdInput = $('#reportCustomId');
    var reportTypeSelect = $('#reportDisplayType');

    //$('#reportConfiguration input').not('#reportCustomId').attr('readonly', false);
    $('#reportConfiguration input').attr('readonly', false);
    $('#reportConfiguration select').attr('disabled', false);
    editor.setReadOnly(false);
    $('.save-report').show();

    if (UIOWA_AdminDash.newReport) {
        nameInput.val('');
        descInput.val('');
        iconInput.val('question');
        iconInput.trigger('input');
        idInput.html(index);
        customIdInput.val('');
        editor.setValue('');
    }
    else {
        nameInput.val(reportInfo['reportName']);
        descInput.val(reportInfo['description']);
        iconInput.val(reportInfo['tabIcon']);
        iconInput.trigger('input');
        idInput.html(index);
        customIdInput.val(UIOWA_AdminDash.reportIDs[index]);
        editor.setValue(reportInfo['sql'] ? reportInfo['sql'] : '');

        if (reportInfo['readOnly']) {
            //$('#reportConfiguration input').not('#reportCustomId').attr('readonly', true);
            $('#reportConfiguration input').attr('readonly', true);
            $('#reportConfiguration select').attr('disabled', true);
            editor.setReadOnly(true);
            $('.save-report').hide();

            //$('#formattingTab').removeClass('disabled').tooltip('disable').attr('data-toggle', 'tab');

        }

        if (reportInfo['type'] === 'projects') {
            reportTypeSelect.val('projects').trigger('change');

            $.each([1, 2], function (index, num) {
                var pidNum = 'pid' + num;

                if ($(".pid-select[data-project=" + num + "] option[value='" + reportInfo[pidNum] + "']").length > 0) {
                    $(".pid-select[data-project=" + num + "]")
                        .val(reportInfo[pidNum])
                        .trigger('change');
                }
                else {
                    $("input[name='otherPid" + num + "']")
                        .val(reportInfo[pidNum]);

                    $(".pid-select[data-project=" + num + "]")
                        .val('other')
                        .trigger('change');
                }
            });
        }
    }

    editor.clearSelection();
    editor.getSession().getUndoManager().reset();
};

UIOWA_AdminDash.saveReportConfiguration = function() {
    var index = UIOWA_AdminDash.currentReportConfigIndex;
    var newReportTitle = $('#reportName').val();
    var reportRow = $('.table-report-title')[index];
    var reportNavTitle = $('.report-title')[index];
    var reportNavIcon = $('.report-icon')[index];
    var oldReportTitle = $(reportRow).html();

    $(reportRow).html(newReportTitle);
    $(reportNavTitle).html(newReportTitle);

    $(reportNavIcon).removeClass (function (index, className) {
        return (className.match (/(^|\s)fa-\S+/g) || []).join(' ');
    });
    $(reportNavIcon).addClass('fa-' + $('#reportIcon').val());

    UIOWA_AdminDash.reportReference[index] = {
        'reportName': newReportTitle,
        'description': $('#reportDescription').val(),
        'tabIcon': $('#reportIcon').val(),
        'customID': $('#reportCustomId').val()
    };

    var additionalConfig = {};

    if ($('#reportDisplayType').val() === 'sql') {
        additionalConfig = {
            'sql': editor.getValue(),
            'type': 'table',
            'checked': UIOWA_AdminDash.lastTestQuery['report'] === index ? UIOWA_AdminDash.lastTestQuery['checked'] : false
        };
    }
    else {
        var p1Fields = [];
        var p2Fields = [];

        $(".subOption[data-project=1]:checked").each(function (index, checkbox) {
            p1Fields.push($(checkbox).attr('name'));
        });
        $(".subOption[data-project=2]:checked").each(function (index, checkbox) {
            p2Fields.push($(checkbox).attr('name'));
        });

        additionalConfig = {
            'pid1': $("[name='pid1']").val() === 'other' ? $("[name='otherPid1']").val() : $("[name='pid1']").val(),
            'pid2': $("[name='pid2']").val() === 'other' ? $("[name='otherPid2']").val() : $("[name='pid2']").val(),
            'type': 'projects',
            'p1Fields': p1Fields,
            'p2Fields': p2Fields,
            'p1JoinField': $("input[name='join_project_1']:checked").val(),
            'p2JoinField': $("input[name='join_project_2']:checked").val(),
            'showChoiceLabels': $('#showChoiceLabels').prop('checked'),
            'allowProjectUsersP1': $("input[name='allowProjectUsersP1']:checked").val(),
            'allowProjectUsersP2': $("input[name='allowProjectUsersP2']:checked").val(),
            'matchesOnly': $("#matchesOnly").prop('checked')
        };
    }

    $.extend(UIOWA_AdminDash.reportReference[index], additionalConfig);

    // get special formatting
    var formattingColumns = $('.column-list > tr');
    var specialFormatting = {};

    if (formattingColumns.length > 0) {
        formattingColumns.each(function(index, row) {
            var colName = $(row).find('.column-name').text();
            var displaySelect = Number($(row).find('.display-select').val());
            var linkSelect = $(row).find('.link-type-select').val();
            var linkGroup = $(row).find('.link-type-select option:selected').parent('optgroup').attr('label');
            var customInput = $(row).find('.custom-link').val();

            specialFormatting[index] = {
                column: colName,
                display: displaySelect,
                link: linkSelect
            };

            if (linkGroup) {
                specialFormatting[index]['linkGroup'] = linkGroup;
            }
            if (customInput) {
                specialFormatting[index]['custom'] = customInput;
            }
        });

        UIOWA_AdminDash.reportReference[index]['formatting'] = specialFormatting;
    }

    UIOWA_AdminDash.reportIDs[index] = $('#reportCustomId').val();

    if (!UIOWA_AdminDash.newReport) {
        UIOWA_AdminDash.adminVisibility = UIOWA_AdminDash.updateVisibilityReportTitle(
            UIOWA_AdminDash.adminVisibility,
            oldReportTitle,
            newReportTitle
        );
        UIOWA_AdminDash.executiveVisibility = UIOWA_AdminDash.updateVisibilityReportTitle(
            UIOWA_AdminDash.executiveVisibility,
            oldReportTitle,
            newReportTitle
        );
    }
    else {
        UIOWA_AdminDash.adminVisibility[newReportTitle] = false;
        UIOWA_AdminDash.executiveVisibility[newReportTitle] = [];
    }

    UIOWA_AdminDash.saveReportSettingsToDb('all');
};

UIOWA_AdminDash.updateVisibilityReportTitle = function(array, oldTitle, newTitle) {
    if (oldTitle !== newTitle) {
        Object.defineProperty(array, newTitle,
            Object.getOwnPropertyDescriptor(array, oldTitle));
        delete array[oldTitle];
    }

    return array;
};

UIOWA_AdminDash.createReportRow = function(reportInfo) {
    return $(
        '<tr>' +
        '<td style="text-align:right; vertical-align:middle; padding-right:10px; word-wrap:break-word; max-width:350px">'+
        '<a href="' + reportInfo['url'] + '" class="table-report-title">' + reportInfo['reportName'] + '</a>' +
        '</td>' +
        '<td style="text-align:left; vertical-align:middle;">' +
        '<button type="button" class="btn btm-sm btn-primary open-report-setup report-settings-button" aria-haspopup="true" aria-expanded="false" data-toggle="modal" data-target="#reportSetupModal">' +
        '<i class="fas fa-edit"></i>' +
        '<span class="sr-only">Edit report</span>' +
        '</button>' +
            //'<button type="button" class="btn btm-sm btn-success report-settings-button" aria-haspopup="true" aria-expanded="false" data-target="#" onclick="UIOWA_AdminDash.copyReport(this)">' +
            //    '<i class="fas fa-copy"></i>' +
            //    '<span class="sr-only">Copy report</span>' +
            //'</button>' +
        '<button type="button" class="btn btm-sm btn-danger report-settings-button custom-report-only" aria-haspopup="true" aria-expanded="false" data-target="#" onclick="UIOWA_AdminDash.deleteReport(this)">' +
        '<i class="fas fa-trash"></i>' +
        '<span class="sr-only">Delete report</span>' +
        '</button>' +
        '</td>' +
        '<td class="table-admin-visible" style="text-align:center">' +
        '<input class="report-visibility-toggle save-enabled" type="checkbox" data-toggle="toggle" data-width="75" data-on="Show" data-off="Hide">' +
        '</td>' +
        '<td class="table-executive-visible" style="text-align:center">' +
        '<input class="report-visibility-toggle save-enabled" type="checkbox" data-toggle="toggle" data-width="75" data-on="Show" data-off="Hide">' +
        '</td>' +
        '</tr>'
    );
};

UIOWA_AdminDash.addExecutiveUser = function () {
    var form = $('#newExecutiveUserForm');
    var usernameInput = $('#new-executive-username');

    form.validate({
        errorPlacement: function(error, element) {
            element.parent().after(error);
        }
    });

    usernameInput.rules("add", {
        username: usernameInput.val()
    });

    usernameInput.rules("add", {
        notInArray: [UIOWA_AdminDash.executiveUsers, 'users']
    });

    if (!form.valid()) {
        return;
    }
    else {
        form.validate().resetForm();
        form.parent().find('.error').removeClass('error');
    }

    $('#noExecutiveUsers').hide();

    var addButtonRow = $('#add-executive-user-button').closest('tr');
    var executiveUserSelect = $('#executiveUser');

    var userRow = $(
        '<tr>'+
        '    <td align="center">'+
        '        <button'+
        '                type="button"'+
        '                class="btn btm-sm btn-danger remove-executive-user"'+
        '                aria-haspopup="true"'+
        '                aria-expanded="false"'+
        '                data-target="#"'+
        '                onclick=""'+
        '        >'+
        '            <i class="fas fa-trash"></i>'+
        '            <span class="sr-only">Remove Executive User</span>'+
        '        </button>'+
        '    </td>'+
        '    <td class="executive-user" style="text-align:center; vertical-align:middle; padding-right:10px; word-wrap:break-word; max-width:200px">' +
        usernameInput.val() +
        '    </td>'+
        '    <td align="center">'+
        '        <input'+
        '                type="checkbox"'+
        '                data-toggle="toggle"'+
        '                data-width="160"'+
        '                data-height="40"'+
        '                data-on="Export Enabled"'+
        '                data-off="Export Disabled"'+
        '                data-username="' + usernameInput.val() + '"'+
        '                class="module-config"'+
        '                value="0"'+
        '        >'+
        '    </td>'+
        '    <td>'+
        '        <button'+
        '                type="button"'+
        '                class="btn btm-sm btn-primary email-executive-user"'+
        '                aria-haspopup="true"'+
        '                aria-expanded="false"'+
        '                data-target="#"'+
        '                onclick=""'+
        '        >'+
        '            <i class="fas fa-envelope"></i>' +
        '               Send Link'+
        '            <span class="sr-only">Send Link</span>'+
        '        </button>'+
        '    </td>'+
        '</tr>'
    ).insertBefore(addButtonRow);

    $('input', userRow).bootstrapToggle();

    executiveUserSelect.append($('<option>', {
        value: usernameInput.val(),
        text: usernameInput.val()
    }));
    $('.display-executive-username').text(usernameInput.val());
    executiveUserSelect.val(usernameInput.val());
    UIOWA_AdminDash.executiveUsers.push(usernameInput.val());
    UIOWA_AdminDash.saveConfigSettingToDb('executive-users', UIOWA_AdminDash.executiveUsers);

    usernameInput.val('');
    $('#add-executive-user-button').prop('disabled', true);

    $('#executiveUserAdded').modal('show');
};

UIOWA_AdminDash.deleteReport = function(deleteLink) {
    UIOWA_AdminDash.reportRowToDelete = $(deleteLink).closest('tr');
    UIOWA_AdminDash.reportNameToDelete = $('.table-report-title', UIOWA_AdminDash.reportRowToDelete).text();

    $('#confirmDelete').modal('show');
    $('.confirmMsgReport').html('Are you sure you want to delete this report?<br/><br/><span style="color:red">' + UIOWA_AdminDash.reportNameToDelete + '<strong>');
};

//UIOWA_AdminDash.archiveReport = function(archiveLink) {
//    var reportRow = $(archiveLink).closest('tr');
//    var settingsButton = $('button', reportRow);
//    var settingsButtonIcon = $('button > i', reportRow);
//
//    settingsButton.removeClass('btn-primary');
//    settingsButton.addClass('btn-warning');
//
//    settingsButtonIcon.removeClass();
//    settingsButtonIcon.addClass('fas fa-eye-slash');
//
//    reportRow.hide();
//};
//
//UIOWA_AdminDash.toggleArchivedReports = function() {
//    UIOWA_AdminDash.showArchivedReports = !UIOWA_AdminDash.showArchivedReports;
//
//    $('.report-visibility-table tr').each(function () {
//        var reportName = $('td', this).html();
//
//        if (UIOWA_AdminDash.showArchivedReports == true) {
//            $(this).show();
//        }
//        else if (UIOWA_AdminDash.showArchivedReports == false) {
//            if ($.inArray(reportName, UIOWA_AdminDash.archivedReports) != -1) {
//                $(this).hide();
//            }
//        }
//    });
//};

UIOWA_AdminDash.loadReportFormatting = function (type, data) {
    // clear existing column list
    $('.column-list').empty('');

    // create list of column settings
    var columns = [];
    var linkPreview = $('<textarea class="form-control custom-link" style="display: none">');
    if (type == 'existing') {
        columns = $.map(data, function(item) {
            return item['column'];
        });
    }
    else {
        columns = data;
    }
    $.each(columns, function(index, value) {
        var row = $(
            "<tr>" +
            "<td class='column-name' style='vertical-align: middle; word-wrap:break-word'>" +
            value +
            "</td>" +
            "<td style='vertical-align: middle; text-align:center;' class='link-type-td'>" +
            "</td>" +
            "<td style='vertical-align: middle' class='display-td'>" +
            "</td>" +
            "</tr>");

        $('.column-list').append(row);
    });

    // create display options
    var displayReference = UIOWA_AdminDash.formattingReference['display'];
    var displaySelect = $('<select class="form-control display-select"></select>');
    $.each(displayReference, function(index, name) {
        var option = new Option(name, index);
        displaySelect.append(option);
    });
    $('.display-td').append(displaySelect);

    // create link options
    var linkReference = UIOWA_AdminDash.formattingReference['links'];
    var linkSelect = $('<select class="form-control link-type-select"><option value="not set">---Not Set---</option></select>');
    $.each(linkReference, function(groupName, options) {
        var optgroup = $('<optgroup label="' + groupName + '"></optgroup>');

        $.each(options, function(optionName) {
            var option = new Option(optionName, optionName);
            optgroup.append(option);
        });

        linkSelect.append(optgroup);
    });
    $('.link-type-td')
        .append(linkSelect)
        .append(linkPreview);


    $('.link-type-select').on('change', function() {
        var linkTypeSelect = $(this);
        var customLink = $(this).parent().find('.custom-link');
        var selectedLinkType = $('option:selected', linkTypeSelect).text();

        if (selectedLinkType == 'Custom') {
            customLink.val(linkReference['Other Links']['Custom']);
            customLink.show();
        }
        else {
            customLink.hide();
        }
    });

    if (!UIOWA_AdminDash.newReport) {
        var columnFormatting = UIOWA_AdminDash.reportReference[UIOWA_AdminDash.currentReportConfigIndex]['formatting'];
        var columnRows = $('.column-list > tr');

        // load existing values
        if (columnRows.length > 0 && columnFormatting) {
            columnRows.each(function(index) {
                var formattingValues = columnFormatting[index];

                if (formattingValues) {
                    $(this).find('.display-select').val(formattingValues['display']);
                    $(this).find('.link-type-select').val(formattingValues['link']);
                    $(this).find('.custom-link').val(formattingValues['custom']);

                    if ($(this).find('.link-type-select option:selected').text() == 'Custom') {
                        $(this).find('.custom-link').show();
                    }
                }
            });
        }

        UIOWA_AdminDash.reportReference[UIOWA_AdminDash.currentReportConfigIndex]['checked'] = true;

        if (UIOWA_AdminDash.reportReference[UIOWA_AdminDash.currentReportConfigIndex]['readOnly']) {
            $('#formattingConfig').find('.form-control').prop('disabled', true);
        }
    }

    UIOWA_AdminDash.lastTestQuery['checked'] = true;

    $('#formattingTab').removeClass('disabled').tooltip('disable').attr('data-toggle', 'tab');
    $('#chartConfigTab').removeClass('disabled').tooltip('disable').attr('data-toggle', 'tab');
};

UIOWA_AdminDash.saveReportSettingsToDb = function(type) {
    var customReports = $.grep(UIOWA_AdminDash.reportReference, function(report) {
        if ($.inArray(report['reportName'], UIOWA_AdminDash.defaultReportNames) == -1) {
            return report;
        }
    });

    if (customReports.length == 0) {
        customReports = 'none';
    }

    var allSettings = JSON.stringify({
        'reportReference': type == 'all' || type == 'reports' ? customReports : null,
        'adminVisibility': type == 'all' || type == 'visibility' ? UIOWA_AdminDash.adminVisibility : null,
        'executiveVisibility': type == 'all' || type == 'visibility' ? UIOWA_AdminDash.executiveVisibility : null
    });

    $.ajax({
            method: 'POST',
            url: UIOWA_AdminDash.requestHandlerUrl + '&type=saveReportSettings',
            data: allSettings
        })
        .done(function() {
            if (UIOWA_AdminDash.loadReportAfterSave) {
                window.location.href = UIOWA_AdminDash.lastSavedReportUrl;
            }
        })
};

UIOWA_AdminDash.saveConfigSettingToDb = function(key, value) {
    $.ajax({
        method: 'POST',
        url: UIOWA_AdminDash.requestHandlerUrl + '&type=saveConfigSetting',
        data: JSON.stringify({
            key: key,
            value: value
        })
    })
};