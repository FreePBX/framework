<?php

namespace PicoFeed\Parser;

use SimpleXMLElement;
use PicoFeed\Filter\Filter;
use PicoFeed\Client\Url;

/**
 * Atom parser
 *
 * @author  Frederic Guillot
 * @package Parser
 */
class Atom extends Parser
{
    /**
     * Supported namespaces
     */
    protected $namespaces = array(
        'atom' => 'http://www.w3.org/2005/Atom',
    );

    /**
     * Get the path to the items XML tree
     *
     * @access public
     * @param  SimpleXMLElement   $xml   Feed xml
     * @return SimpleXMLElement
     */
    public function getItemsTree(SimpleXMLElement $xml)
    {
        return XmlParser::getXPathResult($xml, 'atom:entry', $this->namespaces)
               ?: XmlParser::getXPathResult($xml, 'entry');
    }

    /**
     * Find the feed url
     *
     * @access public
     * @param  SimpleXMLElement          $xml     Feed xml
     * @param  \PicoFeed\Parser\Feed     $feed    Feed object
     */
    public function findFeedUrl(SimpleXMLElement $xml, Feed $feed)
    {
        $feed->feed_url = $this->getUrl($xml, 'self');
    }

    /**
     * Find the site url
     *
     * @access public
     * @param  SimpleXMLElement          $xml     Feed xml
     * @param  \PicoFeed\Parser\Feed     $feed    Feed object
     */
    public function findSiteUrl(SimpleXMLElement $xml, Feed $feed)
    {
        $feed->site_url = $this->getUrl($xml, 'alternate', true);
    }

    /**
     * Find the feed description
     *
     * @access public
     * @param  SimpleXMLElement          $xml     Feed xml
     * @param  \PicoFeed\Parser\Feed     $feed    Feed object
     */
    public function findFeedDescription(SimpleXMLElement $xml, Feed $feed)
    {
        $description = XmlParser::getXPathResult($xml, 'atom:subtitle', $this->namespaces)
                       ?: XmlParser::getXPathResult($xml, 'subtitle');

        $feed->description = (string) current($description);
    }

    /**
     * Find the feed logo url
     *
     * @access public
     * @param  SimpleXMLElement          $xml     Feed xml
     * @param  \PicoFeed\Parser\Feed     $feed    Feed object
     */
    public function findFeedLogo(SimpleXMLElement $xml, Feed $feed)
    {
        $logo = XmlParser::getXPathResult($xml, 'atom:logo', $this->namespaces)
                ?: XmlParser::getXPathResult($xml, 'logo');

        $feed->logo = (string) current($logo);
    }

    /**
     * Find the feed icon
     *
     * @access public
     * @param  SimpleXMLElement          $xml     Feed xml
     * @param  \PicoFeed\Parser\Feed     $feed    Feed object
     */
    public function findFeedIcon(SimpleXMLElement $xml, Feed $feed)
    {
        $icon = XmlParser::getXPathResult($xml, 'atom:icon', $this->namespaces)
                ?: XmlParser::getXPathResult($xml, 'icon');

        $feed->icon = (string) current($icon);
    }

    /**
     * Find the feed title
     *
     * @access public
     * @param  SimpleXMLElement          $xml     Feed xml
     * @param  \PicoFeed\Parser\Feed     $feed    Feed object
     */
    public function findFeedTitle(SimpleXMLElement $xml, Feed $feed)
    {
        $title = XmlParser::getXPathResult($xml, 'atom:title', $this->namespaces)
                ?: XmlParser::getXPathResult($xml, 'title');

        $feed->title = Filter::stripWhiteSpace((string) current($title)) ?: $feed->getSiteUrl();
    }

    /**
     * Find the feed language
     *
     * @access public
     * @param  SimpleXMLElement          $xml     Feed xml
     * @param  \PicoFeed\Parser\Feed     $feed    Feed object
     */
    public function findFeedLanguage(SimpleXMLElement $xml, Feed $feed)
    {
        $language = XmlParser::getXPathResult($xml, '*[not(self::atom:entry)]/@xml:lang', $this->namespaces)
                    ?: XmlParser::getXPathResult($xml, '@xml:lang');

        $feed->language = (string) current($language);
    }

