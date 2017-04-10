<?php

function LogToConsole( $data ) {
    $output = $data;
    if ( is_array( $output ) )
        $output = implode( ',', $output);

    echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
}

function SqlQuery($conn, $queryInfo)
{
// execute the SQL statement
    $result = mysqli_query($conn,  $queryInfo['sql'] );

    if ( ! $result )  // sql failed
    {
        $message = printf( "Line: %d<br />
                          Could not execute SQL<br />
                          Error #: %d<br />
                          Error Msg: %s",
            __LINE__,
            mysqli_errno( $conn ),
            mysqli_error( $conn ) );
        die( $message );
    }
    else
    {
        return $result;
    }
}

function FormatQueryResults($conn, $result, $format)
{
    $redcapProjects = GetRedcapProjectNames($conn);
    $isFirstRow = TRUE;

    while ( $row = mysqli_fetch_assoc( $result ) )
    {
        if ( $format == 'html' ) {
            if ( $isFirstRow )
            {
                // use column aliases for column headers
                $headers = array_keys( $row );
                // print table header
                PrintTableHeader( $headers );
                printf( "   <tbody>\n" );
                $isFirstRow = FALSE;  // toggle flag
            }

            $webData = WebifyDataRow( $row, $redcapProjects );
            PrintTableRow( $webData );
            }
        elseif ( $format == 'csv' ) {
            if ( $isFirstRow )
            {
                // use column aliases for column headers
                $headers = array_keys( $row );

                $headerStr = implode( "\",\"", $headers );
                printf( "\"%s\"\n", $headerStr );

                $isFirstRow = FALSE;  // toggle flag
            }

            $row['Purpose Specified'] = ConvertProjectPurpose2List($row['Purpose Specified']);

            $rowStr = implode( "\",\"", $row );
            printf( "\"%s\"\n", $rowStr );
        }
        elseif ( $format == 'text' ) {
            $rowStr = implode( "\",\"", $row );
            return $rowStr;
            }
    }
}

function DisplayElapsedTime()
{
    $load = sys_getloadavg();

    printf( "<div id='elapsedTime'>
            Elapsed Execution Time: %s<br />
            System load avg last minute: %d%%<br />
            System load avg last 5 mins: %d%%<br />
            System load avg last 15 min: %d%%</div>",
            ElapsedTime(), $load[0] * 100, $load[1] * 100, $load[2] * 100 );
}

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

}