<?php

namespace Wicked_Folders\Integrations\WPML;

use Wicked_Folders;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) die( '-1' );

class Integrator {

    public function __construct() {
        add_filter( 'Wicked_Folders\Folder_Collection\fetch_item_counts\count_query', 			array( $this, 'maybe_modify_count_query' ), 10, 2 );
        add_filter( 'Wicked_Folders\Folder_Collection\fetch_item_counts\assigned_count_query', 	array( $this, 'maybe_modify_assigned_count_query' ), 10, 2 );
        add_filter( 'Wicked_Folders\Folder_Collection\fetch_item_counts\total_count_query', 	array( $this, 'maybe_modify_total_count_query' ), 10, 2 );
        add_filter( 'wicked_folders_after_ajax_scripts',                                        array( $this, 'add_after_ajax_scripts' ) );
    }

    public function add_after_ajax_scripts( $scripts ) {
        if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
            $scripts[] = plugins_url( 'sitepress-multilingual-cms/res/js/post-edit-languages.js' );
        }

        return $scripts;
    }
    
    /**
     * Modifies the count query to only include items in the current language
     * when WPML is active.
     */
    public function maybe_modify_count_query( $query, $collection ) {
        global $wpdb;

        $wpml_lang 	= false;
        $post_type 	= $collection->post_type;
        $wpml_type 	= "post_{$post_type}";
        $taxonomy 	= Wicked_Folders::get_tax_name( $post_type );

        // Polylang also uses the wpml_current_language filter for some reason.
        // Only use the  filter if WPML is really active
        if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
            $wpml_lang = apply_filters( 'wpml_current_language', false );
        }	
        
        if ( false !== $wpml_lang ) {
            $query = $wpdb->prepare( "SELECT tt.term_id, COUNT(tr.object_id) AS n FROM {$wpdb->term_relationships} AS tr INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID INNER JOIN {$wpdb->prefix}icl_translations translations ON p.ID = translations.element_id WHERE tt.taxonomy = %s AND p.post_status NOT IN ('trash', 'auto-draft') AND translations.language_code = %s AND translations.element_type = %s GROUP BY tr.term_taxonomy_id", $taxonomy, $wpml_lang, $wpml_type );
        }

        return $query;
    }

    /**
     * Modifies the assignedcount query to only include items in the current language
     * when WPML is active.
     */
    public function maybe_modify_assigned_count_query( $query, $collection ) {
        global $wpdb;

        $wpml_lang 	= false;
        $post_type 	= $collection->post_type;
        $wpml_type 	= "post_{$post_type}";
        $taxonomy 	= Wicked_Folders::get_tax_name( $post_type );

        // Polylang also uses the wpml_current_language filter for some reason.
        // Only use the  filter if WPML is really active
        if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
            $wpml_lang = apply_filters( 'wpml_current_language', false );
        }	
        
        if ( false !== $wpml_lang ) {
            $query = $wpdb->prepare( "SELECT COUNT(DISTINCT(p.ID)) AS n, tt.taxonomy FROM {$wpdb->posts} AS p INNER JOIN {$wpdb->term_relationships} AS tr ON p.ID = tr.object_id INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN {$wpdb->prefix}icl_translations translations ON p.ID = translations.element_id WHERE p.post_type = %s AND p.post_status NOT IN ('trash', 'auto-draft') AND tt.taxonomy = %s AND translations.language_code = %s AND translations.element_type = %s GROUP BY tt.taxonomy", $post_type, $taxonomy, $wpml_lang, $wpml_type );
        }

        return $query;
    }

    /**
     * Alters the total count query when WPML is active to only include items
     * in the current language.
     */
    public function maybe_modify_total_count_query( $query, $collection ) {
        global $wpdb;

        $wpml_lang 	= false;
        $post_type 	= $collection->post_type;
        $wpml_type 	= "post_{$post_type}";
        $taxonomy 	= Wicked_Folders::get_tax_name( $post_type );

        // Polylang also uses the wpml_current_language filter for some reason.
        // Only use the  filter if WPML is really active
        if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
            $wpml_lang = apply_filters( 'wpml_current_language', false );
        }	
        
        if ( false !== $wpml_lang ) {
            $query = $wpdb->prepare( "SELECT COUNT(p.ID) AS n FROM {$wpdb->posts} AS p INNER JOIN {$wpdb->prefix}icl_translations translations ON p.ID = translations.element_id WHERE p.post_type = %s AND p.post_status NOT IN ('trash', 'auto-draft') AND translations.language_code = %s AND translations.element_type = %s", $post_type, $wpml_lang, $wpml_type );
        }

        return $query;
    }    
}