<?php
namespace UIOWA\AdminDash;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class AdminDash extends AbstractExternalModule {
    public static $purposeMaster = array
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

    public static $visualizationQueries = array
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

    public static $miscQueryReference = array
    (
    array
    (
    "queryName" => "Suspended Users",
    "sql" => "
            SELECT count(*) FROM redcap_user_information WHERE user_suspended_time IS NOT NULL
            "
   )
   );

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
        <script src="<?= $this->getUrl("/resources/bootstrap-toggle/bootstrap-toggle.min.js") ?>"></script>

        <link href="<?= $this->getUrl("/resources/tablesorter/tablesorter/theme.blue.min.css") ?>" rel="stylesheet">
        <link href="<?= $this->getUrl("/resources/tablesorter/tablesorter/jquery.tablesorter.pager.min.css") ?>" rel="stylesheet">
        <link href="<?= $this->getUrl("/resources/c3/c3.css") ?>" rel="stylesheet" type="text/css">
        <link href="<?= $this->getUrl("/resources/styles.css") ?>" rel="stylesheet" type="text/css"/>
        <link href="<?= $this->getUrl("/resources/tablesorter/tablesorter/theme.bootstrap_4.min.css") ?>" rel="stylesheet">
        <link href="<?= $this->getUrl("/resources/bootstrap-toggle/bootstrap-toggle.min.css") ?>" rel="stylesheet">

        <script src="<?= $this->getUrl("/adminDash.js") ?>"></script>

        <?php

        $restrictedAccess = ($_REQUEST['page'] == 'executiveView' && (in_array(USERID, $this->getSystemSetting("executive-users")) || SUPER_USER) ? 1 : 0);
        $reportReference = $this->generateReportReference();
        $defaultTab = $this->getSystemSetting("default-report") - 1;
        $defaultVisibilitySettings = array();

        foreach ($reportReference as $index => $reportInfo) {
            $defaultVisibilitySettings[$reportInfo['reportName']][0] = $reportInfo['defaultVisibility'];
            $defaultVisibilitySettings[$reportInfo['reportName']][1] = false;
        }

        if ((!isset($_REQUEST['tab']) && !$restrictedAccess) && $defaultTab != -1) {
            $_REQUEST['tab'] = $defaultTab;
        }

        $title = $restrictedAccess ? "Executive Dashboard" : $this->getModuleName();
        $pageInfo = $reportReference[$_REQUEST['tab']];
        $isSelectQuery = (strtolower(substr($pageInfo['sql'], 0, 6)) == "select");
        $visibilitySettings = self::getSystemSetting('report-visibility') != null ? json_decode(self::getSystemSetting('report-visibility'), true) : $defaultVisibilitySettings;
        $reportEnabled = $visibilitySettings[$pageInfo['reportName']][$restrictedAccess];
        $restrictedViewEnabled = false;
        $exportEnabled = ($this->getSystemSetting("executive-export-enabled") && $restrictedAccess) || (SUPER_USER && !$restrictedAccess);

        foreach ($visibilitySettings as $index => $report) {
            if ($report[1] == true) {
                $restrictedViewEnabled = true;
                break;
            }
        }

        if (!SUPER_USER) {
            if (
                (!$restrictedAccess) ||
                (!$reportEnabled && isset($_REQUEST['tab'])) ||
                (!$restrictedViewEnabled)
            ) {
                die("Access denied! You do not have permission to view this page.");
            }
        }

        if (!$pageInfo['sql'] && !$pageInfo['userDefined']) :
         foreach (self::$visualizationQueries as $vis => $visInfo):
            ?>
               <script>
               d3.json("<?= $this->getUrl("requestHandler.php?type=getVisData&vis=" . $vis) ?>", function (error, json) {
                   if (error) return console.warn(error);

                    UIOWA_AdminDash.createPieChart(
                      UIOWA_AdminDash.getCountsFromJson(
                          json,
                          <?php echo json_encode($visInfo['countColumns']) ?>
                    ),
                     <?= $visInfo['visName'] ?>,
                     <?= $visInfo['visID'] ?>
                   );
               });
               </script>
            <?php
         endforeach;
        endif;

