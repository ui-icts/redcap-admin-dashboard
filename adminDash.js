(function($, window, document) {
    $(document).ready(function () {
        UIOWA_AdminDash.updateReportTabs(UIOWA_AdminDash.userID);

        $('#pagecontainer').css('cursor', 'default');
        $('#loading').hide();

        // build report table
        if (UIOWA_AdminDash.data != null) {
            var data = UIOWA_AdminDash.data['data'];
            var headers = Object.values(UIOWA_AdminDash.data['headers']);
            var table = $('#reportTable');
            var specialFormatting = {};

            $.each(data, function (i, row) {
                var formattedRow = [];

                $.each(headers, function (j, header) {
                    formattedRow.push(row[header]);
                });

                data[i] = formattedRow;
            });

            if (UIOWA_AdminDash.reportInfo['formatting']) {
                specialFormatting = UIOWA_AdminDash.reportInfo['formatting'];
                data = UIOWA_AdminDash.formatTableData(data, headers, specialFormatting);
            }

            var strTable = "";
            strTable += "<thead>";

            $.each(headers, function(headerIndex, headerValue) {
                strTable += "<th>";
                strTable += headerValue;
                strTable += "</th>";
            });

            strTable += "</thead>";

            $.each(data, function(rowIndex, rowValue) {
                strTable += "<tr>";

                $.each(rowValue, function(cellIndex, cellValue) {
                    strTable += "<td>";
                    strTable += cellValue;
                    strTable += "</td>";
                });

                strTable += "</tr>";
            });

            // get column indexes to hide in export
            var columnId = 0;
            var hiddenExportColumns = [];
            $.each(specialFormatting, function(key, value) {
                if (value['display'] == 1 || value['display'] == 3) {
                    hiddenExportColumns.push(columnId);
                }
                columnId++;
            });

            table.append(strTable);
            table.tablesorter({
                theme: UIOWA_AdminDash.theme,
                widthFixed: true,
                usNumberFormat: false,
                sortReset: false,
                sortRestart: true,
                widgets: ['zebra', 'filter', 'stickyHeaders', 'pager', 'output'],

                widgetOptions: {

                    // output default: '{page}/{totalPages}'
                    // possible variables: {size}, {page}, {totalPages}, {filteredPages}, {startRow}, {endRow}, {filteredRows} and {totalRows}
                    // also {page:input} & {startRow:input} will add a modifiable input in place of the value
                    pager_output: '{startRow:input} – {endRow} / {totalRows} rows', // '{page}/{totalPages}'

                    // apply disabled classname to the pager arrows when the rows at either extreme is visible
                    pager_updateArrows: true,

                    // starting page of the pager (zero based index)
                    pager_startPage: 0,

                    // Number of visible rows
                    pager_size: 10,

                    // Save pager page & size if the storage script is loaded (requires $.tablesorter.storage in jquery.tablesorter.widgets.js)
                    pager_savePages: true,

                    // if true, the table will remain the same height no matter how many records are displayed. The space is made up by an empty
                    // table row set to a height to compensate; default is false
                    pager_fixedHeight: false,

                    // remove rows from the table to speed up the sort of large tables.
                    // setting this to false, only hides the non-visible rows; needed if you plan to add/remove rows with the pager enabled.
                    pager_removeRows: false, // removing rows in larger tables speeds up the sort

                    // use this format: "http://mydatabase.com?page={page}&size={size}&{sortList:col}&{filterList:fcol}"
                    // where {page} is replaced by the page number, {size} is replaced by the number of records to show,
                    // {sortList:col} adds the sortList to the url into a "col" array, and {filterList:fcol} adds
                    // the filterList to the url into an "fcol" array.
                    // So a sortList = [[2,0],[3,0]] becomes "&col[2]=0&col[3]=0" in the url
                    // and a filterList = [[2,Blue],[3,13]] becomes "&fcol[2]=Blue&fcol[3]=13" in the url
                    pager_ajaxUrl: null,

                    // modify the url after all processing has been applied
                    pager_customAjaxUrl: function (table, url) {
                        return url;
                    },

                    // ajax error callback from $.tablesorter.showError function
                    // pager_ajaxError: function( config, xhr, settings, exception ){ return exception; };
                    // returning false will abort the error message
                    pager_ajaxError: null,

                    // modify the $.ajax object to allow complete control over your ajax requests
                    pager_ajaxObject: {
                        dataType: 'json'
                    },

                    // process ajax so that the following information is returned:
                    // [ total_rows (number), rows (array of arrays), headers (array; optional) ]
                    // example:
                    // [
                    //   100,  // total rows
                    //   [
                    //     [ "row1cell1", "row1cell2", ... "row1cellN" ],
                    //     [ "row2cell1", "row2cell2", ... "row2cellN" ],
                    //     ...
                    //     [ "rowNcell1", "rowNcell2", ... "rowNcellN" ]
                    //   ],
                    //   [ "header1", "header2", ... "headerN" ] // optional
                    // ]
                    pager_ajaxProcessing: function (ajax) {
                        return [0, [], null];
                    },

                    // css class names that are added
                    pager_css: {
                        container: 'tablesorter-pager',    // class added to make included pager.css file work
                        errorRow: 'tablesorter-errorRow', // error information row (don't include period at beginning); styled in theme file
                        disabled: 'disabled'              // class added to arrows @ extremes (i.e. prev/first arrows "disabled" on first page)
                    },

                    // jQuery selectors
                    pager_selectors: {
                        container: '.pager',       // target the pager markup (wrapper)
                        first: '.first',       // go to first page arrow
                        prev: '.prev',        // previous page arrow
                        next: '.next',        // next page arrow
                        last: '.last',        // go to last page arrow
                        gotoPage: '.gotoPage',    // go to page selector - select dropdown that sets the current page
                        pageDisplay: '.pagedisplay', // location of where the "output" is displayed
                        pageSize: '.pagesize'     // page size selector - select dropdown that sets the "size" option
                    },

                    //stickyHeaders_attachTo: '.redcap-home-navbar-collapse',
                    stickyHeaders_offset: 50,

                    output_separator     : ',',         // ',' 'json', 'array' or separator (e.g. ';')
                    output_ignoreColumns : hiddenExportColumns,         // columns to ignore [0, 1,... ] (zero-based index)
                    output_hiddenColumns : true,       // include hidden columns in the output
                    output_includeFooter : true,        // include footer rows in the output
                    output_includeHeader : true,        // include header rows in the output
                    output_headerRows    : false,       // output all header rows (if multiple rows)
                    output_dataAttrib    : 'data-export', // data-attribute containing alternate cell text
                    output_delivery      : 'd',         // (p)opup, (d)ownload
                    output_saveRows      : 'a',         // (a)ll, (v)isible, (f)iltered, jQuery filter selector (string only) or filter function
                    output_duplicateSpans: true,        // duplicate output data in tbody colspan/rowspan
                    output_replaceQuote  : '\u201c;',   // change quote to left double quote
                    output_includeHTML   : false,        // output includes all cell HTML (except the header cells)
                    output_trimSpaces    : true,       // remove extra white-space characters from beginning & end
                    output_wrapQuotes    : false,       // wrap every cell output in quotes
                    output_popupStyle    : 'width=580,height=310',
                    output_saveFileName  : UIOWA_AdminDash.csvFileName,
                    // callback executed after the content of the table has been processed
                    output_formatContent : function(config, widgetOptions, data) {
                        // data.isHeader (boolean) = true if processing a header cell
                        // data.$cell = jQuery object of the cell currently being processed
                        // data.content = processed cell content (spaces trimmed, quotes added/replaced, etc)
                        // data.columnIndex = column in which the cell is contained
                        // data.parsed = cell content parsed by the associated column parser
                        return data.content.replace(/ \[suspended]/ig, '').replace(/Email All/ig, '');
                    },
                    // callback executed when processing completes
                    output_callback      : function(config, data, url) {
                        // return false to stop delivery & do something else with the data
                        // return true OR modified data (v2.25.1) to continue download/output

                        if (config['widgetOptions']['output_delivery'] == 'd') {
                            data = '\ufeff' + data;
                        }

                        return data;
                    },
                    // callbackJSON used when outputting JSON & any header cells has a colspan - unique names required
                    output_callbackJSON  : function($cell, txt, cellIndex) {
                        return txt + '(' + cellIndex + ')';
                    },
                    // the need to modify this for Excel no longer exists
                    //output_encoding      : 'data:application/octet-stream;charset=utf8',
                    // override internal save file code and use an external plugin such as
                    // https://github.com/eligrey/FileSaver.js
                    output_savePlugin    : null /* function(config, widgetOptions, data) {
                     var blob = new Blob([data], {type: widgetOptions.output_encoding});
                     saveAs(blob, widgetOptions.output_saveFileName);
                     } */

                }
            });

            // additonal post-tablesorter formatting
            columnId = 1;
            $.each(specialFormatting, function(key, value) {
                var column = $('td:nth-child(' + columnId + ')');

                column.each(function (i, td) {
                    var links = $('a', td);
                    var csvList = '';
                    var mailto = 'mailto:?bcc=';

                    // format link lists into csv for export
                    if (links.length > 1) {
                        links.each(function (j, link) {
                            var linkText = $(link).text();

                            if (value['link'] == 'Email') {
                                mailto += linkText + ';';
                            }

                            if (csvList == '') {
                                csvList = linkText;
                            }
                            else {
                                csvList += ',' + linkText;
                            }
                        });

                        if (value['link'] == 'Email') {
                            // add 'Email All' button where appropriate
                            $(td).append('<div style="padding-top:10px;"><button style="float:right;" class="btn btn-info btn-sm" onclick="location.href=\'' + mailto + '\'">Email All</button></div>')
                        }

                        $(td).attr('data-export', csvList);
                    }
                });

                if (value['display'] == 2 || value['display'] == 3) {
                    column = $('td:nth-child(' + columnId + '),th:nth-child(' + columnId + ')');
                    column.hide();
                }
                columnId++;
            });

            var $this = $(".output-button");

            $this.find('.dropdown-toggle').click(function(e) {
                // this is needed because clicking inside the dropdown will close
                // the menu with only bootstrap controlling it.
                $this.find('.dropdown-menu').toggle();
                return false;
            });
            // make separator & replace quotes buttons update the value
            $this.find('.output-separator').click(function() {
                $this.find('.output-separator').removeClass('active');
                var txt = $(this).addClass('active').html();
                $this.find('.output-separator-input').val( txt );
                var filename = $this.find('.output-filename');
                var filetype = (txt === 'json' || txt === 'array') ? 'js' :
                    txt === ',' ? 'csv' : 'txt';
                filename.val(function(i, v) {
                    // change filename extension based on separator
                    return v.replace(/\.\w+$/, '.' + filetype);
                });
                var outputType = $($this.find('.output-type.active'))[0].innerText;
                if (outputType == 'File') {
                    $this.find('.download').html('<span class="fas fa-download"></span> Export ' + filetype.toUpperCase() + ' File');
                }
                else if (outputType == 'Popup') {
                    $this.find('.download').html('<span class="far fa-window-maximize"></span> Open ' + filetype.toUpperCase() + ' Popup');
                }
                return false;
            });
            $this.find('.output-type').click(function() {
                var outputType = $(this)[0].innerText;
                var filename = $this.find('.output-filename');
                var txt = $($this.find('.output-separator.active')).html();
                var filetype = (txt === 'json' || txt === 'array') ? 'js' :
                    txt === ',' ? 'csv' : 'txt';
                if (outputType == 'File') {
                    $this.find('.download').html('<span class="fas fa-download"></span> Export ' + filetype.toUpperCase() + ' File');
                    $this.find('.filename-field-display').removeClass('hidden');
                    $this.find('.separator-field-display').removeClass('hidden');
                    $this.find('.include-field-display').removeClass('hidden');
                    $this.find('.target-field-display').addClass('hidden');
                    $this.find('.download').prop('disabled', false);
                }
                else if (outputType == 'Popup') {
                    $this.find('.download').html('<span class="far fa-window-maximize"></span> Open ' + filetype.toUpperCase() + ' Popup');
                    $this.find('.filename-field-display').addClass('hidden');
                    $this.find('.separator-field-display').removeClass('hidden');
                    $this.find('.include-field-display').removeClass('hidden');
                    $this.find('.target-field-display').addClass('hidden');
                    $this.find('.download').prop('disabled', false);
                }
                else if (outputType == 'Project') {
                    $this.find('.download').html('<span class="far fa-list-alt"></span> Send to REDCap');
                    $this.find('.filename-field-display').addClass('hidden');
                    $this.find('.separator-field-display').addClass('hidden');
                    $this.find('.include-field-display').addClass('hidden');
                    $this.find('.target-field-display').removeClass('hidden');
                    $this.find('.download').prop('disabled', true);
                }
                //return false;
            });
            // clicking the download button; all you really need is to
            // trigger an "output" event on the table
            $this.find('.download').click(function() {
                var outputType = $($this.find('.output-type.active'))[0].innerText;

                if (outputType == 'Project') {
                    $('#projectImportConfirm').modal({backdrop: 'static', keyboard: false});
                }
                else {
                    var typ,
                        $table = $("#reportTable"),
                        wo = $table[0].config.widgetOptions,
                        val = $this.find('.output-filter-all :checked').attr('class');

                    wo.output_saveRows     = val === 'output-filter' ? 'f' :
                        val === 'output-visible' ? 'v' :
                            // checked class name, see table.config.checkboxClass
                            val === 'output-selected' ? '.checked' :
                                val === 'output-sel-vis' ? '.checked:visible' :
                                    'a';
                    val = $this.find('.output-download-popup :checked').attr('class');
                    wo.output_delivery     = val === 'output-download' ? 'd' : 'p';
                    wo.output_separator    = $this.find('.output-separator-input').val();
                    //wo.output_replaceQuote = $this.find('.output-replacequotes').val();
                    //wo.output_trimSpaces   = $this.find('.output-trim').is(':checked');
                    //wo.output_includeHTML  = $this.find('.output-html').is(':checked');
                    //wo.output_wrapQuotes   = $this.find('.output-wrap').is(':checked');

                    var filename = $this.find('.output-filename').val();

                    if ($this.find('.filename-datetime').is(':checked')) {
                        var splitFilename = filename.split('.');
                        splitFilename.splice(-1, 0, UIOWA_AdminDash.renderDatetime);
                        wo.output_saveFileName = splitFilename.join('.');
                    }
                    else {
                        wo.output_saveFileName = filename;
                    }

                    $table.trigger('outputTable');
                    return false;
                }
            });

            $this.show();
            $('#report-content').show();
        }
        else {
            $('#no-results').show();
        }

        if (UIOWA_AdminDash.hideColumns) {
            for (var i in UIOWA_AdminDash.hideColumns) {
                $('#reportTable tr > *:nth-child(' + UIOWA_AdminDash.hideColumns[i] + ')').hide();
                $('#reportTable-sticky tr > *:nth-child(' + UIOWA_AdminDash.hideColumns[i] + ')').hide();
            }
        }

        //todo ???
        //if (sessionStorage.getItem("selectedUser") && UIOWA_AdminDash.superuser) {
        //    $('.executiveUser').val( sessionStorage.getItem("selectedUser") );
        //    UIOWA_AdminDash.userID = $('.executiveUser')[0].value;
        //}
        //$('.executiveUser').change(function() {
        //    $('.executiveUser').not(this).val( this.value );
        //    sessionStorage.setItem("selectedUser", this.value);
        //    UIOWA_AdminDash.updateSettingsModal(this.value);
        //});

        $('.output-filename').val(UIOWA_AdminDash.csvFileName);

        $('#exportProjectSelect').change(function() {
            $('#pagecontainer').css('cursor', 'progress');
            $this.find('.download').prop('disabled', true);

            var projectLink = $('.target-project-link');

            projectLink.html($('#exportProjectSelect option:selected').text());
            projectLink.attr('href', UIOWA_AdminDash.redcapVersionUrl + 'ProjectSetup/index.php?pid=' + $('#exportProjectSelect').val());

            if (this.value != '') {
                $.ajax({
                    method: 'POST',
                    url: UIOWA_AdminDash.requestHandlerUrl + '&type=getApiToken',
                    data: {
                        pid: $this.find('#exportProjectSelect')[0].value
                    }
                })
                .done(function(token) {
                    $.ajax({
                        method: 'POST',
                        url: UIOWA_AdminDash.redcapBaseUrl + 'api/',
                        data: {
                            token: token,
                            content: 'metadata',
                            format: 'json',
                            field: JSON.stringify(UIOWA_AdminDash.data['project_headers'])
                        },
                        success: function(data) {
                            var projectFields = $.map(data, function (field) {
                                return field['field_name'];
                            });

                            var requiredFields = UIOWA_AdminDash.data['project_headers'];

                            var validProject = projectFields.filter(function (elem) {
                                return requiredFields.indexOf(elem) > -1;
                            }).length == requiredFields.length;

                            // if project contains required fields for import, enable button
                            if (validProject) {
                                $this.find('.download').prop('disabled', false);
                            }
                            else {
                                $this.find('.download').prop('disabled', true);
                                $('#invalidProjectWarning').modal('show');
                            }

                            $('#pagecontainer').css('cursor', 'default');
                        }
                    })
                });
            }
            else {
                $this.find('.download').prop('disabled', true);
            }
        });

        $('#invalidProjectWarning').on('hidden.bs.modal', function () {
            $('#confirmProjectUpdate').hide();
            $('.force-import').hide();
            $('.force-import-close').html('Close');
        });

        $('.show-project-warning-text').click(function () {
            $('#confirmProjectUpdate').show();

            $('.force-import').show();
            $('.force-import-close').html('Cancel');
        });

        $('.confirm-import').click(function () {
            $(this).prop('disabled', true);
            $('.import-close').prop('disabled', true);
            $(this).html('<i class="fas fa-spinner fa-spin import-progress"></i>');

            // get project api token
            $.ajax({
                method: 'POST',
                url: UIOWA_AdminDash.requestHandlerUrl + '&type=getApiToken',
                data: {
                    pid: $this.find('#exportProjectSelect')[0].value
                }
            })
            .done(function(token) {
                // import data
                $.ajax({
                    method: 'POST',
                    url: UIOWA_AdminDash.redcapBaseUrl + 'api/',
                    data: {
                        token: token,
                        content: 'record',
                        format: 'json',
                        data: JSON.stringify(UIOWA_AdminDash.data['project_data'])
                    },
                    success: function(data) {
                        $('.confirm-import').html('<i class="fas fa-check"></i> Success').removeClass('btn-primary').addClass('btn-success');
                        $('#importInfoText').hide();
                        $('.import-close').html('Close').prop('disabled', false);

                        if (data['count']) {
                            $('#importedRecordCount').html(data['count']);
                            $('#importCompleteText').show();
                        }
                    },
                    error: function(data) {
                        var response = data['responseJSON'];
                        $('.confirm-import').html('<i class="fas fa-times"></i> Error').removeClass('btn-primary').addClass('btn-danger');
                        $('#importInfoText').hide();
                        $('.import-close').html('Close').prop('disabled', false);

                        if (response['error']) {
                            $('#redcapApiErrorText').html(response['error']);
                            $('#importErrorText').show();
                        }
                        else {
                            $('#redcapApiErrorText').html('An unknown error occurred.');
                            $('#importErrorText').show();
                        }
                    }
                })
            })
        });

        $('#projectImportConfirm').on('hidden.bs.modal', function () {
            $('#importErrorText').hide();
            $('#importCompleteText').hide();
            $('#importInfoText').show();
            $('.import-close').html('Cancel');
            $('.confirm-import').html('Import').prop('disabled', false).removeClass('btn-danger btn-success').addClass('btn-primary');
        });

        $('.force-import').click(function () {
            $.ajax({
                method: 'POST',
                url: UIOWA_AdminDash.requestHandlerUrl + '&type=getApiToken',
                data: {
                    pid: $this.find('#exportProjectSelect')[0].value
                }
            })
            .done(function (token) {
                UIOWA_AdminDash.importProjectMetadata(token);
            });
        });

        $('.open-settings').click(function() {
            window.open(UIOWA_AdminDash.settingsUrl, "_self");
        });

        UIOWA_AdminDash.updateReportTabs('');

        $('#primaryUserSelect').change(function() {
            UIOWA_AdminDash.updateReportTabs($('#primaryUserSelect').val());
        });

        // Enable tooltips
        $('[data-toggle="tooltip"]').tooltip();

    });

}(window.jQuery, window, document));

