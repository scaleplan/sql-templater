<?php

namespace avtomon;

class SqlTemplaterException extends \Exception
{
}

class SqlTemplater
{
    /**
     * Разбор SQL-шаблона
     *
     * @param string $request - шаблон SQL-запроса
     * @param array $data - данные для выполнения запроса
     *
     * @return array
     */
    public static function sql(string &$request, array &$data): array
    {
        $createExpression = function (&$data, $pos) use ($request): string
        {
            $iPos = strripos(substr($request, 0, $pos), 'INSERT');
            $uPos = strripos(substr($request, 0, $pos), 'UPDATE');
            if (($iPos !== false && ($iPos > $uPos || $uPos === false))) {
                return self::createPrepareFields($data);
            } elseif ($uPos !== false && ($uPos > $iPos || $iPos === false)) {
                $data2 = $data;
                foreach (array_keys($data) as $key) {
                    if (strpos($request, ':' . $key) !== false) {
                        unset($data2[$key]);
                    }
                }

                return self::createPrepareFields($data2, 'update');
            }

            return $request;
        };

        if (preg_match_all('/\[fields\:not\((.+?)\)\]/i', $request, $match)) {
            foreach ($match[0] as $key => & $value) {
                if ($value) {
                    if (isset($data[0]) && is_array($data[0])) {
                        $new_data = $data[0];
                    } else {
                        $new_data = $data;
                    }

                    $request = str_replace($value, self::createSelectString(
                        array_diff_key(
                            $new_data,
                            array_flip(explode(',', str_replace(' ', '', $match[1][$key])))
                        )
                    ), $request);
                }
            }

            unset($value);
        }

        if (preg_match_all('/\[expression:not\((.+?)\)\]/i', $request, $match, PREG_OFFSET_CAPTURE)) {
            $new_data = [];
            foreach ($match[0] as $key => &$value) {
                if ($value) {
                    if (isset($data[0]) && is_array($data[0])) {
                        foreach ($data as $k => & $v) {
                            $new_data[$k] = array_diff_key(
                                $v,
                                array_flip(explode(',', str_replace(' ', '', $match[1][$key][0])))
                            );
                        }

                        unset($v);
                    } else {
                        $new_data = array_diff_key(
                            $data,
                            array_flip(explode(',', str_replace(' ', '', $match[1][$key][0])))
                        );
                    }

                    $request = substr_replace($request, $createExpression($new_data, $value[1]), $value[1], strlen($value[0]));
                    $createExpression($data, $value[1]);
                    foreach (array_keys($data) as $k) {
                        if (strpos($request, ':' . $k) === false) {
                            unset($data[$k]);
                        }
                    }
                }
            }

            unset($value);
        }

        if (stripos($request, '[fields]')) {
            $request = str_replace('[fields]', self::createSelectString($data), $request);
        }

        if (preg_match_all('/\[expression\]/i', $request, $match, PREG_OFFSET_CAPTURE)) {
            foreach ($match[0] as $key => &$value) {
                if ($value) {
                    $request = substr_replace($request, $createExpression($data, $value[1]), $value[1], strlen($value[0]));
                }
            }

            unset($value);
        }

        if (preg_match_all('/\[(?:AND|OR|NOT|AND NOT|OR NOT|WHERE)*\s*[\w\d_\-\.]+\s*(?:=|!=|<>|IN|NOT\s+IN)\s*:([\w\d_\-]+)\]/i', $request, $match, PREG_SET_ORDER)) {
            foreach ($match as $m) {
                if (in_array($m[4], $data)) {
                    $request = str_replace($m[2], $m[3], $request);
                } else {
                    $request = str_replace($m[0], '', $request);
                }
            }
        }

        self::createAllPostgresArrayPlaceholders($request, $data);
        return [$request, $data];
    }

    /**
     * Формирование строки плейсхолдеров для SQL-запросов
     *
     * @param array $data - массив данных
     * @param string $type - тип строки плейсхолдеров
     *
     * @return string
     */
    public static function createPrepareFields(array & $data, string $type = 'insert'): string
    {
        $string = '';
        $dataTmp = [];
        switch ($type) {
            case 'insert':
                if (isset($data[0]) && is_array($data[0])) {
                    foreach ($data as $index => & $value) {
                        $tmp = '';
                        foreach ($value as $k => & $v) {
                            $tmp .= ":$k$index,";
                            $dataTmp[$k . $index] = $v;
                        }

                        unset($v);
                        $string .= '(' . trim($tmp, ',') . '),';
                    }

                    $string = trim($string, ',');
                    $data = $dataTmp;
                    unset($dataTmp, $tmp, $value);
                } else {
                    $string = '(:' . implode(',:', $data) . ')';
                }

                break;

            case 'update':
                $dataTmp = array_map(function ($item) {
                    return "$item = :$item";
                }, array_keys($data));

                $string = implode(', ', $dataTmp);

                break;
        }

        return $string;
    }

    /**
     * Формирование списка полей для SQL-запросов
     *
     * @param array $data - массив данных
     *
     * @return string
     */
    public static function createSelectString(array $data): string
    {
        if (isset($data[0])) {
            $data = $data[0];
        }

        return implode(',', array_keys($data));
    }

    /**
     * Сформировать Postgres-массив из PHP-массива
     *
     * @param array $array - PHP-массив
     * @param string $fieldName - префикс имен плейсхолдеров
     *
     * @return array
     */
    public static function createPostgresArrayPlaceholders(array &$array, string $fieldName = null): array
    {
        $fieldName = $fieldName ?? 'array';
        $count = count($array);
        $placeholders = [];
        for ($i = 0; $i < $count; $i++) {
            $name = "$fieldName$i";
            $array += [$name => $array[$i]];
            unset($array[$i]);
            $placeholders[] = ":$name";
        }

        return [$placeholders, $array];
    }

    /**
     * Перестроить запрос и набор данных, если данные содержат значения в виде массиво
     *
     * @param string $sql - текст SQL-запроса
     * @param array $array - массив данных для выполения запроса
     *
     * @return array
     */
    public static function createAllPostgresArrayPlaceholders(string &$sql, array &$array): array
    {
        if (empty($array[0])) {
            $array = [$array];
        }

        foreach ($array as $record) {
            foreach ($record as $key => &$value) {
                if (!is_array($value)) {
                    continue;
                }

                list($placeholders, $newValue) = self::createPostgresArrayPlaceholders($value, $key);
                $sql = str_replace(":$key", '{' . implode(', ', $placeholders) . '}', $sql);
                unset($value);
                $record += $newValue;
            }
        }

        $array = count($array) > 1 ? $array : reset($array);
        return [$sql, $array];
    }
}