SELECT
    projects.project_id AS 'PID',
    projects.app_title AS 'Project Title',
    CAST(CASE projects.status
        WHEN 0 THEN 'Development'
        WHEN 1 THEN 'Production'
        WHEN 2 THEN 'Inactive'
        WHEN 3 THEN 'Archived'
        ELSE projects.status
    END AS CHAR(50)) AS 'Status',
    counts.record_count AS 'Record Count',
    CAST(CASE projects.purpose
        WHEN 0 THEN 'Practice / Just for fun'
        WHEN 4 THEN 'Operational Support'
        WHEN 2 THEN 'Research'
        WHEN 3 THEN 'Quality Improvement'
        WHEN 1 THEN 'Other'
        ELSE projects.purpose
    END AS CHAR(50)) AS 'Purpose',
    GROUP_CONCAT(rights.username SEPARATOR '@@@') AS 'Users',
    DATE_FORMAT(projects.creation_time, '%Y-%m-%d') AS 'Creation Date',
    DATE_FORMAT(projects.last_logged_event, '%Y-%m-%d') AS 'Last Logged Event Date',
    DATEDIFF(now(), projects.last_logged_event) AS 'Days Since Last Event',
    COUNT(rights.username) AS 'Total Users',
    -- Hidden reference columns
    projects.project_id,
    projects.status,
    projects.date_deleted,
    GROUP_CONCAT(CAST(
        CASE WHEN info.user_suspended_time IS NOT NULL THEN 'T' ELSE 'F'
    END AS CHAR(1)) SEPARATOR '@@@') AS 'user_suspended_time'
FROM redcap_projects AS projects
LEFT JOIN redcap_record_counts AS counts ON projects.project_id = counts.project_id
LEFT JOIN redcap_user_rights AS rights ON projects.project_id = rights.project_id
LEFT JOIN redcap_user_information AS info ON rights.username = info.username
$formattedWhereFilterSql
GROUP BY projects.project_id
ORDER BY projects.project_id