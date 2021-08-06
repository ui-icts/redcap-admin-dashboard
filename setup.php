<?php
/** @var \UIOWA\AdminDash\AdminDash $module */

$page = new HtmlPage();
$page->PrintHeaderExt();
include APP_PATH_VIEWS . 'HomeTabs.php';

// check if user has supertoken for auto-config button
$query = $module->query("SELECT count(api_token) as token_exists FROM redcap_user_information where username = ?", USERID);
$hasSupertoken = $query->fetch_assoc()['token_exists'] == 1;

?>
<script>
    $(document).ready(function () {
        $('.create-config-project').click(function () {
            $.ajax({
                method: 'POST',
                url: '<?= $module->getUrl("post_internal.php") ?>',
                data: {
                    adMethod: 'createConfigProject',
                    redcap_csrf_token: '<?= $module->getCSRFToken() ?>'
                },
                success: function (pid) {
                    $('.success-msg').show()
                        .html(
                            "Success! " +
                            "You can now <a href='#' onclick='location.reload()'>reload this page</a> to view the Admin Dashboard " +
                            "or <a href='" + '<?= $module->getRedcapUrl() ?>' + "record_status_dashboard.php?pid=" + pid + "'>" +
                            "visit the configuration project to create/edit reports.</a>"
                        );
                }
            });
        });
    });
</script>

<div style="text-align: center">
    <h2 style="padding-top: 50px">Thank you for installing the Admin Dashboard module!</h2>
</div>
<p>
    As of version 4.0, reports are configured and stored in a dedicated REDCap project. If you're seeing this page, you likely do not have the required configuration project linked to the module.
</p>
<p>
    If this is your first time using the Admin Dashboard (or you're upgrading from an older version), you can press the button below to automatically create the configuration project and link it to the module.
</p>
<div style="margin:10px; text-align: center">
    <button class="btn btn-primary create-config-project" disabled <?= !$hasSupertoken ? "disabled" : "" ?> data-toggle="tooltip" data-placement="top" title="Tooltip on top">Create & Link Configuration Project</button>
    <div>
        <?= !$hasSupertoken ?
            "<small style='color:red'>Super API token required</small>" :
            "<div class='success-msg alert alert-success' style='display: none; font-weight: bold'>
            </div>"
        ?>
    </div>
    <small style='color:red'>Auto-create project is a WIP and disabled for now</small>
</div>
<details>
    <summary>If the button doesn't work or you would simply prefer to set things up manually, click here for manual steps.</summary>
<ol>
    <li><a href="<?= $module->getUrl("AdminDashboardReportsTemplate.xml") ?>" download>Download configuration project template</a></li>
    <li>Create a new REDCap project</li>
    <ul>
        <li>Title and project purpose can be anything ("Admin Dashboard Reports" and "Operational Support" recommended)</li>
        <li>Choose the "Upload a REDCap project XML file" option and upload the template</li>
    </ul>
    <li>Visit the "External Modules" page for this project and enable the Admin Dashboard module</li>
    <li>Visit the "External Modules" page in the Control Center and set the "report configuration project" setting to the project you just created</li>
</ol>
</details>