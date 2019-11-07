<?php
namespace Bpi\ApiBundle\Domain\Entity\Resource;

class Body
{
    const BASE_URL_STUB = '__embedded_asset_base_url__';
    const WELLFORM_INDICATOR = '__wellform__';

    /**
     *
     * @var \DOMDocument
     */
    protected $dom;

    protected $filesystem;
    protected $router;
    protected $assets = array();

    /**
     *
     * @param string $content
     * @throws \RuntimeException
     */
    public function __construct($content, $filesystem=null, $router=null)
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
        $this->filesystem = $filesystem;
    }

    /**
     * Handy way to present object as string for persistence
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getFlattenContent();
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
        // Rebuild images
        $url = $this->router->generate('index', array(), true) . 'images/image.png';

        /*
        $images = $this->dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttributeNode('src')->value;
            $ext = pathinfo(parse_url($src, PHP_URL_PATH), PATHINFO_EXTENSION);

            if ($src == $url) {
                continue;
            }

            // Download file and save to db.
            $filename = md5($src.microtime());
            $file = $this->filesystem->createFile($filename);
            // @todo Download files in a proper way.
            $file->setContent(file_get_contents($src));

            // Build URL for new image and replace img src.
            $this->assets[] = array('file'=>$file->getKey(), 'type'=>'embedded', 'extension'=>$ext);

            $img->setAttribute('src', $url);
        }
        */
        preg_match_all('/<img[^>]+>/im', $this->dom, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {

            preg_match('/src=\"([^"]+)\"/i', $match[0], $src);

            $srcFile = $src[1];
            $ext = pathinfo(parse_url($srcFile, PHP_URL_PATH), PATHINFO_EXTENSION);

            // Download file and save to db.
            $filename = md5($src.microtime());
            $file = $this->filesystem->createFile($filename);
            // @todo Download files in a proper way.
            $file->setContent(file_get_contents($srcFile));

            // Build URL for new image and replace img src.
            $this->assets[] = array('file'=>$file->getKey(), 'type'=>'embedded', 'extension'=>$ext);

            $tag = str_replace($srcFile, $url, $match[0]);

            $this->dom = str_replace($match[0], $tag, $this->dom);
        }

    }

    public function getAssets()
    {
        return $this->assets;
    }
}
