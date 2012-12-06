<?php
/**
 * Nagios Stats
 *
 * Pull statistics from local nagios caches
 *
 * @package Nagios Stats
 *
 * @author James Cunningham <j@goscale.com>
 * @copyright Copyright 2012 James Cunningham
 */


/**
 * Nagios Stats
 */
class nagios_stats
{

	/* --- Private Variables ---
	   ------------------------------------------------------------ */
	
	/**
	 * Status.dat file
	 *
	 * @var     string
	 * @access  private
	 */
	private $status_file;
	
	/**
	 * Objects.cache file
	 *
	 * @var     string
	 * @access  private
	 */
	private $object_file;
	
	
	
	/* --- Constructs ---
	   ------------------------------------------------------------ */
	
	/**
	 * Construct
	 *
	 * @param   string  $status_file  Location of Status.dat
	 * @param   string  $object_file  Location of Objects.cache
	 */
	function __construct( $status_file = '/var/cache/nagios3/status.dat', $object_file = '/var/cache/nagios3/objects.cache' )
	{
		// Status.dat and Objects.cache
		$this->status_file = $status_file;
		$this->object_file = $object_file;
		
		// Check Files Exist
		if ( !file_exists( $this->status_file ) || !file_exists( $this->object_file ) )
		{
			throw new Exception( 'Status.dat or Object.cache not found!' );
		}
		
		// Refresh Data
		$this->refresh();
	}
	
	
	
	/* --- Parse Nagios Objects Cache ---
	   ------------------------------------------------------------ */
	
	/**
	 * Parse Nagios Objects Cache
	 *
	 * @param   string  $file  File to Parse
	 * @return  object|boolean
	 */
	public function parse_nagios_objects_cache( $file )
	{
		// Open File Pointer
		$f = fopen( $file, 'r' );
		
		if ( !$f ) { throw new Exception( 'Unable to open file ' . $file ); return false; }
		
		$out = new stdClass;
		
		// Read Line-By-Line
		while ( ( $line = fgets( $f, 4096 ) ) !== false )
		{
			// Remove Excess Whitespace
			$line = trim( $line );
			
			// Skip Empty Lines
			if ( empty( $line ) ) continue;
			
			// Ignore Comments
			if ( substr( $line, 0, 1 ) == '#' ) continue;
			
			// New Object
			if ( substr( $line, -1 ) == '{' )
			{
				// Remove Define and trailing {
				if ( substr( $line, 0, 7 ) == 'define ' ) { $line = substr( $line, 7 ); }
				if ( substr( $line, -1 ) == '{' ) { $line = substr( $line, 0, -1 ); }
				
				$obj = new stdClass;
				
				// Set Type
				$type = trim( $line );
				
				continue;	// Next Line
			}
			
			// End Object
			if ( $line == '}' )
			{
				// Output Format
				if ( in_array( $type, array( 'host', 'hostgroup', 'contact', 'contactgroup', 'timeperiod', 'command' ) ) )
				{
					$out->{ $type }->{ $name } = $obj;
				} elseif( $type == 'service' )
				{
					// Add to Output
					$out->{ $type }->{ $command } = $obj;
				} else
				{
					// Add to Output
					$out->{ $type }[] = $obj;
				}
				
				continue;	// Next Line
			}
			
			// Split by Whitespace
			list( $key, $value ) = preg_split( '/[\s,]+/', $line, 2 );
			
			// Set Name and Command
			if ( substr( $key, -5 ) == '_name' ) { $name = $value; }
			if ( $key == 'check_command' ) { $command = $value; }
			
			// Members
			if ( $key == 'members' ) { $value = preg_split( '/[\s,]+/', $value, 2 ); }
			
			// Append to Object
			$obj->{ $key } = $value;
		}
		
		// Empty Output
		if ( empty( $out ) ) { return false; }
		
		return $out;
	}
	
	
	
	/* --- Parse Nagios Status File ---
	   ------------------------------------------------------------ */
	
	/**
	 * Parse Nagios Status File
	 *
	 * @param   string  $file  File to Parse
	 * @return  object|boolean
	 */
	public function parse_nagios_status_file( $file )
	{
		// Open File Pointer
		$f = fopen( $file, 'r' );
		
		if ( !$f ) { throw new Exception( 'Unable to open file ' . $file ); return false; }
		
		$out = new stdClass;
		
		// Read Line-By-Line
		while ( ( $line = fgets( $f, 4096 ) ) !== false )
		{
			// Remove Excess Whitespace
			$line = trim( $line );
			
			// Skip Empty Lines
			if ( empty( $line ) ) continue;
			
			// Ignore Comments
			if ( substr( $line, 0, 1 ) == '#' ) continue;
			
			// New Object
			if ( substr( $line, -1 ) == '{' )
			{
				// Remove Define and trailing status {
				if ( substr( $line, 0, 7 ) == 'define ' ) { $line = substr( $line, 7 ); }
				if ( substr( $line, -8 ) == 'status {' ) { $line = substr( $line, 0, -8 ); }
				if ( substr( $line, -1 ) == '{' ) { $line = substr( $line, 0, -1 ); }
				
				$obj = new stdClass;
				
				// Set Type
				$type = trim( $line );
				
				continue;	// Next Line
			}
			
			// End Object
			if ( $line == '}' )
			{
				// Add to Output
				$out->{ $type }[] = $obj;
				
				continue;	// Next Line
			}
			
			// Split by Whitespace
			list( $key, $value ) = explode( '=', $line, 2 );
			
			// Append to Object
			$obj->{ $key } = $value;
		}
		
		// Empty Output
		if ( empty( $out ) ) { return false; }
		
		return $out;
	}
	
	
	
	/* --- Refresh Data ---
	   ------------------------------------------------------------ */
	
	/**
	 * Refresh Stored Data
	 *
	 * @return  boolean
	 */
	public function refresh()
	{
		$this->refresh_objects();
		$this->refresh_status();
		
		return true;
	}
	
	
	/**
	 * Refresh Objects
	 *
	 * @return  boolean
	 */
	public function refresh_objects()
	{
		// Parse Objects
		$objects = $this->parse_nagios_objects_cache( $this->object_file );
		
		// Assign Objects
		$this->host			= $objects->host;
		$this->hostgroup	= $objects->hostgroup;
		$this->contact		= $objects->contact;
		$this->contactgroup	= $objects->contactgroup;
		$this->timeperiod	= $objects->timeperiod;
		$this->command		= $objects->command;
		
		// Assign Services
		foreach( $objects->service as $service )
		{
			// Add to host object
			$this->host->{ $service->host_name }->service->{ $service->check_command } = $service;
		}
		
		return true;
	}
	
	
	/**
	 * Refresh Status
	 *
	 * @return  boolean
	 */
	public function refresh_status()
	{
		// Parse Status
		$status = $this->parse_nagios_status_file( $this->status_file );
		
		// Assign Host Status
		foreach( $status->host as $host )
		{
			$this->host->{ $host->host_name }->status = $host;
		}
		
		// Assign Service Status
		foreach( $status->service as $service )
		{
			$this->host->{ $service->host_name }->service->{ $service->check_command }->status = $service;
		}
		
		return true;
	}


}