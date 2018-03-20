## Admin Dashboard

### Description
The REDCap Admin Dashboard provides a number of reports on various project and user metadata in a sortable table view. This data can also be downloaded in CSV format (NOTE: CSV exports will always include all results and not respect any filtering).

The following reports are enabled by default:
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
After downloading and enabling this module in your REDCap instance, a link to the Admin Dashboard will appear at the bottom of the Control Center sidebar.

### Configuration Options
* **Show archived projects:** Enabling this option will include archived projects in report views. Archived projects titles are grey in color.
* **Show deleted projects:** Enabling this option will include projects marked for deletion in report views. Deleted projects titles are red in color.
* **Display PIDs instead of titles in 'Projects by User' CSV file:** The list of project titles displayed on the "Projects by User" report can be extremely long and difficult to read in CSV format. Enabling this option will display a list of project ID numbers instead when exporting the CSV file.

The following settings are specific to optional reports and will not have any effect if their related report is not enabled:

* **Additional search term for Login Credentials Check reports:** This repeatable field can be used to define additional search terms to be queried when running the Login Credentials Check reports. This can be helpful for defining institution specific usernames.