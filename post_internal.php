<?php
/** @var \UIOWA\AdminDash\AdminDash $module */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('This method is not supported.');
}

if (!isset($_POST['params']['id'])) {
    $_POST['params']['id'] = $_GET['id'];
}

call_user_func(array($module, $_POST['method']), $_POST['params']);