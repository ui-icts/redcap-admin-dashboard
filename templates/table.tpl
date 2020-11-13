{if $superUser}
    <div>
        <div style="float: left">
            <button type="button" class="btn btn-primary open-settings">
                <span class="fas fa-cog"></span> Settings
            </button>
        </div>
    </div>
{/if}
<div class="modal fade" id="invalidProjectWarning" tabindex="-1" role="dialog" aria-labelledby="invalidProjectWarning" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="text-align: center;">Invalid Project Selected</h5>
            </div>
            <div class="modal-body">
                <p class="confirmMsg">
                    The target project you selected does not contain the required fields to import data from this report.
                    <br />
                    <br />
                    Please choose a different project or <a class="show-project-warning-text" href="#" style="font-size: large; text-decoration: underline;">click here</a> to overwrite this project's metadata automatically and complete the import.
                </p>
                <p id="confirmProjectUpdate" style="display: none">
                    <br />
                    <span style="color:red;"><strong>WARNING:</strong></span> Clicking "Force Update" will immediately overwrite the selected project's data dictionary to mirror the currently open report. <span style="color:red">This means that any existing data contained in the target project may be compromised by this action.</span> It is recommended that you visit the target project (<a href="#" id="target-project-link" class="target-project-link" target="_blank"></a>) to ensure you have selected the correct project before proceeding.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger force-import" data-dismiss="modal" style="display: none">Force Update</button>
                <button type="button" class="btn btn-secondary force-import-close" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="projectImportConfirm" tabindex="-1" role="dialog" aria-labelledby="projectImportConfirm" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="text-align: center;">Send to REDCap Project</h5>
            </div>
            <div class="modal-body">
                <p class="confirmMsg">
                    Target Project: <a href="#" id="target-project-link" class="target-project-link" target="_blank"></a>
                    <br />
                    <br />
                    <span id="importInfoText">
                        Are you sure you want to import the data from this report into the target REDCap project?
                        <br />
                        <br />
                        <span style="color:red">This action will <strong>overwrite</strong> any existing data in the project.</span>
                    </span>
                    <span id="importCompleteText" style="display: none;">
                        <strong><span style="color:green">Imported <span id="importedRecordCount"></span> record(s) into target project.</span></strong>
                    </span>
                    <span id="importErrorText" style="display: none;">
                        <strong><span id="redcapApiErrorText" style="color:red"></span></strong>
                    </span>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary confirm-import">Import</button>
                <button type="button" class="btn btn-secondary import-close" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

