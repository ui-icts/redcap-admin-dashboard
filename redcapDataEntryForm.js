$(document).ready(function() {
    UIOWA_AdminDash.fieldCustomizations = {
        report_icon: function() {
            let $input = $('input[name="report_icon"]');

            $('#report_icon-tr').find('.data').prepend(`
                <span class="fas fa-2x icon-preview" style="margin: 10px;">
                    <span class='fa-${$input.val()}'></span>
                </span>
            `)
                .find('input').css('width', 'auto');

            $input.on('input', function () {
                $('.icon-preview span')
                    .removeClass()
                    .addClass('fa-' + $(this).val());
            });
        },
        report_sql: function() {
            let $fieldTr = $('#report_sql-tr');

            $(`
            <tr class="ace-editor-td" style="height:400px">
                <td colspan="2">
                    <pre id="report_sql-editor"></pre>
                    <div id="testQueryResult" style="float:left; padding-left: 10px; padding-top: 10px; max-width:80%;"></div>
                    <div style="text-align:right; float:right; padding-bottom: 10px; padding-right: 10px">
                        <button type="button" class="btn btn-info test-query">Test Query</button>
                    </div>
                </td>
            </tr>
        `).insertAfter($fieldTr);

            let $resultDiv = $('#testQueryResult');

            $fieldTr.find('#report_sql-expand, .data').hide();
            $fieldTr.find('.labelrc').attr('colspan', 2)

            $('#report_sql-editor').attr('colspan', 2).find('pre');

            ace.require("ace/ext/language_tools");

            // initialize ace editor
            editor = ace.edit("report_sql-editor", {
                theme: "ace/theme/monokai",
                mode: "ace/mode/sql",
                minLines: 10,
                enableBasicAutocompletion: true,
                enableLiveAutocompletion: true
            });

            editor.setValue($fieldTr.find('.data textarea').val());

            editor.session.on('change', function() {
                $("#report_sql").val(editor.getValue()).change();
                $('.test-query').html('Test Query').prop('disabled', false).removeClass('btn-danger btn-success').addClass('btn-info');
            });

            $('.ace_editor').css('height', '400px');

            $('.test-query').click(function() {
                let $this = $(this);

                $this.prop('disabled', true);
                $this.html('<i class="fas fa-spinner fa-spin test-progress"></i>');

                let startTime = performance.now();

                $.ajax({
                    method: 'POST',
                    url: UIOWA_AdminDash.urlLookup.post,
                    dataType: 'json',
                    data: {
                        adMethod: 'runReport',
                        sql: editor.getValue(),
                        test: true
                    },
                    timeout: UIOWA_AdminDash.queryTimeout,
                    success: function(data) {
                        let endTime = performance.now();

                        console.log(data);

                        $this.html('<i class="fas fa-check"></i> Success').removeClass('btn-info').addClass('btn-success');

                        $('[name="test_query_error"]').val('');
                        $('[name="test_query_columns"]').val(data.columns.length);
                        $('[name="test_query_column_list"]').val(JSON.stringify(data.columns, null, 2));
                        $('[name="test_query_success___radio"][value="1"]')
                            .prop('disabled', '')
                            .prop("checked", true)
                            .click()
                            .prop('disabled', 'disabled');

                        $resultDiv.html('<span style="color:green;">Query returned ' + data.row_count + ' row(s) in ' + Math.floor(endTime - startTime) + 'ms</span>');
                    },
                    error: function(err) {
                        let errorMsg = err.responseText;

                        errorMsg = errorMsg.substring(
                            errorMsg.lastIndexOf("The error from the database was:"),
                            errorMsg.lastIndexOf("See the server error log for more details")
                        );

                        $('[name="test_query_column_list"], [name="test_query_columns"]').val('');
                        $('[name="test_query_success___radio"][value="0"]')
                            .prop('disabled', '')
                            .click()
                            .prop('disabled', 'disabled');

                        $this.html('<i class="fas fa-times"></i> Error').removeClass('btn-info').addClass('btn-danger');
                        $resultDiv.html('<span style="color:red;">Query failed! ' + errorMsg + '</span>');
                    }
                })
            });
        },
        link_type: function() {
            $('#link_type-tr option').map(function() {
                let $option = $(this);

                if ($option.val() >= 100) {
                    $option.prop('disabled', true)
                }
                else if (!$option.val()) {
                    $option.html('N/A');
                }

                return $option;
            })
        },
        link_source_column: function() {
            let $selfOption = $("#link_source_column-tr option").filter(function() {
                return $(this).text() === $('#column_name-tr input').val();
            });
            let selfText = $selfOption.text() + ' [self]';

            $selfOption.html(selfText);
        },
        code_type: function() {
            $('#code_type-tr option').map(function() {
                let $option = $(this);

                if (!$option.val()) {
                    $option.html('N/A');
                }

                return $option;
            })
        },
        hint_icons: function() {
            $('#hint_icons-tr .icon-placeholder').each(function(index) {
                let content = $(this).html();
                let iconLookup = [
                    `<span class="user-detail" title="User suspended" data-toggle="tooltip" data-placement="left"><i class="fas fa-ban fa-fw" style="color: red;"></i></span>`,
                    `<span class="user-detail" title="User does not exist" data-toggle="tooltip" data-placement="left"><i class="fas fa-times fa-fw" style="color: red;"></i></span>`,
                    `<span class="user-detail" title="Production" data-toggle="tooltip" data-placement="left"><i class="fas fa-check-square fa-fw" style="color: green;"></i></span>`
                ]

                let $iconSpan = $(iconLookup[index]).replaceAll($(this));

                $iconSpan
                    .append(' ' + content)
                    .find('.fas').css('display', 'inherit')
            })
        },
        api_url: function() {
            let apiUrl = UIOWA_AdminDash.urlLookup.post + '&NOAUTH=&id=' + UIOWA_AdminDash.reportId;

            $('#api_url-tr')
                .find('.url-placeholder')
                .html(apiUrl.replace('_internal', ''));

            $('.api-pre')
                .css('white-space', 'pre-wrap')
                .css('width', '80%');
        },
        tab_color: function() { // also modifies 'tab_color_custom'
            let $tabColorSelect = $('#tab_color-tr select');
            let $hexColorInput = $('#tab_color_custom-tr input');

            $tabColorSelect.change(function () {
                if (this.value === '99') {
                    $hexColorInput.attr('type', 'color');
                }
                else {
                    $hexColorInput
                        .attr('type', 'text')
                        .val('');
                }
            });

            if ($hexColorInput.val() !== '') {
                $hexColorInput.attr('type', 'color');
            }
        },
        executive_username: function () {
            let $input = $('#executive_username-tr input');

            $input.autocomplete({
                source: app_path_webroot + "UserRights/search_user.php",
                minLength: 2,
                delay: 150,
                html: true,
                select: function (event, ui) {
                    $(this).val(ui.item.value);
                    // UIOWA_AdminDash.fillInfoFields('executive', 'user', ui.item.value)

                    return false;
                }
            });

            $[ "ui" ][ "autocomplete" ].prototype["_renderItem"] = function( ul, item) {
                return $( "<li></li>" )
                    .data( "item.autocomplete", item )
                    .append( $( "<a></a>" ).html( item.label ) )
                    .appendTo( ul );
            };

            $input.on('blur', function() {
                UIOWA_AdminDash.fillInfoFields('executive', 'user', $(this).val())
            });
        },
        executive_url: function() {
            let $fieldTr = $('#executive_url-tr');
            let execUrl = UIOWA_AdminDash.urlLookup.reportBase + '&id=' + UIOWA_AdminDash.reportId;

            $fieldTr
                .find('.url-placeholder')
                .html(execUrl)
                .css('white-space', 'pre-wrap')
                .css('width', '60%')
                .css('float', 'left');

            $fieldTr
                .find('.buttons-placeholder')
                .css('float', 'right')
                .css('padding', '20px')
                .append(`
                <button type="button" class="btn btn-primary exec-action-preview"><i class="fas fa-eye">&nbsp</i>Preview</button>
<!--                <button type="button" class="btn btn-primary exec-action-email"><i class="fas fa-envelope">&nbsp</i>Email User</button>-->
            `);

            $('.exec-action-preview').click(function() {
                window.open(execUrl + '&asUser=' + $('#executive_username-tr input').val(), '_blank'); //todo not working
            })

            // todo update email button on change
            // $('#executive-user-email-tr input').change(function() {
            //     window.open('mailto:' + $('#executive_user_email-tr input').val(), '_blank');
            // })

            $('.exec-action-email').click(function() {
                window.open('mailto:' + $('#executive_user_email-tr input').val(), '_blank');
            })
        },
        sync_project_id: function () {
            let $input = $('#sync_project_id-tr input');

            $input.on('blur', function() {
                UIOWA_AdminDash.fillInfoFields('sync', 'project', $(this).val());
            });
        },
        join_project_id: function () {
            let $input = $('#join_project_id-tr input');

            $input.on('blur', function() {
                UIOWA_AdminDash.fillInfoFields('join', 'project', $(this).val());
            });
        },
        join_report_id: function () {
            let $input = $('#join_report_id-tr input');

            $input.on('blur', function() {
                UIOWA_AdminDash.fillInfoFields('join', 'report',
                    [$(this).val(), $('#join_project_id-tr input').val()]
                );
            });
        }
    }

    UIOWA_AdminDash.fillInfoFields = function(fieldSuffix, type, whereVal) {
        $.ajax({
            url: UIOWA_AdminDash.urlLookup.post,
            method: 'POST',
            data: {
                adMethod: 'getAdditionalInfo',
                type: type,
                whereVal: whereVal,
                redcap_csrf_token: UIOWA_AdminDash.redcap_csrf_token
            },
            success: function(json) {
                console.log(json)
                json = JSON.parse(json);

                $.each(json, function(key, value) {
                    $('#' + fieldSuffix + '_' + key + '-tr input').val(value).blur();
                })
            }

        })
    }

    $.each(UIOWA_AdminDash.fieldCustomizations, function (field_name, fn) {
        if (UIOWA_AdminDash.dataEntryForm.fields.includes(field_name)) {
            fn();
        }
    });

    $('[sq_id^="alert_"]').each(function () {
        let alertClass = $(this).attr('sq_id').split('_')[1];
        let alertIcon = alertClass === 'info' ? 'info-circle' : 'exclamation-triangle';

        let $alertTr = $(this);
        let $alertWrapper = $('<div class="alert alert-' + alertClass + '"/>');
        $alertWrapper.append($alertTr.find('.labelrc').html());
        $alertTr.find('.labelrc').empty().append($alertWrapper);

        $alertWrapper
            .css({
                cssText: 'border-color: black !important',
                margin: '15px'
            })

        $('<i class="fas fa-' + alertIcon + ' fa">&nbsp;</i>').prependTo($alertWrapper);
    });

    console.log('Customizations for Admin Dashboard configuration project active');
})
