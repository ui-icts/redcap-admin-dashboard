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
                widgets: ['zebra', 'filter', 'resizable', 'stickyHeaders', 'pager'],

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
                    }

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

        // Delete a row
        // *************
        //$table.delegate('button.remove', 'click', function () {
        //    // disabling the pager will restore all table rows
        //    // $table.trigger('disablePager');
        //    // remove chosen row
        //    $(this).closest('tr').remove();
        //    // restore pager
        //    // $table.trigger('enablePager');
        //    $table.trigger('update');
        //    return false;
        //});

        // Destroy pager / Restore pager
        // **************
        //$('button:contains(Destroy)').click(function () {
        //    // Exterminate, annhilate, destroy! http://www.youtube.com/watch?v=LOqn8FxuyFs
        //    var $t = $(this);
        //    if (/Destroy/.test($t.text())) {
        //        $table.trigger('destroyPager');
        //        $t.text('Restore Pager');
        //    } else {
        //        $('table').trigger('applyWidgetId', 'pager');
        //        $t.text('Destroy Pager');
        //    }
        //    return false;
        //});

        // Disable / Enable
        // **************
        $('.toggle').click(function () {
            var mode = /Disable/.test($(this).text());
            // using disablePager or enablePager
            $table.trigger((mode ? 'disable' : 'enable') + 'Pager');
            $(this).text((mode ? 'Enable' : 'Disable') + 'Pager');
            return false;
        });
        //$table.bind('pagerChange', function () {
        //    // pager automatically enables when table is sorted.
        //    $('.toggle').text('Disable Pager');
        //});

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