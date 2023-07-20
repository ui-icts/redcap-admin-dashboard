<?php
/** @var \UIOWA\AdminDash\AdminDash $module */

// module not yet configured
if (!isset($module->configPID) && SUPER_USER == '1') {
    include 'setup.php';
    die();
}

if (isset($_GET['report'])) {
    $customReportIdLookup = $module->getCustomReportIds();

    $report_id = $customReportIdLookup[$_GET['report']] + 1;
}
else {
    $report_id = isset($_GET['id']) ? intval($_GET['id']) : -1;
}

// clean bookmark url if no record context piped in
if ($report_id == '[record-name]') {
    $query = $_GET;
    unset($query['id']);
    $query['type'] = 'module';
    $query_result = http_build_query($query);

    header('Location: ' . str_replace($_SERVER['PHP_SELF'], 'index.php', '') . '?' . $query_result);
}
elseif (isset($_GET['record'])) {
    $query = array(
        'type' => 'module',
        'prefix' => $_GET['prefix'],
        'page' => $_GET['page'],
        'id' => $_GET['record']
    );
    $query_result = http_build_query($query);

    header('Location: ' . str_replace($_SERVER['PHP_SELF'], 'index.php', '') . '?' . $query_result);
}

$reportRights = $module->getUserAccess(USERID, $_GET['pid']);

// if not superuser, verify access
if (SUPER_USER !== '1' && !$reportRights[$report_id]['project_view'] && !$reportRights[$report_id]['executive_view']) {
    die('You do not have access to this page.');
}

$executiveView = $reportRights[$report_id]['executive_view'] || isset($_GET['asUser']);
$syncProjectView = $reportRights[$report_id]['project_view'];
$exportEnabled = SUPER_USER || $reportRights[$report_id]['export_access'];

if ($syncProjectView) {
    require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
}
else {
    $page = new HtmlPage();
    $page->PrintHeaderExt();
    include APP_PATH_VIEWS . 'HomeTabs.php';
}

$sanitizedJavascriptObject = htmlentities($module->getJavascriptObject($report_id, false, $_GET['asUser']), ENT_QUOTES, 'UTF-8');

?>
<style>
    /* make the display the full width */
    div#outer
    {
        width: 50%;
    }

    #pagecontainer
    {
        max-width: 80%;
        /*cursor: progress;*/
    }

    td.details-control {
        text-align:center;
        color:forestgreen;
        cursor: pointer;
    }
    tr.shown td.details-control {
        text-align:center;
        color:red;
    }

    .user-detail
    {
        color: red;
        font-size: 10px;
        padding-right: 2px;
    }

    .btn-admindash-edit-report {
        background-color: rgb(16, 108, 214) !important;
        border-radius: 10px;
        border: 0;
        color:white;
    }

    table thead tr th {
        background-color: #aed8ff;

    }

    table thead tr td  {
        background-color: #aed8ff;
        
    }

    table.dataTable tbody tr:hover td  {
        background-color: #b7b7b7 !important;
    }

    /* Define an animation behavior */
    @keyframes spinner {
        to { transform: rotate(360deg); }
    }
    /* This is the class name given by the Font Awesome component when icon contains 'spinner' */
    .fa-sync {
        /* Apply 'spinner' keyframes looping once every second (1s)  */
        animation: spinner 1s linear infinite;
    }

    .dt-head-center {text-align: center;}

    [v-cloak] { display: none; }

</style>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/jszip-2.5.0/b-1.6.5/b-colvis-1.6.5/b-html5-1.6.5/b-print-1.6.5/cr-1.5.3/fh-3.1.7/r-2.2.7/rg-1.1.2/rr-1.2.7/sb-1.0.1/datatables.min.css"/>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.10.22/b-1.6.5/b-colvis-1.6.5/b-html5-1.6.5/b-print-1.6.5/cr-1.5.3/fh-3.1.7/r-2.2.7/rg-1.1.2/rr-1.2.7/sb-1.0.1/datatables.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/plug-ins/1.10.22/dataRender/datetime.js"></script>
<script type="text/javascript" src="https://code.highcharts.com/highcharts.js"></script>
<script src="<?= $module->getUrl("/resources/vue.min.js") ?>"></script>

<script>
    let UIOWA_AdminDash = <?= str_replace(array("&quot;", "&amp;", "&lt;", "&gt;"), array('"', "&", "<", ">"), $sanitizedJavascriptObject); ?>;
