## Admin Dashboard

### Description
The REDCap Admin Dashboard provides a number of reports on various project and user metadata in a sortable table view. This data can also be downloaded as a CSV formatted file (as well as other delimited formats). Additionally, user-defined reports can be included via custom SQL queries.

The following reports are enabled by default:
* **Projects by User** (List of all users and the projects to which they have access)
* **Users by Project** (List of all projects and the users which have access)
* **Research Projects** (List of all projects that are identified as being used for research purposes)
* **Development Projects** (List of all projects that are in Development Mode)
* **All Projects** (List of all projects)
* **Visualizations** (3 simple pie chart representations of project status and research purpose counts)

Additional reports are included but hidden by default:
* **External Modules by Project** (List of External Modules and the projects they are enabled in)
* **Login Credentials Check** (Reports to find strings related to usernames/passwords in projects)

All reports (built-in and user-defined) can be toggled on/off via the "Show/Hide Reports" button located in the top left of any dashboard page. Hidden reports will not be shown in the navigation bar but can still be accessed via direct link.

### Usage
After downloading and enabling this module on your REDCap instance, a link to the Admin Dashboard will appear at the bottom of the Control Center sidebar.

#### Filtering
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

#### Exporting
Report results can be exported via the button located in the top right (just above the report title) of the page. By default, this button will download a CSV file with all rows titled with the name of the report and the date/time it was loaded. A dropdown menu with additional export options can be opened by clicking the arrow next to this button. The options are as follows:

* **Separator:** The delimiter for exported data can be selected from 4 common options (comma, semicolon, tab, and space) or special formatting to JSON or an array format can be selected via two additional buttons. The separator can also be manually defined.
* **Include:** 'All' will export all rows regardless of visibility due to pagination or filtering. 'Filtered' will only return the rows currently visible based on the current column filters set (this also does not care about pagination and will return rows not currently visible as well, so long as they meet the filter criteria).
* **Export to:** 'Download' will initiate a file download of the exported data. Additionally, the filename can be defined and the appended date/timestamp can be toggled on/off. 'Popup' will open a popup window with the exported data in a text box so it can be easily copied and pasted elsewhere.

When exporting a report that includes the "Purpose Specified" column, it will split the purpose data into separate columns marked as TRUE/FALSE for easier analysis.

#### Executive View
Non-admin users with a valid REDCap login can be granted access to a limited version of the dashboard without link formatting (projects, emails, etc). The reports accessible in this view can be customized via the "Show/Hide Reports" button located in the top left of the page. By default, no reports are enabled for Executive View and attempts to access it by non-admin users will display an "access denied" error instead (this will also happen if a user attempts to follow a direct link to a report that is marked as hidden in Executive View).

Access to this view can be granted by whitelisting usernames via the module configuration page. Admins can switch between the "Admin" and "Executive" views by clicking the button located at the bottom of any dashboard page. Admins can also access the "Show/Hide Reports" button from either view, but both of these buttons will be hidden from non-admin users.

For non-admin users, there is no way to directly access this view through the REDCap UI. An admin will need to provide them with a direct link to the page so they can bookmark it for future use.

### Configuration Options
* **Default report view:** Selecting one of the default reports here will make it load immediately after opening the Admin Dashboard. Leaving this option unselected will display a simple landing page instead of loading a report (this is recommended, as reports with large result sets can take a while to process and should not be run unless necessary). This setting only affects the main Admin View (the Executive View will always show a landing page regardless).
* **Show "Practice / Just for Fun" projects:** Enabling this option will include projects marked as "Practice / Just for Fun" in report views.
* **Show archived projects:** Enabling this option will include archived projects in report views. Archived project titles are grey in color.
* **Show deleted projects:** Enabling this option will include projects marked for deletion in report views. Deleted project titles are red in color.
* **Mark suspended users with red [suspended] tag:** Enabling this option will add a red "[suspended]" tag after suspended usernames in the reports that they appear. Suspended users will always be included in default reports regardless of this setting; it only changes how they are displayed.

The following settings are specific to optional reports:

* **Additional search term for Login Credentials Check reports:** This repeatable field can be used to define additional search terms to be queried when running the Login Credentials Check reports. This can be helpful for defining institution specific usernames. See [this page](https://www.w3schools.com/sql/sql_wildcards.asp) for information about using wildcards in search terms.

The following settings are specific to Executive View:

* **Username to allow access to the Executive View:** This repeatable field can be used to define which REDCap users are allowed access to the Executive View.
* **Enable Export button in Executive View:** Enabling this option will allow the Export button to appear on the Executive View (disabled by default).

### User Defined Reports
Additional reports can be defined through custom SQL queries. After adding them via the module configuration, they can be enabled via the "Show/Hide Reports" button on any dashboard page. Some things to be mindful of when creating your own reports:

* Please exercise caution when adding your own SQL queries. For security reasons, only 'SELECT' queries can be used. Executing queries with large result sets could impact server performance.
* The "Report Icon" field uses [Font Awesome icons](http://fontawesome.com/icons). You can add one by pasting the class string into this field. (e.g. to use the [solid folder icon](https://fontawesome.com/icons/folder?style=solid), the class string would be 'fa fa-folder')
* Custom reports are not affected by the "Show archived/deleted projects" configuration options.

When the following column aliases are used in queries (e.g. `SELECT app_title AS 'Project Title'`), their results will receive special formatting in the Admin View (same as the built-in reports):

* 'Project Title' - Returns a link to the related project.
* 'PID' - Returns a link to the related project's settings page inside the Control Center.
* 'Username' - Returns a link to the related user's information page inside the Control Center).
* 'Email'/'PI Email' - Returns a mailto link addressed to the given email address.

Some additional special formatting may be available (if a built-in report uses it, a custom report can use it), but may require more specific usage to work correctly. The built-in queries can be found [here](https://gist.github.com/eaneuhaus/95ec2010599497e88dfaf710a86a5f99) for reference.