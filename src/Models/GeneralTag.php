<?php

namespace Angujo\Lareloquent\Models;

use Laminas\Code\Generator\AbstractGenerator;
use Laminas\Code\Generator\DocBlock\Tag\LicenseTag;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlock\Tag\TagInterface;
use Laminas\Code\Generator\DocBlock\TagManager;
use Laminas\Code\Reflection\DocBlock\Tag\TagInterface as ReflectionTagInterface;

class GeneralTag extends AbstractGenerator implements TagInterface
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $description;

    /**
     * @param string      $name
     * @param string|null $description
     */
    public function __construct($name = null, string $description = null)
    {
        if (!empty($name)) {
            $this->setName($name);
        }

        if (!empty($description)) {
            $this->setDescription($description);
        }
    }

    /**
     * @return TagInterface
     * @deprecated Deprecated in 2.3. Use TagManager::createTagFromReflection() instead
     *
     */
    public static function fromReflection(ReflectionTagInterface $reflectionTag)
    {
        $tagManager = new TagManager();
        $tagManager->initializeDefaultTags();
        return $tagManager->createTagFromReflection($reflectionTag);
    }

    /**
     * @param string $name
     *
     * @return GeneralTag
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return GeneralTag
     */
    public function setDescription($name)
    {
        $this->description = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function generate()
    {
        return '@'
            .(!empty($this->name) ? $this->name : 'unknown')
            .(!empty($this->description) ? ' '.$this->description : '');
    }

    public static function fromContent(string $name, string $description = '')
    {
        return new GeneralTag($name, $description);
    }

    public static function returnTag(string $description)
    {
        return self::fromContent('return', $description);
    }
}