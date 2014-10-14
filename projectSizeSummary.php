<?php
/**
 * @file projectSizeSummary.php
 * @author Fred R. McClurg, University of Iowa
 * @date August 28, 2014
 * @version 1.1
 *
 * @brief An application that displays project size and summary information by combining three SQL select statements.
 */

// set error reporting for debugging
require_once('lib/errorReporting.php');

// handy html utilities
require_once('lib/htmlUtilities.php');

// handy html utilities
require_once('lib/redcapUtilities.php');

// connect to the REDCap database
require_once('../redcap_connect.php');

// only allow super users to view this information
if (!SUPER_USER) die("Access denied! Only super users can access this page.");

// start the stopwatch ...
ElapsedTime();

$title = "REDCap Admin Dashboard";
$projectTable = "projectTable";

/**
 * @brief For reference: short version for capturing number
 *        of fields in a project
 * SELECT
 *    project_id,
 *    COUNT( project_id ) AS 'Field Count'
 * FROM redcap_metadata
 * -- WHERE project_id = 15
 * GROUP BY project_id
 * ORDER BY project_id";
 */

// capture the number of fields in a project
$fieldCountSql = "
SELECT
   project.project_id AS 'PID',
   project.app_title AS 'Project Name',
   COUNT( meta.project_id ) AS 'Fields',
      CAST( CASE status
         WHEN 0 THEN 'Development'
         WHEN 1 THEN 'Production'
         WHEN 2 THEN 'Inactive'
         WHEN 3 THEN 'Archive'
         ELSE status
      END AS CHAR(50) ) AS 'Category',
   project.last_logged_event AS 'Last Event Date',
   DATEDIFF( now(), project.last_logged_event ) AS 'Days Ago'
FROM redcap_projects AS project,
     redcap_metadata AS meta
WHERE
   project.project_id = meta.project_id
GROUP BY meta.project_id
ORDER BY project.project_id";

// count number of records per project
$recordCountSql = "
SELECT project_id AS 'PID',
       COUNT( DISTINCT record ) AS 'Records'
       -- GROUP_CONCAT( record ORDER BY record ) AS 'Records'
FROM
(
   SELECT project_id,
          record
   FROM redcap_data
   GROUP BY project_id, record
) AS a
GROUP BY project_id
ORDER BY project_id";

// count number of records per project
$userCountSql = "
SELECT
   project.project_id AS 'PID',
   -- users.username AS 'HawkID',
   COUNT( project.project_id ) AS 'Users'
   -- GROUP_CONCAT( users.username ) AS 'Project Users'
FROM redcap_projects AS project,
     redcap_user_rights AS users
WHERE
   project.project_id = users.project_id
GROUP BY project.project_id
ORDER BY project.project_id";

/*
 * The following was an attempt to combine the first two SQL statements above together
 * into single statement.  When executed, this SQL statement consumes all the temp space
 * and never returns data.
 */

/*
SELECT
   project.project_id AS 'PID',
   project.app_title AS 'ProjectName',
   COUNT( meta.project_id ) AS 'FieldCount',
      CAST( CASE status
         WHEN 0 THEN 'Development'
         WHEN 1 THEN 'Production'
         WHEN 2 THEN 'Inactive'
         WHEN 3 THEN 'Archive'
         ELSE status
      END AS CHAR(50) ) AS 'Category',
         COUNT( DISTINCT redcap_data.record ) AS 'RecordCount'
FROM redcap_projects AS project,
     redcap_metadata AS meta,
       redcap_data
WHERE
   project.project_id = meta.project_id
   AND redcap_data.project_id = project.project_id
GROUP BY  project.project_id, project.app_title, meta.project_id , redcap_data.record
ORDER BY project.project_id,project.app_title
 */

// Project Size Summary
$projectTitle = "Project Size Summary";
$fileName = "projectSizeSummary";
$description = "Listing of all REDCap projects and a count regarding the number of records
                (including orphaned data), number of fields, and number of users.  In addition,
                it displays the date the last event was performed on the database and the
                number of days elapsed since that date.";

// Display the REDCap header
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
   $csvFileName = sprintf( "%s.%s.csv",
                           $fileName,
                           date( "Y-m-d_His" ) );
?>

<?php
/*
<p style="text-align: right;">
   <a href="downloadCsvViaSql.php?file=<?= $csvFileName; ?>&sql=<?= urlencode( $sql ); ?>"
      class="btn btn-default btn-lg">
      <span class="glyphicon glyphicon-download"></span>
      Download CSV File</a>
</p>

<p />
*/
?>

<h4 style="text-align: center;
    font-weight: bold;">
   <?= $projectTitle ?>
</h4>

<p>
   <?= $description ?>
</p>

<?php
   // execute the SQL statement
   // $result = mysqli_query( $conn, $sql );
   $result = mysql_query( $fieldCountSql );

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

   // while ( $row = mysqli_fetch_assoc( $result ) )
   while ( $dbData = mysql_fetch_assoc( $result ) )
   {
      $pid = $dbData['PID'];

      foreach ( $dbData as $key => $value )
      {
         $combinedData[$pid][$key] = $value;
      }
   }


   // execute the SQL statement
   // $result = mysqli_query( $conn, $sql );
   $result = mysql_query( $recordCountSql );

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

   // while ( $row = mysqli_fetch_assoc( $result ) )
   while ( $dbData = mysql_fetch_assoc( $result ) )
   {
      $pid = $dbData['PID'];

      foreach ( $dbData as $key => $value )
      {
         $combinedData[$pid][$key] = $value;
      }
   }


   // execute the SQL statement
   // $result = mysqli_query( $conn, $sql );
   $result = mysql_query( $userCountSql );

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

   // while ( $row = mysqli_fetch_assoc( $result ) )
   while ( $dbData = mysql_fetch_assoc( $result ) )
   {
      $pid = $dbData['PID'];

      foreach ( $dbData as $key => $value )
      {
         $combinedData[$pid][$key] = $value;
      }
   }

   // printf( "<pre>%s</pre>\n", var_export( $combinedData, true ) );

   // $redcapProjects = GetRedcapProjectNames();  // not needed for table
   $redcapProjects = null;

   // must match SQL field aliases
   // $headers = array( "PID", "Project Name", "Records", "Fields", "Users", "Category",
   $headers = array( "PID", "Project Name", "Records", "Fields", "Users", "Category",
                     "Last Event Date", "Days Ago" );

   // print table header
   PrintTableHeader( $projectTable, $headers );
   printf( "   <tbody>\n" );

   foreach ( $combinedData as $pid => $rowHash )
   {
      if ( ! isset( $rowHash['Project Name'] ) )
      {
         // don't consider any "projects" that do not have a name
         break;
      }

      // reset value
      $dbData = array();

      foreach ( $headers as $title )
      {
         $dbData[$title] = $rowHash[$title];
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

   // Display the REDCap footer
   $HtmlPage->PrintFooterExt();
?>
