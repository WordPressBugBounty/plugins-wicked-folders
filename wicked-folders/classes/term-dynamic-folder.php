<?php

namespace Wicked_Folders;

use Wicked_Folders;

// Disable direct load
defined( 'ABSPATH' ) || exit;

/**
 * Represents a dynamically-generated term folder.
 */
class Term_Dynamic_Folder extends Dynamic_Folder {

    public $term_id = false;

    public function __construct( $args = array() ) {
        parent::__construct( $args );  
    }

    public function pre_get_posts( $query ) {
        $this->parse_id();

        if ( $this->taxonomy && $this->term_id ) {
            $tax_query = ( array ) $query->get( 'tax_query' );
            $tax_query[] = array(
                'taxonomy'  => $this->taxonomy,
                'terms'     => $this->term_id,
            );
            $query->set( 'tax_query', $tax_query );
        }
    }

    /**
     * Parses the folder's ID to determine the taxonomy and term ID to filter by.
     * ID's are in the format dynamic_term_{taxonomy} for the parent taxonomy folders
     * and dynamic_term_{taxonomy}__id__{term_id} for child term folders.
     */
    private function parse_id() {
        $id = substr( $this->id, 13 );

        if ( false === strpos( $id, '__id__' ) ) {
            $this->taxonomy = $id;
        } else {
            $id = explode( '__id__', $id );

            if ( isset( $id[0] ) ) $this->taxonomy = $id[0];
            if ( isset( $id[1] ) ) $this->term_id  = $id[1];
        }
    }

    public function jsonSerialize(): array {
        $data = parent::jsonSerialize();

        $data['termId'] = $this->term_id;

        return $data;
    }

    public function get_child_folders() {
        $this->parse_id();

        $folders    = new Folder_Collection();
        $post_type  = $this->post_type;
        $taxonomy   = $this->taxonomy;

        // Nothing to do if we don't have a taxonomy
        if ( ! $taxonomy ) {
            return $folders;
        }

        $term_query = array(
            'taxonomy'     => $taxonomy,
            'hide_empty'   => false,
            'parent'       => 0,
        );

        if ( $this->term_id ) {
            $term_query['parent'] = $this->term_id;
        }

        $terms = get_terms( $term_query );

        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            foreach ( $terms as $index => $term ) {
                $parent = 'dynamic_term_' . $taxonomy;

                if ( $term->parent ) {
                    $parent .=  '__id__' . $term->parent;
                }       

                $folders->add( new Term_Dynamic_Folder( array(
                    'id'            => 'dynamic_term_' . $taxonomy . '__id__' . $term->term_id,
                    'name'          => $term->name,
                    'parent'        => $parent,
                    'post_type'     => $post_type,
                    'taxonomy'      => $taxonomy,
                    'term_id'       => $term->term_id,
                    'assignable' 	=> 'attachment' == $post_type ? false : true,
                    'order' 		=> $index,   
                    'lazy'          => true,                 
                ) ) );
            }
        }

        return $folders;
    }

    public function get_ancestors() {
        $this->parse_id();

        $ancestors  = new Folder_Collection();
        $post_type  = $this->post_type;
        $taxonomy   = $this->taxonomy;

        if ( $this->term_id ) {
            $ancestor_ids = get_ancestors( $this->term_id, $taxonomy, 'taxonomy' );

            if ( ! empty( $ancestor_ids ) ) {
                $terms = get_terms( array(
                    'taxonomy'      => $taxonomy,
                    'hide_empty'    => false,
                    'include'       => $ancestor_ids,
                ) );

                if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                    foreach ( $terms as $term ) {
                        $parent = 'dynamic_term_' . $taxonomy;

                        if ( $term->parent ) {
                            $parent .= '__id__' . $term->parent;
                        }

                        $ancestors->add( new Term_Dynamic_Folder( array(
                            'id'        => 'dynamic_term_' . $taxonomy . '__id__' . $term->term_id,
                            'name'      => $term->name,
                            'parent'    => $parent,
                            'post_type' => $post_type,
                            'taxonomy'  => $taxonomy,
                            'assignable' => 'attachment' == $post_type ? false : true,
                            'term_id'   => $term->term_id,
                            'lazy'      => true,
                        ) ) );
                    }
                }
            }
        }

        return $ancestors;
    }
    
    public function determine_parent() {
        $this->parse_id();

        if ( $this->term_id ) {
            $term = get_term( $this->term_id, $this->taxonomy );

            if ( ! is_wp_error( $term ) ) {
                if ( $term->parent ) {
                    $this->parent = 'dynamic_term_' . $this->taxonomy . '__id__' . $term->parent;
                } else {
                    $this->parent = 'dynamic_term_' . $this->taxonomy;
                }
            }
        } elseif ( $this->taxonomy ) {
            $this->parent = 'dynamic_root_' . $this->taxonomy;
        } else {
            $this->parent = 'dynamic_root';
        }
    }    
}
