<?php
// query REDCap data and download as CSV file

$adminDash = new \UIOWA\AdminDash\AdminDash();

header('Content-type: text/csv');
header("Content-Description: File Transfer");
header( sprintf( "Content-Disposition: attachment; filename=%s", $_REQUEST['file'] ) );
header('Expires: 0');
header('Pragma: no-cache');

// initialize variables
$reportReference = $adminDash->generateReportReference();
$pageInfo = $reportReference[ (!$_REQUEST['tab']) ? 0 : $_REQUEST['tab'] ];
$result = db_query($pageInfo['sql']);
$isFirstRow = TRUE;

$adminDash->formatQueryResults($result, "csv", $pageInfo);