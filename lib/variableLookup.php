<?php
/**
 * @file variableLookup.php
 * @author Fred R. McClurg, University of Iowa
 * @date September 5, 2014
 * @version 1.0
 */

/**
 * @brief Defines a number of variables like SQL queries that are referenced by
 *        this program in one place
 */

// set standard error reporting
// require_once( "errorReporting.php");

// debugging functions
// require_once( "debugFunctions.php" );


/**
 * @brief Retrieves the information that is common to each page
 *
 * @param  $tabNumber         Current tab selected
 * @retval $data['subtitle']  The page title
 * @retval $data['file']      Name of the filename prefix used for downloading
 * @retval $data['summary']   Project summary description
 * @retval $data['sql']       The SQL command
 */
function GetPageDetails( $tabNumber )
{
   if ( $tabNumber == 1 )  // Users by Project
   {
      $projectTitle = "Users by Project";
      $fileName = "usersByProject";
      $description = "Listing of REDCap users and the projects
                      of which they are members.";

      $sql = "
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
      ORDER BY info.user_lastname, info.user_firstname, info.username";
   }
   elseif ( $tabNumber == 2 )  // Research Projects
   {
      $projectTitle = "Research Projects";
      $fileName = "researchProjects";
      $description = "Listing of only REDCap Projects that are
                      identified as Research Projects.  Also
                      includes the associated Principal
                      Investigator (PIs) and project owner (creator).";

      $sql = "
   SELECT
      project_id AS PID,
      app_title AS 'Project Name',
      -- 0 = development  1 = production  3 = archive
      CAST( CASE status
         WHEN 0 THEN 'Development'
         WHEN 1 THEN 'Production'
         WHEN 2 THEN 'Inactive'
         WHEN 3 THEN 'Archive'
         ELSE status
      END AS CHAR(50) ) AS 'Category',
      -- 0 = Practice / Just for fun
      -- 1 = Operational Support
      -- 2 = Research
      -- 3 = Quality Improvement
      -- 4 = Other
      purpose_other AS 'Purpose Specified',
                      -- 0 = Basic or bench research
                      -- 1 = Clinical research study or trial
                      -- 2 = Translational research 1 (applying discoveries to the development of trials and studies in humans)
                      -- 3 = Translational research 2 (enhancing adoption of research findings and best practices into the community)
                      -- 4 = Behavioral or psychosocial research study
                      -- 5 = Epidemiology
                      -- 6 = Repository (developing a data or specimen repository for future use by investigators)
                      -- 7 = Other
      CONCAT( project_pi_lastname, ', ', project_pi_firstname, ' ', project_pi_mi ) AS 'PI Name',
      project_pi_email AS 'PI Email',
      project_irb_number AS 'IRB Number',
      CONCAT( user_lastname, ', ', user_firstname ) AS 'Owner Name',
      user_email AS 'Owner Email',  -- FROM redcap_user_information
      username AS 'Owner HawkID'  -- FROM redcap_user_information
      FROM redcap_projects, redcap_user_information
      WHERE ui_id = created_by AND
            purpose = 2  -- 'Research'
      ORDER BY app_title";
   }
   elseif ( $tabNumber == 3 )  // Owner Project Summary
   {
      $projectTitle = "Owner Project Summary";
      $fileName = "ownerProjectSummary";
      $description = "Listing of REDCap owners, their
                      associated projects and a sum total of
                      projects.";

      $sql = "
      SELECT info.username AS 'Owner HawkID',
         CONCAT( info.user_lastname, ', ', info.user_firstname ) AS 'Owner Name',
         info.user_email AS 'Owner Email',
         GROUP_CONCAT( CAST( project.project_id AS CHAR(50) ) SEPARATOR ', ' ) AS 'Project Titles',
         COUNT( info.ui_id ) AS 'Projects Owned'
      FROM redcap_projects AS project, redcap_user_information AS info
      WHERE project.created_by = info.ui_id
      GROUP BY info.ui_id
      ORDER BY info.user_lastname, info.user_firstname";
   }
   elseif ( $tabNumber == 4 )  // Power User Summary
   {
      $projectTitle = "Power User Summary";
      $fileName = "powerUserSummary";
      $description = "Listing of REDCap users and a summation
                      of events they have performed in the last
                      12 months. An event represents a specific
                      operation within REDCap like creating,
                      modifying, or removing a record.";

      // obtain a listing of all REDCap owners and totals
      $sql = "
      SELECT user AS 'HawkID',
          CONCAT( info.user_lastname, ', ', info.user_firstname ) AS 'Users Name',
          info.user_email AS 'User Email',
          GROUP_CONCAT( DISTINCT event ORDER BY event DESC SEPARATOR ', ' ) AS 'User Events',
          COUNT( user ) AS 'Event Count'
      FROM redcap_log_event AS logs,
           redcap_user_information AS info
      WHERE ts BETWEEN DATE_FORMAT( SUBDATE( NOW(), 365 ), '%Y%m%d%H%i%s' ) AND DATE_FORMAT( NOW(), '%Y%m%d%H%i%s' ) AND
            logs.user = info.username
      GROUP BY user
      ORDER BY info.user_lastname, info.user_firstname";
   }
   elseif ( $tabNumber == 5 )  // Power User Details
   {
      $projectTitle = "Power User Details";
      $fileName = "powerUserDetails";
      $description = "Listing of REDCap users and the specific
                      events they have performed in the last
                      12 months. An event represents a specific
                      operation within REDCap like creating,
                      modifying, or removing a record.";

      $sql = "
      SELECT user AS 'HawkID',
          CONCAT( info.user_lastname, ', ', info.user_firstname ) AS 'Users Name',
          info.user_email AS 'User Email',
          event AS 'User Event',
          -- GROUP_CONCAT( DISTINCT event ORDER BY event DESC SEPARATOR ', ' ) AS 'User Events',
          COUNT( event ) AS 'Event Count'
      FROM redcap_log_event AS logs,
           redcap_user_information AS info
      WHERE ts BETWEEN DATE_FORMAT( SUBDATE( NOW(), 365 ), '%Y%m%d%H%i%s' ) AND DATE_FORMAT( NOW(), '%Y%m%d%H%i%s' ) AND
            logs.user = info.username
      GROUP BY user, event
      ORDER BY info.user_lastname, info.user_firstname, event DESC";
   }
   else  // if ( ! isset( $_REQUEST['tab'] ) )  // Project by Owners
   {
      $projectTitle = "Project by Owner";
      $fileName = "projectByOwner";
      $description = "Listing of REDCap Projects and its
                      associated owner (original creator).
                      The listing also includes the PI and
                      the users that have access to the project.";

      $sql = "
      SELECT
         project.project_id AS 'PID',
         TRIM( project.app_title ) AS 'Project Name',
         CAST( CASE status
            WHEN 0 THEN 'Development'
            WHEN 1 THEN 'Production'
            WHEN 2 THEN 'Inactive'
            WHEN 3 THEN 'Archive'
            ELSE status
         END AS CHAR(50) ) AS 'Category',
         CAST( CASE purpose
            WHEN 0 THEN 'Practice'
            WHEN 1 THEN 'Operational Support'
            WHEN 2 THEN 'Research'
            WHEN 3 THEN 'Quality Improvement'
            WHEN 4 THEN 'Other'
            ELSE purpose
         END AS CHAR(50) ) AS 'Purpose',
            -- 0 = Basic or bench research
            -- 1 = Clinical research study or trial
            -- 2 = Translational research 1 (applying discoveries to the development of trials and studies in humans)
            -- 3 = Translational research 2 (enhancing adoption of research findings and best practices into the community)
            -- 4 = Behavioral or psychosocial research study
            -- 5 = Epidemiology
            -- 6 = Repository (developing a data or specimen repository for future use by investigators)
            -- 7 = Other
         purpose_other AS 'Purpose Specified',
         CONCAT( project_pi_lastname, ', ', project_pi_firstname, ' ', project_pi_mi ) AS 'PI Name',
         project_pi_email AS 'PI Email',
         project_irb_number AS 'IRB Number',
         info.username AS 'Owner HawkID',
         CONCAT( user_lastname, ', ', user_firstname ) AS 'Owner Name',
         info.user_email AS 'Owner Email',
         GROUP_CONCAT(user.username) AS 'Project Users',
         COUNT(user.username) AS 'User Count'
      FROM redcap_projects AS project,
           redcap_user_rights AS user,
           redcap_user_information AS info
      WHERE project.project_id = user.project_id AND
            project.created_by = info.ui_id
      GROUP BY project.project_id
      ORDER BY TRIM( project.app_title )";
   }

   $data['subtitle'] = $projectTitle;
   $data['file'] = $fileName;
   $data['summary'] = $description;
   $data['sql'] = $sql;

   return( $data );
}

?>
