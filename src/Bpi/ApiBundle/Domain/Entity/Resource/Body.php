<?php

namespace Bpi\ApiBundle\Domain\Entity\Resource;

use Bpi\ApiBundle\Domain\Entity\File;

class Body
{
    const BASE_URL_STUB = '__embedded_asset_base_url__';
    const WELLFORM_INDICATOR = '__wellform__';

    /**
     *
     * @var \DOMDocument
     */
    protected $dom;

    protected $router;
    protected $assets = [];

    /**
     *
     * @param string $content
     *
     * @throws \RuntimeException
     */
    public function __construct($content, $router = null)
    {
        $this->dom = $content;
        /*
        $this->dom = new \DOMDocument();
        $this->dom->strictErrorChecking = false;

        libxml_use_internal_errors(true);

        // DOMDocument detects encoding from meta tag
        if (false === stristr($content, 'id="' . self::WELLFORM_INDICATOR . '"'))
        {
            $wellformed_content = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" id="' . self::WELLFORM_INDICATOR . '" /></head><body>';
            $wellformed_content .= $content;
            $wellformed_content .= '</body></html>';
        }
        else
        {
            $wellformed_content = $content;
        }

        $result = @$this->dom->loadHTML($wellformed_content);
        libxml_clear_errors();

        if (false === $result) {
            // @todo write details in log
            throw new \RuntimeException('Unable to import content into DOMDocument');
        }
*/
        $this->router = $router;
    }

    /**
     * Handy way to present object as string for persistence
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getFlattenContent();
    }

    /**
     * Convert into flat string
     *
     * @return string
     */
    public function getFlattenContent()
    {
        /*
        // Fixed length strings must work faster that regexp
        $replaces = array(
            '<html>', '</html>',
            '<head>', '</head>',
            '<body>', '</body>',
            "<!DOCTYPE html>\n",
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" id="__wellform__">', '</meta>',
        );
        $html = $this->dom->saveHTML();
        return str_ireplace($replaces, '', $html);
        */

        return $this->dom;
    }

    public function rebuildInlineAssets()
    {
        preg_match_all('/<img[^>]+>/im', $this->dom, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            preg_match('/src=\"([^"]+)\"/i', $match[0], $src);
            preg_match('/title=\"([^"]+)\"/i', $match[0], $title);
            preg_match('/alt=\"([^"]+)\"/i', $match[0], $alt);
            preg_match('/width=\"([^"]+)\"/i', $match[0], $width);
            preg_match('/height=\"([^"]+)\"/i', $match[0], $height);

            $file = [];
            $pathinfo = pathinfo(parse_url($src[1], PHP_URL_PATH));

            $file['path'] = $src[1];
            $file['extension'] = $pathinfo['extension'];
            $file['name'] = $pathinfo['filename'];
            $file['title'] = !empty($title[1]) ? $title[1] : '';
            $file['alt'] = !empty($alt[1]) ? $alt[1] : '';
            $file['width'] = !empty($width[1]) ? $width[1] : '';
            $file['height'] = !empty($height[1]) ? $height[1] : '';
            $file['type'] = 'body';

            $bpi_file = new File($file);
            if ($bpi_file->createFile()) {
                $this->assets[] = $bpi_file;
                $tag = str_replace($bpi_file->getExternal(), $bpi_file->getPath(), $match[0]);
                $this->dom = str_replace($match[0], $tag, $this->dom);
            }
        }
    }

    public function getAssets()
    {
        return $this->assets;
    }
}