{if isset($reportId)}
    {if $exportEnabled || $superUser}
        <div style="float: right; display: none;" class="output-button">
            <div class="btn-group">
                <button type="button" class="btn btn-info download"><span class="fas fa-download"></span> Export CSV File</button>
                <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu export-menu" role="menu">
                    <li>
                        <label>Export to:</label>
                        <div class="btn-group btn-group-toggle output-download-popup" data-toggle="buttons" title="Choose export method">
                            <label class="btn btn-info btn-sm active output-type">
                                <input type="radio" name="delivery1" class="output-download" checked> File
                            </label>
                            <label class="btn btn-info btn-sm output-type">
                                <input type="radio" name="delivery1" class="output-popup"> Popup
                            </label>
                            {*<label class="btn btn-info btn-sm output-type">*}
                                {*<input type="radio" name="delivery1" class="output-popup"> Project*}
                            {*</label>*}
                        </div>
                    </li>
                    <li class="separator-field-display">
                        <br />
                        <label>Separator: <input class="output-separator-input" type="text" size="4" value=","></label><br />
                        <button class="output-separator btn btn-info btn-xs active" title="Comma">,</button>
                        <button class="output-separator btn btn-info btn-xs" title="Semicolon">;</button>
                        <button class="output-separator btn btn-info btn-xs" title="Tab">⇥</button>
                        <button class="output-separator btn btn-info btn-xs" title="Space">␣</button>
                        <button class="output-separator btn btn-info btn-xs" title="JSON Formatted">json</button>
                        <button class="output-separator btn btn-info btn-xs" title="Array Formatted">array</button>
                    </li>
                    <li class="include-field-display">
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
                    <li class="dropdown-divider filename-field-display"></li>
                    <li class="filename-field-display"><label title="Choose a download filename">Filename: <input class="output-filename" type="text" size="25" value=""></label></li>
                    <li class="filename-field-display"><label title="Append date and time of report render to filename">Include timestamp: <input class="filename-datetime" type="checkbox" checked></li>
                    <li class="target-field-display hidden">
                        <br />
                        <label>Target Project:
                            <i
                                class="fas fa-question-circle"
                                style="color:#3E72A8"
                                data-toggle="tooltip"
                                title="If your project isn't listed, make sure there is an associated API token with Import rights."
                            ></i>
                        </label>
                        <select id="exportProjectSelect" style="width:100%">
                            <option value="">---Select---</option>
                            {foreach $exportProjects as $project}
                                <option value="{$project['project_id']}">{$project['project_id']} - {$project['app_title']}</option>
                            {/foreach}
                        </select>
                    </li>
                </ul>
            </div>
        </div>
    {/if}

    <br />
    <br />
    <br />
    <br />

    <h3 id="reportTitle" style="text-align: center;">
        {$reportReference[$reportId]['reportName']}
    </h3>

    <div style="text-align: center; font-size: 14px">
        {$reportReference[$reportId]['description']}
    </div>

    {if $sqlErrorMsg}
        <br/>
        <br/>
        <br/>
        <br/>
        <h5>
            <div class="error">Failed to run report!</div>
        </h5>
        <br/>
        <br/>
        <br/>
        <br/>
    {else}
        <div id="loading" style="text-align: center">
            <br />
            <br />
            <br />
            <br />
            <img src="{$loadingGif}">
            <br />
            <br />
        </div>
        <div id="report-content" style="display: none">
            <div id="pager" class="pager">
                <form>
                    <img src="{$iconUrls['first']}" class="first"/>
                    <img src="{$iconUrls['prev']}" class="prev"/>
                    {literal}
                        <span class="pagedisplay" data-pager-output-filtered="{startRow:input} &ndash; {endRow} / {filteredRows} of {totalRows} total rows"></span>
                    {/literal}
                    <img src="{$iconUrls['next']}" class="next"/>
                    <img src="{$iconUrls['last']}" class="last"/>
                    <select class="pagesize">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </form>
            </div>

            {$pageInfo['sqlErrorMsg']}

            <table id='reportTable' class="table-hideable">
            </table>
            <div style="display: inline-block">
                <button type="button" class="btn btn-outline-primary toggle-edit-columns" style="display: inline-block">
                    <span class="fas fa-columns"></span> Edit Columns
                </button>
            </div>
            <div class="footer-restore-columns" style="float: right; display: none"><a class="restore-columns" href="#">Some columns hidden - click to show all</a></div>
        </div>
    {/if}
{else}
    <br />
    <br />
    <br />
    <br />

    <div style="text-align: center;">
        <h3>Welcome to the REDCap {if $executiveAccess}Executive{else}Admin{/if} Dashboard!</h3>
    </div>

    <div style="text-align: center;">
        Click one of the tabs above to view a report.
        <br />
        <br />
        {if $executiveAccess && $superUser}
            To grant a non-admin user access to this dashboard, you must add their username in the "Executive User Management" section of the Settings menu, then provide them with this page's URL.
        {/if}
    </div>
    <br />
    <br />
{/if}

{if $superUser}
    <div style="text-align: center">
        <a id="switchView" class="btn btn-success" style="color: #FFFFFF" href="{$viewUrl}">
            Switch to {if $executiveAccess}Admin{else}Executive{/if} View
        </a>
    </div>
{/if}