<?php
require_once '../Readability.php';
header('Content-Type: text/plain; charset=utf-8');

// get latest Medialens alert 
// (change this URL to whatever you'd like to test)
$url = 'http://medialens.org/alerts/index.php';
$html = file_get_contents($url);

// Note: PHP Readability expects UTF-8 encoded content.
// If your content is not UTF-8 encoded, convert it 
// first before passing it to PHP Readability. 
// Both iconv() and mb_convert_encoding() can do this.

// If we've got Tidy, let's clean up input.
// This step is highly recommended - PHP's default HTML parser
// often does a terrible job and results in strange output.
if (function_exists('tidy_parse_string')) {
	$tidy = tidy_parse_string($html, array(), 'UTF8');
	$tidy->cleanRepair();
	$html = $tidy->value;
}

// give it to Readability
$readability = new Readability($html, $url);
// print debug output? 
// useful to compare against Arc90's original JS version - 
// simply click the bookmarklet with FireBug's console window open
$readability->debug = false;
// convert links to footnotes?
$readability->convertLinksToFootnotes = true;
// process it
$result = $readability->init();
// does it look like we found what we wanted?
if ($result) {
	echo "== Title =====================================\n";
	echo $readability->getTitle()->textContent, "\n\n";
	echo "== Body ======================================\n";
	$content = $readability->getContent()->innerHTML;
	// if we've got Tidy, let's clean it up for output
	if (function_exists('tidy_parse_string')) {
		$tidy = tidy_parse_string($content, array('indent'=>true, 'show-body-only' => true), 'UTF8');
		$tidy->cleanRepair();
		$content = $tidy->value;
	}
	echo $content;
} else {
	echo 'Looks like we couldn\'t find the content. :(';
}
?>