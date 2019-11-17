<?php

namespace Bpi\RestMediaTypeBundle\Element;

use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\XmlRoot("file")
 */
class File
{
    /**
     * @Serializer\Type("string")
     * @Serializer\XmlAttribute
     */
    protected $path;

    /**
     * @Serializer\Type("string")
     * @Serializer\XmlAttribute
     */
    protected $name;

    /**
     * @Serializer\Type("string")
     * @Serializer\XmlAttribute
     */
    protected $title;

    /**
     * @Serializer\Type("string")
     * @Serializer\XmlAttribute
     */
    protected $alt;

    /**
     * @Serializer\Type("string")
     * @Serializer\XmlAttribute
     */
    protected $extension;

    /**
     * @Serializer\Type("string")
     * @Serializer\XmlAttribute
     */
    protected $external;

    /**
     * @Serializer\Type("string")
     * @Serializer\XmlAttribute
     */
    protected $type;

    /**
     * @Serializer\Type("string")
     * @Serializer\XmlAttribute
     */
    protected $width;

    /**
     * @Serializer\Type("string")
     * @Serializer\XmlAttribute
     */
    protected $height;

    /**
     *
     * @param array $data
     */
    public function __construct($data)
    {
        $this->name = !empty($data['name']) ? $data['name'] : null;
        $this->path =  !empty($data['path']) ? $data['path'] : null;
        $this->title =  !empty($data['title']) ? $data['title'] : null;
        $this->alt =  !empty($data['alt']) ? $data['alt'] : null;
        $this->extension = !empty($data['extension']) ? $data['extension'] : null;
        $this->external = !empty($data['external']) ? $data['external'] : null;
        $this->type = !empty($data['type']) ? $data['type'] : null;
        $this->width = !empty($data['width']) ? $data['width'] : null;
        $this->height = !empty($data['height']) ? $data['height'] : null;
    }
}
