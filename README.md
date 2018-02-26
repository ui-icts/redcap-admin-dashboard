## Admin Dashboard

### Description
The REDCap Admin Dashboard provides a number of reports on various project and user metadata in a sortable table view. This data can also be downloaded in CSV format (NOTE: CSV exports will always include all results and not respect any filtering).

The following reports are included:
* **Projects by User** (List of users and the projects to which they have access)
* **Users by Project** (List of projects and the users which have access)
* **Research Projects** (List of projects that are identified as being used for research purposes)
* **Development Projects** (List of projects that are in Development Mode)
* **All Projects** (List of all projects, excluding those designated as 'Practice/Just for Fun')
* **Passwords in Project Titles/Instruments/Fields** (List of projects that contain strings related to REDCap login credentials in Project Titles/Instruments/Fields)

Some simple visual representations of project statuses and research purposes are also available.

### Usage
After downloading and enabling this module in your REDCap instance, a link to the Admin Dashboard will appear at the bottom of the Control Center sidebar.

### Configuration Options
* **Show archived projects:** Enabling this option will include archived projects in report views. Archived projects titles are grey in color.
* **Show deleted projects:** Enabling this option will include projects marked for deletion in report views. Deleted projects titles are red in color.
* **Additional search term for username/password reports:** Any terms entered here will be added to the list of default terms checked when running the "Passwords in Project Titles/Instruments/Fields" reports. The "%" symbol is a wildcard that can be used to represent zero, one, or multiple characters.
* **Display PIDs instead of titles in 'Projects by User' CSV file:** The list of project titles displayed on the "Projects by User" report can be extremely long and difficult to read in CSV format. Enabling this option will display a list of project ID numbers instead when exporting the CSV file.