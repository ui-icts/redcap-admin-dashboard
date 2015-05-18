<?php
/**
 * @file searchProjectForPasswords.php
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
 * @brief For reference: SQL for capturing usernames and passwords in project fields
 *
 *    SELECT projects.project_id AS 'PID',
 *       projects.app_title AS 'Project Name',
 *       meta.form_name AS 'Form Name',
 *       meta.field_name AS 'Field Name',
 *       meta.element_label AS 'Field Label',
 *       meta.element_note AS 'Field Note',
 *       users.username AS 'Owner HawkID',
 *       CONCAT( users.user_lastname, ', ', users.user_firstname ) AS 'Owner Name',
 *       users.user_email AS 'Owner Email'
 *    FROM redcap_projects AS projects,
 *       redcap_metadata AS meta,
 *       redcap_user_information AS users
 *    WHERE (projects.created_by = users.ui_id) AND
 *       (projects.project_id = meta.project_id) AND
 *       ( (field_name LIKE '%pass%word%') OR
 *       (field_name LIKE '%pass%wd%') OR
 *       (field_name LIKE '%hawk%id%') OR
 *       (field_name LIKE '%hwk%id%') OR
 *       (field_name LIKE '%user%name%') OR
 *       (field_name LIKE '%user%id%') OR
 *       (field_name LIKE '%usr%name%') OR
 *       (field_name LIKE '%usr%id%') OR
 *       (element_label LIKE '%pass%word%') OR
 *       (element_label LIKE '%pass%wd%') OR
 *       (element_label LIKE '%hawk%id%') OR
 *       (element_label LIKE '%hwk%id%') OR
 *       (element_label LIKE '%user%name%') OR
 *       (element_label LIKE '%user%id%') OR
 *       (element_label LIKE '%usr%name%') OR
 *       (element_label LIKE '%usr%id%') OR
 *       (element_note LIKE '%pass%word%') OR
 *       (element_note LIKE '%pass%wd%') OR
 *       (element_note LIKE '%hawk%id%') OR
 *       (element_note LIKE '%hwk%id%') OR
 *       (element_note LIKE '%user%name%') OR
 *       (element_note LIKE '%user%id%') OR
 *       (element_note LIKE '%usr%name%') OR
 *       (element_note LIKE '%usr%id%') )
 *    ORDER BY projects.project_id, form_name, field_name;";
 */

// capture the number of fields in a project
$baseSql = "
      SELECT projects.project_id AS 'PID',
         projects.app_title AS 'Project Name',
         meta.form_name AS 'Form Name',
         meta.field_name AS 'Field Name',
         meta.element_label AS 'Field Label',
         meta.element_note AS 'Field Note',
         users.username AS 'Owner HawkID',
         CONCAT( users.user_lastname, ', ', users.user_firstname ) AS 'Owner Name',
         users.user_email AS 'Owner Email'
      FROM redcap_projects AS projects,
         redcap_metadata AS meta,
         redcap_user_information AS users
      WHERE (projects.created_by = users.ui_id) AND
         (projects.project_id = meta.project_id) AND";

/*
         ( (field_name LIKE '%pass%word%') OR
         (field_name LIKE '%pass%wd%') OR
         (field_name LIKE '%hawk%id%') OR
         (field_name LIKE '%hwk%id%') OR
         (field_name LIKE '%user%name%') OR
         (field_name LIKE '%user%id%') OR
         (field_name LIKE '%usr%name%') OR
         (field_name LIKE '%usr%id%') OR
         (element_label LIKE '%pass%word%') OR
         (element_label LIKE '%pass%wd%') OR
         (element_label LIKE '%hawk%id%') OR
         (element_label LIKE '%hwk%id%') OR
         (element_label LIKE '%user%name%') OR
         (element_label LIKE '%user%id%') OR
         (element_label LIKE '%usr%name%') OR
         (element_label LIKE '%usr%id%') OR
         (element_note LIKE '%pass%word%') OR
         (element_note LIKE '%pass%wd%') OR
         (element_note LIKE '%hawk%id%') OR
         (element_note LIKE '%hwk%id%') OR
         (element_note LIKE '%user%name%') OR
         (element_note LIKE '%user%id%') OR
         (element_note LIKE '%usr%name%') OR
         (element_note LIKE '%usr%id%') )
      ORDER BY projects.project_id, form_name, field_name;";
      */

