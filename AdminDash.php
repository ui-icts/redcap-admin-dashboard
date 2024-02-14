<?php
namespace UIOWA\AdminDash;

use ExternalModules\ExternalModules;
use ExternalModules\AbstractExternalModule;

class AdminDash extends AbstractExternalModule
{
    public $configPID;
    public $currentPID;

    public function __construct()
    {
        parent::__construct();
        define("MODULE_DOCROOT", $this->getModulePath());

        $this->configPID = $this->getSystemSetting('config-pid');
        $this->currentPID = isset($_GET['pid']) ? $_GET['pid'] : $this->configPID;
    
    }  

    function redcap_module_system_change_version($version, $old_version) {
        $result = $this->query('SELECT value FROM redcap_config WHERE field_name = \'auth_meth_global\'', []);
        $authMethod = db_fetch_assoc($result)['value'];

        if ($authMethod == 'shibboleth') {
            $this->setSystemSetting('use-api-urls', false);
        } else {
            $this->setSystemSetting('use-api-urls', true);
        }
    }

    function redcap_module_link_check_display($project_id, $link) {
        if ($project_id) {
            $link_id = intval(explode('_', $link['name'])[1]);

            $reportRights = $this->getUserAccess(USERID, $project_id);

            $reportId = json_decode(\REDCap::getData(array(
                'project_id' => $this->configPID,
                'fields' => array('report_id'),
                'return_format' => 'json',
                'events' => ['user_access_arm_1', 'user_access_arm_2'],
                'filterLogic' => '[sync_project_id] = ' . $project_id
            )), true)[$link_id]['report_id'];

            $reportInfo = json_decode(\REDCap::getData(array(
                'project_id' => $this->configPID,
                'events' => ['report_config_arm_1', 'report_config_arm_2'],
                'records' => $reportId,
                'fields' => ['report_id', 'report_title', 'report_icon'],
                'return_format' => 'json'
            )), true)[0];

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
        }

        return $link;
    }

    function redcap_module_project_enable($version, $project_id) {

        if(SUPER_USER === "1") {
            $configPid = $this->getSystemSetting("config-pid");

            if (!isset($configPid)) {
                //  check if link_source_column field exists in project and if it's been set.
                $query = $this->query('SELECT element_enum FROM redcap_metadata WHERE field_name = "link_source_column" AND project_id = ?', [$project_id]);
                $queryResults = $query->fetch_assoc();
                $hasLinkSourceColumnField = false;
                if($queryResults !== null) {
                    $hasLinkSourceColumnField2 = array_key_exists('element_enum', $queryResults);
    
                    if($hasLinkSourceColumnField2) {
                        $hasLinkSourceColumnField = true;
                        $sqlEnum = $queryResults['element_enum'];

                        if ($sqlEnum === null || $sqlEnum === '') {
                        
                            $dataTable = method_exists('\REDCap', 'getDataTable') ? "[data-table]" : "redcap_data";
            
                            $linkSourceColumnSql = 'SELECT value, value FROM ' . $dataTable . '
                            WHERE project_id = [project-id] and
                            field_name = "column_name" AND
                            record = [record-name]
                            ORDER BY instance ASC';
            
                            // add missing sql field (and other settings not included in XML)
                            $this->query(
                                "UPDATE redcap_metadata SET element_enum =
                                    ?
                                    WHERE field_name = 'link_source_column' AND project_id = ?
                                ",
                                [$linkSourceColumnSql, $project_id]
                            );

                            // Get the next order number for bookmark
                            $query = $this->query("SELECT max(link_order) FROM redcap_external_links WHERE project_id = ?", [$project_id]);
                            $max_link_order = $query->fetch_assoc()['link_order'];
                            $next_link_order = (is_numeric($max_link_order) ? $max_link_order+1 : 1);
                
                            // Insert into table
                            $this->query("INSERT INTO redcap_external_links (project_id, link_order, link_label, link_url, open_new_window, link_type,
                                link_to_project_id, user_access, append_record_info) values
                                (?, ?, ?, ?, ?, ?, ?, ?, ?)", [$project_id, $next_link_order, "Open Admin Dashboard", $this->getUrl("index.php"), 1, "LINK", null, "ALL", 1]);
                        
                            $this->query("UPDATE redcap_projects SET secondary_pk_display_value = 0, secondary_pk_display_label = 0 WHERE project_id = ?", [$project_id]);
                        } 
                    }
                    
                }
    
                if(SUPER_USER === "1" && $hasLinkSourceColumnField) {
                    $this->setSystemSetting("config-pid", $project_id);
                }
              
            }
        }

    }



