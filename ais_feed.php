<?php
/**
 * ais_feed - Feed syndication for Textpattern
 * 
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * 
 * @author	Ashley Butcher
 * @copyright   Copyright (c) 2024 Alien Internet Services
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3
 * @version	0.2
 * @link	https://github.com/alieninternet/ais_feed/
 */


// Test mode of operation
switch (txpinterface) {
 case 'public':
    /**
     * Fetch a feed
     *
     * @param  array  $atts  Tag attribute name-value pairs
     * @param  string $thing Contained content
     * @return string
     */
    function ais_feed(array $atts, ?string $thing = null): string
    {
	extract(lAtts(array(
	    'cache' => '3600', // Cache time (default = 1 hours)
	    'feed' => '',      // Feed URL
	    'limit' => ''      // Maximum number of items to dump
	), $atts));

	if (isset($feed) &&
	    !empty($feed)) {
	    $feed = ais_feed::newFromURL($feed, intval($cache));

	    if (is_object($feed)) {
		// Container mode?
		if (isset($thing)) {
		    if (ais_feed_state::inFeed()) {
			if ($production_status !== 'live') {
			    echo gTxt("ais_feed_nested");
			}
			return parse($thing, false);
		    }
		    
		    // Load feed into global state
		    ais_feed_state::setFeed($feed);
		    $result = '';
		    
		    if (is_numeric($limit)) {
			$limit = intval($limit);
		    }
		    
		    // Loop through articles
		    foreach ($feed as $article) {
			$result .= parse($thing, true);
			
			if (is_int($limit)) {
			    --$limit;
			    if ($limit == 0) {
				break;
			    }
			}
		    }
		    
		    // Unset the feed to restore the state
		    ais_feed_state::unsetFeed();
		    
		    return $result;
		}
		
		// Single tag mode - return the feed's name (title)
		return $feed->getTitle();
	    } else if (isset($thing)) {
		return parse($thing, false);
	    }
	} else if ($production_status !== 'live') {
	    echo gTxt('ais_feed_missing_feed_url');
	}
	
	return '';
    }

    
    /**
     * Fetch a feed item ID
     *
     * @param  array  $atts  Tag attribute name-value pairs
     * @return string
     */
    function ais_feed_item_id(array $atts) : string
    {
	// This is only useful in a feed context
	if (ais_feed_state::inFeed()) {
	    return ais_feed_state::getFeed()->getItemID();
	}

	// Output error if not in live mode
	if ($production_status !== 'live') {
	    echo gTxt('ais_feed_tag_no_feed',
		      ['tag' => 'ais_feed_item_id']);
	}
	
	return '';
    }
    
    
    /**
     * Conditional tag based on an XPath query
     *
     * @param  array  $atts  Tag attribute name-value pairs
     * @param  string $thing Contained content
     * @return string
     */
    function ais_feed_item_if_xpath(array $atts, ?string $thing = null) : string
    {
	extract(lAtts(array(
	    'xpath' => ''    // XPath query
	), $atts));
	
	// This is only useful in a feed context
	if (ais_feed_state::inFeed()) {
	    if (isset($xpath)) {
		return parse($thing, ais_feed_state::getFeed()->testItemXPath(strval($xpath)));
	    }
	    
	    // Output error if not in live mode
	    if ($production_status !== 'live') {
		echo gTxt('ais_feed_tag_missing_xpath',
			  ['tag' => 'ais_feed_item_if_xpath']);
	    }
	}
	
	// Output error if not in live mode
	if ($production_status !== 'live') {
	    echo gTxt('ais_feed_tag_no_feed',
		      ['tag' => 'ais_feed_item_if_xpath']);
	}

	return '';
    }
    
    
    /**
     * Fetch a feed item link
     *
     * @param  array  $atts  Tag attribute name-value pairs
     * @param  string $thing Contained content
     * @return string
     */
    function ais_feed_item_link(array $atts, ?string $thing = null) : string
    {
	extract(lAtts(array(
	    'class' => '',   // Class attribute
	    'id' => '',      // ID attribute
	    'style' => '',   // Inline CSS
	    'target' => ''   // Target frame
	), $atts));
	
	// This is only useful in a feed context
	if (ais_feed_state::inFeed()) {
	    $feed = ais_feed_state::getFeed();
	    
	    // Fetch URL for the article
	    $itemURL = $feed->getItemURL();
	    
	    if (filter_var($itemURL, FILTER_VALIDATE_URL) !== false) {
		// Container mode?
		if (isset($thing)) {
		    if (!empty($itemURL)) {
			// Open the anchor tag
			$result = ('<a href="' . $itemURL . '"');

			// Add item title if we have it
			$itemTitle = $feed->getItemTitle();
			if (isset($itemTitle) &&
			    !empty($itemTitle)) {
			    $result .= (' title="' . $itemTitle .'"');
			}
			
			// Add class if set
			if (isset($id) &&
			    is_string($id) &&
			    !empty($id)) {
			    $result .= (' id="' . $id . '"');
			}
			
			// Add class if set
			if (isset($class) &&
			    is_string($class) &&
			    !empty($class)) {
			    $result .= (' class="' . $class . '"');
			}
			
			// Add inline CSS if set
			if (isset($style) &&
			    is_string($style) &&
			    !empty($style)) {
			    $result .= (' style="' . $style . '"');
			}
			
			// Add target frame attribute if set
			if (isset($target) &&
			    is_string($target) &&
			    !empty($target)) {
			    $result .= (' target="' . $target . '"');
			}
			
			$result .= '>';
		    }
		    
		    // Parse the enclosed content
		    $result .= parse($thing, true);
		    
		    if (!empty($itemURL)) {
			// Close the tag
			$result .= '</a>';
		    }
		    
		    return $result;
		}
	    
		return $itemURL;
	    }
	}
	
	// If we are in container mode, attempt to parse but in negative mode
	if (isset($thing)) {
	    return parse($thing, false);
	}
	
	// Output error if not in live mode
	if ($production_status !== 'live') {
	    echo gTxt('ais_feed_tag_no_feed',
		      ['tag' => 'ais_feed_item_link']);
	}

	return '';
    }
    
    
    /**
     * Fetch a feed item posted timestamp
     *
     * @param  array  $atts  Tag attribute name-value pairs
     * @param  string $thing Contained content
     * @return string
     */
    function ais_feed_item_posted(array $atts, ?string $thing = null) : string
    {
	extract(lAtts(array(
	    'calendar' => '', // Calendar type
	    'format' => '',   // Explicit time format
	    'gmt' => '',      // Use GMT? If false, use local time instead.
	    'lang' => ''      // Language
	), $atts));
	
	// This is only useful in a feed context
	if (ais_feed_state::inFeed()) {
	    $time = ais_feed_state::getFeed()->getItemTimePosted();
	    
	    // Fix language - set it to the default if it's not yet set
	    if (!isset($lang)) {
		$lang = $lang = LANG;
	    }
	    
	    // If the calendar is prepared, append it properly on the language field
	    if ($calendar) {
		$lang = ($lang . '@calendar=' . $calendar);
	    }
	    
	    // Set the date format to the default if it is not set
	    if (!isset($format) || 
		empty($format)) {
		// This $dateformat is a global from Textpattern
		$format = $dateformat;
	    }
	    
	    return safe_strftime($format, $time, $gmt, $lang);
	}
	
	// Output error if not in live mode
	if ($production_status !== 'live') {
	    echo gTxt('ais_feed_tag_no_feed',
		      ['tag' => 'ais_feed_item_posted']);
	}
	
	return '';
    }
    
    
    /**
     * Fetch a feed item title
     *
     * @param  array  $atts  Tag attribute name-value pairs
     * @return string
     */
    function ais_feed_item_title(array $atts) : string
    {
	// This is only useful in a feed context
	if (ais_feed_state::inFeed()) {
	    return ais_feed_state::getFeed()->getItemTitle();
	}
	
	// Output error if not in live mode
	if ($production_status !== 'live') {
	    echo gTxt('ais_feed_tag_no_feed',
		      ['tag' => 'ais_feed_item_title']);
	}

	return '';
    }

    
    /**
     * Fetch a feed item xpath query
     *
     * @param  array  $atts  Tag attribute name-value pairs
     * @param  string $thing Contained content
     * @return string
     */
    function ais_feed_item_xpath(array $atts, ?string $thing = null) : string
    {
	extract(lAtts(array(
	    'xpath' => ''    // XPath query
	), $atts));
	
	// This is only useful in a feed context
	if (ais_feed_state::inFeed()) {
	    if (isset($xpath)) {
		return ais_feed_state::getFeed()->getItemXPath(strval($xpath));
	    }
	    
	    // Output error if not in live mode
	    if ($production_status !== 'live') {
		echo gTxt('ais_feed_tag_missing_xpath',
			  ['tag' => 'ais_feed_item_xpath']);
	    }
	}
	
	// Output error if not in live mode
	if ($production_status !== 'live') {
	    echo gTxt('ais_feed_tag_no_feed',
		      ['tag' => 'ais_feed_item_xpath']);
	}

	return '';
    }

    
    // Register tags
    if (class_exists('\Textpattern\Tag\Registry')) {
	\Txp::get('\Textpattern\Tag\Registry')
	  ->register('ais_feed')
	  ->register('ais_feed_item_if_xpath')
	  ->register('ais_feed_item_link')
	  ->register('ais_feed_item_posted')
	  ->register('ais_feed_item_title')
	  ->register('ais_feed_item_xpath');
    }
    
    break;
}