        ?>
        <h2 style="text-align: center; color: #106CD6; font-weight: bold;">
             <?= $title ?>
             </h2>

             <p />

                <!-- create navigation tabs -->
             <ul class='nav nav-tabs'>
             <?php foreach($reportReference as $report => $reportInfo): ?>
             <li class="nav-item <?= $_REQUEST['tab'] == $report && isset($_REQUEST['tab']) ? "active" : "" ?>" style="display:none" >
             <a class="nav-link" href="<?= $this->formatUrl($report) ?>">
             <span class="<?= $reportInfo['tabIcon'] ?>"></span>&nbsp; <?= $reportInfo['reportName'] ?></a>
         </li>
        <?php endforeach; ?>
         </ul>

         <p />

        <script>
            var csvFileName = '<?= sprintf("%s.csv", $pageInfo['fileName']); ?>';
            var renderDatetime = '<?= date("Y-m-d_His") ?>';
            $('.output-filename').val(csvFileName);

            var reportReference = <?= json_encode($reportReference) ?>;
            var restrictedAccess = <?= $restrictedAccess ?>;
            var visibilitySettings = <?= json_encode($visibilitySettings) ?>;
            var saveVisibilityUrl = "<?= $this->getUrl("requestHandler.php?type=saveVisibilitySettings") ?>";

            UIOWA_AdminDash.setReportVisibility(visibilitySettings, restrictedAccess);
        </script>

        <div>
            <?php if (SUPER_USER) : ?>
            <div style="float: left">
                <button type="button" class="btn btn-primary open-visibility-settings" data-toggle="modal" data-target="#reportVisibilityModal">
                    <span class="fas fa-cog"></span> Show/Hide Reports
                </button>
            </div>
            <!-- Modal -->
            <div class="modal fade" id="reportVisibilityModal" tabindex="-1" role="dialog" aria-labelledby="reportVisibilityModal" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="reportVisibilityModalLongTitle" style="text-align: center">Report Visibility Settings</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <table class="table table-striped">
                                <thead class="report-visibility-table">
                                <tr>
                                    <th></th>
                                    <th style="text-align: center; font-size: 18px"><b>Admin View</b></th>
                                    <th style="text-align: center; font-size: 18px"><b>Executive View</b></th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary save-visibility-settings" data-dismiss="modal">Save</button>
                        </div>
                    </div>
                </div>
            </div>
                <script>
                    for (var i in reportReference) {
                        var reportName = reportReference[i]['reportName'];

                        $('.report-visibility-table').append(
                            '<tr>' +
                            '<td class="table-report-title" style="text-align:right; vertical-align:middle; padding-right:50px">' + reportName + '</td>' +
                            '<td class="table-admin-visible" style="text-align:center"><input type="checkbox" data-toggle="toggle" data-width="75" data-on="Show" data-off="Hide"></td>' +
                            '<td class="table-restricted-visible" style="text-align:center"><input type="checkbox" data-toggle="toggle" data-width="75" data-on="Show" data-off="Hide"></td>' +
                            '</tr>'
                        );
                    }

                    $('.open-visibility-settings').click(function() {
                        $('.report-visibility-table tr').each(function () {
                            var reportName = $(this).find('.table-report-title').html();

                            if (reportName == undefined) {return;}

                            var adminVisible = visibilitySettings[reportName][0];
                            var restrictedVisible = visibilitySettings[reportName][1];
                            var adminToggle = $(this).find('.table-admin-visible input');
                            var restrictedToggle = $(this).find('.table-restricted-visible input');


                            adminVisible ? adminToggle.bootstrapToggle('on') : adminToggle.bootstrapToggle('off');
                            restrictedVisible ? restrictedToggle.bootstrapToggle('on') : restrictedToggle.bootstrapToggle('off');
                        });
                    });

