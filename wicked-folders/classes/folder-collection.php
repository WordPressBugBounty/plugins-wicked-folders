<?php

namespace Wicked_Folders;

use Exception;
use DirectoryIterator;
use Wicked_Folders;
use JsonSerializable;

// Disable direct load
defined( 'ABSPATH' ) || exit;

/**
 * Holds a collection of folders.
 */
class Folder_Collection extends Collection implements JsonSerializable {

    const SORT_MODE_ALPHABETICAL 	= 'alpha';
	const SORT_MODE_CUSTOM 			= 'custom';

    public $post_type = false;

    public function __construct( $post_type = false ) {
        if ( $post_type ) {
            $this->post_type = $post_type;
            
            $this->fetch();
        }
    }

    /**
     * Add a folder.
     *
     * @param Folder
     *  The folder to add.
     */
    public function add( $item ) {
        $this->add_if( $item, 'Wicked_Folders\Folder' );
    }

    /**
     * Get a folder by ID.
     *
     * @param string $id
     *  The ID of the folder to get.
     *
     * @return Folder|bool
     *  The folder object or false if not found.
     */
    public function get( $id ) {
        foreach ( $this->items as $folder ) {
            if ( $folder->id == $id ) {
                return $folder;
            }
        }

        return false;
    }

    /**
     * Remove a folder.
     * 
     * @param Folder
     *  The folder to remove.
     */
    public function remove( $item ) {
        foreach ( $this->items as $index => $folder ) {
            if ( $folder->id === $item->id ) {
                unset( $this->items[ $index ] );

                break;
            }
        }
    }

    /**
     * Deletes the specified folders and removes them from the collection.
     * 
     * @param array $ids
     *  The IDs of the folders to delete.
     * 
     * @return Folder_Collection
     *  Folders that were changed (i.e. assigned to a new parent).
     */
    public function delete( $ids ) {
        $changed = new Folder_Collection();

        // Assign new parents before deleting
        foreach ( $this->items as $folder ) {
            if ( in_array( $folder->id, $ids ) ) {
                $new_parent = '0';

                // Assign a new parent to the folder's children
                $children = $this->get_children( $folder );
                
                // Get the folder's ancestors...
                $ancestors = $this->get_ancestors( $folder );

                // ...and find the first ancestor that is not being deleted
                if ( ! empty( $ancestors ) ) {
                    foreach ( $ancestors as $ancestor ) {
                        if ( ! in_array( $ancestor->id, $ids ) ) {
                            $new_parent = $ancestor->id;

                            break;
                        }
                    }
                }

                // Assign the new parent to the folder's children
                foreach ( $children as $child ) {
                    // Nothing to do if the child is being deleted too
                    if ( ! in_array( $child->id, $ids ) ) {
                        $child->parent = $new_parent;

                        $changed->add( $child );
                    }
                }
            }
        }

        // Now we can delete
        foreach ( $this->items as $folder ) {
            if ( in_array( $folder->id, $ids ) ) {
                $folder->delete();

                $this->remove( $folder );
            }
        }
        
        return $changed;
    }

    /**
     * Get the children of a folder.
     * 
     * @param Folder $folder
     *  The folder to get the children of.
     * 
     * @return Folder_Collection
     *  The folder's children.
     */
    public function get_children( $folder ) {
        $children = new Folder_Collection();

        foreach ( $this->items as $child ) {
            if ( $child->parent == $folder->id ) {
                $children->add( $child );
            }
        }

        return $children;
    }

    /**
     * Get the ancestors of a folder.
     * 
     * @param Folder $folder
     *  The folder to get the ancestors of.
     * 
     * @return Folder_Collection
     *  The folder's ancestors.
     */
    public function get_ancestors( $folder ) {
        $ancestors = new Folder_Collection();

        // Get the folder's parent
        $parent = $this->get( $folder->parent );

        if ( $parent ) {
            $ancestors->add( $parent );

            $parent_ancestors = $this->get_ancestors( $parent );

            // Add parent's ancestors
            foreach ( $parent_ancestors as $ancestor ) {
                $ancestors->add( $ancestor );
            }
        }

        return $ancestors;
    }

    /**
     * Saves all folders in the collection.
     */
    public function save() {
        foreach ( $this->items as $folder ) {
            $folder->save();
        }
    }

    public function sort( $mode = self::SORT_MODE_CUSTOM ) {
        if ( self::SORT_MODE_ALPHABETICAL == $mode ) {
            $this->sort_by_name();
        } else {
            $this->sort_by_order();
        }
    }

    /**
     * Sorts folders by name.  Note: for now, root level folders
     * are sorted by their order.
     */
    public function sort_by_name() {
        usort( $this->items, function( $a, $b ) {
            $a_order = intval( $a->order );
            $b_order = intval( $b->order );
            $a_name = strtoupper( $a->name );
            $b_name = strtoupper( $b->name );
            $a_parent = $a->parent;
            $b_parent = $b->parent;
        
            // Always sort root folders by their sort order
            if ( 'root' == $a_parent || 'root' == $b_parent ) {
                if ( 'root' == $a_parent && 'root' == $b_parent ) {
                    return $a_order < $b_order ? -1 : 1;
                }
        
                if ( 'root' == $a_parent ) {
                    $b_order = 1;
                }
        
                if ( 'root' == $b_parent ) {
                    $a_order = 1;
                }
        
                return $a_order < $b_order ? -1 : 1;
            }
        
            if ( $a_name == $b_name ) return 0;
            
            return $a_name < $b_name ? -1 : 1;
        } );
    }

