<?php
/**
 * @file downloadCsvViaSql.php
 * @author Fred R. McClurg, University of Iowa
 * @date August 14, 2014
 * @version 1.0
 */

/**
 * @brief Downloads a CSV from the passed SQL
 *
 * @param file  File name of the download
 * @param tab   Corresponds to the tab number selected.  Also
 *              determines the SQL, the page title, and the page
 *              description.
 *
 * @example https://www-dev.icts.uiowa.edu/redcap/adminDash/downloadCsvViaSql.php?file=usersByProject.2014-10-14_085438.csv&tab=1
 */

// set standard error reporting
require_once("lib/errorReporting.php");

// debugging functions
require_once("lib/debugFunctions.php");

// connect to the REDCap database
require_once('../redcap_connect.php');

// define all the SQL statements that are used
require_once('lib/variableLookup.php');

header('Content-type: text/csv');
header("Content-Description: File Transfer");
header( sprintf( "Content-Disposition: attachment; filename=%s", $_REQUEST['file'] ) );
header('Expires: 0');
header('Pragma: no-cache');

$pageInfo = GetPageDetails( $_REQUEST['tab'] );

// Note: $sql is defined from $_REQUEST['tab'] within file
//       variableLookup.php
$query = mysqli_query($conn,  $pageInfo['sql'] );

if ( ! $query )  // sql failed
{
   die( "Could not execute SQL:
         <pre>$sql</pre> <br />" .
         mysqli_error($conn) );
}

// initialize variables
$isFirstRow = TRUE;

while ( $row = mysqli_fetch_assoc($query) )
{
   if ( $isFirstRow )
   {
      // use column aliases for column headers
      $headers = array_keys( $row );

      $headerStr = implode( "\",\"", $headers );
      printf( "\"%s\"\n", $headerStr );

      $isFirstRow = FALSE;  // toggle flag
   }

   $rowStr = implode( "\",\"", $row );
   printf( "\"%s\"\n", $rowStr );
}
