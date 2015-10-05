<?php

namespace PicoFeed\Reader;

use DOMXPath;
use PicoFeed\Config\Config;
use PicoFeed\Client\Client;
use PicoFeed\Client\Url;
use PicoFeed\Logging\Logger;
use PicoFeed\Parser\XmlParser;

/**
 * Reader class
 *
 * @author  Frederic Guillot
 * @package Reader
 */
class Reader
{
    /**
     * Feed formats for detection
     *
     * @access private
     * @var array
     */
    private $formats = array(
        'Atom' => '//feed',
        'Rss20' => '//rss[@version="2.0"]',
        'Rss92' => '//rss[@version="0.92"]',
        'Rss91' => '//rss[@version="0.91"]',
        'Rss10' => '//rdf',
    );

    /**
     * Config class instance
     *
     * @access private
     * @var \PicoFeed\Config\Config
     */
    private $config;

    /**
     * Constructor
     *
     * @access public
     * @param  \PicoFeed\Config\Config   $config   Config class instance
     */
    public function __construct(Config $config = null)
    {
        $this->config = $config ?: new Config;
        Logger::setTimezone($this->config->getTimezone());
    }

    /**
     * Download a feed (no discovery)
     *
     * @access public
     * @param  string            $url              Feed url
     * @param  string            $last_modified    Last modified HTTP header
     * @param  string            $etag             Etag HTTP header
     * @param  string            $username         HTTP basic auth username
     * @param  string            $password         HTTP basic auth password
     * @return \PicoFeed\Client\Client
     */
    public function download($url, $last_modified = '', $etag = '', $username = '', $password = '')
    {
        $url = $this->prependScheme($url);

        return Client::getInstance()
                        ->setConfig($this->config)
                        ->setLastModified($last_modified)
                        ->setEtag($etag)
                        ->setUsername($username)
                        ->setPassword($password)
                        ->execute($url);
    }

    /**
     * Discover and download a feed
     *
     * @access public
     * @param  string            $url              Feed or website url
     * @param  string            $last_modified    Last modified HTTP header
     * @param  string            $etag             Etag HTTP header
     * @param  string            $username         HTTP basic auth username
     * @param  string            $password         HTTP basic auth password
     * @return \PicoFeed\Client\Client
     */
    public function discover($url, $last_modified = '', $etag = '', $username = '', $password = '')
    {
        $client = $this->download($url, $last_modified, $etag, $username, $password);

        // It's already a feed or the feed was not modified
        if (! $client->isModified() || $this->detectFormat($client->getContent())) {
            return $client;
        }

        // Try to find a subscription
        $links = $this->find($client->getUrl(), $client->getContent());

        if (empty($links)) {
            throw new SubscriptionNotFoundException('Unable to find a subscription');
        }

        return $this->download($links[0], $last_modified, $etag, $username, $password);
    }

    /**
     * Find feed urls inside a HTML document
     *
     * @access public
     * @param  string    $url        Website url
     * @param  string    $html       HTML content
     * @return array                 List of feed links
     */
    public function find($url, $html)
    {
        Logger::setMessage(get_called_class().': Try to discover subscriptions');

        $dom = XmlParser::getHtmlDocument($html);
        $xpath = new DOMXPath($dom);
        $links = array();

        $queries = array(
            '//link[@type="application/rss+xml"]',
            '//link[@type="application/atom+xml"]',
        );

        foreach ($queries as $query) {

            $nodes = $xpath->query($query);

            foreach ($nodes as $node) {

                $link = $node->getAttribute('href');

                if (! empty($link)) {

                    $feedUrl = new Url($link);
                    $siteUrl = new Url($url);

                    $links[] = $feedUrl->getAbsoluteUrl($feedUrl->isRelativeUrl() ? $siteUrl->getBaseUrl() : '');
                }
            }
        }

        Logger::setMessage(get_called_class().': '.implode(', ', $links));

        return $links;
    }

    /**
     * Get a parser instance
     *
     * @access public
     * @param  string                $url          Site url
     * @param  string                $content      Feed content
     * @param  string                $encoding     HTTP encoding
     * @return \PicoFeed\Parser\Parser
     */
    public function getParser($url, $content, $encoding)
    {
        $format = $this->detectFormat($content);

        if (empty($format)) {
            throw new UnsupportedFeedFormatException('Unable to detect feed format');
        }

        $className = '\PicoFeed\Parser\\'.$format;

        $parser = new $className($content, $encoding, $url);
        $parser->setHashAlgo($this->config->getParserHashAlgo());
        $parser->setTimezone($this->config->getTimezone());
        $parser->setConfig($this->config);

        return $parser;
    }

    /**
     * Detect the feed format
     *
     * @access public
     * @param  string    $content     Feed content
     * @return string
     */
    public function detectFormat($content)
    {
        $dom = XmlParser::getHtmlDocument($content);
        $xpath = new DOMXPath($dom);

        foreach ($this->formats as $parser_name => $query) {
            $nodes = $xpath->query($query);

            if ($nodes->length === 1) {
                return $parser_name;
            }
        }

        return '';
    }

    /**
     * Add the prefix "http://" if the end-user just enter a domain name
     *
     * @access public
     * @param  string    $url    Url
     * @retunr string
     */
    public function prependScheme($url)
    {
        if (! preg_match('%^https?://%', $url)) {
           $url = 'http://' . $url;
        }

        return $url;
    }
}
