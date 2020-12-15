<?php
namespace UIOWA\AdminDash;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

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

    private $configPID;
    private $currentPID;
    private $overridePID;

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
        $this->currentPID = $_GET['pid'];
        $this->overridePID = $_GET['override']; // todo
    }

    function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance)
    {
        if ($project_id != $this->configPID) {
            return;
        }
?>
        <script src="<?= $this->getUrl("/resources/ace/ace.js") ?>" type="text/javascript" charset="utf-8"></script>

        <script>
            let $iconTr = $('#report_icon-tr');
            let $sqlTr = $('#report_sql-tr');

            $iconTr.find('.data input').css('width', 'auto')

            let $iconInput = $('input[name="report_icon"]');

            $iconTr.find('.data')
                .prepend(`
                    <span class="fas fa-2x icon-preview" style="margin: 10px">
                        <span class='fa-${$iconInput.val()}'></span>
                    </span>
                `);

            $iconInput.on('input',function () {
                $('.icon-preview span')
                    .removeClass()
                    .addClass('fa-' + $iconInput.val());
            });

            $(`
                <tr class="ace-editor-td" style="height:400px">
                    <td colspan="2">
                        <textarea id="report_sql-editor"></textarea>
                        <div id="testQueryResult" style="float:left; padding-left: 10px; padding-top: 10px; max-width:80%;"></div>
                            <div style="text-align:right; float:right; padding-bottom: 10px; padding-right: 10px">
                                <button type="button" class="btn btn-info test-query">Test Query</button>
                            </div>
                    </td>
                </tr>
            `).insertAfter($sqlTr);

            $sqlTr.find('#report_sql-expand').hide();
            $sqlTr.find('.data').hide();

            $sqlTr.find('.labelrc').attr('colspan', 2);
            $('#report_sql-editor').attr('colspan', 2).find('pre');

            // initialize ace editor
            editor = ace.edit("report_sql-editor", {
                theme: "ace/theme/monokai",
                mode: "ace/mode/sql",
                minLines: 10
            });

            editor.setValue($sqlTr.find('.data > textarea').val());

            editor.session.on('change', function() {
                $("#report_sql").val(editor.getValue()).change();
                $('.test-query').html('Test Query').prop('disabled', false).removeClass('btn-danger btn-success').addClass('btn-info');
            });

            $('.ace_editor').css('height', '400px');

            $('input[name="report_type___radio"]').on('change', function() {
                if ($(this).val() === '1') {
                    $('.ace-editor-td').show();
                }
                else {
                    $('.ace-editor-td').hide();
                }
            })

            $('.test-query').click(function() {
                var testQueryButton = $(this);

                testQueryButton.prop('disabled', true);
                testQueryButton.html('<i class="fas fa-spinner fa-spin test-progress"></i>');

                var startTime = performance.now();

                $.ajax({
                    method: 'POST',
                    url: '<?= $this->getUrl("post.php?method=testQuery") ?>',
                    data: { params: editor.getValue() },
                    timeout: 3000 //todo should come from settings
                })
                    .done(function(data) {
                        console.log(data);
                        var endTime = performance.now();
                        data = JSON.parse(data);

                        if (data['error']) {
                            testQueryButton.html('<i class="fas fa-times"></i> Error').removeClass('btn-info').addClass('btn-danger');
                            $('#testQueryResult').html('<span style="color:red;">Query failed: ' + data['error'] + '</span>');

                            $('[name="test_query_error"]').val(data['error']);
                            $('[name="test_query_columns"]').val('');
                            $('[name="test_query_rows"]').val('');
                            $('[name="test_query_success___radio"][value=0]')
                                .prop('disabled', '')
                                .prop("checked", true)
                                .click()
                                .prop('disabled', 'disabled');
                        }
                        else {
                            testQueryButton.html('<i class="fas fa-check"></i> Success').removeClass('btn-info').addClass('btn-success');

                            var isFirstRow = true;
                            var rowCount = 0;
                            var columns = [];

                            $.each(data, function (index, row) {
                                if (isFirstRow) {
                                    columns = Object.keys(row);
                                    isFirstRow = false;
                                }

                                rowCount++
                            });

                            $('[name="test_query_error"]').val('');
                            $('[name="test_query_columns"]').val(columns.length);
                            $('[name="test_query_column_list"]').val(JSON.stringify(columns, null, 2));
                            $('[name="test_query_rows"]').val(rowCount);
                            $('[name="test_query_success___radio"][value=1]')
                                .prop('disabled', '')
                                .prop("checked", true)
                                .click()
                                .prop('disabled', 'disabled');
                        }

                        $('#testQueryResult').html('<span style="color:green;">Query returned ' + rowCount + ' row(s) in ' + Math.floor(endTime - startTime) + 'ms</span>');
                    })
                    .fail(function() {
                        testQueryButton.html('<i class="fas fa-times"></i> Error').removeClass('btn-info').addClass('btn-danger');
                        $('#testQueryResult').html('<span style="color:red;">Query timed out! You may want to optimize your query before running again or increase/disable the timeout via this module\'s settings.</span>');
                    })
            });
        </script>
            <?php
    }

    function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {
        if ($project_id != $this->configPID) {
            return;
        }

        if ($instrument == 'report_config' or $instrument == 'advanced_config') {
            $this->saveReportColumns($project_id, $record);
        }
    }

    public function getJavascriptObject($report_id)
    {
        $this->overridePID = $_GET['override']; // todo

//        // Get list of valid "target" REDCap projects for export feature
//        $sql = "
//            select u.project_id, app_title from redcap_user_rights as u
//            left join redcap_projects as p on p.project_id = u.project_id
//            where api_token is not null
//                  and api_import = 1
//                  and username = '" . USERID . "'
//        ";
//
//        $sql = db_query($sql);
//        $exportProjects = [];
//
//        while ($row = db_fetch_assoc($sql)) {
//            array_push($exportProjects, $row);
//        }
//
//        $showChangelog = json_encode($this->getSystemSetting('show-changelog'));

        $reportList = json_decode(\REDCap::getData(array(
            'project_id' => $this->configPID,
            'return_format' => 'json',
            'filterLogic' => '[report_title] <> "" and [report_visibility] = "1"',
            'fields' => array('report_id', 'report_title', 'report_icon', 'report_type')
        )), true);

        $loadedReportMetadata = json_decode(\REDCap::getData(array(
            'project_id' => $this->configPID,
            'return_format' => 'json',
            'records' => $report_id
        )), true);

        $configDataDictionary = json_decode(\REDCap::getDataDictionary(
            $this->configPID, 'json', null, null, 'advanced_config'), true);

        $formattingReference = array();

        foreach($configDataDictionary as $row) {
            $codes = preg_replace('(\d, )', '', $row['select_choices_or_calculations']);
            $formattingReference[$row['field_label']] = explode(' | ', $codes);
        }

        $formattedMeta = array();

        foreach($loadedReportMetadata as $index => $row) {
            if ($row['redcap_repeat_instrument'] == 'column_formatting') {
                $column_name = $row['column_name'];

                $formattedMeta['columnDetails'][$column_name] = $row;

                $formattedMeta['columnVis']['dashboard'][$column_name] = $row['show_column___1'] === '1';
                $formattedMeta['columnVis']['childRow'][$column_name] = $row['show_column___2'] === '1';
                $formattedMeta['columnVis']['export'][$column_name] = $row['show_column___3'] === '1';
            }
            elseif ($row['redcap_repeat_instrument'] == 'project_join') {
                $instanceId = $row['redcap_repeat_instance'];

                $formattedMeta['joinDetails'][$instanceId] = $row;
            }
            else {
                $formattedMeta['config'] = $row;
            }
        }

        return json_encode(array(
            'baseRedcapUrl' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . SERVER_NAME . APP_PATH_WEBROOT,
            'baseReportUrl' => $this->getUrl("index.php"),
            'postUrl' => $this->getUrl("post.php"),
            'reportLookup' => $reportList,
            'loadedReport' => array(
                'meta' => $formattedMeta,
                'ready' => false
            ),
            'noReportId' => sizeof($formattedMeta) === 0,
            'showAdminControls' => SUPER_USER,
            'configPID' => $this->configPID,
            'formattingReference' => $formattingReference
        ));

//        if ($showChangelog) {
//            $this->setSystemSetting('show-changelog', false);
//        }
    }

