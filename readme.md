# Nagios Status #

Nagios Status is a PHP class which parses `object.cache` and `status.dat`
from Nagios and provides an object for read-only access to its status
data and objects.


## Setup ##

    <?php
    
        require( 'nagios_status.class.php' );
        
        $ns = new nagios_status;
        
        echo $ns->host->localhost->status;
        
    ?>


## API ##

### __construct ###

    function __construct ( string $status_file, string $object_file )

`$status_file` is the location of status.dat, defaults to `/var/cache/nagios3/status.dat`
`$object_file` is the location of objects.cache, defaults to `/var/cache/nagios3/objects.cache`


### refresh() ###

Refreshes the data stored in the class. By default the given files are parsed on startup.

    function refresh ()
