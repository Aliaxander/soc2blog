<?php
/**
 * Created by PhpStorm.
 * User: aliaxander
 * Date: 18.09.17
 * Time: 16:46
 */

namespace app\models\Helpers;


class Item extends \Bhaktaraz\RSSGenerator\Item
{
    protected $comments;
    
    /**
     * Set item comments url
     *
     * @param string $url
     *
     * @return $this
     */
    public function comments($url)
    {
        $this->comments = $url;
        
        return $this;
    }
    
    /**
     * Return XML object
     *
     * @return SimpleXMLElement
     */
    public function asXML()
    {
        $xml = new \Bhaktaraz\RSSGenerator\SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><item></item>',
            LIBXML_NOERROR | LIBXML_ERR_NONE | LIBXML_ERR_FATAL);
        $xml->addChild('title', $this->title);
        $xml->addChild('link', $this->url);
        if ($this->pubDate !== null) {
            $xml->addChild('pubDate', date(DATE_RSS, $this->pubDate));
        }
        if ($this->comments !== null) {
            $xml->addChild('comments', $this->comments);
        }
        
        if ($this->creator) {
            $xml->addChildCData("xmlns:dc:creator", $this->creator);
        }
        
        if ($this->guid) {
            $guid = $xml->addChild('guid', $this->guid);
            
            if ($this->isPermalink) {
                $guid->addAttribute('isPermaLink', 'true');
            }
        }
        
        foreach ($this->categories as $category) {
            $element = $xml->addChild('category', $category[0]);
            
            if (isset($category[1])) {
                $element->addAttribute('domain', $category[1]);
            }
        }
        
        $xml->addChild('description', $this->description);
        $xml->addChildCData('xmlns:content:encoded', $this->content);
        
        if (is_array($this->enclosure) && (count($this->enclosure) == 3)) {
            $element = $xml->addChild('enclosure');
            $element->addAttribute('url', $this->enclosure['url']);
            $element->addAttribute('type', $this->enclosure['type']);
            
            if ($this->enclosure['length']) {
                $element->addAttribute('length', $this->enclosure['length']);
            }
        }
        
        return $xml;
    }
    
}