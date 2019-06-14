<?php
namespace UIOWA\AdminDash;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once 'vendor/autoload.php';

class AdminDash extends AbstractExternalModule {
    private static $smarty;

    private static $purposeMaster = array(
        "Basic or Bench Research",
        "Clinical Research Study or Trial",
        "Translational Research 1",
        "Translational Research 2",
        "Behavioral or Psychosocial Research Study",
        "Epidemiology",
        "Repository",
        "Other"
    );

    private static $configSettings = array(
        array(
            "key" => "use-api-urls",
            "name" => "Use versionless URLs for easier bookmarking (refresh page to take effect)",
            "data-on" => "On",
            "data-off" => "Off",
            "type" => "checkbox",
            "default" => true
        ),
        array(
            "key" => "show-user-icons",
            "name" => "Show icons next to suspended or non-existent usernames",
            "type" => "checkbox",
            "default" => true
        )
    );

    private $sqlError;

    public function redcap_module_system_change_version($version, $old_version) {
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
            $untitledCount = 1;

            foreach ($oldNameData as $index => $oldName) {
                if (empty($oldNameData[$index])) {
                    $newName = 'Untitled ' . $untitledCount;
                    $untitledCount += 1;
                }
                else {
                    $newName = $oldNameData[$index];
                }

                array_push($newCustomReportData, array(
                    'reportName' => $newName,
                    'description' => empty($oldDescData[$index]) ? 'No description defined.' : $oldDescData[$index],
                    'tabIcon' => empty($oldIconData[$index]) ? 'fas fa-question-circle' : str_replace('fas fa', '', $oldIconData[$index]),
                    'sql' => $oldSqlData[$index],
                    'type' => 'table',
                    "customID" => "",
                ));
            }

            $this->setSystemSetting("custom-reports", $newCustomReportData);
            $this->removeSystemSetting("custom-report");
            $this->removeSystemSetting("custom-report-name");
            $this->removeSystemSetting("custom-report-desc");
            $this->removeSystemSetting("custom-report-icon");
            $this->removeSystemSetting("custom-report-sql");
        }

        // downgrade Credentials Check reports to "custom" status so they can be deleted and add defaults for new settings (v3.4)
        if(version_compare('3.4', $old_version)) {
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

            $credentialCheckReports = array(
                array
                (
                    "reportName" => "Credentials Check (Project Titles)",
                    "customID" => "projectCredentials",
                    "description" => "List of projects titles that contain strings related to login credentials (usernames/passwords). Search terms include the following: " . implode(', ', $pwordSearchTerms),
                    "tabIcon" => "key",
                    "defaultVisibility" => false,
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
                )
            );

            $existingCustomReports = $this->getSystemSetting('custom-reports');
            $mergedCustomReports = array_merge($credentialCheckReports, $existingCustomReports);
            $this->setSystemSetting('custom-reports', $mergedCustomReports);

            $this->setSystemSetting('show-user-icons', true);

            $result = $this->sqlQuery('select value from redcap_config where field_name = \'auth_meth_global\'');
            $authMethod = db_fetch_assoc($result)['value'];

            if ($authMethod == 'shibboleth') {
                $this->setSystemSetting('use-api-urls', false);
            }
            else {
                $this->setSystemSetting('use-api-urls', true);
            }

            $oldExportSetting = $this->getSystemSetting('executive-export-enabled');
            $exportEnabledLookup = [];

            if ($oldExportSetting) {
                foreach ($this->getSystemSetting('executive-users') as $user) {
                    array_push($exportEnabledLookup, $user);
                }
            }

            $this->setSystemSetting('executive-user-export', $exportEnabledLookup);
        }

