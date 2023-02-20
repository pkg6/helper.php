<?php


namespace Pkg6\Helper;


use ArrayAccess;
use InvalidArgumentException;
use Traversable;

class Arr
{
    /**
     * @param $array
     * @return string
     */
    public static function query($array)
    {
        return http_build_query($array, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Converts an object or an array of objects into an array.
     * @param object|array|string $object the object to be converted into an array
     * @param array               $properties a mapping from object class names to the properties that need to put into the resulting arrays.
     * The properties specified for each class is an array of the following format:
     * ```php
     * [
     *     'app\models\Post' => [
     *         'id',
     *         'title',
     *         // the key name in array result => property name
     *         'createTime' => 'created_at',
     *         // the key name in array result => anonymous function
     *         'length' => function ($post) {
     *             return strlen($post->content);
     *         },
     *     ],
     * ]
     * ```
     * The result of `ArrayHelper::toArray($post, $properties)` could be like the following:
     * ```php
     * [
     *     'id' => 123,
     *     'title' => 'test',
     *     'createTime' => '2013-01-01 12:00AM',
     *     'length' => 301,
     * ]
     * ```
     * @param bool                $recursive whether to recursively converts properties which are objects into arrays.
     * @return array the array representation of the object
     * @throws \Exception
     */
    public static function toArray($object, $properties = [], $recursive = true)
    {
        if (is_array($object)) {
            if ($recursive) {
                foreach ($object as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $object[$key] = static::toArray($value, $properties, true);
                    }
                }
            }
            return $object;
        } elseif ($object instanceof \DateTimeInterface) {
            return (array)$object;
        } elseif (is_object($object)) {
            if (!empty($properties)) {
                $className = get_class($object);
                if (!empty($properties[$className])) {
                    $result = [];
                    foreach ($properties[$className] as $key => $name) {
                        if (is_int($key)) {
                            $result[$name] = $object->$name;
                        } else {
                            $result[$key] = static::get($object, $name);
                        }
                    }

                    return $recursive ? static::toArray($result, $properties) : $result;
                }
            }
            $result = [];
            foreach ($object as $key => $value) {
                $result[$key] = $value;
            }
            return $recursive ? static::toArray($result, $properties) : $result;
        }

        return [$object];
    }

    /**
     * Checks if the given array contains the specified key.
     * This method enhances the `array_key_exists()` function by supporting case-insensitive
     * key comparison.
     * @param string            $key the key to check
     * @param array|ArrayAccess $array the array with keys to check
     * @param bool              $caseSensitive whether the key comparison should be case-sensitive
     * @return bool whether the array contains the specified key
     */
    public static function exists($array, $key, $caseSensitive = true)
    {
        if ($caseSensitive) {
            // Function `isset` checks key faster but skips `null`, `array_key_exists` handles this case
            // https://www.php.net/manual/en/function.array-key-exists.php#107786
            if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
                return true;
            }
            return $array instanceof ArrayAccess && $array->offsetExists($key);
        }
        if ($array instanceof ArrayAccess) {
            throw new InvalidArgumentException('Second parameter($array) cannot be ArrayAccess in case insensitive mode');
        }
        foreach (array_keys($array) as $k) {
            if (strcasecmp($key, $k) === 0) {
                return true;
            }
        }
        return false;
    }


    /**
     * Below are some usage examples,
     * @param array|object          $array array or object to extract value from
     * @param string|\Closure|array $key key name of the array element, an array of keys or property name of the object,
     * or an anonymous function returning the value. The anonymous function signature should be:
     * `function($array, $defaultValue)`.
     * The possibility to pass an array of keys is available since version 2.0.4.
     * @param mixed                 $default the default value to be returned if the specified array key does not exist. Not used when
     * getting value from an object.
     * @return mixed the value of the element if found, default value otherwise
     * @throws \Exception
     */
    public static function get($array, $key, $default = null)
    {
        if ($key instanceof \Closure) {
            return $key($array, $default);
        }
        if (is_array($key)) {
            $lastKey = array_pop($key);
            foreach ($key as $keyPart) {
                $array = static::get($array, $keyPart);
            }
            $key = $lastKey;
        }
        if (is_object($array) && property_exists($array, $key)) {
            return $array->$key;
        }
        if (static::exists($array, $key)) {
            return $array[$key];
        }
        if ($key && ($pos = strrpos($key, '.')) !== false) {
            $array = static::get($array, substr($key, 0, $pos), $default);
            $key   = substr($key, $pos + 1);
        }
        if (static::exists($array, $key)) {
            return $array[$key];
        }
        if (is_object($array)) {
            try {
                return $array->$key;
            } catch (\Exception $e) {
                if ($array instanceof ArrayAccess) {
                    return $default;
                }
                throw $e;
            }
        }
        return $default;
    }

