<?php
namespace UIOWA\AdminDash;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class AdminDash extends AbstractExternalModule {
    public $reportReference;

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
    "visName" => "\"Purpose Specified (Research Projects)\"",
    "visID" => "\"purpose_all\"",
    "visType" => "\"count\"",
    "countColumns" => ["Purpose"],
    "sql" => "
            SELECT
            CAST(CASE purpose
            WHEN 0 THEN 'Practice / Just for fun'
            WHEN 1 THEN 'Operational Support'
            WHEN 2 THEN 'Research'
            WHEN 3 THEN 'Quality Improvement'
            WHEN 4 THEN 'Other'
            ELSE purpose
            END AS CHAR(50)) AS 'Purpose'
            FROM redcap_projects
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

    public function generateAdminDash(&$reportReference) {
        ?>
        <script src="<?= $this->getUrl("/resources/tablesorter/jquery-3.2.0.min.js") ?>"></script>
        <script src="<?= $this->getUrl("/resources/tablesorter/jquery.tablesorter.min.js") ?>"></script>
        <script src="<?= $this->getUrl("/resources/tablesorter/jquery.tablesorter.widgets.min.js") ?>"></script>
        <script src="<?= $this->getUrl("/resources/tablesorter/widgets/widget-pager.min.js") ?>"></script>
        <script src="<?= $this->getUrl("/resources/c3/d3.min.js") ?>" charset="utf-8"></script>
        <script src="<?= $this->getUrl("/resources/c3/c3.min.js") ?>"></script>

        <link href="<?= $this->getUrl("/resources/tablesorter/tablesorter/theme.blue.min.css") ?>" rel="stylesheet">
        <link href="<?= $this->getUrl("/resources/tablesorter/tablesorter/jquery.tablesorter.pager.min.css") ?>" rel="stylesheet">
        <link href="<?= $this->getUrl("/resources/c3/c3.css") ?>" rel="stylesheet" type="text/css">
        <link href="<?= $this->getUrl("/resources/font-awesome-4.7.0/css/font-awesome.min.css") ?>" rel="stylesheet">
        <link href="<?= $this->getUrl("/resources/styles.css") ?>" rel="stylesheet" type="text/css"/>

        <script src="<?= $this->getUrl("/adminDash.js") ?>"></script>
        <?php

        $reportReference = $this->generateReportReference();

        // display the header
        $HtmlPage = new \HtmlPage();
        $HtmlPage->PrintHeaderExt();

        // define variables
        $title = $this->getModuleName();
        $pageInfo = $reportReference[ (!$_REQUEST['tab']) ? 0 : $_REQUEST['tab'] ];
        $csvFileName = sprintf("%s.%s.csv", $pageInfo['fileName'], date("Y-m-d_His"));

        // only allow super users to view this information
        if (!SUPER_USER) die("Access denied! Only super users can access this page.");

        // start the stopwatch ...
        $this->elapsedTime();

        if (!$pageInfo['sql']) :
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
             <li <?= $_REQUEST['tab'] == $report ? "class=\"active\"" : "" ?> >
             <a href="<?= $this->formatUrl($report) ?>">
             <span class="<?= $reportInfo['tabIcon'] ?>"></span>&nbsp; <?= $reportInfo['reportName'] ?></a>
         </li>
        <?php endforeach; ?>
         </ul>

         <p />

         <!-- display csv download button (for reports) -->
        <?php if($pageInfo['sql']) : ?>
        <div style="text-align: right; width: 100%">
            <a href="<?= $this->getUrl("downloadCsvViaSql.php?tab=" . $_REQUEST['tab'] . "&file=" . $csvFileName) ?>"
               class="btn btn-default btn-lg">
          <span class="fa fa-download"></span>&nbsp;
        Download CSV File</a>
        </div>
        <?php endif; ?>

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
          <option value="all">All Rows</option>
        </select>
        </form>
        </div>
        <?php else : ?>
         <!-- display graphs -->
        <div style="display: inline-block; margin: 0 auto;">
          <div style="width: 100%; display: table; max-height: 500px;">
          <div style="display: table-row">
          <?php foreach (self::$visualizationQueries as $vis => $visInfo): ?>
          <div style="width: 500px; display: table-cell;" id=<?= $visInfo['visID'] ?>></div>
        <?php endforeach; ?>
        </div>
        <div style="display: table-row">
          <div style="display: table-cell" id="status_research"></div>
          </div>
          </div>
          </div>

        <?php endif; ?>

        <?php
        // display normal reports
        if($pageInfo['sql']) {
         // execute the SQL statement
         $result = $this->sqlQuery($pageInfo['sql']);

          $this->formatQueryResults($result, "html");

         printf("   </tbody>\n");
         printf("</table>\n");  // <table> created by PrintTableHeader

         if ($_REQUEST['tab'] == 0) {
            $result = db_query(self::$miscQueryReference[0]['sql']);
            printf($this->formatQueryResults($result, "text") . " users are currently suspended.");
         }
        }

        $this->displayElapsedTime();

        // Display the footer
        $HtmlPage->PrintFooterExt();
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
            $pwordSearchTerms[] = $term;
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

        $pwordProjectSql =  $pwordProjectSql . $formattedFilterSql;
        $pwordInstrumentSql = $pwordInstrumentSql . $formattedFilterSql;
        $pwordFieldSql = $pwordFieldSql . $formattedFilterSql;

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
                "tabIcon" => "fa fa-folder",
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
        WHEN 1 THEN 'Operational Support'
        WHEN 2 THEN 'Research'
        WHEN 3 THEN 'Quality Improvement'
        WHEN 4 THEN 'Other'
        ELSE purpose
        END AS CHAR(50)) AS 'Purpose',
        GROUP_CONCAT((redcap_user_rights.username) SEPARATOR ', ') AS 'Users',
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
                "description" => "List of record counts for projects in Development Mode.",
                "tabIcon" => "fa fa-folder",
                "sql" => "
        SELECT
        projects.project_id AS 'PID',
        app_title AS 'Project Title',
        record_count AS 'Record Count',
        CAST(CASE purpose
        WHEN 0 THEN 'Practice / Just for fun'
        WHEN 1 THEN 'Operational Support'
        WHEN 2 THEN 'Research'
        WHEN 3 THEN 'Quality Improvement'
        WHEN 4 THEN 'Other'
        ELSE purpose
        END AS CHAR(50)) AS 'Purpose',
        CAST(CASE WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
        END AS CHAR(50)) AS 'Project Deleted Date (Hidden)',
        creation_time AS 'Creation Time',
        last_logged_event AS 'Last Logged Event Date'
        FROM redcap_projects AS projects
        INNER JOIN redcap_record_counts ON projects.project_id = redcap_record_counts.project_id
        WHERE projects.status = 0 and projects.purpose != 0
        $formattedFilterSql
        "
            ),
