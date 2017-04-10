<?php

require_once('resources/config.php');

require_once('../redcap_connect.php');

$pageInfo = $reportReference[ (!$_REQUEST['tab']) ? 0 : $_REQUEST['tab'] ];

$result = sqlQuery($conn, $pageInfo);

$data = array();

while ( $row = mysqli_fetch_assoc( $result ) )
{
    $data[] = $row;
}

echo json_encode($data);