SELECT
    projects.project_id AS 'PID',
    projects.app_title AS 'Project Title',
    CAST(CASE
        WHEN rcounts.record_count IS NULL THEN 0
        ELSE rcounts.record_count
    END AS CHAR(50)) AS 'Record Count',
    projects.status AS 'Status',
    projects.purpose AS 'Purpose',
    DATE_FORMAT(projects.creation_time, '%Y-%m-%d') AS 'Creation Date',
    DATE_FORMAT(projects.last_logged_event, '%Y-%m-%d') AS 'Last Logged Event Date',
    DATEDIFF(now(), projects.last_logged_event) AS 'Days Since Last Event',
    -- Additional reference columns for Special Formatting (hidden)
    projects.project_id, -- for links to project pages
    projects.status, -- for highlighting archived projects in grey
    projects.date_deleted -- for highlighting deleted projects in red
FROM redcap_projects AS projects
LEFT JOIN redcap_record_counts AS rcounts ON rcounts.project_id = projects.project_id
ORDER BY projects.project_id DESC