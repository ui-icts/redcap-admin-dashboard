<?php
/**
 * @brief Downloads a CSV from the passed SQL
 *
 * @file downloadCsvViaSql.php
 * @version 1.0
 * @author Fred R. McClurg, University of Iowa
 * @date August 14, 2014
 *
 * @param file  Name of the download
 * @param sql   SQL code
 */

// set standard error reporting
require_once( "lib/errorReporting.php");

// debugging functions
// require_once( "debugFunctions.php" );

// connect to the REDCap database
require_once('../redcap_connect.php');

// only allow super users to download this information
if (!SUPER_USER) die("Access denied! Only super users can access this page.");

header('Content-type: text/csv');
header("Content-Description: File Transfer");
header( sprintf( "Content-Disposition: attachment; filename=%s", $_REQUEST['file'] ) );
header('Expires: 0');
header('Pragma: no-cache');

// $csv = "First,Last,Email\nFred,McClurg,frmcclurg@gmail.com";
// echo $csv;

$sql = $_REQUEST['sql'];
$query = mysql_query( $sql );

if ( ! $query )  // sql failed
{
   die( "Could not execute SQL:
         <pre>$sql</pre> <br />" .
         mysql_error() );
}

$isFirstRow = TRUE;

while ( $row = mysql_fetch_assoc($query) )
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
