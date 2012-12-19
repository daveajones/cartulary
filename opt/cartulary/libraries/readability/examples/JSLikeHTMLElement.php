<?php
require_once '../JSLikeHTMLElement.php';
header('Content-Type: text/plain');
$doc = new DOMDocument();
$doc->registerNodeClass('DOMElement', 'JSLikeHTMLElement');
$doc->loadHTML('<div><p>Para 1</p><p>Para 2</p></div>');
$elem = $doc->getElementsByTagName('div')->item(0);

// print innerHTML
echo $elem->innerHTML; // prints '<p>Para 1</p><p>Para 2</p>'
echo "\n\n";

// set innerHTML
$elem->innerHTML = '<a href="http://fivefilters.org">FiveFilters.org</a>';
echo $elem->innerHTML; // prints '<a href="http://fivefilters.org">FiveFilters.org</a>'
echo "\n\n";

// print document (with our changes)
echo $doc->saveXML();
?>