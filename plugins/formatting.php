<?php
// why don't QC posts linkify?

add_filter('the_content', 'sd_linkify');
function sd_linkify( $content ) {
	global $post;
	if ( 'ticket' == $post->post_type )
		$content = make_clickable($content);
	return $content;
}