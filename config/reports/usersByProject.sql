SELECT
    projects.project_id AS 'PID',
    projects.app_title AS 'Project Title',
    projects.status AS 'Status',
    CAST(CASE
        WHEN rcounts.record_count IS NULL THEN 0
        ELSE rcounts.record_count
    END AS CHAR(50)) AS 'Record Count',
    projects.purpose AS 'Purpose',
    GROUP_CONCAT(rights.username SEPARATOR '@@@') AS 'Users',
    DATE_FORMAT(projects.creation_time, '%Y-%m-%d') AS 'Creation Date',
    DATE_FORMAT(projects.last_logged_event, '%Y-%m-%d') AS 'Last Logged Event Date',
    DATEDIFF(now(), projects.last_logged_event) AS 'Days Since Last Event',
    COUNT(rights.username) AS 'Total Users',
    -- Additional reference columns for Special Formatting (hidden)
    projects.project_id, -- for links to project pages
    projects.status, -- for highlighting archived projects in grey
    projects.date_deleted, -- for highlighting deleted projects in red
    GROUP_CONCAT(CAST(CASE
        WHEN users.user_suspended_time IS NULL THEN 'N/A'
        ELSE users.user_suspended_time
    END AS CHAR(50)) SEPARATOR '@@@') AS 'user_suspended_time', -- for displaying icons next to suspended users
    GROUP_CONCAT(CAST(CASE
        WHEN users.username IS NULL THEN 'N/A'
        ELSE users.username
    END AS CHAR(50)) SEPARATOR '@@@') AS 'user_exists' -- for displaying icons next to users without profiles
FROM redcap_projects AS projects
LEFT JOIN redcap_record_counts AS rcounts ON rcounts.project_id = projects.project_id
LEFT JOIN redcap_user_rights AS rights ON rights.project_id = projects.project_id
LEFT JOIN redcap_user_information AS users ON users.username = rights.username
GROUP BY projects.project_id
ORDER BY projects.project_id DESC