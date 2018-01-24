<?php

namespace avtomon;

class SqlTemplaterException extends \Exception
{
}

class SqlTemplater
{
    public static function renderConditions(string &$sql, array &$data): array
    {
        if (!preg_match_all('/\[((?:AND|OR|NOT|AND NOT|OR NOT|WHERE)\s+.+?\s+:([\w\d_\-]+))\]/i', $sql, $match, PREG_SET_ORDER)) {
            return [$sql, $data];
        }

        foreach ($match as $m) {
            $replace = '';
            if (array_key_exists($m[2], $data)) {
                $replace = $m[1];
            }

            $sql = str_replace($m[0], $replace, $sql);
        }

        return [$sql, $data];
    }

    /**
     * Разбор SQL-шаблона
     *
     * @param string $sql - шаблон SQL-запроса
     * @param array $data - данные для выполнения запроса
     *
     * @return array
     */
    public static function sql(string &$sql, array &$data): array
    {
        self::renderConditions($sql, $data);

        if (!preg_match('/(\[expression.*\]|\[fields.*\])/i', $sql)) {
            return [$sql, self::removeExcessSQLArgs($sql, $data)];
        }

        $createExpression = function (&$data, $pos) use ($sql): string
        {
            $iPos = strripos(substr($sql, 0, $pos), 'INSERT');
            $uPos = strripos(substr($sql, 0, $pos), 'UPDATE');
            if (($iPos !== false && ($iPos > $uPos || $uPos === false))) {
                return self::createPrepareFields($data);
            } elseif ($uPos !== false && ($uPos > $iPos || $iPos === false)) {
                $data2 = $data;
                foreach (array_keys($data) as $key) {
                    if (strpos($sql, ':' . $key) !== false) {
                        unset($data2[$key]);
                    }
                }

                return self::createPrepareFields($data2, 'update');
            }

            return $sql;
        };

        if (preg_match_all('/\[fields\:not\((.+?)\)\]/i', $sql, $match)) {
            foreach ($match[0] as $key => & $value) {
                if ($value) {
                    if (isset($data[0]) && is_array($data[0])) {
                        $new_data = $data[0];
                    } else {
                        $new_data = $data;
                    }

                    $sql = str_replace($value, self::createSelectString(
                        array_diff_key(
                            $new_data,
                            array_flip(explode(',', str_replace(' ', '', $match[1][$key])))
                        )
                    ), $sql);
                }
            }

            unset($value);
        }

        if (preg_match_all('/\[expression:not\((.+?)\)\]/i', $sql, $match, PREG_OFFSET_CAPTURE)) {
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

                    $sql = substr_replace($sql, $createExpression($new_data, $value[1]), $value[1], strlen($value[0]));
                    $createExpression($data, $value[1]);
                    foreach (array_keys($data) as $k) {
                        if (strpos($sql, ':' . $k) === false) {
                            unset($data[$k]);
                        }
                    }
                }
            }

            unset($value);
        }

        if (stripos($sql, '[fields]')) {
            $sql = str_replace('[fields]', self::createSelectString($data), $sql);
        }

        if (preg_match_all('/\[expression\]/i', $sql, $match, PREG_OFFSET_CAPTURE)) {
            foreach ($match[0] as $key => &$value) {
                if ($value) {
                    $sql = substr_replace($sql, $createExpression($data, $value[1]), $value[1], strlen($value[0]));
                }
            }

            unset($value);
        }

        self::createAllPostgresArrayPlaceholders($sql, $data);
        return [$sql, $data];
    }

    /**
     * Формирование строки плейсхолдеров для SQL-запросов
     *
     * @param array $data - массив данных
     * @param string $type - тип строки плейсхолдеров
     *
     * @return string
     */
    public static function createPrepareFields(array &$data, string $type = 'insert'): string
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
                    $string = '(:' . implode(',:', array_keys($data)) . ')';
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

    /**
     * Получить из SQL-запроса все параметры
     *
     * @param string $sql - текст запроса
     *
     * @return array
     */
    public static function getSQLParams(string $sql): array
    {
        if (preg_match_all('/[^:]:([\w\d_\-]+)/i', $sql, $matches))
        {
            return array_unique($matches[1]);
        }

        return [];
    }

    /**
     * Почистить параметры SQL-запроса от неиспользуемых в запросе
     *
     * @param string $sql - текст запроса
     * @param array $args - параметры запроса
     *
     * @return array
     */
    public static function removeExcessSQLArgs(string &$sql, array &$args): array
    {
        return $args = array_intersect_key($args, array_flip(self::getSQLParams($sql)));
    }

    /**
     * Слить несколько полей в одно hstore-поле
     *
     * @param string $fieldName - в какое поле писать результат
     * @param array $keys - какие поля сливаем
     * @param array - обрабатываемые данные
     *
     * @return array
     */
    public static function arraysToHstoreArrays(string $fieldName, array $keys, array &$data): array
    {
        if (empty($data[0])) {
            $data = [$data];
        }

        foreach ($data as $record) {
            $cnt = !empty($record[$keys[0]]) ? (is_array($record[$keys[0]]) ? count($record[$keys[0]]) : 1) : 0;
            for ($i = 0; $i < $cnt; $i++) {
                foreach ($keys as $key) {
                    if (empty($record[$key][$i])) {
                        continue;
                    }

                    $record[$fieldName][][] = "hstore($key, {$record[$key][$i]})";
                }

                $record[$fieldName][] = implode(' || ', $record[$fieldName][$i]);
            }

            $record[$fieldName] = !empty($record[$fieldName]) ? '{' . implode(', ', $record[$fieldName]) . '}' : null;
            $record = array_diff_key($record, $keys);
        }

        return count($data) > 1 ? $data : $data[0];
    }
}