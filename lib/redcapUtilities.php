<?php
/**
 * @file redcapFunctions.php
 * @author Fred R. McClurg, University of Iowa
 * @date July 31, 2014
 * @version 1.0
 *
 * @brief REDCap specific utilities
 */

// set standard error reporting
// require_once( "errorReporting.php");

// debugging functions
// require_once( "debugFunctions.php" );


/**
 * @brief Obtains the all the REDCap project names and PIDs
 *
 * @retval $projectNameStr  Returns a hash of project names
 *                          with the id as the keys.
 */
function GetRedcapProjectNames($conn)
{
   if ( SUPER_USER )
   {
      $sql = "SELECT project_id AS pid,
                     TRIM(app_title) AS title
              FROM redcap_projects
              ORDER BY pid";
   }
   else
   {
      $sql = sprintf( "SELECT p.project_id AS pid,
                              TRIM(p.app_title) AS title
                       FROM redcap_projects p, redcap_user_rights u
                       WHERE p.project_id = u.project_id AND
                             u.username = '%s'
                       ORDER BY pid", USERID );
   }

    $query = mysqli_query($conn,  $sql );

   if ( ! $query )  // sql failed
   {
      die( "Could not execute SQL:
            <pre>$sql</pre> <br />" .
            mysqli_error($conn) );
   }

   $projectNameHash = array();

   while ( $row = mysqli_fetch_assoc($query) )
   {
      // $value = strip_tags( $row['app_title'] );
      $key = $row['pid'];
      $value = $row['title'];

      if (strlen($value) > 80)
      {
         $value = trim(substr($value, 0, 70)) . " ... " .
                             trim(substr($value, -15));
      }

      if ($value == "")
      {
         $value = "[Project title missing]";
      }

      $projectNameHash[$key] = $value;
   }

   return( $projectNameHash );

}  // function GetRedcapWildcardValues()
