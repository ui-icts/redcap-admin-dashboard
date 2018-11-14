<?php
namespace UIOWA\AdminDash;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class AdminDash extends AbstractExternalModule {
    private static $purposeMaster = array
    (
        "Basic or Bench Research",
        "Clinical Research Study or Trial",
        "Translational Research 1",
        "Translational Research 2",
        "Behavioral or Psychosocial Research Study",
        "Epidemiology",
        "Repository",
        "Other"
    );

    private static $visualizationQueries = array
    (
    array
    (
    "visName" => "\"Status (All Projects)\"",
    "visID" => "\"status_all\"",
    "visType" => "\"count\"",
    "countColumns" => ["Status"],
    "sql" => "
            SELECT
            CAST(CASE status
            WHEN 0 THEN 'Development'
            WHEN 1 THEN 'Production'
            WHEN 2 THEN 'Inactive'
            WHEN 3 THEN 'Archived'
            ELSE status
            END AS CHAR(50)) AS 'Status'
            FROM redcap_projects
            ",
    "type" => "\"bar\"",
    "data" => "
        {
            labels: [\"Red\", \"Blue\", \"Yellow\", \"Green\", \"Purple\", \"Orange\"],
            datasets: [{
                label: '# of Votes',
                data: [12, 19, 3, 5, 2, 3],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)'
                ],
                borderColor: [
                    'rgba(255,99,132,1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        }
    ",
    "options" => "
        {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero:true
                    }
                }]
            }
        }
    "
   ),
    array
    (
    "visName" => "\"Purpose (All Projects)\"",
    "visID" => "\"purpose_all\"",
    "visType" => "\"count\"",
    "countColumns" => ["Purpose"],
    "sql" => "
            SELECT
            CAST(CASE purpose
            WHEN 0 THEN 'Practice / Just for fun'
            WHEN 4 THEN 'Operational Support'
            WHEN 2 THEN 'Research'
            WHEN 3 THEN 'Quality Improvement'
            WHEN 1 THEN 'Other'
            ELSE purpose
            END AS CHAR(50)) AS 'Purpose'
            FROM redcap_projects
            "
   ),
    array
    (
        "visName" => "\"Purpose (Research Projects)\"",
        "visID" => "\"purpose_research\"",
        "visType" => "\"count\"",
        "countColumns" => ["Purpose Specified"],
        "sql" => "
            SELECT
              CAST(CASE SUBSTRING_INDEX(SUBSTRING_INDEX(redcap_projects.purpose_other, ',', numbers.n), ',', -1)
                   WHEN 0 THEN 'Basic or Bench Research'
                   WHEN 1 THEN 'Clinical Research Study or Trial'
                   WHEN 2 THEN 'Translational Research 1'
                   WHEN 3 THEN 'Translational Research 2'
                   WHEN 4 THEN 'Behavioral or Psychosocial Research Study'
                   WHEN 5 THEN 'Epidemiology'
                   WHEN 6 THEN 'Repository'
                   WHEN 7 THEN 'Other'
                   ELSE purpose
                   END AS CHAR(50)) AS 'Purpose Specified'
            FROM
              (SELECT 1 n
               UNION ALL SELECT 2
               UNION ALL SELECT 3
               UNION ALL SELECT 4
               UNION ALL SELECT 5
               UNION ALL SELECT 6
               UNION ALL SELECT 7
               UNION ALL SELECT 8) numbers INNER JOIN redcap_projects
                ON CHAR_LENGTH(redcap_projects.purpose_other)
                   -CHAR_LENGTH(REPLACE(redcap_projects.purpose_other, ',', ''))>=numbers.n-1
            WHERE purpose = 2 AND purpose_other != ' '
        "
    )
   );

    private static $miscQueryReference = array
    (
    array
    (
    "queryName" => "Suspended Users",
    "sql" => "
            SELECT count(*) FROM redcap_user_information WHERE user_suspended_time IS NOT NULL
            "
   )
   );

    public function redcap_module_system_change_version() {
        // update db for compatibility with v3.2
        $oldVisibilityJson = $this->getSystemSetting("report-visibility");

        if ($oldVisibilityJson) {
            $oldVisibilityArray = json_decode($oldVisibilityJson, true);
            $executiveUsers = $this->getSystemSetting("executive-users");

            $visibilityArrayAdmin = array();
            $visibilityArrayExecutive = array();

            foreach ($oldVisibilityArray as $key => $oldVisibilityInfo) {
                $visibilityArrayAdmin[$key] = $oldVisibilityArray[$key][0];
                $visibilityArrayExecutive[$key] = array();

                foreach ($executiveUsers as $user) {
                    if ($oldVisibilityArray[$key][1]) {
                        array_push($visibilityArrayExecutive[$key], $user);
                    }
                }
            }

            $this->setSystemSetting("report-visibility-admin", $visibilityArrayAdmin);
            $this->setSystemSetting("report-visibility-executive", $visibilityArrayExecutive);
            $this->removeSystemSetting("report-visibility");
        }

        // update db for compatibility with v3.3
        $oldNameData = $this->getSystemSetting("custom-report-name");
        $oldDescData = $this->getSystemSetting("custom-report-desc");
        $oldIconData = $this->getSystemSetting("custom-report-icon");
        $oldSqlData = $this->getSystemSetting("custom-report-sql");

        if ($oldNameData) {
            $newCustomReportData = array();
            $untitledCount = 0;

            foreach ($oldNameData as $index => $oldName) {
                array_push($newCustomReportData, array(
                    'reportName' => empty($oldNameData[$index]) ? 'Untitled' : $oldNameData[$index],
                    'description' => empty($oldDescData[$index]) ? 'No description defined.' : $oldDescData[$index],
                    'tabIcon' => empty($oldIconData[$index]) ? 'fas fa-question-circle' : str_replace('fas fa', '', $oldIconData[$index]),
                    'sql' => $oldSqlData[$index],
                    'type' => 'table'
                ));

                if ($newCustomReportData[$index]['reportName'] == 'Untitled') {
                    $untitledCount += 1;
                    $newCustomReportData[$index]['reportName'] .= $untitledCount;
                }
            }

            $this->setSystemSetting("custom-reports", $newCustomReportData);
            $this->removeSystemSetting("custom-report");
            $this->removeSystemSetting("custom-report-name");
            $this->removeSystemSetting("custom-report-desc");
            $this->removeSystemSetting("custom-report-icon");
            $this->removeSystemSetting("custom-report-sql");
        }
    }

    public function generateAdminDash() {

        ?>
        <div style="text-align: left; width: 100%">
            <div style="height: 50px;"></div>
        </div>

        <script src="<?= $this->getUrl("/resources/tablesorter/jquery.tablesorter.min.js") ?>"></script>
        <script src="<?= $this->getUrl("/resources/tablesorter/jquery.tablesorter.widgets.min.js") ?>"></script>
        <script src="<?= $this->getUrl("/resources/tablesorter/widgets/widget-pager.min.js") ?>"></script>
        <script src="<?= $this->getUrl("/resources/c3/d3.min.js") ?>" charset="utf-8"></script>
        <script src="<?= $this->getUrl("/resources/c3/c3.min.js") ?>"></script>
        <script src="<?= $this->getUrl("/resources/tablesorter/parsers/parser-input-select.min.js") ?>"></script>
        <script src="<?= $this->getUrl("/resources/tablesorter/widgets/widget-output.min.js") ?>"></script>
        <script src="<?= $this->getUrl("/resources/Chart.min.js") ?>"></script>
        <script src="<?= $this->getUrl("/resources/jquery.validate.min.js") ?>"></script>

        <link href="<?= $this->getUrl("/resources/tablesorter/tablesorter/theme.blue.min.css") ?>" rel="stylesheet">
        <link href="<?= $this->getUrl("/resources/tablesorter/tablesorter/theme.ice.min.css") ?>" rel="stylesheet">

        <link href="<?= $this->getUrl("/resources/tablesorter/tablesorter/jquery.tablesorter.pager.min.css") ?>" rel="stylesheet">
        <link href="<?= $this->getUrl("/resources/c3/c3.css") ?>" rel="stylesheet" type="text/css">
        <link href="<?= $this->getUrl("/resources/styles.css") ?>" rel="stylesheet" type="text/css"/>
        <link href="<?= $this->getUrl("/resources/tablesorter/tablesorter/theme.bootstrap_4.min.css") ?>" rel="stylesheet">

        <script src="<?= $this->getUrl("/adminDash.js") ?>"></script>

        <?php

        $reportReference = $this->generateReportReference();
        $executiveUsers = $this->getSystemSetting("executive-users");
        $defaultTab = $this->getSystemSetting("default-report") - 1;
        $executiveAccess = ($_REQUEST['page'] == 'executiveView' && (in_array(USERID, $executiveUsers) || SUPER_USER) ? 1 : 0);
        $exportEnabled = ($this->getSystemSetting("executive-export-enabled") && $executiveAccess) || (SUPER_USER && !$executiveAccess);
        $reportIDlookup = [];

        foreach($reportReference as $index => $reportInfo) {
            array_push($reportIDlookup, $reportInfo['customID']);
        }

        if (isset($_REQUEST['report']) && !is_numeric($_REQUEST['report'])) {
            $_REQUEST['report'] = array_search($_REQUEST['report'], $reportIDlookup);
        }

        $title = $executiveAccess ? "Executive Dashboard" : $this->getModuleName();
        $pageInfo = $reportReference[$_REQUEST['report']];
        $isSelectQuery = (strtolower(substr($pageInfo['sql'], 0, 6)) == "select");

        $adminVisibility = $this->loadVisibilitySettings('admin', $reportReference);
        $executiveVisibility = $this->loadVisibilitySettings('executive', $reportReference);
        $executiveVisible = false;

        if ($pageInfo['reportName']) {
            $executiveVisible = in_array(USERID, $executiveVisibility->{$pageInfo['reportName']});
        }

        if (!SUPER_USER) {
            if (!$executiveAccess || (!$executiveVisible && isset($_REQUEST['report']))) {
                die("Access denied! You do not have permission to view this page.");
            }
        }

        if ((!isset($_REQUEST['report']) && !$executiveAccess) && $defaultTab != -1) {
            $_REQUEST['report'] = $defaultTab;
        }

        ?>
        <h2 style="text-align: center; color: <?= (!$executiveAccess) ? "#106CD6" : "#4DADAF" ?>; font-weight: bold;">
            <?= $title ?>
        </h2>
            <?php if ($executiveAccess && SUPER_USER) : ?>
                <div style="text-align: center;" id="currentExecutiveUser">
                    <b>Viewing as:</b>
                    <select id="primaryUserSelect" class="executiveUser">
                        <option value="">[Select User]</option>
                        <?php
                        foreach($executiveUsers as $user):
                            if ($user) {
                                echo '<option value="' . $user . '">' . $user . '</option>';
                            }
                        endforeach;
                        ?>
                    </select>
                </div>
            <?php endif; ?>

             <p />

                <!-- create navigation tabs -->
             <ul class='nav nav-tabs report-tabs'>
             <?php foreach($reportReference as $index => $reportInfo): ?>
             <li class="nav-item <?= $_REQUEST['report'] == $index && isset($_REQUEST['report']) ? "active" : "" ?>" style="display:none" >
             <a class="nav-link" href="<?= $this->formatUrl($reportIDlookup[$index] != '' ? $reportIDlookup[$index] : $index) ?>">
             <span class="report-icon fas fa-<?= $reportInfo['tabIcon'] ?>"></span>&nbsp; <span class="report-title"><?= $reportInfo['reportName'] ?></span></a>
         </li>
        <?php endforeach; ?>
         </ul>

         <p />

        <script>
            UIOWA_AdminDash.csvFileName = '<?= sprintf("%s.csv", $pageInfo['customID']); ?>';
            UIOWA_AdminDash.renderDatetime = '<?= date("Y-m-d_His") ?>';

            UIOWA_AdminDash.executiveAccess = <?= $executiveAccess ?>;
            UIOWA_AdminDash.adminVisibility = <?= json_encode($adminVisibility) ?>;
            UIOWA_AdminDash.executiveVisibility = <?= json_encode($executiveVisibility) ?>;
            UIOWA_AdminDash.reportIDs = <?= json_encode($reportIDlookup) ?>;
            UIOWA_AdminDash.saveSettingsUrl = "<?= $this->getUrl("requestHandler.php?type=saveReportSettings") ?>";
            UIOWA_AdminDash.reportUrlTemplate = "<?= $this->getUrl(
                $executiveAccess ? "executiveView" : "index" . ".php", false, true) ?>";

            UIOWA_AdminDash.hideColumns = [];
            UIOWA_AdminDash.reportReference = <?= json_encode($reportReference) ?>;
            UIOWA_AdminDash.showArchivedReports = false;
            UIOWA_AdminDash.superuser = <?= SUPER_USER ?>;
            UIOWA_AdminDash.theme = UIOWA_AdminDash.executiveAccess ? 'ice' : 'blue';

            UIOWA_AdminDash.userID = '<?= USERID ?>';

        </script>

        <div>
            <?php if (SUPER_USER) : ?>
                <script src="<?= $this->getUrl("/resources/bootstrap-toggle/bootstrap-toggle.min.js") ?>"></script>
                <link href="<?= $this->getUrl("/resources/bootstrap-toggle/bootstrap-toggle.min.css") ?>" rel="stylesheet">

                <script src="<?= $this->getUrl("/resources/ace/ace.js") ?>" type="text/javascript" charset="utf-8"></script>

                <div style="float: left">
                <button type="button" class="btn btn-primary open-visibility-settings" data-toggle="modal" data-target="#reportVisibilityModal">
                    <span class="fas fa-cog"></span> Configure Reports
                </button>
            </div>
            <!-- Modal -->
            <div class="modal fade" id="reportVisibilityModal" tabindex="-1" role="dialog" aria-labelledby="reportVisibilityModal" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="reportVisibilityModalLongTitle" style="text-align: center">Report Settings</h5>
                            <div>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary save-visibility-settings" data-dismiss="modal">Save</button>
                            </div>
                        </div>
                        <div class="modal-body">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th style="text-align: center; font-size: 18px">
                                        <b>Admin View</b>
                                    </th>
                                    <th style="text-align: center; font-size: 18px">
                                        <select id="modalUserSelect" class="executiveUser">
                                            <option value="">[Select User]</option>
                                            <?php
                                            foreach($executiveUsers as $user):
                                                if ($user) {
                                                    echo '<option value="' . $user . '">' . $user . '</option>';
                                                }
                                            endforeach;
                                            ?>
                                        </select>
                                        <br/>
                                        <b>Executive View</b>
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="report-visibility-table">
                                    <tr>
                                        <td style="text-align: center;" colspan="4">
                                            <button type="button" class="btn btm-sm btn-success open-report-setup add-report-button" aria-haspopup="true" aria-expanded="false" data-toggle="modal" data-target="#reportSetupModal">
                                                <span class="fas fa-plus"></span> Add New Report
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal -->
            <div class="modal fade" id="reportSetupModal" tabindex="-1" role="dialog" aria-labelledby="reportSetupModal" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content secondary-modal">
                        <div class="modal-header">
                            <h5 class="modal-title" id="reportSetupModalLongTitle" style="text-align: center">Configure Report</h5>
                            <div>
                                <button type="button" class="btn btn-secondary close-report-setup" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary save-report-setup">Save</button>
                            </div>
                        </div>
                        <div class="modal-body">
                            <div id="reportIndex" style="display: none;"></div>
                            <form id="reportConfiguration" novalidate data-toggle="validator">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="reportName">Title:</label>
                                        <input id="reportName" name="reportName" class="form-control" required>
                                        <small id="titleValidation" class="invalid-feedback">
                                            Report name must be unique.
                                        </small>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="reportIcon">Icon:</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i id="reportIconPreview" class="fas fa-question"></i></span>
                                            </div>
                                            <input id="reportIcon" data-placement="bottomRight" class="form-control" value="question" type="text" aria-describedby="iconHelpBlock">
                                        </div>
                                        <small id="iconHelpBlock" class="form-text text-muted">
                                            Accepts most Solid Icons from Font Awesome (<a href="https://fontawesome.com/cheatsheet#solid" style="font-size: inherit" target="_blank">reference</a>)
                                        </small>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="reportDescription">Description:</label>
                                    <input id="reportDescription" class="form-control">
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6" hidden>
                                        <label for="reportDisplayType">Display Type:</label>
                                        <div class="input-group">
                                            <select class="form-control" id="reportDisplayType" aria-describedby="displayHelpBlock">
                                                <option>Table</option>
                                                <option>Chart</option>
                                            </select>
                                            <small id="displayHelpBlock" class="form-text text-muted">
                                                Display query results in a sortable table view or in a custom visualization.
                                            </small>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="reportCustomId">Report ID:</label>
                                        <div class="input-group">
                                            <input id="reportCustomId" data-placement="bottomRight" class="form-control" type="text" aria-describedby="idHelpBlock">
                                            <div class="input-group-append">
                                                <span id="reportId" class="input-group-text" readonly="true"></span>
                                            </div>
                                        </div>
                                        <small id="idHelpBlock" class="form-text text-muted">
                                            Define optional alphanumeric string for easier bookmarking. The report index (in grey) is used by default.
                                        </small>
                                    </div>
                                </div>
                                <ul class="nav nav-tabs" id="myTab" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="sql-tab" data-toggle="tab" href="#sql" role="tab" aria-controls="sql" aria-selected="true">SQL Query</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="formatting-tab" data-toggle="tab" href="#formatting" role="tab" aria-controls="formatting" aria-selected="false" hidden>Special Formatting</a>
                                    </li>
                                </ul>
                                <div class="tab-content" id="myTabContent">
                                    <div class="tab-pane fade show active" id="sql" role="tabpanel" aria-labelledby="sql-tab">
                                        <div class="form-group">
                                            <small id="queryHelpBlock" class="form-text text-muted">
                                                SELECT queries only.
                                            </small>
                                            <textarea id="reportQuery" aria-describedby="queryHelpBlock">
