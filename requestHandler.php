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
elseif ($_REQUEST['type'] == 'testQuery') {
    $module->testQuery();
}