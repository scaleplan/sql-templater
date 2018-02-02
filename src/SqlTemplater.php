<?php

namespace avtomon;

class SqlTemplaterException extends \Exception
{
}

class SqlTemplater
{
    /**
     * Актуализировать условия
     *
     * @param string $sql - текст запроса
     * @param array $data - параметры запроса
     *
     * @return array
     */
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
     * Собрать данные для вставки или изменениях
     *
     * @param string $sql - текст запроса
     * @param array $data - параметры запроса
     * @param int $pos - позиция вставки
     *
     * @return array
     */
    public static function createExpression(string &$sql, array &$data, int &$pos): string
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
    }

    /**
     * Разбор SQL-шаблона
     *
     * @param string $sql - шаблон SQL-запроса
     * @param array $data - данные для выполнения запроса
     * @param bool $convertArrays - преобразовывать ли массивы PHP в массивы PostgreSQL
     *
     * @return array
     */
    public static function sql(string &$sql, array &$data, bool $convertArrays = true): array
    {
        self::renderConditions($sql, $data);

        if (!preg_match('/(\[expression.*\]|\[fields.*\])/i', $sql)) {
            return [$sql, self::removeExcessSQLArgs($sql, $data)];
        }

        if (preg_match_all('/\[fields\:not\((.+?)\)\]/i', $sql, $match)) {
            foreach ($match[0] as $key => &$value) {
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

                    $sql = substr_replace($sql, self::createExpression($sql, $new_data, $value[1]), $value[1], strlen($value[0]));
                    self::createExpression($sql, $data, $value[1]);
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
                    $sql = substr_replace($sql, self::createExpression($sql,$data, $value[1]), $value[1], strlen($value[0]));
                }
            }

            unset($value);
        }

        if ($convertArrays) {
            self::createAllPostgresArrayPlaceholders($sql, $data);
        }

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
                        foreach ($value as $k => &$v) {
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
    public static function createPostgresArrayPlaceholders(array &$array, string $fieldName = null, string $castType = ''): array
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

        return ['ARRAY[' . implode(', ', $placeholders) . "]::$castType" , $array];
    }

    public static function replacePostgresArray(string &$sql, array &$record, string $fieldName, string $castType = ''): array
    {
        if (empty($record[$fieldName]) || !is_array($record[$fieldName])) {
            return [$sql, $record];
        }

        list($placeholders, $newValue) = self::createPostgresArrayPlaceholders($record[$fieldName], $fieldName, $castType);
        $sql = str_replace(":$fieldName", $placeholders, $sql);
        unset($record[$fieldName]);
        $record += $newValue;

        return [$sql, $record];
    }

    /**
     * Перестроить запрос и набор данных, если данные содержат значения в виде массиво
     *
     * @param string $sql - текст SQL-запроса
     * @param array $data - массив данных для выполения запроса
     *
     * @return array
     */
    public static function createAllPostgresArrayPlaceholders(string &$sql, array &$data): array
    {
        if (empty($data[0])) {
            $data = [$data];
        }

        foreach ($data as &$record) {
            foreach (array_keys($record) as &$key) {
                self::replacePostgresArray($sql, $record, $key);
            }

            unset($key);
        }

        unset($record);

        $data = count($data) > 1 ? $data : reset($data);
        return [$sql, $data];
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
     * @param string $sql - SQL-запрос
     * @param string $fieldName - в какое поле писать результат
     * @param array $dataSlice - обрабатываемые данные
     * @param $defaultValue - значение по умолчанию для получаемого поля
     *
     * @return array
     */
    public static function arraysToHstoreArrays(string &$sql, string $fieldName, array $dataSlice, $defaultValue = null): array
    {
        $returnData = [];
        if (!preg_match_all("/:{$fieldName}[\s,\)]+/i", $sql)) {
            return [$sql, []];
        }

        if (!$dataSlice) {
            return [$sql, [":$fieldName" => $returnData]];
        }

        $cnt = 0;
        foreach ($dataSlice as &$value) {
            $cnt = max($cnt, count($value));
        }

        $hstoreArray = [];
        for ($i = 0; $i < $cnt; $i++) {
            foreach ($dataSlice as $key => &$value) {
                if (empty($value[$i])) {
                    continue;
                }

                $newKey = $fieldName . $key . $i;
                $hstoreArray[$i][] = "hstore('$key', :$newKey)";
                $returnData[$newKey] = $value[$i];
            }

            $hstoreArray[$i] = implode(' || ', $hstoreArray[$i]);
        }

        unset($value);

        $sql = preg_replace("/:{$fieldName}([\s,\)]+)/i", 'ARRAY[' . implode(', ', $hstoreArray) . ']$1', $sql);

        return [$sql, $returnData];
    }
}