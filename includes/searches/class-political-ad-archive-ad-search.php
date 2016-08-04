<?php

/**
 * The candidate model
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes/models
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveAdSearch implements PoliticalAdArchiveBufferedQuery {

	private $posts_per_page;

	// Make it possible to specify filters for the search
	// These are all arrays of tuples:
	//   'term' => the string being searched
	//   'boolean' => "and" or "or" or "not"
	private $word_filters = array();
	private $candidate_filters = array();
	private $sponsor_filters = array();
	private $sponsor_type_filters = array();
	private $subject_filters = array();
	private $type_filters = array();
	private $market_filters = array();
	private $channel_filters = array();
	private $program_filters = array();
	private $transcript_filters = array();

	// filter cache stores the results of the filter (a list of post IDs)
	private $_filter_cache = array();

	public function PoliticalAdArchiveAdSearch($args = null) {
		$this->posts_per_page = 500;
	}

	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}

	public function __set($property, $value) {
		if (property_exists($this, $property)) {
			$this->$property = $this->parse_filter($value);
		}

		return $this;
	}

	private function parse_filter($filter) {
		if(is_array($filter))
			return $filter;
		$query_boolean_parts = preg_split("/(\sAND\s|\sOR\s|\sNOT\s)/", $filter, -1, PREG_SPLIT_DELIM_CAPTURE);

		$filter_array = array();
		$active_boolean = "or";
		foreach($query_boolean_parts as $query_part) {
			if($query_part == " AND "
			|| $query_part == " OR "
			|| $query_part == " NOT ") {
				$active_boolean = trim(strtolower($query_part));
				continue;
			}
			if(trim($query_part) == "")
				continue;

			$filter_array[] = array(
				'term' => $query_part,
				'boolean' => $active_boolean
			);
		}
		return $filter_array;
	} 

	public function get_chunk($page) {
		$filtered_ids = $this->get_filtered_ids();

	    $args = array(
	        'post_type'      => 'archive_political_ad',
	        'post_status'    => 'publish',
	        'orderby'        => 'post_date',
	        'order'          => 'DESC',
            'posts_per_page' => $this->posts_per_page,
            'paged' => $page + 1,
            'post__in' => (sizeof($filtered_ids) > 0)?$filtered_ids:array(-1)
	    );

	    $wp_query = new WP_Query($args);
	    $ads = $wp_query->posts;

	    $rows = array();
	    foreach($ads as $ad) {
	    	$rows[] = $this->generate_row($ad);
	    }
	    return $rows;
	}

	private function run_meta_filter($filters, $meta_key, $exact_match=false) {
		global $wpdb;

        $meta_table = $wpdb->prefix . 'postmeta';
        $posts_table = $wpdb->prefix . 'posts';
        $or_parts = array();
		$and_parts = array();
        foreach($filters as $filter) {
            if($filter['term'] == "")
                continue;

            $subquery = "
             SELECT * from ".$meta_table."
    		  WHERE ".$meta_table.".meta_key LIKE '".$meta_key."'
    			AND ".$meta_table.".meta_value LIKE '".($exact_match?"":"%").esc_sql($filter['term']).($exact_match?"":"%")."'
    			AND ".$meta_table.".post_id = ".$posts_table.".ID";
            switch($filter['boolean']) {
            	case 'not':
            		$and_parts[] = " NOT EXISTS (".$subquery.")";
            		break;
            	case 'and':
            		$and_parts[] = " EXISTS (".$subquery.")";
            		break;
            	case 'or':
            		$or_parts[] = " EXISTS (".$subquery.")";
            		break;
            }
        }

        $or_clause = sizeof($or_parts)>0?implode(") OR (", $or_parts):"true";
        $and_clause = sizeof($and_parts)>0?implode(") AND (", $and_parts):"true";
        $query = "SELECT ID
                    FROM ".$posts_table."
                   WHERE ((".$or_clause.") AND (".$and_clause."))";

        $results = $wpdb->get_results($query);
	    $filtered_ids = array();

	    foreach($results as $row) {
            $filtered_ids[] = $row->ID;
	    }
	    return $filtered_ids;
	}

	private function run_sponsor_filter($filters, $field, $exact_match = false) {
		global $wpdb;
        $meta_table = $wpdb->prefix . 'postmeta';
        $sponsors_table = $wpdb->prefix . 'ad_sponsors';
        $posts_table = $wpdb->prefix . 'posts';
        $or_parts = array();
		$and_parts = array();
        foreach($filters as $filter) {
            if($filter['term'] == "")
                continue;
            
            $subquery = "
	             SELECT * from ".$meta_table."
	    		   JOIN ".$sponsors_table." ON ".$sponsors_table.".name = ".$meta_table.".meta_value
	    		  WHERE ".$meta_table.".meta_key LIKE 'ad_sponsors_%ad_sponsor'
	    			AND ".$sponsors_table.".".$field." LIKE '".($exact_match?"":"%").esc_sql($filter['term']).($exact_match?"":"%")."'
	    			AND ".$meta_table.".post_id = ".$posts_table.".ID";

            switch($filter['boolean']) {
            	case 'not':
            		$and_parts[] = " NOT EXISTS (".$subquery.")";
            		break;
            	case 'and':
            		$and_parts[] = " EXISTS (".$subquery.")";
            		break;
            	case 'or':
            		$or_parts[] = " EXISTS (".$subquery.")";
            		break;
            }
        }

        $or_clause = sizeof($or_parts)>0?implode(") OR (", $or_parts):"true";
        $and_clause = sizeof($and_parts)>0?implode(") AND (", $and_parts):"true";
        $query = "SELECT ID
                    FROM ".$posts_table."
                   WHERE ((".$or_clause.") AND (".$and_clause."))";

        $results = $wpdb->get_results($query);
	    $filtered_ids = array();

	    foreach($results as $row) {
            $filtered_ids[] = $row->ID;
	    }
	    return $filtered_ids;
	}

	private function run_instance_filter($filters, $field, $exact_match = false) {
		global $wpdb;
        $instances_table = $wpdb->prefix . 'ad_instances';
        $posts_table = $wpdb->prefix . 'posts';
        $or_parts = array();
		$and_parts = array();
        foreach($filters as $filter) {
            if($filter['term'] == "")
                continue;
            
            $subquery = "
	             SELECT * from ".$instances_table."
	    		  WHERE ".$instances_table.".".$field." LIKE '".($exact_match?"":"%").esc_sql($filter['term']).($exact_match?"":"%")."'
	    			AND ".$instances_table.".wp_identifier = ".$posts_table.".ID";

            switch($filter['boolean']) {
            	case 'not':
            		$and_parts[] = " NOT EXISTS (".$subquery.")";
            		break;
            	case 'and':
            		$and_parts[] = " EXISTS (".$subquery.")";
            		break;
            	case 'or':
            		$or_parts[] = " EXISTS (".$subquery.")";
            		break;
            }
        }

        $or_clause = sizeof($or_parts)>0?implode(") OR (", $or_parts):"true";
        $and_clause = sizeof($and_parts)>0?implode(") AND (", $and_parts):"true";
        $query = "SELECT ID
                    FROM ".$posts_table."
                   WHERE ((".$or_clause.") AND (".$and_clause."))";

        $results = $wpdb->get_results($query);
	    $filtered_ids = array();

	    foreach($results as $row) {
            $filtered_ids[] = $row->ID;
	    }
	    return $filtered_ids;
	}

	private function get_filtered_ids() {
		// Return the cached values if they exist
		if($this->_filter_cache != null)
			return $this->_filter_cache;

		// Start off with all IDs
		$ids = get_posts(array(
			'fields' => 'ids',
	        'post_status' => 'publish',
	        'post_type'   => 'archive_political_ad'
	    ));

	    // Run the additive filters
	    if(sizeof($this->word_filters) > 0) {
	    	$ids = array_unique(
	    		$this->run_meta_filter($this->word_filters,'ad_candidates_%_ad_candidate'),
	    		$this->run_meta_filter($this->word_filters,'ad_sponsors_%ad_sponsor'),
	    		$this->run_sponsor_filter($this->word_filters, "type", true),
	    		$this->run_meta_filter($this->word_filters, 'ad_subjects_%_ad_subject'),
	    		$this->run_meta_filter($this->word_filters, 'ad_message'),
	    		$this->run_meta_filter($this->word_filters, 'ad_type', true),
	    		$this->run_meta_filter($this->word_filters, 'archive_id', true),
	    		$this->run_meta_filter($this->word_filters, 'transcript')
	    	);
	    }

		// Run the subtractive filters
		if(sizeof($this->candidate_filters) > 0) {
			$filtered_ids = $this->run_meta_filter(
				$this->candidate_filters,
				'ad_candidates_%_ad_candidate'
			);
		    $ids = array_intersect($ids, $filtered_ids);
	    }

		if(sizeof($this->sponsor_filters) > 0) {
			$filtered_ids = $this->run_meta_filter(
				$this->sponsor_filters,
				'ad_sponsors_%ad_sponsor'
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if(sizeof($this->sponsor_type_filters) > 0) {
			$filtered_ids = $this->run_sponsor_filter(
				$this->sponsor_type_filters,
				"type",
				true
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if(sizeof($this->subject_filters) > 0) {
			$filtered_ids = $this->run_meta_filter(
				$this->subject_filters,
				'ad_subjects_%_ad_subject'
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if(sizeof($this->message_filters) > 0) {
			$filtered_ids = $this->run_meta_filter(
				$this->message_filters,
				'ad_message'
			);
		    $ids = array_intersect($ids, $filtered_ids, true);
		}

		if(sizeof($this->type_filters) > 0) {
			$filtered_ids = $this->run_meta_filter(
				$this->type_filters,
				'ad_type',
				true
			);
		    $ids = array_intersect($ids, $filtered_ids, true);
		}

		if(sizeof($this->archive_id_filters) > 0) {
			$filtered_ids = $this->run_meta_filter(
				$this->archive_id_filters,
				'archive_id',
				true
			);
		    $ids = array_intersect($ids, $filtered_ids, true);
		}

		if(sizeof($this->network_filters) > 0) {
			$filtered_ids = $this->run_instance_filter(
				$this->network_filters,
				'network',
				true
			);
		    $ids = array_intersect($ids, $filtered_ids, true);
		}

		if(sizeof($this->market_filters) > 0) {
			$filtered_ids = $this->run_instance_filter(
				$this->market_filters,
				'market',
				true
			);
		    $ids = array_intersect($ids, $filtered_ids, true);
		}

		if(sizeof($this->location_filters) > 0) {
			$filtered_ids = $this->run_instance_filter(
				$this->location_filters,
				'location'
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if(sizeof($this->program_filters) > 0) {
			$filtered_ids = $this->run_instance_filter(
				$this->program_filters,
				'program'
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if(sizeof($this->program_type_filters) > 0) {
			$filtered_ids = $this->run_instance_filter(
				$this->program_type_filters,
				'program_type',
				true
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

	    $this->_filter_cache = $ids;
	    return $ids;
	}

	private function generate_row($row) {
        $ad = new PoliticalAdArchiveAd($row->ID);
        $parsed_row = [
            "wp_identifier" => $ad->wp_id,
            "archive_id" => $ad->archive_id,
            "embed_url" => $ad->embed_url,
            "sponsors" => implode(", ", $ad->sponsor_names),
            "sponsor_types" => implode(", ", $ad->sponsor_types),
            "sponsor_affiliations" => implode(", ", $ad->sponsor_affiliations),
            "sponsor_affiliation_types" => implode(", ", $ad->sponsor_affiliation_types),
            "subjects" => implode(", ", $ad->subjects),
            "candidates" => implode(", ", $ad->candidate_names),
            "type" => $ad->type,
            "race" => $ad->race,
            "cycle" => $ad->cycle,
            "message" => $ad->message,
            "air_count" => $ad->air_count,
            "reference_count" => $ad->references,
            "market_count" => $ad->market_count,
            "transcript" => $ad->transcript,
            "date_ingested" => $ad->ingest_date
        ];

		return $parsed_row;
	}
}