<?php
/** @var \UIOWA\AdminDash\AdminDash $module */

header('Location: ' . $module->getUrl("index.php", false, $module->getSystemSetting("use-api-urls")));