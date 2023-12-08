<?php



namespace Solenoid\CDN;



use \Solenoid\CDN\Server;
use \Solenoid\System\Stream;
use \Solenoid\System\Resource;
use \Solenoid\System\File;
use \Solenoid\System\Directory;



class Router
{
    private string $basedir;
    private array  $middlewares;

    private array  $exception_handlers;



    # Returns [self]
    public function __construct (string $basedir, array $middlewares = [], array $exception_handlers = [])
    {
        // (Getting the values)
        $this->basedir            = $basedir;
        $this->middlewares        = $middlewares;

        $this->exception_handlers = $exception_handlers;



        // (Setting the value)
        $this->exception_handlers =
        [
            'INVALID_PATH' => function ()
            {
                // (Setting the status code)
                http_response_code( 403 );

                // (Sending the header)
                header('Content-Type: application/json');

                // Printing the value
                echo json_encode( [ 'error' => [ 'message' => 'Path is not valid' ] ] );
            },

            'RESOURCE_NOT_FOUND' => function ()
            {
                // (Setting the status code)
                http_response_code( 404 );

                // (Sending the header)
                header('Content-Type: application/json');

                // Printing the value
                echo json_encode( [ 'error' => [ 'message' => 'Resource not found' ] ] );
            }
        ]
        ;
    }

    # Returns [Router]
    public static function create (string $basedir, array $middlewares = [], array $exception_handlers = [])
    {
        // Returning the value
        return new Router( $basedir, $middlewares, $exception_handlers );
    }



    # Returns [bool]
    public function resolve (string $path)
    {
        // (Getting the values)
        $rel_path = $path;

        $abs_path = $this->basedir . '/' . ( $rel_path[0] === '/' ? substr( $rel_path, 1 ) : $rel_path );
        $abs_path = Resource::select( $abs_path )->normalize()->get_path();



        // (Getting the value)
        $resource = Resource::select( $abs_path );



        if ( array_diff( explode( '/', $this->basedir ), explode( '/', $abs_path ) ) )
        {// (Path is not valid)
            // (Calling the function)
            $this->exception_handlers['INVALID_PATH']( $resource );

            // Returning the value
            return false;
        }

        if ( !$resource->exists() )
        {// (Resource not found)
            // (Calling the function)
            $this->exception_handlers['RESOURCE_NOT_FOUND']( $resource );



            // Returning the value
            return false;
        }



        foreach ($this->middlewares as $regex => $callback)
        {// Processing each entry
            if ( preg_match( $regex, $rel_path ) === 1 )
            {// (Regex matches the text)
                if ( $callback( $resource ) === false )
                {// Value is true
                    // Returning the value
                    return false;
                }
            }
        }



        if ( $resource->is_file() )
        {// (Resource is a file)
            // (Getting the value)
            $file_headers =
            [
                'Content-Type'        => $resource->get_type(),
                'Content-Length'      => File::select( $resource )->get_size(),

                'Content-Disposition' => 'attachment; filename="' . basename( $resource->get_path() ) . '"'
            ]
            ;

            foreach ($file_headers as $k => $v)
            {// Processing each entry
                // (Setting the header)
                header("$k: $v");
            }



            // (Sending the stream)
            Server::send( Stream::open( $resource->get_path() ) );
        }
        else
        if ( $resource->is_dir() )
        {// (Resource is a directory)
            // (Getting the value)
            $resources = array_map
            (
                function ($resource)
                {
                    // (Getting the value)
                    $resource = Resource::select( $resource );



                    // Returning the value
                    return
                    [
                        'path' => basename( $resource ),

                        'type' => $resource->get_type(),
                        'size' => $resource->is_file() ? File::select( $resource )->get_size() : 0
                    ]
                    ;
                },
                Directory::select( $resource )->list( 1 )
            )
            ;



            // (Getting the values)
            $content = json_encode( $resources );
            $headers =
            [
                'Content-Type'        => 'application/json',
                'Content-Length'      => strlen( $content ),
                'Content-Disposition' => 'inline'
            ]
            ;



            foreach ($headers as $k => $v)
            {// Processing each entry
                // (Setting the header)
                header("$k: $v");
            }



            // Printing the value
            echo $content;
        }



        // Returning the value
        return true;
    }
}



?>