<?php

namespace Wicked_Folders;

// Disable direct load
defined( 'ABSPATH' ) || exit;

/**
 * Holds a collection of Author_Dynamic folders.
 */
class Author_Dynamic_Folder_Collection extends Dynamic_Folder_Collection {

    public $lazy = true;

    public static $folder_class = Author_Dynamic_Folder::class;

    public function fetch() {
        // We already have logic to fetch child folders so create a root folder
        // and fetch it's children
        $folder = new Author_Dynamic_Folder( array(
            'post_type' => $this->post_type,
            'lazy'      => $this->lazy,
        ) );

        $folders = $folder->get_child_folders();

        foreach ( $folders as $folder ) {
            $folder->lazy = $this->lazy;

            $this->add( $folder );
        }        
    }
}
