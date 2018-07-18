// tablesorter js
(function($, window, document) {
    // sort table when document is loaded
    $(document).ready(function () {
        $("#reportTable")
            .tablesorter({
                theme: 'blue',
                widthFixed: true,
                usNumberFormat: false,
                sortReset: false,
                sortRestart: true,
                widgets: ['zebra', 'filter', 'resizable', 'stickyHeaders', 'pager', 'output'],

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
                    output_ignoreColumns : '',         // columns to ignore [0, 1,... ] (zero-based index)
                    output_hiddenColumns : false,       // include hidden columns in the output
                    output_includeFooter : true,        // include footer rows in the output
                    output_includeHeader : true,        // include header rows in the output
                    output_headerRows    : false,       // output all header rows (if multiple rows)
                    output_dataAttrib    : 'data-name', // data-attribute containing alternate cell text
                    output_delivery      : 'd',         // (p)opup, (d)ownload
                    output_saveRows      : 'a',         // (a)ll, (v)isible, (f)iltered, jQuery filter selector (string only) or filter function
                    output_duplicateSpans: true,        // duplicate output data in tbody colspan/rowspan
                    output_replaceQuote  : '\u201c;',   // change quote to left double quote
                    output_includeHTML   : false,        // output includes all cell HTML (except the header cells)
                    output_trimSpaces    : true,       // remove extra white-space characters from beginning & end
                    output_wrapQuotes    : false,       // wrap every cell output in quotes
                    output_popupStyle    : 'width=580,height=310',
                    output_saveFileName  : csvFileName,
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
                        data = '\ufeff' + data;
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

            })

            // bind to pager events
            // *********************
            .bind('pagerChange pagerComplete pagerInitialized pageMoved', function (e, c) {
                var p = c.pager, // NEW with the widget... it returns config, instead of config.pager
                    msg = '"</span> event triggered, ' + (e.type === 'pagerChange' ? 'going to' : 'now on') +
                        ' page <span class="typ">' + (p.page + 1) + '/' + p.totalPages + '</span>';
                $('#display')
                    .append('<li><span class="str">"' + e.type + msg + '</li>')
                    .find('li:first').remove();
            })

        // Add two new rows using the "addRows" method
        // the "update" method doesn't work here because not all rows are
        // present in the table when the pager is applied ("removeRows" is false)
        // ***********************************************************************
        var r, $row, num = 50,
            row = '<tr><td>Student{i}</td><td>{m}</td><td>{g}</td><td>{r}</td><td>{r}</td><td>{r}</td><td>{r}</td><td><button type="button" class="remove" title="Remove this row">X</button></td></tr>' +
                '<tr><td>Student{j}</td><td>{m}</td><td>{g}</td><td>{r}</td><td>{r}</td><td>{r}</td><td>{r}</td><td><button type="button" class="remove" title="Remove this row">X</button></td></tr>';
        $('button:contains(Add)').click(function () {
            // add two rows of random data!
            r = row.replace(/\{[gijmr]\}/g, function (m) {
                return {
                    '{i}': num + 1,
                    '{j}': num + 2,
                    '{r}': Math.round(Math.random() * 100),
                    '{g}': Math.random() > 0.5 ? 'male' : 'female',
                    '{m}': Math.random() > 0.5 ? 'Mathematics' : 'Languages'
                }[m];
            });
            num = num + 2;
            $row = $(r);
            $table
                .find('tbody').append($row)
                .trigger('addRows', [$row]);
            return false;
        });

        // Disable / Enable
        // **************
        $('.toggle').click(function () {
            var mode = /Disable/.test($(this).text());
            // using disablePager or enablePager
            $table.trigger((mode ? 'disable' : 'enable') + 'Pager');
            $(this).text((mode ? 'Enable' : 'Disable') + 'Pager');
            return false;
        });

        // clear storage (page & size)
        $('.clear-pager-data').click(function () {
            // clears user set page & size from local storage, so on page
            // reload the page & size resets to the original settings
            $.tablesorter.storage($table, 'tablesorter-pager', '');
        });

        // go to page 1 showing 10 rows
        $('.goto').click(function () {
            // triggering "pageAndSize" without parameters will reset the
            // pager to page 1 and the original set size (10 by default)
            // $('table').trigger('pageAndSize')
            $table.trigger('pageAndSize', [1, 10]);
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
            if (outputType == 'Download') {
                $this.find('.download').html('<span class="fas fa-download"></span><b> Export ' + filetype.toUpperCase() + ' File</b>');
            }
            else {
                $this.find('.download').html('<span class="far fa-window-maximize"></span><b> Open ' + filetype.toUpperCase() + ' Popup</b>');
            }
            return false;
        });
        $this.find('.output-type').click(function() {
            var outputType = $(this)[0].innerText;
            var filename = $this.find('.output-filename');
            var txt = $($this.find('.output-separator.active')).html();
            var filetype = (txt === 'json' || txt === 'array') ? 'js' :
                txt === ',' ? 'csv' : 'txt';
            if (outputType == 'Download') {
                $this.find('.download').html('<span class="fas fa-download"></span><b> Export ' + filetype.toUpperCase() + ' File</b>');
                $this.find('.filename-field-display').removeClass('hidden');
            }
            else {
                $this.find('.download').html('<span class="far fa-window-maximize"></span><b> Open ' + filetype.toUpperCase() + ' Popup</b>');
                $this.find('.filename-field-display').addClass('hidden');
            }
            //return false;
        });
        // clicking the download button; all you really need is to
        // trigger an "output" event on the table
        $this.find('.download').click(function() {
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
                splitFilename.splice(-1, 0, renderDatetime);
                wo.output_saveFileName = splitFilename.join('.');
            }
            else {
                wo.output_saveFileName = filename;
            }

            $table.trigger('outputTable');
            return false;
        });
    });

}(window.jQuery, window, document));

var UIOWA_AdminDash = {};

// generate pie chart with c3.js
UIOWA_AdminDash.createPieChart = function(json, title, chartID) {
    var chart = c3.generate({
        data: {
            json: json,
            type: 'pie'
        },
        title: {
            text: title
        },
        legend: {
            position: 'inset',
            width: '50%',
            inset: {
                anchor: 'top-right',
                x: 100,
                y: 0
            }
        },
        tooltip: {
            show: false
        },
        bindto: "#" + chartID
    });
};

// flatten REDCap data json into counts
UIOWA_AdminDash.getCountsFromJson = function(json, column) {
    var countList = {};

    for (var i = 0; i < json.length; i++) {
        var currentValue = json[i][column] ? json[i][column] : "N/A"; // Value to be tallied

        if (!(currentValue in countList)) {
            countList[currentValue] = 1;
        }
        else {
            countList[currentValue]++;
        }
    }
    return countList;
};