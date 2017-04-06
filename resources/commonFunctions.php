<?php

function LogToConsole( $data ) {
    $output = $data;
    if ( is_array( $output ) )
        $output = implode( ',', $output);

    echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
}

function SqlQuery($conn, $pageInfo)
{
// execute the SQL statement
    $result = mysqli_query($conn,  $pageInfo['sql'] );

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