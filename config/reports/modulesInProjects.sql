SELECT
    REPLACE(modules.directory_prefix, '_', ' ') AS 'Module Title',
    GROUP_CONCAT(DISTINCT CAST(projects.app_title AS CHAR(50)) SEPARATOR '@@@') AS 'Project Titles',
    GROUP_CONCAT(DISTINCT CAST(users.user_email AS CHAR(50)) SEPARATOR '@@@') AS 'User Emails',
    COUNT(DISTINCT projects.project_id) AS 'Total Projects',
    -- Additional reference columns for Special Formatting (hidden)
    GROUP_CONCAT(projects.project_id SEPARATOR '@@@') AS 'project_id', -- for links to project pages
    GROUP_CONCAT(projects.status SEPARATOR '@@@') AS 'status', -- for highlighting archived projects in grey
    GROUP_CONCAT(CAST(CASE
        WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
    END AS CHAR(50)) SEPARATOR '@@@') AS 'date_deleted' -- for highlighting deleted projects in red
FROM redcap_external_module_settings AS settings
LEFT JOIN redcap_external_modules AS modules ON modules.external_module_id = settings.external_module_id
LEFT JOIN redcap_projects AS projects ON projects.project_id = settings.project_id
LEFT JOIN redcap_user_rights AS rights ON rights.project_id = projects.project_id
LEFT JOIN redcap_user_information AS users ON users.username = rights.username
WHERE settings.key = 'enabled'
    AND (settings.value = 'true' OR settings.value = 'enabled')
    AND settings.project_id IS NOT NULL
GROUP BY settings.external_module_id
ORDER BY directory_prefix