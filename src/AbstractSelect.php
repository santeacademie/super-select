<?php
/**
 * @author JRK <jessym@santeacademie.com>
 */

namespace Santeacademie\SuperSelect;

use Santeacademie\SuperSelect\Annotation\SelectOption;
use Doctrine\Common\Annotations\AnnotationReader;

abstract class AbstractSelect
{

    private static function readAnnotations(): array
    {
        $reflectionClass = new \ReflectionClass(get_called_class());
        $props = $reflectionClass->getProperties(\ReflectionProperty::IS_STATIC | \ReflectionProperty::IS_PUBLIC);
        $reader = new AnnotationReader();

        return array_filter(array_map(function (\ReflectionProperty $prop) use ($reflectionClass, $reader) {
            $selectAnnotation = $reader->getPropertyAnnotation(
                $reflectionClass->getProperty($prop->getName()),
                SelectOption::class
            );

            return $selectAnnotation ? [
                'class' => $reflectionClass->getName(),
                'property' => $prop->getName(),
                'value' => $reflectionClass->getName()::${$prop->getName()},
                'annotation' => $selectAnnotation
            ] : false;
        }, $props));
    }

    public static function keys(?array $optionsFilters = null): array
    {
        return array_filter(
            array: array_map(function ($selectOption) use($optionsFilters) {
                /** @var SelectOption $annotation */
                $options = $selectOption['annotation']->getOptions();

                if (is_array($optionsFilters)) {
                    foreach($optionsFilters as $k => $v) {
                        if (isset($options[$k]) && $options[$k] !== $optionsFilters[$k]) {
                            return null;
                        }
                    }
                }

                return $selectOption['value'];
            }, self::readAnnotations()),
            callback: function($item) {
                return $item !== null;
            }
        ) ?? [];
    }

    public static function keysWithDescriptions(?array $optionsFilters = null): array
    {
        return array_reduce(self::readAnnotations(), function ($carry, $selectOption) use($optionsFilters) {
            /** @var SelectOption $annotation */
            $options = $selectOption['annotation']->getOptions();

            if (is_array($optionsFilters)) {
                foreach($optionsFilters as $k => $v) {
                    if (isset($options[$k]) && $options[$k] !== $optionsFilters[$k]) {
                        return $carry;
                    }
                }
            }

            $annotation = $selectOption['annotation'];
            $carry[$selectOption['value']] = $annotation->getDescription();

            return $carry;
        }, []) ?? [];
    }

    public static function keysWithAnnotationAttributes(string $valueFilter = null, ?array $optionsFilters = null): array
    {
        $filtered = array_reduce(self::readAnnotations(), function ($carry, $selectOption) use($valueFilter, $optionsFilters) {
            /** @var SelectOption $annotation */
            $options = $selectOption['annotation']->getOptions();

            if (is_array($optionsFilters)) {
                foreach($optionsFilters as $k => $v) {
                    if (isset($options[$k]) && $options[$k] !== $optionsFilters[$k]) {
                        return $carry;
                    }
                }
            }

            if (!is_null($valueFilter) && $selectOption['value'] !== $valueFilter) {
                return $carry;
            }

            $annotation = $selectOption['annotation'];
            $carry[$selectOption['value']] = array_replace(
                ['description' => $annotation->getDescription()],
                $annotation->getOptions() ?? []
            );
            return $carry;
        }, []) ?? [];

        if (!is_null($valueFilter) && !empty($filtered)) {
            return $filtered[$valueFilter];
        }

        return $filtered;
    }

    public static function getMetadata(): array
    {
        return self::readAnnotations();
    }

}
