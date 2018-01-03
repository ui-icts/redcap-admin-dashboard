<?php

$adminDash = new \UIOWA\AdminDash\AdminDash();

$pageInfo = $adminDash::$visualizationQueries[ $_REQUEST['vis'] ];
$result = db_query($pageInfo['sql']);
$data = array();

//var_dump($data);

while ( $row = db_fetch_assoc( $result ) )
{
    $data[] = $row;
}

echo json_encode($data);