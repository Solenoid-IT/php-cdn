<?php



namespace Solenoid\CDN;



class DataURL
{
    # Returns [string]
    public static function build (string $data, string $mime_type, ?string $charset = null)
    {
        // (Getting the value)
        $charset = $charset ? ";charset=$charset" : '';



        // Returning the value
        return "data:$mime_type{$charset};base64," . base64_encode( $data );
    }
}



?>