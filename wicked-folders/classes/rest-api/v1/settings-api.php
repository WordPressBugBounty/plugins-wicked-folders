<?php

namespace Wicked_Folders\REST_API\v1;

use WP_REST_Server;
use Wicked_Folders\Settings;

// Disable direct load
defined( 'ABSPATH' ) || exit;

class Settings_API extends REST_API {

    public function __construct() {
        $this->register_routes();
    }

    public function register_routes() {
        $args = array(
            'context' => array(
                'type'      => 'string',
                'enum'      => array( 'site', 'network' ),
                'default'   => 'site',
            ),
        );

        register_rest_route( $this->base, '/settings', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => $args,
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_item' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array_merge( $args, array(
                    'values' => array(
                        'type'      => 'object',
                        'required'  => true,
                    ),
                ) ),
            ),
        ) );
    }

    public function permissions_check( $request ) {
        return current_user_can( Settings::get_capability( $request['context'] ) );
    }

    /**
     * Returns the settings schema and values.
     */
    public function get_item( $request ) {
        $settings = Settings::get_instance();

        return rest_ensure_response( array(
            'schema' => $settings->get_schema( $request['context'] ),
            'values' => $settings->get_values( $request['context'] ),
        ) );
    }

    /**
     * Saves settings and returns the updated values.
     */
    public function update_item( $request ) {
        $settings = Settings::get_instance();

        $settings->save_values( ( array ) $request['values'], $request['context'] );

        return rest_ensure_response( array(
            'values' => $settings->get_values( $request['context'] ),
        ) );
    }
}
