SELECT
    info.username AS 'Username',
    info.user_lastname AS 'Last Name',
    info.user_firstname AS 'First Name',
    info.user_email AS 'Email',
    GROUP_CONCAT(projects.app_title SEPARATOR '@@@') AS 'Project Titles',
    COUNT(projects.project_id) AS 'Total Projects',
    -- Hidden reference columns
    GROUP_CONCAT(projects.project_id SEPARATOR '@@@') AS 'project_id',
    GROUP_CONCAT(projects.status SEPARATOR '@@@') AS 'status',
    GROUP_CONCAT(CAST(
        CASE WHEN projects.date_deleted IS NOT NULL THEN 'T' ELSE 'F'
    END AS CHAR(1)) SEPARATOR '@@@') AS 'date_deleted',
    info.user_suspended_time
FROM redcap_user_information AS info,
    redcap_projects AS projects,
    redcap_user_rights AS access
WHERE info.username = access.username AND
    access.project_id = projects.project_id
    $formattedFilterSql
GROUP BY info.ui_id
ORDER BY info.user_lastname,
    info.user_firstname,
    info.username