/**
 * Feed base support class
 * 
 * These is a monolithic design. It's not pretty, but it simplifies things considering
 * Textpattern's architecture is still very flat for plugins.
 */
abstract class ais_feed implements Iterator
{
    protected SimpleXMLElement $feedXML;
    protected ?string $title = null;
    protected ?string $itemID = null;
    protected ?int $itemTimePosted = null;
    protected ?string $itemTitle = null;
    protected ?string $itemURL = null;
    protected array $xpathCache = [];
    protected array $xpathTestCache = [];
    private Iterator $articleIterator;
    
    
    /**
     * Constructor
     *
     * @param  object $feedXML  The feed's XML
     */
    function __construct(SimpleXMLElement $feedXML)
    {
	$this->feedXML = $feedXML;

	$this->registerXPathNamespace($this->feedXML);
    }
    
    
    /**
     * Fetch an article iterator
     * 
     * This is done like this to save memory
     */
    abstract protected function fetchArticleIterator(): Iterator;

    
    /**
     * Fetch the title of the feed
     *
     * @return The title of the feed
     */
    abstract protected function fetchTitle(): string;

    
    /**
     * Fetch the current item's ID
     */
    abstract protected function fetchItemID(): string;

    
    /**
     * Fetch the current item's posted time (relative to the local timezone)
     */
    abstract protected function fetchItemTimePosted(): int;

    
    /**
     * Fetch the current item's title
     */
    abstract protected function fetchItemTitle(): string;
    
    
    /**
     * Fetch the current item's URL
     */
    abstract protected function fetchItemURL(): string;
    
    
    /**
     * Fetch a value from the current item via an xpath query
     */
    protected function fetchItemXPath(string $xpath): string
    {
	$xml = $this->current();
	
	if (isset($xml)) {
	    $this->registerXPathNamespace($xml);

	    $value = $xml->xpath($xpath);
	    
	    if (isset($value) &&
		is_array($value)) {
		return strval($value[0]);
	    }
	}

	return '';
    }
    
    
    /**
     * Get the title of the feed
     *
     * @return The title of the feed
     */
    public function getTitle(): string
    {
	if (!isset($this->title)) {
	    $this->title = $this->fetchTitle();
	}
	
	return $this->title;
    }

    
    /**
     * Get the current item's ID
     */
    public function getItemID(): string
    {
	if (!isset($this->itemID)) {
	    $this->itemID = $this->fetchItemID();
	}

	return $this->itemID;
    }

    
    /**
     * Get the current item's title (relative to local timezone)
     */
    public function getItemTimePosted(): int
    {
	if (!isset($this->itemTimePosted)) {
	    $this->itemTimePosted = $this->fetchItemTimePosted();
	}

	return $this->itemTimePosted;
    }
    
    
    /**
     * Get the current item's title
     */
    public function getItemTitle(): string
    {
	if (!isset($this->itemTitle)) {
	    $this->itemTitle = $this->fetchItemTitle();
	}

	return $this->itemTitle;
    }
    
    
    /**
     * Get the current item's URL
     */
    public function getItemURL(): string
    {
	if (!isset($this->itemURL)) {
	    $this->itemURL = $this->fetchItemURL();
	}

	return $this->itemURL;
    }
    
    
    /**
     * Get a value from the current item using an XPath query
     */
    public function getItemXPath(string $xpath): string
    {
	if (!array_key_exists($xpath, $this->xpathCache)) {
	    $this->xpathCache[$xpath] = $this->fetchItemXPath($xpath);
	}
	
	return $this->xpathCache[$xpath];
    }

    
    /**
     * Fetch a value from the current item via an xpath query
     */
    protected function existItemXPath(string $xpath): bool
    {
	$xml = $this->current();
	
	if (isset($xml)) {
	    $this->registerXPathNamespace($xml);

	    $value = $xml->xpath($xpath);

	    if (isset($value) &&
		($value !== false)) {
		return true;
	    }
	}

	return false;
    }

    
    /**
     * Test a value from the current item based on an XPath query
     */
    public function testItemXPath(string $xpath): bool
    {
	if (!array_key_exists($xpath, $this->xpathTestCache)) {
	    $this->xpathTestCache[$xpath] = $this->existItemXPath($xpath);
	}
	
	return $this->xpathTestCache[$xpath];
    }
    
    
    /**
     * Register xpath namespace(s)
     */
    protected function registerXPathNamespace(SimpleXMLElement &$xml)
    {
    }
    
    
    /**
     * Rewind to the first iteration
     */
    public function rewind(): void
    {
	$this->articleIterator = $this->fetchArticleIterator();
	$this->articleIterator->rewind();
    }
  
    
    /**
     * Fetch the data at the current iteration
     * 
     * @return Data at the current iteration
     */
    public function current(): SimpleXMLElement
    {
	return $this->articleIterator->current();
    }
    
    
    /**
     * Fetch the key for the iteration
     * 
     * @return The key for the current iteration
     */
    public function key(): int
    {
	return $this->articleIterator->key();
    }
    
    
    /**
     * Move to the next iteration
     */
    public function next(): void
    {
	$this->articleIterator->next();
	$this->resetItemVars();
    }
  
    
    /**
     * Check if this iteration is valid
     */
    public function valid(): bool
    {
	return $this->articleIterator->valid();
    }
    
    
    /**
     * Reset item variables
     */
    public function resetItemVars()
    {
	$this->itemID = null;
	$this->itemTimePosted = null;
	$this->itemTitle = null;
	$this->itemURL = null;
	$this->xpathCache = [];
	$this->xpathTestCache = [];
    }
    
        
    /**
     * Construct a new feed support class from a feed URL
     *
     * @param  object $feedURL  The feed URL
     * @return ais_feed object
     */
    public static function newFromURL(string $feedURL, int $cacheTime = 3600): ?ais_feed
    {
	$errorMessage = '';

	if (function_exists('simplexml_load_file')) {
	    if (filter_var($feedURL, FILTER_VALIDATE_URL) !== false) {
		// Calculate a cache file name
		$cacheFile = ($GLOBALS['tempdir'] . DIRECTORY_SEPARATOR . 'txp_ais_feed_' . md5($feedURL));
		
		// Do we have cached content? Is it fresh enough?
		if (file_exists($cacheFile) && 
		    ((filemtime($cacheFile) + $cacheTime) > time())) {
		    // Good! Read in from file
		    $feedContent = file_get_contents($cacheFile);
		} else {
		    // No. Load XML content directly from the URL
		    $feedContent = file_get_contents($feedURL);
		    
		    // Store cache. The temporary file here is to avoid locks and partial concurrent reads if the write to the disk is slow
		    $cacheFileTmp = ($cacheFile . '.' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, 10));
		    file_put_contents($cacheFileTmp, $feedContent, LOCK_EX);
		    rename($cacheFileTmp, $cacheFile);
		}
		
		if (isset($feedContent) &&
		    is_string($feedContent) &&
		    ($feedContent !== false)) {
		    $feedXML = simplexml_load_string($feedContent);
		    if ($feedXML !== false) {
			// Check if this is an Atom feed
			if (($feedXML->getName() == 'feed') && 
			    in_array('http://www.w3.org/2005/Atom', $feedXML->getDocNamespaces(), true)) {
			    return new ais_feed_atom($feedXML);
			}
			
			// Check if this is an RSS feed - not sure if there is a more reliable way
			// to detect this since RSS is usually fairly wishy-washy and implementations
			// vary wildly.
			if ($feedXML->getName() == 'rss') {
			    return new ais_feed_rss($feedXML);
			}
			
			$errorMessage = gTxt('ais_feed_unknown_type');
		    } else {
			$errorMessage = gTxt('ais_feed_malformed_xml');
		    }
		} else {
		    $errorMessage = gTxt('ais_feed_url_load_failed');
		}
	    } else {
		$errorMessage = gTxt('ais_feed_malformed_url');
	    }
	} else {
	    $errorMessage = gTxt('ais_feed_missing_simplexml');
	}
	