var UIOWA_AdminDash = {};

UIOWA_AdminDash.formatTableData = function(data, headers, formatting)
{
    var formattingReference = UIOWA_AdminDash.formattingReference['links'];
    var formattedData = {};

    // loop through each row
    $.each(data, function(rowIndex, row) {
        //var pidIndex = headers.indexOf('project_id');
        //var projectIds = (row[pidIndex] == null) ? null : row[pidIndex].split('@@@');
        //headers = $.grep(headers, function(value) {
        //    return value != 'project_id';
        //});
        //delete row[pidIndex];

        //console.log(JSON.parse(JSON.stringify(projectIds)));

        formattedData[rowIndex] = {};
        var newHeaders = [];
        var newValues = [];

        // loop through each cell
        $.each(row, function(cellIndex, cell) {
            formattedData[rowIndex][cellIndex] = '';
            var columnHeader = headers[cellIndex];
            var cellValues = (cell == null) ? [] : cell.split('@@@');
            // prepare reference columns, otherwise use self
            var projectIds = (row[headers.indexOf('project_id')]) ? row[headers.indexOf('project_id')].split('@@@') : [];
            var projectStatuses = (row[headers.indexOf('status')]) ? row[headers.indexOf('status')].split('@@@') : [];
            var projectDeletedDates = (row[headers.indexOf('date_deleted')]) ? row[headers.indexOf('date_deleted')].split('@@@') : [];
            var userSuspendedTimes = (row[headers.indexOf('user_suspended_time')]) ? row[headers.indexOf('user_suspended_time')].split('@@@') : [];

            // repeat for each group item in cell
            $.each(cellValues, function (index, value) {
                var columnFormatting = formatting[columnHeader] ? formatting[columnHeader] : {
                    linkGroup: 'none',
                    link: 'not set'
                };
                var linkGroup = columnFormatting['linkGroup'];
                var linkType = columnFormatting['link'];

                if (linkType != 'not set' && linkGroup != 'Code Lookup') {
                    var linkUrl = '';
                    var linkStyle = '';
                    var suspendedTag = '';

                    if (linkType == 'Custom') {
                        linkUrl = columnFormatting['custom'].replace('{value}', value);
                    }
                    else if (linkType == 'Survey Hash') {
                        linkUrl = UIOWA_AdminDash.redcapBaseUrl + formattingReference[linkGroup][linkType] + value;
                    }
                    else if (linkType == 'Email') {
                        linkUrl = 'mailto:' + value;
                    }
                    else if (linkGroup == 'Project Links (project_id)') {

                        if (projectDeletedDates != null) {
                            var status = projectStatuses[index];
                            var dateDeleted = projectDeletedDates[index];

                            if (status == '3') {
                                linkStyle = 'id="archived"';
                            }
                            if (dateDeleted != 'F' && dateDeleted != null) {
                                linkStyle = 'id="deleted"';
                            }
                        }

                        linkUrl = UIOWA_AdminDash.redcapVersionUrl + formattingReference[linkGroup][linkType] + projectIds[index];
                    }
                    else if (linkGroup == 'User Links (username)') {
                        if (userSuspendedTimes != null) {
                            var suspended = userSuspendedTimes[index];

                            if (suspended != 'F' && suspended != null) {
                                suspendedTag = '<br /><span id="suspended">[suspended]</span>';
                            }
                        }

                        linkUrl = UIOWA_AdminDash.redcapVersionUrl + formattingReference[linkGroup][linkType] + value;
                    }

                    formattedData[rowIndex][cellIndex] += '<a href="' + linkUrl + '" target="_blank"' + linkStyle + '>' + value + '</a>' + suspendedTag + '<br />';
                }
                else if (linkType == 'Project Purpose' || linkType == 'Project Status') {
                    formattedData[rowIndex][cellIndex] = formattingReference[linkGroup][linkType][value];
                }
                else if (linkType == 'Research Purpose') {
                    value = value.split(',');
                    value = $.map(value, function (index, item) {
                        return formattingReference[linkGroup][linkType][item];
                    });

                    formattedData[rowIndex][cellIndex] = value.join(', ');
                }
                else if (linkType == 'Research Purpose (Split)') { //todo
                    value = value.split(',');

                    $.each(formattingReference[linkGroup][linkType], function (index, item) {
                        newHeaders.push(item);

                        if (value.indexOf(index) != -1) {
                            newValues.push('TRUE');
                        }
                        else {
                            newValues.push('FALSE');
                        }
                    });
                    //formattedData[rowIndex][cellIndex] = formattingReference[linkGroup][linkType][value];
                }
                else {
                    formattedData[rowIndex][cellIndex] += value + '<br />';
                }
            });
        });

        //console.log(newHeaders);
        //console.log(newValues);

        //formattedData.splice(0, 0, newValues)
    });

    return formattedData;
};

