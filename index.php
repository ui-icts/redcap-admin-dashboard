<?php
/**
 * @file index.php
 * @author Fred R. McClurg, University of Iowa
 * @date July 24, 2014
 * @version 1.2
 *
 * @brief An application that displays project and Principal Investigator information.
 */

// set error reporting for debugging
require_once('lib/errorReporting.php');

// handy html utilities
require_once('lib/htmlUtilities.php');

// handy html utilities
require_once('lib/redcapUtilities.php');

// define all the SQL statements that are used
require_once('lib/variableLookup.php');

// connect to the REDCap database
require_once('../redcap_connect.php');

// only allow super users to view this information
if (!SUPER_USER) die("Access denied! Only super users can access this page.");

// start the stopwatch ...
ElapsedTime();

// define variables
$title = "REDCap Admin Dashboard";
$projectTable = "projectTable";

// Display the header
$HtmlPage = new HtmlPage();
$HtmlPage->PrintHeaderExt();

?>

<!-- tablesorter -->
<script src="js/tablesorter/jquery.tablesorter.min.js"></script>
<script src="js/tablesorter/jquery.tablesorter.widgets.min.js"></script>
<script src="js/tablesorter/widgets/widget-pager.min.js"></script>

<!-- tablesorter CSS-->
<link href="css/tablesorter/theme.blue.min.css" rel="stylesheet">
<link href="css/tablesorter/jquery.tablesorter.pager.min.css" rel="stylesheet">

<!-- local CSS-->
<link rel="stylesheet" href="css/styles.css" type="text/css" />

<!-- Font Awesome fonts (for tab icons)-->
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">


