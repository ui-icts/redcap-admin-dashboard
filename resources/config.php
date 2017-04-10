<?php

require_once('commonFunctions.php');

// handy html utilities
require_once('htmlUtilities.php');

// set error reporting for debugging
require_once('errorReporting.php');

// Report titles, descriptions, and SQL data
$reportReference = array
(
    array // Projects by User
    (
        "reportName" => "Projects By User",
        "fileName" => "projectsByUser",
        "description" => "Listing of users and the projects of which they are members.",
        "tabIcon" => "fa fa-male",
        "sql" => "
SELECT
    info.username AS 'HawkID',
    CONCAT( info.user_lastname, ', ', info.user_firstname ) AS 'User Name',
    info.user_email AS 'User Email',
    GROUP_CONCAT( CAST( project.project_id AS CHAR(50) ) SEPARATOR ', ' ) AS 'Project Titles',
    COUNT( project.project_id ) AS 'Projects Total'
    -- project.app_title AS 'Project Title'
FROM redcap_user_information AS info,
    redcap_projects AS project,
    redcap_user_rights AS access
WHERE info.username = access.username AND
      access.project_id = project.project_id
GROUP BY info.ui_id
ORDER BY info.user_lastname, info.user_firstname, info.username
      "
    ),
    array // Users by Project
    (
        "reportName" => "Users by Project",
        "fileName" => "usersByProject",
        "description" => "Listing of projects and their members.",
        "tabIcon" => "fa fa-folder",
        "sql" => "
SELECT
    redcap_projects.project_id AS PID,
    app_title AS 'Project Name',
    CAST( CASE status
        WHEN 0 THEN 'Development'
        WHEN 1 THEN 'Production'
        WHEN 2 THEN 'Inactive'
        WHEN 3 THEN 'Archive'
        ELSE status
    END AS CHAR(50) ) AS 'Status',
    record_count AS 'Record Count',
    CAST( CASE purpose
        WHEN 0 THEN 'Practice / Just for fun'
        WHEN 1 THEN 'Operational Support'
        WHEN 2 THEN 'Research'
        WHEN 3 THEN 'Quality Improvement'
        WHEN 4 THEN 'Other'
        ELSE purpose
    END AS CHAR(50) ) AS 'Purpose',
    GROUP_CONCAT( ( redcap_user_rights.username ) SEPARATOR ', ' ) AS 'Project Users',
    DATE_FORMAT( last_logged_event, '%Y-%m-%d' ) AS 'Last Event Date',
    DATEDIFF( now(), last_logged_event ) AS 'Event Days',
    COUNT( redcap_user_rights.username ) AS 'Users Total'
FROM redcap_projects
LEFT JOIN redcap_record_counts ON redcap_projects.project_id = redcap_record_counts.project_id
LEFT JOIN redcap_user_rights ON redcap_projects.project_id = redcap_user_rights.project_id
GROUP BY redcap_projects.project_id
ORDER BY app_title
       "
    ),
    array // Research Projects
    (
        "reportName" => "Research Projects",
        "fileName" => "researchProjects",
        "description" => "Listing of projects that are identified as being used for research purposes.",
        "tabIcon" => "fa fa-flask",
        "sql" => "
SELECT
    redcap_projects.project_id AS PID,
    app_title AS 'Project Name',
    CAST( CASE status
        WHEN 0 THEN 'Development'
        WHEN 1 THEN 'Production'
        WHEN 2 THEN 'Inactive'
        WHEN 3 THEN 'Archive'
        ELSE status
    END AS CHAR(50) ) AS 'Status',
    record_count AS 'Record Count',
    purpose_other AS 'Purpose Specified',
    CONCAT( project_pi_lastname, ', ', project_pi_firstname, ' ', project_pi_mi ) AS 'PI Name',
    project_pi_email AS 'PI Email',
    project_irb_number AS 'IRB Number',
    DATE_FORMAT( last_logged_event, '%Y-%m-%d' ) AS 'Last Event Date',
    DATEDIFF( now(), last_logged_event ) AS 'Event Days'
FROM redcap_projects
LEFT JOIN redcap_record_counts ON redcap_projects.project_id = redcap_record_counts.project_id
WHERE purpose = 2  -- 'Research'
ORDER BY app_title
      "
    ),
    array // Passwords in Projects
    (
        "reportName" => "Passwords in Projects",
        "fileName" => "projectPassword",
        "description" => "Listing of projects that contain strings related to passwords/HawkIDs in the project title.",
        "tabIcon" => "fa fa-key",
        "sql" => "
SELECT projects.project_id AS 'PID',
      app_title AS 'Project Name'
FROM redcap_projects AS projects,
      redcap_user_information AS users
WHERE (projects.created_by = users.ui_id) AND
    ( (app_title LIKE '%pass%word%') OR
      (app_title LIKE '%pass%wd%') OR
      (app_title LIKE '%hawk%id%') OR
      (app_title LIKE '%user%name%' ) OR
      (app_title LIKE '%user%id%' ) );
       "
    ),
    array // Passwords in Instruments
    (
        "reportName" => "Passwords in Instruments",
        "fileName" => "instrumentPassword",
        "description" => "Listing of projects that contain strings related to passwords/HawkIDs in the instrument or form name.",
        "tabIcon" => "fa fa-key",
        "sql" => "
SELECT projects.project_id AS 'PID',
        projects.app_title AS 'Project Name',
        meta.form_menu_description AS 'Instrument Name'
FROM redcap_projects AS projects,
        redcap_metadata AS meta,
        redcap_user_information AS users
WHERE (projects.created_by = users.ui_id) AND
      (projects.project_id = meta.project_id) AND
      (meta.form_menu_description IS NOT NULL) AND
    ( (app_title LIKE '%pass%word%') OR
      (app_title LIKE '%pass%wd%') OR
      (app_title LIKE '%hawk%id%') OR
      (app_title LIKE '%user%name%' ) OR
      (app_title LIKE '%user%id%' ) );
              "
    ),
    array // Passwords in Fields
    (
        "reportName" => "Passwords in Fields",
        "fileName" => "fieldPassword",
        "description" => "Listing of projects that contain strings related to passwords/HawkIDs in one of the fields.",
        "tabIcon" => "fa fa-key",
        "sql" => "
SELECT projects.project_id AS 'PID',
        projects.app_title AS 'Project Name',
        meta.form_name AS 'Form Name',
        meta.field_name AS 'Field Name',
        meta.element_label AS 'Field Label',
        meta.element_note AS 'Field Note'
FROM redcap_projects AS projects,
        redcap_metadata AS meta,
        redcap_user_information AS users
WHERE (projects.created_by = users.ui_id) AND
        (projects.project_id = meta.project_id) AND
        ( (field_name LIKE '%pass%word%') OR
        (field_name LIKE '%pass%wd%') OR
        (field_name LIKE '%hawk%id%') OR
        (field_name LIKE '%hwk%id%') OR
        (field_name LIKE '%user%name%') OR
        (field_name LIKE '%user%id%') OR
        (field_name LIKE '%usr%name%') OR
        (field_name LIKE '%usr%id%') OR
        (element_label LIKE '%pass%word%') OR
        (element_label LIKE '%pass%wd%') OR
        (element_label LIKE '%hawk%id%') OR
        (element_label LIKE '%hwk%id%') OR
        (element_label LIKE '%user%name%') OR
        (element_label LIKE '%user%id%') OR
        (element_label LIKE '%usr%name%') OR
        (element_label LIKE '%usr%id%') OR
        (element_note LIKE '%pass%word%') OR
        (element_note LIKE '%pass%wd%') OR
        (element_note LIKE '%hawk%id%') OR
        (element_note LIKE '%hwk%id%') OR
        (element_note LIKE '%user%name%') OR
        (element_note LIKE '%user%id%') OR
        (element_note LIKE '%usr%name%') OR
        (element_note LIKE '%usr%id%') )
ORDER BY projects.project_id, form_name, field_name;
      "
    ),
    array // Graphs
    (
        "reportName" => "Graphs",
        "fileName" => "graphs",
        "description" => "Additional data presented in graph form.",
        "tabIcon" => "fa fa-pie-chart"
    )
);

$miscQueryReference = array
(
    array
    (
        "queryName" => "Suspended Users",
        "sql" => "
SELECT count(*) FROM redcap_user_information WHERE user_suspended_time IS NOT NULL
        "
    )
);