        $this->setSystemSetting('show-changelog', true);
    }

    public function __construct()
    {
        parent::__construct();
        define("MODULE_DOCROOT", $this->getModulePath());
    }

    public function initializeSmarty()
    {
        self::$smarty = new \Smarty();
        self::$smarty->setTemplateDir(MODULE_DOCROOT . 'templates');
        self::$smarty->setCompileDir(MODULE_DOCROOT . 'templates_c');
        self::$smarty->setConfigDir(MODULE_DOCROOT . 'configs');
        self::$smarty->setCacheDir(MODULE_DOCROOT . 'cache');
//        self::$smarty->compile_check = false;
//        self::$smarty->caching = true;
    }

    public function displayTemplate($template)
    {
        self::$smarty->display($template);
    }

    public function includeJsAndCss() {
        ?>
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

            <script src="<?= $this->getUrl("/resources/bootstrap-toggle/bootstrap-toggle.min.js") ?>"></script>
            <link href="<?= $this->getUrl("/resources/bootstrap-toggle/bootstrap-toggle.min.css") ?>" rel="stylesheet">

            <script src="<?= $this->getUrl("/resources/ace/ace.js") ?>" type="text/javascript" charset="utf-8"></script>

            <script src="<?= $this->getUrl("/adminDash.js") ?>"></script>
        <?php
            if ($_REQUEST['page'] == 'settings') {
                echo '<script src="' . $this->getUrl("/settings.js") . '"></script>';
            }
    }

    public function initializeVariables() {
        $reportReference = $this->generateReportReference();
        $configSettings = $this::$configSettings;
        $executiveUsers = $this->getSystemSetting("executive-users");
        $executiveAccess = ($_REQUEST['page'] == 'executiveView' && (in_array(USERID, $executiveUsers) || SUPER_USER) ? 1 : 0);
        $executiveExportLookup = $this->getSystemSetting("executive-user-export");
        $exportEnabled = (in_array(USERID, $executiveExportLookup) && $executiveAccess) || (SUPER_USER && !$executiveAccess);
        $reportIDlookup = [];

        // if reports have custom IDs, match them to report IDs for future reference
        foreach($reportReference as $index => $reportInfo) {
            array_push($reportIDlookup, $reportInfo['customID']);
            $reportReference[$index]['url'] = $this->formatReportUrl($index, $reportInfo['customID']);
        }

        // if custom ID is given, convert it to a report index
        if (isset($_REQUEST['report'])) {
            $reportIndex = array_search($_REQUEST['report'], $reportIDlookup);

            if ($reportIndex !== false) {
                $_REQUEST['id'] = $reportIndex;
            }
            else {
                unset($_REQUEST['report']);
                unset($_REQUEST['id']);
            }
        }

        // if report ID isn't valid, send to the landing page
        if (intval($_REQUEST['id']) > sizeof($reportReference)) {
            unset($_REQUEST['report']);
            unset($_REQUEST['id']);
        }

        if ($_REQUEST['page'] != 'settings') {
            $_REQUEST['lastPage'] = $_REQUEST['page'];
        }

        $pageInfo = $reportReference[$_REQUEST['id']];

        $adminVisibility = $this->loadVisibilitySettings('admin', $reportReference);
        $executiveVisibility = $this->loadVisibilitySettings('executive', $reportReference);
        $executiveVisible = false;

        if ($pageInfo['reportName']) {
            $executiveVisible = in_array(USERID, $executiveVisibility->{$pageInfo['reportName']});
        }

        if (!SUPER_USER) {
            if (!$executiveAccess || (!$executiveVisible && isset($_REQUEST['id']))) {
                die("Access denied! You do not have permission to view this page.");
            }
        }

        if ($pageInfo['type'] == 'table') {
            $result = $this->sqlQuery($pageInfo['sql']);
            $pageInfo['sqlErrorMsg'] = $this->formatQueryResults($result);
        }

        // construct URL redirect to alternate view
        if (SUPER_USER) {
            if ($_REQUEST['page'] == 'executiveView') {
                $viewUrl = 'index.php';
            }
            else {
                $viewUrl = 'executiveView.php';
            }

            if (isset($_REQUEST['report'])) {
                $viewUrl .= '?report=' . $_REQUEST['report'];
            }
            else if (isset($_REQUEST['id'])) {
                $viewUrl .= '?id=' . $_REQUEST['id'];
            }

            $viewUrl = urldecode($this->getUrl($viewUrl));
            self::$smarty->assign('viewUrl', $viewUrl);
        }

        $changelogContent = json_decode($this->curl_get_contents($this->getUrl("config/changelog.json")));

        $iconUrls = array(
            'first' => $this->getUrl("resources/tablesorter/tablesorter/images/icons/first.png"),
            'prev' => $this->getUrl("resources/tablesorter/tablesorter/images/icons/prev.png"),
            'next' => $this->getUrl("resources/tablesorter/tablesorter/images/icons/next.png"),
            'last' => $this->getUrl("resources/tablesorter/tablesorter/images/icons/last.png"),
        );

        foreach ($configSettings as $index => $setting) {
            if(isset($setting['key'])) {
                $getSetting = $this->getSystemSetting($setting['key']);

                if (isset($getSetting)) {
                    $configSettings[$index]['default'] = $getSetting;
                }
            }
        }

        self::$smarty->assign('configSettings', $configSettings);

        // Get list of valid "target" REDCap projects for export feature
        $sql = "
            select u.project_id, app_title from redcap_user_rights as u
            left join redcap_projects as p on p.project_id = u.project_id
            where api_token is not null
                  and api_import = 1
                  and username = '" . USERID . "'
        ";

        $sql = db_query($sql);
        $exportProjects = [];

        while ($row = db_fetch_assoc($sql)) {
            array_push($exportProjects, $row);
        }

        self::$smarty->assign('exportProjects', $exportProjects);
        self::$smarty->assign('executiveAccess', $executiveAccess);
        self::$smarty->assign('executiveUsers', $executiveUsers);
        self::$smarty->assign('executiveExportLookup', $executiveExportLookup);
        self::$smarty->assign('superUser', SUPER_USER);
        self::$smarty->assign('reportId', $_REQUEST['id']);
        self::$smarty->assign('reportReference', $reportReference);
        self::$smarty->assign('changelogContent', $changelogContent);
        self::$smarty->assign('iconUrls', $iconUrls);
        self::$smarty->assign('exportEnabled', $exportEnabled);
        self::$smarty->assign('sqlErrorMsg', $pageInfo['sqlErrorMsg']);
        self::$smarty->assign('loadingGif', $this->getUrl('resources/loading.gif'));

        $showChangelog = json_encode($this->getSystemSetting('show-changelog'));

        ?>
            <script>

                UIOWA_AdminDash.csvFileName = '<?= sprintf("%s.csv", $pageInfo['customID'] != '' ? $pageInfo['customID'] : 'customReport' ); ?>';
                UIOWA_AdminDash.renderDatetime = '<?= date("Y-m-d_His") ?>';

                UIOWA_AdminDash.executiveAccess = <?= $executiveAccess ?>;
                UIOWA_AdminDash.executiveUsers = <?= json_encode($executiveUsers) ?>;
                UIOWA_AdminDash.executiveExportLookup = <?= json_encode($executiveExportLookup) ?>;
                UIOWA_AdminDash.adminVisibility = <?= json_encode($adminVisibility) ?>;
                UIOWA_AdminDash.executiveVisibility = <?= json_encode($executiveVisibility) ?>;
                UIOWA_AdminDash.reportIDs = <?= json_encode($reportIDlookup) ?>;
                UIOWA_AdminDash.requestHandlerUrl = "<?= $this->getUrl("requestHandler.php") ?>";
                UIOWA_AdminDash.reportUrlTemplate = "<?= $this->getUrl(
                    $executiveAccess ? "executiveView.php" : "index.php") ?>";
                UIOWA_AdminDash.executiveUrl = "<?= $this->getUrl("executiveView.php", false, true) ?>";
                UIOWA_AdminDash.settingsUrl = "<?= $this->getUrl("settings.php") ?>";
                UIOWA_AdminDash.redcapBaseUrl = "<?= APP_PATH_WEBROOT_FULL ?>";
                UIOWA_AdminDash.redcapVersionUrl = "<?= (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . SERVER_NAME . APP_PATH_WEBROOT ?>";
                UIOWA_AdminDash.showChangelog = <?= json_encode($this->getSystemSetting('show-changelog')) ?>;
                UIOWA_AdminDash.readmeUrl = "<?= $this->getUrl('README.md') ?>";

                UIOWA_AdminDash.hideColumns = [];
                UIOWA_AdminDash.reportReference = <?= json_encode($reportReference) ?>;
                UIOWA_AdminDash.showArchivedReports = false;
                UIOWA_AdminDash.superuser = <?= SUPER_USER ?>;
                UIOWA_AdminDash.theme = UIOWA_AdminDash.executiveAccess ? 'ice' : 'blue';
                UIOWA_AdminDash.showUserIcons = <?= json_encode($this->getSystemSetting('show-user-icons')); ?>;

                UIOWA_AdminDash.userID = '<?= USERID ?>';
                UIOWA_AdminDash.lastTestQuery = {
                    report: -1,
                    columns: [],
                    rowCount: 0,
                    checked: false
                };

                UIOWA_AdminDash.reportInfo = <?= json_encode($pageInfo) ?>;
                UIOWA_AdminDash.formattingReference = <?= $this->curl_get_contents($this->getUrl("config/formattingReference.json")) ?>;
            </script>
        <?php

        if ($showChangelog) {
            $this->setSystemSetting('show-changelog', false);
        }
   }

    private function generateReportReference() {
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

//        $formattedFilterSql = ($hideDeleted || $hideArchived || $hidePractice) ? ("AND " . implode(" AND ", $hideFiltersSql)) : '';
//        $formattedWhereFilterSql = ($hideDeleted || $hideArchived || $hidePractice) ? ("WHERE " . implode(" AND ", $hideFiltersSql)) : '';

        $reportReference = json_decode($this->curl_get_contents($this->getUrl("config/reportReference.json")));

        foreach ($reportReference as $index => $report) {
            $reportReference[$index]['readOnly'] = true;
            $reportReference[$index]['type'] = 'table';

            // load report sql from file and add filters
            $reportSql = $this->curl_get_contents($this->getUrl($reportReference[$index]['sql']));
//            $reportSql = str_replace('$formattedFilterSql', $formattedFilterSql, $reportSql);
//            $reportSql = str_replace('$formattedWhereFilterSql', $formattedWhereFilterSql, $reportSql);
            $reportReference[$index]['sql'] = $reportSql;
        }

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

        return $reportReference;
    }

    private function formatQueryResults($result)
    {
        $isFirstRow = true;
        $record_id = 1;

        $tableData = array(
            'headers' => array(),
            'data' => array(),
            'project_data' => array(),
            'project_headers' => array()
        );

        while ($row = db_fetch_assoc($result))
        {
//            if (isset($row['project_id']) == 1) {
//                $pid = $row['project_id'];
//                $row['~app_title'] = $redcapProjectsLookup[$pid];
//            }

            if ($isFirstRow) {
                // get column titles
                $tableData['headers'] = array_keys($row);

                foreach ($row as $key => $value) {
                    $key = str_replace(' ', '_', preg_replace("/[^A-Za-z0-9 _]/", '', strtolower($key)));

                    array_push($tableData['project_headers'], $key); //todo format headers for field names
                }

                $isFirstRow = false;
            }

            array_push($tableData['data'], $row);

            // save unformatted data for import to REDCap project todo - dealing with duplicate field names
            $index = 0;
            $fieldNames = array();
            foreach ($row as $key => $value) {
                if (!array_search($key, $fieldNames)) {
                    $row[$tableData['project_headers'][$index]] = $value;
                    array_push($key, $fieldNames);
                    $index++;
                }

                unset($row[$key]);

            }
            $row['record_id'] = $record_id;
            array_push($tableData['project_data'], $row);
            $record_id++;
        }

        if ($this->sqlError) {
            ?>
            <script>
                UIOWA_AdminDash.data = null;
            </script>
            <?php

            return "Failed to run report!<br /><br />";
        }
        if (!$tableData['data']) {
            ?>
            <script>
                UIOWA_AdminDash.data = null;
            </script>
            <?php

            return "No results returned.";
        }
        else {
            ?>
            <script>
                UIOWA_AdminDash.data = <?= json_encode($tableData) ?>;
            </script>
            <?php

            return null;
        }
    }

    private function formatReportUrl($reportIndex, $customID)
    {
        unset($_GET['report']);
        unset($_GET['id']);

        if ($_GET['page'] == 'settings') {
            $_GET['page'] = 'index';
        }

        $query = $_GET;
        $moduleParam = array('type' => 'module');
        $query = $moduleParam + $query;

        if ($customID !== '') {
            $query['report'] = $customID;
        }
        else {
            $query['id'] = $reportIndex;
        }

        $url = http_build_query($query);

        $url = $this->getSystemSetting('use-api-urls') ?
            APP_PATH_WEBROOT_FULL . 'api/?' . $url :
            $_SERVER['PHP_SELF'] . '?' . $url;

        return ($url);
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

    public function saveConfigSetting() {
        $setting = json_decode($this->curl_get_contents('php://input'));

        $this->setSystemSetting($setting->key, $setting->value);
    }

    public function saveReportSettings() {
        $allSettings = json_decode($this->curl_get_contents('php://input'));

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

    public function exportDiagnosticFile() {
        $sql = "select external_module_id from redcap_external_modules where directory_prefix = 'admin_dash'";
        $moduleID = db_fetch_assoc(db_query($sql))['external_module_id'];

        $sql = "select * from redcap_external_module_settings where external_module_id = $moduleID";
        $result = db_query($sql);

        $data = array();

        while ( $row = db_fetch_assoc( $result ) )
        {
            $data[] = $row;
        }

        header('Content-disposition: attachment; filename=admin-dash-settings.json');
        header('Content-type: application/json');
        echo json_encode($data);
    }

    public function getApiToken($pid) {
        $sql = "
            select api_token from redcap_user_rights as u
            left join redcap_projects as p on p.project_id = u.project_id
            where u.project_id = $pid
                  and username = '" . USERID . "'
        ";

        $sql = db_query($sql);
        $token = db_fetch_assoc($sql)['api_token'];

        echo $token;
    }

    public function sqlQuery($query = null) {
        $returnType = null;
        $data = array();

        if ($query == null) {
            $query = $this->curl_get_contents('php://input');
            $returnType = 'json';
        }

        // error out if no query
        if ($query == '') {
            $this->sqlError = 'No SQL query defined.';
            $data['error'] = $this->sqlError;
            echo json_encode($data);
        }
        // error out if query doesn't begin with "select"
        elseif (!(strtolower(substr($query, 0, 6)) == "select")) {
            $this->sqlError = 'SQL query is not a SELECT query.';
            $data['error'] = $this->sqlError;
            echo json_encode($data);
        }
        // execute the SQL statement
        else {
            // fix for group_concat limit
            global $conn;
            if (!isset($conn))
            {
                db_connect(false);
            }
            $conn->query('SET SESSION group_concat_max_len = 1000000;');

            $result = db_query($query);

            if (! $result || $result == 0)  // sql failed
            {
                $this->sqlError = json_encode(db_error());
            }

            if ($returnType == 'json') {
                if ($this->sqlError) {
                    $data['error'] = $this->sqlError;
                }
                else {
                    while ( $row = db_fetch_assoc( $result ) )
                    {
                        $data[] = $row;
                    }
                }

                echo json_encode($data);
            }
            else {
                return $result;
            }
        }
    }

    function curl_get_contents($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);

        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }
}
?>