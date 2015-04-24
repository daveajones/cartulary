<?php
/**
 * Created by PhpStorm.
 * User: DJ
 * Date: 4/5/2015
 * Time: 9:31 PM
 */
include_once "Podcast.php";

class Item extends Podcast {
    public $author = "";
    public $enclosures = array();
    public $guid = array(
        "value" => "",
        "isPermalink" => FALSE
    );
    public $itunes_duration = "";


    public function __construct( $title = "", $description = "", $link = "", $guid = "") {
        //Check default params
        if(empty($title)) return FALSE;
        if(empty($description)) return FALSE;
        if(empty($link)) return FALSE;

        //Create the xml
        $this->xmlFeed = new SimpleXMLElement('<item xmlns:itunes="'.$this->itunes_ns.'"></item>');

        $this->title = $title;
        $this->description = $description;
        $this->link = $link;

        //Check the guid
        if(empty($guid)) {
            $this->guid['value'] = $link;
        } else {
            $this->guid['value'] = $guid;
            if(stripos($guid, 'http') === 0) {
                $this->guid['isPermalink'] = TRUE;
            }
        }

        return(TRUE);
    }

    public function addEnclosure( $url = "", $length = "", $type = "audio/mpeg" ) {
        if(empty($url)) return FALSE;

        //Try to get a file size if none given
        if(empty($length)) {
            //Get the content-length header
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $data = curl_exec($ch);
            curl_close($ch);
            if (preg_match('/Content-Length: (\d+)/', $data, $matches)) {
                // Contains file size in bytes
                $length = (int)$matches[1];
            }
        }

        $this->enclosures[] = array(
            'url'       =>  $url,
            'length'    =>  $length,
            'type'      =>  $type
        );

        return(TRUE);
    }

    public function purgeFeed() {
        //Remove all of the itunes stuff
        $this->removeNodes("", $this->itunes_ns);
    }

    protected function buildFeedObject() {
        //Clean the feed before rebuilding
        if($this->built_once) $this->purgeFeed();

        //Add the required channel elements
        $this->xmlFeed->title = $this->title;
        $this->xmlFeed->description = $this->description;
        $this->xmlFeed->link = $this->link;
        $this->xmlFeed->guid = $this->guid['value'];
        if(!$this->guid['isPermalink']) {
            $this->xmlFeed->guid['isPermalink'] = 'false';
        } else {
            $this->xmlFeed->guid['isPermalink'] = 'true';
        }

        //Dates
        $this->xmlFeed->pubDate = $this->pubDate;

        //Itunes stuff
        if(!empty($this->itunes_subtitle)) {
            $this->xmlFeed->addChild('subtitle', "", $this->itunes_ns);
            $this->xmlFeed->children('itunes', TRUE)->subtitle = $this->itunes_subtitle;
        }

        $this->xmlFeed->addChild('summary', "", $this->itunes_ns);
        if(empty($this->itunes_summary)) {
            $this->itunes_summary = $this->description;
        }
        $this->xmlFeed->children('itunes', TRUE)->summary = $this->itunes_summary;

        if(!empty($this->itunes_author) || !empty($this->author)) {
            $this->xmlFeed->addChild('author', "", $this->itunes_ns);
            if(empty($this->itunes_author)) $this->itunes_author = $this->author;
            $this->xmlFeed->children('itunes', TRUE)->author = $this->itunes_author;
            if(empty($this->itunes_author)) $this->author = $this->itunes_author;
            $this->xmlFeed->author = $this->author;
        }

        if(!empty($this->itunes_duration)) {
            $this->xmlFeed->addChild('duration', "", $this->itunes_ns);
            $this->xmlFeed->children('itunes', TRUE)->duration = $this->itunes_duration;
        }

        if(!empty($this->itunes_image) || !empty($this->image['url'])) {
            $this->xmlFeed->addChild('image', "", $this->itunes_ns);
            if(empty($this->itunes_image)) $this->itunes_image = $this->image['url'];
            $this->xmlFeed->children('itunes', TRUE)->image['href'] = $this->itunes_image;
        }

        $this->xmlFeed->addChild('explicit', "", $this->itunes_ns);
        $this->xmlFeed->children('itunes', TRUE)->explicit = $this->itunes_explicit;

        //Itunes keywords
        if(!empty($this->itunes_keywords)) {
            $itk = "";
            foreach($this->itunes_keywords as $kw) {
                $itk = $itk . " " . $kw;
            }
            $this->xmlFeed->addChild('keywords', "", $this->itunes_ns);
            $this->xmlFeed->children('itunes', TRUE)->keywords = trim($itk);
        }

        //Enclosures
        $count = 0;
        foreach($this->enclosures as $enclosure) {
            $this->xmlFeed->addChild('enclosure');
            $this->xmlFeed->enclosure[$count]['url'] = $enclosure['url'];
            $this->xmlFeed->enclosure[$count]['length'] = $enclosure['length'];
            $this->xmlFeed->enclosure[$count]['type'] = $enclosure['type'];
            $count++;
        }

        //We built the feed
        $this->built_once = TRUE;

        return(TRUE);
    }
}