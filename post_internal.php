<?php
/** @var \UIOWA\AdminDash\AdminDash $module */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('This method is not supported.');
}

if (!isset($_POST['id'])) {
    $_POST['id'] = $_GET['id'];
}
error_log($_POST['adMethod']);
if(isset($_POST['adMethod'])) {
    if($_POST['adMethod'] == 'runReport') {
        call_user_func(array($module, 'runReport'), $_POST);
    } elseif($_POST['adMethod'] == 'joinProjectData') {
        call_user_func(array($module, 'joinProjectData'), $_POST);
    } elseif($_POST['adMethod'] == 'getAdditionalInfo') {
        call_user_func(array($module, 'getAdditionalInfo'), $_POST);
    }
     else {
        die('Something went wrong');
    }
    
}