                    $('.save-visibility-settings').click(function() {
                        visibilitySettings = {};

                        $('.report-visibility-table tr').each(function () {
                            var reportName = $(this).find('.table-report-title').html();

                            if (reportName == undefined) {return;}

                            visibilitySettings[reportName] = [
                                !$(this).find('.table-admin-visible div').hasClass('off'),
                                !$(this).find('.table-restricted-visible div').hasClass('off')
                            ];
                        });

                        UIOWA_AdminDash.saveReportVisibility(visibilitySettings, saveVisibilityUrl);
                        UIOWA_AdminDash.setReportVisibility(visibilitySettings, restrictedAccess);
                    });
                </script>
            <?php endif; ?>
            <?php if (isset($_REQUEST['tab'])): ?>
                <?php if ($exportEnabled): ?>
                <div style="float: right; <?= $pageInfo['sql'] == '' ? 'visibility: hidden;' : '' ?>" class="output-button">
                <div class="btn-group">
                    <button type="button" class="btn btn-info download"><span class="fas fa-download"></span> Export CSV File</button>
                    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu" role="menu">
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
                <?php endif; ?>
            </div>

            <script>
                var csvFileName = '<?= sprintf("%s.csv", $pageInfo['fileName']); ?>';
                var renderDatetime = '<?= date("Y-m-d_His") ?>';
                $('.output-filename').val(csvFileName);
            </script>

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


            <?php if($pageInfo['sql']) : ?>
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
            <?php else : ?>
                <br />
             <!-- display graphs -->
            <div style="display: inline-block; margin: 0 auto;">
              <div style="width: 100%; display: table; max-height: 500px;">
                <?php foreach (self::$visualizationQueries as $vis => $visInfo): ?>
                    <div style="width: 500px; display: table-row;" id=<?= $visInfo['visID'] ?>></div>
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
            <h3>Welcome to the REDCap <?= (!$restrictedAccess) ? "Admin" : "Executive" ?> Dashboard!</h3>
        </div>
            <div style="text-align: center;">Click one of the tabs above to view a report. <?php if (!$restrictedAccess) : ?>You can choose a report to open by default (instead of seeing this page) via the module's configuration settings.<?php endif; ?>
                <br />
                <br />
                <?php if ($restrictedAccess && SUPER_USER) : ?>To grant a non-admin user access to this dashboard, you must add their username to the whitelist in the module configuration settings, then provide them with this page's URL.<?php endif; ?>
            </div>
            <br/>
            <br/>
        <?php endif; ?>


        <?php
        if(!$isSelectQuery && $pageInfo['userDefined'] && $pageInfo['sql'] != '') {
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

         if ($_REQUEST['tab'] == 0) {
            $result = db_query(self::$miscQueryReference[0]['sql']);
            printf($this->formatQueryResults($result, "text", $pageInfo) . " users are currently suspended.");
         }
        }
        elseif ($pageInfo['userDefined']) {
            printf("<div id='deleted'> " . $pageInfo['sqlErrorMsg'] . "</div>");
        }