</script>

<script src="<?= $module->getUrl("/adminDash.js") ?>"></script>


<div id="adminDashApp">
    <?php if(!$syncProjectView): ?>
    <h2 v-cloak style="text-align: center; color: #106CD6; font-weight: bold; padding-top: 75px; padding-bottom: 10px">
        <?php if($executiveView): ?>Executive<?php else: ?>Admin<?php endif; ?> Dashboard
    </h2>
    <div id="nav" v-cloak v-if="Object.keys(reportLookup).length > 0">
        <ul class="nav nav-tabs border-bottom">
                <li class="nav-item dropdown"
                    v-for="(value, name) in getReports(reportLookup, true)"
                >
                    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                        <span class="fas fa-bars">&nbsp;</span>
                        {{ name }}
                    </a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item"
                           v-for="report in value"
                           :key="report.report_id"
                           :data-id="report.report_id"
                           :href="urlLookup.reportBase + '&id=' + report.report_id"
                           :style="{ backgroundColor: getTabColor(report), color: getTabColor(report, true) }"
                        >
                            <span class="report-icon" :class="getReportIcon(report.report_icon)">&nbsp;</span>
                            <span class="report-title">{{ report.report_title }}</span>
                        </a>
                    </div>
                </li>
                <li class="nav-item"
                    v-for="report in getReports(reportLookup, false)"
                    :key="report.report_id"
                    :data-id="report.report_id"
                >
                    <a class="nav-link"
                       :href="urlLookup.reportBase + '&id=' + report.report_id"
                       :class="!loadedReport.ready ? '' : isActiveReport(report.report_id)"
                       :style="{ backgroundColor: getTabColor(report), color: getTabColor(report, true) }"
                    >
                        <span class="report-icon" :class="getReportIcon(report.report_icon)">&nbsp;</span>
                        <span class="report-title">{{ report.report_title }}</span>
                    </a>
                </li>
        </ul>
    </div>
    <?php endif; ?>

    <div id="reportContent" v-cloak v-if="loadedReport" style="width: 98%">
        <div style="padding: 25px">
            <div style="float: left">
                <div id="visButtons"></div>
            </div>

            <?php if($exportEnabled): ?>
            <div style="float:right">
                <div id="buttons" style="display: none">Export: </div>
            </div>
            <?php endif; ?>
        </div>
        <div style="text-align: center; clear: both;">
            <h3 id="reportTitle">
                <span class="report-icon" :class="getReportIcon(loadedReport.meta.config.report_icon)">&nbsp;</span>{{ loadedReport.meta.config.report_title ? loadedReport.meta.config.report_title : 'Untitled Report' }}
                <?php if(!$executiveView): ?>
                <button v-if="showAdminControls === '1'" class="btn-sm edit-report btn-admindash-edit-report" style="margin: 5px; vertical-align: text-top">
                    <span class="fas fa-edit"></span>
                </button>
                <?php endif; ?>
            </h3>
        </div>
        <div style="text-align: center; font-size: 14px">
            {{ loadedReport.meta.config.report_description }}
        </div>
        <table v-if="loadedReport.ready" :id="'reportTable_' + loadedReport.meta.config.report_id" class="table-primary report-table row-border" style="width: 100%">
            <thead><tr>
                <th
                    v-for="column in loadedReport.columns"
                    :key="column"
                >{{ getDisplayHeader(column) }}</th>
            </tr></thead>
        </table>
        <div v-if="loadedReport.error !== ''">
            <div class="alert alert-warning" style="border-color: black !important; margin-top: 10%; width: 30%; text-align: center">
                <h4 class="center-block">
                    <i class="fas fa-exclamation-triangle fa-2x" style="vertical-align: sub">&nbsp;</i>
                    {{ loadedReport.error }}
                </h4>
            </div>
        </div>
    </div>

    <div style="text-align: center; padding: 50px" v-cloak>
        <h4 v-if="loadedReport">
            <div v-if="!loadedReport.ready" id="reportLoading" class="fa-10x" style="text-align: center; padding: 50px">
                <i class="fas fa-sync fa-pulse"></i>
                <!-- <font-awesome-icon icon="spinner" class="fa-spin" /> -->
            </div>
        </h4>
        <span v-else>
            <h4>Welcome to the REDCap Admin Dashboard!</h4>
            <span>Click one of the tabs above to view a report.</span>
        </span>
    </div>
<?php

if ($syncProjectView) {
    require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
}
