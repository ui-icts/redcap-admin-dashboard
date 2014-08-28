<?php
/**
 * @file index.php
 * @author Fred R. McClurg, University of Iowa
 * @date July 24, 2014
 * @version 1.0
 */

/**
 * @brief An application that displays project and Principal Investigator information.
 *
 * @remarks An example of a bookmark using this plugin might be the following:
 *             https://www-dev.icts.uiowa.edu/redcap/redirector/index.php?url=https%3A%2F%2Fredcap.icts.uiowa.edu%2Fredcap%2Fredcap_v5.10.0%2FDataEntry%2Findex.php%3Fpid%3D20%26page%3Ddemographics
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

$title = "REDCap Admin Dashboard";
$projectTable = "projectTable";

// capture the number of fields in a project
/*
$sql = "
SELECT
   project_id,
   COUNT( project_id ) AS 'Field Count'
FROM redcap_metadata
-- WHERE project_id = 15
GROUP BY project_id
ORDER BY project_id";
*/

// more robust number of fields in a project
/*
$sql = "
SELECT
   project.project_id AS 'PID',
   project.app_title AS 'Project Name',
   COUNT( meta.project_id ) AS 'Field Count',
      CAST( CASE status
         WHEN 0 THEN 'Development'
         WHEN 1 THEN 'Production'
         WHEN 2 THEN 'Inactive'
         WHEN 3 THEN 'Archive'
         ELSE status
      END AS CHAR(50) ) AS 'Category',
   project.last_logged_event AS 'Last Event Date',
   DATEDIFF( now(), project.last_logged_event ) AS 'Days Since Event'
FROM redcap_projects AS project,
     redcap_metadata AS meta
WHERE
   project.project_id = meta.project_id
GROUP BY meta.project_id
ORDER BY project.project_id";
*/

// count number of records per project
/*
SELECT project_id AS 'PID',
       COUNT( DISTINCT record ) AS 'Record Count',
       GROUP_CONCAT( record ORDER BY record ) AS 'Records'
FROM
(
   SELECT redcap_data.project_id,
          redcap_data.record
   FROM redcap_data
   GROUP BY redcap_data.project_id, redcap_data.record
) AS a
GROUP BY project_id
ORDER BY project_id
 */

