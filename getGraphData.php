<?php

require_once('resources/config.php');

require_once('../redcap_connect.php');

$pageInfo = $visualizationQueries[ $_REQUEST['vis'] ];

$result = sqlQuery($conn, $pageInfo['sql']);

$data = array();

while ( $row = mysqli_fetch_assoc( $result ) )
{
    $data[] = $row;
}

echo json_encode($data);