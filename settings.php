<?php
/** @var \UIOWA\AdminDash\AdminDash $module */

$page = new HtmlPage();
$page->PrintHeaderExt();
include APP_PATH_VIEWS . 'HomeTabs.php';

$module->initializeSmarty();
$module->includeJsAndCss();
$module->initializeVariables();
$module->displayTemplate('nav.tpl');
$module->displayTemplate('settings.tpl');