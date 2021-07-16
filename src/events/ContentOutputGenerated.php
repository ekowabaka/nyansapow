<?php

namespace foonoo\events;


// use foonoo\content\Content;
// use foonoo\content\ThemableInterface;
// use foonoo\sites\AbstractSite;

/**
 * This event is triggered after the output of any content is generated and ready to be written.
 *
 * @package foonoo\events
 */
class ContentOutputGenerated extends BaseOutputEvent
{
    public function getDOM(): \DOMDocument
    {
        // Create a DOM tree for objects that are possibly themed
        if (!$this->dom && $this->hasDOM()) {
            $this->dom = new \DOMDocument();
            $this->dom->encoding = "UTF-8";
            @$this->dom->loadHTML(mb_convert_encoding("<section>$this->output</section>", "HTML-ENTITIES", "UTF-8"), LIBXML_HTML_NODEFDTD|LIBXML_HTML_NOIMPLIED);
        }
        $this->domPossiblyModified = true;
        return $this->dom;
    }

    public function getOutput(): string
    {
        if ($this->dom && $this->domPossiblyModified) {
            $wrapper =$this->dom->childNodes->item(0);
            $this->output = '';
            foreach ($wrapper->childNodes as $node) {
                $this->output .= $this->dom->saveHTML($node);
            }
            $this->domPossiblyModified = false;
        }
        return $this->output;
    }
}
