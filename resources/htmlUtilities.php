<?php
/**
 * @file htmlUtilities.php
 * @author Fred R. McClurg, University of Iowa
 * @date October 14, 2014
 * @version 1.0
 *
 * @brief Library of HTML convenience utilities
 */


/**
 * @brief Displays a table header
 *
 * @param $tableId ID of of the table
 * @param $columns Array of table headings
 */
function PrintTableHeader( $tableId, $columns )
{
   printf( "
<table id='%s' class='tablesorter'>
   <thead>
      <tr>\n", $tableId );

   foreach ( $columns as $name )
      printf( "         <th> %s </th>\n", $name );

   printf( "
      </tr>
   </thead>\n" );

}  // function PrintTableHeader();


/**
 * @brief Displays a table row
 *
 * @param $row Hash table cells
 */
function PrintTableRow( $row )
{
   printf( "      <tr>\n" );

   foreach ( $row as $key => $value )
   {
      printf( "         <td> %s </td>\n", $value );
   }

   printf( "      </tr>\n" );
}  // function PrintTableRow();


/**
 * @brief Converts the data columns to the proper HTML
 *        format by adding mailto links to email etc.
 *
 * @param  $row             Associative array of table cells
 * @param  $projectTitles  Associative array of REDCap Titles
 * @return $webified       HTML table cell data ready for output
 */
function WebifyDataRow( $row, &$projectTitles )
{
   // initialize value
   $webified = array();

   foreach ( $row as $key => $value )
   {
      if ( $key == "PID" )
      {
         $webified[$key] = ConvertPid2Link( $value, $value );
      }
      elseif ( $key == "Project Name" )
      {
         $pid = $row['PID'];
         $hrefStr = $row['Project Name'];

         $webified[$key] = ConvertPid2Link( $pid, $hrefStr );
      }
      elseif ( $key == "Name of Project" )
      {
         $pid = $row['PID'];
         $hrefStr = $row['Name of Project'];

         $webified[$key] = ConvertPid2AdminLink( $pid, $hrefStr );
      }
      elseif ( $key == "Project Titles" )
      {
         $webified[$key] = ConvertPidList2Links( $value, $projectTitles );
      }
      elseif ( $key == "Purpose Specified" )
      {
         $webified[$key] = ConvertProjectPurpose2List( $value );
      }
      elseif ( ( $key == "PI Email" ) ||
               ( $key == "Owner Email" ) ||
               ( $key == "User Email" ) )
      {
         $webified[$key] = ConvertEmail2Link( $value );
      }
      elseif ( ( $key == "Owner HawkID" ) ||
               ( $key == "Project Users" ) ||
               ( $key == "HawkID" ) )
      {
         $webified[$key] = ConvertHawkId2Link( $value );
      }
      else
      {
         $webified[$key] = $value;
      }
   }

   return( $webified );
}  // function WebifyDataRow();


/**
 * @brief Converts an email address into a clickable
 *        link using the <a> tag and mailto URL
 *
 * @param  $email       Email address
 * @return $mailtoLink  HTML mailto link
 */
function ConvertEmail2Link( $email )
{
   $mailtoLink = sprintf( "<a href=\"mailto:%s\">%s</a>",
                            $email, $email );

   return( $mailtoLink );
}  // function ConvertEmail2Link();


/**
 * @brief Converts a Project ID into a HTML link to the
 *        REDCap Project
 *
 * @param  $pid      Project Identifier Number
 * @param  $hrefStr  Text string of the link
 * @return $pidLink  HTML link to REDCap project
 */
function ConvertPid2Link( $pid, $hrefStr )
{
   // https://www-dev.icts.uiowa.edu/redcap/redcap_v5.10.0/ProjectSetup/index.php?pid=15
   $urlString =
      sprintf( "https://%s%sProjectSetup/index.php?pid=%d",  // Project Setup page
                  SERVER_NAME,  // www-dev.icts.uiowa.edu
                  APP_PATH_WEBROOT, // /redcap/redcap_v5.10.0/
                  $pid );  // 15

   $pidLink = sprintf( "<a href=\"%s\"
                          target=\"_blank\">%s</a>",
                            $urlString, $hrefStr );

   return( $pidLink );
}  // function ConvertPid2Link();


/**
 * @brief Converts a Project ID into a HTML link to the
 *        REDCap Project
 *
 * @param  $pid      Project Identifier Number
 * @param  $hrefStr  Text string of the link
 * @return $pidLink  HTML link to REDCap project
 */
function ConvertPid2AdminLink( $pid, $hrefStr )
{
   // https://www-dev.icts.uiowa.edu/redcap/redcap_v6.1.4/ControlCenter/edit_project.php?project=15
   $urlString =
      sprintf( "https://%s%sControlCenter/edit_project.php?project=%d",  // Project Setup page
                  SERVER_NAME,  // www-dev.icts.uiowa.edu
                  APP_PATH_WEBROOT, // /redcap/redcap_v5.10.0/
                  $pid );  // 15

   $pidLink = sprintf( "<a href=\"%s\"
                          target=\"_blank\">%s</a>",
                            $urlString, $hrefStr );

   return( $pidLink );
}  // function ConvertPid2Link();


/**
 * @brief Converts a Project ID into a HTML link to the
 *        REDCap Project
 *
 * @param  $pidStr     Comma separated list of Project IDs (PIDs)
 * @param  $pidTitles  Associative array of project titles
 * @return $pidCell    Comma-delimited href links to projects
 */
function ConvertPidList2Links( $pidStr, &$pidTitles )
{
   // convert comma-delimited string to array
   $pidList = explode( ", ", $pidStr );
   $pidLinks = array();

   foreach ( $pidList as $pid )
   {
      $hrefStr = $pidTitles[$pid];
      array_push( $pidLinks, ConvertPid2Link( $pid, $hrefStr ) );
   }

   // convert array back to comma-delimited string
   $pidCell = implode( "<br />", $pidLinks );

   return( $pidCell );
}  // function ConvertPidList2Links();


/**
 * @brief Converts a HawkID into a HTML link to the
 *        REDCap user information
 *
 * @param  $hawkID    Comma separated list of REDCap
 *                    HawkIDs (usernames) of users
 * @return $linkStr  HTML link to REDCap user
 */
function ConvertHawkId2Link( $hawkIDs )
{
   // convert comma delimited string to array
   $hawkIDList = explode( ", ", $hawkIDs );
   $linkList = array();

   foreach ( $hawkIDList as $hawkID )
   {
      // https://www-dev.icts.uiowa.edu/redcap/redcap_v5.10.0/ControlCenter/view_users.php?username=fmcclurg
      $urlString =
         sprintf( "https://%s%sControlCenter/view_users.php?username=%s",  // Browse User Page
                     SERVER_NAME,  // www-dev.icts.uiowa.edu
                     APP_PATH_WEBROOT, // /redcap/redcap_v5.10.0/
                     $hawkID );  // fmcclurg

      $userLink = sprintf( "<a href=\"%s\"
                              target=\"_blank\">%s</a>",
                                 $urlString, $hawkID );

      array_push( $linkList, $userLink );
   }

   // convert array to comma delimited string
   $linkStr = implode(  "<br>", $linkList );

   return( $linkStr );
}  // function ConvertHawkId2Link();


/**
 * @brief Converts a comma separated list of integers
 *        to the equivalent project purpose strings
 *
 * @param  $purposeList  Comma delimited string of purpose integers
 * @return $purposeStr   Comma delimited String of project purpose names
 */
function ConvertProjectPurpose2List( $purposeList )
{
   // initialize variables
   $purposeResults = array();
   $purposeParts = explode( ",", $purposeList );
   $purposeMaster = array( "Basic or Bench Research",
                           "Clinical Research Study or Trial",
                           "Translational Research 1",
                           "Translational Research 2",
                           "Behavioral or Psychosocial Research Study",
                           "Epidemiology",
                           "Repository",
                           "Other" );

   foreach ( $purposeParts as $index )
   {
      array_push( $purposeResults, $purposeMaster[$index] );
   }

   $purposeStr = implode( ", ", $purposeResults );

   return( $purposeStr );
}  // function ConvertProjectPurpose2List();


/**
 * @brief Functions like a stopwatch and displays time elapsed time
 *
 *         Executing the function the first time starts the clock.
 *         When the function is executed after the first time,
 *         the function returns the elapsed time as a string.
 *
 * @return $elapsedTimeStr  Elapsed time in hours, minutes, seconds
 */
function ElapsedTime()
{
   // initialize variables
   static $startTime = null;
   $elapseTimeStr = "";

   if ( $startTime == null )  // start the clock
   {
      $startTime = round( microtime( true ) );
      // printf( "\$startTime: %f<br />", $startTime );
   }
   else
   {
      $endTime = round( microtime( true ) );
      // printf( "\$endTime: %f<br />", $endTime );
      $elapseTime = $endTime - $startTime;
      // printf( "\$elapsedTime: %f<br />", $elapsedTime );

      $elapseTimeStr = date( "i:s", $elapseTime );
   }

   return( $elapseTimeStr );
}  // function ElapsedTime();