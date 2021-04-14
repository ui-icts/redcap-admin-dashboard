<?php
/** @var \UIOWA\AdminDash\AdminDash $module */

// api request requires token, only method allowed is runReport
if (isset($_POST['token'])) {
    $_POST['method'] = 'runReport';

    $result = $module->query('
        select * from redcap_user_rights
        where project_id = ? and
              api_token = ?
    ', [
        $_GET['pid'],
        $_POST['token']
    ]);

    $apiEnabled = json_decode(\REDCap::getData(array(
        'project_id' => $module->configPID,
        'return_format' => 'json',
        'records' => $_GET['id'],
        'fields' => 'api_access'
    )), true)[0]['api_access'];

    // verify api token is valid and this report has api_access enabled
    if ($result->num_rows !== 1 || $apiEnabled !== '1') {
        die('You do not have permissions to use the API');
    }
}
// need token for anything other than data entry form or dashboard, only accept post request
else if (
    !$_POST['fromModule'] ||
    $_SERVER['REQUEST_METHOD'] !== 'POST'
) {
    die('This method is not supported.');
}

if (!isset($_POST['params']['id'])) {
    $_POST['params']['id'] = $_GET['id'];
}

call_user_func(array($module, $_POST['method']), $_POST['params']);