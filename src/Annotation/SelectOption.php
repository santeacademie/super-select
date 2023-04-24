<?php
/**
 * @author JRK <jessym@santeacademie.com>
 */

namespace Santeacademie\SuperSelect\Annotation;

use Symfony\Component\Routing\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Annotation class for SelectOption
 *
 * @Annotation
 * @Target({"ALL"})
 *
 */
class SelectOption
{
    private $description;
    private $options;

    /**
     * @param array $data An array of key/value parameters
     *
     * @throws \BadMethodCallException
     */
    public function __construct(array $data)
    {
        if (!isset($data['description'])) {
            throw new \BadMethodCallException(sprintf('Unknown property "description" on annotation "%s".', static::class));
        }

        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', $key);
            if (!method_exists($this, $method)) {
                throw new \BadMethodCallException(sprintf('Unknown property "%s" on annotation "%s".', $key, static::class));
            }
            $this->$method($value);
        }

        if (is_array($this->options)) {
            foreach ($this->options as $key => &$value) {
                if ($value !== null && str_contains($value, '::$')) {
                    list($className, $propertyName) = explode('::$', $value);
                    $class = new \ReflectionClass($className);
                    $value = $class->getStaticPropertyValue($propertyName);
                }
            }
        }
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription($description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions($options): self
    {
        $this->options = $options;

        return $this;
    }

}
