<?php
namespace UIOWA\AdminDash;

use ExternalModules\AbstractExternalModule;

class AdminDash extends AbstractExternalModule
{
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

    public $configPID;
    private $currentPID;

    public function redcap_module_system_change_version($version, $old_version)
    {
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
                } else {
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
        if (version_compare('3.4', $old_version)) {
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

            foreach ($userDefinedTerms as $term) {
                $pwordSearchTerms[] = db_real_escape_string($term);
            }

            $pwordProjectSql = array();
            $pwordInstrumentSql = array();
            $pwordFieldSql = array();

            foreach ($pwordSearchTerms as $term) {
                $pwordProjectSql[] = '(app_title LIKE \'%' . $term . '%\')';

                $pwordInstrumentSql[] = '(form_name LIKE \'%' . $term . '%\')';

                $pwordFieldSql[] = '(field_name LIKE \'%' . $term . '%\')';
                $pwordFieldSql[] = '(element_label LIKE \'%' . $term . '%\')';
                $pwordFieldSql[] = '(element_note LIKE \'%' . $term . '%\')';
            }

            $pwordProjectSql = "(" . implode(" OR ", $pwordProjectSql) . ")";
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
            } else {
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

        $this->configPID = $this->getSystemSetting('config-pid');
        $this->currentPID = isset($_GET['pid']) ? $_GET['pid'] : $this->configPID;
    }

    function redcap_module_link_check_display($project_id, $link) {
        $config_report_id = $_GET['id'];
        $link_id = intval(explode('_', $link['name'])[1]);

        $reportRights = $this->getReportRights(USERID, $project_id);

        $reportId = json_decode(\REDCap::getData(array(
            'project_id' => $this->configPID,
            'fields' => array('report_id'),
            'return_format' => 'json',
            'filterLogic' => '[user_access_arm_1][sync_project_id] = ' . $project_id
        )), true)[$link_id]['report_id'];

        $reportInfo = json_decode(\REDCap::getData(array(
            'project_id' => $this->configPID,
            'events' => 'report_config_arm_1',
            'records' => $reportId,
            'fields' => ['report_id', 'report_title', 'report_icon'],
            'return_format' => 'json'
        )), true)[0];

        // inside config project only
//        if (
//            $link['name'] == 'Open Admin Dashboard (Current Report)' &&
//            isset($config_report_id) &&
//            $_GET['prefix'] != 'admin_dash' &&
//            $project_id == $this->configPID
//        ) {
//            $link['url'] = $link['url'] . '&id=' . $config_report_id;
//        }
        // project sync reports
        if (
            $reportRights[$reportInfo['report_id']]['project_view'] &&
            isset($reportId)
        ) {
            $link['name'] = 'Dashboard - ' . $reportInfo['report_title'];
            $link['url'] = $link['url'] . '&id=' . $reportInfo['report_id'];
            $link['icon'] = 'fas fa-' . $reportInfo['report_icon'];
        }
        else {
            $link['name'] = '';
            $link['url'] = '';
        }

        return $link;
    }

    function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance) {
        // load customizations for config project
        if ($project_id == $this->configPID) {
            $configDataDictionary = json_decode(\REDCap::getDataDictionary(
                $this->configPID, 'json', null, 'icon_lookup'), true);

            $formattingReference = array();

            foreach($configDataDictionary as $row) {
                $codes = preg_replace('(\d, )', '', $row['select_choices_or_calculations']);
                $formattingReference[$row['field_label']] = explode(' | ', $codes);
            }

            ?>
            <script>
                let UIOWA_AdminDash = {
                    reportUrl: '<?= $this->getUrl("index.php", false, $this->getSystemSetting("use-api-urls")) ?>',
                    postUrl: '<?= $this->getUrl("post.php") ?>',
                    queryTimeout: <?= $this->getSystemSetting('test-query-timeout') ?>,
                    fields: <?= json_encode(\REDCap::getFieldNames($_GET['page'])) ?>,
                    record: '<?= $_GET['id'] ?>',
                    iconLookup: <?= json_encode($formattingReference['icons']) ?>
                }
            </script>
            <script src="<?= $this->getUrl("/resources/ace/ace.js") ?>" type="text/javascript" charset="utf-8"></script>
            <script src="<?= $this->getUrl("/resources/ace/ext-language_tools.js") ?>" type="text/javascript" charset="utf-8"></script>
            <script src="<?= $this->getUrl("redcapDataEntryForm.js") ?>" type="text/javascript" charset="utf-8"></script>
            <?php
        }
    }