UIOWA_AdminDash.updateReportTabs = function(user) {
    var keys = Object.keys(UIOWA_AdminDash.adminVisibility);

    for (var i in keys) {
        var reportTitle = keys[i];
        var adminVisible = UIOWA_AdminDash.adminVisibility[reportTitle];
        var executiveVisible = $.inArray(user, UIOWA_AdminDash.executiveVisibility[reportTitle]) != -1;
        var reportTab = $('.report-tabs > li ').filter(function() {
            return $('.report-title', this).text() === reportTitle;
        });

        if (!adminVisible && !UIOWA_AdminDash.executiveAccess) {
            reportTab.hide("slow");
        }
        else if (!executiveVisible && UIOWA_AdminDash.executiveAccess) {
            reportTab.hide();
        }
        else {
            reportTab.show("slow");
        }
    }
};

UIOWA_AdminDash.importProjectMetadata = function (token) {
    var reportTitle = $('#reportTitle').html().trim().toLowerCase().split(' ').join('_');

    var metadata = [];
    var fieldInfo = {
        field_name: 'record_id',
        form_name: reportTitle,
        section_header: '',
        field_type: 'text',
        field_label: 'Record ID'
    };

    metadata.push(fieldInfo);

    $.each(UIOWA_AdminDash.data['project_headers'], function (index, value) {
        fieldInfo = {
            field_name: value,
            form_name: reportTitle,
            section_header: '',
            field_type: 'text',
            field_label: UIOWA_AdminDash.data['headers'][index]
        };

        metadata.push(fieldInfo);
    });

    $.ajax({
        method: 'POST',
        url: UIOWA_AdminDash.redcapBaseUrl + 'api/',
        data: {
            token: token,
            content: 'metadata',
            format: 'json',
            data: JSON.stringify(metadata)
        },
        success: function(data) {
            console.log(data);

            $('#invalidProjectWarning').modal('hide');
            $('#projectImportConfirm').modal({backdrop: 'static', keyboard: false});
        },
        error: function(data) {
            var response = data['responseJSON'];

            if (response['error']) {
                alert(response['error']);
            }
            else {
                alert('An unknown error occurred when attempting to update your project.');
            }
        }
    })
};