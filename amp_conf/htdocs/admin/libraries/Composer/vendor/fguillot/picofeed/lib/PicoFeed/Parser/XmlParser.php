<?php

namespace PicoFeed\Parser;

use Closure;
use DomDocument;
use DOMXPath;
use SimpleXmlElement;

/**
 * XML parser class
 *
 * Checks for XML eXternal Entity (XXE) and XML Entity Expansion (XEE) attacks on XML documents
 *
 * @author  Frederic Guillot
 * @package Parser
 */
class XmlParser
{
    /**
     * Get a SimpleXmlElement instance or return false
     *
     * @static
     * @access public
     * @param  string   $input   XML content
     * @return mixed
     */
    public static function getSimpleXml($input)
    {
        $dom = self::getDomDocument($input);

        if ($dom !== false) {

            $simplexml = simplexml_import_dom($dom);

            if (! $simplexml instanceof SimpleXmlElement) {
                return false;
            }

            return $simplexml;
        }

        return false;
    }

    /**
     * Scan the input for XXE attacks
     *
     * @param string    $input       Unsafe input
     * @param Closure   $callback    Callback called to build the dom.
     *                               Must be an instance of DomDocument and receives the input as argument
     *
     * @return bool|DomDocument      False if an XXE attack was discovered,
     *                               otherwise the return of the callback
     */
    private static function scanInput($input, Closure $callback)
    {
        $isRunningFpm = substr(php_sapi_name(), 0, 3) === 'fpm';

        if ($isRunningFpm) {

            // If running with PHP-FPM and an entity is detected we refuse to parse the feed
            // @see https://bugs.php.net/bug.php?id=64938
            if (strpos($input, '<!ENTITY') !== false) {
                return false;
            }
        }
        else {
            $entityLoaderDisabled = libxml_disable_entity_loader(true);
        }

        libxml_use_internal_errors(true);

        $dom = $callback($input);

        // Scan for potential XEE attacks using ENTITY
        foreach ($dom->childNodes as $child) {
            if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
                if ($child->entities->length > 0) {
                    return false;
                }
            }
        }

        if ($isRunningFpm === false) {
            libxml_disable_entity_loader($entityLoaderDisabled);
        }

        return $dom;
    }

    /**
     * Get a DomDocument instance or return false
     *
     * @static
     * @access public
     * @param  string   $input   XML content
     * @return \DOMNDocument
     */
    public static function getDomDocument($input)
    {
        if (empty($input)) {
            return false;
        }

        $dom = self::scanInput($input, function ($in) {
            $dom = new DomDocument;
            $dom->loadXml($in, LIBXML_NONET);
            return $dom;
        });

        // The document is empty, there is probably some parsing errors
        if ($dom && $dom->childNodes->length === 0) {
            return false;
        }

        return $dom;
    }

    /**
     * Load HTML document by using a DomDocument instance or return false on failure
     *
     * @static
     * @access public
     * @param  string   $input   XML content
     * @return \DOMDocument
     */
    public static function getHtmlDocument($input)
    {
        if (empty($input)) {
            return new DomDocument;
        }

        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $callback = function ($in) {
                $dom = new DomDocument;
                $dom->loadHTML($in, LIBXML_NONET);
                return $dom;
            };
        }
        else {
            $callback = function ($in) {
                $dom = new DomDocument;
                $dom->loadHTML($in);
                return $dom;
            };
        }

        return self::scanInput($input, $callback);
    }

    /**
     * Convert a HTML document to XML
     *
     * @static
     * @access public
     * @param  string   $html   HTML document
     * @return string
     */
    public static function HtmlToXml($html)
    {
        $dom = self::getHtmlDocument('<?xml version="1.0" encoding="UTF-8">'.$html);
        return $dom->saveXML($dom->getElementsByTagName('body')->item(0));
    }

    /**
     * Get XML parser errors
     *
     * @static
     * @access public
     * @return string
     */
    public static function getErrors()
    {
        $errors = array();

        foreach(libxml_get_errors() as $error) {

            $errors[] = sprintf('XML error: %s (Line: %d - Column: %d - Code: %d)',
                $error->message,
                $error->line,
                $error->column,
                $error->code
            );
        }

        return implode(', ', $errors);
    }

    /**
     * Get the encoding from a xml tag
     *
     * @static
     * @access public
     * @param  string  $data  Input data
     * @return string
     */
    public static function getEncodingFromXmlTag($data)
    {
        $encoding = '';

        if (strpos($data, '<?xml') !== false) {

            $data = substr($data, 0, strrpos($data, '?>'));
            $data = str_replace("'", '"', $data);

            $p1 = strpos($data, 'encoding=');
            $p2 = strpos($data, '"', $p1 + 10);

            if ($p1 !== false && $p2 !== false) {
                $encoding = substr($data, $p1 + 10, $p2 - $p1 - 10);
                $encoding = strtolower($encoding);
            }
        }

        return $encoding;
    }

    /**
     * Get the charset from a meta tag
     *
     * @static
     * @access public
     * @param  string  $data  Input data
     * @return string
     */
    public static function getEncodingFromMetaTag($data)
    {
        $encoding = '';

        if (preg_match('/<meta.*?charset\s*=\s*["\']?\s*([^"\'\s\/>;]+)/i', $data, $match) === 1) {
            $encoding = strtolower($match[1]);
        }

        return $encoding;
    }

    /**
     * Rewrite XPath query to use namespace-uri and local-name derived from prefix
     *
     * @param string               $query                   XPath query
     * @param array                $ns                      Prefix to namespace URI mapping
     * @return string
     */
    public static function replaceXPathPrefixWithNamespaceURI($query, array $ns) {
        return preg_replace_callback('/([A-Z0-9]+):([A-Z0-9]+)/iu', function($matches) use($ns) {
            // don't try to map the special prefix XML
            if (strtolower($matches[1]) === 'xml') {
                return $matches[0];
            }

            return '*[namespace-uri()="'.$ns[$matches[1]].'" and local-name()="'.$matches[2].'"]';
        },
        $query);
    }

    /**
     * Get the result elements of a XPath query
     *
     * @param \SimpleXMLElement    $xml                     XML element
     * @param string               $query                   XPath query
     * @param array                $ns                      Prefix to namespace URI mapping
     * @return \SimpleXMLElement
     */
    public static function getXPathResult(SimpleXMLElement $xml, $query, array $ns = array())
    {
        if (! empty($ns)) {
            $query = static::replaceXPathPrefixWithNamespaceURI($query, $ns);
        }

        return $xml->xpath($query);
    }
}
