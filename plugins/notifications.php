<?php

class SD_Ticket_Notify {
	
	const POST_TYPE = 'ticket';
	const NOTIFY_META = '_ticket_notifications';
	
	public function __construct() {
		add_action('save_post', array($this,'save_post'), 10, 2 );
		add_action('quality_form_list_taxonomy', array($this, 'print_assign_notification') );
		add_action('quality_update_ticket_comment', array($this, 'comment_update'), 10, 1);
		add_action('quality_comment_update_made', array($this, 'send_comment_notification'), 10, 1 );
	}
	
	public function send_comment_notification( $comment_id ) {
		$this->send_notification( $GLOBALS['post']->ID, $comment_id );
	}
	
	public function comment_update($post) {
		if ( ! isset( $_POST[self::NOTIFY_META] ) || empty( $_POST[self::NOTIFY_META] ) )
			delete_post_meta( $post->ID, self::NOTIFY_META );
		else if ( isset( $_POST[self::NOTIFY_META] ) )
			update_post_meta( $post->ID, self::NOTIFY_META, $_POST[self::NOTIFY_META] );
	}
	
	/**
	 * Save user ids to notify.
	 */
	
	public function save_post( $post_id, $post ) {
		if ( self::POST_TYPE != $post->post_type || ( defined('DOING_AJAX') && DOING_AJAX ) || wp_is_post_autosave($post) || wp_is_post_revision($post) || 'auto-draft' == $post->post_status )
			return;
		
		if ( isset($_POST[self::NOTIFY_META]) ) {
			update_post_meta( $post_id, self::NOTIFY_META, $_POST[self::NOTIFY_META] );
		}

		$this->send_notification( $post_id );
	}
	
	private function send_notification( $post_id = null, $comment_id = 0 ) {
		$post_id = ( $post_id ) ? $post_id : $GLOBALS['post']->ID;
		$post = get_post($post_id);
		
		$notification_type = ( $comment_id ) ? 'comment' : 'post';
		$notification_nicename = ( 'comment' == $notification_type ) ? 'Ticket Updated' : 'New Ticket';
		if ( $comment_id ) {
			$comment = get_comment($comment_id);
			$cbody = strip_tags($comment->comment_content);
			$cauthor = $comment->comment_author;
		}
		
		$user_ids = get_post_meta($post_id, self::NOTIFY_META, true);
		if ( empty($user_ids) )
			return;
	
		$messages = array(
			'comment' => sprintf( "%s has updated Ticket #%s ‘%s’: \n\n%s", $cauthor, $post_id, $post->post_title, $cbody ),
			'post' => 
			sprintf( "%s has created Ticket #%s ‘%s’: \n\n%s", get_the_author_meta('user_nicename', $post->post_author ), $post_id, $post->post_title, strip_tags($post->post_content) )
		);

		
		$msg = $messages[$notification_type] . "\n\n____\n\n" . sprintf( 'View this ticket: %s', get_permalink($post_id) );
		
		foreach ( $user_ids as $uid )
			$to_arr[] = get_the_author_meta( 'user_email', $uid );
		
		$to = implode(', ', $to_arr);
		$subject = sprintf( '[%s] %s: %s', get_bloginfo('name'), $notification_nicename, $post->post_title );
		
		$reply_to = ( 'comment' == $notification_type ) ? $cauthor : get_the_author_meta('user_email', $post->post_author);
		$headers = "Reply-To: {$reply_to}\r\n";
		
		if ( wp_mail($to, $subject, $msg, $headers) ) {
			$this->disable_default_notifications();
		}
		else {
			// error?
		}
	}
	
	private function disable_default_notifications() {
		add_filter( 'option_comments_notify', '__return_false');
	}
	
	public function print_assign_notification() {
		global $post;
		
		$notifyees = array();
		// make sure we're on an existing ticket before trying to grab current notifications
		if ( 'ticket' == $post->post_type )
			$notifyees = (array) get_post_meta( $post->ID, self::NOTIFY_META, true);
		
		$users = get_users_of_blog();
		
		if ( empty($users) )
			return;
		
		foreach ( $users as $user ) {
			$checked = ( in_array( $user->user_id, $notifyees ) ) ? ' checked="checked" ' : '';
			$ret .= '<label><input type="checkbox" value="'. $user->user_id .'" name="' . self::NOTIFY_META . '[]" '. $checked .' /> '. $user->display_name .'</label>';
			
		}
		
		$ret = '<p id="sd-ticket-notify"><label>Notifications: <em>These users will be notified of this ticket and its updates</em></label><span>'. $ret .'</span></p>';
		
		echo $ret;
	}
}

new SD_Ticket_Notify;