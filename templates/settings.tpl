<div id="accordion">
    <div class="card">
        <div class="card-header" id="headingOne" data-toggle="collapse" data-target="#collapseOne">
            <h5 class="mb-0">
                <span class="accordion-header" aria-expanded="true" aria-controls="collapseOne">
                    <span class="fas fa-wrench fa-fw">&nbsp</span>
                    Configure Reports
                </span>
            </h5>
        </div>

        <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
            <div class="card-body">
                <div class="report-list-loading-icon" style="text-align: center; padding: 5%;">
                    <i class="fas fa-spinner fa-spin fa-7x"></i>
                </div>
                <table id="reportTable" class="table table-striped" style="display: none">
                    <thead>
                    <tr>
                        <th></th>
                        <th></th>
                        <th style="text-align: center; font-size: 18px">
                            <b>Admin View</b>
                        </th>
                        <th style="text-align: center; font-size: 18px">
                            <select id="executiveUser" class="executiveUser" style="width: 150px">
                                <option value="">[Select User]</option>
                                    {foreach $executiveUsers as $user}
                                        <option value="{$user}">{$user}</option>
                                    {/foreach}
                            </select>
                            <br/>
                            <b>Executive View</b>
                        </th>
                    </tr>
                    </thead>
                    <tbody class="report-visibility-table">
                    <tr>
                        <td style="text-align: center;" colspan="4">
                            <button type="button" class="btn btm-sm btn-success open-report-setup add-report-button" aria-haspopup="true" aria-expanded="false" data-toggle="modal" data-target="#reportSetupModal">
                                <span class="fas fa-plus"></span> Add New Report
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header" id="headingTwo" data-toggle="collapse" data-target="#collapseTwo">
            <h5 class="mb-0">
                <span class="collapsed accordion-header" aria-expanded="false" aria-controls="collapseTwo">
                    <span class="fas fa-users fa-fw"></span>
                    Executive User Management
                </span>
            </h5>
        </div>
        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
            <div class="card-body about-text">
                <p>
                    The <a href="#" class="executive-link">Executive Dashboard</a> is a limited view of the Admin Dashboard without links (to project pages, user info, etc). Non-admin REDCap users can be granted access to this view by adding them to the list below.
                </p>
                <table class="table table-striped executive-table" align="center">
                    <tbody>
                        <tr>
                            <td id="noExecutiveUsers" colspan="3" {if $executiveUsers}style="display: none"{/if}>No users currently added.</td>
                        </tr>
                        {foreach $executiveUsers as $user}
                        <tr>
                            <td align="center">
                                <button
                                        type="button"
                                        class="btn btm-sm btn-danger remove-executive-user"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                        data-target="#"
                                        onclick=""
                                >
                                    <i class="fas fa-trash"></i>
                                    <span class="sr-only">Remove Executive User</span>
                                </button>
                            </td>
                            <td class="executive-user" style="text-align:center; vertical-align:middle; padding-right:10px word-wrap:break-word; max-width:200px">
                                {$user}
                            </td>
                            <td align="center">
                                <input
                                        type="checkbox"
                                        data-toggle="toggle"
                                        data-width="160"
                                        data-height="40"
                                        data-on="Export Enabled"
                                        data-off="Export Disabled"
                                        data-username="{$user}"
                                        class="module-config"
                                        value="{if $user|in_array:$executiveExportLookup}1{else}0{/if}"
                                >
                            </td>
                            <td>
                                <button
                                        type="button"
                                        class="btn btm-sm btn-primary email-executive-user"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                        data-target="#"
                                        onclick=""
                                >
                                    <i class="fas fa-envelope"></i>
                                    Send Link
                                    <span class="sr-only">Send Link</span>
                                </button>
                            </td>
                        </tr>
                        {/foreach}
                        <tr>
                            <td colspan="4">
                                <form id="newExecutiveUserForm">
                                    <div class="input-group mb-3" style="width: 60%; margin:auto; padding:10px;">
                                        <input id="new-executive-username" type="text" class="form-control valid custom-error" placeholder="Username" aria-label="Username" aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button id="add-executive-user-button" onclick="UIOWA_AdminDash.addExecutiveUser();" class="btn btn-info" type="button" disabled>Add User</button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header" aria-expanded="false" id="headingThree" data-target="#collapseThree" data-toggle="collapse">
            <h5 class="mb-0">
                <span class="collapsed" aria-controls="collapseThree">
                    <span class="fas fa-desktop fa-fw"></span>
                    Additional Options
                </span>
            </h5>
        </div>
        <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
            <div class="card-body">
                <table class="table table-no-top-row-border">
                    <tbody class="config-settings-table">
                        {foreach $configSettings as $setting}
                            <tr><td class="module-config-toggle"><label>{$setting['name']}</label></td>
                                {if $setting['type'] == 'checkbox'}
                                    <td>
                                        <input
                                                type="checkbox"
                                                data-toggle="toggle"
                                                data-width="75"
                                                data-height="40"
                                                data-on="{if $setting['data-on']}{$setting['data-on']}{else}Show{/if}"
                                                data-off="{if $setting['data-off']}{$setting['data-off']}{else}Hide{/if}"
                                                data-username="{$setting['key']}"
                                                class="module-config"
                                                value={$setting['default']}
                                        >
                                    </td>
                                {elseif $setting['type'] == 'text'}
                                    <td>
                                        {if $setting['repeatable'] == '1'}
                                            <span style="color:#00A000">
                                                <i id='addRepeatableInput' class="fas fa-plus-circle"></i>
                                            </span>
                                        {/if}
                                        <input>
                                    </td>
                                {else}
                                    <td></td>
                                {/if}
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header" id="headingFour" data-toggle="collapse" data-target="#collapseFour">
            <h5 class="mb-0">


                    <span class="fas fa-question fa-fw"></span>
                    Help
                </span>
            </h5>
        </div>
        <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#accordion">
            <div class="card-body about-text">
                <p>The REDCap Admin Dashboard provides a number of reports on various project and user metadata in a sortable table view. This data can also be downloaded as a CSV formatted file (as well as other delimited formats). Additionally, user-defined reports can be included via custom SQL queries. Reports can optionally be shared with non-admin users in a limited format (Executive Dashboard).</p>

                <p>Please refer to the README for extensive documentation on this module's usage and configuration options. The update changelog can also be a valuable source of information on new features or bugfixes.</p>

                <div style="text-align: center;">
                    <button id="viewReadme" class="btn btn-info">View README</button>
                    <button id="showChangelog" class="btn btn-info">Show Changelog</button>
                </div>

                <br />

                <p>Feedback is welcome, as are any questions/concerns/issues you may have. Please send an email to <a href="mailto:isabelle-neuhaus@uiowa.edu?subject=Admin Dashboard">isabelle-neuhaus@uiowa.edu</a> or create a post mentioning me (@izzy.neuhaus) on the REDCap community. If you are having an issue, it is recommended that you include a diagnostic file, as it can be immensely helpful for troubleshooting purposes.</p>

                <p>Please note that the diagnostic file includes <strong>all Admin Dashboard settings stored in your database</strong> (including custom report SQL queries and executive usernames), formatted in JSON. This file can be easily edited with any text editor program if you would like to remove sensitive information before sharing.</p>

                <br />
                <br />

                <div style="text-align: center;">
                    <button id="diagnostic" class="btn btn-info">Download diagnostic file</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Report setup modal -->