	// Output any error message if we are not live
	if (!empty($errorMessage) &&
	    ($production_status !== 'live')) {
	    echo $errorMessage;
	}
	
	return null;
    }
}


/**
 * Atom feed support class
 */
class ais_feed_atom extends ais_feed
{
    /**
     * Constructor
     *
     * @param  object $feedXML  The XML feed
     * @return ais_feed
     */
    function __construct(SimpleXMLElement $feedXML)
    {
	parent::__construct($feedXML);
    }

    
    /**
     * Fetch an article iterator
     * 
     * This is done like this to save memory
     */
    protected function fetchArticleIterator(): Iterator
    {
	$result = $this->feedXML->xpath('/atom:feed/atom:entry');
	
	if (is_array($result)) {
	    return new ArrayIterator($result);
	}
	
        return new ArrayIterator([]);
    }
    

    /**
     * Fetch the title from the feed
     *
     * @return The title of the feed
     */
    protected function fetchTitle(): string
    {
	$result = $this->feedXML->xpath('/atom:feed/atom:title');

	if (is_array($result)) {
	    return strval($result[0]);
	}
	
	return '';
    }

    
    /**
     * Fetch the current item's ID
     */
    protected function fetchItemID(): string
    {
	return $this->fetchItemXPath('./atom:id');
    }

    
    /**
     * Fetch the current item's posted time (relative to the local timezone)
     */
    protected function fetchItemTimePosted(): int
    {
	$timeString = $this->fetchItemXPath('./atom:published');
	
	if (is_string($timeString)) {
	    $time = strtotime($timeString);
	    
	    if (is_int($time)) {
		return $time;
	    }
	}
	
	return -1;
    }

    
    /**
     * Fetch the current item's title
     */
    protected function fetchItemTitle(): string
    {
	return $this->fetchItemXPath('./atom:title');
    }
    
    
    /**
     * Fetch the current item's URL
     */
    protected function fetchItemURL(): string
    {
	return $this->fetchItemXPath('./atom:link/@href');
    }
    
    
    /**
     * Register xpath namespace(s)
     */
    protected function registerXPathNamespace(SimpleXMLElement &$xml)
    {
	$xml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
    }
}