// Project Size Summary
$projectTitle = "Password In Fields";
$fileName = "passwordInFields";
$description = "Listing of projects that contain the string \"password\" in one of the fields.";

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
            // widgets: ['zebra']
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
   /* associative array to identify the column names and checkbox labels */
   $fieldTypes = array( "field_name" => "Variable Name",
                        "element_label" => "Field Label",
                        "element_note" => "Field Note" );

   /* array to identify the SQL wildcards names and checkbox labels */
   /* can be tested as follows: SELECT 'userid' REGEXP 'us(e)?r *(name)|(id)'; */
   $sqlWildCards = array( "pass[- ]*w(or)?d" => "password",
                          "h(a)?wk[- ]*id" => "hawkid",
                          "us(e)?r[- ]*(name)|(id)" => "userid or username",
                          "log[io]n[- ]*(name)|(id)" => "loginid or loginname",
                          "sign(-)?[io]n[- ]*(name)|(id)" => "sign-in id or sign-in namespace" );
?>

<form name="passwordSearch" class="form-group">

         ( (field_name LIKE '%pass%word%') OR
         (field_name LIKE '%pass%wd%') OR
         (field_name LIKE '%hawk%id%') OR
         (field_name LIKE '%hwk%id%') OR
         (field_name LIKE '%user%name%') OR
         (field_name LIKE '%user%id%') OR
         (field_name LIKE '%usr%name%') OR
         (field_name LIKE '%usr%id%') OR
         (element_label LIKE '%pass%word%') OR
         (element_label LIKE '%pass%wd%') OR
         (element_label LIKE '%hawk%id%') OR
         (element_label LIKE '%hwk%id%') OR
         (element_label LIKE '%user%name%') OR
         (element_label LIKE '%user%id%') OR
         (element_label LIKE '%usr%name%') OR
         (element_label LIKE '%usr%id%') OR
         (element_note LIKE '%pass%word%') OR
         (element_note LIKE '%pass%wd%') OR
         (element_note LIKE '%hawk%id%') OR
         (element_note LIKE '%hwk%id%') OR
         (element_note LIKE '%user%name%') OR
         (element_note LIKE '%user%id%') OR
         (element_note LIKE '%usr%name%') OR
         (element_note LIKE '%usr%id%') )

<table class="table table-striped table-bordered table-hover">
   <tr>
      <th style="font-weight: bold; text-align: center;">
         Field Type
      </th>
      <th style="font-weight: bold; text-align: center;">
         Search String
      </th>
   </tr>

   <tr>
      <td>  <!--  Field Type -->
        <div class="checkbox">
           <?= GenerateCheckBox( "field_type", "field_name", "Variable Name" ); ?>
           <label>
              <input type="checkbox" name="field_type[]" value="field_name" /> Variable Name
           </label> <br />

           <label>
              <input type="checkbox" name="field_type[]" value="element_label" /> Field Label
           </label> <br />
           <label>
              <input type="checkbox" name="field_type[]" value="element_note" /> Field Note
           </label> <br />
         </div>
      </td>
      <td>  <!-- Search String -->
         <div class="checkbox">
            <label>
               <input type="checkbox" name="search_string[]" value="%pass%word%" /> <tt>%pass%word%</tt>
            </label> <br />

            <label>
               <input type="checkbox" name="search_string[]" value="%pass%wd%" /> <tt>%pass%wd%</tt>
            </label> <br />

            <label>
               <input type="checkbox" name="search_string[]" value="%hawk%id%" /> <tt>%hawk%id%</tt>
            </label> <br />
         </div>
      </td>
   </tr>

   <tr>
      <th colspan="2" style="text-align: center;">
         <button type="submit" class="btn btn-default" name="doit">Submit</button>
      </th>
   </tr>
</table>
</form>

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