//    public function getMetadataProjectId() {
//        $result = $this->query("
//            select project_id from redcap_external_module_settings ems
//            left join redcap_external_modules em on ems.external_module_id = em.external_module_id
//            where em.directory_prefix = 'admin_dash'
//                and ems.project_id
//                and ems.`key` = 'enabled' is not null
//            limit 1");
//
//        return db_fetch_assoc($result)['project_id'];
//    }

    public function runReport($report_id) {
        // get sql query from REDCap record
        $data = \REDCap::getData(array(
            'project_id' => $this->configPID,
            'return_format' => 'json',
            'records' => $report_id,
            'fields' => 'report_sql'
        ));
        $returnData = array();

        $sql = json_decode($data, true)[0]['report_sql'];
//        $sql = $this->includeDynamicValues($sql);
        $sql = \Piping::pipeSpecialTags($sql,
            isset($this->overridePID) ? $this->overridePID : $this->currentPID
        );

        // error out if no query
        if ($sql == '') {
            $returnData['error'] = 'No SQL query defined.';
        }
        elseif (!(strtolower(substr($sql, 0, 6)) == "select")) {
            $returnData['error'] = 'SQL query is not a SELECT query.';
        }
        else {
            // fix for group_concat limit
            $this->query('SET SESSION group_concat_max_len = 1000000;');

            //todo handle error

            // prepare data for table
            $result = $this->query($sql);
            while ($row = db_fetch_assoc($result)) {
                $returnData[] = $row;
            }
        }

        echo json_encode($returnData);
    }

    public function testQuery($sql) {
//        $sql = $this->includeDynamicValues($sql);
        $sql = \Piping::pipeSpecialTags($sql, $this->currentPID);
        $returnData = array();
        // fix for group_concat limit
        $this->query('SET SESSION group_concat_max_len = 1000000;');

        //todo handle invalid queries on js side

        // prepare data for table
        $result = $this->query($sql);
        while ($row = db_fetch_assoc($result)) {
            $returnData[] = $row;
        }

        echo json_encode($returnData);
    }

    public function includeDynamicValues($sql) {
        return str_replace('[USERID]', USERID, $sql);
    }

    private function generateReportReference()
    {
        $hideDeleted = !self::getSystemSetting('show-deleted-projects');
        $hideArchived = !self::getSystemSetting('show-archived-projects');
        $hidePractice = !self::getSystemSetting('show-practice-projects');
        $moduleDirectory = $this->getModulePath();

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

        $reportReference = json_decode(file_get_contents($moduleDirectory . "config/reportReference.json"), true);

        foreach ($reportReference as $index => $report) {
            $reportReference[$index]['readOnly'] = true;
            $reportReference[$index]['type'] = 'table';

            // load report sql from file and add filters
            $reportSql = file_get_contents($moduleDirectory . $reportReference[$index]['sql']);
//            $reportSql = str_replace('$formattedFilterSql', $formattedFilterSql, $reportSql);
//            $reportSql = str_replace('$formattedWhereFilterSql', $formattedWhereFilterSql, $reportSql);
            $reportReference[$index]['sql'] = $reportSql;
        }

        ?>
        <script>
            var defaultReports = <?= json_encode($reportReference) ?>;
            UIOWA_AdminDash.defaultReportNames = $.map(defaultReports, function (report) {
                return report['reportName'];
            });
        </script>
        <?php

        $customReports = $this->getSystemSetting('custom-reports');

        foreach ($customReports as $report) {
            array_push($reportReference, $report);
        }

        return $reportReference;

//        $params = array(
//            'project_id' => 19, // todo get this pid
//            'return_format' => 'json',
//            'fields' => array(
//                'report_id',
//                'report_title',
//                'report_icon'
//            ),
//            'filterLogic' => '[report_visibility] <> 3' // todo filter for admin/user
//        );
//
//        $data = \REDCap::getData($params);
//
//        return $data;
    }

    private function formatQueryResults($result, $fromDb = true)
    {
        $isFirstRow = true;
        $record_id = 1;

        $tableData = array(
            'headers' => array(),
            'data' => array(),
            'project_data' => array(),
            'project_headers' => array()
        );

        if ($fromDb) {
            while ($row = db_fetch_assoc($result)) {
                $newResult[] = $row;
            }

            $result = $newResult;
        }

        foreach ($result as $row) {
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
        } else {
            ?>
            <script>
                UIOWA_AdminDash.data = <?= json_encode($tableData) ?>;
            </script>
            <?php

            return null;
        }
    }

    public function saveReportColumns($project_id, $record)
    {
//        $columns = json_decode($params['columns']);
        $config = json_decode(\REDCap::getData(
            $project_id,
            'json',
            $record,
            [
                'test_query_column_list',
                'auto_header_format',
                'auto_link_format',
                'initial_formatting_created'
            ]
        ), true)[0];

        // don't do this a second time (unless prompted by user)
        if ($config['initial_formatting_created___1'] == '1') {
            return;
        }

        $columns = json_decode($config['test_query_column_list']);
        $json = array();
        $index = 1;

        foreach ($columns as $column) {
            $instance = array(
                'report_id' => $record,
                'redcap_repeat_instrument' => 'column_formatting',
                'redcap_repeat_instance' => $index,
                'column_name' => $column,
                'show_column___1' => 1,
                'show_column___2' => 0,
                'show_column___3' => 1,
                'group_concat' => 0,
                'link' => 0,
                'code_lookup' => 0
            );

            $groupStrIndex = strpos($column, '_GROUP');
            $grouped = false;

            if ($groupStrIndex > -1) {
                $column = substr($column, 0, $groupStrIndex);
                $grouped = true;
            }

            $newHeader = $column;

            if ($grouped) {
                $instance['group_concat'] = 1;
                $instance['group_concat_separator'] = '@@@';
                $instance['column_formatting_complete'] = 1;
            }

            if ($config['auto_header_format___1'] == '1') {
                $newHeader = preg_replace('/_/', ' ', $newHeader);
            }
            if ($config['auto_header_format___2'] == '1') {
                $newHeader = ucwords($newHeader);
            }
            if ($newHeader != $column) {
                $instance['display_header'] = $newHeader;
            }

            if ($config['auto_link_format'] == '1') {
                $linked = false;

                // enable links by default if column name matches
                if ($column == 'project_id') {
                    $instance['link_type'] = 1;
                    $instance['specify_project_link'] = 1;
                    $linked = true;
                } else if ($column == 'username') {
                    $instance['link_type'] = 2;
                    $instance['specify_user_link'] = 1;
                    $linked = true;
                } else if ($column == 'hash') {
                    $instance['link_type'] = 3;
                    $linked = true;
                } else if (in_array($column, array(
                    'project_pi_email',
                    'user_email',
                    'user_email2',
                    'user_email3'
                ))) {
                    $instance['link_type'] = 4;
                    $linked = true;
                }

                if ($linked) {
                    $instance['link'] = 1;
                    $instance['column_formatting_complete'] = 1;
                }
                else {
                    $instance['column_formatting_complete'] = 2;
                }
            }

            // todo is it safe to assume "status" == project status?

            if ($column == 'purpose') {
                $instance['code_lookup'] = 1;
                $instance['specify_code_lookup'] = 2;
                $instance['column_formatting_complete'] = 1;
            } else if ($column == 'purpose_other') {
                $instance['code_lookup'] = 1;
                $instance['specify_code_lookup'] = 3;
                $instance['column_formatting_complete'] = 1;
            }

            // todo formatting for project statuses/user suspension

            array_push($json, $instance);

            $index++;

            // set advanced formatting instrument to complete
            array_push($json, array('report_id' => $record, 'initial_formatting_created___1' => '1', 'advanced_config_complete' => 2));

            echo json_encode(\REDCap::saveData($project_id, 'json', json_encode($json), 'overwrite', 'YMD'));

        }
    }

    public function verifyUserRights($report_id, $username)
    {
        $userAllowed = false;
        $exportAllowed = false; // todo

        $reportRights = \REDCap::getData(array(
            'project_id' => $this->configPID,
            'return_format' => 'json',
            'records' => $report_id,
            'fields' => [
                'project_sync_enabled',
                'sync_pid',
                'project_sync_access',
                'project_sync_role',
                'project_sync_export'
        ]));

        $reportRights = json_decode($reportRights, true)[0];

        if ($reportRights['project_sync_enabled']) {
            $allowedPIDs = explode(',', $reportRights['sync_pid']);
            $projectAllowed = '';

            // check for role
            $projectRights = \REDCap::getUserRights($username);
//            $rights = $this->query("select
//                rur.project_id,
//                rur.data_export_tool,
//                rur.reports,
//                r.role_name
//            from redcap_user_rights rur
//            left join redcap_user_information rui on rur.username = rui.username
//            left join redcap_user_roles r on rur.role_id = r.role_id
//            where rui.username = '$username'");

            $projectRights = $projectRights[$username];

            if (in_array($projectRights['project_id'], $allowedPIDs)) {
                $projectAllowed = $projectRights['project_id'];
            }

            if ($projectAllowed != '') {
                if (
                    $reportRights['project_sync_access'] == '3' &&
                    isset($reportRights['project_sync_role']) &&
                    isset($projectRights['role_name'])
                ) { // match role
                    $userAllowed = $reportRights['project_sync_role'] == $projectRights['role_name'];
                } elseif ($reportRights['project_sync_access'] == '2') { // match report rights
                    $userAllowed = $projectRights['reports'] == 1;
                } elseif ($reportRights['project_sync_access'] == '1') { // any project-level rights
                    $userAllowed = $projectAllowed != '';
                }
            }
        }

        return $userAllowed;
    }

    public function getProjectList()
    {
        $username = USERID;

        $sql = "
            select
                ur.project_id,
                p.app_title
            from redcap_user_rights ur
            left join redcap_projects p on p.project_id = ur.project_id
            where ur.username = '$username'
        ";

        $result = $this->query($sql);

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

        $result = $this->query("select app_title from redcap_projects where project_id = $pid");
        $projectTitle = db_fetch_assoc($result)['app_title'];

        echo json_encode(array(
            'projectTitle' => $projectTitle,
            'fieldLookup' => $fields
        ));
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
            $pid = $join['join_project_id'];
            $rid = $join['join_report_id'];

            $sql = "
                select rf.field_name, r.advanced_logic from redcap_reports r
                left join redcap_reports_fields rf on r.report_id = rf.report_id
                where r.project_id = $pid and r.report_id = $rid
            ";

            $fields = array();
            $logic = '';
            $firstRow = true;

            $result = $this->query($sql);

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
}
?>