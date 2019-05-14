<?php
/** @var \UIOWA\AdminDash\AdminDash $module */

if (!SUPER_USER) {
    die("Access denied! You do not have permission to view this page.");
}
else {
    $page = new HtmlPage();
    $page->PrintHeaderExt();
    include APP_PATH_VIEWS . 'HomeTabs.php';

    $module->initializeSmarty();
    $module->includeJsAndCss();
    $module->initializeVariables();
    $module->displayTemplate('nav.tpl');
    $module->displayTemplate('settings.tpl');
}