<?php

namespace Wicked_Folders;

use Wicked_Folders;

// Disable direct load
defined( 'ABSPATH' ) || exit;

/**
 * Displays post hierarchy as a folder structure.
 *
 * @since 2.10
 */
class Post_Hierarchy_Dynamic_Folder extends Dynamic_Folder {

    private $post_id = 0;

    public function __construct( $args = array() ) {
        parent::__construct( $args );

        $this->lazy = true;
        $this->show_item_count = false;
    }

    public function pre_get_posts( $query ) {
        $this->parse_id();

        // Get include children setting
        $include_children = Wicked_Folders::include_children( $this->post_type, $this->id );

        // Include items from child folders if include children is enabled
        if ( $include_children ) {
            $ids            = array( $this->post_id );
            $child_folders  = $this->get_child_folders();

            foreach ( $child_folders as $folder ) {
                $folder->parse_id();

                $ids[] = $folder->post_id;
            }

            $query->set( 'post_parent__in', $ids );
        } else {
            // Otherwise, only show direct descendants
            $query->set( 'post_parent', $this->post_id );
        }
    }

    /**
     * Parses the folder's ID to determine the post parent that the folder
     * should filter by.
     */
    private function parse_id() {
        $this->post_id = ( int ) substr( $this->id, 18 );
    }

    public function get_child_folders() {
        global $wpdb;

        $this->parse_id();

        $folders = new Folder_Collection();

        $posts = $wpdb->get_results( $wpdb->prepare( "
            SELECT DISTINCT
                parents.ID, parents.post_title
            FROM
                {$wpdb->posts} AS parents
            INNER JOIN
                {$wpdb->posts} AS children
            ON
                parents.ID = children.post_parent
            WHERE
                parents.post_type = %s
            AND
                parents.post_status IN ('publish', 'future', 'draft', 'pending', 'private')
            AND
                children.post_status IN ('publish', 'future', 'draft', 'pending', 'private')
            AND
                parents.post_parent = %d
            ORDER BY
                parents.menu_order ASC, parents.post_title ASC
        ", $this->post_type, $this->post_id ) );

        foreach ( $posts as $post ) {
            $folders->add( new Post_Hierarchy_Dynamic_Folder( array(
                'id' 		=> 'dynamic_hierarchy_' . $post->ID,
                'name' 		=> $post->post_title,
                'parent' 	=> 'dynamic_hierarchy_' . $this->post_id,
                'post_type' => $this->post_type,
                'taxonomy' 	=> $this->taxonomy,
                'type'      => 'Wicked_Folders\Post_Hierarchy_Dynamic_Folder',
            ) ) );
        }

        return $folders;
    }

    public function get_ancestors() {
        $this->parse_id();

        $ancestors  = new Folder_Collection();
        $post_type  = $this->post_type;
        $taxonomy   = $this->taxonomy;

        if ( $this->post_id ) {
            $ancestor_ids = get_ancestors( $this->post_id, $post_type, 'post_type' );

            if ( ! empty( $ancestor_ids ) ) {
                $posts = get_posts( array(
                    'post_type'         => $post_type,
                    'post_status'       => 'any',
                    'posts_per_page'    => -1,
                    'orderby'           => 'post__in',
                    'post__in'          => $ancestor_ids,
                ) );

                if ( ! is_wp_error( $posts ) && ! empty( $posts ) ) {
                    foreach ( $posts as $post ) {
                        $parent = 'dynamic_hierarchy_0';

                        if ( $post->post_parent ) {
                            $parent = 'dynamic_hierarchy_' . $post->post_parent;
                        }

                        $ancestors->add( new Post_Hierarchy_Dynamic_Folder( array(
                            'id'        => 'dynamic_hierarchy_' . $post->ID,
                            'name'      => $post->post_title,
                            'parent'    => $parent,
                            'post_type' => $post_type,
                            'taxonomy'  => $taxonomy,
                            'lazy'      => true,
                        ) ) );
                    }
                }
            }
        }

        return $ancestors;
    }

    public function fetch() {
        $this->parse_id();

        $post = get_post( $this->post_id );

        if ( $post ) {
            $this->name         = $post->post_title;
            $this->parent       = 'dynamic_hierarchy_' . $post->post_parent;
            $this->post_type    = $post->post_type;
            $this->taxonomy     = Wicked_Folders::get_tax_name( $post->post_type );
            $this->type         = 'Wicked_Folders\Post_Hierarchy_Dynamic_Folder';
        }
    }

    public function get_ancestor_ids( $id = false ) {
        $ancestors = array();

        if ( false === $id ) {
            $id = $this->id;
        }

        if ( 'dynamic_hierarchy_0' != $id ) {
            $post_id            = ( int ) substr( $id, 18 );
            $parent             = 'dynamic_hierarchy_' . wp_get_post_parent_id( $post_id );
            $ancestors[]        = $parent;
            $parent_ancestors   = $this->get_ancestor_ids( $parent );
            $ancestors          = array_merge( $ancestors, $parent_ancestors );
        } else {
            $ancestors[] = 'dynamic_root';
        }

        return $ancestors;
    }

    /**
     * Adds the root hierarchy folder to a collection.
     */
    public static function add_to_collection( $collection ) {
        $post_type = get_post_type_object( $collection->post_type );
        $taxonomy  = Wicked_Folders::get_tax_name( $collection->post_type );

        // Sanity check to make sure we have a post type to work with
        if ( $post_type ) {
            if ( isset( $post_type->hierarchical ) && true == $post_type->hierarchical ) {
                $collection->add( new Post_Hierarchy_Dynamic_Folder( array(
                    'id' 		=> 'dynamic_hierarchy_0',
                    'name' 		=> sprintf( '%1$s %2$s', $post_type->labels->singular_name, __( 'Hierarchy', 'wicked-folders' ) ),
                    'parent' 	=> 'dynamic_root',
                    'post_type' => $post_type->name,
                    'taxonomy' 	=> $taxonomy,
                    'lazy'      => true,
                ) ) );
            }
        }
    }

    public function determine_parent() {
        $this->parse_id();

        if ( $this->post_id ) {
            $post = get_post( $this->post_id );

            if ( ! is_wp_error( $post ) ) {
                if ( $post->post_parent ) {
                    $this->parent = 'dynamic_hierarchy_' . $post->post_parent;
                } else {
                    $this->parent = 'dynamic_hierarchy_0';
                }
            }
        } else {
            $this->parent = 'dynamic_hierarchy_0';
        }
    }      
}
