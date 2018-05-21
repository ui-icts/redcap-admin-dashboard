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

        <script src="<?= $this->getUrl("/resources/tablesorter/jquery-3.2.0.min.js") ?>"></script>
        <script src="<?= $this->getUrl("/resources/tablesorter/jquery.tablesorter.min.js") ?>"></script>
        <script src="<?= $this->getUrl("/resources/tablesorter/jquery.tablesorter.widgets.min.js") ?>"></script>
        <script src="<?= $this->getUrl("/resources/tablesorter/widgets/widget-pager.min.js") ?>"></script>
        <script src="<?= $this->getUrl("/resources/c3/d3.min.js") ?>" charset="utf-8"></script>
        <script src="<?= $this->getUrl("/resources/c3/c3.min.js") ?>"></script>

        <link href="<?= $this->getUrl("/resources/tablesorter/tablesorter/theme.blue.min.css") ?>" rel="stylesheet">
        <link href="<?= $this->getUrl("/resources/tablesorter/tablesorter/jquery.tablesorter.pager.min.css") ?>" rel="stylesheet">
        <link href="<?= $this->getUrl("/resources/c3/c3.css") ?>" rel="stylesheet" type="text/css">
        <link href="<?= $this->getUrl("/resources/styles.css") ?>" rel="stylesheet" type="text/css"/>

        <script src="<?= $this->getUrl("/adminDash.js") ?>"></script>

        <?php

        $reportReference = $this->generateReportReference();
        $defaultTab = $this->getSystemSetting("default-report");

        if (!isset($_REQUEST['tab']) && $defaultTab != "none") {
            $_REQUEST['tab'] = $defaultTab;
        }

        // define variables
        $title = $this->getModuleName();
        $pageInfo = $reportReference[$_REQUEST['tab']];
        $isSelectQuery = (strtolower(substr($pageInfo['sql'], 0, 7)) == "select ");
        $csvFileName = sprintf("%s.%s.csv", $pageInfo['fileName'], date("Y-m-d_His"));

        // only allow super users to view this information
        if (!SUPER_USER) die("Access denied! Only super users can access this page.");

        if (!$pageInfo['sql'] && !$pageInfo['userDefined']) :
         foreach (self::$visualizationQueries as $vis => $visInfo):
            ?>
               <script>
               d3.json("<?= $this->getUrl("getVisData.php?vis=" . $vis) ?>", function (error, json) {
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
         <h2 style="text-align: center; color: #800000; font-weight: bold;">
             <?= $title ?>
             </h2>

             <p />

                <!-- create navigation tabs -->
             <ul class='nav nav-tabs' role='tablist'>
             <?php foreach($reportReference as $report => $reportInfo): ?>
             <li <?= $_REQUEST['tab'] == $report && isset($_REQUEST['tab']) ? "class=\"active\"" : "" ?> >
             <a href="<?= $this->formatUrl($report) ?>">
             <span class="<?= $reportInfo['tabIcon'] ?>"></span>&nbsp; <?= $reportInfo['reportName'] ?></a>
         </li>
        <?php endforeach; ?>
         </ul>

         <p />

        <?php if (isset($_REQUEST['tab'])): ?>
             <!-- display csv download button (for reports) -->
            <div style="text-align: right; width: 100%">
                <a href="<?= $this->getUrl("downloadCsvViaSql.php?tab=" . $_REQUEST['tab'] . "&file=" . $csvFileName) ?>"
                   class="btn btn-default <?= $pageInfo['sql'] == '' ? 'disabled' : '' ?> btn-lg">
              <span class="fa fa-download"></span>&nbsp;
            Download CSV File</a>
            </div>

            <p />

            <h3 style="text-align: center">
              <?= $pageInfo['reportName'] ?>
              </h3>

              <h5 style="text-align: center">
              <?= $pageInfo['description']; ?>
              </h5>

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

            <?php endif; ?>
        <?php else : ?>
            <br />
            <br />
            <h3 style="text-align: center;">Welcome to the REDCap Admin Dashboard!</h3>
            <div style="text-align: center;">Click one of the tabs above to view a report. You can choose a report to open by default (instead of seeing this page) via the module's configuration settings.</div>
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
   }

    public function generateReportReference() {
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

        $hideFiltersSql = array();

        if ($hideArchived) {
            $hideFiltersSql[] = "projects.status != 3";
        }
        if ($hideDeleted) {
            $hideFiltersSql[] = "projects.date_deleted IS NULL";
        }

        $formattedFilterSql = ($hideDeleted || $hideArchived) ? ("AND " . implode(" AND ", $hideFiltersSql)) : '';
        $formattedWhereFilterSql = ($hideDeleted || $hideArchived) ? ("WHERE " . implode(" AND ", $hideFiltersSql)) : '';

        $reportReference = array
        (
            array // Projects by User
            (
                "reportName" => "Projects by User",
                "fileName" => "projectsByUser",
                "description" => "List of users and the projects to which they have access.",
                "tabIcon" => "fa fa-male",
                "sql" => "
        SELECT
        info.username AS 'Username',
        CONCAT(info.user_lastname, ', ', info.user_firstname) AS 'Name',
        info.user_email AS 'Email',
        GROUP_CONCAT(CAST(projects.project_id AS CHAR(50)) SEPARATOR ', ') AS 'Project Titles',
        GROUP_CONCAT(CAST(projects.app_title AS CHAR(50)) SEPARATOR ', ') AS 'Project CSV Titles (Hidden)',
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
                "description" => "List of projects and the users which have access.",
                "tabIcon" => "fas fa-users",
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
        GROUP_CONCAT(CAST(CASE WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
        END AS CHAR(50))) AS 'Project Deleted Date (Hidden)',
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
        DATE_FORMAT(creation_time, '%Y-%m-%d') AS 'Creation Date',
        DATE_FORMAT(last_logged_event, '%Y-%m-%d') AS 'Last Logged Event Date',
        DATEDIFF(now(), last_logged_event) AS 'Days Since Last Event',
        COUNT(redcap_user_rights.username) AS 'Total Users'
        FROM redcap_projects AS projects
        LEFT JOIN redcap_record_counts ON projects.project_id = redcap_record_counts.project_id
        LEFT JOIN redcap_user_rights ON projects.project_id = redcap_user_rights.project_id
        $formattedWhereFilterSql
        GROUP BY projects.project_id
        ORDER BY app_title
        "
            ),
            array // Research Projects
            (
                "reportName" => "Research Projects",
                "fileName" => "researchProjects",
                "description" => "List of projects that are identified as being used for research purposes.",
                "tabIcon" => "fa fa-flask",
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
        CONCAT(project_pi_lastname, ', ', project_pi_firstname, ' ', project_pi_mi) AS 'PI Name',
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
                "description" => "List of projects that are in Development Mode.",
                "tabIcon" => "fas fa-wrench",
                "sql" => "
        SELECT
        projects.project_id AS 'PID',
        app_title AS 'Project Title',
        record_count AS 'Record Count',
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
        INNER JOIN redcap_record_counts ON projects.project_id = redcap_record_counts.project_id
        WHERE projects.status = 0 and projects.purpose != 0
        $formattedFilterSql
        ORDER BY app_title
        "
            ),
            array // All Projects
            (
                "reportName" => "All Projects",
                "fileName" => "allProjects",
                "description" => "List of all projects (excluding those designated as 'Practice/Just for Fun').",
                "tabIcon" => "fas fa-folder-open",
                "sql" => "
        SELECT
        projects.project_id AS 'PID',
        app_title AS 'Project Title',
        record_count AS 'Record Count',
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
        INNER JOIN redcap_record_counts ON projects.project_id = redcap_record_counts.project_id
        WHERE projects.purpose != 0
        $formattedFilterSql
        ORDER BY app_title
        "
            )
        );

        if (self::getSystemSetting('optional-report-passwords')) {
            array_push($reportReference,
                array
                (
                    "reportName" => "Credentials Check (Project Titles)",
                    "fileName" => "projectCredentials",
                    "description" => "List of projects titles that contain strings related to REDCap login credentials (usernames/passwords). Search terms include the following: " . implode(', ', $pwordSearchTerms),
                    "tabIcon" => "fa fa-key",
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
                    "description" => "List of projects that contain strings related to REDCap login credentials (usernames/passwords) in the instrument or form name. Search terms include the following: " . implode(', ', $pwordSearchTerms),
                    "tabIcon" => "fa fa-key",
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
                    "description" => "List of projects that contain strings related to REDCap login credentials (usernames/passwords) in fields. Search terms include the following: " . implode(', ', $pwordSearchTerms),
                    "tabIcon" => "fa fa-key",
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
                )

            );
        }

        if (self::getSystemSetting('optional-report-modules')) {
            array_push($reportReference,
                array
                (
                    "reportName" => "Projects with External Modules",
                    "fileName" => "modulesByProject",
                    "description" => "List of External Modules and the projects they are enabled in.",
                    "tabIcon" => "fas fa-plug",
                    "sql" => "
        SELECT
        REPLACE(directory_prefix, '_', ' ') AS 'Module Title',
        GROUP_CONCAT(CAST(projects.project_id AS CHAR(50)) SEPARATOR ', ') AS 'Project Titles',
        GROUP_CONCAT(CAST(projects.app_title AS CHAR(50)) SEPARATOR ', ') AS 'Project CSV Titles (Hidden)',
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
                ));
        }

        $customNames = $this->getSystemSetting('custom-report-name');
        $customDescs = $this->getSystemSetting('custom-report-desc');
        $customIcons = $this->getSystemSetting('custom-report-icon');
        $customSqls = $this->getSystemSetting('custom-report-sql');
        $customToggles = $this->getSystemSetting('custom-report-enabled');


        for($i = 0; $i < count($customNames); $i++) {
            if ($customToggles[$i] == "true") {
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
        }

        array_push($reportReference,
            array // Visualizations
            (
                "reportName" => "Visualizations",
                "fileName" => "visualizations",
                "description" => "Additional metadata presented in a visual format.",
                "tabIcon" => "fas fa-chart-pie"
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

            // set PI Name blank if not entered
            if ($row['PI Name'] == ',  ') {
                $row['PI Name'] = '';
            }

            if ($row['Module Title']) {
                $row['Module Title'] = ucwords($row['Module Title']);
            }

            if ($pageInfo['fileName'] == 'modulesByProject' ||
                strpos($_REQUEST['file'], 'modulesByProject') !== false) {
                    $rowArray = explode(', ', $row['Project Titles']);
                    $rowArray = array_unique($rowArray);
                    $row['Project Titles'] = implode(', ', $rowArray);
                    $row['Total Projects'] = sizeof($rowArray);

                    $rowArray = explode(', ', $originalRow['Project CSV Titles (Hidden)']);
                    $rowArray = array_unique($rowArray);
                    $csvTitles = implode(',', $rowArray);

                    $rowArray = explode(', ', $row['User Emails']);
                    $rowArray = array_unique($rowArray);
                    $row['User Emails'] = implode(', ', $rowArray);
            }

            if ($format == 'html') {
                if ($isFirstRow)
                {
                    // print table header
                    $this->printTableHeader($headers);
                    printf("   <tbody>\n");
                    $isFirstRow = FALSE;  // toggle flag
                }

                $webData = $this->webifyDataRow($row, $redcapProjects);
                $this->printTableRow($webData, $hiddenColumns);
            }
            elseif ($format == 'csv') {
                if ($isFirstRow)
                {
                    if ($_REQUEST['tab'] == 2) {
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

                    $headerStr = implode("\",\"", $headers);
                    printf("\"%s\"\n", $headerStr);

                    $isFirstRow = FALSE;  // toggle flag
                }

                if ($_REQUEST['tab'] == 2) {
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

                if ($row['Purpose Specified'] != null) {
                    $row['Purpose Specified'] = $this->convertProjectPurpose2List($row['Purpose Specified']);
                }

                $pidsInCsv = self::getSystemSetting('csv-display-pids');

                if (!$pidsInCsv) {
                    $row['Project Titles'] = $csvTitles;
                }

                foreach ($hiddenColumns as $column)
                {
                    unset($row[$column]);
                }

                $titlesStr = implode("\",\"", $row);

                printf("\"%s\"\n", $titlesStr);
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

        foreach ($columns as $name)
            printf("         <th> %s </th>\n", $name);

        printf("
      </tr>
   </thead>\n");

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

        foreach ($row as $key => $value)
        {
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
            elseif ($key == "Project Titles")
            {
                $projectStatuses = $row['Project Statuses (Hidden)'];
                $projectDeleted = $row['Project Deleted Date (Hidden)'];

                $webified[$key] = $this->convertPidList2Links($value, $projectTitles, $projectStatuses, $projectDeleted);
            }
            elseif ($key == "Purpose Specified")
            {
                $webified[$key] = $this->convertProjectPurpose2List($value);
            }
            elseif (($key == "PI Email") ||
                ($key == "Email"))
            {
                $webified[$key] = $this->convertEmail2Link($value);
            }
            elseif ($key == "User Emails")
            {
                $webified[$key] = $this->convertEmailList2Links($value);
            }
            elseif (($key == "Users") ||
                ($key == "Username"))
            {
                $webified[$key] = $this->convertUsername2Link($value);
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

    private function convertEmailList2Links($emailStr)
    {
        // convert comma-delimited string to array
        $emailList = explode(", ", $emailStr);
        $emailLinks = array();

        foreach ($emailList as $index=>$email)
        {
            array_push($emailLinks, $this->convertEmail2Link($email));
        }

        // convert array back to comma-delimited string
        $emailCell = implode("<br />", $emailLinks);
        $mailtoLink = 'mailto:?bcc=' . str_replace(', ', ';', $emailStr);

        if (count($emailLinks) > 1) {
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

        if ($projectStatus == 'Archived') {
            $styleIdStr = "id=archived";
        }
        if ($projectDeleted != 'N/A') {
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

    private function convertPidList2Links($pidStr, $pidTitles, $projectStatuses, $projectDeleted)
    {
        // convert comma-delimited string to array
        $pidList = explode(", ", $pidStr);
        $pidLinks = array();

        $statusList = explode(",", $projectStatuses);
        $deletedList = explode(",", $projectDeleted);

        foreach ($pidList as $index=>$pid)
        {
            $hrefStr = $pidTitles[$pid];
            array_push($pidLinks, $this->convertPid2Link($pid, $hrefStr, $statusList[$index], $deletedList[$index]));
        }

        // convert array back to comma-delimited string
        $pidCell = implode("<br />", $pidLinks);

        return($pidCell);
    }

    private function convertUsername2Link($userIDs)
    {
        // convert comma delimited string to array
        $userIDlist = explode(", ", $userIDs);
        $linkList = array();

        foreach ($userIDlist as $userID)
        {
            $urlString =
                sprintf("https://%s%sControlCenter/view_users.php?username=%s",  // Browse User Page
                    SERVER_NAME,
                    APP_PATH_WEBROOT,
                    $userID);

            $userLink = sprintf("<a href=\"%s\"
                              target=\"_blank\">%s</a>",
                $urlString, $userID);

            array_push($linkList, $userLink);
        }

        // convert array to comma delimited string
        $linkStr = implode( "<br>", $linkList);

        return($linkStr);
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
        if (SUPER_USER)
        {
            $sql = "SELECT project_id AS pid,
                     TRIM(app_title) AS title
              FROM redcap_projects
              ORDER BY pid";
        }
        else
        {
            $sql = sprintf("SELECT p.project_id AS pid,
                              TRIM(p.app_title) AS title
                       FROM redcap_projects p, redcap_user_rights u
                       WHERE p.project_id = u.project_id AND
                             u.username = '%s'
                       ORDER BY pid", USERID);
        }

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
}
?>