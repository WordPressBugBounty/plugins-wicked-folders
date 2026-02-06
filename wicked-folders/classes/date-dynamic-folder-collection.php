<?php

namespace Wicked_Folders;

use Wicked_Folders;
use DateTimeZone;
use DateTime;

// Disable direct load
defined( 'ABSPATH' ) || exit;

/**
 * Holds a collection of Date_Dynamic folders.
 */
class Date_Dynamic_Folder_Collection extends Dynamic_Folder_Collection {

    public static $folder_class = Date_Dynamic_Folder::class;

    public function fetch() {
		global $wpdb;

		$years 		= array();
		$folders 	= array();
        $post_type 	= $this->post_type;
        $taxonomy 	= Wicked_Folders::get_tax_name( $post_type );

		// Fetch post dates
		if ( 'attachment' == $post_type ) {
			$results = $wpdb->get_results( "SELECT DISTINCT(DATE_FORMAT(post_date, '%Y-%m-%d')) AS post_date FROM {$wpdb->posts} WHERE post_type = 'attachment' ORDER BY post_date ASC" );
		} else {
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT(DATE_FORMAT(post_date, '%%Y-%%m-%%d')) AS post_date FROM {$wpdb->posts} WHERE post_type = %s AND post_status NOT IN ('trash', 'auto-draft') ORDER BY post_date ASC", $post_type ) );         
		}

		// Organize dates into an array that will be easy to loop through
		foreach ( $results as $row ) {
			// Skip blank dates
			if ( '0000-00-00' == $row->post_date ) continue;

			$timezone = new DateTimeZone( Wicked_Folders::timezone_identifier() );

			$date = new DateTime( $row->post_date, $timezone );

			$year 	= $date->format( 'Y' );
			$month 	= $date->format( 'm' );
			$day 	= $date->format( 'd' );

			// $dates[ $year ][ $month ][ $day ] = array();
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

		// Create folders
		foreach ( $years as $year ) {
			$year_id = 'dynamic_date_' . $year['year'];

			$this->add( new Date_Dynamic_Folder( array(
                'id' 		=> $year_id,
                'name' 		=> $year['name'],
                'parent' 	=> 'dynamic_date',
                'post_type' => $post_type,
                'taxonomy' 	=> $taxonomy,
                'order' 	=> $year['year'],
                'lazy'      => $this->lazy,
            ) ) );

			foreach ( $year['months'] as $month ) {
    			$month_id = 'dynamic_date_' . $year['year'] . '_' . $month['month'];

				$this->add( new Date_Dynamic_Folder( array(
                    'id' 		=> $month_id,
                    'name' 		=> $month['name'],
                    'parent' 	=> $year_id,
                    'post_type' => $post_type,
                    'taxonomy' 	=> $taxonomy,
                    'order' 	=> $month['month'],
                    'lazy'      => $this->lazy,
                ) ) );

				foreach ( $month['days'] as $day ) {
    				$day_id = 'dynamic_date_' . $year['year'] . '_' . $month['month'] . '_' . $day['day'];

					$this->add( new Date_Dynamic_Folder( array(
                        'id' 		=> $day_id,
                        'name' 		=> $day['name'],
                        'parent' 	=> $month_id,
                        'post_type' => $post_type,
                        'taxonomy' 	=> $taxonomy,
                        'order' 	=> $day['day'],
                        'lazy'      => $this->lazy,
                    ) ) );
				}
			}
		}
    }
}