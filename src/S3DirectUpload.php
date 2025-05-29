<?php


namespace Alexrsk\S3DirectUpload;

use Laravel\Nova\ResourceTool;

class  S3DirectUpload extends ResourceTool
{

    public function __construct(string $title, string $fieldName)
    {
        parent::__construct();
        $this->withTitle($title);
        $this->withMeta(['fieldName' => $fieldName]);
    }
    /**
     * Get the displayable name of the resource tool.
     *
     * @return string
     */
    public function name()
    {
        return 'S3 Direct Upload';
    }

    /**
     * Get the component name for the resource tool.
     *
     * @return string
     */
    public function component()
    {
        return 's3-direct-upload';
    }

    public function withTitle(string $title) {
        return $this->withMeta(['title' => $title]);
    }

    public function withPostProcessAction(string $url) {
        return $this->withMeta(['postProcessAction' => $url]);
    }
}