    function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance) {
        // load customizations for config project
        
        $sanitizedJavascriptObject = htmlentities($this->getJavascriptObject($record, true), ENT_QUOTES, 'UTF-8');
        if ($project_id == $this->configPID) {
            ?>
            <script>
            
               let UIOWA_AdminDash = <?= str_replace(array("&quot;", "&amp;", "&lt;", "&gt;"), array('"', "&", "<", ">"), $sanitizedJavascriptObject); ?>;
            </script>
            <link rel="stylesheet" type="text/css" href="<?= $this->getUrl("/resources/styles.css") ?>"/>

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

    public function isHttps() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTOCOL'])) {
            return ($_SERVER['HTTP_X_FORWARDED_PROTOCOL'] === 'https');
        } elseif (isset($_SERVER['HTTPS'])
            && !empty($_SERVER['HTTPS'])
            && strcasecmp($_SERVER['HTTPS'], 'off') !== 0
        ) {
            return true;
        }
        return false;
    }

    // todo get rid of report_id probably
    public function getJavascriptObject($report_id = -1, $isDataEntryForm = false, $execPreviewUser = null)
    {


        
        $jsObject = array(
            'urlLookup' => array(
                'redcapBase' => ($this->isHttps() ? 'https://' : 'http://') . SERVER_NAME . APP_PATH_WEBROOT,
                'reportBase' => $this->getUrl("index.php", false, $this->getSystemSetting("use-api-urls")), // todo - config setting
                'post' => $this->getUrl("post_internal.php")
            ),
            'reportId' => $report_id,
            'queryTimeout' => $this->getSystemSetting('query-timeout'),
            'redcap_csrf_token' => $this->getCSRFToken(),
            'loadedReport' => false,
            // 'isSuperUser' => SUPER_USER
        );

        // remove PID if project context added it
        foreach ($jsObject['urlLookup'] as $key => $url) {
            $jsObject['urlLookup'][$key] = str_replace('&pid=' . $this->configPID, '', $url);
        }

        // redcap data entry only
        if ($isDataEntryForm) {
            $configDataDictionary = json_decode(\REDCap::getDataDictionary(
                $this->configPID, 'json', null, 'icon_lookup'), true);

            $formattingReference = array();

            foreach($configDataDictionary as $row) {
                $codes = preg_replace('(\d, )', '', $row['select_choices_or_calculations']);
                $formattingReference[$row['field_label']] = explode(' | ', $codes);
            }

            $jsObject = array_merge($jsObject, array(
                'dataEntryForm' => array(
                    'fields' => \REDCap::getFieldNames($_GET['page']),
                    'iconLookup' => $formattingReference['icons']
                )
            ));

            return json_encode($jsObject,true);
        }

        // get list of reports
        $reportLookup = $this->getReportLookup();
        $reportAccess = $this->getUserAccess(isset($execPreviewUser) ? $execPreviewUser : USERID, $_GET['pid']);

        // error_log(json_encode($reportAccess));

        // remove any reports user does not have access to
        foreach($reportLookup as $index => $report) {
            $accessDetails = $reportAccess[$report['report_id']];

            if (  
                (SUPER_USER != '1' || $execPreviewUser) &&
                !$accessDetails['sync_project_access'] &&
                !$accessDetails['executive_view']
            ) {
                unset($reportLookup[$index]);
            }
        }

        $jsObject['reportLookup'] = $reportLookup;

        if ($report_id !== -1) {
            $loadedReportMetadata = json_decode(\REDCap::getData(array(
                'project_id' => $this->configPID,
                'events' => ['report_config_arm_1', 'report_config_arm_2'],
                'return_format' => 'json',
                'records' => $report_id
            )), true);

            $configDataDictionary = json_decode(\REDCap::getDataDictionary(
                $this->configPID, 'json', null, null, 'formatting_reference'), true);

            $formattingReference = array();

            foreach($configDataDictionary as $row) {
                $codes = preg_replace('(\d, )', '', $row['select_choices_or_calculations']);
                $formattingReference[$row['field_label']] = explode('|', $codes);
            }

            $formattedMeta = array();

            foreach($loadedReportMetadata as $index => $row) {
                
                if ($row['redcap_repeat_instrument'] !== '') {
                    
                    if ($row['column_name'] !== '') {
                        
                        $instrument = $row['redcap_repeat_instrument'];
                        $instanceKey = $row['column_name'];
                    }
                    else if ($row['join_project_id'] !== '') {
                        
                        $instrument = $row['redcap_repeat_instrument'];
                        $instanceKey = $row['join_project_id'];
                    }

                    $formattedMeta[$instrument][$instanceKey] = $row;
                }
                else {
                    
                    $formattedMeta['config'] = $row;
                }
            }

            // error_log($accessDetails['sync_project_access']);

            $jsObject = array_merge($jsObject, array(
                'loadedReport' => array(
                    'meta' => $formattedMeta,
                    'error' => '',
                    'ready' => false
                ),
                'showAdminControls' => SUPER_USER,
                'configPID' => $this->configPID,
                'formattingReference' => $formattingReference,
                'executiveView' => $reportAccess[$report_id]['executive_view'] || isset($execPreviewUser),
                'executiveExport' => $reportAccess[$report_id]['executive_export'],
                'syncView' => $reportAccess[$report_id]['project_view'],
                'redcap_csrf_token' => $this->getCSRFToken(),
                'redcap_version_url' => APP_PATH_WEBROOT
            ));

            error_log($reportAccess[$report_id]['project_view']);
        }

        return json_encode($jsObject);
    }



    public function getReportProps($params) {
        $report_id = $params['id']; // user-facing call - lookup query by record id
        $username = isset($params['username']) ? $params['username'] : USERID;
        
        $pid = isset($params['project_id']) ? $params['project_id'] : $this->currentPID;
        // get sql query from REDCap record

        if(!isset($params['query'])) {
            $data = \REDCap::getData(array(
                'project_id' => $this->configPID,
                'return_format' => 'json',
                'records' => $report_id,
                'fields' => ['report_sql', 'executive_username', 'executive_view']
            ));

        }

        return $data;
    }


    public function getQuery($params) {  
        if(SUPER_USER === "1") {
            $report_id = $params['id']; // user-facing call - lookup query by record id
            $username = isset($params['username']) ? $params['username'] : USERID;
            
            $pid = isset($params['project_id']) ? $params['project_id'] : $this->currentPID;
            // get sql query from REDCap record
    
            if(!isset($params['query'])) {
                $data = \REDCap::getData(array(
                    'project_id' => $this->configPID,
                    'return_format' => 'json',
                    'records' => $report_id,
                    'fields' => 'report_sql'
                ));
    
        
                $sql = json_decode($data, true)[0]['report_sql'];
            } else {
                
                $sql = $params['query'];
            }
    
            if (!isset($params['token'])) {
                $sql = \Piping::pipeSpecialTags($sql, $pid, null, null, null, $username);
            }
    
            $formattedSql = htmlentities(strip_tags($sql), ENT_QUOTES, 'UTF-8');
    
            echo $formattedSql;
        } else {
            echo htmlentities("error: something went wrong.", ENT_QUOTES, 'UTF-8');
        }
     
    }

    public function runApiReport($params) {
        $reportProps = json_decode($this->getReportProps($params),true);
        $pid = isset($params['project_id']) ? $params['project_id'] : $this->currentPID;

        $sql = $reportProps[0]['report_sql'];
        $returnData = array();

        if ($sql == '') {
            
            $returnData['error'] = 'No SQL query defined.';
        } elseif (!(strtolower(substr($sql, 0, 6)) == "select")) {
        
            $returnData['error'] = 'SQL query is not a SELECT query.';
        } else {
            //fix for group_concat limit
            // $this->query('SET SESSION group_concat_max_len = 1000000;', []);
    
            $result = $this->query($sql, []);

            if (is_string($result)) {
                echo $result;
                return;
            }
      
            //prepare data for table
            while ($row = db_fetch_assoc($result)) {
                $returnData[] = $row;
            }

        }
        echo htmlentities(json_encode($returnData), ENT_QUOTES, 'UTF-8');

    }

    public function runExecutiveReport($params) {
   
        if(SUPER_USER != "1") {  //  prevent super users from viewing executive dashboard
            $reportProps = json_decode($this->getReportProps($params),true);
            $pid = isset($params['project_id']) ? $params['project_id'] : $this->currentPID;
            $isExecutive = false;
            foreach($reportProps AS $instance => $data) {
                $executiveUsername = $data['executive_username'];
       
             
                if($executiveUsername === USERID && $data['executive_view'] == 1) {
                    $isExecutive = true;
                    break;
                }
            }
    
            if($isExecutive) {
                $sql = $reportProps[0]['report_sql'];
                $returnData = array();
                // supports [user-name] and [project-id]
                if (!isset($params['token'])) {
                    $sql = \Piping::pipeSpecialTags($sql, $pid, null, null, null, $username);
                }
             
                
                if ($sql == '') {
                
                    $returnData['error'] = 'No SQL query defined.';
                } elseif (!(strtolower(substr($sql, 0, 6)) == "select")) {
                
                    $returnData['error'] = 'SQL query is not a SELECT query.';
                } else {
                    //fix for group_concat limit
                    // $this->query('SET SESSION group_concat_max_len = 1000000;', []);
            
                    $result = $this->query($sql, []);
    
                    
                    if (is_string($result)) {
                        echo $result;
                        return;
                    }
              
                    //prepare data for table
                    while ($row = db_fetch_assoc($result)) {
                        $returnData[] = $row;
                    }
        
    
                  
                }
                echo htmlentities(json_encode($returnData), ENT_QUOTES, 'UTF-8');
            } else {
                echo htmlentities("error: something went wrong.", ENT_QUOTES, 'UTF-8');
            }
        } else {
            echo htmlentities("error: something went wrong.", ENT_QUOTES, 'UTF-8');
        }


      
    }

    public function runProjectViewReport($params) {
   
        if(SUPER_USER != "1") {  //  prevent super users from viewing executive dashboard
            // error_log("project sync view");
            $reportProps = json_decode($this->getReportProps($params),true);
            $pid = isset($params['project_id']) ? $params['project_id'] : $this->currentPID;

            $reportAccess = $this->getUserAccess(USERID, $pid);
            // error_log(json_encode($reportProps));
            // error_log(json_encode($reportAccess));
            // echo json_encode($reportAccess);
            $projectView = $reportAccess[$params["reportId"]]["project_view"];

 
            // error_log(json_encode($projectView));

            // $isExecutive = false;
            // foreach($reportProps AS $instance => $data) {
            //     $executiveUsername = $data['executive_username'];
       
             
            //     if($executiveUsername === USERID && $data['executive_view'] == 1) {
            //         $isExecutive = true;
            //         break;
            //     }
            // }
    
            if($projectView) {
                $sql = $reportProps[$params["reportId"]]['report_sql'];
                $returnData = array();
                // supports [user-name] and [project-id]
                if (!isset($params['token'])) {
                    $sql = \Piping::pipeSpecialTags($sql, $pid, null, null, null, $username);
                }
             
                
                if ($sql == '') {
                
                    $returnData['error'] = 'No SQL query defined.';
                } elseif (!(strtolower(substr($sql, 0, 6)) == "select")) {
                
                    $returnData['error'] = 'SQL query is not a SELECT query.';
                } else {
                    //fix for group_concat limit
                    // $this->query('SET SESSION group_concat_max_len = 1000000;', []);
            
                    $result = $this->query($sql, []);
    
                    
                    if (is_string($result)) {
                        echo $result;
                        return;
                    }
              
                    //prepare data for table
                    while ($row = db_fetch_assoc($result)) {
                        $returnData[] = $row;
                    }
        
    
                  
                }
                echo htmlentities(json_encode($returnData), ENT_QUOTES, 'UTF-8');
            } else {
                echo htmlentities("error: something went wrong.", ENT_QUOTES, 'UTF-8');
            }
        } else {
            echo htmlentities("error: something went wrong.", ENT_QUOTES, 'UTF-8');
        }


      
    }

    public function saveReportColumns($project_id, $record, $columns)
    {

        $existingColumns  = json_decode(\REDCap::getData(array(
            'project_id' => $this->configPID,
            'return_format' => 'json',
            'events' => ['report_config_arm_1'],
            'fields' => [
                'report_id', 'redcap_repeat_instrument', 'redcap_repeat_instance', 'column_name', 'dashboard_show_column', 'dashboard_show_filter', 'dashboard_display_header', 'export_show_column', 'export_display_header', 'link_type', 'link_source_column', 'specify_custom_link', 'export_urls', 'code_type', 'export_codes', 'group_concat_separator'],
            'records' => [$record]
        )));

        $existingColumnNames = [];

        foreach($existingColumns AS $inst => $formValues) {
            array_push($existingColumnNames, $formValues->column_name);
        }
      
        $columns = json_decode($columns);
        $json = array();
//        $validTags = array('#hidden', '#ignore');
        $groupCheck = array();

        $formattingPresets = array(
            'project_id' => array(
                'link_type' => 5,
                'link_source_column' => 'project_id',
                'export_urls' => 0,
            ),
            'app_title' => array(
                'link_type' => 1,
                'link_source_column' => 'project_id',
                'export_urls' => 0
            ),
            'username' => array(
                'link_type' => 6,
                'export_urls' => 0,
                'link_source_column' => "username"
            ),
            'user_firstname' => array(
                'link_type' => 6,
                'export_urls' => 0,
                'link_source_column' => "username"
            ),
            'user_lastname' => array(
                'link_type' => 6,
                'export_urls' => 0,
                'link_source_column' => "username"
            ),
            'hash' => array(
                'link_type' => 8,
                'export_urls' => 0
            ),
            'email' => array(
                'link_type' => 9,
                'export_urls' => 0,
            ),
            'status' => array(
                'code_type' => 1,
                'export_codes' => 0,
            ),
            'purpose' => array(
                'code_type' => 2,
                'export_codes' => 0,
            ),
            'purpose_other' => array(
                'code_type' => 3,
                'export_codes' => 0,
            )
        );

        foreach ($columns as $index => $column_name) {

            if(in_array($column_name, $existingColumnNames)) {
                $instance = array();
                $instIndex = array_search($column_name, $existingColumnNames);
            
                foreach($existingColumns[$instIndex] AS $field => $fieldVal) {
                    $instance[$field] = $fieldVal;
                }
                
                $instance['redcap_repeat_instance'] = $index + 1;
                array_push($json, $instance);
            
            }                 
            else {

                $tags = explode('#', $column_name);
                $root_column_name = array_shift($tags);

                $instance = array(
                    'report_id' => $record,
                    'redcap_repeat_instrument' => 'column_formatting',
                    'redcap_repeat_instance' => $index + 1,
                    'column_name' => $column_name,
                    'dashboard_show_column' => 1,
                    'export_display_header' => $root_column_name,
                    'dashboard_display_header' => $root_column_name,
                    'export_show_column' => 1,
                    'column_formatting_complete' => 0,
                    'link_type' => "",
                    'link_source_column' => ""
                );

                // check for hashtag shorthand        
                // flags for tracking what formatting can/cannot be applied
                $hidden = in_array('hidden', $tags);
                $ignore = in_array('ignore', $tags);
                $group = in_array('group', $tags);
    
                // add default separator for #group
                if ($group) {
                    $instance['group_concat_separator'] = '@@@';
                    $groupCheck[$root_column_name] = $column_name;
                } else {
                    $instance['group_concat_separator'] = '';
                }
    
                // set hidden with #hide, otherwise set default filter visible
                if ($hidden) {
                    $instance['dashboard_show_column'] = 0;
                    $instance['dashboard_show_filter'] = "";
                    $instance['export_show_column'] = 0;
                    $instance['column_formatting_complete'] = 2;
                    $instance['export_urls'] = "";
                    $instance['link_type'] = "";
                    $instance['link_source_column'] = "";
                }
                else {
                    $instance['dashboard_show_column'] = 1;
                    $instance['dashboard_show_filter'] = 1;
                    $instance['export_show_column'] = 1;
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
                    } else {
                        $instance = array_merge($instance, ['dashboard_display_header' => $root_column_name]);
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

        $numberOfColumns = count($columns);

        //  Check existing column formatting instances
        $getColumnInstances = \REDCap::getData(array(
            'project_id' => $this->configPID,
            'return_format' => 'json',
            'events' => ['report_config_arm_1'],
            'fields' => [
                'column_name'],
            'records' => [$record]
        ));

        //  If there are more existing column instances than the new query has, delete the extra column instances. 
        if((count(json_decode($getColumnInstances))-1) > $numberOfColumns) {          //  -1 because getData returns an empty data point as index 0
            $startDeleteIndex = $numberOfColumns + 1;
            
            for($i = $numberOfColumns+1; $i < count(json_decode($getColumnInstances)); $i++) {
                //  TODO check if instance number can be an array
                \REDCap::deleteRecord($this->configPID,
                    $record,
                    1,  //  arm
                    'report_config_arm_1',  //  event name
                    'column_formatting',  //  instrument name
                    $i);  //  repeat instance
            }
            
            $getColumnInstances2 = \REDCap::getData(array(
                'project_id' => $this->configPID,
                'return_format' => 'json',
                'events' => ['report_config_arm_1'],
                'fields' => [
                    'column_name'
            ]));
    
        }

   
    }

    public function getUserAccess($username, $pid)
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
                if ($reportRights['executive_username'] === $username) {
                    if ($reportRights['executive_view'] == '1') {
                        $userRightsArray[$report_id]['executive_view'] = true;

                        if ($reportRights['executive_export'] == '1') {
                            $userRightsArray[$report_id]['export_access'] = true;
                            $userRightsArray[$report_id]['executive_export'] = true;  //  TODO check if this or the line above is necessary
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

                    // error_log(json_encode($userRightsArray));

                    if ($reportRights['project_sync_export'] == '2') { // only users with "full data set" rights can export
                        $userRightsArray[$report_id]['export_access'] = $projectRights['data_export_tool'] == '1';
                    } elseif ($reportRights['project_sync_export'] == '1') { // any user can export
                        $userRightsArray[$report_id]['export_access'] = true;
                    }
                }
            }
        }
        // error_log(json_encode($userRightsArray));
        return $userRightsArray;
    }

    public function joinProjectData($params)
    {

        $joinConfig = json_decode(\REDCap::getData(array(
            'project_id' => $this->configPID,
            'return_format' => 'json',
            'records' => $params['report_id'],
            'fields' => array(
                'join_project_id',
                'join_report_id',
                'join_primary_field'
            ),
            'filterLogic' => '[join_project_id] <> ""'
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
                'project_id' => $join['join_project_id'],
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
        
        echo htmlentities(json_encode($joinedData, true), ENT_QUOTES, 'UTF-8');
        // echo json_encode($joinedData);
    }

    public function getAdditionalInfo($params) { // params - type, whereVal
        if(SUPER_USER === "1") {
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
    
            echo htmlentities(json_encode(db_fetch_assoc($result)), ENT_QUOTES, 'UTF-8');
        } else {
            echo htmlentities("error: something went wrong.", ENT_QUOTES, 'UTF-8');
        }
     
    }

    public function apiCall($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
        $output = curl_exec($ch);

        curl_close($ch);

        return $output;
    }

    public function getRedcapUrl() {
        return (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . SERVER_NAME . APP_PATH_WEBROOT;
    }

    public function getReportLookup() {
        return json_decode(\REDCap::getData(array(
            'project_id' => $this->configPID,
            'return_format' => 'json',
            'filterLogic' => '[report_visibility] = "1"',
            'fields' => array('report_id', 'report_id_custom', 'report_title', 'report_icon', 'report_type', 'folder_name', 'tab_color', 'tab_color_custom')
        )), true);
    }

    public function getCustomReportIds() {
        $reportLookup = $this->getReportLookup();
        $idLookup = array();

        // remove any reports user does not have access to
        foreach($reportLookup as $index => $report) {
            if ($report['report_id_custom'] !== '') {
                $idLookup[$report['report_id_custom']] = $index;
            }
        }

        return $idLookup;
    }
}
?>
