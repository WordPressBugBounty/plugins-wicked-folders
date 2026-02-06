<?php

namespace Wicked_Folders;

// Disable direct load
defined( 'ABSPATH' ) || exit;

/**
 * Dynamic folder that displays items that haven't been assigened to any folders.
 */
class Unassigned_Dynamic_Folder extends Dynamic_Folder {

    private $extension = false;

    public function __construct( $args = array() ) {
        parent::__construct( wp_parse_args( $args, array(
            'id'                => 'unassigned_dynamic_folder',
            'name'              => __( 'Unassigned Items', 'wicked-folders' ),
            'parent'            => apply_filters( 'wicked_folders_unassigned_items_parent', 'root' ),
            'show_item_count'   => true,
            'assignable'        => true,
            'order'             => -10,
        ) ) );

        // $this->show_item_count = true;
        // $this->assignable = true;
    }

    public function pre_get_posts( $query ) {
        $folder_ids = get_terms( $this->taxonomy, array( 'fields' => 'ids', 'hide_empty' => false ) );

        $tax_query = array(
            array(
                'taxonomy' 	=> $this->taxonomy,
                'field' 	=> 'term_id',
                'terms' 	=> $folder_ids,
                'operator' 	=> 'NOT IN',
            ),
        );

        $query->set( 'tax_query', $tax_query );
    }

    public static function add_to_collection( $collection ) {
        $enabled = get_option( 'wicked_folders_show_unassigned_folder', true );

        if ( ! $enabled ) {
            return;
        }

        parent::add_to_collection( $collection );
    }    
}
