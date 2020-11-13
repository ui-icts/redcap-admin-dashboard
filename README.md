## Admin Dashboard

### Description
The REDCap Admin Dashboard provides a number of reports on various project and user metadata in a sortable, filterable table view. Additional user-defined reports can be included via custom SQL queries. This data can also be downloaded as a CSV formatted file (as well as other delimited formats) and reports can optionally be shared with non-admin users in a limited format (see Executive Dashboard).

The following reports are included by default:
* **Projects by User** (List of all users and the projects to which they have access)
* **Users by Project** (List of all projects and the users which have access)
* **Research Projects** (List of all projects that are identified as being used for research purposes)
* **Development Projects** (List of all projects that are in Development status)
* **All Projects** (List of all projects)
* **External Modules by Project** (List of External Modules and the projects they are enabled in)

All reports (built-in and user-defined) can be toggled on/off via the "Settings" page (accessed by clicking the button located in the top left of any dashboard page). Hidden reports will not be shown in the navigation bar but can still be accessed via direct link. User-defined reports can also be added/edited/deleted in this view (built-in reports can be opened in a read-only view for reference purposes).

### Usage
After downloading and enabling this module on your REDCap instance, a link to the Admin Dashboard will appear at the bottom of the Control Center sidebar. This module only works on a system-level and should not be enabled on projects.

### Filtering
Just below the header for each column is an input for filtering. Simple text filtering can be performed as well as more complex filtering with the following:

* **Greater/Less than (equal to):** `< <= >= >`
* **Not:** `!` or `!=`
* **Exact:** `"` or `=`
* **And:** `&&` or `and`
* **Range:** `-` or `to`
* **Wildcard:** `*` or `?`
* **Or:** `|` or `or`
* **Fuzzy:** `~`

Regular Expressions can also be used for filtering.

### Exporting
Report results can be exported via the button located in the top right (just above the report title) of the page. By default, this button will download a CSV file with all rows titled with the name of the report and the date/time it was loaded. A dropdown menu with additional export options can be opened by clicking the arrow next to this button. The options are as follows:

* **Export to:** 'Download' will initiate a file download of the exported data. Additionally, the filename can be defined and the appended date/timestamp can be toggled on/off. 'Popup' will open a popup window with the exported data in a text box so it can be easily copied and pasted elsewhere.
* **Separator:** The delimiter for exported data can be selected from 4 common options (comma, semicolon, tab, and space), special formatting to JSON, or an array format. The separator can also be manually defined.
* **Include:** 'All' will export all rows regardless of visibility due to pagination or filtering. 'Filtered' will only return the rows currently visible based on the current column filters set (this also does not care about pagination and will return rows not currently visible as well, so long as they meet the filter criteria).

### User-Defined Reports
Additional reports can be defined via the "Configure Reports" section of the settings page. The following information can be defined:

* **Title** - The name of your report. This value must be unique (no two reports can have the same title) and cannot be blank.
* **Icon** - An icon that will appear next to your report title in the report navigation bar. This accepts most "solid" icons from Font Awesome and will display a preview if the icon name entered is valid.
* **Description** - A short description that will appear under the report title when the report is rendered.
* **Report Type** - "SQL Query" is the most common type of report, but a "Project Join" option is also available. This secondary option will return a joined result set from two REDCap projects.
* **Report ID** - A unique alphanumeric string that can be used as an alternative to the report index when loading a report directly via URL. This can be useful for permanently bookmarking reports (report indexes can change as reports are added and deleted so they are not reliable in this respect). The report index can still be used even if a custom ID is defined.

## Special Formatting
A number of special formatting options are available to further customize the functionality and appearance of your reports. The "Special Formatting" tab will be enabled after the query has had a successful test run.

The column being configured will receive the special formatting, but a secondary column with an **exact name** will often be used as reference (for example, designating a column with "Project Setup" will cause that column's data to appear as a link to a REDCap Project Setup page, based on the value from a secondary **project_id** column).

A number of links to various REDCap project pages and user-related pages can be added as special formatting. These links require "project_id" and "username" (respectively) columns to exist in order to build the URLs. Formatting options in the "Other" category will use itself as reference instead of a secondary column.

Formatting options in the "Code Lookup" category will not generate links and do not require a secondary reference column. The intended use for these options is to automatically convert common columns used in REDCap from coded values to readable text (such as project status or purpose).

Additionally, columns can be set to show/hide always, or specific to Admin/Executive views. It may be preferable to hide the "secondary" reference columns for a cleaner report view.

When using a "GROUP_CONCAT" function on a secondary column, be sure to CAST null values to 'N/A' so they can maintain their correct positions in the list and match properly with the primary column's values.

NOTE: All built-in reports use special formatting and may be helpful for reference purposes. They cannot be edited, but the queries and special formatting options can still be viewed as read-only.

### Executive Dashboard
Non-admin users with a valid REDCap login may be granted access to a limited version of the dashboard without special link formatting (projects, emails, etc). The reports accessible in this view can be customized on a per-user basis via the "Configure Reports" section of the settings page. By default, no reports are enabled for added users and any attempt to access this view by non-admin users will display an "access denied" error instead (this will also happen if an executive user attempts to follow a direct link to a specific report that they do not have permission to access).

Access to this view can be granted by whitelisting usernames in the "Executive User Management" section of the settings page. The data export functionality can also be enabled/disabled on a per-user basis via this screen.

Admins can switch between the "Admin" and "Executive" views by clicking the button located at the bottom of any dashboard page. When viewing the Executive Dashboard as an admin, the "Viewing as" dropdown is visible below the page header. This allows you to quickly switch between whitelisted users and preview their dashboard views to ensure the proper set of reports are visible/invisible to them.

For executive users, there is no way to directly access this view through the REDCap UI. An admin will need to provide them with a direct link to the page so they can bookmark it for future use. The "Send Link" button next to each user in the "Executive User Management" section will open an email template addressed to the user's primary REDCap email (if found).

### Custom Columns
Columns can be hidden and rearranged dynamically by clicking the "Edit Columns" button at the bottom of a report table. These changes do not currently persist on page refresh.

### Additional Options
* **Use versionless URLs for easier bookmarking:** This option uses REDCap's API endpoint to make URLs shorter and not require a specified version number (which would normally break bookmarks post-upgrade). This should be ideal for most users, but due to known issues with Shibboleth authentication, this feature can be disabled if dashboard pages fail to load. It will be enabled by default for most users, but will be automatically disabled (upon enabling the module itself) if the REDCap server has Shibboleth authentication enabled.
* **Show icons next to suspended or non-existent usernames:** Disabling this option will hide the status icons next to usernames that appear if a user is either suspended or if the given username does not appear to have a valid REDCap account (no user profile). NOTE: These icons rely on secondary reference columns "user_suspended_time" and "user_exists" respectively. If these columns are not present in a user-defined report, the icons will not display.

### Help
The "Help" section of the settings page provides some contact information for questions/feedback, as well as a button that initiates a file download containing **all Admin Dashboard-related settings stored in your database**. It is recommended to include this file as an attachment when submitting requests for support, as it can make it much easier to pin down issues. This file is in a plainly readable JSON format, so if you have any concern about what information it includes, you can easily open the file to confirm (and remove sensitive information before submitting, if necessary).