    function redcap_save_record($project_id, $record) {
        // generate column formatting instances
        if ($project_id == $this->configPID && $_POST['__chk__generate_column_formatting_RC_1'] == '1') {
            $this->saveReportColumns($project_id, $record, $_POST['test_query_column_list']);
        }
    }

    public function getJavascriptObject($report_id)
    {
        $reportList = json_decode(\REDCap::getData(array(
            'project_id' => $this->configPID,
            'return_format' => 'json',
            'filterLogic' => '[report_visibility] = "1"',
            'fields' => array('report_id', 'report_title', 'report_icon', 'report_type', 'folder_name', 'tab_color', 'tab_color_custom')
        )), true);

        $reportRights = $this->getReportRights(USERID, $_GET['pid']);

        // remove any reports user does not have access to
        foreach($reportList as $index => $report) {
            $accessDetails = $reportRights[$report['report_id']];

            if (SUPER_USER !== '1' && !$accessDetails['sync_project_access'] && !$accessDetails['executive_view']) {
                unset($reportList[$index]);
            }
        }

        $loadedReportMetadata = json_decode(\REDCap::getData(array(
            'project_id' => $this->configPID,
            'return_format' => 'json',
            'records' => $report_id
        )), true);

        $configDataDictionary = json_decode(\REDCap::getDataDictionary(
            $this->configPID, 'json', null, null, 'formatting_reference'), true);

        $formattingReference = array();

        foreach($configDataDictionary as $row) {
            $codes = preg_replace('(\d, )', '', $row['select_choices_or_calculations']);
            $formattingReference[$row['field_label']] = explode(' | ', $codes);
        }

        $formattedMeta = array();

        foreach($loadedReportMetadata as $index => $row) {
            if ($row['redcap_repeat_instrument'] !== '') {
                if (isset($row['column_name'])) {
                    $instrument = $row['redcap_repeat_instrument'];
                    $instanceKey = $row['column_name'];
//                $formattedMeta['columnVis']['dashboard'][$column_name] = $row['show_column___1'] === '1';
//                $formattedMeta['columnVis']['childRow'][$column_name] = $row['show_column___2'] === '1';
//                $formattedMeta['columnVis']['export'][$column_name] = $row['show_column___3'] === '1';
                }
                else if (isset($row['join_project_id'])) {
                    $instrument = $row['redcap_repeat_instrument'];
                    $instanceKey = $row['join_project_id'];
                }

                $formattedMeta[$instrument][$instanceKey] = $row;
            }
            else {
                if ($row['redcap_event_name'] == 'report_config_arm_1') {
                    $formattedMeta['config'] = $row;
                }
            }
        }

        return json_encode(array(
            'baseRedcapUrl' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . SERVER_NAME . APP_PATH_WEBROOT,
            'baseReportUrl' => $this->getUrl("index.php", false, $this->getSystemSetting("use-api-urls")), // todo - config setting
            'postUrl' => $this->getUrl("post.php?id=" . $report_id),
            'reportLookup' => $reportList,
            'loadedReport' => array(
                'meta' => $formattedMeta,
                'ready' => false
            ),
            'noReportId' => sizeof($formattedMeta) === 0,
            'showAdminControls' => SUPER_USER,
            'configPID' => $this->configPID,
            'formattingReference' => $formattingReference,
            'isFetching' => true,
            'executiveView' => $reportRights[$report_id]['executive_view']
        ));

//        if ($showChangelog) { // todo changelog?
//            $this->setSystemSetting('show-changelog', false);
//        }
    }

