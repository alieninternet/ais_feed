# ais_feed - A simple feed syndication plugin for Textpattern

## Features

* Support for Atom and RSS feeds
* Allow querying any field in the feed with XPath queries
* Feed data can be formatted as needed
* No dependencies - no need for libraries, making installation simple
* Caching of feeds (file based)

## Example

The following example shows how the feed can iterate over a feed, returning at most 5 items, output a custom field, and perform simple logic based on an XPath query.

```html
<txp:ais_feed feed="http://example.com/feed.xml" limit="5">
   <article class="article feed">
      <h2>
         <txp:ais_feed_item_link>
            <txp:ais_feed_item_title />
         </txp:ais_feed_item_link>
      </h2>
      <p>Posted: <txp:ais_feed_item_posted format="%B %Y" /></p>
      <p><txp:ais_feed_item_xpath xpath="./custom:field" /></p>
      <txp:ais_feed_item_if_xpath xpath="./custom:something[@foo='1']">
         <p>Something happens when XPath query is true</p>
      <txp:else />
         <p>Something different happens when not true</p>
      </txp:ais_feed_item_if_xpath>
   </article>
</txp:ais_feed>
```

Full instructions are available [here](help.textile), as well as included with the plugin, and includes tag instructions and practical examples.
