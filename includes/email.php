<?php
//########################################################################################
// API for managing email tasks
//########################################################################################


// Recurse through an imap structure and return an html subtype if one is found
function email_get_html_part($arr) {
    if ($arr) {
        foreach ($arr as $value) {
            if (is_array($value)) {
                //Found a sub-part so check if it's html.  If not, recurse
                //into it and look for more
                //if()
                email_get_html_part($value);
            }
        }
    }
}

//Clean an email html part to remove funky stuff
function clean_email_html($html = NULL) {

    $cleaned = str_replace("&nbsp;", "", $html);

    return($cleaned);
}

//Flatten imap parts into an array
//__via: http://www.electrictoolbox.com/php-imap-message-body-attachments/
function flattenParts($messageParts, $flattenedParts = array(), $prefix = '', $index = 1, $fullPrefix = true) {

    foreach($messageParts as $part) {
        $flattenedParts[$prefix.$index] = $part;
        if(isset($part->parts)) {
            if($part->type == 2) {
                $flattenedParts = flattenParts($part->parts, $flattenedParts, $prefix.$index.'.', 0, false);
            }
            elseif($fullPrefix) {
                $flattenedParts = flattenParts($part->parts, $flattenedParts, $prefix.$index.'.');
            }
            else {
                $flattenedParts = flattenParts($part->parts, $flattenedParts, $prefix);
            }
            unset($flattenedParts[$prefix.$index]->parts);
        }
        $index++;
    }

    return $flattenedParts;

}

//Get a certain part from the imap array
//__via: http://www.electrictoolbox.com/php-imap-message-body-attachments/
function getPart($connection, $messageNumber, $partNumber, $encoding) {

    $data = imap_fetchbody($connection, $messageNumber, $partNumber);
    switch($encoding) {
        case 0: return $data; // 7BIT
        case 1: return $data; // 8BIT
        case 2: return $data; // BINARY
        case 3: return base64_decode($data); // BASE64
        case 4: return quoted_printable_decode($data); // QUOTED_PRINTABLE
        case 5: return $data; // OTHER
    }

    return("");
}

//Get filename from an imap array part
//__via: http://www.electrictoolbox.com/php-imap-message-body-attachments/
function getFilenameFromPart($part) {

    $filename = '';

    if($part->ifdparameters) {
        foreach($part->dparameters as $object) {
            if(strtolower($object->attribute) == 'filename') {
                $filename = $object->value;
            }
        }
    }

    if(!$filename && $part->ifparameters) {
        foreach($part->parameters as $object) {
            if(strtolower($object->attribute) == 'name') {
                $filename = $object->value;
            }
        }
    }

    return $filename;

}