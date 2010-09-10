<?php

/* MU Plugins Style. I hate messy functions.php files */
function sd_load_plugins() {
	$plugins = array();
	$plugin_dir = trailingslashit( STYLESHEETPATH ) . 'plugins';
	
	if ( !is_dir( $plugin_dir ) )
		return $plugins;
	if ( ! $dh = opendir( $plugin_dir ) )
		return $plugins;
	while ( ( $plugin = readdir( $dh ) ) !== false ) {
		if ( substr( $plugin, -4 ) == '.php' )
			$plugins[] = $plugin_dir . '/' . $plugin;
	}
	closedir( $dh );
	sort( $plugins );

	return $plugins;
}

foreach ( sd_load_plugins() as $sd_plugin )
	include_once( $sd_plugin );