<?php
namespace Bpi\ApiBundle\Domain\Entity\Resource;

/**
 * Class Body.
 */
class Body
{
    const BASE_URL_STUB = '__embedded_asset_base_url__';
    const WELLFORM_INDICATOR = '__wellform__';

    /**
     * @var \DOMDocument
     */
    protected $dom;

    protected $filesystem;
    protected $router;
    protected $assets = array();

    /**
     * Body constructor.
     *
     * @param $content
     * @param null $filesystem
     * @param null $router
     */
    public function __construct($content, $filesystem = null, $router = null)
    {
        $this->dom = $content;
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
        return $this->dom;
    }

    public function rebuildInlineAssets()
    {
        preg_match_all('/<img[^>]+>/im', $this->dom, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            preg_match('/src=\"([^"]+)\"/i', $match[0], $src);

            $srcFile = $src[1];
            $ext = pathinfo(parse_url($srcFile, PHP_URL_PATH), PATHINFO_EXTENSION);

            // Download file and save to db.
            $filename = md5($srcFile.microtime());
            $file = $this->filesystem->createFile($filename);
            // @todo Download files in a proper way.
            $file->setContent(file_get_contents($srcFile));

            // Build URL for new image and replace img src.
            $this->assets[] = array(
                'file' => $file->getKey(),
                'type' => 'embedded',
                'extension' => $ext
            );

            $url = $this->router->generate(
                'get_asset',
                array(
                    'filename' => $file->getKey(),
                    'extension' => $ext,
                ),
                true
            );
            $tag = str_replace($srcFile, $url, $match[0]);

            $this->dom = str_replace($match[0], $tag, $this->dom);
        }
    }

    /**
     * Gets a set of assets.
     *
     * @return array
     *   A set of inline assets.
     */
    public function getAssets()
    {
        return $this->assets;
    }
}
