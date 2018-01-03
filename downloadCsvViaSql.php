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

$adminDash = new \UIOWA\AdminDash\AdminDash();

header('Content-type: text/csv');
header("Content-Description: File Transfer");
header( sprintf( "Content-Disposition: attachment; filename=%s", $_REQUEST['file'] ) );
header('Expires: 0');
header('Pragma: no-cache');

// initialize variables
$pageInfo = $adminDash::$reportReference[ (!$_REQUEST['tab']) ? 0 : $_REQUEST['tab'] ];
$result = db_query($pageInfo['sql']);
$isFirstRow = TRUE;

$adminDash->formatQueryResults($result, "csv");