    public function runReport($params) { // id, sql
        $report_id = $params['id']; // user-facing call - lookup query by record id
        $sql = $params['sql']; // test query from data entry form

        // get sql query from REDCap record
        if (!isset($sql)) {
            $data = \REDCap::getData(array(
                'project_id' => $this->configPID,
                'return_format' => 'json',
                'records' => $report_id,
                'fields' => 'report_sql'
            ));

            $sql = json_decode($data, true)[0]['report_sql'];
        }

        $returnData = array();

        $sql = \Piping::pipeSpecialTags($sql, $this->currentPID);

        // error out if no query
        if ($sql == '') {
            $returnData['error'] = 'No SQL query defined.';
        }
        elseif (!(strtolower(substr($sql, 0, 6)) == "select")) {
            $returnData['error'] = 'SQL query is not a SELECT query.';
        }
        else {
            // fix for group_concat limit
            $this->query('SET SESSION group_concat_max_len = 1000000;', []);

            //todo handle error

            $result = $this->query($sql, []);

            // prepare data for table
            while ($row = db_fetch_assoc($result)) {
                $returnData[] = $row;
            }

            // only return column/row info if test query
            if (isset($params['test'])) {
                $returnData = array(
                    'columns' => array_keys($returnData[0]),
                    'row_count' => sizeof($returnData)
                );
            }
        }

        echo json_encode($returnData);
    }

    public function saveReportColumns($project_id, $record, $columns)
    {
        $columns = json_decode($columns);
        $json = array();
//        $validTags = array('#hidden', '#ignore');
        $groupCheck = array();

        foreach ($columns as $index => $column_name) {
            $instance = array(
                'report_id' => $record,
                'redcap_repeat_instrument' => 'column_formatting',
                'redcap_repeat_instance' => $index + 1,
                'column_name' => $column_name,
                'dashboard_show_column' => 1,
                'export_show_column' => 1,
                'column_formatting_complete' => 0
            );

            $formattingPresets = array(
                'project_id' => array(
                    'link_type' => 5,
                    'export_urls' => 0
                ),
                'app_title' => array(
                    'link_type' => 1,
                    'link_source_column' => 'project_id',
                    'export_urls' => 0
                ),
                'username' => array(
                    'link_type' => 6,
                    'export_urls' => 0
                ),
                'hash' => array(
                    'link_type' => 8,
                    'export_urls' => 0
                ),
                'email' => array(
                    'link_type' => 9,
                    'export_urls' => 0
                ),
                'status' => array(
                    'code_type' => 1,
                    'export_codes' => 0
                ),
                'purpose' => array(
                    'code_type' => 2,
                    'export_codes' => 0
                ),
                'purpose_other' => array(
                    'code_type' => 3,
                    'export_codes' => 0
                )
            );

            // check for hashtag shorthand
            $tags = explode('#', $column_name);
            $root_column_name = array_shift($tags);

            // flags for tracking what formatting can/cannot be applied
            $hidden = in_array('hidden', $tags);
            $ignore = in_array('ignore', $tags);
            $group = in_array('group', $tags);

            // add default separator for #group
            if ($group) {
                $instance['group_concat_separator'] = '@@@';
                $groupCheck[$root_column_name] = $column_name;
            }

            // set hidden with #hide, otherwise set default filter visible
            if ($hidden) {
                $instance['dashboard_show_column'] = 0;
                $instance['export_show_column'] = 0;
                $instance['column_formatting_complete'] = 2;
            }
            else {
                $instance['dashboard_show_filter'] = 1;
            }

            // skip all formatting rules if column is hidden or tagged as "ignore"
            if (!$hidden && !$ignore) {
                // if there are formatting presets, apply them
                if (array_key_exists($root_column_name, $formattingPresets)) {
                    $instance = array_merge($instance, $formattingPresets[$root_column_name]);

                    // make sure grouped columns have grouped source column
                    if ($group && array_key_exists($root_column_name, $groupCheck)) {
                        $instance['link_source_column'] = $groupCheck[$instance['link_source_column']];
                    }

                    // set record status to unverified so user can review formatting
                    $instance['column_formatting_complete'] = 1;
                }
                // match partial "email" column
                else if (strpos($root_column_name, 'email') !== false) {
                    $instance = array_merge($instance, $formattingPresets['email']);
                }

                // if no source column specified, default to self
                if (isset($instance['link_type']) && !isset($instance['link_source_column'])) {
                    $instance['link_source_column'] = $instance['column_name'];
                }

                // use select filter for coded data
                if (isset($instance['code_type'])) {
                    $instance['dashboard_show_filter'] = 2;
                }
            }

            array_push($json, $instance);
        }

//        $reportSql = json_decode(\REDCap::getData(
//            $project_id,
//            'json',
//            $record,
//            'report_sql'
//        ), true)[0]['report_sql'];
//
//        // strip shorthand tags out of query
//        $reportSql = str_replace($validTags, '', $reportSql);

        // toggle formatting trigger off
        array_push($json, array(
            'report_id' => $record,
//            'report_sql' => $reportSql,
            'generate_column_formatting___1' => 0
        ));

        \REDCap::saveData(
            $project_id,
            'json',
            json_encode($json),
            'overwrite',
            'YMD'
        );
    }