if ( ! isset( $_REQUEST['tab'] ) )  // Project by Owners
{
   $projectTitle = "Project by Owner";
   $fileName = "projectByOwner";
   $description = "Listing of REDCap Projects and its
                   associated owner (original creator).
                   The listing also includes the PI and
                   the users that have access to the project.";

   $sql = "
   SELECT
      project.project_id AS 'PID',
      TRIM( project.app_title ) AS 'Project Name',
      CAST( CASE status
         WHEN 0 THEN 'Development'
         WHEN 1 THEN 'Production'
         WHEN 2 THEN 'Inactive'
         WHEN 3 THEN 'Archive'
         ELSE status
      END AS CHAR(50) ) AS 'Category',
      CAST( CASE purpose
         WHEN 0 THEN 'Practice'
         WHEN 1 THEN 'Operational Support'
         WHEN 2 THEN 'Research'
         WHEN 3 THEN 'Quality Improvement'
         WHEN 4 THEN 'Other'
         ELSE purpose
      END AS CHAR(50) ) AS 'Purpose',
         -- 0 = Basic or bench research
         -- 1 = Clinical research study or trial
         -- 2 = Translational research 1 (applying discoveries to the development of trials and studies in humans)
         -- 3 = Translational research 2 (enhancing adoption of research findings and best practices into the community)
         -- 4 = Behavioral or psychosocial research study
         -- 5 = Epidemiology
         -- 6 = Repository (developing a data or specimen repository for future use by investigators)
         -- 7 = Other
      purpose_other AS 'Purpose Specified',
      CONCAT( project_pi_lastname, ', ', project_pi_firstname, ' ', project_pi_mi ) AS 'PI Name',
      project_pi_email AS 'PI Email',
      project_irb_number AS 'IRB Number',
      info.username AS 'Owner HawkID',
      CONCAT( user_lastname, ', ', user_firstname ) AS 'Owner Name',
      info.user_email AS 'Owner Email',
      GROUP_CONCAT(user.username) AS 'Project Users',
      COUNT(user.username) AS 'User Count'
   FROM redcap_projects AS project,
        redcap_user_rights AS user,
        redcap_user_information AS info
   WHERE project.project_id = user.project_id AND
         project.created_by = info.ui_id
   GROUP BY project.project_id
   ORDER BY TRIM( project.app_title )";
}
elseif ( $_REQUEST['tab'] == 1 )  // Users by Project
{
   $projectTitle = "Users by Project";
   $fileName = "usersByProject";
   $description = "Listing of REDCap users and the projects
                   of which they are members.";

   $sql = "
   SELECT
      info.username AS 'HawkID',
      CONCAT( info.user_lastname, ', ', info.user_firstname ) AS 'User Name',
      info.user_email AS 'User Email',
      GROUP_CONCAT( CAST( project.project_id AS CHAR(50) ) SEPARATOR ', ' ) AS 'Project Titles',
      COUNT( project.project_id ) AS 'Projects Total'
      -- project.app_title AS 'Project Title'
   FROM redcap_user_information AS info,
        redcap_projects AS project,
        redcap_user_rights AS access
   WHERE info.username = access.username AND
         access.project_id = project.project_id
   GROUP BY info.ui_id
   ORDER BY info.user_lastname, info.user_firstname, info.username";
}
elseif ( $_REQUEST['tab'] == 2 )  // Research Projects
{
   $projectTitle = "Research Projects";
   $fileName = "researchProjects";
   $description = "Listing of only REDCap Projects that are
                   identified as Research Projects.  Also
                   includes the associated Principal
                   Investigator (PIs) and project owner (creator).";

   $sql = "
SELECT
   project_id AS PID,
   app_title AS 'Project Name',
   -- 0 = development  1 = production  3 = archive
   CAST( CASE status
      WHEN 0 THEN 'Development'
      WHEN 1 THEN 'Production'
      WHEN 2 THEN 'Inactive'
      WHEN 3 THEN 'Archive'
      ELSE status
   END AS CHAR(50) ) AS 'Category',
   -- 0 = Practice / Just for fun
   -- 1 = Operational Support
   -- 2 = Research
   -- 3 = Quality Improvement
   -- 4 = Other
   purpose_other AS 'Purpose Specified',
                   -- 0 = Basic or bench research
                   -- 1 = Clinical research study or trial
                   -- 2 = Translational research 1 (applying discoveries to the development of trials and studies in humans)
                   -- 3 = Translational research 2 (enhancing adoption of research findings and best practices into the community)
                   -- 4 = Behavioral or psychosocial research study
                   -- 5 = Epidemiology
                   -- 6 = Repository (developing a data or specimen repository for future use by investigators)
                   -- 7 = Other
   CONCAT( project_pi_lastname, ', ', project_pi_firstname, ' ', project_pi_mi ) AS 'PI Name',
   project_pi_email AS 'PI Email',
   project_irb_number AS 'IRB Number',
   CONCAT( user_lastname, ', ', user_firstname ) AS 'Owner Name',
   user_email AS 'Owner Email',  -- FROM redcap_user_information
   username AS 'Owner HawkID'  -- FROM redcap_user_information
   FROM redcap_projects, redcap_user_information
   WHERE ui_id = created_by AND
         purpose = 2  -- 'Research'
   ORDER BY app_title";
}
elseif ( $_REQUEST['tab'] == 3 )  // Owner Project Summary
{
   $projectTitle = "Owner Project Summary";
   $fileName = "ownerProjectSummary";
   $description = "Listing of REDCap owners, their
                   associated projects and a sum total of
                   projects.";

   $sql = "
   SELECT info.username AS 'Owner HawkID',
      CONCAT( info.user_lastname, ', ', info.user_firstname ) AS 'Owner Name',
      info.user_email AS 'Owner Email',
      GROUP_CONCAT( CAST( project.project_id AS CHAR(50) ) SEPARATOR ', ' ) AS 'Project Titles',
      COUNT( info.ui_id ) AS 'Projects Owned'
   FROM redcap_projects AS project, redcap_user_information AS info
   WHERE project.created_by = info.ui_id
   GROUP BY info.ui_id
   ORDER BY info.user_lastname, info.user_firstname";
}
elseif ( $_REQUEST['tab'] == 4 )  // Power User Summary
{
   $projectTitle = "Power User Summary";
   $fileName = "powerUserSummary";
   $description = "Listing of REDCap users and a summation
                   of events they have performed in the last
                   6 months. An event represents a specific
                   operation within REDCap like creating,
                   modifying, or removing a record.";

   // obtain a listing of all REDCap owners and totals
   $sql = "
   SELECT user AS 'HawkID',
       CONCAT( info.user_lastname, ', ', info.user_firstname ) AS 'Users Name',
       info.user_email AS 'User Email',
       GROUP_CONCAT( DISTINCT event ORDER BY event DESC SEPARATOR ', ' ) AS 'User Events',
       COUNT( user ) AS 'Event Count'
   FROM redcap_log_event AS logs,
        redcap_user_information AS info
   WHERE ts BETWEEN DATE_FORMAT( SUBDATE( NOW(), 180 ), '%Y%m%d%H%i%s' ) AND DATE_FORMAT( NOW(), '%Y%m%d%H%i%s' ) AND
         logs.user = info.username
   GROUP BY user
   ORDER BY info.user_lastname, info.user_firstname";
}
elseif ( $_REQUEST['tab'] == 5 )  // Power User Details
{
   $projectTitle = "Power User Details";
   $fileName = "powerUserDetails";
   $description = "Listing of REDCap users and the specific
                   events they have performed in the last
                   6 months. An event represents a specific
                   operation within REDCap like creating,
                   modifying, or removing a record.";

   $sql = "
   SELECT user AS 'HawkID',
       CONCAT( info.user_lastname, ', ', info.user_firstname ) AS 'Users Name',
       info.user_email AS 'User Email',
       event AS 'User Event',
       -- GROUP_CONCAT( DISTINCT event ORDER BY event DESC SEPARATOR ', ' ) AS 'User Events',
       COUNT( event ) AS 'Event Count'
   FROM redcap_log_event AS logs,
        redcap_user_information AS info
   WHERE ts BETWEEN DATE_FORMAT( SUBDATE( NOW(), 180 ), '%Y%m%d%H%i%s' ) AND DATE_FORMAT( NOW(), '%Y%m%d%H%i%s' ) AND
         logs.user = info.username
   GROUP BY user, event
   ORDER BY info.user_lastname, info.user_firstname, event DESC";
}

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

<p style="text-align: right;">
   <a href="downloadCsvViaSql.php?file=<?= $csvFileName; ?>&sql=<?= urlencode( $sql ); ?>"
      class="btn btn-default btn-lg">
      <span class="glyphicon glyphicon-download"></span>
      Download CSV File</a>
</p>

<p />

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
   $result = mysql_query( $sql );

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

   // Display the footer
   $HtmlPage->PrintFooterExt();
?>