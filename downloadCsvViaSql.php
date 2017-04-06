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

require_once('resources/config.php');

require_once('../redcap_connect.php');

header('Content-type: text/csv');
header("Content-Description: File Transfer");
header( sprintf( "Content-Disposition: attachment; filename=%s", $_REQUEST['file'] ) );
header('Expires: 0');
header('Pragma: no-cache');

// initialize variables
$pageInfo = $reportReference[ (!$_REQUEST['tab']) ? 0 : $_REQUEST['tab'] ];
$result = sqlQuery($conn, $pageInfo);
$isFirstRow = TRUE;

while ( $row = mysqli_fetch_assoc($result) )
{
   if ( $isFirstRow )
   {
      // use column aliases for column headers
      $headers = array_keys( $row );

      $headerStr = implode( "\",\"", $headers );
      printf( "\"%s\"\n", $headerStr );

      $isFirstRow = FALSE;  // toggle flag
   }

    $row['Purpose Specified'] = ConvertProjectPurpose2List($row['Purpose Specified']);

    $rowStr = implode( "\",\"", $row );
   printf( "\"%s\"\n", $rowStr );
}
