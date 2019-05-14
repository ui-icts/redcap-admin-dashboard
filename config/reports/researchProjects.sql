SELECT
    projects.project_id AS 'PID',
    projects.app_title AS 'Project Title',
    projects.status AS 'Status',
    CAST(CASE
        WHEN rcounts.record_count IS NULL THEN 0
        ELSE rcounts.record_count
    END AS CHAR(50)) AS 'Record Count',
    projects.purpose_other AS 'Purpose Specified',
    projects.purpose_other AS 'Purpose Specified (Export)', -- split into true/false columns
    projects.project_pi_lastname AS 'PI Last Name',
    projects.project_pi_firstname AS 'PI First Name',
    projects.project_pi_email AS 'PI Email',
    projects.project_irb_number AS 'IRB Number',
    DATE_FORMAT(projects.creation_time, '%Y-%m-%d') AS 'Creation Date',
    DATE_FORMAT(projects.last_logged_event, '%Y-%m-%d') AS 'Last Logged Event Date',
    DATEDIFF(now(), projects.last_logged_event) AS 'Days Since Last Event',
    -- Additional reference columns for Special Formatting (hidden)
    projects.project_id, -- for links to project pages
    projects.status, -- for highlighting archived projects in grey
    projects.date_deleted -- for highlighting deleted projects in red
FROM redcap_projects AS projects
LEFT JOIN redcap_record_counts AS rcounts ON rcounts.project_id = projects.project_id
WHERE projects.purpose = 2
ORDER BY projects.project_id DESC