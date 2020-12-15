$.extend(UIOWA_AdminDash, {
    //todo clean up
    applyColumnFormatting: function (data) {
        let self = this;
        let formattedData = $.extend(true,{},data);
        let returnData = [];
        let userColumnMeta = self.loadedReport.meta.columnDetails
        let userColumnVis = self.loadedReport.meta.columnVis
        let formattingReference = self.formattingReference;

        // for each data row
        $.each(formattedData, function (index, row) {
            let formattedRow = row;

            // for each column
            $.each(row, function (column_name, value) {
                let userConfig = userColumnMeta[column_name];

                // todo skip formatting if no matching metadata is found
                // if (userConfig === undefined) {
                //     return true;
                // }

                if (value === null) {
                    return true;
                }

                if (!userColumnVis.dashboard[column_name] && !userColumnVis.childRow[column_name]) {
                    delete row[column_name];
                    return;
                }

                // first pass - convert group_concat result to array
                if (userConfig.group_concat === '1') {
                    value = value.split(userConfig.group_concat_separator);
                }
                else if (userConfig.code_lookup === '1') { // todo make this work with group_concat/links
                    let codeReference = {};

                    if (userConfig.specify_code_lookup === '1') { // Project Status
                        codeReference = formattingReference.status;
                    }
                    else if (userConfig.specify_code_lookup === '2') { // Project Purpose
                        codeReference = formattingReference.purpose;
                    }
                    else if (userConfig.specify_code_lookup === '3') { // Research Purpose
                        codeReference = formattingReference.purpose_other;
                    }
                    // else if (userConfig.specify_code_lookup === '4') { // todo Research Purpose split
                    //     codeReference = formattingReference.research_code_lookup;
                    // }

                    value = codeReference[value];
                }

                if (userConfig.link === '1') {
                    let linkedData = value
                    let lookupColumn = column_name;
                    let lookupCode = 0;
                    let urlPart = '';

                    // pull link values from other column if needed
                    if (userConfig.source_column_name !== '') {
                        lookupColumn = userConfig.source_column_name

                        let source_concat_separator = userColumnMeta[userConfig.source_column_name].group_concat_separator;
                        linkedData = data[index][userConfig.source_column_name];

                        if (Array.isArray(value)) {
                            linkedData = linkedData.split(source_concat_separator);
                        }
                    }

                    if (lookupColumn.includes('_GROUP')) {
                        lookupColumn = lookupColumn.replace('_GROUP', '')
                    }

                    if (userConfig.link_type === '1') { // project_id
                        lookupCode = userConfig.specify_project_link;
                    }
                    else if (userConfig.link_type === '2') { // username
                        lookupCode = userConfig.specify_user_link;
                    }
                    else if (userConfig.link_type === '3') { // survey hash
                        lookupCode = 1;
                        lookupColumn = 'hash'
                    }
                    else if (userConfig.link_type === '4') { // email
                        lookupCode = 1;
                        lookupColumn = 'email'
                    }

                    try {
                        urlPart = formattingReference[lookupColumn][lookupCode - 1];
                    } catch (error) {
                        console.error(error);
                    }

                    urlPart = (userConfig.link_type === '4' ? '' : self.baseRedcapUrl) + urlPart;


                    if (Array.isArray(value)) {
                        $.each(value, function (index, subvalue) {
                            value[index] = (
                                `<a href="${ urlPart + linkedData[index] }" target="_blank">${ subvalue }</a>`);
                        })

                        console.log(value);


                        value = value.join('<br/>');
                    }
                    else {
                        value =
                            `<a href="${ urlPart + linkedData }" target="_blank">${ value }</a>`;
                    }
                }

                formattedRow[column_name] = value;
            })
            returnData.push(formattedRow);
        })

        return returnData;
    },
    initializeDatatable: function () {
        let self = this;

        $('.edit-report').click(function () {
            let self = UIOWA_AdminDash;

            let loadedId = self.loadedReport.meta.config.report_id;
            let url = self.baseRedcapUrl + '/DataEntry/record_home.php?pid=' + self.configPID + '&id=' + loadedId;

            window.open(url, '_blank');
        })

        let columns = $.map(self.loadedReport.columns, function (value) {
            return {data: value};
        });

        let data = self.loadedReport.data;

        // only apply column formatting for sql
        if (self.loadedReport.meta.config.report_type === '1') {
            data = self.applyColumnFormatting(self.loadedReport.data);
        }

        // add column for child row collapse buttons (if at least one column needs it)
        if (self.columnRequires('show_column___2')) {
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


        let table = $('.report-table').DataTable({
            data: data,
            scrollX: true,
            // scrollY: true,
            stateSave: true,
            colReorder: true,
            fixedHeader: {
                header: true,
                headerOffset: $('#redcap-home-navbar-collapse').height()
            },
            columns: columns,
            order: [[1, 'asc']]
        });

        let buttons = new $.fn.dataTable.Buttons(table, {
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                {
                    text: 'JSON',
                    action: function ( e, dt, button, config ) {
                        var data = dt.buttons.exportData();

                        $.fn.dataTable.fileSave(
                            new Blob( [ JSON.stringify( data ) ] ),
                            'Export.json'
                        );
                    }
                }
            ]
        }).container().appendTo($('#buttons'));

        let visButtons = new $.fn.dataTable.Buttons(table, {
            buttons: [
                {
                    text: 'Show/Hide Columns',
                    extend: 'colvis',
                    columns: ':not(.noVis)'
                }
            ]
        }).container().appendTo($('#visButtons'));

        $('.report-table tbody').on('click', 'td.details-control', function () {
            var tr = $(this).closest('tr');
            var row = table.row( tr );

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
    columnRequires: function (field) {
        let columnDetails = this.loadedReport.meta.columnDetails;
        let required = false;

        $.each(columnDetails, function (column_name, value) {
            if (value[field] === '1') {
                required = true;
            }
        });

        return required;
    },
    formatChildRow: function( d ) {
        let columnDetails = this.loadedReport.meta.columnDetails;
        let htmlRows = '';

        $.each(columnDetails, function(column_name, value) {

            if (value.show_column___2 === '1') {
                htmlRows = htmlRows.concat('<tr>' +
                    '<td>' + (value.display_header !== '' ? value.display_header : column_name) + '</td>' +
                    '<td>' + d[column_name] + '</td>' +
                    '</tr>');
            }
        })

    // `d` is the original data object for the row
    return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">'+
        htmlRows+
        '</table>';
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
                $('#reportContent').show();
                self.initializeDatatable();
            })
        },
        methods: {
            isActiveReport: function (id) {
                let loadedId = self.loadedReport.meta.config.report_id;

                return id === loadedId ? 'active' : '';
            },
            getReportIcon: function (icon) {

                return icon !== '' ? 'fas fa-' + icon : 'fas fa-question';
            },
            getDisplayHeader: function (column) {
                if (typeof self.loadedReport.meta.columnDetails !== 'undefined') {
                    let columnDetails = self.loadedReport.meta.columnDetails[column];
                    column = columnDetails.display_header !== '' ? columnDetails.display_header : column;
                }

                return column;
            }
        }
    })

    // run report SQL query on server
    if (!self.noReportId) {
        let reportId = self.loadedReport.meta.config.report_id;
        let requestType = self.loadedReport.meta.config.report_type === '2' ? 'joinProjectData' : 'runReport';

        $.ajax({
            url: self.postUrl + '&method=' + requestType,
            type: 'POST',
            data: {
                params: reportId
            },
            success: function(result) {

                console.log(result);

                result = JSON.parse(result);


                if (result.length > 0) {
                    let columns = [];

                    if (requestType === 'joinProjectData') {
                        columns = Object.keys(result[0]);
                    }
                    else {
                        let userColumnVis = self.loadedReport.meta.columnVis.dashboard;

                        columns = $.map(userColumnVis, function (value, key) {
                            return !value ? null : key;
                        });
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
        })
    }
});