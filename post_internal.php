<?php
/** @var \UIOWA\AdminDash\AdminDash $module */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('This method is not supported.');
}

if (!isset($_POST['id'])) {
    $_POST['id'] = $_GET['id'];
}

if(isset($_POST['adMethod'])) {
    if($_POST['adMethod'] === 'joinProjectData') {
        $module->joinProjectData($_POST);
    } elseif(SUPER_USER != "1" && $_POST['adMethod'] === 'runExecutiveReport') {
        $module->runExecutiveReport($_POST);
    } elseif(SUPER_USER === "1" && $_POST['adMethod'] === 'getAdditionalInfo') {
        $module->getAdditionalInfo($_POST);
    } elseif(SUPER_USER === "1" && $_POST['adMethod'] === 'getQuery') {
        $module->getQuery($_POST);
    } 
     else {
        die('error: something went wrong');
    }
    
}
