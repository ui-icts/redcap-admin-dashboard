<?php
/**
 * Created by PhpStorm.
 * User: eneuhaus
 * Date: 3/28/17
 * Time: 12:25 PM
 */

    function debug_to_console( $data ) {
        $output = $data;
        if ( is_array( $output ) )
            $output = implode( ',', $output);

        echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
    }

?>