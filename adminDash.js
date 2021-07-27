$.extend(UIOWA_AdminDash, {
    initDatatable: function () {
        let self = this;

        // report edit shortcut (admins only)
        $('.edit-report').click(function () {
            let loadedReportMeta = self.loadedReport.meta;
            let url = self.urlLookup.redcapBase + '/DataEntry/record_home.php?pid=' + self.configPID + '&id=' + loadedReportMeta.config.report_id;

            if ("project_join_info" in loadedReportMeta) {
                url += "&arm=2";
            }

            window.open(url, '_blank');
        })

        let data = self.loadedReport.data;
        let columns = $.map(self.loadedReport.columns, function (column_name) {
            return {
                title: column_name,
                data: column_name,
                className: '',
                contentPadding: "mmm",
                createdCell: function (td, cellData, rowData, row, col) {
                    $(td).css('text-align', 'center')
                }
            };
        });

        if (self.loadedReport.meta.column_formatting) {
            // set column titles and renderers
            columns = $.map(self.loadedReport.columns, function (column_name) {
                let columnDetails = self.loadedReport.meta.column_formatting[column_name];
                let column = {
                    title: columnDetails.displayHeader !== '' ? columnDetails.displayHeader : column_name,
                    data: column_name,
                    className: columnDetails.dashboard_show_column === '0' ? 'noVis' : '',
                    contentPadding: "mmm",
                    createdCell: function (td, cellData, rowData, row, col) {
                        $(td).css('text-align', 'center')
                    }
                };

                // only apply column formatting for sql reports
                if (self.loadedReport.meta.config.report_sql !== '') {
                    $.fn.dataTable.render.adFormat = function(column_name) {
                        return function(data, type, row) {
                            return self.adFormat(column_name, data, type, row);
                        };
                    }

                    column.render = $.fn.dataTable.render.adFormat(column_name);
                }

                return column;
            });

            // add column for child row collapse buttons (if at least one column needs it)
            let hasChildRow = false;
            $.each(self.loadedReport.meta.column_formatting, function (column_name, value) {

                if (value.dashboard_show_column === '2') {
                    hasChildRow = true;
                }
            });
            if (hasChildRow) {
                $('.report-table > thead > tr:first').prepend('<th></th>');

                columns.unshift({
                    className: 'details-control',
                    orderable: false,
                    data: null,
                    defaultContent: '',
                    render: function () {
                        return '<i class="fa fa-plus-square" aria-hidden="true"></i>';
                    },
                    width:"15px"
                });
            }
        }

        // init DataTable
        let table = $('.report-table').DataTable({
            data: data,
            scrollXInner: true,
            // scrollY: true,
            // stateSave: true, todo - saved sorting can be confusing
            colReorder: true,
            fixedHeader: {
                header: true,
                headerOffset: $('#redcap-home-navbar-collapse').height()
            },
            columns: columns,
            order: [],
            initComplete: function() {
                let hasFilters = false;
                let $filterRow = $('<tr class="filter-row"></tr>');

                // add column filters
                this.api().columns().every( function () {
                    let $filter = self.dtFilterInit(this);

                    if ($filter) {
                        $filterRow.append($filter);
                        hasFilters = true
                    }
                } );

                if (hasFilters) {
                    $('thead').append($filterRow);
                }
            }
        });

        // generate export buttons
        self.dtExportInit(table);
        $('#buttons').show();

        // show/hide columns
        new $.fn.dataTable.Buttons(table, {
            buttons: [
                {
                    text: 'Show/Hide Columns',
                    extend: 'colvis',
                    columns: ':not(.noVis)'
                }
            ]
        }).container().appendTo($('#visButtons'));

        // sync filter visibility with column
        table.on( 'column-visibility.dt', function ( e, settings, column, state ) {
            console.log(
                'Column '+ column +' has changed to '+ (state ? 'visible' : 'hidden')
            );

            let $filterTd = $('.filter-row > td').eq(column);

            state ? $filterTd.show() : $filterTd.hide();
        } );

        // child row show/hide logic
        $('.report-table tbody').on('click', 'td.details-control', function () {
            let tr = $(this).closest('tr');
            let row = table.row( tr );

            if ( row.child.isShown() ) {
                // This row is already open - close it
                row.child.hide();
                tr.removeClass('shown');
            }
            else {
                // Open this row
                row.child( self.formatChildRow(row.data()) ).show();
                tr.addClass('shown');
            }
        } );
    },
    formatChildRow: function(row) {
        let self = this;
        let columnDetails = this.loadedReport.meta.column_formatting;
        let htmlRows = '';

        $.each(columnDetails, function(column_name, details) {
            let data = self.adFormat(column_name, row[column_name], row)

            if (details.dashboard_show_column === '2') {
                htmlRows = htmlRows.concat('<tr>' +
                    '<td>' + (details.dashboard_display_header !== '' ? details.dashboard_display_header : column_name) + '</td>' +
                    '<td>' + data + '</td>' +
                    '</tr>');
            }
        })

        // `d` is the original data object for the row
        return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">'+
            htmlRows+
        '</table>';
    },
    splitData: function(data, column_name) {
        let separator = this.loadedReport.meta.column_formatting[column_name].group_concat_separator;

        return separator !== '' ? data.split(separator) : [data];
    },
    dtFilterInit: function(column) {
        let self = this;
        let $filterTd = $('<td data-column-index="' + column.index() + '"></td>');
        let column_name = self.loadedReport.columns[column.index()];
        let columnDetails = self.loadedReport.meta.column_formatting[column_name];

        // todo
        if (column_name === undefined) {
            return;
        }

        // hide column
        if (columnDetails.dashboard_show_column === '0') {
            column.visible(false);
            return;
        }

        // add dropdown filter
        if (columnDetails.dashboard_show_filter === '2') {
            $filterTd.append('<select style="width: 100%"><option value=""></option></select>');
            let $select = $filterTd.find('select')
                .on( 'change', function () {
                    let val = $.fn.dataTable.util.escapeRegex(
                        $(this).val()
                    );

                    column
                        .search( val ? '^'+val+'$' : '', true, false )
                        .draw();
                } );

            // get unique values and add to dropdown list
            column.data().unique().sort().each( function ( value ) {
                // todo filtering for null values
                if (value == null) {
                    $select.prepend( '<option value="null">[null]</option>' )
                }
                // else {
                let formattedValue = self.adFormat_code(value, columnDetails.code_type)

                if (formattedValue) {
                    $select.append( '<option value="'+formattedValue+'">'+formattedValue+'</option>' );
                }

                // }
                // if ($(d).has('<a>')) {
                //     d = $(d).text();
                // }
                // console.log(j)

            } );
        }
        // add free text filter
        else if (columnDetails.dashboard_show_filter === '1') {
            $filterTd.append('<input style="width: 100%"/>');

            $( 'input', $filterTd ).on( 'keyup change clear', function () {
                if ( column.search() !== this.value ) {

                    // todo split grouped data and filter items
                    // let groupData = column.data().split()

                    column
                        .search( this.value )
                        .draw();
                }
            } );
        }

        return $filterTd;
    },
    dtExportInit: function(table) {
        let buttonCommon = {
            title: this.loadedReport.meta.config.report_title,
            exportOptions: {
                orthogonal: 'export',
                // format: {
                //     body: function ( data, row, column, node ) {
                //         // Strip $ from salary column to make it numeric
                //         return column === 5 ?
                //             data.replace( /[$,]/g, '' ) :
                //             data;
                //     }
                // }
            }
        };

        // export buttons
        new $.fn.dataTable.Buttons(table, {
            buttons: [
                $.extend( true, {}, buttonCommon, {
                    extend: 'copyHtml5'
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'csvHtml5'
                } ),
                // $.extend( true, {}, buttonCommon, {
                //     extend: 'pdfHtml5'
                // } ),
                $.extend( true, {
                    text: 'JSON',
                    action: function ( e, dt, button, config ) {
                        let data = dt.buttons.exportData();

                        $.fn.dataTable.fileSave(
                            new Blob( [ JSON.stringify( data ) ] ),
                            buttonCommon.title + '.json'
                        );
                    }
                }, buttonCommon)
            ]
        }).container().appendTo($('#buttons'));
    },
    adFormat: function (column_name, data, type, row) {
        if (data === null) {
            return type === 'display' ? '<span class="text-muted">null</span>' : 'null';
        }

        let self = this;
        let columnDetails = self.loadedReport.meta.column_formatting[column_name];
        let sourceColumn = columnDetails.link_source_column !== '' ? columnDetails.link_source_column : column_name;
        data = self.splitData(data, column_name);
        let sourceData = data;
        let formattedSeparator = type === 'export' ? ';' : '<br />'

        if (sourceColumn !== column_name) {
            sourceData = self.splitData(row[sourceColumn], sourceColumn);
        }

        // for each item (in case data is grouped)
        data = $.map(data, function(item, index) {
            if (item === null) {
                return type === 'display' ? '<span class="text-muted">null</span>' : 'null';
            }

            //todo
            // fix for "Archived" projects
            // if (value === 3) {
            //     value = 2;
            // }

            let formattedVal = item;
            let rawUrl = '';
            let iconsHtml = '';

            // Replace coded value with label
            if (columnDetails.code_type !== '') {
                try {
                    // if export, check if labels are preferred
                    if (type === 'export' && columnDetails.export_codes === '0') {
                        formattedVal = item
                    }
                    else {
                        formattedVal = self.adFormat_code(item, columnDetails.code_type);
                    }

                    if (type === 'filter') {
                        return formattedVal; //todo broken
                    }
                }
                catch(e) {
                    console.groupCollapsed("Failed to replace codes with labels for " + column_name);
                    console.log(columnDetails.code_type, item)
                    console.log(e)
                    console.groupEnd()
                }
            }

            // generate url for linking
            if (columnDetails.link_type !== '' && !self.executiveView) {
                try {
                    rawUrl = self.adFormat_url(
                        item,
                        sourceData[index],
                        columnDetails.link_type,
                        columnDetails.specify_custom_link
                    );

                    if (type === 'export') {
                        if (columnDetails.export_urls === '1') {
                            formattedVal = rawUrl;
                        } else {
                            formattedVal = item;
                        }
                    } else if (type === 'filter') {
                        formattedVal = item;
                    } else {
                        formattedVal = `<a href="${rawUrl}" target="_blank">${formattedVal}</a>`;
                    }
                }
                catch(e) {
                    console.groupCollapsed("Failed to generate url(s) for " + column_name)
                    console.log(e)
                    console.groupEnd()
                }
            }

            // prepend hint icons
            if (
                (columnDetails.hint_icons___1 === '1' ||
                columnDetails.hint_icons___2 === '1') &&
                type === 'display'
            ) {
                try {

                        let columnReference = {
                            withTags: self.loadedReport.columns,
                            tagless: $.map(self.loadedReport.columns, function(value) {
                                return value.split('#')[0]
                            })
                        }

                        if (item) {
                            iconsHtml = self.adFormat_icons(item, index, row, columnReference, columnDetails);
                        }
                    }
                catch(e) {
                    console.groupCollapsed("Failed to process hint icon(s) for " + column_name)
                    console.log(item)
                    console.log(e)
                    console.groupEnd()
                }
            }

            return iconsHtml + formattedVal;
        })
        data = data.join(formattedSeparator)

        return data;
    },
    adFormat_url: function (value, sourceValue, linkIndex, customUrl) {
        let url = '';

        // set custom url
        if (linkIndex === '99') {
            url = customUrl.replace('{value}', sourceValue);
        }
        // set mailto
        else if (linkIndex === '9') {
            url = 'mailto:' + sourceValue;
        }
        // set redcap url
        else {
            try {
                url = this.urlLookup.redcapBase + this.formattingReference.links[linkIndex - 1].trim() + sourceValue;
            }
            catch (error) { // invalid link index
                console.error(error);
                return value;
            }
        }

        return url;
    },
    adFormat_code: function (value, codeIndex) {
        if (codeIndex === '1') { // Project Status
            return this.formattingReference.status[value];
        }
        else if (codeIndex === '2') { // Project Purpose
            return this.formattingReference.purpose[value];
        }
        else if (codeIndex === '3') { // Research/Other Purpose
            let self = this;
            let valueArray = value.split(',');

            if (Array.isArray(valueArray) && !valueArray.some(isNaN)) {
                valueArray = $.map(value, function (code) {
                    return self.formattingReference.purpose_other[code]
                });

                value = valueArray.join(', ')
            }

            return value;
        }
        // else if (userConfig.specify_code_lookup === '4') { // todo Research Purpose split
        //     codeReference = this.formattingReference.research_code_lookup;
        // }
    },
    adFormat_icons: function (value, index, row, columnReference, columnDetails) {
        let returnHtml = '';

        // suspended users
        if (columnDetails.hint_icons___1 === '1' && columnReference.tagless.includes('user_suspended_time')) {
            let suspendedColumnName = columnReference.withTags[columnReference.tagless.indexOf('user_suspended_time')];
            let suspendedValue = row[suspendedColumnName] !== null ? this.splitData(row[suspendedColumnName], suspendedColumnName)[index] : null;

            if (suspendedValue.length > 8) {
                returnHtml +=
                    `<span class="user-detail" title="User suspended" data-toggle="tooltip" data-placement="left">
                    <i class="fas fa-ban fa-fw" style="color: red;"></i>
                </span>`;
            }
        }
        // project status
        if (columnDetails.hint_icons___2 === '1') {
            let hintIcon = {};

            if (columnReference.tagless.includes('status')) {
                let iconLookup = [
                    {
                        class: '',
                        tooltip: 'Development',
                        icon: 'wrench',
                        color: '#444'
                    },
                    {
                        class: '',
                        tooltip: 'Production',
                        icon: 'check-square',
                        color: '#00A000'
                    },
                    {
                        class: '',
                        tooltip: 'Analysis/Cleanup',
                        icon: 'minus-circle',
                        color: '#A00000'
                    }
                ]

                let statusColumnName = columnReference.withTags[columnReference.tagless.indexOf('status')];
                let statusValue = this.splitData(row[statusColumnName], statusColumnName)[index];

                hintIcon = iconLookup[statusValue]
            }
            if (columnReference.tagless.includes('completed_time')) {
                let completedColumnName = columnReference.withTags[columnReference.tagless.indexOf('completed_time')];
                let completedValue = this.splitData(row[completedColumnName], completedColumnName)[index];

                if (completedValue) {
                    hintIcon = {
                        class: '',
                        tooltip: 'Completed',
                        icon: 'archive',
                        color: '#C00000'
                    }
                }
            }
            if (columnReference.tagless.includes('date_deleted')) {
                let deletedColumnName = columnReference.withTags[columnReference.tagless.indexOf('date_deleted')];
                let deletedValue = this.splitData(row[deletedColumnName], deletedColumnName)[index];

                if (deletedValue) {
                    hintIcon = {
                        class: '',
                        tooltip: 'Deleted',
                        icon: 'trash',
                        color: '#A00000'
                    }
                }
            }

            returnHtml +=
                `<span class="${hintIcon.class}" title="${hintIcon.tooltip}" data-toggle="tooltip" data-placement="left">
                    <i class="fas fa-${hintIcon.icon} fa-fw" style="color: ${hintIcon.color};"></i>
                </span>`;
        }

        return returnHtml;
    }
});

