<?php

/**
 * An object that genreates an API response from a set of data
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/public/models
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveApiResponse {

	const FORMAT_JSON = "json";
	const FORMAT_CSV = "csv";

	private $data; // The array of data to be included
	private $format; // The format that the data should be printed as

	public function PoliticalAdArchiveApiResponse($data, $format) {
		$this->data = $data;
		$this->format = $format;
	}

	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}

	public function __set($property, $value) {
		if (property_exists($this, $property)) {
			$this->$property = $value;
		}

		return $this;
	}

	public function send() {
		$this->send_headers();
		$this->send_body();
	}

	/**
	 * Sets header information based on the response
	 * @return 
	 */
	public function send_headers() {
		switch($this->format) {
	        case PoliticalAdArchiveApiResponse::FORMAT_CSV:
	            // output headers so that the file is downloaded rather than displayed
	            header('Content-Type: text/csv; charset=utf-8');
	            header('Content-Disposition: attachment; filename='.time().'_export.csv');
	            break;
	        case PoliticalAdArchiveApiResponse::FORMAT_JSON:
	            header('Content-Type: application/json');
	            break;
    	}
	}

	public function send_body() {
		// Depending on the type of data, send in chunks or all at once
		// Currently this can only send arrays or buffered queries
		
		// Send the array
		if(is_array($this->data)) {
			$this->send_start($this->data);
			$this->send_chunk($this->data);
			$this->send_end();
			return;
		}

		// Send the buffered query
		if($this->data instanceof PoliticalAdArchiveBufferedQuery) {
			$page = 0;
		    while(true) {

		    	// Get the chunk
		    	$chunk = $this->data->get_chunk($page);

		    	// If this is the first chunk, send the start info
		    	if($page == 0)
					$this->send_start($chunk);

		    	// Are we done?
				if(sizeof($chunk) == 0)
					break;

				// If this is JSON we need to delimit chunks
				if($this->format == PoliticalAdArchiveApiResponse::FORMAT_JSON
				&& $page > 0)
	                echo(",");


				// Send the chunk
		        $this->send_chunk($chunk);

		        // Move to the next page
		        $page += 1;
		    }
			$this->send_end();

		}
	}

	// Sine data can be a buffer, we need a sample row that is guaranteed to be a keyed array
	private function send_start($rows) {
	    switch($this->format) {
	        case PoliticalAdArchiveApiResponse::FORMAT_CSV:

				if(sizeof($rows) == 0)
					return;
				$row = $rows[0];

	            // output headers so that the file is downloaded rather than displayed
	            $header = array_keys($rows[0]);
	            $output = fopen('php://output', 'w');
	            fputcsv($output, $header);
	            fclose($output);
	            break;
	        case PoliticalAdArchiveApiResponse::FORMAT_JSON:
	        	$total_results = 0;
	        	if($this->data instanceof PoliticalAdArchiveBufferedQuery)
		        	$total_results = $this->data->get_total_rows();

				if(is_array($this->data))
					$total_results = sizeof($this->data);

	        	echo('
		        	{
		        		"total_results": '.$total_results.',
		        		"data":
		        			[');
	            break;
	    }
	}

	private function send_chunk($rows) {
		if(!is_array($rows)
		|| sizeof($rows) == 0)
			return;

		switch($this->format) {
	        case PoliticalAdArchiveApiResponse::FORMAT_CSV:
	            // create a file pointer connected to the output stream
	            $output = fopen('php://output', 'w');
	            
	            // loop over the rows, outputting them
                foreach($rows as $row) {
	                fputcsv($output, $row);
	            }

	            // Close the file pointer
                fclose($output);
	            break;

	        case PoliticalAdArchiveApiResponse::FORMAT_JSON:
	            // strip the array braces
	            $json = substr(json_encode($rows), 1, -1);
	            echo($json);
	            break;
	    }
	}

	private function send_end() {
	    switch($this->format) {
	        case PoliticalAdArchiveApiResponse::FORMAT_CSV:
	            break;
	        case PoliticalAdArchiveApiResponse::FORMAT_JSON:
	            echo("]}");
	            break;
	    }
	}
}