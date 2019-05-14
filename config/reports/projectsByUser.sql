SELECT
    users.username AS 'Username',
    users.user_lastname AS 'Last Name',
    users.user_firstname AS 'First Name',
    users.user_email AS 'Email',
    GROUP_CONCAT(projects.app_title SEPARATOR '@@@') AS 'Project Titles',
    COUNT(projects.project_id) AS 'Total Projects',
    -- Additional reference columns for Special Formatting (hidden)
    GROUP_CONCAT(projects.project_id SEPARATOR '@@@') AS 'project_id', -- for links to project pages
    GROUP_CONCAT(projects.status SEPARATOR '@@@') AS 'status', -- for highlighting archived projects in grey
    GROUP_CONCAT(CAST(CASE
        WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
    END AS CHAR(50)) SEPARATOR '@@@') AS 'date_deleted', -- for highlighting deleted projects in red
    users.user_suspended_time -- for displaying icons next to suspended users
FROM redcap_user_information AS users,
    redcap_user_rights AS rights,
    redcap_projects AS projects
WHERE users.username = rights.username AND
    rights.project_id = projects.project_id
GROUP BY users.ui_id
ORDER BY users.user_lastname,
    users.user_firstname,
    users.username