SELECT
    project_id AS "PID",
    app_title AS "Project Title"
FROM redcap_projects</textarea>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="formatting" role="tabpanel" aria-labelledby="formatting-tab">
                                        <table class="reportSpecialFormatting table table-striped">
                                            <thead>
                                                <th>Column Name</th>
                                                <th>Format As...</th>
                                                <th></th>
                                                <th></th>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <input class="column-name">
                                                    </td>
                                                    <td>
                                                        <select>
                                                            <option>Link</option>
                                                            <option>Email</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input class="">
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
                <script>
                    if (sessionStorage.getItem("selectedUser") && UIOWA_AdminDash.superuser) {
                        $('.executiveUser').val( sessionStorage.getItem("selectedUser") );
                        UIOWA_AdminDash.userID = $('.executiveUser')[0].value;
                    }

                    var reportTable = $('.report-visibility-table');
                    var addButtonRow = $('.add-report-button').closest('tr');

                    for (var i in UIOWA_AdminDash.reportReference) {
                        var reportName = UIOWA_AdminDash.reportReference[i]['reportName'];
                        var readOnly = UIOWA_AdminDash.reportReference[i]['readOnly'];
                        var reportRow = UIOWA_AdminDash.createReportRow(reportName).insertBefore(addButtonRow);

                        if (readOnly) {
                            $('.custom-report-only', reportRow).hide();
                        }
                    }

                    $('.executiveUser').change(function() {
                        $('.executiveUser').not(this).val( this.value );
                        sessionStorage.setItem("selectedUser", this.value);
                        UIOWA_AdminDash.updateSettingsModal(this.value);
                    });

                    $('.open-visibility-settings').click(function() {
                        var username = null;

                        if (UIOWA_AdminDash.executiveAccess) {
                            username = $('#primaryUserSelect')[0].value;

                            if ($('#primaryUserSelect')[0].value) {
                                $('#modalUserSelect')[0].value = $('#primaryUserSelect')[0].value;
                            }
                        }
                        else {
                            username = $('#modalUserSelect')[0].value;
                        }

                        UIOWA_AdminDash.updateSettingsModal(username);
                    });

                    $('.save-visibility-settings').click(function() {
                        UIOWA_AdminDash.adminVisibility = {};

                        var selectedUser = $('#modalUserSelect')[0].value;

                        $('.report-visibility-table tr').each(function () {
                            var reportTitle = $(this).find('.table-report-title').html();
                            var adminVisible = !$(this).find('.table-admin-visible div').hasClass('off');
                            var executiveVisible = !$(this).find('.table-executive-visible div').hasClass('off');
                            var prevVisible = $.inArray(selectedUser, UIOWA_AdminDash.executiveVisibility[reportTitle]) != -1;

                            if (reportTitle == undefined) {return;}

                            UIOWA_AdminDash.adminVisibility[reportTitle] = adminVisible;

                            if (!UIOWA_AdminDash.executiveVisibility[reportTitle]) {
                                UIOWA_AdminDash.executiveVisibility[reportTitle] = [];
                            }

                            if (executiveVisible) {
                                if (prevVisible) {return;}

                                UIOWA_AdminDash.executiveVisibility[reportTitle].push(selectedUser);
                            }
                            else {
                                UIOWA_AdminDash.executiveVisibility[reportTitle] = $.grep(UIOWA_AdminDash.executiveVisibility[reportTitle], function(e){
                                    return e != selectedUser;
                                });
                            }
                        });

                        UIOWA_AdminDash.saveReportSettingsToDb('visibility');
                        UIOWA_AdminDash.updateReportTabs(selectedUser);
                    });

                    reportTable.on('click', '.open-report-setup', function() {
                        var reportTr = $(this).closest('tr');
                        UIOWA_AdminDash.newReport = $('.add-report-button', reportTr).length !== 0;

                        UIOWA_AdminDash.currentReportConfigIndex = reportTr.index();
                        UIOWA_AdminDash.updateReportSetupModal();
                    });

                    $('.save-report-setup').click(function() {

                        var form = $('#reportConfiguration');
                        var reportName = $('#reportName');
                        var reportCustomId = $('#reportCustomId');
                        var existingReports = Object.keys(UIOWA_AdminDash.adminVisibility);

                        jQuery.validator.addMethod("notInArray", function(value, element, param) {
                            var index = $.inArray(value, param);
                            return index == -1 || index == UIOWA_AdminDash.currentReportConfigIndex;
                        }, "Value must be unique.");

                        form.validate();

                        reportName.rules("add", {
                            notInArray: existingReports
                        });

//                        reportCustomId.rules("add", {
//                            notInArray: UIOWA_AdminDash.reportIDs
//                        });

                        var valid = form.valid();

                        if (!valid) {
                            return;
                        }

                        if (UIOWA_AdminDash.newReport) {
                            var addButtonRow = $('.add-report-button').closest('tr');
                            var navBar = $('.report-tabs');
                            var reportRow = $(UIOWA_AdminDash.createReportRow('Untitled')).insertBefore(addButtonRow);

                            $('input', reportRow).bootstrapToggle();

                            if (!$('#modalUserSelect')[0].value) {
                                $('.table-executive-visible input', reportRow).prop('disabled', true);
                                $('.table-executive-visible .toggle-off', reportRow).addClass('disabled');
                            }

                            var newTab = $(
                                '<li class="nav-item" style="display:none">' +
                                '<a class="nav-link" href="' +
                                    UIOWA_AdminDash.reportUrlTemplate +
                                    '&report=' + ($('#reportCustomId').val() != '' ?
                                        $('#reportCustomId').val() : UIOWA_AdminDash.currentReportConfigIndex) + '">' +
                                '<span class="report-icon fas fa-' + $('#reportIcon').val() + '">' +
                                '</span>&nbsp; <span class="report-title">' + $('#reportName').val() + '</span>' +
                                '</a>'
                            ).appendTo(navBar);
                        }

                        UIOWA_AdminDash.saveReportConfiguration();

                        $('#reportSetupModal').modal('toggle');
                    });

                    $('#reportSetupModal').on('hidden.bs.modal', function() {
                        var $alertas = $('#reportConfiguration');
                        $alertas.validate().resetForm();
                        $alertas.find('.error').removeClass('error');
                    });

