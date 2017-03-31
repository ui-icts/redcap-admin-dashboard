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
<link href="css/tablesorter/theme.blue.min.css" rel="stylesheet">
<script src="js/jquery.tablesorter.min.js"></script>
<script src="js/jquery.tablesorter.widgets.min.js"></script>

<!-- local CSS-->
<link rel="stylesheet" href="css/styles.css" type="text/css" />

<!-- Font Awesome fonts (for tab icons)-->
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">


<script>
   // set the window title
   document.title = "<?= $title ?>";

   // sort table when document is loaded
   $(document).ready(function(){
      $("#<?= $projectTable ?>").tablesorter({
         theme : 'blue',
         widgets        : ['zebra', 'resizable', 'stickyHeaders'],
         usNumberFormat : false,
         sortReset      : false,
         sortRestart    : true
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
            Elapsed Execution Time: %s<br />
            System load avg last minute: %d%%<br />
            System load avg last 5 mins: %d%%<br />
            System load avg last 15 min: %d%%</div>",
            ElapsedTime(), $load[0] * 100, $load[1] * 100, $load[2] * 100 );

   // Display the footer
   $HtmlPage->PrintFooterExt();
?>
