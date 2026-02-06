<?php

namespace Wicked_Folders;

use DateTime;
use DateTimeZone;
use Wicked_Folders;

// Disable direct load
defined( 'ABSPATH' ) || exit;

/**
 * Represents a dynamically-generated date folder.
 */
class Date_Dynamic_Folder extends Dynamic_Folder {

    private $year   = false;
    private $month  = false;
    private $day    = false;

    public $id      = 'dynamic_date';

    public $parent  = 'dynamic_root';

    public $lazy    = 'true';

    public function __construct( $args = array() ) {
        parent::__construct( wp_parse_args( $args, array(
            'id'        => $this->id,
            'parent'    => $this->parent,
            'name'      => __( 'All Dates', 'wicked-folders' ),
        ) ) );      
    }

    public function pre_get_posts( $query ) {
        $this->parse_id();

        if ( $this->year ) {
            $query->set( 'year', $this->year );
        }

        if ( $this->month ) {
            $query->set( 'monthnum', $this->month );
        }

        if ( $this->day ) {
            $query->set( 'day', $this->day );
        }
    }

    /**
     * Parses the folder's ID to determine the year, month, and day that the
     * folder should filter by.
     */
    private function parse_id() {

        $date = substr( $this->id, 13 );

        $date = explode( '_', $date );

        if ( isset( $date[0] ) ) {
            $this->year = $date[0];
        }

        if ( isset( $date[1] ) ) {
            $this->month = $date[1];
        }

        if ( isset( $date[2] ) ) {
            $this->day = $date[2];
        }

    }

    public function get_child_folders() {
        global $wpdb;

        $this->parse_id();

		$years 		= array();
		$folders 	= new Folder_Collection();
        $post_type  = $this->post_type;
        $taxonomy   = Wicked_Folders::get_tax_name( $post_type );
 
		// Fetch post dates
        // TODO: Optimize this query to only fetch years/months/days that are needed
		if ( 'attachment' == $this->post_type ) {
			$results = $wpdb->get_results( "SELECT post_date FROM {$wpdb->posts} WHERE post_type = 'attachment' ORDER BY post_date ASC" );
		} else {
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT post_date FROM {$wpdb->posts} WHERE post_type = %s AND post_status NOT IN ('trash', 'auto-draft') ORDER BY post_date ASC", $post_type ) );
		}

		// Organize dates into an array that will be easy to loop through
		foreach ( $results as $row ) {
			// Skip blank dates
			if ( '0000-00-00 00:00:00' == $row->post_date ) continue;

			$timezone   = new DateTimeZone( Wicked_Folders::timezone_identifier() );
			$date       = new DateTime( $row->post_date, $timezone );
			$year 	    = $date->format( 'Y' );
			$month 	    = $date->format( 'm' );
			$day 	    = $date->format( 'd' );

			if ( ! isset( $years[ $year ] ) ) {
				$years[ $year ] = array(
					'year' 		=> $year,
					'name' 		=> $year,
					'months' 	=> array(),
				);
			}

			if ( ! isset( $years[ $year ]['months'][ $month ] ) ) {
				$years[ $year ]['months'][ $month ] = array(
					'month' => $month,
					'name' 	=> $date->format( 'F' ),
					'days' 	=> array(),
				);
			}

			if ( ! isset( $years[ $year ]['months'][ $month ]['days'][ $day ] ) ) {
				$years[ $year ]['months'][ $month ]['days'][ $day ] = array(
					'day' 	=> $day,
					'name' 	=> $date->format( 'j' ),
				);
			}

		}

        if ( $this->day ) {
            // Day folders have no children
            return $folders;
        } elseif ( $this->month ) {
            // If the folder is a month folder, generate day folders
            foreach ( $years[ $this->year ]['months'][ $this->month ]['days'] as $day ) {
                $folders->add( new Date_Dynamic_Folder( array(
                    'id' 		=> 'dynamic_date_' . $this->year . '_' . $this->month . '_' . $day['day'],
                    'name' 		=> $day['name'],
                    'parent' 	=> 'dynamic_date_' . $this->year . '_' . $this->month,
                    'post_type' => $this->post_type,
                    'taxonomy' 	=> $taxonomy,
                    'order' 	=> $day['day'],
                ) ) );
            }
        } elseif ( $this->year ) {
            // If the folder is a year folder, generate month folders
            foreach ( $years[ $this->year ]['months'] as $month ) {
                $folders->add( new Date_Dynamic_Folder( array(
                    'id' 		=> 'dynamic_date_' . $this->year . '_' . $month['month'],
                    'name' 		=> $month['name'],
                    'parent' 	=> 'dynamic_date_' . $this->year,
                    'post_type' => $this->post_type,
                    'taxonomy' 	=> $taxonomy,
                    'order' 	=> $month['month'],
                    'lazy'      => true,
                ) ) );
            }
        } else {
            // Otherwise, generate year folders
            foreach ( $years as $year ) {
                $folders->add( new Date_Dynamic_Folder( array(
                    'id' 		=> 'dynamic_date_' . $year['year'],
                    'name' 		=> $year['name'],
                    'parent' 	=> 'dynamic_date',
                    'post_type' => $this->post_type,
                    'taxonomy' 	=> $taxonomy,
                    'order' 	=> $year['year'],
                    'lazy'      => true,
                ) ) );
            }
        }

        return $folders;
    }

    public function get_ancestors() {
        $this->parse_id();

        $ancestors = new Folder_Collection();
        $taxonomy  = Wicked_Folders::get_tax_name( $this->post_type );

        if ( $this->day ) {
            $date = new DateTime();

            $date->setDate( $this->year, $this->month, $this->day );
  
            $ancestors->add( new Date_Dynamic_Folder( array(
                'id' 		=> 'dynamic_date_' . $this->year . '_' . $this->month,
                'name' 		=> $date->format( 'F' ),
                'parent' 	=> 'dynamic_date_' . $this->year,
                'post_type' => $this->post_type,
                'taxonomy' 	=> $taxonomy,
                'order' 	=> $date->format( 'm' ),
                'lazy'      => true,
            ) ) );
        }
        
        if ( $this->month ) {
            $date = new DateTime();

            $date->setDate( $this->year, $this->month, 1 );
  
            $ancestors->add( new Date_Dynamic_Folder( array(
                'id' 		=> 'dynamic_date_' . $this->year,
                'name' 		=> $date->format( 'Y' ),
                'parent' 	=> 'dynamic_date',
                'post_type' => $this->post_type,
                'taxonomy' 	=> $taxonomy,
                'order' 	=> $date->format( 'Y' ),
                'lazy'      => true,
            ) ) );
        }

        return $ancestors;
    }

    public function determine_parent() {
        $this->parse_id();

        if ( $this->day ) {
            $this->parent = 'dynamic_date_' . $this->year . '_' . $this->month;
        } elseif ( $this->month ) {
            $this->parent = 'dynamic_date_' . $this->year;
        } else {
            $this->parent = 'dynamic_date';
        }
    }

    public static function add_to_all_folders( $collection ) {
        $enable_date_dynamic_folder = apply_filters( 'wicked_folders_enable_date_dynamic_folder', true, $collection->post_type );

        if ( $enable_date_dynamic_folder ) {
            $collection->add( new Date_Dynamic_Folder( array(
                'id' 		=> 'dynamic_date',
                'name' 		=> __( 'All Dates', 'wicked-folders' ),
                'parent' 	=> 'dynamic_root',
                'post_type' => $collection->post_type,
                'lazy'      => true,
            ) ) );  
        }        
    }
}
