<?php



namespace Solenoid\CDN;



use \Solenoid\System\Stream;



class Server
{
    # Returns [void]
    public static function send (Stream $stream, ?int $rate = null)
    {
        while ( !$stream->is_ended() )
        {// Processing each iteration
            // (Reading the content)
            echo $stream->read( $rate ?? 4096 );

            // (Flushing the buffer)
            flush();



            if ( $rate )
            {// Value found
                // (Waiting for the seconds)
                sleep( 1 );
            }
        }



        // (Closing the stream)
        $stream->close();
    }
}



?>