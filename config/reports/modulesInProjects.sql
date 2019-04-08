SELECT
    REPLACE(directory_prefix, '_', ' ') AS 'Module Title',
    GROUP_CONCAT(DISTINCT CAST(projects.project_id AS CHAR(50)) SEPARATOR ', ') AS 'Project Titles',
    GROUP_CONCAT(DISTINCT CAST(users.user_email AS CHAR(50)) SEPARATOR ', ') AS 'User Emails',
    GROUP_CONCAT(CAST(CASE projects.status
        WHEN 0 THEN 'Development'
        WHEN 1 THEN 'Production'
        WHEN 2 THEN 'Inactive'
        WHEN 3 THEN 'Archived'
        ELSE projects.status
    END AS CHAR(50))) AS 'Project Statuses (Hidden)',
    GROUP_CONCAT(CAST(CASE
        WHEN projects.date_deleted IS NULL THEN 'N/A'
        ELSE projects.date_deleted
    END AS CHAR(50))) AS 'Project Deleted Date (Hidden)',
    COUNT(DISTINCT projects.project_id) AS 'Total Projects'
FROM redcap_external_module_settings AS settings
LEFT JOIN redcap_external_modules ON redcap_external_modules.external_module_id = settings.external_module_id
LEFT JOIN redcap_projects AS projects ON projects.project_id = settings.project_id
LEFT JOIN redcap_user_rights AS rights ON rights.project_id = projects.project_id
LEFT JOIN redcap_user_information AS users ON users.username = rights.username
WHERE settings.key = 'enabled'
  AND (settings.value = 'true' OR settings.value = 'enabled')
  AND settings.project_id IS NOT NULL
  $formattedFilterSql
GROUP BY settings.external_module_id
ORDER BY directory_prefix