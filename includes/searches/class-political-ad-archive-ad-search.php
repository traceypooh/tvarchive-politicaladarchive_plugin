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
	private $pages = array();
	private $sort = 'air_date';

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
	private $archive_id_filters = array();
    private $start_time;
    private $end_time;

	// filter cache stores the results of the filter (a list of post IDs)
	private $_filter_cache = array();

	public function __construct($args = null) {
		$this->posts_per_page = 500;
	}

	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}

	public function __set($property, $value) {
		if (property_exists($this, $property)) {
			// Filters get specially formatted
			if(strpos($property, 'filters') !== false) {
				$this->$property = $this->parse_filter($value);
			}
			else {
				$this->$property = $value;
			}
		}

		return $this;
	}

	private function parse_filter($filter) {
		if(is_array($filter))
			return $filter;

		// replace commas with " OR "
		$filter = str_replace(",", " OR ", $filter);
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

			error_log("parse_filter: $query_part");
		}

		return $filter_array;
	}

	public function get_total_rows() {
		return $this->get_chunk(0, true);
	}

	// This is terrible, but if you pass true to the second parameter
	// then the first is ignored and a total count is returned
	public function get_chunk($page, $get_count=false) {
		$filtered_ids = $this->get_filtered_ids();

		global $wpdb;
        $instances_table = $wpdb->prefix . 'ad_instances';
        $posts_table = $wpdb->prefix . 'posts';

        if($get_count) {
			$query = "SELECT count(DISTINCT ".$posts_table.".ID) as row_count";
		} else {
			$query = "SELECT ".$posts_table.".ID as post_id,
			           		 count(".$instances_table.".id) as air_count";
		}

		$query .= " FROM ".$posts_table."
		       LEFT JOIN ".$instances_table." ON ".$instances_table.".wp_identifier = ".$posts_table.".ID
		           WHERE ".$posts_table.".post_status = 'publish'
		             AND ".$posts_table.".ID IN (".implode(',', ((count((array)$this->filtered_ids))?$filtered_ids:array(-1))).")"; // xxx

        // Instance filters
        $query_parts = array();
        if(count((array)$this->market_filters))
            $query_parts[] = $this->generate_instance_filter_query_part($this->market_filters, "market");
        if(count((array)$this->channel_filters))
            $query_parts[] = $this->generate_instance_filter_query_part($this->channel_filters, "network");
        if(count((array)$this->program_filters))
            $query_parts[] = $this->generate_instance_filter_query_part($this->program_filters, "program");
		if($this->start_time)
			$query_parts[] = $instances_table.".end_time > '".esc_sql(date('Y-m-d H:i:s',strtotime($this->start_time)))."'";
		if($this->end_time)
			$query_parts[] = $instances_table.".start_time < '".esc_sql(date('Y-m-d H:i:s',strtotime($this->end_time)))."'";

        if(count((array)$this->query_parts))
            $query .= " AND ".implode(' AND ', $query_parts);

        // Are we gtting results or pages
        if(!$get_count) {

			// Do we only want specific pages
			if(count((array)$this->pages)) {
				if(!(array_key_exists($page, $this->pages)))
					return array();
				$page = $this->pages[$page];
			}
	        $query .= " GROUP BY ".$posts_table.".ID ";

	        if($this->sort == "air_count")
	        	$query .= " ORDER BY air_count DESC ";
	        else
	        	$query .= " ORDER BY ".$posts_table.".post_date DESC ";

	        if($this->per_page != -1)
				$query .= " LIMIT ".($page * $this->posts_per_page).", ".$this->posts_per_page;

error_log("AAAS $query");

	        $results = $wpdb->get_results($query);
		    $rows = array();
		    foreach($results as $result) {
		    	$ad_id = $result->post_id;
		    	$air_count = $result->air_count;
		    	$rows[] = $this->generate_row($ad_id, $air_count);
		    }
		    return $rows;
		} else {
	        $result = $wpdb->get_row($query);
	        return $result->row_count;
		}

	}

    private function generate_instance_filter_query_part($filters, $field) {
		global $wpdb;
        $instances_table = $wpdb->prefix . 'ad_instances';
        $subquery = "";
        $and_parts = array();
        $or_parts = array();
        foreach($filters as $filter) {
            switch($filter['boolean']) {
                case 'not':
                    $and_parts[] = $instances_table.".".$field." != '".esc_sql($filter['term'])."'";
                    break;
                case 'and':
                    $and_parts[] = $instances_table.".".$field." = '".esc_sql($filter['term'])."'";
                    break;
                case 'or':
                    $or_parts[] = $instances_table.".".$field." = '".esc_sql($filter['term'])."'";
                    break;
            }
        }

        $subquery .= "(";
        if(count((array)$this->and_parts))
            $subquery .= "(".implode(' AND ', $and_parts).")";
        else
            $subquery .= "false";
        if(count((array)$this->or_parts))
            $subquery .= " OR (".implode(' OR ', $or_parts).")";
        $subquery .= ")";
        return $subquery;
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

        $or_clause = count((array)$this->or_parts)?implode(") OR (", $or_parts):"true";
        $and_clause = count((array)$this->and_parts)?implode(") AND (", $and_parts):"true";
        $query = "SELECT DISTINCT ID
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

        $or_clause = count((array)$this->or_parts)?implode(") OR (", $or_parts):"true";
        $and_clause = count((array)$this->and_parts)?implode(") AND (", $and_parts):"true";
        $query = "SELECT DISTINCT ID
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

        $or_clause = count((array)$this->or_parts)?implode(") OR (", $or_parts):"true";
        $and_clause = count((array)$this->and_parts)?implode(") AND (", $and_parts):"true";
        $query = "SELECT DISTINCT ID
                    FROM ".$posts_table."
                   WHERE ((".$or_clause.")
                     AND (".$and_clause."))
                     AND ".$posts_table.".post_status = 'publish'";

        $results = $wpdb->get_results($query);
	    $filtered_ids = array();

	    foreach($results as $row) {
            $filtered_ids[] = $row->ID;
	    }
	    return $filtered_ids;
	}

	public function run_date_range_filter($start_time, $end_time) {
		global $wpdb;

		// Make sure the times are populated
		if(!$end_time)
			$end_time = date('Y-m-d H:i:s');
		if(!$start_time)
			$start_time = 0;

        $instances_table = $wpdb->prefix . 'ad_instances';
        $posts_table = $wpdb->prefix . 'posts';
        $query = "SELECT DISTINCT ".$posts_table.".ID
                    FROM ".$posts_table."
                    JOIN ".$instances_table." ON ".$instances_table.".wp_identifier = ".$posts_table.".ID
                   WHERE end_time > '".esc_sql(date('Y-m-d H:i:s',strtotime($start_time)))."'
                     AND start_time < '".esc_sql(date('Y-m-d H:i:s',strtotime($end_time)))."'
                     AND ".$posts_table.".post_status = 'publish'";
        $results = $wpdb->get_results($query);
	    $filtered_ids = array();

	    foreach($results as $row) {
            $filtered_ids[] = $row->ID;
	    }
	    return $filtered_ids;
	}

	public function get_filtered_ids() {
		// Return the cached values if they exist
		if($this->_filter_cache != null)
			return $this->_filter_cache;

		// Start off with all IDs
		$ids = get_posts([
			'fields' => 'ids',
	        'post_status' => 'publish',
	        'post_type'   => 'archive_political_ad',
	        'numberposts' => -1
	    ]);

			error_log('get_filtered_ids() A count: ' . count($ids));

	    // Run the additive filters
	    if(count((array)$this->word_filters)) {
	    	$ids = array_unique(
	    		array_merge(
		    		$this->run_meta_filter($this->word_filters,'ad_candidates_%_ad_candidate'),
		    		$this->run_meta_filter($this->word_filters,'ad_sponsors_%ad_sponsor'),
		    		$this->run_sponsor_filter($this->word_filters, "type", true),
		    		$this->run_meta_filter($this->word_filters, 'ad_subjects_%_ad_subject'),
		    		$this->run_meta_filter($this->word_filters, 'ad_message'),
		    		$this->run_meta_filter($this->word_filters, 'ad_type', true),
		    		$this->run_meta_filter($this->word_filters, 'archive_id', true),
		    		$this->run_meta_filter($this->word_filters, 'transcript')
		    	)
	    	);
	    }

		error_log('get_filtered_ids() B count: ' . count($ids));

		// Run the subtractive filters
		if(count((array)$this->archive_id_filters)) {
			$filtered_ids = $this->run_meta_filter(
				$this->archive_id_filters,
				'archive_id'
			);
		    $ids = array_intersect($ids, $filtered_ids);
	    }

		if(count((array)$this->candidate_filters)) {
			$filtered_ids = $this->run_meta_filter(
				$this->candidate_filters,
				'ad_candidates_%_ad_candidate'
			);
		    $ids = array_intersect($ids, $filtered_ids);
	    }

		if(count((array)$this->sponsor_filters)) {
			$filtered_ids = $this->run_meta_filter(
				$this->sponsor_filters,
				'ad_sponsors_%ad_sponsor'
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if(count((array)$this->sponsor_type_filters)) {
			$filtered_ids = $this->run_sponsor_filter(
				$this->sponsor_type_filters,
				"type",
				true
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if(count((array)$this->subject_filters)) {
			$filtered_ids = $this->run_meta_filter(
				$this->subject_filters,
				'ad_subjects_%_ad_subject'
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if (count((array)$this->message_filters)) {
			$filtered_ids = $this->run_meta_filter(
				$this->message_filters,
				'ad_message',
				true
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if(count((array)$this->type_filters)) {
			$filtered_ids = $this->run_meta_filter(
				$this->type_filters,
				'ad_type',
				true
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if(count((array)$this->archive_id_filters)) {
			$filtered_ids = $this->run_meta_filter(
				$this->archive_id_filters,
				'archive_id',
				true
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if(count((array)$this->network_filters)) {
			$filtered_ids = $this->run_instance_filter(
				$this->network_filters,
				'network',
				true
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if(count((array)$this->market_filters)) {
			$filtered_ids = $this->run_instance_filter(
				$this->market_filters,
				'market',
				true
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if(count((array)$this->location_filters)) {
			$filtered_ids = $this->run_instance_filter(
				$this->location_filters,
				'location'
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if(count((array)$this->program_filters)) {
			$filtered_ids = $this->run_instance_filter(
				$this->program_filters,
				'program'
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if(count((array)$this->program_type_filters)) {
			$filtered_ids = $this->run_instance_filter(
				$this->program_type_filters,
				'program_type',
				true
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		if($this->start_time || $this->end_time) {
			$filtered_ids = $this->run_date_range_filter(
				$this->start_time,
				$this->end_time
			);
		    $ids = array_intersect($ids, $filtered_ids);
		}

		error_log('get_filtered_ids() C count: ' . count($ids));

	  $this->_filter_cache = $ids;
	  return $ids;
	}

	private function generate_row($ad_id, $air_count) {
        $ad = new PoliticalAdArchiveAd($ad_id);
        $parsed_row = [
            "wp_identifier" => $ad->wp_id,
            "archive_id" => $ad->archive_id,
            "embed_url" => $ad->embed_url,
            "sponsors" => implode(", ", $ad->sponsor_names),
            "sponsor_types" => implode(", ", $ad->sponsor_types),
            // "sponsor_affiliations" => implode(", ", $ad->sponsor_affiliations),
            // "sponsor_affiliation_types" => implode(", ", $ad->sponsor_affiliation_types),
            "subjects" => implode(", ", $ad->subjects),
            "candidates" => implode(", ", $ad->candidate_names),
            "type" => $ad->type,
            "race" => $ad->race,
            "cycle" => $ad->cycle,
            "message" => $ad->message,
            "air_count" => $air_count,
            "reference_count" => $ad->reference_count,
            "market_count" => $ad->market_count,
            "transcript" => $ad->transcript,
            "date_ingested" => $ad->date_ingested
        ];

		return $parsed_row;
	}
}