<script>
   // set the window title
   document.title = "<?= $title ?>";

   // sort table when document is loaded
   $(document).ready(function(){
      $("#<?= $projectTable ?>")
      .tablesorter({
         theme: 'blue',
         widthFixed: true,
         usNumberFormat : false,
         sortReset      : false,
         sortRestart    : true,
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
            pager_fixedHeight: true,

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
            pager_customAjaxUrl: function(table, url) { return url; },

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
            pager_ajaxProcessing: function(ajax){ return [ 0, [], null ]; },

            // css class names that are added
            pager_css: {
               container   : 'tablesorter-pager',    // class added to make included pager.css file work
               errorRow    : 'tablesorter-errorRow', // error information row (don't include period at beginning); styled in theme file
               disabled    : 'disabled'              // class added to arrows @ extremes (i.e. prev/first arrows "disabled" on first page)
            },

            // jQuery selectors
            pager_selectors: {
               container   : '.pager',       // target the pager markup (wrapper)
               first       : '.first',       // go to first page arrow
               prev        : '.prev',        // previous page arrow
               next        : '.next',        // next page arrow
               last        : '.last',        // go to last page arrow
               gotoPage    : '.gotoPage',    // go to page selector - select dropdown that sets the current page
               pageDisplay : '.pagedisplay', // location of where the "output" is displayed
               pageSize    : '.pagesize'     // page size selector - select dropdown that sets the "size" option
            }

         }

      })

      // bind to pager events
      // *********************
          .bind('pagerChange pagerComplete pagerInitialized pageMoved', function(e, c){
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
      $('button:contains(Add)').click(function(){
         // add two rows of random data!
         r = row.replace(/\{[gijmr]\}/g, function(m){
            return {
               '{i}' : num + 1,
               '{j}' : num + 2,
               '{r}' : Math.round(Math.random() * 100),
               '{g}' : Math.random() > 0.5 ? 'male' : 'female',
               '{m}' : Math.random() > 0.5 ? 'Mathematics' : 'Languages'
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
      $table.delegate('button.remove', 'click' ,function(){
         // disabling the pager will restore all table rows
         // $table.trigger('disablePager');
         // remove chosen row
         $(this).closest('tr').remove();
         // restore pager
         // $table.trigger('enablePager');
         $table.trigger('update');
         return false;
      });

      // Destroy pager / Restore pager
      // **************
      $('button:contains(Destroy)').click(function(){
         // Exterminate, annhilate, destroy! http://www.youtube.com/watch?v=LOqn8FxuyFs
         var $t = $(this);
         if (/Destroy/.test( $t.text() )){
            $table.trigger('destroyPager');
            $t.text('Restore Pager');
         } else {
            $('table').trigger('applyWidgetId', 'pager');
            $t.text('Destroy Pager');
         }
         return false;
      });

      // Disable / Enable
      // **************
      $('.toggle').click(function(){
         var mode = /Disable/.test( $(this).text() );
         // using disablePager or enablePager
         $table.trigger( (mode ? 'disable' : 'enable') + 'Pager');
         $(this).text( (mode ? 'Enable' : 'Disable') + 'Pager');
         return false;
      });
      $table.bind('pagerChange', function(){
         // pager automatically enables when table is sorted.
         $('.toggle').text('Disable Pager');
      });

      // clear storage (page & size)
      $('.clear-pager-data').click(function(){
         // clears user set page & size from local storage, so on page
         // reload the page & size resets to the original settings
         $.tablesorter.storage( $table, 'tablesorter-pager', '' );
      });

      // go to page 1 showing 10 rows
      $('.goto').click(function(){
         // triggering "pageAndSize" without parameters will reset the
         // pager to page 1 and the original set size (10 by default)
         // $('table').trigger('pageAndSize')
         $table.trigger('pageAndSize', [1, 10]);
      });

   });
</script>

<h2 style="text-align: center;
    color: #800000;
    font-weight: bold;">
   <?= $title ?>
</h2>

<p />

<?php
   // display navigation tabs
   require_once('include/navigationTabs.php');
?>

<p />

<?php
   $pageInfo = GetPageDetails( $_REQUEST['tab'] );

   $csvFileName = sprintf( "%s.%s.csv",
                           $pageInfo['file'],
                           date( "Y-m-d_His" ) );
?>

<div style="text-align: right; width: 100%">
   <a href="downloadCsvViaSql.php?file=<?= $csvFileName; ?>&tab=<?= $_REQUEST['tab'] ?>"
      class="btn btn-default btn-lg">
      <span class="fa fa-download"></span>&nbsp;
      Download CSV File</a>
</div>

<p />

<h3 style="text-align: center">
   <?= $pageInfo['subtitle'] ?>
</h3>

<h5 style="text-align: center">
   <?= $pageInfo['summary'] ?>
</h5>

<!-- pager -->
<div id="pager" class="pager">
   <form>
      <img src="css/tablesorter/images/icons/first.png" class="first"/>
      <img src="css/tablesorter/images/icons/prev.png" class="prev"/>
      <!-- the "pagedisplay" can be any element, including an input -->
      <span class="pagedisplay" data-pager-output-filtered="{startRow:input} &ndash; {endRow} / {filteredRows} of {totalRows} total rows"></span>
      <img src="css/tablesorter/images/icons/next.png" class="next"/>
      <img src="css/tablesorter/images/icons/last.png" class="last"/>
      <select class="pagesize">
         <option value="25">25</option>
         <option value="50">50</option>
         <option value="100">100</option>
         <option value="all">All Rows</option>
      </select>
   </form>
</div>

<?php
   // execute the SQL statement
   $result = mysqli_query($conn,  $pageInfo['sql'] );

   if ( ! $result )  // sql failed
   {
      $message = printf( "Line: %d<br />
                          Could not execute SQL:
                          <pre>%s</pre> <br />
                          Error #: %d<br />
                          Error Msg: %s",
                          __LINE__,
                          $sql,
                          mysqli_errno( $conn ),
                          mysqli_error( $conn ) );
      die( $message );
   }

   $redcapProjects = GetRedcapProjectNames($conn);
   $isFirstRow = TRUE;

   while ( $row = mysqli_fetch_assoc( $result ) )
   {
      if ( $isFirstRow )
      {
         // use column aliases for column headers
         $headers = array_keys( $row );

         // print table header
         PrintTableHeader( $projectTable, $headers );
         printf( "   <tbody>\n" );

         $isFirstRow = FALSE;  // toggle flag
      }

      $webData = WebifyDataRow( $row, $redcapProjects );
      PrintTableRow( $webData );
   }

   printf( "   </tbody>\n" );
   printf( "</table>\n" );  // <table> created by PrintTableHeader
   printf( "<p /> <br />\n" );

   $load = sys_getloadavg();
   printf( "<div id='elapsedTime'>
            Returned $result->num_rows rows<br />
            Elapsed Execution Time: %s<br />
            System load avg last minute: %d%%<br />
            System load avg last 5 mins: %d%%<br />
            System load avg last 15 min: %d%%</div>",
            ElapsedTime(), $load[0] * 100, $load[1] * 100, $load[2] * 100 );

   // Display the footer
   $HtmlPage->PrintFooterExt();
?>
