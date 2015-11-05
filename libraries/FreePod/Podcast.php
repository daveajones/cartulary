<?php
include_once "Item.php";

class Podcast {
    protected $built_once = FALSE;
    public $changed = FALSE;
    public $itunes_ns = "http://www.itunes.com/dtds/podcast-1.0.dtd";
    public $title = "";
    public $description = "";
    public $link = "";
    public $categories = array();
    public $copyright = "";
    public $docs = "http://blogs.law.harvard.edu/tech/rss";
    public $language = "en";
    public $lastBuildDate = "";
    public $managingEditor = "";
    public $pubDate = "";
    public $webMaster = "";
    public $generator = "Freedom Controller";
    public $itunes_subtitle = "";
    public $itunes_summary = "";
    public $itunes_categories = array();
    public $itunes_keywords = array();
    public $itunes_author = "";
    public $itunes_owner = array(
        "email" => "",
        "name" => ""
    );
    public $itunes_image = "";
    public $itunes_explicit = "no";
    public $image = array(
        "url" => "",
        "title" => "",
        "link" => "",
        "description" => "",
        "width" => 0,
        "height" => 0
    );
    protected $xmlFeed = NULL;
    protected $items = array();
    protected $channel = NULL;
    protected $hash = NULL;


    public function __construct( $title = "", $description = "", $link = "" ) {
        if(empty($title)) return FALSE;
        if(empty($description)) return FALSE;
        if(empty($link)) return FALSE;

        //Create the xml feed
        $this->xmlFeed = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss xmlns:itunes="'.$this->itunes_ns.'" version="2.0"></rss>');
        //Channel
        $this->xmlFeed->addChild("channel");
        $this->channel = $this->xmlFeed->channel;
        //Required
        $this->title = $title;
        $this->description = $description;
        $this->link = $link;
        //Dates
        $this->lastBuildDate = $this->pubDate();
        $this->pubDate = $this->lastBuildDate;

        return(TRUE);
    }

    public function newItem( $title = "", $description = "", $link = "", $guid = "" ) {
        $this->changed = TRUE;
        $item = new Item($title, $description, $link, $guid);
        //Set some defaults
        $item->author = $this->managingEditor;
        $item->itunes_author = $this->itunes_author;
        $item->itunes_explicit = $this->itunes_explicit;
        $item->pubDate = $this->pubDate();
        $this->items[] = $item;

        return($item);
    }

    private function addItem( Item $item ) {
        //Convert channel to a DOM element and import item into it
        $domchannel = dom_import_simplexml($this->xmlFeed->channel);
        $domnew = $domchannel->ownerDocument->importNode($item->domObject(), TRUE);
        $domchannel->appendChild($domnew);
    }

    protected function removeNodes( $val, $ns = "" ) {
        //echo "removeNodes(".$val.",".$ns.")\n";
        if(!empty($ns)) {
            $this->xmlFeed->registerXPathNamespace("default", $this->itunes_ns);
            $nsp = "default:";
        } else {
            $nsp = "";
        }

        if(empty($val)) {
            $val = "*";
        }

        $nsnodes = $this->xmlFeed->xpath('//'.$nsp.$val);

        foreach ( $nsnodes as $child )
        {
            if($child) {
                //echo print_r($child, TRUE);
                unset($child[0]);
            }
        }
        return(TRUE);
    }

    public function setValue( $key, $val) {
        $this->changed = TRUE;
        $this->$key = $val;

        return(TRUE);
    }

    protected function pubDate( $val = "" ) {
        if(empty($val)) {
            $pd = date(DATE_RSS);
        } else {
            $pd = strtotime($val);
        }
        return($pd);
    }

    public function addCategory( $val, $itunes = FALSE ) {
        $this->changed = TRUE;
        if($itunes) {
            $this->itunes_categories[] = $val;
        } else {
            $this->categories[] = $val;
        }
        return(TRUE);
    }

    public function addCopyright( $val ) {
        $this->changed = TRUE;
        $this->xmlFeed->channel->copyright = $val;
        $this->copyright = $val;
        return(TRUE);
    }

    public function xml( $pretty = FALSE) {
        $this->buildFeedObject();

        //Output the xml
        if($pretty) {
            $dom = new DOMDocument("1.0");
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($this->xmlFeed->asXML());
            return $dom->saveXML();
        } else {
            return $this->xmlFeed->asXML();
        }
    }

    public function xmlObject() {
        return $this->xmlFeed;
    }

    public function domObject() {
        $this->buildFeedObject();
        return dom_import_simplexml($this->xmlFeed);
    }

    public function purgeFeed() {
        //Remove all of the itunes stuff
        $this->removeNodes("", $this->itunes_ns);
        //Remove the category node
        $this->removeNodes("category");
        //Remove the items
        $this->removeNodes("item");
        //Remove pubDate
        $this->removeNodes("pubDate");
        $this->removeNodes("lastBuildDate");
    }

