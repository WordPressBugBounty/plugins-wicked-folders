<?php

namespace Wicked_Folders\REST_API\v1;

use WP_Error;
use Exception;
use stdClass;
use WP_Post;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Controller;
use Wicked_Folders;
use Wicked_Folders\Folder;
use Wicked_Folders\Term_Folder;
use Wicked_Folders\All_Folders_Collection;
use Wicked_Folders\Folder_Collection;
use Wicked_Folders\Folder_Factory;

// Disable direct load
defined( 'ABSPATH' ) || exit;

class Folder_API extends REST_API {

    public function __construct() {
        $this->register_routes();
    }

    public function register_routes() {
        register_rest_route( $this->base, '/folders', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_folder' ),
                'permission_callback' => array( $this, 'create_folder_permissions_check' ),
                'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),				
            ),	
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_folders' ),
                'permission_callback' => array( $this, 'get_folders_permissions_check' ),
                'args'                => array(
                    'post_type' => array(
                        'required' => true,
                    ),
                    'include_item_counts' => array(
                        'required' 	=> false,
                        'default' 	=> false,
                    ),					
                    'sort_mode' => array(
                        'required' 	=> false,
                        'default' 	=> Folder_Collection::SORT_MODE_CUSTOM,
                    ),						
                ),			
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_folders' ),
                'permission_callback' => array( $this, 'delete_folders_permissions_check' ),
                'args'                => array(
                    'post_type' => array(
                        'required' => true,
                    ),		
                    'folder_ids' => array(
                        'required' 	=> true,
                        'default' 	=> array(),
                    ),	
                    'include_item_counts' => array(
                        'required' 	=> false,
                        'default' 	=> false,
                    ),	                    
                    'sort_mode' => array(
                        'required' 	=> false,
                        'default' 	=> Folder_Collection::SORT_MODE_CUSTOM,
                    ),												
                ),				
            ),										       
        ) );

        register_rest_route( $this->base, '/folders/all', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_all_folders' ),
                'permission_callback' => array( $this, 'get_all_folders_permissions_check' ),
                'args'                => array(
                    'post_type' => array(
                        'required' => true,
                    ),
                    'include_item_counts' => array(
                        'required' 	=> false,
                        'default' 	=> false,
                    ),					
                    'sort_mode' => array(
                        'required' 	=> false,
                        'default' 	=> Folder_Collection::SORT_MODE_CUSTOM,
                    ),						
                ),			
            ),										       
        ) );

        register_rest_route( $this->base, '/folders/unassign', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'unassign_folders' ),
                'permission_callback' => array( $this, 'unassign_folders_permissions_check' ),
                'args'                => array(
                    'post_type' => array(
                        'required' => true,
                    ),		
                    'post_ids' => array(
                        'required' 	=> true,
                        'default' 	=> array(),
                    ),	
                    'include_item_counts' => array(
                        'required' 	=> false,
                        'default' 	=> false,
                    ),					
                    'sort_mode' => array(
                        'required' 	=> false,
                        'default' 	=> Folder_Collection::SORT_MODE_CUSTOM,
                    ),												
                ),
            ),				       
        ) );

        register_rest_route( $this->base, '/folders/sort', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'sort_folders' ),
                'permission_callback' => array( $this, 'sort_folders_permissions_check' ),
                'args'                => array(
                    'folder_ids' => array(
                        'required' 	=> true,
                        'default' 	=> array(),
                    ),													
                ),				
            ),				       
        ) );

        register_rest_route( $this->base, '/folders/(?P<id>[\d]+)', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'clone_folder' ),
                'permission_callback' => array( $this, 'clone_folder_permissions_check' ),
                'args'                => array(
                    'post_type' => array(
                        'required' => true,
                    ),
                    'clone_children' => array(
                        'required' 	=> true,
                        'default' 	=> false,
                    ),					
                ),
            ),				
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_folder' ),
                'permission_callback' => array( $this, 'update_folder_permissions_check' ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_folder' ),
                'permission_callback' => array( $this, 'delete_folder_permissions_check' ),
            ),
        ) );

        register_rest_route( $this->base, '/folders/(?P<id>[\d]+)/assign', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'assign_items' ),
                'permission_callback' => array( $this, 'assign_items_permissions_check' ),
                'args'                => array(
                    'post_type' => array(
                        'required' => true,
                    ),
                    'post_ids' => array(
                        'required' 	=> true,
                        'default' 	=> array(),
                    ),
                    'from_folder_id' => array(
                        'default' 	=> false,
                    ),	
                    'copy' => array(
                        'default' 	=> false,
                    ),
                    'include_item_counts' => array(
                        'required' 	=> false,
                        'default' 	=> false,
                    ),
                    'sort_mode' => array(
                        'required' 	=> false,
                        'default' 	=> Folder_Collection::SORT_MODE_CUSTOM,
                    ),
                ),
            ),				
        ) );

        register_rest_route( $this->base, '/folders/(?P<id>[^/]+)/children', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_child_folders' ),
                'permission_callback' => array( $this, 'get_child_folders_permissions_check' ),
                'args'                => array(
                    'post_type' => array(
                        'required' => true,
                    ),							
                ),				
            ),				
        ) );

        register_rest_route( $this->base, '/folders/(?P<id>[^/]+)/ancestors', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_ancestor_folders' ),
                'permission_callback' => array( $this, 'get_ancestor_folders_permissions_check' ),
                'args'                => array(
                    'post_type' => array(
                        'required' => true,
                    ),
                    'include_siblings' => array(
                        'required' 	=> false,
                        'default' 	=> true,
                    ),											
                ),				
            ),				
        ) );	
        
        register_rest_route( $this->base, '/folders/(?P<id>[^/]+)/item-count', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_item_count' ),
                'permission_callback' => array( $this, 'get_item_count_permissions_check' ),
                'args'                => array(
                    'post_type' => array(
                        'required' => true,
                    ),							
                ),				
            ),				
        ) );   
        
        register_rest_route( $this->base, '/folders/(?P<id>[\d]+)/empty', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'empty_folder' ),
                'permission_callback' => array( $this, 'empty_folder_permissions_check' ),
                'args'                => array(
                    'post_type' => array(
                        'required' => true,
                    ),					
                ),
            ),				
        ) );        
    }

    public function create_folder_permissions_check( $request ) {
        $allowed 	= current_user_can( 'edit_posts' );
        $post_type  = $request->get_param( 'postType' );
        $taxonomy   = Wicked_Folders::get_tax_name( $post_type );
        $user_id 	= get_current_user_id();

        return apply_filters( 'wicked_folders_can_create_folders', $allowed, $user_id, $taxonomy );
    }

    public function clone_folder_permissions_check( $request ) {
        return current_user_can( 'edit_posts' );
    }

    public function empty_folder_permissions_check( $request ) {
        return current_user_can( 'edit_posts' );
    }

    public function get_folders_permissions_check( $request ) {
        return current_user_can( 'edit_posts' );
    }

    public function get_all_folders_permissions_check( $request ) {
        return current_user_can( 'edit_posts' );
    }

    public function update_folder_permissions_check( $request ) {
        $allowed 	= current_user_can( 'edit_posts' );
        $user_id 	= get_current_user_id();
        $term_id    = $request->get_param( 'id' );
        $post_type  = $request->get_param( 'postType' );
        $taxonomy   = Wicked_Folders::get_tax_name( $post_type );

        return apply_filters( 'wicked_folders_can_edit_folder', $allowed, $user_id, $term_id, $taxonomy );
    }

    public function delete_folder_permissions_check( $request ) {
        $allowed 	= current_user_can( 'edit_posts' );
        $user_id 	= get_current_user_id();
        $term_id    = $request->get_param( 'id' );
        $post_type  = $request->get_param( 'postType' );
        $taxonomy   = Wicked_Folders::get_tax_name( $post_type );

        return apply_filters( 'wicked_folders_can_delete_folder', $allowed, $user_id, $term_id, $taxonomy );
    }

    public function delete_folders_permissions_check( $request ) {
        return current_user_can( 'edit_posts' );
    }

    public function assign_items_permissions_check( $request ) {
        $allowed 	= current_user_can( 'edit_posts' );
        $user_id 	= get_current_user_id();
        $term_id    = $request->get_param( 'id' );
        $post_type  = $request->get_param( 'post_type' );
        $taxonomy   = Wicked_Folders::get_tax_name( $post_type );

        return apply_filters( 'wicked_folders_can_assign_items_to_folder', $allowed, $user_id, $term_id, $taxonomy );
    }	

    public function unassign_folders_permissions_check( $request ) {
        return current_user_can( 'edit_posts' );
    }	

    public function get_child_folders_permissions_check( $request ) {
        return current_user_can( 'edit_posts' );
    }

    public function get_ancestor_folders_permissions_check( $request ) {
        return current_user_can( 'edit_posts' );
    }

    public function get_item_count_permissions_check( $request ) {
        return current_user_can( 'edit_posts' );
    }

    public function sort_folders_permissions_check( $request ) {
        return current_user_can( 'edit_posts' );
    }

    public function create_folder( $request ) {
        try {
            // Decode the JSON ourself since WordPress decodes JSON as an array
            // and we want an object
            $json 	= json_decode( $request->get_body(), false );
            $folder = new Term_Folder();

            $folder->from_json( $json );
            $folder->save();

            return $folder;
        } catch ( Exception $exception ) {
            return new WP_Error(
                'wf_error',
                $exception->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    public function update_folder( $request ) {
        try {
            // Decode the JSON ourself since WordPress decodes JSON as an array
            // and we want an object
            $json 	= json_decode( $request->get_body(), false );
            $folder = new Term_Folder();

            $folder->from_json( $json );
            $folder->save();

            return $folder;
        } catch ( Exception $exception ) {
            return new WP_Error(
                'wf_error',
                $exception->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    public function delete_folder( $request ) {
        try {
            // Decode the JSON ourself since WordPress decodes JSON as an array
            // and we want an object
            $json 	= json_decode( $request->get_body(), false );
            $folder = new Term_Folder();

            $folder->from_json( $json );
            $folder->delete();

            return true;
        } catch ( Exception $exception ) {
            return new WP_Error(
                'wf_error',
                $exception->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    public function delete_folders( $request ) {
        try {
            $post_type              = $request->get_param( 'post_type' );
            $folder_ids             = $request->get_param( 'folder_ids' );
            $sort_mode 	            = $request->get_param( 'sort_mode' );
            $include_item_counts 	= $request->get_param( 'include_item_counts' );
            $folders                = new Folder_Collection( $post_type );

            $folders->delete( $folder_ids )->save();

            $folders->sort( $sort_mode );

            if ( $include_item_counts ) {
                $folders->fetch_item_counts();
            }

            return $folders;
        } catch ( Exception $exception ) {
            return new WP_Error(
                'wf_error',
                $exception->getMessage(),
                array( 'status' => 500 )
            );
        }        
    }

    public function clone_folder( $request ) {
        try {
            $id 			= $request->get_param( 'id' );
            $post_type 		= $request->get_param( 'post_type' );
            $clone_children = $request->get_param( 'clone_children' );
            $clone_children = 'true' == $clone_children ? true : false;
            $folder 		= new Term_Folder();

            $folder->id 		= $id;
            $folder->post_type 	= $post_type;

            $folder->fetch();

            $folders = $folder->clone_folder( $clone_children );

            return $folders;
        } catch ( Exception $exception ) {
            return new WP_Error(
                'wf_error',
                $exception->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    public function empty_folder( $request ) {
        try {
            $id 		= $request->get_param( 'id' );
            $post_type 	= $request->get_param( 'post_type' );
            $folder 	= Folder_Factory::get_folder( $id, $post_type );

            $folder->empty();

            return true;
        } catch ( Exception $exception ) {
            return new WP_Error(
                'wf_error',
                $exception->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Gets all term folders for a given post type.
     */
    public function get_folders( $request ) {
        try {
            $post_type 				= $request->get_param( 'post_type' );
            $sort_mode 				= $request->get_param( 'sort_mode' );
            $include_item_counts 	= $request->get_param( 'include_item_counts' );
            $folders 				= new Folder_Collection( $post_type );

            $folders->sort( $sort_mode );

            if ( $include_item_counts ) {
                $folders->fetch_item_counts();
            }

            return $folders;
        } catch ( Exception $exception ) {
            return new WP_Error(
                'wf_error',
                $exception->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Gets all folders for a post type including the unassigned items folder,
     * the 'All Folders' folder, dynamic folders, and term folders.
     */
    public function get_all_folders( $request ) {
        try {
            $post_type 				= $request->get_param( 'post_type' );
            $sort_mode 				= $request->get_param( 'sort_mode' );
            $include_item_counts 	= $request->get_param( 'include_item_counts' );
            $folders 				= new All_Folders_Collection( $post_type );

            $folders->sort( $sort_mode );

            if ( $include_item_counts ) {
                $folders->fetch_item_counts();
            }

            return $folders;
        } catch ( Exception $exception ) {
            return new WP_Error(
                'wf_error',
                $exception->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    public function assign_items( $request ) {
        try {
            $to_folder_id 			= absint( $request->get_param( 'id' ) );
            $from_folder_id 		= absint( $request->get_param( 'from_folder_id' ) );
            $post_type 				= $request->get_param( 'post_type' );
            $post_ids 				= $request->get_param( 'post_ids' );
            $copy 					= $request->get_param( 'copy' );
            $sort_mode 				= $request->get_param( 'sort_mode' );
            $include_item_counts 	= $request->get_param( 'include_item_counts' );

            // Setting the from folder ID to false prevents the move_object function from unassigning
            // the posts from the folder
            if ( $copy ) $from_folder_id = false;

            foreach ( $post_ids as $id ) {
                Wicked_Folders::move_object( 'post', ( int ) $id, $to_folder_id, $from_folder_id );
            }

            // Folders are used in response to update item counts	
            $folders = new All_Folders_Collection( $post_type );		

            $folders->sort( $sort_mode );

            if ( $include_item_counts ) {
                $folders->fetch_item_counts();
            }

            return $folders;
        } catch ( Exception $exception ) {
            return new WP_Error(
                'wf_error',
                $exception->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    public function unassign_folders( $request ) {
        try {
            $post_type 	= $request->get_param( 'post_type' );
            $post_ids 	= $request->get_param( 'post_ids' );
            $include_item_counts 	= $request->get_param( 'include_item_counts' );
            $sort_mode 	= $request->get_param( 'sort_mode' );
            $taxonomy 	= Wicked_Folders::get_tax_name( $post_type );
            $user_id 	= get_current_user_id();

            foreach ( $post_ids as $id ) {
                $folder_ids = wp_get_object_terms( $id, $taxonomy, array( 'fields' => 'ids' ) );

                for ( $i = count( $folder_ids ) - 1; $i > -1; $i-- ) {
                    $allowed = apply_filters( 'can_assign_items_to_folder', true, $user_id, $folder_ids[ $i ], $taxonomy );

                    // Only unassign folders from the post that the user has permission to assign items to/from
                    if ( $allowed ) {
                        unset( $folder_ids[ $i ] );
                    }
                }

                $update_terms_result = wp_set_object_terms( ( int ) $id, $folder_ids, $taxonomy );
            }

            // Folders are used in response to update item counts
            $folders = new All_Folders_Collection( $post_type );

            $folders->sort( $sort_mode );

            if ( $include_item_counts ) {
                $folders->fetch_item_counts();
            }

            return $folders;
        } catch ( Exception $exception ) {
            return new WP_Error(
                'wf_error',
                $exception->getMessage(),
                array( 'status' => 500 )
            );
        }		
    }

    public function get_child_folders( $request ) {
        $id 		= $request->get_param( 'id' );
        $post_type 	= $request->get_param( 'post_type' );

        $folder = Folder_Factory::get_folder( $id, $post_type );

        return $folder->get_child_folders();
    }

    public function get_ancestor_folders( $request ) {
        $id 				= $request->get_param( 'id' );
        $post_type 			= $request->get_param( 'post_type' );
        $include_siblings 	= $request->get_param( 'include_siblings' );
        $folder 			= Folder_Factory::get_folder( $id, $post_type );

        $ancestors  = $folder->get_ancestors();
        $children   = $folder->get_child_folders();

        if ( $include_siblings ) {
            $parent 	= Folder_Factory::get_folder( $folder->parent, $post_type );
            $siblings 	= $parent->get_child_folders();

            foreach ( $siblings as $sibling ) {
                $ancestors->add( $sibling );
            }
        }

        foreach ( $children as $child ) {
            $ancestors->add( $child );
        }

        return $ancestors;
    }

    /**
     * Get the item count for a specific folder.
     */
    public function get_item_count( $request ) {
        $id 		= $request->get_param( 'id' );
        $post_type 	= $request->get_param( 'post_type' );
        $folders 	= new All_Folders_Collection( $post_type );

        $folders->fetch_item_counts();

        return $folders->get( $id )->item_count;
    }

    public function sort_folders( $request ) {
        global $wpdb;
        
        $folder_ids = $request->get_param( 'folder_ids' );

        foreach ( $folder_ids as $index => $folder_id ) {
            update_term_meta( ( int ) $folder_id, 'wf_order', $index );

            // Update wp_terms.term_order if the field exists. This field is
            // used by the Category Order and Taxonomy Terms Order plugin so
            // this should ensure that the folders appear in the expected order
            // for users who use this plugin
            if ( Wicked_Folders::get_instance()->term_order_field_exists() ) {
                $wpdb->update(
                    $wpdb->terms,
                    array( 'term_order' => $index ),
                    array( 'term_id' => ( int ) $folder_id ),
                    array( '%d' ),
                    array( '%d' )
                );
            }
        }
        
        return true;
    }
}