    /**
     * Find the feed id
     *
     * @access public
     * @param  SimpleXMLElement          $xml     Feed xml
     * @param  \PicoFeed\Parser\Feed     $feed    Feed object
     */
    public function findFeedId(SimpleXMLElement $xml, Feed $feed)
    {
        $id = XmlParser::getXPathResult($xml, 'atom:id', $this->namespaces)
              ?: XmlParser::getXPathResult($xml, 'id');

        $feed->id = (string) current($id);
    }

    /**
     * Find the feed date
     *
     * @access public
     * @param  SimpleXMLElement          $xml     Feed xml
     * @param  \PicoFeed\Parser\Feed     $feed    Feed object
     */
    public function findFeedDate(SimpleXMLElement $xml, Feed $feed)
    {
        $updated = XmlParser::getXPathResult($xml, 'atom:updated', $this->namespaces)
                   ?: XmlParser::getXPathResult($xml, 'updated');

        $feed->date = $this->date->getDateTime((string) current($updated));
    }

    /**
     * Find the item date
     *
     * @access public
     * @param  SimpleXMLElement          $entry   Feed item
     * @param  Item                      $item    Item object
     * @param  \PicoFeed\Parser\Feed     $feed    Feed object
     */
    public function findItemDate(SimpleXMLElement $entry, Item $item, Feed $feed)
    {
        $published = XmlParser::getXPathResult($entry, 'atom:published', $this->namespaces)
                     ?: XmlParser::getXPathResult($entry, 'published');

        $updated = XmlParser::getXPathResult($entry, 'atom:updated', $this->namespaces)
                   ?: XmlParser::getXPathResult($entry, 'updated');

        $published = ! empty($published) ? $this->date->getDateTime((string) current($published)) : null;
        $updated = ! empty($updated) ? $this->date->getDateTime((string) current($updated)) : null;

        if ($published === null && $updated === null) {
            $item->date = $feed->getDate(); // We use the feed date if there is no date for the item
        }
        else if ($published !== null && $updated !== null) {
            $item->date = max($published, $updated); // We use the most recent date between published and updated
        }
        else {
            $item->date = $updated ?: $published;
        }
    }

    /**
     * Find the item title
     *
     * @access public
     * @param  SimpleXMLElement   $entry   Feed item
     * @param  Item               $item    Item object
     */
    public function findItemTitle(SimpleXMLElement $entry, Item $item)
    {
        $title = XmlParser::getXPathResult($entry, 'atom:title', $this->namespaces)
                 ?: XmlParser::getXPathResult($entry, 'title');

        $item->title = Filter::stripWhiteSpace((string) current($title)) ?: $item->url;
    }

    /**
     * Find the item author
     *
     * @access public
     * @param  SimpleXMLElement          $xml     Feed
     * @param  SimpleXMLElement          $entry   Feed item
     * @param  \PicoFeed\Parser\Item     $item    Item object
     */
    public function findItemAuthor(SimpleXMLElement $xml, SimpleXMLElement $entry, Item $item)
    {
        $author = XmlParser::getXPathResult($entry, 'atom:author/atom:name', $this->namespaces)
                  ?: XmlParser::getXPathResult($entry, 'author/name')
                  ?: XmlParser::getXPathResult($xml, 'atom:author/atom:name', $this->namespaces)
                  ?: XmlParser::getXPathResult($xml, 'author/name');

        $item->author = (string) current($author);
    }

    /**
     * Find the item content
     *
     * @access public
     * @param  SimpleXMLElement   $entry   Feed item
     * @param  \PicoFeed\Parser\Item     $item    Item object
     */
    public function findItemContent(SimpleXMLElement $entry, Item $item)
    {
        $item->content = $this->getContent($entry);
    }

    /**
     * Find the item URL
     *
     * @access public
     * @param  SimpleXMLElement   $entry   Feed item
     * @param  \PicoFeed\Parser\Item     $item    Item object
     */
    public function findItemUrl(SimpleXMLElement $entry, Item $item)
    {
        $item->url = $this->getUrl($entry, 'alternate', true);
    }

