<?php
/** @var \UIOWA\AdminDash\AdminDash $module */

if ($_REQUEST['type'] == 'saveConfigSetting') {
    $module->saveConfigSetting();
}
elseif ($_REQUEST['type'] == 'saveReportSettings') {
    $module->saveReportSettings();
}
elseif ($_REQUEST['type'] == 'exportDiagnosticFile') {
    $module->exportDiagnosticFile();
}
elseif ($_REQUEST['type'] == 'getApiToken') {
    $module->getApiToken($_POST['pid']);
}
elseif ($_REQUEST['type'] == 'sqlQuery') {
    $module->sqlQuery();
}
elseif ($_REQUEST['type'] == 'getProjectList') {
    $module->getProjectList();
}
elseif ($_REQUEST['type'] == 'getProjectFields') {
    $module->getProjectFields($_POST['pid']);
}