<div style="text-align: left; width: 100%">
    <div style="height: 75px;"></div>
</div>

<h2 style="text-align: center; color: {if $executiveAccess}#4DADAF{else}#106CD6{/if}; font-weight: bold;">
    {if $executiveAccess}Executive{else}Admin{/if} Dashboard
</h2>

{if ($executiveAccess && $superUser)}
    <div style="text-align: center;" id="currentExecutiveUser">
        <label for="primaryUserSelect">
            <b>Viewing as:</b>
        </label>
        <select id="primaryUserSelect" class="executiveUser">
            <option value="">[Select User]</option>
            {foreach $executiveUsers as $user}
                {if $user}
                    <option value="{$user}">{$user}</option>
                {/if}
            {/foreach}
        </select>
    </div>
{/if}

<p />

<ul class='nav nav-tabs report-tabs'>
    {foreach $reportReference as $index => $reportInfo}
        <li class="nav-item {if $reportId == $index && $reportId != null}active{/if}" style="display:none">
            <a class="nav-link" href="{$reportInfo['url']}">
                <span class="report-icon fas fa-{$reportInfo['tabIcon']}"></span>
                &nbsp; <span class="report-title">{$reportInfo['reportName']}</span>
            </a>
        </li>
    {/foreach}
</ul>

<p />

<div class="modal fade" id="changelogModal" tabindex="-1" role="dialog" aria-labelledby="updateModal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateModal" style="text-align: center">Changelog</h5>
                <div>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
            <div class="modal-body">
                {foreach $changelogContent as $versionInfo}
                    <h3>v{$versionInfo['version']}</h3>
                    <p style="font-size:14px">{$versionInfo['notes']}</p>
                    <ul>
                        {foreach $versionInfo['changes'] as $change}
                            <li>{$change}</li>
                        {/foreach}
                    </ul>
                {/foreach}
            </div>
        </div>
    </div>
</div>