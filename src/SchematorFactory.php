<?php


namespace Smoren\Schemator;


class SchematorFactory
{
    /**
     * @param bool $withBaseFilters
     * @param array $extraFilters
     * @return Schemator
     */
    public static function create(bool $withBaseFilters = true, array $extraFilters = []): Schemator
    {
        $schemator = new Schemator();

        if($withBaseFilters) {
            static::addBaseFilters($schemator);
        }

        foreach($extraFilters as $filterName => $filterCallback) {
            $schemator->addFilter($filterName, $filterCallback);
        }

        return $schemator;
    }

    /**
     * @param Schemator $schemator
     */
    public static function addBaseFilters(Schemator $schemator)
    {
        $schemator->addFilter(
            'implode',
            function(Schemator $executor, array $source, array $rootSource, string $delimiter = ', ') {
                return implode($delimiter, $source);
            }
        );

        $schemator->addFilter(
            'explode',
            function(Schemator $executor, string $source, array $rootSource, string $delimiter = ', ') {
                return explode($delimiter, $source);
            }
        );

        $schemator->addFilter(
            'filter',
            function(Schemator $executor, array $source, array $rootSource, $filterConfig) {
                if(is_callable($filterConfig)) {
                    return array_values(array_filter($source, $filterConfig));
                }

                $result = [];

                foreach($source as $item) {
                    foreach($filterConfig as $args) {
                        $rule = array_shift($args);

                        if(Helper::checkRule($item, $rule, $args)) {
                            $result[] = $item;
                            break;
                        }
                    }
                }

                return $result;
            }
        );

        $schemator->addFilter(
            'sort',
            function(Schemator $executor, array $source, array $rootSource, ?callable $sortCallback = null) {
                if($sortCallback !== null) {
                    usort($source, $sortCallback);
                } else {
                    sort($source);
                }
                return $source;
            }
        );

        $schemator->addFilter(
            'path',
            function(Schemator $executor, string $source, array $rootSource) {
                return $executor->getValue($rootSource, $source);
            }
        );

        $schemator->addFilter(
            'flatten',
            function(Schemator $executor, array $source) {
                return Helper::flattenArray($source);
            }
        );

        $schemator->addFilter(
            'replace',
            function(Schemator $executor, array $source, array $rootSource, array $rules) {
                $result = [];

                foreach($source as $item) {
                    $isReplaced = false;

                    foreach($rules as $args) {
                        $value = array_shift($args);
                        $rule = array_shift($args);

                        $replace = null;

                        if(Helper::checkRule($item, $rule, $args)) {
                            $replace = $value;
                            $isReplaced = true;

                            $result[] = $replace;
                            break;
                        }
                    }

                    if(!$isReplaced) {
                        $result[] = null;
                    }
                }

                return $result;
            }
        );
    }
}