    /**
     * Writes a value into an associative array at the key path specified.
     * If there is no such key path yet, it will be created recursively.
     * If the key exists, it will be overwritten.
     * ```php
     *  $array = [
     *      'key' => [
     *          'in' => [
     *              'val1',
     *              'key' => 'val'
     *          ]
     *      ]
     *  ];
     * ```
     * The result of `Arr::set($array, 'key.in.0', ['arr' => 'val']);` will be the following:
     * ```php
     *  [
     *      'key' => [
     *          'in' => [
     *              ['arr' => 'val'],
     *              'key' => 'val'
     *          ]
     *      ]
     *  ]
     * ```
     * The result of
     * `Arr::set($array, 'key.in', ['arr' => 'val']);` or
     * `Arr::set($array, ['key', 'in'], ['arr' => 'val']);`
     * will be the following:
     * ```php
     *  [
     *      'key' => [
     *          'in' => [
     *              'arr' => 'val'
     *          ]
     *      ]
     *  ]
     * ```
     * @param array             $array the array to write the value to
     * @param string|array|null $path the path of where do you want to write a value to `$array`
     * the path can be described by a string when each key should be separated by a dot
     * you can also describe the path as an array of keys
     * if the path is null then `$array` will be assigned the `$value`
     * @param mixed             $value the value to be written
     */
    public static function set(&$array, $path, $value)
    {
        if ($path === null) {
            $array = $value;
            return;
        }
        $keys = is_array($path) ? $path : explode('.', $path);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key])) {
                $array[$key] = [];
            }
            if (!is_array($array[$key])) {
                $array[$key] = [$array[$key]];
            }
            $array = &$array[$key];
        }
        $array[array_shift($keys)] = $value;
    }

    /**
     * Removes an item from an array and returns the value. If the key does not exist in the array, the default value
     * will be returned instead.
     * Usage examples,
     * ```php
     * // $array = ['type' => 'A', 'options' => [1, 2]];
     * // working with array
     * $type = Arr::remove($array, 'type');
     * // $array content
     * // $array = ['options' => [1, 2]];
     * ```
     * @param array  $array the array to extract value from
     * @param string $key key name of the array element
     * @param mixed  $default the default value to be returned if the specified key does not exist
     * @return mixed|null the value of the element if found, default value otherwise
     */
    public static function remove(&$array, $key, $default = null)
    {
        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
            $value = $array[$key];
            unset($array[$key]);

            return $value;
        }

        return $default;
    }

    /**
     * Removes items with matching values from the array and returns the removed items.
     * Example,
     * ```php
     * $array = ['Bob' => 'Dylan', 'Michael' => 'Jackson', 'Mick' => 'Jagger', 'Janet' => 'Jackson'];
     * $removed = Arr::removeValue($array, 'Jackson');
     * // result:
     * // $array = ['Bob' => 'Dylan', 'Mick' => 'Jagger'];
     * // $removed = ['Michael' => 'Jackson', 'Janet' => 'Jackson'];
     * ```
     * @param array $array the array where to look the value from
     * @param mixed $value the value to remove from the array
     * @return array the items that were removed from the array
     */
    public static function removeValue(&$array, $value)
    {
        $result = [];
        if (is_array($array)) {
            foreach ($array as $key => $val) {
                if ($val === $value) {
                    $result[$key] = $val;
                    unset($array[$key]);
                }
            }
        }
        return $result;
    }

    /**
     * Builds a map (key-value pairs) from a multidimensional array or an array of objects.
     * The `$from` and `$to` parameters specify the key names or property names to set up the map.
     * Optionally, one can further group the map according to a grouping field `$group`.
     * For example,
     * ```php
     * $array = [
     *     ['id' => '123', 'name' => 'aaa', 'class' => 'x'],
     *     ['id' => '124', 'name' => 'bbb', 'class' => 'x'],
     *     ['id' => '345', 'name' => 'ccc', 'class' => 'y'],
     * ];
     * $result = Arr::map($array, 'id', 'name');
     * // the result is:
     * // [
     * //     '123' => 'aaa',
     * //     '124' => 'bbb',
     * //     '345' => 'ccc',
     * // ]
     * $result = Arr::map($array, 'id', 'name', 'class');
     * // the result is:
     * // [
     * //     'x' => [
     * //         '123' => 'aaa',
     * //         '124' => 'bbb',
     * //     ],
     * //     'y' => [
     * //         '345' => 'ccc',
     * //     ],
     * // ]
     * ```
     * @param array                $array
     * @param string|\Closure      $from
     * @param string|\Closure      $to
     * @param string|\Closure|null $group
     * @return array
     * @throws \Exception
     */
    public static function map($array, $from, $to, $group = null)
    {
        $result = [];
        foreach ($array as $element) {
            $key   = static::get($element, $from);
            $value = static::get($element, $to);
            if ($group !== null) {
                $result[static::get($element, $group)][$key] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Returns the values of a specified column in an array.
     * The input array should be multidimensional or an array of objects.
     * For example,
     * ```php
     * $array = [
     *     ['id' => '123', 'data' => 'abc'],
     *     ['id' => '345', 'data' => 'def'],
     * ];
     * $result = Arr::column($array, 'id');
     * // the result is: ['123', '345']
     * // using anonymous function
     * $result = Arr::column($array, function ($element) {
     *     return $element['id'];
     * });
     * ```
     * @param array                     $array
     * @param int|string|array|\Closure $name
     * @param bool                      $keepKeys whether to maintain the array keys. If false, the resulting array
     * will be re-indexed with integers.
     * @return array the list of column values
     * @throws \Exception
     */
    public static function column($array, $name, $keepKeys = true)
    {
        $result = [];
        if ($keepKeys) {
            foreach ($array as $k => $element) {
                $result[$k] = static::get($element, $name);
            }
        } else {
            foreach ($array as $element) {
                $result[] = static::get($element, $name);
            }
        }
        return $result;
    }

    /**
     * Indexes and/or groups the array according to a specified key.
     * The input should be either multidimensional array or an array of objects.
     * The $key can be either a key name of the sub-array, a property name of object, or an anonymous
     * function that must return the value that will be used as a key.
     * $groups is an array of keys, that will be used to group the input array into one or more sub-arrays based
     * on keys specified.
     * If the `$key` is specified as `null` or a value of an element corresponding to the key is `null` in addition
     * to `$groups` not specified then the element is discarded.
     * For example:
     * ```php
     * $array = [
     *     ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
     *     ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
     *     ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
     * ];
     * $result = Arr::index($array, 'id');
     * ```
     * The result will be an associative array, where the key is the value of `id` attribute
     * ```php
     * [
     *     '123' => ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
     *     '345' => ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone']
     *     // The second element of an original array is overwritten by the last element because of the same id
     * ]
     * ```
     * An anonymous function can be used in the grouping array as well.
     * ```php
     * $result = Arr::index($array, function ($element) {
     *     return $element['id'];
     * });
     * ```
     * Passing `id` as a third argument will group `$array` by `id`:
     * ```php
     * $result = Arr::index($array, null, 'id');
     * ```
     * The result will be a multidimensional array grouped by `id` on the first level, by `device` on the second level
     * and indexed by `data` on the third level:
     * ```php
     * [
     *     '123' => [
     *         ['id' => '123', 'data' => 'abc', 'device' => 'laptop']
     *     ],
     *     '345' => [ // all elements with this index are present in the result array
     *         ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
     *         ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
     *     ]
     * ]
     * ```
     * The anonymous function can be used in the array of grouping keys as well:
     * ```php
     * $result = Arr::index($array, 'data', [function ($element) {
     *     return $element['id'];
     * }, 'device']);
     * ```
     * The result will be a multidimensional array grouped by `id` on the first level, by the `device` on the second one
     * and indexed by the `data` on the third level:
     * ```php
     * [
     *     '123' => [
     *         'laptop' => [
     *             'abc' => ['id' => '123', 'data' => 'abc', 'device' => 'laptop']
     *         ]
     *     ],
     *     '345' => [
     *         'tablet' => [
     *             'def' => ['id' => '345', 'data' => 'def', 'device' => 'tablet']
     *         ],
     *         'smartphone' => [
     *             'hgi' => ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone']
     *         ]
     *     ]
     * ]
     * ```
     * @param array                           $array the array that needs to be indexed or grouped
     * @param string|\Closure|null            $key the column name or anonymous function which result will be used to index the array
     * @param string|string[]|\Closure[]|null $groups the array of keys, that will be used to group the input array
     * by one or more keys. If the $key attribute or its value for the particular element is null and $groups is not
     * defined, the array element will be discarded. Otherwise, if $groups is specified, array element will be added
     * to the result array without any key. This parameter is available since version 2.0.8.
     * @param bool                            $keepKeys
     * @return array the indexed and/or grouped array
     * @throws \Exception
     */
    public static function index($array, $key, $groups = [], $keepKeys = false)
    {
        $result = [];
        $groups = (array)$groups;
        foreach ($array as $index => $element) {
            $lastArray = &$result;
            foreach ($groups as $group) {
                $value = static::get($element, $group);
                if (!array_key_exists($value, $lastArray)) {
                    $lastArray[$value] = [];
                }
                $lastArray = &$lastArray[$value];
            }
            if ($key === null) {
                if (!empty($groups)) {
                    if ($keepKeys) {
                        $lastArray[$index] = $element;
                    } else {
                        $lastArray[] = $element;
                    }
                }
            } else {
                $value = static::get($element, $key);
                if ($value !== null) {
                    if (is_float($value)) {
                        $value = Str::floatToString($value);
                    }
                    $lastArray[$value] = $element;
                }
            }
            unset($lastArray);
        }
        return $result;
    }


    /**
     * Sorts an array of objects or arrays (with the same structure) by one or several keys.
     * @param array                 $array the array to be sorted. The array will be modified after calling this method.
     * @param string|\Closure|array $key the key(s) to be sorted by. This refers to a key name of the sub-array
     * elements, a property name of the objects, or an anonymous function returning the values for comparison
     * purpose. The anonymous function signature should be: `function($item)`.
     * To sort by multiple keys, provide an array of keys here.
     * @param int|array             $direction the sorting direction. It can be either `SORT_ASC` or `SORT_DESC`.
     * When sorting by multiple keys with different sorting directions, use an array of sorting directions.
     * @param int|array             $sortFlag the PHP sort flag. Valid values include
     * `SORT_REGULAR`, `SORT_NUMERIC`, `SORT_STRING`, `SORT_LOCALE_STRING`, `SORT_NATURAL` and `SORT_FLAG_CASE`.
     * Please refer to [PHP manual](https://www.php.net/manual/en/function.sort.php)
     * for more details. When sorting by multiple keys with different sort flags, use an array of sort flags.
     * @throws InvalidArgumentException if the $direction or $sortFlag parameters do not have
     * @throws \Exception
     * correct number of elements as that of $key.
     */
    public static function multisort(&$array, $key, $direction = SORT_ASC, $sortFlag = SORT_REGULAR)
    {
        $keys = is_array($key) ? $key : [$key];
        if (empty($keys) || empty($array)) {
            return;
        }
        $n = count($keys);
        if (is_scalar($direction)) {
            $direction = array_fill(0, $n, $direction);
        } elseif (count($direction) !== $n) {
            throw new InvalidArgumentException('The length of $direction parameter must be the same as that of $keys.');
        }
        if (is_scalar($sortFlag)) {
            $sortFlag = array_fill(0, $n, $sortFlag);
        } elseif (count($sortFlag) !== $n) {
            throw new InvalidArgumentException('The length of $sortFlag parameter must be the same as that of $keys.');
        }
        $args = [];
        foreach ($keys as $i => $k) {
            $flag   = $sortFlag[$i];
            $args[] = static::column($array, $k);
            $args[] = $direction[$i];
            $args[] = $flag;
        }
        // This fix is used for cases when main sorting specified by columns has equal values
        // Without it it will lead to Fatal Error: Nesting level too deep - recursive dependency?
        $args[] = range(1, count($array));
        $args[] = SORT_ASC;
        $args[] = SORT_NUMERIC;
        $args[] = &$array;
        call_user_func_array('array_multisort', $args);
    }

    /**
     * Returns a value indicating whether the given array is an indexed array.
     * An array is indexed if all its keys are integers. If `$consecutive` is true,
     * then the array keys must be a consecutive sequence starting from 0.
     * Note that an empty array will be considered indexed.
     * @param array $array the array being checked
     * @param bool  $consecutive whether the array keys must be a consecutive sequence
     * in order for the array to be treated as indexed.
     * @return bool whether the array is indexed
     */
    public static function isIndexed($array, $consecutive = false)
    {
        if (!is_array($array)) {
            return false;
        }
        if (empty($array)) {
            return true;
        }
        $keys = array_keys($array);
        if ($consecutive) {
            return $keys === array_keys($keys);
        }
        foreach ($keys as $key) {
            if (!is_int($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns a value indicating whether the given array is an associative array.
     * An array is associative if all its keys are strings. If `$allStrings` is false,
     * then an array will be treated as associative if at least one of its keys is a string.
     * Note that an empty array will NOT be considered associative.
     * @param array $array the array being checked
     * @param bool  $allStrings whether the array keys must be all strings in order for
     * the array to be treated as associative.
     * @return bool whether the array is associative
     */
    public static function isAssociative($array, $allStrings = true)
    {
        if (empty($array) || !is_array($array)) {
            return false;
        }
        if ($allStrings) {
            foreach ($array as $key => $value) {
                if (!is_string($key)) {
                    return false;
                }
            }
            return true;
        }
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Merges two or more arrays into one recursively.
     * If each array has an element with the same string key value, the latter
     * will overwrite the former (different from array_merge_recursive).
     * Recursive merging will be conducted if both arrays have an element of array
     * type and are having the same key.
     * For integer-keyed elements, the elements from the latter array will
     * be appended to the former array.
     * @param array $a array to be merged to
     * @param array $b array to be merged from. You can specify additional
     * arrays via third argument, fourth argument etc.
     * @return array the merged array (the original arrays are not changed.)
     */
    public static function merge($a, $b)
    {
        $args = func_get_args();
        $res  = array_shift($args);
        while (!empty($args)) {
            foreach (array_shift($args) as $k => $v) {
                if (is_int($k)) {
                    if (array_key_exists($k, $res)) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = static::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }
        return $res;
    }
    /**
     * Check whether an array or [[Traversable]] contains an element.
     *
     * This method does the same as the PHP function [in_array()](https://www.php.net/manual/en/function.in-array.php)
     * but additionally works for objects that implement the [[Traversable]] interface.
     *
     * @param mixed $needle The value to look for.
     * @param iterable $haystack The set of values to search.
     * @param bool $strict Whether to enable strict (`===`) comparison.
     * @return bool `true` if `$needle` was found in `$haystack`, `false` otherwise.
     * @throws InvalidArgumentException if `$haystack` is neither traversable nor an array.
     * @see https://www.php.net/manual/en/function.in-array.php
     */
    public static function isIn($needle, $haystack, $strict = false)
    {
        if (!static::isTraversable($haystack)) {
            throw new InvalidArgumentException('Argument $haystack must be an array or implement Traversable');
        }
        if (is_array($haystack)) {
            return in_array($needle, $haystack, $strict);
        }
        foreach ($haystack as $value) {
            if ($strict ? $needle === $value : $needle == $value) {
                return true;
            }
        }
        return false;
    }
    /**
     * Checks whether a variable is an array or [[Traversable]].
     *
     * This method does the same as the PHP function [is_array()](https://www.php.net/manual/en/function.is-array.php)
     * but additionally works on objects that implement the [[Traversable]] interface.
     * @param mixed $var The variable being evaluated.
     * @return bool whether $var can be traversed via foreach
     * @see https://www.php.net/manual/en/function.is-array.php
     */
    public static function isTraversable($var)
    {
        return is_array($var) || $var instanceof Traversable;
    }
}