<div class="modal fade" id="reportSetupModal" tabindex="-1" role="dialog" aria-labelledby="reportSetupModal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content secondary-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="reportSetupModalLongTitle" style="text-align: center">Configure Report</h5>
                <div>
                    <button type="button" class="btn btn-secondary close-report-setup" data-dismiss="modal">Close</button>
                    <div class="btn-group save-report">
                        <button type="button" class="btn btn-primary save-report-setup">Save</button>
                        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item save-report-setup" href="#">Save & View Report</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <div id="reportIndex" style="display: none;"></div>
                <form id="reportConfiguration" novalidate data-toggle="validator">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="reportName">Title:</label>
                            <input id="reportName" name="reportName" class="form-control" required>
                            <small id="titleValidation" class="invalid-feedback">
                                Report name must be unique.
                            </small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="reportIcon">Icon:</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i id="reportIconPreview" class="fas fa-question"></i></span>
                                </div>
                                <input id="reportIcon" data-placement="bottomRight" class="form-control" value="question" type="text" aria-describedby="iconHelpBlock">
                            </div>
                            <small id="iconHelpBlock" class="form-text text-muted">
                                Accepts most Solid Icons from Font Awesome (<a href="https://fontawesome.com/cheatsheet#solid" style="font-size: inherit" target="_blank">reference</a>)
                            </small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="reportDescription">Description:</label>
                        <input id="reportDescription" class="form-control">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="reportDisplayType">Report Type:</label>
                            <div class="input-group">
                                <select class="form-control" id="reportDisplayType" aria-describedby="displayHelpBlock">
                                    <option value="sql">SQL Query</option>
                                    <option value="projects">Project Join</option>
                                </select>
                            </div>
                            <small id="displayHelpBlock" class="form-text text-muted">
                                Query REDCap metadata or combined project data
                            </small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="reportCustomId">Report ID:</label>
                            <div class="input-group custom-report-id-group">
                                <input id="reportCustomId" name="reportCustomId" data-placement="bottomRight" class="form-control custom-error" type="text" aria-describedby="idHelpBlock">
                                <div class="input-group-append">
                                    <span id="reportId" class="input-group-text" readonly="true"></span>
                                </div>
                            </div>
                            <small id="idHelpBlock" class="form-text text-muted">
                                Define optional string for easier bookmarking. The report index (in grey) is used by default.
                            </small>
                        </div>
                    </div>
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="sqlTab" data-toggle="tab" href="#sql" role="tab" aria-controls="sql" aria-selected="true">SQL Query</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="projectJoinTab" data-toggle="tab" href="#projectJoin" role="tab" aria-controls="projectJoin" aria-selected="true" style="display: none">Select Fields</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link disabled" id="formattingTab" data-toggle="tooltip" href="#formatting" role="tab" aria-controls="formatting" aria-selected="false" title="Run 'Test Query' first to populate Special Formatting tab">Special Formatting</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link disabled" id="joinOptionsTab" data-toggle="tab" href="#joinOptions" role="tab" aria-controls="joinOptions" aria-selected="false" style="display: none">Options</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link disabled" id="chartConfigTab" data-toggle="tooltip" href="#chartConfig" role="tab" aria-controls="chartConfig" aria-selected="false" style="display: none;" title="Run 'Test Query' first to populate Chart Configuration tab">Chart Configuration</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="sql" role="tabpanel" aria-labelledby="sqlTab">
                            <div class="form-group" style="padding-bottom: 5%">
                                <small id="queryHelpBlock" class="form-text text-muted">
                                    SELECT queries only.
                                </small>
                                            <textarea id="reportQuery" aria-describedby="queryHelpBlock"></textarea>
                                <div id="testQueryResult" style="float:left; padding-left: 10px; padding-top: 10px; max-width:80%;"></div>
                                <div style="text-align:right; float:right; padding-top: 10px;">
                                    <button type="button" class="btn btn-info test-query">Test Query</button>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="projectJoin" role="tabpanel" aria-labelledby="projectJoinTab">
                            <div class="form-group" style="padding-bottom: 5%">
                                <small style="text-align: center; margin-bottom:30px" class="form-text text-muted">
                                    <i class="fas fa-dot-circle"></i> to designate matching field (typically Record ID for 2nd project)
                                    <br />
                                    <i class="fas fa-check-square"></i> to include field data in report
                                </small>
                                <div class="container">
                                    <div class="row">
                                        <div class="col">
                                            <label for="pid1">Primary Project: </label>
                                            <br />
                                            <select class="pid-select form-control" name="pid1" data-project="1">
                                                <option value="">---Select---</option>
                                            </select>
                                            <div class="otherPidDiv" style="display: none; padding:10px" data-project="1">
                                                <label for="otherPid1" style="float: left; padding:10px">Enter PID: </label>
                                                <input class="pid-other form-control w-50" name="otherPid1" data-project="1">
                                            </div>
                                        </div>
                                        <div class="col">
                                            <label for="pid2">Joined Project: </label>
                                            <br />
                                            <select class="pid-select form-control" name="pid2" data-project="2">
                                                <option value="">---Select---</option>
                                            </select>
                                            <div class="otherPidDiv" style="display: none; padding:10px" data-project="2">
                                                <label for="otherPid2" style="float: left; padding:10px">Enter PID: </label>
                                                <input class="pid-other form-control w-50" name="otherPid2" data-project="2">
                                            </div>
                                        </div>
                                        <div class="w-100"></div>
                                        <div class="col">
                                            <div class="loading-icon" style="text-align: center; padding: 5%; display: none" data-project="1">
                                                <i class="fas fa-spinner fa-spin fa-7x"></i>
                                            </div>
                                            <div class="join-field-list border rounded" data-project="1" style="display: none">
                                                <br />
                                                <label style="font-weight: bold">Project Fields [<span class="p1Title"></span>]:</label>
                                                <div class="project-checkboxes-1">
                                                    <ul>
                                                        <li>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="loading-icon" style="text-align: center; padding: 5%; display: none" data-project="2">
                                                <i class="fas fa-spinner fa-spin fa-7x"></i>
                                            </div>
                                            <div class="join-field-list border rounded" data-project="2" style="display: none">
                                                <br />
                                                <label style="font-weight: bold">Project Fields [<span class="p2Title"></span>]:</label>
                                                <div class="project-checkboxes-2">
                                                    <ul>
                                                        <li>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="formatting" role="tabpanel" aria-labelledby="formattingTab">
                            <div class="config-modal" id="formattingConfig">
                                <table class="table table-striped" style="table-layout: fixed;">
                                    <thead>
                                    <tr>
                                        <th><strong>Column</strong></th>
                                        <th><strong>Formatting</strong></th>
                                        <th><strong>Display</strong></th>
                                    </tr>
                                    </thead>
                                    <tbody class="column-list">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="joinOptions" role="tabpanel" aria-labelledby="joinOptionsTab">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showChoiceLabels">
                                <label class="form-check-label" for="showChoiceLabels">
                                    Show choice labels (instead of coded values)
                                </label>
                            </div>
                            <br />
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="matchesOnly">
                                <label class="form-check-label" for="matchesOnly">
                                    Only return records with valid matches in secondary project
                                </label>
                            </div>
                            <br />
                            <div style="display: none">
                                <div style="font-weight: bold">Project-Level Access [<span class="p1Title"></span>]</div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="allowProjectUsersP1" value="0" id="allowProjectUsersP1None" checked>
                                    <label class="form-check-label" for="allowProjectUsersP1None">
                                        None (Admin/Executive only)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="allowProjectUsersP1" value="1" id="allowProjectUsersP1Full">
                                    <label class="form-check-label" for="allowProjectUsersP1Full">
                                        Allow users with "Full Data Set" rights
                                    </label>
                                </div>
                                <br />
                                <div style="font-weight: bold">Project-Level Access [<span class="p2Title"></span>]</div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="allowProjectUsersP2" value="0" id="allowProjectUsersP2None" checked>
                                    <label class="form-check-label" for="allowProjectUsersP2None">
                                        None (Admin/Executive only)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="allowProjectUsersP2" value="1" id="allowProjectUsersP2Full">
                                    <label class="form-check-label" for="allowProjectUsersP2Full">
                                        Allow users with "Full Data Set" rights
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="chartConfig" role="tabpanel" aria-labelledby="chartConfigTab">
                            <div class="config-modal" id="chartConfigView">
                                <select>
                                    <option value="line">Line</option>
                                    <option value="bar">Bar</option>
                                    <option value="pie">Pie</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="confirmDelete">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Delete Report</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="confirmMsgReport">Are you sure you want to delete this report?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger confirmDelete" data-dismiss="modal">Delete</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="executiveUserAdded">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Executive User Added</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="confirmMsgExec">Click "Continue" to edit report visibility for <span class="display-executive-username" style="color: green; word-wrap: break-word"></span>.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary edit-executive-visibility" data-dismiss="modal">Continue</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="executiveUserRemove">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Remove Executive User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="confirmMsgExec">Are you sure you want to revoke <span class="display-executive-username" style="color: red; word-wrap: break-word"></span>'s Executive Dashboard access?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger confirm-remove-user" data-dismiss="modal">Confirm</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>