    protected function buildFeedObject() {
        //Clean the feed before rebuilding
        if($this->built_once) $this->purgeFeed();

        //Update pubdate
        $this->pubDate = $this->pubDate();

        //Add the required channel elements
        $this->xmlFeed->channel->title = $this->title;
        $this->xmlFeed->channel->description = $this->description;
        $this->xmlFeed->channel->link = $this->link;
        //Add categories
        $ctg = "";
        foreach( $this->categories as $cat ) {
            $ctg = $ctg . " " . $cat;
        }
        if(!empty($ctg)) {
            $this->xmlFeed->channel->category = trim($ctg);
        }
        //Copyright
        if(!empty($this->copyright)) {
            $this->xmlFeed->channel->copyright = $this->copyright;
        }
        //Spec stuff
        $this->xmlFeed->channel->docs = $this->docs;
        $this->xmlFeed->channel->language = $this->language;

        //Dates
        $this->xmlFeed->channel->pubDate = $this->pubDate;
        if($this->changed) {
            $this->xmlFeed->channel->lastBuildDate = $this->pubDate;
        } else {
            $this->xmlFeed->channel->lastBuildDate = $this->lastBuildDate;
        }

        //Names
        if(!empty($this->managingEditor)) {
            $this->xmlFeed->channel->managingEditor = $this->managingEditor;
        }
        if(!empty($this->managingEditor) || !empty($this->webMaster)) {
            if(empty($this->webMaster)) {
                $this->webMaster = $this->managingEditor;
            }
            $this->xmlFeed->channel->webMaster = $this->webMaster;
        }
        //System
        $this->xmlFeed->channel->generator = $this->generator;
        if(!empty($this->itunes_owner['email']) || !empty($this->managingEditor)) {
            $this->xmlFeed->channel->addChild('owner', "", "http://www.itunes.com/dtds/podcast-1.0.dtd");
            if(empty($this->itunes_owner['email'])) {
                $this->itunes_owner['email'] = $this->managingEditor;
            }
            $this->xmlFeed->channel->children('itunes', TRUE)->owner->email = $this->itunes_owner['email'];
        }
        if(!empty($this->itunes_owner['name'])) {
            $this->xmlFeed->channel->children('itunes', TRUE)->owner->name = $this->itunes_owner['name'];
        }
        //Album art
        if(!empty($this->itunes_image) || !empty($this->image['url'])) {
            if(empty($this->image['url'])) {
                $this->image['url'] = $this->itunes_image;
            }
            $this->xmlFeed->channel->image->url = $this->image['url'];
            if(empty($this->image['title'])) {
                $this->image['title'] = $this->title;
            }
            $this->xmlFeed->channel->image->title = $this->image['title'];
            if(empty($this->image['link'])) {
                $this->image['link'] = $this->link;
            }
            $this->xmlFeed->channel->image->link = $this->image['link'];
            if(empty($this->image['description'])) {
                $this->image['description'] = $this->description;
            }
            $this->xmlFeed->channel->image->description = $this->image['description'];
            if(empty($this->image['width']) || empty($this->image['height']) ) {
                list($width, $height, $type, $attr) = getimagesize($this->image['url']);
                $this->image['width'] = $width;
                $this->image['height'] = $height;
            }
            $this->xmlFeed->channel->image->width = $this->image['width'];
            $this->xmlFeed->channel->image->height = $this->image['height'];
        }

        //Itunes stuff
        if(!empty($this->itunes_subtitle)) {
            $this->xmlFeed->channel->addChild('subtitle', "", $this->itunes_ns);
            $this->xmlFeed->channel->children('itunes', TRUE)->subtitle = $this->itunes_subtitle;
        }

        $this->xmlFeed->channel->addChild('summary', "", $this->itunes_ns);
        if(empty($this->itunes_summary)) {
            $this->itunes_summary = $this->description;
        }
        $this->xmlFeed->channel->children('itunes', TRUE)->summary = $this->itunes_summary;

        if(!empty($this->itunes_author) || !empty($this->managingEditor)) {
            $this->xmlFeed->channel->addChild('author', "", $this->itunes_ns);
            if (empty($this->itunes_author)) {
                $this->itunes_author = $this->managingEditor;
            }
            $this->xmlFeed->channel->children('itunes', TRUE)->author = $this->itunes_author;
        }

        if(!empty($this->itunes_image) || !empty($this->image['url'])) {
            $this->xmlFeed->channel->addChild('image', "", $this->itunes_ns);
            if(empty($this->itunes_image)) {
                $this->itunes_image = $this->image['url'];
            }
            $this->xmlFeed->channel->children('itunes', TRUE)->image['href'] = $this->itunes_image;
        }

        $this->xmlFeed->channel->addChild('explicit', "", $this->itunes_ns);
        $this->xmlFeed->channel->children('itunes', TRUE)->explicit = $this->itunes_explicit;

        //Itunes keywords
        if(!empty($this->itunes_keywords)) {
            $itk = "";
            foreach($this->itunes_keywords as $kw) {
                $itk = $itk . " " . $kw;
            }
            $this->xmlFeed->channel->addChild('keywords', "", $this->itunes_ns);
            $this->xmlFeed->channel->children('itunes', TRUE)->keywords = trim($itk);
        }

        //Itunes categories
        if(empty($this->itunes_categories)) {
            //Split category value into an array and add as itunes_category values
            $this->itunes_categories = $this->categories;
        }
        $count = 0;
        foreach($this->itunes_categories as $cat) {
            $this->xmlFeed->channel->addChild('category', "", $this->itunes_ns);
            $this->xmlFeed->channel->children('itunes', TRUE)->category[$count] = $cat;
            $count++;
        }

        //Add all of the items
        foreach($this->items as $item) {
            $this->addItem($item);
            $this->lastBuildDate = $item->pubDate;
        }

        //We built the feed
        $this->built_once = TRUE;

        //Reset change track
        $this->changed = FALSE;



        return(TRUE);
    }
}