<?php

	require( 'nagios_stats.class.php' );
	
	$n = new nagios_stats;

?>

<table width="100%" border="1" cellpadding="2" cellspacing="2">

	<tr>
		<td>Host</td>
		<td>Check</td>
		<td>Status</td>
	</tr>

<?php foreach( $n->host as $host ): ?>
<?php foreach( $host->service as $service ): ?>

	<tr>
		<td><?php echo $host->host_name; ?></td>
		<td><?php echo $service->service_description; ?></td>
		<td><?php echo $service->status->plugin_output; ?></td>
	</tr>

<?php endforeach; ?>
<?php endforeach; ?>

</table>