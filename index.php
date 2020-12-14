<?php
/** @var \UIOWA\AdminDash\AdminDash $module */

$report_id = -1;

if (isset($_GET['id'])) {
    $report_id = $_GET['id'];
}

if (isset($_GET['record'])) {
    $report_id = $_GET['record'];
}

// if not superuser, verify access
if (SUPER_USER !== '1' && !$module->verifyUserRights($report_id, USERID)) {
    die('You do not have access to this page.');
}

$page = new HtmlPage();
$page->PrintHeaderExt();
include APP_PATH_VIEWS . 'HomeTabs.php';
//require APP_PATH_VIEWS . "HeaderProject.php";

?>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/jszip-2.5.0/b-1.6.5/b-colvis-1.6.5/b-html5-1.6.5/b-print-1.6.5/cr-1.5.3/fh-3.1.7/r-2.2.6/rg-1.1.2/rr-1.2.7/sb-1.0.1/datatables.min.css"/>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.10.22/b-1.6.5/b-colvis-1.6.5/b-html5-1.6.5/b-print-1.6.5/cr-1.5.3/fh-3.1.7/r-2.2.6/rg-1.1.2/rr-1.2.7/sb-1.0.1/datatables.min.js"></script>
<script src="//unpkg.com/vue@latest/dist/vue.min.js"></script>

<script>
    let UIOWA_AdminDash = <?= $module->getJavascriptObject($report_id) ?>;
</script>

<script src="<?= $module->getUrl("/adminDash.js") ?>"></script>

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
</style>

<h2 style="text-align: center; color: #106CD6; font-weight: bold; padding-top: 75px; padding-bottom: 10px">
    Admin Dashboard
</h2>

<div id="adminDashApp">
    <div id="nav">
        <ul class="nav nav-tabs border-bottom">
            <li class="nav-item"
                v-for="report in reportLookup"
                :key="report.report_id"
                :data-id="report.report_id"
            >
                <a class="nav-link"
                   :href="baseReportUrl + '&id=' + report.report_id"
                   :class="noReportId ? '' : isActiveReport(report.report_id)"
                >
                    <span class="report-icon" :class="getReportIcon(report.report_icon)">&nbsp;</span>
                    <span class="report-title">{{ report.report_title }}</span>
                </a>
            </li>
        </ul>
    </div>

    <div id="reportContent" v-if="loadedReport.ready" style="display: none">
        <div style="padding: 25px">
            <div style="float: left">
                <div id="visButtons"></div>
            </div>

            <div style="float:right">
                <div id="buttons">Export: </div>
            </div>
        </div>
        <div style="text-align: center;">
            <h3 id="reportTitle">
                <span class="report-icon" :class="getReportIcon(loadedReport.meta.config.report_icon)">&nbsp;</span>{{ loadedReport.meta.config.report_title }}
                <button v-if="showAdminControls" class="btn-sm btn-primary edit-report" style="margin: 5px; vertical-align: text-top">
                    <span class="fas fa-edit"></span>
                </button>
            </h3>
        </div>
        <div style="text-align: center; font-size: 14px">
            {{ loadedReport.meta.config.report_description }}
        </div>
        <table :id="'reportTable_' + loadedReport.meta.config.report_id" class="table-primary report-table" style="width: 100%">
            <thead><tr>
                <th
                    v-for="column in loadedReport.columns"
                    :key="column"
                >{{ getDisplayHeader(column) }}</th>
            </tr></thead>
        </table>
    </div>
    <div v-else-if="loadedReport.error !== ''">
        <div style="text-align: center;">
            {{ loadedReport.error }}
        </div>
    </div>
    <div v-else-if="noReportId">
        <div style="text-align: center;">
            Click one of the tabs above to view a report.
        </div>
    </div>
    <div v-else class="fa-10x" style="text-align: center; padding: 50px">
        <i class="fas fa-spinner fa-pulse"></i>
    </div>
