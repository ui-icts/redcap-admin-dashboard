## Admin Dashboard

### Description
The REDCap Admin Dashboard provides a number of reports on various project and user metadata in a sortable table view. This data can also be downloaded in CSV format (NOTE: CSV exports will always include all results and not respect any filtering or sorting).

The following reports are included by default:
* **Projects by User** (List of users and the projects to which they have access)
* **Users by Project** (List of projects and the users which have access)
* **Research Projects** (List of projects that are identified as being used for research purposes)
* **Development Projects** (List of projects that are in Development Mode)
* **All Projects** (List of all projects, excluding those designated as 'Practice/Just for Fun')

Additional reports can be optionally be enabled via the Configuration page:
* **External Modules by Project** (List of External Modules and the projects they are enabled in)
* **Login Credentials Check** (Reports to find strings related to REDCap usernames/passwords in projects)

Some simple visual representations of project statuses and research purposes are also available.

### Usage
After downloading and enabling this module on your REDCap instance, a link to the Admin Dashboard will appear at the bottom of the Control Center sidebar.

### Configuration Options
* **Default report view:** Selecting one of the default reports here will make it load immediately after opening the Admin Dashboard. Leaving this option unselected will display a simple landing page instead of loading a report (this is recommended, as reports with large result sets can take a while to process and should not be run unless necessary).
* **Show archived projects:** Enabling this option will include archived projects in report views. Archived project titles are grey in color.
* **Show deleted projects:** Enabling this option will include projects marked for deletion in report views. Deleted project titles are red in color.
* **Display PIDs instead of titles in 'Projects by User' CSV file:** The list of project titles displayed on the "Projects by User" report can be extremely long and difficult to read in CSV format. Enabling this option will display a list of project ID numbers instead when exporting the CSV file.

The following settings are specific to optional reports:

* **Additional search term for Login Credentials Check reports:** This repeatable field can be used to define additional search terms to be queried when running the Login Credentials Check reports. This can be helpful for defining institution specific usernames. See [this page](https://www.w3schools.com/sql/sql_wildcards.asp) for information about using wildcards in search terms.

### User Defined Reports
Additional reports can be defined through custom SQL queries. Some things to be mindful of when creating your own reports:

* Please exercise caution when adding your own SQL queries. For security reasons, only 'SELECT' queries can be used. Executing queries with large result sets could impact server performance.
* The "Report Icon" field uses [Font Awesome icons](http://fontawesome.com/icons). You can add one by pasting the class string into this field. (e.g. to use the [solid folder icon](https://fontawesome.com/icons/folder?style=solid), the class string would be 'fa fa-folder')
* The "Report Enabled" option must be checked for each custom report tab to be visible on the Admin Dashboard. This is included to easily hide custom reports while keeping their SQL queries saved and ready to be re-enabled at any time.
* Custom reports are not affected by the "Show archived/deleted projects" configuration options.

When the following column aliases are used in queries (e.g. *SELECT app_title AS 'Project Title'*), their results will receive special formatting in the Admin Dashboard view (same as the built-in reports):

* 'Project Title' - Returns a link to the related project.
* 'PID' - Returns a link to the related project's settings page inside the Control Center.
* 'Username' - Returns a link to the related user's information page inside the Control Center.
* 'Email'/'PI Email' - Returns a mailto link addressed to the given email address.