// todo
//            array // Publication Matches
//            (
//                "reportName" => "Publication Matches",
//                "fileName" => "pubMatches",
//                "description" => "List of publication matches",
//                "tabIcon" => "fa fa-book",
//                "sql" => "
//SELECT
//    *
//FROM
//    redcap_pub_articles
//INNER JOIN redcap_pub_authors ON redcap_pub_articles.article_id = redcap_pub_authors.article_id
//INNER JOIN redcap_pub_matches ON redcap_pub_articles.article_id = redcap_pub_matches.article_id
//INNER JOIN redcap_pub_mesh_terms ON redcap_pub_articles.article_id = redcap_pub_mesh_terms.article_id
//      "
//            ),
            array // Passwords in Project Titles
            (
                "reportName" => "Passwords in Project Titles",
                "fileName" => "projectPassword",
                "description" => "List of projects that contain strings related to REDCap login credentials (usernames/passwords) in the project title. Search terms include the following: " . implode(', ', $pwordSearchTerms),
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
            array // Passwords in Instruments
            (
                "reportName" => "Passwords in Instruments",
                "fileName" => "instrumentPassword",
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
            array // Passwords in Fields
            (
                "reportName" => "Passwords in Fields",
                "fileName" => "fieldPassword",
                "description" => "List of projects that contain strings related to REDCap login credentials (usernames/passwords) in one of the fields. Search terms include the following: " . implode(', ', $pwordSearchTerms),
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
            ),
            array // Visualizations
            (
                "reportName" => "Visualizations",
                "fileName" => "visualizations",
                "description" => "Additional metadata presented in a visual format.",
                "tabIcon" => "fa fa-pie-chart"
            )
        );

        return $reportReference;
    }

    public function formatQueryResults($result, $format)
    {
        $redcapProjects = $this->getRedcapProjectNames();
        $isFirstRow = TRUE;

        if ($result -> num_rows == 0) {
            printf("No records found.");
        }

        while ($row = db_fetch_assoc($result))
        {
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
                    $row['Project Titles'] = $row['Project CSV Titles (Hidden)'];
                }

                foreach ($hiddenColumns as $column)
                {
                    unset($row[$column]);
                }

                $rowStr = implode("\",\"", $row);

                printf("\"%s\"\n", $rowStr);
            }
            elseif ($format == 'text') {
                $rowStr = implode("\",\"", $row);
                return $rowStr;
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
                $webified[$key] = $this->convertPid2AdminLink($value, $value);
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
                ($key == "Owner Email") ||
                ($key == "Email"))
            {
                $webified[$key] = $this->convertEmail2Link($value);
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

    private function elapsedTime()
    {
        // initialize variables
        static $startTime = null;
        $elapseTimeStr = "";

        if ($startTime == null)  // start the clock
        {
            $startTime = round(microtime(true));
            // printf("\$startTime: %f<br />", $startTime);
        }
        else
        {
            $endTime = round(microtime(true));
            // printf("\$endTime: %f<br />", $endTime);
            $elapseTime = $endTime - $startTime;
            // printf("\$elapsedTime: %f<br />", $elapsedTime);

            $elapseTimeStr = date("i:s", $elapseTime);
        }

        return($elapseTimeStr);
    }

    private function sqlQuery($query)
    {
        // execute the SQL statement
        $result = db_query($query);

        if (! $result)  // sql failed
        {
            $message = printf("Line: %d<br />
                          Could not execute SQL<br />
                          Error #: %d<br />
                          Error Msg: %s",
                __LINE__);
            die($message);
        }
        else
        {
            return $result;
        }
    }

    private function displayElapsedTime()
    {
        $load = sys_getloadavg();

        printf("<div id='elapsedTime'>
            Elapsed Execution Time: %s<br />
            System load avg last minute: %d%%<br />
            System load avg last 5 mins: %d%%<br />
            System load avg last 15 min: %d%%</div>",
            $this->elapsedTime(), $load[0] * 100, $load[1] * 100, $load[2] * 100);
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