<?php

namespace Scaleplan\SqlTemplater;

/**
 * Класс методов для SQL-шаблонизации
 *
 * Class SqlTemplater
 *
 * @package Scaleplan\SqlTemplater
 */
class SqlTemplater
{
    /**
     * Шаблон списка доступных полей запроса
     */
    protected const FIELDS_LABEL = 'fields';

    /**
     * Шаблон доступных выражений запроса
     */
    protected const EXPRESSION_LABEL = 'expression';

    /**
     * Регулярка поиска опцональных частей запроса
     */
    protected const OPTIONAL_TEMPLATE = '[^A-Z](?=(\[([^\[\]:]*?:([\w_\-]+)(?:::\w+\[\])?.*?)\]))';

    /**
     * Актуализировать условия
     *
     * @param string $sql - текст запроса
     * @param array $data - параметры запроса
     *
     * @return array
     */
    public static function parseConditions(string &$sql, array &$data): array
    {
        if (!preg_match_all('/\[((?:AND|OR|NOT|AND NOT|OR NOT|WHERE)\s+.+?\s+:([\w_\-]+))\]/i', $sql, $match, PREG_SET_ORDER)) {
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
     * Актуализировать необязательные части запроса
     *
     * @param string $sql - текст запроса
     * @param array $data - параметры запроса
     *
     * @return array
     */
    public static function parseOptional(string &$sql, array &$data): array
    {
        if (!preg_match_all('/' . self::OPTIONAL_TEMPLATE . '/s', $sql, $match, PREG_SET_ORDER)) {
            return [$sql, $data];
        }

        foreach ($match as $m) {
            $replace = '';
            if (array_key_exists($m[3], $data)) {
                $replace = $m[2];
            }

            $sql = str_replace($m[1], $replace, $sql);
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
     * @return string
     */
    public static function createExpression(string &$sql, array &$data, int &$pos): string
    {
        $iPos = strripos(substr($sql, 0, $pos), 'INSERT');
        $uPos = strripos(substr($sql, 0, $pos), 'UPDATE');
        if ($iPos !== false && ($iPos > $uPos || $uPos === false)) {
            return self::createPrepareFields($data);
        }

        if ($uPos !== false && ($uPos > $iPos || $iPos === false)) {
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
     * Распарсить часть expression
     *
     * @param string $sql - шаблон SQL-запроса
     * @param array $data - данные для выполнения запроса
     *
     * @return array
     */
    public static function parseExpressions(string &$sql, array &$data): array
    {
        if (preg_match_all('/\[' . self::EXPRESSION_LABEL . ':not\((.+?)\)\]/i', $sql, $match, PREG_OFFSET_CAPTURE)) {
            $new_data = [];
            foreach ($match[0] as $key => &$value) {
                if ($value) {
                    if (isset($data[0]) && \is_array($data[0])) {
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

                    $sql = substr_replace($sql, self::createExpression($sql, $new_data, $value[1]), $value[1], \strlen($value[0]));
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

        if (preg_match_all('/\[' . self::EXPRESSION_LABEL . '\]/i', $sql, $match, PREG_OFFSET_CAPTURE)) {
            foreach ($match[0] as $key => &$value) {
                if ($value) {
                    $sql = substr_replace($sql, self::createExpression($sql,$data, $value[1]), $value[1], \strlen($value[0]));
                }
            }

            unset($value);
        }

        return [$sql, $data];
    }

    /**
     * Распарсить часть fields
     *
     * @param string $sql - шаблон SQL-запроса
     * @param array $data - данные для выполнения запроса
     *
     * @return array
     */
    public static function parseFields(string &$sql, array &$data): array
    {
        if (preg_match_all('/\[' . self::FIELDS_LABEL . '\:not\((.+?)\)\]/i', $sql, $match)) {
            foreach ($match[0] as $key => &$value) {
                if ($value) {
                    if (isset($data[0]) && \is_array($data[0])) {
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

        if (stripos($sql, '[' . self::FIELDS_LABEL . ']')) {
            $sql = str_replace('[' . self::FIELDS_LABEL . ']', self::createSelectString($data), $sql);
        }

        return [$sql, $data];
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
        if (!preg_match('/(\[' . self::EXPRESSION_LABEL . '\]|\[' . self::FIELDS_LABEL . '\]|[^:]+?:[\w_\-]+.*?)/i', $sql)) {
            return [$sql, $data];
        }

        self::parseFields($sql, $data);
        self::parseExpressions($sql, $data);
        self::parseOptional($sql, $data);

        if ($convertArrays) {
            self::createAllPostgresArrayPlaceholders($sql, $data);
        }

        self::removeExcessSQLArgs($sql, $data);

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
                if (isset($data[0]) && \is_array($data[0])) {
                    foreach ($data as $index => &$value) {
                        $tmp = '';
                        foreach ($value as $k => &$v) {
                            $tmp .= ":$k$index,";
                            $dataTmp[$k . $index] = $v;
                        }

                        unset($v);

                        $string .= '(' . trim($tmp, ',') . '),';
                    }

                    unset($value);

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
     * @param string $castType - к какому типу приводить полученный массив
     * @param bool $insertValues - вставлять значения, а не плейсхолдеры
     *
     * @return array
     */
    public static function createPostgresArrayPlaceholders(array &$array, string $fieldName = null, string $castType = '', bool $insertValues = false): array
    {
        $fieldName = $fieldName ?? 'array';
        $placeholders = $newArray = [];
        foreach ($array as $i => &$value) {
            if ($insertValues) {
                $placeholders[] = "'$value'";
                continue;
            }

            $name = "$fieldName$i";
            $placeholders[] = ":$name";
            $newArray[$name] = $value;
        }

        unset($value);

        $array = $newArray;

        if ($castType) {
            $castType = "::$castType";
        }

        return ['ARRAY[' . implode(', ', $placeholders) . "]$castType" , $array];
    }

    /**
     * Заменить PHP-массив в параметрах запроса Postgres-массивом
     *
     * @param string $sql - текст запроса
     * @param array $record - параметры запроса
     * @param string $fieldName - какое поле заменяем
     * @param string $castType - к какому типу приводить полученный массив
     * @param bool $insertValues - вставлять значения, а не плейсхолдеры
     *
     * @return array
     */
    public static function replacePostgresArray(string &$sql, array &$record, string $fieldName, string $castType = '', bool $insertValues = false): array
    {
        if (empty($record[$fieldName]) || !\is_array($record[$fieldName])) {
            return [$sql, $record];
        }

        [$placeholders, $newValue] = self::createPostgresArrayPlaceholders($record[$fieldName], $fieldName, $castType, $insertValues);
        $sql = preg_replace("/:$fieldName/i", $placeholders, $sql);
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
        if (empty($data[0]) || !\is_array($data[0])) {
            $data = [$data];
        }

        foreach ($data as &$record) {
            foreach (array_keys($record) as &$key) {
                self::replacePostgresArray($sql, $record, $key);
            }

            unset($key);
        }

        unset($record);

        $data = \count($data) > 1 ? $data : reset($data);
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
        if (preg_match_all('/[^:]+?:([\w_\-]+).*?/i', $sql, $matches))
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
}