        if (SUPER_USER) {
            if ($_REQUEST['page'] == 'executiveView') {
                echo ("<div style=\"text-align: center\"><a class=\"btn btn-success\" style=\"color: #FFFFFF\" href=" . urldecode($this->getUrl("index.php")) . ">Switch to Admin View</a></div>");
            }
            else {
                echo ("<div style=\"text-align: center\"><a class=\"btn btn-success\" style=\"color: #FFFFFF\" href=" . urldecode($this->getUrl("executiveView.php")) . ">Switch to Executive View</a></div>");
            }
        }
   }

    public function generateReportReference() {
        $restrictedAccess = ($_REQUEST['page'] == 'executiveView' && (in_array(USERID, $this->getSystemSetting("executive-users")) || SUPER_USER) ? 1 : 0);

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
                "fileName" => "projectsByUser",
                "description" => "List of all users and the projects to which they have access.",
                "tabIcon" => "fa fa-male",
                "defaultVisibility" => true,
                "sql" => "
        SELECT
        info.username AS 'Username',
        CAST(CASE WHEN info.user_suspended_time IS NULL THEN 'N/A'
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
        GROUP_CONCAT(CAST(CASE WHEN projects.date_deleted IS NULL THEN 'N/A'
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
        ORDER BY info.user_lastname, info.user_firstname, info.username
        "
            ),
            array // Users by Project
            (
                "reportName" => "Users by Project",
                "fileName" => "usersByProject",
                "description" => "List of all projects and the users which have access.",
                "tabIcon" => "fas fa-users",
                "defaultVisibility" => true,
                "sql" => "
        SELECT
        projects.project_id AS PID,
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
        GROUP_CONCAT(CAST(CASE WHEN redcap_user_information.user_suspended_time IS NULL THEN 'N/A'
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
        ORDER BY app_title
        "
            ),
            array // Research Projects
            (
                "reportName" => "Research Projects",
                "fileName" => "researchProjects",
                "description" => "List of all projects that are identified as being used for research purposes.",
                "tabIcon" => "fa fa-flask",
                "defaultVisibility" => true,
                "sql" => "
        SELECT
        projects.project_id AS PID,
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
        ORDER BY app_title
        "
            ),
            array // Development Projects
            (
                "reportName" => "Development Projects",
                "fileName" => "developmentProjects",
                "description" => "List of all projects that are in Development Mode.",
                "tabIcon" => "fas fa-wrench",
                "defaultVisibility" => true,
                "sql" => "
        SELECT
        projects.project_id AS 'PID',
        app_title AS 'Project Title',
        CAST(CASE WHEN record_count IS NULL THEN 0
        ELSE record_count END AS CHAR(10)) AS 'Record Count',
        CAST(CASE purpose
        WHEN 0 THEN 'Practice / Just for fun'
        WHEN 4 THEN 'Operational Support'
        WHEN 2 THEN 'Research'
        WHEN 3 THEN 'Quality Improvement'
        WHEN 1 THEN 'Other'
        ELSE purpose
        END AS CHAR(50)) AS 'Purpose',
        CAST(CASE WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
        END AS CHAR(50)) AS 'Project Deleted Date (Hidden)',
        DATE_FORMAT(creation_time, '%Y-%m-%d') AS 'Creation Date',
        DATE_FORMAT(last_logged_event, '%Y-%m-%d') AS 'Last Logged Event Date',
        DATEDIFF(now(), last_logged_event) AS 'Days Since Last Event'
        FROM redcap_projects AS projects
        LEFT JOIN redcap_record_counts ON redcap_record_counts.project_id = projects.project_id
        WHERE projects.status = 0
        $formattedFilterSql
        ORDER BY app_title
        "
            ),
            array // All Projects
            (
                "reportName" => "All Projects",
                "fileName" => "allProjects",
                "description" => "List of all projects.",
                "tabIcon" => "fas fa-folder-open",
                "defaultVisibility" => true,
                "sql" => "
        SELECT
        projects.project_id AS 'PID',
        app_title AS 'Project Title',
        CAST(CASE WHEN record_count IS NULL THEN 0
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
        CAST(CASE WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
        END AS CHAR(50)) AS 'Project Deleted Date (Hidden)',
        DATE_FORMAT(creation_time, '%Y-%m-%d') AS 'Creation Date',
        DATE_FORMAT(last_logged_event, '%Y-%m-%d') AS 'Last Logged Event Date',
        DATEDIFF(now(), last_logged_event) AS 'Days Since Last Event'
        FROM redcap_projects AS projects
        LEFT JOIN redcap_record_counts ON projects.project_id = redcap_record_counts.project_id
        $formattedWhereFilterSql
        ORDER BY app_title
        "
            ),
            array
            (
                "reportName" => "Credentials Check (Project Titles)",
                "fileName" => "projectCredentials",
                "description" => "List of projects titles that contain strings related to login credentials (usernames/passwords). Search terms include the following: " . implode(', ', $pwordSearchTerms),
                "tabIcon" => "fa fa-key",
                "defaultVisibility" => false,
                "sql" => "
        SELECT projects.project_id AS 'PID',
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
                "fileName" => "instrumentCredentials",
                "description" => "List of projects that contain strings related to login credentials (usernames/passwords) in the instrument or form name. Search terms include the following: " . implode(', ', $pwordSearchTerms),
                "tabIcon" => "fa fa-key",
                "defaultVisibility" => false,
                "sql" => "
        SELECT projects.project_id AS 'PID',
        projects.app_title AS 'Project Title',
        meta.form_menu_description AS 'Instrument Name',
        CAST(CASE WHEN projects.date_deleted IS NULL THEN 'N/A'
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
                "fileName" => "fieldCredentials",
                "description" => "List of projects that contain strings related to login credentials (usernames/passwords) in fields. Search terms include the following: " . implode(', ', $pwordSearchTerms),
                "tabIcon" => "fa fa-key",
                "defaultVisibility" => false,
                "sql" => "
        SELECT projects.project_id AS 'PID',
        projects.app_title AS 'Project Title',
        meta.form_name AS 'Form Name',
        meta.field_name AS 'Variable Name',
        meta.element_label AS 'Field Label',
        meta.element_note AS 'Field Note',
        CAST(CASE WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
        END AS CHAR(50)) AS 'Project Deleted Date (Hidden)'
        FROM redcap_projects AS projects,
        redcap_metadata AS meta,
        redcap_user_information AS users
        WHERE (projects.created_by = users.ui_id) AND
        (projects.project_id = meta.project_id) AND
        " . $pwordFieldSql . "
        ORDER BY projects.project_id, form_name, field_name;
        "
                ),
            array
            (
                "reportName" => "Projects with External Modules",
                "fileName" => "modulesInProjects",
                "description" => "List of External Modules and the projects they are enabled in.",
                "tabIcon" => "fas fa-plug",
                "defaultVisibility" => false,
                "sql" => "
        SELECT
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
        GROUP_CONCAT(CAST(CASE WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
        END AS CHAR(50))) AS 'Project Deleted Date (Hidden)',
        COUNT(projects.project_id) AS 'Total Projects'
        FROM redcap_external_module_settings AS settings
        LEFT JOIN redcap_external_modules ON redcap_external_modules.external_module_id = settings.external_module_id
        LEFT JOIN redcap_projects AS projects ON projects.project_id = settings.project_id
        LEFT JOIN redcap_user_rights AS rights ON rights.project_id = projects.project_id
        LEFT JOIN redcap_user_information AS users ON users.username = rights.username
        WHERE settings.key = 'enabled' AND settings.value = 'true' AND settings.project_id IS NOT NULL
        $formattedFilterSql
        GROUP BY settings.external_module_id
        ORDER BY directory_prefix
        "
            )
        );

        $customNames = $this->getSystemSetting('custom-report-name');
        $customDescs = $this->getSystemSetting('custom-report-desc');
        $customIcons = $this->getSystemSetting('custom-report-icon');
        $customSqls = $this->getSystemSetting('custom-report-sql');

        for($i = 0; $i < count($customNames); $i++) {
            $customFileName = preg_replace('/\s*/', '', $customNames[$i]);

            array_push($reportReference,
                array
                (
                    "reportName" => $customNames[$i] != '' ? $customNames[$i] : 'Untitled',
                    "fileName" => $customNames[$i] != '' ? $customFileName : 'untitled',
                    "description" => $customDescs[$i] != '' ? $customDescs[$i] : 'No description defined.',
                    "tabIcon" => $customIcons[$i] != '' ? $customIcons[$i] : 'fas fa-question-circle',
                    "sql" => $customSqls[$i],
                    "userDefined" => TRUE
                ));
        }

        array_push($reportReference,
            array // Visualizations
            (
                "reportName" => "Visualizations",
                "fileName" => "visualizations",
                "description" => "Additional metadata presented in a visual format.",
                "tabIcon" => "fas fa-chart-pie",
                "defaultVisibility" => true
            ));

        return $reportReference;
    }

    public function formatQueryResults($result, $format, $pageInfo)
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

            if ($pageInfo['fileName'] == 'modulesInProjects' ||
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

                ?> <script>var hideColumns = <?= json_encode($purposeColumns) ?>;</script> <?php
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

        $restrictedAccess = ($_REQUEST['page'] == 'executiveView' && (in_array(USERID, $this->getSystemSetting("executive-users")) || SUPER_USER) ? 1 : 0);

        foreach ($row as $key => $value)
        {
            if ($key == "Project Titles")
            {
                $projectStatuses = $row['Project Statuses (Hidden)'];
                $projectDeleted = $row['Project Deleted Date (Hidden)'];

                $webified[$key] = $this->formatProjectList($value, $projectTitles, $projectStatuses, $projectDeleted, $restrictedAccess);
            }
            elseif ($key == "Users")
            {
                $suspended = $row['User Suspended Date (Hidden)'];

                $webified[$key] = $this->formatUsernameList($value, $suspended, $restrictedAccess);
            }
            elseif ($key == "User Emails")
            {
                $webified[$key] = $this->formatEmailList($value, $restrictedAccess);
            }
            elseif ($key == "Purpose Specified")
            {
                $webified[$key] = $this->convertProjectPurpose2List($value);
            }
            elseif (!$restrictedAccess) {
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

    private function formatEmailList($emailStr, $restrictedAccess)
    {
        // convert comma-delimited string to array
        $emailList = explode(", ", $emailStr);
        $emailLinks = array();

        foreach ($emailList as $index=>$email)
        {
            $formattedEmail = $email;

            if (!$restrictedAccess) {
                $formattedEmail = $this->convertEmail2Link($email);
            }

            array_push($emailLinks, $formattedEmail . ($index < count($emailList) - 1 ? '<span class=\'hide-in-table\'>, </span>' : ''));
        }

        // convert array back to comma-delimited string
        $emailCell = implode("<br />", $emailLinks);
        $mailtoLink = 'mailto:?bcc=' . str_replace(', ', ';', $emailStr);


        if (count($emailLinks) > 1 && !$restrictedAccess) {
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

    private function formatProjectList($pidStr, $projectTitles, $projectStatuses, $projectDeleted, $restrictedAccess)
    {
        // convert comma-delimited string to array
        $pidList = explode(", ", $pidStr);
        $formattedTitles = array();

        $statusList = explode(",", $projectStatuses);
        $deletedList = explode(",", $projectDeleted);

        foreach ($pidList as $index=>$pid)
        {
            $formattedProjectTitle = $projectTitles[$pid];

            if (!$restrictedAccess) {
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

    private function formatUsernameList($userIDs, $suspendedList, $restrictedAccess)
    {
        // convert comma delimited string to array
        $userIDlist = explode(", ", $userIDs);
        $formattedUsers = array();

        $suspendedList = explode(",", $suspendedList);

        foreach ($userIDlist as $index=>$userID)
        {
            $formattedUsername = $userID;
            $suspended = $suspendedList[$index];

            if (!$restrictedAccess) {
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
        $query['tab'] = $tab;

        $url = http_build_query($query);
        $url = $_SERVER['PHP_SELF'] . '?' . $url;

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

    public function saveVisibilitySettings() {
        $visibilityJson = file_get_contents('php://input');
        self::setSystemSetting('report-visibility', $visibilityJson);
    }
}
?>