//                    $('#reportName').on('input', function() {
//                        var input = $('#reportName');
//                        var reportTitles = $.map(UIOWA_AdminDash.reportReference, function (i) {
//                            return i['reportName'];
//                        });
//                        var titleHelpText = $('#titleHelpBlock');
//                        var saveButton = $('.save-report-setup');
//
//                        if ($.inArray(input.val(), reportTitles) != -1) {
//                            titleHelpText.show();
//                            saveButton.prop('disabled', 'disabled')
//                        }
//                        else {
//                            titleHelpText.hide();
//                            saveButton.prop('disabled', '')
//                        }
//                    });

                    $('#reportIcon').on('input', function() {
                        var input = $('#reportIcon');
                        var icon = $('#reportIconPreview');

                        icon.removeClass();
                        icon.addClass('fas fa-' + input.val());
                    });

                    var editor = ace.edit("reportQuery", {
                        theme: "ace/theme/monokai",
                        mode: "ace/mode/sql",
                        minLines: 10
                    });
                </script>
            <?php endif; ?>
            <?php if (isset($_REQUEST['report'])): ?>
                <?php if ($exportEnabled): ?>
                <div style="float: right; <?= $pageInfo['sql'] == '' ? 'visibility: hidden;' : '' ?>" class="output-button">
                <div class="btn-group">
                    <button type="button" class="btn btn-info download"><span class="fas fa-download"></span> Export CSV File</button>
                    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu export-menu" role="menu">
                        <li>
                            <label>Separator: <input class="output-separator-input" type="text" size="4" value=","></label><br />
                            <button class="output-separator btn btn-info btn-xs active" title="Comma">,</button>
                            <button class="output-separator btn btn-info btn-xs" title="Semicolon">;</button>
                            <button class="output-separator btn btn-info btn-xs" title="Tab">⇥</button>
                            <button class="output-separator btn btn-info btn-xs" title="Space">␣</button>
                            <button class="output-separator btn btn-info btn-xs" title="JSON Formatted">json</button>
                            <button class="output-separator btn btn-info btn-xs" title="Array Formatted">array</button>
                        </li>
                        <li>
                            <br />
                            <label>Include:</label>
                            <div class="btn-group btn-group-toggle output-filter-all" data-toggle="buttons" title="Export all rows or filtered rows only">
                                <label class="btn btn-info btn-sm active">
                                    <input type="radio" name="getrows1" class="output-all" checked> All
                                </label>
                                <label class="btn btn-info btn-sm">
                                    <input type="radio" name="getrows1" class="output-filter"> Filtered
                                </label>
                            </div>
                        </li>
                        <li>
                            <br />
                            <label>Export to:</label>
                            <div class="btn-group btn-group-toggle output-download-popup" data-toggle="buttons" title="Download file or open in popup window">
                                <label class="btn btn-info btn-sm active output-type">
                                    <input type="radio" name="delivery1" class="output-download" checked> Download
                                </label>
                                <label class="btn btn-info btn-sm output-type">
                                    <input type="radio" name="delivery1" class="output-popup"> Popup
                                </label>
                            </div>
                        </li>
                        <li class="dropdown-divider filename-field-display"></li>
                        <li class="filename-field-display"><label title="Choose a download filename">Filename: <input class="output-filename" type="text" size="25" value=""></label></li>
                        <li class="filename-field-display"><label title="Append date and time of report render to filename">Include timestamp: <input class="filename-datetime" type="checkbox" checked></li>
                    </ul>
                </div>
                <script>
                    $('.output-filename').val(UIOWA_AdminDash.csvFileName);
                </script>
                <?php endif; ?>
            </div>

            <br />
            <br />
            <br />
            <br />

                <h3 style="text-align: center;">
                <?= $pageInfo['reportName'] ?>
            </h3>

            <div style="text-align: center; font-size: 14px">
                <?= $pageInfo['description']; ?>
            </div>


            <?php if($pageInfo['type'] == 'table' && $pageInfo['sql'] != '') : ?>
                 <!-- display tablesorter pager buttons for reports -->
              <div id="pager" class="pager">
              <form>
              <img src="<?= $this->getUrl("resources/tablesorter/tablesorter/images/icons/first.png") ?>" class="first"/>
              <img src="<?= $this->getUrl("resources/tablesorter/tablesorter/images/icons/prev.png") ?>" class="prev"/>
                 <!-- the "pagedisplay" can be any element, including an input -->
              <span class="pagedisplay" data-pager-output-filtered="{startRow:input} &ndash; {endRow} / {filteredRows} of {totalRows} total rows"></span>
              <img src="<?= $this->getUrl("resources/tablesorter/tablesorter/images/icons/next.png") ?>" class="next"/>
              <img src="<?= $this->getUrl("resources/tablesorter/tablesorter/images/icons/last.png") ?>" class="last"/>
              <select class="pagesize">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
            </form>
            </div>
            <?php elseif($pageInfo['type'] == 'chart') : ?>
                <br />
             <!-- display graphs -->
            <div style="display: inline-block; margin: 0 auto;">
              <div style="width: 100%; display: table; max-height: 500px;">
                <?php foreach (self::$visualizationQueries as $vis => $visInfo): ?>
                    <canvas id=<?= $visInfo['visID'] ?> width="400" height="400"></canvas>
                    <script>
                        UIOWA_AdminDash.createPieChart(
                            <?= $visInfo['visID'] ?>,
                            <?= $visInfo['type'] ?>,
                            <?= $visInfo['data'] ?>,
                            <?= $visInfo['options'] ?>
                        );
                    </script>
                <?php endforeach; ?>
            </div>
              </div>
        </div>

    <?php endif; ?>
        <?php else : ?>
            <br />
            <br />
            <br/>
            <br/>
        <div style="text-align: center;">
            <h3>Welcome to the REDCap <?= (!$executiveAccess) ? "Admin" : "Executive" ?> Dashboard!</h3>
        </div>
            <div style="text-align: center;">Click one of the tabs above to view a report. <?php if (!$executiveAccess) : ?>You can choose a report to open by default (instead of seeing this page) via the module's configuration settings.<?php endif; ?>
                <br />
                <br />
                <?php if ($executiveAccess && SUPER_USER) : ?>To grant a non-admin user access to this dashboard, you must add their username to the whitelist in the module configuration settings, then provide them with this page's URL. Use the "Show/Hide Reports" menu to configure report access.<?php endif; ?>
            </div>
            <br/>
            <br/>
        <?php endif; ?>


        <?php
        if(!$isSelectQuery && $pageInfo['type'] == 'table' && $pageInfo['sql'] != '') {
            $pageInfo['sql'] = '';
            $pageInfo['sqlErrorMsg'] = 'ERROR: SQL query is not a SELECT query.';
        }
        elseif($pageInfo['sql'] == '') {
            $pageInfo['sqlErrorMsg'] = 'ERROR: No SQL query defined.';
        }

        // display normal reports
        if($pageInfo['sql'] != '') {
         // execute the SQL statement
         $result = $this->sqlQuery($pageInfo['sql']);

          $this->formatQueryResults($result, "html", $pageInfo);

         printf("   </tbody>\n");
         printf("</table>\n");  // <table> created by PrintTableHeader

         if ($_REQUEST['report'] == 0) {
            $result = db_query(self::$miscQueryReference[0]['sql']);
            printf($this->formatQueryResults($result, "text", $pageInfo) . " users are currently suspended.");
         }
        }
        elseif ($pageInfo['type'] == 'table') {
            printf("<div id='deleted'> " . $pageInfo['sqlErrorMsg'] . "</div>");
        }

        if (SUPER_USER) {
            if ($_REQUEST['page'] == 'executiveView') {
                $viewUrl = 'index.php';
                $buttonText = 'Switch to Admin View';
            }
            else {
                $viewUrl = 'executiveView.php';
                $buttonText = 'Switch to Executive View';
            }

            if (isset($_REQUEST['report'])) {
                $viewUrl .= '?report=' . $_REQUEST['report'];
            }

            echo ("<div style=\"text-align: center\"><a id=\"switchView\" class=\"btn btn-success\" style=\"color: #FFFFFF\" href=" . urldecode($this->getUrl($viewUrl)) . ">" . $buttonText . "</a></div>");
        }
   }

    private function generateReportReference() {

        $pwordSearchTerms =
            array(
                'p%word',
                'p%wd',
                'user%name',
                'usr%name',
                'user%id',
                'usr%id'
            );

        $userDefinedTerms = self::getSystemSetting('additional-search-terms');

        foreach($userDefinedTerms as $term) {
            $pwordSearchTerms[] = db_real_escape_string($term);
        }

        $pwordProjectSql = array();
        $pwordInstrumentSql = array();
        $pwordFieldSql = array();

        foreach($pwordSearchTerms as $term) {
            $pwordProjectSql[] = '(app_title LIKE \'%' . $term . '%\')';

            $pwordInstrumentSql[] = '(form_name LIKE \'%' . $term . '%\')';

            $pwordFieldSql[] = '(field_name LIKE \'%' . $term . '%\')';
            $pwordFieldSql[] = '(element_label LIKE \'%' . $term . '%\')';
            $pwordFieldSql[] = '(element_note LIKE \'%' . $term . '%\')';
        }

        $pwordProjectSql =  "(" . implode(" OR ", $pwordProjectSql) . ")";
        $pwordInstrumentSql = "(" . implode(" OR ", $pwordInstrumentSql) . ")";
        $pwordFieldSql = "(" . implode(" OR ", $pwordFieldSql) . ")";

        $hideDeleted = !self::getSystemSetting('show-deleted-projects');
        $hideArchived = !self::getSystemSetting('show-archived-projects');
        $hidePractice = !self::getSystemSetting('show-practice-projects');


        $hideFiltersSql = array();

        if ($hideArchived) {
            $hideFiltersSql[] = "projects.status != 3";
        }
        if ($hideDeleted) {
            $hideFiltersSql[] = "projects.date_deleted IS NULL";
        }
        if ($hidePractice) {
            $hideFiltersSql[] = "projects.purpose != 0";
        }

        $formattedFilterSql = ($hideDeleted || $hideArchived || $hidePractice) ? ("AND " . implode(" AND ", $hideFiltersSql)) : '';
        $formattedWhereFilterSql = ($hideDeleted || $hideArchived || $hidePractice) ? ("WHERE " . implode(" AND ", $hideFiltersSql)) : '';

        $reportReference = array
        (
            array // Projects by User
            (
                "reportName" => "Projects by User",
                "customID" => "projectsByUser",
                "description" => "List of all users and the projects to which they have access.",
                "tabIcon" => "male",
                "defaultVisibility" => true,
                "readOnly" => true,
                "sql" => "SELECT
    info.username AS 'Username',
    CAST(CASE
        WHEN info.user_suspended_time IS NULL THEN 'N/A'
        ELSE info.user_suspended_time
    END AS CHAR(50)) AS 'User Suspended Date (Hidden)',
    info.user_lastname AS 'Last Name',
    info.user_firstname AS 'First Name',
    info.user_email AS 'Email',
    GROUP_CONCAT(CAST(projects.project_id AS CHAR(50)) SEPARATOR ', ') AS 'Project Titles',
    GROUP_CONCAT(CAST(CASE projects.status
        WHEN 0 THEN 'Development'
        WHEN 1 THEN 'Production'
        WHEN 2 THEN 'Inactive'
        WHEN 3 THEN 'Archived'
        ELSE projects.status
    END AS CHAR(50))) AS 'Project Statuses (Hidden)',
    GROUP_CONCAT(CAST(CASE
        WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
    END AS CHAR(50))) AS 'Project Deleted Date (Hidden)',
    COUNT(projects.project_id) AS 'Total Projects'
FROM redcap_user_information AS info,
    redcap_projects AS projects,
    redcap_user_rights AS access
WHERE info.username = access.username AND
    access.project_id = projects.project_id
    $formattedFilterSql
GROUP BY info.ui_id
ORDER BY info.user_lastname,
    info.user_firstname,
    info.username"
            ),
            array // Users by Project
            (
                "reportName" => "Users by Project",
                "customID" => "usersByProject",
                "description" => "List of all projects and the users which have access.",
                "tabIcon" => "users",
                "defaultVisibility" => true,
                "readOnly" => true,
                "sql" => "SELECT
    projects.project_id AS PID,
    app_title AS 'Project Title',
    CAST(CASE status
        WHEN 0 THEN 'Development'
        WHEN 1 THEN 'Production'
        WHEN 2 THEN 'Inactive'
        WHEN 3 THEN 'Archived'
        ELSE status
    END AS CHAR(50)) AS 'Status',
    CAST(CASE
        WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
    END AS CHAR(50)) AS 'Project Deleted Date (Hidden)',
    record_count AS 'Record Count',
    CAST(CASE purpose
        WHEN 0 THEN 'Practice / Just for fun'
        WHEN 4 THEN 'Operational Support'
        WHEN 2 THEN 'Research'
        WHEN 3 THEN 'Quality Improvement'
        WHEN 1 THEN 'Other'
        ELSE purpose
    END AS CHAR(50)) AS 'Purpose',
    GROUP_CONCAT((redcap_user_rights.username) SEPARATOR ', ') AS 'Users',
    GROUP_CONCAT(CAST(CASE
        WHEN redcap_user_information.user_suspended_time IS NULL THEN 'N/A'
        ELSE redcap_user_information.user_suspended_time
    END AS CHAR(50))) AS 'User Suspended Date (Hidden)',
    DATE_FORMAT(creation_time, '%Y-%m-%d') AS 'Creation Date',
    DATE_FORMAT(last_logged_event, '%Y-%m-%d') AS 'Last Logged Event Date',
    DATEDIFF(now(), last_logged_event) AS 'Days Since Last Event',
    COUNT(redcap_user_rights.username) AS 'Total Users'
FROM redcap_projects AS projects
LEFT JOIN redcap_record_counts ON projects.project_id = redcap_record_counts.project_id
LEFT JOIN redcap_user_rights ON projects.project_id = redcap_user_rights.project_id
LEFT JOIN redcap_user_information ON redcap_user_rights.username = redcap_user_information.username
$formattedWhereFilterSql
GROUP BY projects.project_id
ORDER BY app_title"
            ),
            array // Research Projects
            (
                "reportName" => "Research Projects",
                "customID" => "researchProjects",
                "description" => "List of all projects that are identified as being used for research purposes.",
                "tabIcon" => "flask",
                "defaultVisibility" => true,
                "readOnly" => true,
                "sql" => "SELECT
    projects.project_id AS PID,
    app_title AS 'Project Title',
    CAST(CASE status
        WHEN 0 THEN 'Development'
        WHEN 1 THEN 'Production'
        WHEN 2 THEN 'Inactive'
        WHEN 3 THEN 'Archived'
        ELSE status
    END AS CHAR(50)) AS 'Status',
    CAST(CASE
        WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
    END AS CHAR(50)) AS 'Project Deleted Date (Hidden)',
    record_count AS 'Record Count',
    purpose_other AS 'Purpose Specified',
    project_pi_lastname AS 'PI Last Name',
    project_pi_firstname AS 'PI First Name',
    project_pi_email AS 'PI Email',
    project_irb_number AS 'IRB Number',
    DATE_FORMAT(creation_time, '%Y-%m-%d') AS 'Creation Date',
    DATE_FORMAT(last_logged_event, '%Y-%m-%d') AS 'Last Logged Event Date',
    DATEDIFF(now(), last_logged_event) AS 'Days Since Last Event'
FROM redcap_projects as projects
LEFT JOIN redcap_record_counts ON projects.project_id = redcap_record_counts.project_id
WHERE purpose = 2  -- 'Research'
    $formattedFilterSql
ORDER BY app_title"
            ),
            array // Development Projects
            (
                "reportName" => "Development Projects",
                "customID" => "developmentProjects",
                "description" => "List of all projects that are in Development Mode.",
                "tabIcon" => "wrench",
                "defaultVisibility" => true,
                "readOnly" => true,
                "sql" => "SELECT
    projects.project_id AS 'PID',
    app_title AS 'Project Title',
    CAST(CASE
        WHEN record_count IS NULL THEN 0
        ELSE record_count
    END AS CHAR(10)) AS 'Record Count',
    CAST(CASE purpose
        WHEN 0 THEN 'Practice / Just for fun'
        WHEN 4 THEN 'Operational Support'
        WHEN 2 THEN 'Research'
        WHEN 3 THEN 'Quality Improvement'
        WHEN 1 THEN 'Other'
        ELSE purpose
    END AS CHAR(50)) AS 'Purpose',
    CAST(CASE
        WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
    END AS CHAR(50)) AS 'Project Deleted Date (Hidden)',
    DATE_FORMAT(creation_time, '%Y-%m-%d') AS 'Creation Date',
    DATE_FORMAT(last_logged_event, '%Y-%m-%d') AS 'Last Logged Event Date',
    DATEDIFF(now(), last_logged_event) AS 'Days Since Last Event'
FROM redcap_projects AS projects
LEFT JOIN redcap_record_counts ON redcap_record_counts.project_id = projects.project_id
WHERE projects.status = 0
    $formattedFilterSql
ORDER BY app_title"
            ),
            array // All Projects
            (
                "reportName" => "All Projects",
                "customID" => "allProjects",
                "description" => "List of all projects.",
                "tabIcon" => "folder-open",
                "defaultVisibility" => true,
                "readOnly" => true,
                "sql" => "SELECT
    projects.project_id AS 'PID',
    app_title AS 'Project Title',
    CAST(CASE
        WHEN record_count IS NULL THEN 0
        ELSE record_count
    END AS CHAR(50)) AS 'Record Count',
    CAST(CASE status
        WHEN 0 THEN 'Development'
        WHEN 1 THEN 'Production'
        WHEN 2 THEN 'Inactive'
        WHEN 3 THEN 'Archived'
        ELSE status
    END AS CHAR(50)) AS 'Status',
    CAST(CASE purpose
        WHEN 0 THEN 'Practice / Just for fun'
        WHEN 4 THEN 'Operational Support'
        WHEN 2 THEN 'Research'
        WHEN 3 THEN 'Quality Improvement'
        WHEN 1 THEN 'Other'
        ELSE purpose
    END AS CHAR(50)) AS 'Purpose',
    CAST(CASE
        WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
    END AS CHAR(50)) AS 'Project Deleted Date (Hidden)',
    DATE_FORMAT(creation_time, '%Y-%m-%d') AS 'Creation Date',
    DATE_FORMAT(last_logged_event, '%Y-%m-%d') AS 'Last Logged Event Date',
    DATEDIFF(now(), last_logged_event) AS 'Days Since Last Event'
FROM redcap_projects AS projects
LEFT JOIN redcap_record_counts ON projects.project_id = redcap_record_counts.project_id
$formattedWhereFilterSql
ORDER BY app_title"
            ),
            array
            (
                "reportName" => "Credentials Check (Project Titles)",
                "customID" => "projectCredentials",
                "description" => "List of projects titles that contain strings related to login credentials (usernames/passwords). Search terms include the following: " . implode(', ', $pwordSearchTerms),
                "tabIcon" => "key",
                "defaultVisibility" => false,
                "readOnly" => true,
                "sql" => "SELECT
    projects.project_id AS 'PID',
    app_title AS 'Project Title',
    CAST(CASE status
        WHEN 0 THEN 'Development'
        WHEN 1 THEN 'Production'
        WHEN 2 THEN 'Inactive'
        WHEN 3 THEN 'Archived'
        ELSE status
    END AS CHAR(50)) AS 'Status',
    CAST(CASE WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
    END AS CHAR(50)) AS 'Project Deleted Date (Hidden)'
FROM redcap_projects AS projects,
    redcap_user_information AS users
WHERE (projects.created_by = users.ui_id) AND
    " . $pwordProjectSql
            ),
            array
            (
                "reportName" => "Credentials Check (Instruments)",
                "customID" => "instrumentCredentials",
                "description" => "List of projects that contain strings related to login credentials (usernames/passwords) in the instrument or form name. Search terms include the following: " . implode(', ', $pwordSearchTerms),
                "tabIcon" => "key",
                "defaultVisibility" => false,
                "readOnly" => true,
                "sql" => "SELECT projects.project_id AS 'PID',
    projects.app_title AS 'Project Title',
    meta.form_menu_description AS 'Instrument Name',
    CAST(CASE
        WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
    END AS CHAR(50)) AS 'Project Deleted Date (Hidden)'
FROM redcap_projects AS projects,
    redcap_metadata AS meta,
    redcap_user_information AS users
WHERE (projects.created_by = users.ui_id) AND
    (projects.project_id = meta.project_id) AND
    (meta.form_menu_description IS NOT NULL) AND
    " . $pwordInstrumentSql
                ),
            array
            (
                "reportName" => "Credentials Check (Fields)",
                "customID" => "fieldCredentials",
                "description" => "List of projects that contain strings related to login credentials (usernames/passwords) in fields. Search terms include the following: " . implode(', ', $pwordSearchTerms),
                "tabIcon" => "key",
                "defaultVisibility" => false,
                "readOnly" => true,
                "sql" => "SELECT
    projects.project_id AS 'PID',
    projects.app_title AS 'Project Title',
    meta.form_name AS 'Form Name',
    meta.field_name AS 'Variable Name',
    meta.element_label AS 'Field Label',
    meta.element_note AS 'Field Note',
    CAST(CASE
        WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
    END AS CHAR(50)) AS 'Project Deleted Date (Hidden)'
FROM redcap_projects AS projects,
    redcap_metadata AS meta,
    redcap_user_information AS users
WHERE (projects.created_by = users.ui_id) AND
    (projects.project_id = meta.project_id) AND
    " . $pwordFieldSql . "
ORDER BY
    projects.project_id,
    form_name,
    field_name;
        "
                ),
            array
            (
                "reportName" => "Projects with External Modules",
                "customID" => "modulesInProjects",
                "description" => "List of External Modules and the projects they are enabled in.",
                "tabIcon" => "plug",
                "defaultVisibility" => false,
                "readOnly" => true,
                "sql" => "SELECT
    REPLACE(directory_prefix, '_', ' ') AS 'Module Title',
    GROUP_CONCAT(CAST(projects.project_id AS CHAR(50)) SEPARATOR ', ') AS 'Project Titles',
    GROUP_CONCAT(CAST(users.user_email AS CHAR(50)) SEPARATOR ', ') AS 'User Emails',
    GROUP_CONCAT(CAST(CASE projects.status
        WHEN 0 THEN 'Development'
        WHEN 1 THEN 'Production'
        WHEN 2 THEN 'Inactive'
        WHEN 3 THEN 'Archived'
        ELSE projects.status
    END AS CHAR(50))) AS 'Project Statuses (Hidden)',
    GROUP_CONCAT(CAST(CASE
        WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
    END AS CHAR(50))) AS 'Project Deleted Date (Hidden)',
    COUNT(projects.project_id) AS 'Total Projects'
FROM redcap_external_module_settings AS settings
LEFT JOIN redcap_external_modules ON redcap_external_modules.external_module_id = settings.external_module_id
LEFT JOIN redcap_projects AS projects ON projects.project_id = settings.project_id
LEFT JOIN redcap_user_rights AS rights ON rights.project_id = projects.project_id
LEFT JOIN redcap_user_information AS users ON users.username = rights.username
WHERE settings.key = 'enabled' AND (settings.value = true OR settings.value = 'enabled') AND settings.project_id IS NOT NULL
    $formattedFilterSql
GROUP BY settings.external_module_id
ORDER BY directory_prefix
        "
            )
        );

        ?>
            <script>
                var defaultReports = <?= json_encode($reportReference) ?>;
                UIOWA_AdminDash.defaultReportNames = $.map(defaultReports, function(report) {
                    return report['reportName'];
                });
            </script>
        <?php

        $customReports = $this->getSystemSetting('custom-reports');

        foreach ($customReports as $report) {
            array_push($reportReference, $report);
        }

//        array_push($reportReference,
//            array // Visualizations
//            (
//                "reportName" => "Visualizations",
//                "customID" => "visualizations",
//                "description" => "Additional metadata presented in a visual format.",
//                "tabIcon" => "fas fa-chart-pie",
//                "defaultVisibility" => true
//            ));

        return $reportReference;
    }

    private function formatQueryResults($result, $format, $pageInfo)
    {
        $redcapProjects = $this->getRedcapProjectNames();
        $isFirstRow = TRUE;

        if ($result -> num_rows == 0 & $result != 0) {
            printf("No records found.");
        }

        while ($row = db_fetch_assoc($result))
        {
            $originalRow = $row;

            if ($isFirstRow) {
                // use column aliases for column headers
                $headers = array_keys($row);

                // remove any columns marked as hidden
                $searchword = '(Hidden)';
                $hiddenColumns = array_filter($headers, function($var) use ($searchword) { return preg_match("/\b$searchword\b/i", $var); });

                foreach ($hiddenColumns as $column)
                {
                    $index = array_search($column, $headers);
                    unset($headers[$index]);
                }
            }

            if ($row['Module Title']) {
                $row['Module Title'] = ucwords($row['Module Title']);
            }

            if ($pageInfo['customID'] == 'modulesInProjects' ||
                strpos($_REQUEST['file'], 'modulesInProjects') !== false) {
                    $rowArray = explode(', ', $row['Project Titles']);
                    $rowArray = array_unique($rowArray);
                    $row['Project Titles'] = implode(', ', $rowArray);
                    $row['Total Projects'] = sizeof($rowArray);

                    $rowArray = explode(', ', $row['User Emails']);
                    $rowArray = array_unique($rowArray);
                    $row['User Emails'] = implode(', ', $rowArray);
            }

            if ($format == 'html') {
                if ($isFirstRow)
                {
                    if (array_search('Purpose Specified', $headers)) {
                        $headerIndex = array_search('Purpose Specified', $headers);
                        $purposeMasterArray = Array();

                        foreach (self::$purposeMaster as $index=>$purposeStr)
                        {
                            $purposeMasterArray[$purposeStr] = "FALSE";
                        }

                        $headers = array_merge(
                            array_merge(
                                array_slice($headers, 0, $headerIndex, true),
                                array_keys($purposeMasterArray)),
                            array_slice($headers, $headerIndex, NULL, true)
                        );
                    }

                    // print table header
                    $purposeColumns = $this->printTableHeader($headers);
                    printf("   <tbody>\n");
                    $isFirstRow = FALSE;  // toggle flag
                }

                if ($purposeMasterArray) {
                    $headerIndex = array_search('Purpose Specified', $headers);
                    $purposeArray = explode(',', $row['Purpose Specified']);
                    $row = array_merge(
                        array_merge(
                            array_slice($row, 0, $headerIndex + 2, true),
                            $purposeMasterArray),
                        array_slice($row, $headerIndex, NULL, true)
                    );
                    foreach (self::$purposeMaster as $index=>$purposeStr) {
                        if ($row['Purpose Specified'] !== '' &&
                            array_search($index, $purposeArray) !== FALSE) {
                            $row[$purposeStr] = 'TRUE';
                        }
                        else {
                            $row[$purposeStr] = 'FALSE';
                        }
                    }
                }

                $webData = $this->webifyDataRow($row, $redcapProjects);
                $this->printTableRow($webData, $hiddenColumns);

                ?> <script>UIOWA_AdminDash.hideColumns = <?= json_encode($purposeColumns) ?>;</script> <?php
            }
            elseif ($format == 'text') {
                $titlesStr = implode("\",\"", $row);
                return $titlesStr;
            }
        }
    }

    private function printTableHeader($columns)
    {
        printf("
<table id='reportTable' class='tablesorter'>
   <thead>
      <tr>\n", 'reportTable');

        $purposeColumns = [];

        foreach ($columns as $index => $name) {
            if (in_array($name, self::$purposeMaster)) {
                array_push($purposeColumns, $index + 1);
            }

            printf("         <th> %s </th>\n", $name);
        }

        printf("
      </tr>
   </thead>\n");

        return $purposeColumns;
    }

    private function printTableRow($row, $hiddenColumns)
    {
        printf("      <tr>\n");

        foreach ($row as $key => $value)
        {
            if (!array_search($key, $hiddenColumns)) {
                printf("         <td> %s </td>\n", $value);
            }
        }

        printf("      </tr>\n");
    }

    private function webifyDataRow($row, $projectTitles)
    {
        // initialize value
        $webified = array();

        $executiveAccess = ($_REQUEST['page'] == 'executiveView' && (in_array(USERID, $this->getSystemSetting("executive-users")) || SUPER_USER) ? 1 : 0);

        foreach ($row as $key => $value)
        {
            $value = htmlentities($value);

            if ($key == "Project Titles")
            {
                $projectStatuses = $row['Project Statuses (Hidden)'];
                $projectDeleted = $row['Project Deleted Date (Hidden)'];

                $webified[$key] = $this->formatProjectList($value, $projectTitles, $projectStatuses, $projectDeleted, $executiveAccess);
            }
            elseif ($key == "Users")
            {
                $suspended = $row['User Suspended Date (Hidden)'];

                $webified[$key] = $this->formatUsernameList($value, $suspended, $executiveAccess);
            }
            elseif ($key == "User Emails")
            {
                $webified[$key] = $this->formatEmailList($value, $executiveAccess);
            }
            elseif ($key == "Purpose Specified")
            {
                $webified[$key] = $this->convertProjectPurpose2List($value);
            }
            elseif (!$executiveAccess) {
                if ($key == "PID")
                {
                    $webified[$key] = $this->convertPid2AdminLink($value);
                }
                elseif ($key == "Project Title")
                {
                    $pid = $row['PID'];
                    $hrefStr = $row['Project Title'];
                    $projectStatus = $row['Status'];
                    $projectDeleted = $row['Project Deleted Date (Hidden)'];

                    $webified[$key] = $this->convertPid2Link($pid, $hrefStr, $projectStatus, $projectDeleted);
                }
                elseif (($key == "PI Email") ||
                    ($key == "Email"))
                {
                    $webified[$key] = $this->convertEmail2Link($value);
                }
                elseif ($key == "Username")
                {
                    $suspended = $row['User Suspended Date (Hidden)'];

                    $webified[$key] = $this->convertUsername2Link($value, $suspended);
                }
                else
                {
                    $webified[$key] = $value;
                }
            }
            else
            {
                $webified[$key] = $value;
            }
        }

        return($webified);
    }

    private function convertEmail2Link($email)
    {
        $mailtoLink = sprintf("<a href=\"mailto:%s\">%s</a>",
            $email, $email);

        return($mailtoLink);
    }

    private function formatEmailList($emailStr, $executiveAccess)
    {
        // convert comma-delimited string to array
        $emailList = explode(", ", $emailStr);
        $emailLinks = array();

        foreach ($emailList as $index=>$email)
        {
            $formattedEmail = $email;

            if (!$executiveAccess) {
                $formattedEmail = $this->convertEmail2Link($email);
            }

            array_push($emailLinks, $formattedEmail . ($index < count($emailList) - 1 ? '<span class=\'hide-in-table\'>, </span>' : ''));
        }

        // convert array back to comma-delimited string
        $emailCell = implode("<br />", $emailLinks);
        $mailtoLink = 'mailto:?bcc=' . str_replace(', ', ';', $emailStr);


        if (count($emailLinks) > 1 && !$executiveAccess) {
            $emailCell .= "<button style='float:right' onclick='location.href=\"" . $mailtoLink . "\"'>Email All</button>";
        }

        return($emailCell);
    }

    private function convertPid2Link($pid, $hrefStr, $projectStatus, $projectDeleted)
    {
        $urlString =
            sprintf("https://%s%sProjectSetup/index.php?pid=%d",  // Project Setup page
                SERVER_NAME,
                APP_PATH_WEBROOT,
                $pid);

        $styleIdStr = '';

        if ($projectStatus == 'Archived' && $projectStatus != null) {
            $styleIdStr = "id=archived";
        }
        if ($projectDeleted != 'N/A' && $projectDeleted != null) {
            $styleIdStr = "id=deleted";
        }
        $pidLink = sprintf("<a href=\"%s\"
                          target=\"_blank\"" . $styleIdStr . ">%s</a>",
            $urlString, $hrefStr);

        return($pidLink);
    }

    private function convertPid2AdminLink($pid)
    {
        $urlString =
            sprintf("https://%s%sControlCenter/edit_project.php?project=%d",  // Project Setup page
                SERVER_NAME,
                APP_PATH_WEBROOT,
                $pid);  // 15

        $pidLink = sprintf("<a href=\"%s\"
                          target=\"_blank\">%s</a>",
            $urlString, $pid);

        return($pidLink);
    }

    private function formatProjectList($pidStr, $projectTitles, $projectStatuses, $projectDeleted, $executiveAccess)
    {
        // convert comma-delimited string to array
        $pidList = explode(", ", $pidStr);
        $formattedTitles = array();

        $statusList = explode(",", $projectStatuses);
        $deletedList = explode(",", $projectDeleted);

        foreach ($pidList as $index=>$pid)
        {
            $formattedProjectTitle = $projectTitles[$pid];

            if (!$executiveAccess) {
                $formattedProjectTitle = $this->convertPid2Link($pid, $formattedProjectTitle, $statusList[$index], $deletedList[$index]);
            }

            array_push($formattedTitles, $formattedProjectTitle . ($index < count($pidList) - 1 ? '<span class=\'hide-in-table\'>, </span>' : ''));
        }

        $titleCell = implode("<br />", $formattedTitles);

        return($titleCell);
    }

    private function convertUsername2Link($userID, $suspended)
    {
        $urlString =
            sprintf("https://%s%sControlCenter/view_users.php?username=%s",  // Browse User Page
                SERVER_NAME,
                APP_PATH_WEBROOT,
                $userID);

        $suspendedTag = '';

        if ($suspended != 'N/A' && self::getSystemSetting('show-suspended-tags') && $suspended != null) {
            $suspendedTag = "<span id='suspended'> [suspended]</span>";
        }

        $userLink = sprintf("<a href=\"%s\"
                          target=\"_blank\">%s</a>" . $suspendedTag,
            $urlString, $userID);

        return($userLink);
    }

    private function formatUsernameList($userIDs, $suspendedList, $executiveAccess)
    {
        // convert comma delimited string to array
        $userIDlist = explode(", ", $userIDs);
        $formattedUsers = array();

        $suspendedList = explode(",", $suspendedList);

        foreach ($userIDlist as $index=>$userID)
        {
            $formattedUsername = $userID;
            $suspended = $suspendedList[$index];

            if (!$executiveAccess) {
                $formattedUsername = $this->convertUsername2Link($formattedUsername, $suspended);
            }

            array_push($formattedUsers, $formattedUsername . ($index < count($userIDlist) - 1 ? '<span class=\'hide-in-table\'>, </span>' : '')
            );
        }

        $userCell = implode( "<br>", $formattedUsers);

        return($userCell);
    }

    private function convertProjectPurpose2List($purposeList)
    {
        // initialize variables
        $purposeResults = array();
        $purposeParts = explode(",", $purposeList);

        foreach ($purposeParts as $index)
        {
            array_push($purposeResults, self::$purposeMaster[$index]);
        }

        $purposeStr = implode(", ", $purposeResults);

        return($purposeStr);
    }

    private function sqlQuery($query)
    {
        // execute the SQL statement
        $result = db_query($query);

        if (! $result || $result == 0)  // sql failed
        {
            printf("<div id='deleted'>Could not execute SQL!<br />
                  Error #" . db_errno() . ": " . db_error() . "</div>");
        }

        return $result;
    }

    private function getRedcapProjectNames()
    {
        $sql = "SELECT project_id AS pid,
                 TRIM(app_title) AS title
          FROM redcap_projects
          ORDER BY pid";

        $query = db_query($sql);

        if (! $query)  // sql failed
        {
            die("Could not execute SQL:
            <pre>$sql</pre> <br />");
        }

        $projectNameHash = array();

        while ($row = db_fetch_assoc($query))
        {
            // $value = strip_tags($row['app_title']);
            $key = $row['pid'];
            $value = $row['title'];

            if (strlen($value) > 80)
            {
                $value = trim(substr($value, 0, 70)) . " ... " .
                    trim(substr($value, -15));
            }

            if ($value == "")
            {
                $value = "[Project title missing]";
            }

            $projectNameHash[$key] = $value;
        }

        return($projectNameHash);

    }

    private function formatUrl($tab)
    {
        $query = $_GET;
        $moduleParam = array('type' => 'module');
        $query = $moduleParam + $query;
        $query['report']     = $tab;

        $url = http_build_query($query);
        $url = APP_PATH_WEBROOT_FULL . 'api/?' . $url;

        return ($url);
    }

    public function getVisData() {
        $pageInfo = self::$visualizationQueries[ $_REQUEST['vis'] ];
        $result = db_query($pageInfo['sql']);
        $data = array();

        while ( $row = db_fetch_assoc( $result ) )
        {
            $data[] = $row;
        }

        echo json_encode($data);
    }

    private function loadVisibilitySettings($type, $reportReference) {
        $storedVisibility = $this->getSystemSetting("report-visibility-" . $type);

        if ($storedVisibility) {
            $visibilityArray = $storedVisibility;
        }
        else {
            $visibilityArray = array();

            foreach ($reportReference as $index => $reportInfo) {
                if ($type == 'admin') {
                    $visibilityArray[$reportInfo['reportName']] = $reportInfo['defaultVisibility'];
                }
                if ($type == 'executive') {
                    $visibilityArray[$reportInfo['reportName']] = array();
                }
            }
        }

        return $visibilityArray;
    }

    public function saveReportSettings() {
        $allSettings = json_decode(file_get_contents('php://input'));

        if ($allSettings->reportReference) {
            if ($allSettings->reportReference == 'none') {
                $this->removeSystemSetting('custom-reports');
            }
            else {
                $this->setSystemSetting('custom-reports', $allSettings->reportReference);
            }
        }
        if ($allSettings->adminVisibility) {
            $this->setSystemSetting('report-visibility-admin', $allSettings->adminVisibility);
        }
        if ($allSettings->executiveVisibility) {
            $this->setSystemSetting('report-visibility-executive', $allSettings->executiveVisibility);
        }
    }
}
?>