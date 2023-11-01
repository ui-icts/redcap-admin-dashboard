<?php
/** @var \UIOWA\AdminDash\AdminDash $module */

$userToken = "";
if (isset($_POST['token'])) { // from API endpoint
    $reportType = json_decode(\REDCap::getData(array(
        'project_id' => $module->configPID,
        'return_format' => 'json',
        'events' => ['report_config_arm_1', 'report_config_arm_2'],
        'records' => $_GET['id']
    )), true)[0];

    $_POST['adMethod'] = $reportType['redcap_event_name'] == 'report_config_arm_2' ? 'joinProjectData' : 'runApiReport';

    $query = $module->query('
        select * from redcap_user_rights
        where project_id = ? and
              api_token = ?
    ', [
        $module->configPID,
        $_POST['token']
    ]);

    $userRights = $query->fetch_assoc();

    $apiEnabled = json_decode(\REDCap::getData(array(
        'project_id' => $module->configPID,
        'return_format' => 'json',
        'events' => ['user_access_arm_1', 'user_access_arm_2'],
        'records' => $_GET['id'],
        'fields' => 'api_access'
    )), true)[0]['api_access'];


    // verify api token is valid and this report has api_access enabled
    if ($userRights['api_token'] !== $_POST['token'] || $apiEnabled !== '1') {
        die('You do not have permissions to use the API');
    } else {
        $userToken = $userRights['api_token'];
    }

    $_POST['username'] = $userRights['username'];
}
else {
    die('No API token defined.');
}

if (!isset($_POST['id'])) {
    $_POST['id'] = $_GET['id'];
}

if($userToken === $_POST['token'] && $apiEnabled === '1') {
    call_user_func(array($module, $_POST['adMethod']), $_POST);
} else {
    die('You do not have permissions to use the API');
}
