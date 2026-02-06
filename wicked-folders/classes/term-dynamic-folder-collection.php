<?php

namespace Wicked_Folders;

use Wicked_Folders;
use DateTimeZone;
use DateTime;

// Disable direct load
defined( 'ABSPATH' ) || exit;

/**
 * Holds a collection of Term_Dynamic_Folder folders.
 */
class Term_Dynamic_Folder_Collection extends Dynamic_Folder_Collection {

    public static $folder_class = Term_Dynamic_Folder::class;

    /**
     * Adds a dynamic folder for each taxonomy registered for the post type
     * to the collection. 
     */
    public static function add_to_collection( $collection ) {
        $lazy = apply_filters(
            'wicked_folders_enable_lazy_dynamic_folders',
            get_option( 'wicked_folders_enable_lazy_dynamic_folders', true ),
            static::$folder_class,
            $collection->post_type
        );

        $term_folders               = new Term_Dynamic_Folder_Collection();
        $term_folders->post_type    = $collection->post_type;
        $term_folders->lazy         = $lazy;

        $term_folders->fetch();

        foreach ( $term_folders as $folder ) {
            $collection->add( $folder );
        }
    }

    public function fetch() {
        $post_type 	= $this->post_type;
        $taxonomy 	= Wicked_Folders::get_tax_name( $post_type );
        $taxonomies = $this->get_taxonomies();

        if ( is_array( $taxonomies ) ) {
            foreach ( $taxonomies as $taxonomy ) {
                // Add root folder for taxonomy
                $root_folder = new Term_Dynamic_Folder( array(
                    'id' 		=> 'dynamic_term_' . $taxonomy->name,
                    'name' 		=> $taxonomy->labels->name,
                    'parent' 	=> 'dynamic_root',
                    'post_type' => $post_type,
                    'taxonomy' 	=> $taxonomy->name,
                    'lazy'      => $this->lazy,
                ) );

                $this->add( $root_folder );

                // Only fetch each taxonomy's terms when lazy loading is disabled
                if ( ! $this->lazy ) {
                    $terms = get_terms( array(
                        'taxonomy' 		=> $taxonomy->name,
                        'hide_empty' 	=> false,
                    ) );

                    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                        foreach ( $terms as $index => $term ) {
                            $id 	= 'dynamic_term_' . $taxonomy->name . '__id__' . $term->term_id;
                            $parent = 'dynamic_term_' . $taxonomy->name;

                            if ( $term->parent ) {
                                $parent .=  '__id__' . $term->parent;
                            }

                            $this->add( new Term_Dynamic_Folder( array(
                                'id' 			=> $id,
                                'name' 			=> $term->name,
                                'parent' 		=> $parent,
                                'post_type' 	=> $post_type,
                                'taxonomy' 		=> $taxonomy->name,
                                'term_id' 		=> $term->term_id,
                                'assignable' 	=> 'attachment' == $post_type ? false : true,
                                'order' 		=> $index,
                                'lazy'      	=> $this->lazy,
                            ) ) );
                        }
                    } else {
                        // Remove the root folder if there are no terms
                        $this->remove( $root_folder );
                    }
                }
            }
        }        
    }

    private function get_taxonomies() {
        $post_type  = $this->post_type;
        $taxonomy   = Wicked_Folders::get_tax_name( $post_type );
        /*
        // get_taxonomies only returns taxonomies that match the query exactly
        // meaning that it will omit any taxonomies that are assigned to multiple
        // post types (since we're only passing one post type to the object_type
        // filter). Therefore, use get_object_taxonomies instead and filter out
        // taxonomies we don't want
        $taxonomies 		= get_taxonomies( array(
            'object_type' 	=> array( $post_type ),
            'hierarchical' 	=> true,
            'show_ui' 		=> true,
        ), 'objects' );
        */
        $taxonomies = get_object_taxonomies( $post_type, 'objects' );
        $taxonomies = wp_filter_object_list( $taxonomies, array(
            'hierarchical' 	=> true,
            'show_ui' 		=> true,
        ) );

        // Remove folders taxonomy
        unset( $taxonomies[ $taxonomy ] );

        return $taxonomies;
    }
}