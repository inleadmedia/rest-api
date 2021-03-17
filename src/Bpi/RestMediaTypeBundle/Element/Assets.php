<?php

namespace Bpi\RestMediaTypeBundle\Element;

use JMS\Serializer\Annotation as Serializer;
use Bpi\RestMediaTypeBundle\Element\Property;
use Bpi\RestMediaTypeBundle\Element\File as File;

/**
 * @Serializer\XmlRoot("assets")
 */
class Assets
{
    /**
     * @Serializer\XmlList(inline=true, entry="file")
     * @Serializer\Type("array<Bpi\RestMediaTypeBundle\Element\File>")
     */
    public $assets = array();

    public function add(File $entity)
    {
        $this->assets[] = $entity;
    }
}
