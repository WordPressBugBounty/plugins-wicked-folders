<?php

namespace Wicked_Folders\Integrations\Elementor;

use Wicked_Folders;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) die( '-1' );

class Integrator {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_elementor_style_tweaks' ) );
    }

    public function maybe_enqueue_elementor_style_tweaks() {
        // No need to tweak styles if folders aren't enabled for Elementor templates
        if ( ! Wicked_Folders::enabled_for( 'elementor_library' ) ) {
            return;
        }

        // Make sure we're on the Elementor saved templates screen
        if ( ! ( isset( $_GET['post_type'] ) && 'elementor_library' === $_GET['post_type'] && isset( $_GET['tabs_group']) && 'library' === $_GET['tabs_group'] ) ) {
            return;
        }

        $version = Wicked_Folders::plugin_version();

		wp_enqueue_style( 'wicked-folders-elementor', plugin_dir_url( dirname( __FILE__ ) ) . 'elementor/style.css', array(), $version );            
    }

}