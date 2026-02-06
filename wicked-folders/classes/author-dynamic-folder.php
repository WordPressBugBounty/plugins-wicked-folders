<?php

namespace Wicked_Folders;

use Wicked_Folders;

// Disable direct load
defined( 'ABSPATH' ) || exit;

/**
 * Represents a dynamically-generated author folder.
 */
class Author_Dynamic_Folder extends Dynamic_Folder {

    private $user_id = false;

    public function __construct( $args = array() ) {
        parent::__construct( wp_parse_args( $args, array(
            'id'        => 'dynamic_author',
            'parent'    => 'dynamic_root',
            'name'      => __( 'All Authors', 'wicked-folders' ),
            'lazy'      => true,
        ) ) );
    }

    public function pre_get_posts( $query ) {
        $this->parse_id();

        if ( $this->user_id ) {
            $query->set( 'author', $this->user_id );
        }
    }

    /**
     * Parses the folder's ID to determine author ID that the folder should
     * filter by.
     */
    private function parse_id() {
        $this->user_id = ( int ) substr( $this->id, 15 );
    }

    public function get_child_folders() {
		global $wpdb;

		$folders                = new Folder_Collection();
        $taxonomy               = Wicked_Folders::get_tax_name( $this->post_type );
		$can_view_others_items  = apply_filters( 'wicked_folders_can_view_others_items', true, get_current_user_id(), $taxonomy );

		// Fetch authors
		$results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT
                    u.ID, u.display_name
                FROM
                    {$wpdb->posts} p
                INNER JOIN
                    {$wpdb->users} u
                ON
                    p.post_author = u.ID
                AND
                    post_status
                NOT IN
                    ('trash', 'auto-draft')
                WHERE
                    post_type = %s
                ORDER BY
                    u.display_name ASC", $this->post_type
            )
        );

		foreach ( $results as $row ) {
			// Skip other authors when the user doesn't have permission to view other's items
			if ( ! $can_view_others_items && get_current_user_id() != $row->ID ) continue;
			
			$folders->add( new Author_Dynamic_Folder( array(
                'id' 		=> 'dynamic_author_' . $row->ID,
                'name' 		=> $row->display_name,
                'parent' 	=> 'dynamic_author',
                'post_type' => $this->post_type,
                'taxonomy' 	=> $taxonomy,
            ) ) );
		}

		return $folders;        
    }
}
