<?php

//pseudo theme activation hook.
if ( is_admin() && isset($_GET['activated'] ) && $GLOBALS['pagenow'] == "themes.php" )
	add_action('init', 'sd_set_qc_defaults', 1000); // run last so that all other stuff is loaded

function sd_set_qc_defaults() {
	$status_tax = 'ticket_status';
	$defaults = array( 'New', 'In Progress', 'Pending Signoff', 'Complete', 'Declined', 'Proposed' );

	foreach ( $defaults as $name ) {
		$ret = wp_insert_term($name, $status_tax);
		if ( is_wp_error($ret) )
			continue;
		if ( 'New' == $name )
			$new_ticket_id = $ret['term_id'];
		if ( 'Complete' == $name )
			$resolved_ticket_id = $ret['term_id'];
	}
	
	$options = get_option( 'quality_options' );
	if ( ! $options )
		$options = array();
	
	if ( ! isset($options['default_status']) || $options['default_status'] < 2 )
		$options['default_status'] = (int) $new_ticket_id;
	
	if ( ! isset($options['ticket_resolved_state']) || $options['ticket_resolved_state'] < 2 )
		$options['ticket_resolved_state'] = (int) $resolved_ticket_id;
		
	if ( ! isset( $options['ticket_page'] ) || $options['ticket_page'] < 2 ) {
		$post = array(
			'post_status' => 'publish',
			'post_type' => 'page',
			'post_content' => 'You must be logged in to create a ticket.',
			'post_title' => 'New Ticket'
		);
		$page_id = wp_insert_post($post);
		if ( ! is_wp_error($page_id) ) {
			update_post_meta( $page_id, '_wp_page_template', 'create-ticket.php' );
			$options['ticket_page'] = $page_id;
		}
		else {
			$options['ticket_page'] = 0;
		}
	}
		
	update_option( 'quality_options', $options );
}