    /**
     * Sort folders by their order.
     */
    public function sort_by_order() {
        usort( $this->items, function( $a, $b ) {
            $a_order = intval( $a->order );
            $b_order = intval( $b->order );
            $a_name = strtoupper( $a->name );
            $b_name = strtoupper( $b->name );
            $a_parent = $a->parent;
            $b_parent = $b->parent;
        
            // If the order is the same for both folders, sort by name
            if ( $a_order == $b_order ) {
                if ( $a_name == $b_name ) return 0;

                return $a_name < $b_name ? -1 : 1;
            }

            return $a_order < $b_order ? -1 : 1;
        } );
    }

	public function jsonSerialize(): array {
		$json = array();

		foreach ( $this->items as $folder ) {
			$json[] = $folder->jsonSerialize();
		}

		return $json;
	}

    /**
     * Fetch folders from the database.
     */
    public function fetch() {
        if ( ! $this->post_type ) {
            throw new Exception(
                __(
                    'Post type is required to fetch folders.',
                    'wicked-folders'
                )
            );
        }

        $taxonomy = Wicked_Folders::get_tax_name( $this->post_type );

		if ( version_compare( get_bloginfo( 'version' ), '4.5.0', '<' ) ) {
			$terms = get_terms( $taxonomy, array(
				'hide_empty' 	=> false,
			) );
		} else {
			$terms = get_terms( array(
				'taxonomy' 		=> $taxonomy,
				'hide_empty' 	=> false,
				// Polylang will only show folders for current language
				// without the following. Show all folders regardless of
				// language
				'lang' 			=> '',
			) );
		}

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_order = ( int ) get_term_meta( $term->term_id, 'wf_order', true );
				$owner_id 	= ( int ) get_term_meta( $term->term_id, 'wf_owner_id', true );
				$owner_data = get_userdata( $owner_id );

				$this->add( new Term_Folder( array(
					'id' 			=> $term->term_id,
					'name' 			=> $term->name,
					'parent' 		=> $term->parent,
					'post_type' 	=> $this->post_type,
					'taxonomy' 		=> $taxonomy,
					'order' 		=> $term_order,
					'owner_id' 		=> $owner_id,
					'owner_name' 	=> isset( $owner_data->data->display_name ) ? $owner_data->data->display_name : '',
                    'color'         => get_term_meta( $term->term_id, 'wf_color', true ),
				) ) );
			}
		}        
    }

    /**
     * Fetches item counts.
     */
    public function fetch_item_counts() {
        global $wpdb;

        if ( ! $this->post_type ) {
            throw new Exception(
                __(
                    'Post type is required to fetch item counts.',
                    'wicked-folders'
                )
            );
        }

        $post_type 	= $this->post_type;
        $taxonomy 	= Wicked_Folders::get_tax_name( $post_type );

        $count_query = $wpdb->prepare( "
            SELECT
                tt.term_id, COUNT(tr.object_id) AS n
            FROM
                {$wpdb->term_relationships} AS tr
            INNER JOIN
                {$wpdb->term_taxonomy} AS tt
            ON
                tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN
                {$wpdb->posts} p
            ON
                tr.object_id = p.ID
            WHERE
                tt.taxonomy = %s
            AND
                p.post_status NOT IN ('trash', 'auto-draft')
            GROUP BY
                tr.term_taxonomy_id
        ", $taxonomy );

        $assigned_count_query = $wpdb->prepare( "
            SELECT
                COUNT(DISTINCT(p.ID)) AS n, tt.taxonomy
            FROM
                {$wpdb->posts} AS p
            INNER JOIN
                {$wpdb->term_relationships} AS tr
            ON
                p.ID = tr.object_id
            INNER JOIN
                {$wpdb->term_taxonomy} AS tt
            ON
                tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE
                p.post_type = %s
            AND
                p.post_status NOT IN ('trash', 'auto-draft')
            AND
                tt.taxonomy = %s
            GROUP BY
                tt.taxonomy
        ", $post_type, $taxonomy );

        $total_count_query = $wpdb->prepare( "
            SELECT
                COUNT(p.ID) AS n
            FROM
                {$wpdb->posts} AS p
            WHERE
                p.post_type = %s
            AND
                p.post_status NOT IN ('trash', 'auto-draft')
        ", $post_type );

        $count_query            = apply_filters( 'Wicked_Folders\Folder_Collection\fetch_item_counts\count_query', $count_query, $this );
        $assigned_count_query   = apply_filters( 'Wicked_Folders\Folder_Collection\fetch_item_counts\assigned_count_query', $assigned_count_query, $this );
        $total_count_query      = apply_filters( 'Wicked_Folders\Folder_Collection\fetch_item_counts\total_count_query', $total_count_query, $this );

        $counts 		        = $wpdb->get_results( $count_query, OBJECT_K );
        $assigned_count         = ( int ) $wpdb->get_var( $assigned_count_query );
        $total_count 	        = ( int ) $wpdb->get_var( $total_count_query );	

        $assigned_count         = apply_filters( "wicked_folders_{$post_type}_assigned_count", $assigned_count );
        $total_count 	        = apply_filters( "wicked_folders_{$post_type}_total_count", $total_count );

        foreach ( $this->items as $folder ) {
            if ( '0' === $folder->id ) {
                $folder->item_count = $total_count;
                
                continue;
            }

            if ( 'unassigned_dynamic_folder' == $folder->id ) {
                $folder->item_count = $total_count - $assigned_count;

                continue;
            }

            if ( isset( $counts[ ( int ) $folder->id ] ) ) {
                $folder->item_count = $counts[ ( int ) $folder->id ]->n;
            }
        }
    }
}
