<?php
/**
 * Site Config
 * 
 * Each instance of this class should hold extraction patterns and other directives
 * for a website. See ContentExtractor class to see how it's used.
 * 
 * @version 0.5
 * @date 2011-03-08
 * @author Keyvan Minoukadeh
 * @copyright 2011 Keyvan Minoukadeh
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPL v3
 */

class SiteConfig
{
	// Use first matching element as title (0 or more xpath expressions)
	public $title = array(); 
	
	// Use first matching element as body (0 or more xpath expressions)
	public $body = array(); 
	
	// Strip elements matching these xpath expressions (0 or more)
	public $strip = array();
	
	// Strip elements which contain these strings (0 or more) in the id or class attribute 
	public $strip_id_or_class = array();
	
	// Strip images which contain these strings (0 or more) in the src attribute 
	public $strip_image_src = array();
	
	// Process HTML with tidy before creating DOM
	public $tidy = true;
	
	// Autodetect title/body if xpath expressions fail to produce results.
	// Note that this applies to title and body separately, ie. 
	//   * if we get a body match but no title match, this option will determine whether we autodetect title 
	//   * if neither match, this determines whether we autodetect title and body.
	// Also note that this only applies when there is at least one xpath expression in title or body, ie.
	//   * if title and body are both empty (no xpath expressions), this option has no effect (both title and body will be auto-detected)
	//   * if there's an xpath expression for title and none for body, body will be auto-detected and this option will determine whether we auto-detect title if the xpath expression for it fails to produce results.
	// Usage scenario: you want to extract something specific from a set of URLs, e.g. a table, and if the table is not found, you want to ignore the entry completely. Auto-detection is unlikely to succeed here, so you construct your patterns and set this option to false. Another scenario may be a site where auto-detection has proven to fail (or worse, picked up the wrong content).
	public $autodetect_on_failure = true;
	
	// Clean up content block - attempt to remove elements that appear to be superfluous
	public $prune = true;
	
	// Test URL - if present, can be used to test the config above
	public $test_url = null;
}
?>