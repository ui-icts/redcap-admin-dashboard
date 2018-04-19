<?php

$page = new HtmlPage();
$page->PrintHeaderExt();
include APP_PATH_VIEWS . 'HomeTabs.php';

$adminDash = new \UIOWA\AdminDash\AdminDash();
$adminDash->generateAdminDash();