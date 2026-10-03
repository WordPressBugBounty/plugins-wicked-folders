<?php

namespace Wicked_Folders\REST_API\v1;

use Exception;
use WP_Error;
use WP_REST_Server;
use Wicked_Folders\Screen_State;

// Disable direct load
defined( 'ABSPATH' ) || exit;

class Screen_State_API extends REST_API {

    public function __construct() {
        $this->register_routes();
    }

    public function register_routes() {
        register_rest_route( $this->base, '/screen-state', array(		
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_item' ),
                'permission_callback' => array( $this, 'update_item_permissions_check' ),
            ),
        ) );
    }

    public function update_item_permissions_check( $request ) {
        return current_user_can( 'edit_posts' );
    }

    public function update_item( $request ) {
        try {
            $json 		= ( array ) $request->get_json_params();
            $screen_id 	= isset( $json['screenId'] ) ? $json['screenId'] : false;

            // Screen state is stored in user meta so it must always be saved
            // for the user making the request; the user ID sent in the request
            // body is ignored to prevent one user from overwriting another
            // user's screen state
            $user_id 	= get_current_user_id();
            $state 		= new Screen_State( $screen_id, $user_id );

            $state->from_json( $json );

            // from_json applies the request's screenId and userId, so restore
            // the values the state was loaded with
            $state->screen_id 	= $screen_id;
            $state->user_id 	= $user_id;

            $state->save();

            return $state;
        } catch ( Exception $exception ) {
            return new WP_Error(
                'wf_error',
                $exception->getMessage(),
                array( 'status' => 500 )
            );
        }
    }
}