$(document).ready(function() {
    let self = UIOWA_AdminDash;

    // initialize Vue.js
    new Vue({
        el: '#adminDashApp',
        data: self,
        updated: function() {
            this.$nextTick(function () {
                $('#adminDashApp').show();
                self.initDatatable();
            })
        },
        methods: {
            isActiveReport: function (id) {
                let loadedId = self.loadedReport.meta.config.report_id;

                return id === loadedId ? 'active' : '';
            },
            getReportIcon: function (icon) {
                return icon !== '' ? 'fas fa-' + icon : 'fas fa-file';
            },
            getDisplayHeader: function (column_name) {
                if (typeof self.loadedReport.meta.column_formatting !== 'undefined') {
                    let columnDetails = self.loadedReport.meta.column_formatting[column_name];
                    column_name = columnDetails.dashboard_display_header !== '' ? columnDetails.dashboard_display_header : column_name;
                }

                return column_name;
            },
            getReports: function (reportLookup, inFolders) {
                let formattedLookup = inFolders ? {} : [];

                $.each(reportLookup, function (index, report) {
                    if (report.report_title === '') {
                        report.report_title = 'Untitled Report';
                    }

                    if (inFolders) {
                        if (report.folder_name !== '') {
                            if (!(report.folder_name in formattedLookup)) {
                                formattedLookup[report.folder_name] = [];
                            }

                            formattedLookup[report.folder_name].push(report);
                        }
                    }
                    else if (report.folder_name === '') {
                        formattedLookup.push(report);
                    }
                })

                return formattedLookup;
            },
            getTabColor: function (reportMeta, forFont = false) {
                let tab_color = reportMeta.tab_color;
                let tab_color_custom = reportMeta.tab_color_custom;

                if (tab_color === '99') {  // use custom color
                    tab_color = tab_color_custom.replace('#', '');
                }
                else if (tab_color === '') { // no color defined, skip
                    return;
                }

                if (forFont) {
                    // break hex code apart
                    let result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(tab_color);
                    let color = {
                        r: parseInt(result[1], 16),
                        g: parseInt(result[2], 16),
                        b: parseInt(result[3], 16)
                    }

                    // return black/white for light/dark color
                    return ((color.r*0.299 + color.g*0.587 + color.b*0.114) > 186) ? '#000000' : '#ffffff';
                }
                else {
                    tab_color = '#' + tab_color;
                }

                return tab_color;
            }
        }
    })

    // run report SQL query on server
    if (!self.loadedReport.ready) {
        let reportId = self.loadedReport.meta.config.report_id;
        let requestType = self.loadedReport.meta.project_join_info ? 'joinProjectData' : 'runReport';

        $.ajax({
            method: 'POST',
            url: UIOWA_AdminDash.urlLookup.post,
            dataType: 'json',
            data: {
                adMethod: requestType,
                id: reportId,
            },
            timeout: UIOWA_AdminDash.queryTimeout,
            success: function(result) {
                if (result.length > 0) {
                    let columns = [];

                    if (requestType === 'joinProjectData') {
                        columns = Object.keys(result[0]);
                    }
                    else {
                        let columnFormatting = self.loadedReport.meta.column_formatting;

                        if (columnFormatting) {
                            columns = Object.keys(columnFormatting)

                            // columns = $.map(columnFormatting, function (columnMeta, column_name) {
                            //     return columnMeta.dashboard_show_column === '0' ? null : column_name;
                            // });
                        }
                    }

                    $.extend(self.loadedReport, {
                        columns: columns,
                        data: result,
                        ready: true
                    });
                }
                else {
                    self.loadedReport.error = "Zero rows returned."
                }
            }
            ,
            error: function(err) {
                let errorMsg = err.responseText;

                self.loadedReport.error = "Failed to run report. " + errorMsg.substring(
                    errorMsg.lastIndexOf("The error from the database was:"),
                    errorMsg.lastIndexOf("See the server error log for more details")
                );
            }
        })
    }
});