    public function getReportRights($username, $pid)
    {
        $userRightsArray = array();

        $allReportRights = \REDCap::getData(array(
            'project_id' => $this->configPID,
            'return_format' => 'json',
            'events' => ['user_access_arm_1', 'user_access_arm_2'],
            'fields' => [
                'report_id',
                'project_sync_enabled',
                'sync_project_id',
                'project_sync_access',
                'project_sync_role',
                'project_sync_export',
                'executive_username',
                'executive_view',
                'executive_export'
        ]));

        $allReportRights = json_decode($allReportRights, true);

        foreach ($allReportRights as $index => $reportRights) {
            $report_id = $reportRights['report_id'];

            if (!isset($userRightsArray[$report_id])) {
                $userRightsArray[$report_id] = array(
                    'project_view' => false,
                    'export_access' => false,
                    'executive_view' => false
                );
            }

            if ($reportRights['redcap_repeat_instrument'] !== '') {
                if ($reportRights['executive_username'] == $username) {
                    if ($reportRights['executive_view'] == '1') {
                        $userRightsArray[$report_id]['executive_view'] = true;

                        if ($reportRights['executive_export'] == '1') {
                            $userRightsArray[$report_id]['export_access'] = true;
                        }
                    }
                }
                elseif (
                    $reportRights['redcap_repeat_instrument'] == 'project_sync' &&
                    $reportRights['project_sync_enabled'] == '1' &&
                    $pid == intval($reportRights['sync_project_id'])
                ) {
                    // get project user rights
                    $projectRights = $this->query("
                        select
                            rur.data_export_tool,
                            rur.reports,
                            r.role_name
                        from redcap_user_rights rur
                        left join redcap_user_information rui on rur.username = rui.username
                        left join redcap_user_roles r on rur.role_id = r.role_id
                        where rui.username = ? and rur.project_id = ?
                    ", [$username, $pid]);

                    $projectRights = db_fetch_assoc($projectRights);

                    if ($reportRights['project_sync_access'] == '3') { // match role
                        $userRightsArray[$report_id]['project_view'] = $reportRights['project_sync_role'] == $projectRights['role_name'];
                    } elseif ($reportRights['project_sync_access'] == '2') { // match report rights
                        $userRightsArray[$report_id]['project_view'] = $projectRights['reports'] == '1';
                    } elseif ($reportRights['project_sync_access'] == '1') { // any project-level rights
                        $userRightsArray[$report_id]['project_view'] = true;
                    }

                    if ($reportRights['project_sync_export'] == '2') { // only users with "full data set" rights can export
                        $userRightsArray[$report_id]['export_access'] = $projectRights['data_export_tool'] == '1';
                    } elseif ($reportRights['project_sync_export'] == '1') { // any user can export
                        $userRightsArray[$report_id]['export_access'] = true;
                    }
                }
            }
        }

        return $userRightsArray;
    }

//    public function updateProjectSyncBookmark($report_id, $target_project_id, $report_title) {
//        $next_ext_id = db_fetch_assoc($this->query("select max(ext_id) as 'ext_id' from redcap_external_links", []))['ext_id'] + 1;
//        $next_link_order = db_fetch_assoc($this->query("select max(link_order) as 'link_order' from redcap_external_links where project_id = ?", [$target_project_id]))['link_order'] + 1;
//
//        $this->query("
//                insert into redcap_external_links (
//                   project_id,
//                   link_order,
//                   link_url,
//                   link_label,
//                   open_new_window,
//                   link_type,
//                   user_access,
//                   append_record_info,
//                   append_pid
//                )
//                values(?, ?, ?, ?, ?, ?, ?, ?, ?)
//            ",
//            [
//                $target_project_id,
//                $next_link_order,
//                $this->getUrl("index.php", false, $this->getSystemSetting("use-api-urls")),
//                $report_title,
//                0,
//                'LINK',
//                'ALL',
//                0,
//                1
//            ]
//        );
//    }

    public function getProjectList()
    {
        $result = $this->query("
            select
                ur.project_id,
                p.app_title
            from redcap_user_rights ur
            left join redcap_projects p on p.project_id = ur.project_id
            where ur.username = ?
        ", [USERID]);

        $projects = array();

        while ($row = db_fetch_assoc($result)) {
            $projects[] = array(
                "value" => $row['project_id'],
                "label" => $row['project_id'] . ' - ' . $row['app_title']
            );
        }

        echo json_encode($projects);
    }

    public function getProjectFields($pid)
    {
        $dd = \REDCap::getDataDictionary($pid, 'array');

        $fields = array();

        foreach ($dd as $field) {
            $fields[$field['form_name']][] = $field['field_name'];
        }

        $result = $this->query("select app_title from redcap_projects where project_id = ?", [$pid]);
        $projectTitle = db_fetch_assoc($result)['app_title'];

        echo json_encode(array(
            'projectTitle' => $projectTitle,
            'fieldLookup' => $fields
        ));
    }

    public function getProjectReports() {
        $reportList = array();

        $sql = "
            select report_id, title
            from redcap_reports
            where project_id = 19
            order by report_order asc
        ";

        $result = $this->query($sql);

        while ($row = db_fetch_assoc($result)) {
            $reportList[$result['report_id']] = $result['title'];
        }

        echo json_encode($reportList);
    }

    public function joinProjectData($record_id)
    {
        $joinConfig = json_decode(\REDCap::getData(array(
            'project_id' => $this->configPID,
            'return_format' => 'json',
            'records' => $record_id,
            'fields' => array(
                'join_project_id',
                'join_report_id',
                'join_primary_field'
            )
        )), true);

        $joinedData = array();
        $firstProject = true;
        $primaryFieldP1 = '';

        foreach($joinConfig as $join) {
            $result = $this->query("
                select rf.field_name, r.advanced_logic from redcap_reports r
                left join redcap_reports_fields rf on r.report_id = rf.report_id
                where r.project_id = ? and r.report_id = ?
            ", [$join['join_project_id'], $join['join_report_id']]);


            $fields = array();
            $logic = '';
            $firstRow = true;

            while ($row = db_fetch_assoc($result)) {
                if ($firstRow) {
                    $logic = $row['advanced_logic'];
                    $firstRow = false;
                }

                array_push($fields, $row['field_name']);
            }

            $newData = json_decode(\REDCap::getData(array(
                'project_id' => $pid,
                'return_format' => 'json',
//                'exportAsLabels' => $params['showChoiceLabels'],
                'filterLogic' => $logic,
                'fields' => $fields
            )), true);

            if ($firstProject) {
                $joinedData = $newData;
                $firstProject = false;
                $primaryFieldP1 = $join['join_primary_field'];
            }
            else {
                foreach ($newData as $index => $record) {
                    $primaryKeyP1 = $joinedData[$index][$primaryFieldP1];

                    $primaryFieldP2 = $join['join_primary_field'];
                    $primaryKeyP2 = $record[$primaryFieldP2];

                    // match to joined project
                    if (isset($primaryKeyP2) && $primaryKeyP1 === $primaryKeyP2) {
                        $recordDataP1 = $joinedData[$index];
                        $recordDataP2 = $newData[$index];

                        unset($recordDataP2[$primaryFieldP2]);

                        $joinedData[$index] = array_merge($recordDataP1, $recordDataP2);
                    }
//                    else if ($params['matchesOnly']) {
//                        unset($data_p1[$index]);
//                    }
                }
            }
        }

//        $eventId_p2 = $this->getFirstEventId($pid2);

        echo json_encode($joinedData);
    }

    public function getAdditionalInfo($params) { // params - type, whereVal
        $queries = array(
            'user' => '
                select user_email, user_firstname, user_lastname
                from redcap_user_information
                where username = ?
                limit 1
            ',
            'project' => '
                select app_title
                from redcap_projects
                where project_id = ?
                limit 1
            ',
            'report' => '
                select title
                from redcap_reports
                where report_id = ? and project_id = ?
                limit 1
            '
        );

        $result = $this->query($queries[$params['type']], $params['whereVal']);

        echo json_encode(db_fetch_assoc($result));
    }

    public function convertOldReports() {
        // get custom reports from db and import them into redcap project
    }
}
?>