/**
 * RSS feed support class
 */
class ais_feed_rss extends ais_feed
{
    /**
     * Constructor
     *
     * @param  object $feedXML  The RSS feed XML
     */
    function __construct(SimpleXMLElement $feedXML)
    {
	parent::__construct($feedXML);
    }

    
    /**
     * Fetch an article iterator
     * 
     * This is done like this to save memory
     */
    protected function fetchArticleIterator(): Iterator
    {
	$result = $this->feedXML->xpath('/rss/channel/item');
	
	if (is_array($result)) {
	    return new ArrayIterator($result);
	}
	
        return new ArrayIterator([]);
    }


    /**
     * Fetch the title of the feed
     *
     * @return The title of the feed
     */
    protected function fetchTitle(): string
    {
	if (isset($this->feedXML->channel) &&
	    isset($this->feedXML->channel->title)) {
	    return strval($this->feedXML->channel->title);
	}
	
	return '';
    }

    
    /**
     * Fetch the current item's ID
     */
    protected function fetchItemID(): string
    {
	return $this->fetchItemXPath('./guid');
    }

    
    /**
     * Fetch the current item's posted time (relative to the local timezone)
     */
    protected function fetchItemTimePosted(): int
    {
	$timeString = $this->fetchItemXPath('./pubDate');
	
	if (is_string($timeString)) {
	    $time = strtotime($timeString);
	    
	    if (is_int($time)) {
		return $time;
	    }
	}
	
	return -1;
    }

    
    /**
     * Fetch the current item's title
     */
    protected function fetchItemTitle(): string
    {
	return $this->fetchItemXPath('./title');
    }
    
    
    /**
     * Fetch the current item's URL
     */
    protected function fetchItemURL(): string
    {
	return $this->fetchItemXPath('./link');
    }
}


/**
 * Feed state singleton
 */
class ais_feed_state {
    private static ?ais_feed_state $instance = null;
    
    private ?ais_feed $feed = null;
    
    /**
     * Constructor
     */
    private function __construct()
    {
    }
    
    
    /**
     * Get an instance of this singleton
     */
    private static function getInstance(): ais_feed_state
    {
	if (self::$instance === null)
	{
	    self::$instance = new ais_feed_state();
	}
	
	return self::$instance;
    }
    
    
    /**
     * Get the feed
     */
    public static function getFeed(): ?ais_feed
    {
	return self::getInstance()->feed;
    }
    
    
    /**
     * Determine if we are in a feed or not
     */
    public static function inFeed(): bool
    {
	return (self::getFeed() !== null);
    }
    
    
    /**
     * Set the feed
     */
    public static function setFeed(ais_feed $feed): void
    {
	self::getInstance()->feed = $feed;
    }
    
    
    /**
     * Unset the feed
     */
    public static function unsetFeed(): void
    {
	self::getInstance()->feed = null;
    }
      
}