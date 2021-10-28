<?php
/** @var \UIOWA\AdminDash\AdminDash $module */

$page = new HtmlPage();
$page->PrintHeaderExt();
include APP_PATH_VIEWS . 'HomeTabs.php';

?>
<script>
    $(document).ready(function () {
        $('#warningModal').modal('show');
    });
</script>

<div style="text-align: center">
    <h2 style="padding-top: 50px">Thank you for installing the Admin Dashboard module!</h2>
    <br />
    <br />
    <h5>As of version 4.0, reports are configured and stored in a dedicated REDCap project. If you're seeing this page, you likely do not have the required configuration project linked to the module.</h5>
    <h5>Please follow the steps below to create the configuration project and link it to the module.</h5>
</div>
<br />
<br />
<h4>
    <ol>
        <li><a href="<?= $module->getUrl("AdminDashboardReportsTemplate.xml") ?>" style="font-size:24px" download>Download configuration project template</a></li>
        <li>Create a new REDCap project</li>
        <ul>
            <li>Title and project purpose can be anything ("Admin Dashboard Reports" and "Operational Support" recommended)</li>
            <li>Choose the "Upload a REDCap project XML file" option and upload the template</li>
        </ul>
        <li>Visit the "External Modules" page for this project and enable the Admin Dashboard module</li>
    </ol>
</h4>


<div class="modal" tabindex="-1" role="dialog" id="warningModal">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">IMPORTANT! PLEASE READ BEFORE CONTINUING</h5>
            </div>
            <div class="modal-body">
                <p>
                    <strong>Version 4.0 is a major overhaul of the Admin Dashboard module that changes the way reports are configured and stored.</strong> Any custom reports created in previous versions will not be automatically carried over.
                </p>
                <p>
                    <strong style="color:red">It is recommended to revert to a previous version of the Admin Dashboard and copy your SQL queries before completing the upgrade to 4.0.</strong>
                </p>
                <p>
                    If you do not wish to carry over your existing custom reports, you may ignore this warning. They will remain if you decide to return to a previous version at a later date.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Continue</button>
                <button type="button" class="btn btn-secondary" onclick="window.history.back()">Go Back</button>
            </div>
        </div>
    </div>
</div>