    /**
     * Genereate the item id
     *
     * @access public
     * @param  SimpleXMLElement   $entry   Feed item
     * @param  \PicoFeed\Parser\Item     $item    Item object
     * @param  \PicoFeed\Parser\Feed     $feed    Feed object
     */
    public function findItemId(SimpleXMLElement $entry, Item $item, Feed $feed)
    {
        $id = XmlParser::getXPathResult($entry, 'atom:id', $this->namespaces)
                  ?: XmlParser::getXPathResult($entry, 'id');

        if (! empty($id)) {
            $item->id = $this->generateId((string) current($id));
        }
        else {
            $item->id = $this->generateId(
                $item->getTitle(), $item->getUrl(), $item->getContent()
            );
        }
    }

    /**
     * Find the item enclosure
     *
     * @access public
     * @param  SimpleXMLElement   $entry   Feed item
     * @param  \PicoFeed\Parser\Item     $item    Item object
     * @param  \PicoFeed\Parser\Feed     $feed    Feed object
     */
    public function findItemEnclosure(SimpleXMLElement $entry, Item $item, Feed $feed)
    {
        $enclosure = $this->findLink($entry, 'enclosure');

        if ($enclosure) {
            $item->enclosure_url = Url::resolve((string) $enclosure['href'], $feed->getSiteUrl());
            $item->enclosure_type = (string) $enclosure['type'];
        }
    }

    /**
     * Find the item language
     *
     * @access public
     * @param  SimpleXMLElement   $entry   Feed item
     * @param  \PicoFeed\Parser\Item     $item    Item object
     * @param  \PicoFeed\Parser\Feed     $feed    Feed object
     */
    public function findItemLanguage(SimpleXMLElement $entry, Item $item, Feed $feed)
    {
        $language = XmlParser::getXPathResult($entry, './/@xml:lang');

        $item->language = (string) current($language) ?: $feed->language;
    }

    /**
     * Get the URL from a link tag
     *
     * @access private
     * @param  SimpleXMLElement   $xml      XML tag
     * @param  string             $rel      Link relationship: alternate, enclosure, related, self, via
     * @return string
     */
    private function getUrl(SimpleXMLElement $xml, $rel, $fallback = false)
    {
        $link = $this->findLink($xml, $rel);

        if ($link) {
            return (string) $link['href'];
        }

        if ($fallback) {
            $link = $this->findLink($xml, '');
            return $link ? (string) $link['href'] : '';
        }

        return '';
    }

    /**
     * Get a link tag that match a relationship
     *
     * @access private
     * @param  SimpleXMLElement   $xml      XML tag
     * @param  string             $rel      Link relationship: alternate, enclosure, related, self, via
     * @return SimpleXMLElement|null
     */
    private function findLink(SimpleXMLElement $xml, $rel)
    {
        $links = XmlParser::getXPathResult($xml, 'atom:link', $this->namespaces)
                ?: XmlParser::getXPathResult($xml, 'link');

        foreach ($links as $link) {
            if ($rel === (string) $link['rel']) {
                return $link;
            }
        }

        return null;
    }

    /**
     * Get the entry content
     *
     * @access private
     * @param  SimpleXMLElement   $entry   XML Entry
     * @return string
     */
    private function getContent(SimpleXMLElement $entry)
    {
        $content = current(
            XmlParser::getXPathResult($entry, 'atom:content', $this->namespaces)
            ?: XmlParser::getXPathResult($entry, 'content')
        );

        if (! empty($content) && count($content->children())) {
            $xml_string = '';

            foreach($content->children() as $child) {
                $xml_string .= $child->asXML();
            }

            return $xml_string;
        }
        else if (trim((string) $content) !== '') {
            return (string) $content;
        }

        $summary = XmlParser::getXPathResult($entry, 'atom:summary', $this->namespaces)
                   ?: XmlParser::getXPathResult($entry, 'summary');

        return (string) current($summary);
    }
}
