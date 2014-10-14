<?php
/**
 * @file index.php
 * @author Fred R. McClurg, University of Iowa
 * @date July 24, 2014
 * @version 1.1
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

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>

<!-- tablesorter plugin -->
<script src="js/jquery_tablesorter/jquery.tablesorter.js"></script>

<!-- tablesorter CSS -->
<link rel="stylesheet" href="js/jquery_tablesorter/themes/blue/style.css" type="text/css" media="print, projection, screen" />

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">

<!-- Optional theme -->
<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">

<!-- local customizations CSS -->
<link rel="stylesheet" href="css/styles.css" type="text/css" />

<!-- Latest compiled and minified JavaScript -->
<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>

<!-- Font Awesome fonts -->
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">

<script>
   // set the window title
   document.title = "<?= $title ?>";

   // sort table when document is loaded
   $(document).ready(function() {
         $("#<?= $projectTable ?>").tablesorter({
            widgets: ['zebra']
         });
      }
   );
</script>

<h3 style="text-align: center;
    color: #800000;
    font-weight: bold;">
   <?= $title ?>
</h3>

<p />

<?php
   // display navigation tabs
   require_once( 'include/navigationTabs.php' );
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
      <!-- <span class="glyphicon glyphicon-download"></span> -->
      <span class="fa fa-download"></span>&nbsp;
      Download CSV File</a>
</div>

<p />

<h4 style="text-align: center;
    font-weight: bold;">
   <?= $pageInfo['subtitle'] ?>
</h4>

<p>
   <?= $pageInfo['summary'] ?>
</p>

<?php
   // execute the SQL statement
   // $result = mysqli_query( $conn, $sql );
   $result = mysql_query( $pageInfo['sql'] );

   if ( ! $result )  // sql failed
   {
      $message = printf( "Line: %d<br />
                          Could not execute SQL:
                          <pre>%s</pre> <br />
                          Error #: %d<br />
                          Error Msg: %s",
                          __LINE__,
                          $sql,
                          // mysqli_errno( $conn ),
                          // mysqli_error( $conn ) );
                          mysql_errno( $conn ),
                          mysql_error( $conn ) );
      die( $message );
   }

   $redcapProjects = GetRedcapProjectNames();
   $isFirstRow = TRUE;

   // while ( $row = mysqli_fetch_assoc( $result ) )
   while ( $dbData = mysql_fetch_assoc( $result ) )
   {
      if ( $isFirstRow )
      {
         // use column aliases for column headers
         $headers = array_keys( $dbData );

         // print table header
         PrintTableHeader( $projectTable, $headers );
         printf( "   <tbody>\n" );

         $isFirstRow = FALSE;  // toggle flag
      }

      $webData = WebifyDataRow( $dbData, $redcapProjects );
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
