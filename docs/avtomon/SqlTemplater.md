<small>avtomon</small>

SqlTemplater
============

Класс методов для SQL-шаблонизации

Описание
-----------

Class SqlTemplater

Сигнатура
---------

- **class**.

Константы
---------

class устанавливает следующие константы:

- [`FIELDS_LABEL`](#FIELDS_LABEL) &mdash; Шаблон списка доступных полей запроса
- [`EXPRESSION_LABEL`](#EXPRESSION_LABEL) &mdash; Шаблон доступных выражений запроса
- [`OPTIONAL_TEMPLATE`](#OPTIONAL_TEMPLATE) &mdash; Регулярка поиска опцональных частей запроса

Методы
-------

Методы класса class:

- [`parseConditions()`](#parseConditions) &mdash; Актуализировать условия
- [`parseOptional()`](#parseOptional) &mdash; Актуализировать необязательные части запроса
- [`createExpression()`](#createExpression) &mdash; Собрать данные для вставки или изменениях
- [`parseExpressions()`](#parseExpressions) &mdash; Распарсить часть expression
- [`parseFields()`](#parseFields) &mdash; Распарсить часть fields
- [`sql()`](#sql) &mdash; Разбор SQL-шаблона
- [`createPrepareFields()`](#createPrepareFields) &mdash; Формирование строки плейсхолдеров для SQL-запросов
- [`createSelectString()`](#createSelectString) &mdash; Формирование списка полей для SQL-запросов
- [`createPostgresArrayPlaceholders()`](#createPostgresArrayPlaceholders) &mdash; Сформировать Postgres-массив из PHP-массива
- [`replacePostgresArray()`](#replacePostgresArray) &mdash; Заменить PHP-массив в параметрах запроса Postgres-массивом
- [`createAllPostgresArrayPlaceholders()`](#createAllPostgresArrayPlaceholders) &mdash; Перестроить запрос и набор данных, если данные содержат значения в виде массиво
- [`getSQLParams()`](#getSQLParams) &mdash; Получить из SQL-запроса все параметры
- [`removeExcessSQLArgs()`](#removeExcessSQLArgs) &mdash; Почистить параметры SQL-запроса от неиспользуемых в запросе

### `parseConditions()` <a name="parseConditions"></a>

Актуализировать условия

#### Сигнатура

- **public static** method.
- Может принимать следующий параметр(ы):
    - `$sql` (`string`) &mdash; - текст запроса
    - `$data` (`array`) &mdash; - параметры запроса
- Возвращает `array` value.

### `parseOptional()` <a name="parseOptional"></a>

Актуализировать необязательные части запроса

#### Сигнатура

- **public static** method.
- Может принимать следующий параметр(ы):
    - `$sql` (`string`) &mdash; - текст запроса
    - `$data` (`array`) &mdash; - параметры запроса
- Возвращает `array` value.

### `createExpression()` <a name="createExpression"></a>

Собрать данные для вставки или изменениях

#### Сигнатура

- **public static** method.
- Может принимать следующий параметр(ы):
    - `$sql` (`string`) &mdash; - текст запроса
    - `$data` (`array`) &mdash; - параметры запроса
    - `$pos` (`int`) &mdash; - позиция вставки
- Возвращает `string` value.

### `parseExpressions()` <a name="parseExpressions"></a>

Распарсить часть expression

#### Сигнатура

- **public static** method.
- Может принимать следующий параметр(ы):
    - `$sql` (`string`) &mdash; - шаблон SQL-запроса
    - `$data` (`array`) &mdash; - данные для выполнения запроса
- Возвращает `array` value.

### `parseFields()` <a name="parseFields"></a>

Распарсить часть fields

#### Сигнатура

- **public static** method.
- Может принимать следующий параметр(ы):
    - `$sql` (`string`) &mdash; - шаблон SQL-запроса
    - `$data` (`array`) &mdash; - данные для выполнения запроса
- Возвращает `array` value.

### `sql()` <a name="sql"></a>

Разбор SQL-шаблона

#### Сигнатура

- **public static** method.
- Может принимать следующий параметр(ы):
    - `$sql` (`string`) &mdash; - шаблон SQL-запроса
    - `$data` (`array`) &mdash; - данные для выполнения запроса
    - `$convertArrays` (`bool`) &mdash; - преобразовывать ли массивы PHP в массивы PostgreSQL
- Возвращает `array` value.

### `createPrepareFields()` <a name="createPrepareFields"></a>

Формирование строки плейсхолдеров для SQL-запросов

#### Сигнатура

- **public static** method.
- Может принимать следующий параметр(ы):
    - `$data` (`array`) &mdash; - массив данных
    - `$type` (`string`) &mdash; - тип строки плейсхолдеров
- Возвращает `string` value.

### `createSelectString()` <a name="createSelectString"></a>

Формирование списка полей для SQL-запросов

#### Сигнатура

- **public static** method.
- Может принимать следующий параметр(ы):
    - `$data` (`array`) &mdash; - массив данных
- Возвращает `string` value.

### `createPostgresArrayPlaceholders()` <a name="createPostgresArrayPlaceholders"></a>

Сформировать Postgres-массив из PHP-массива

#### Сигнатура

- **public static** method.
- Может принимать следующий параметр(ы):
    - `$array` (`array`) &mdash; - PHP-массив
    - `$fieldName` (`string`) &mdash; - префикс имен плейсхолдеров
    - `$castType` (`string`) &mdash; - к какому типу приводить полученный массив
    - `$insertValues` (`bool`) &mdash; - вставлять значения, а не плейсхолдеры
- Возвращает `array` value.

### `replacePostgresArray()` <a name="replacePostgresArray"></a>

Заменить PHP-массив в параметрах запроса Postgres-массивом

#### Сигнатура

- **public static** method.
- Может принимать следующий параметр(ы):
    - `$sql` (`string`) &mdash; - текст запроса
    - `$record` (`array`) &mdash; - параметры запроса
    - `$fieldName` (`string`) &mdash; - какое поле заменяем
    - `$castType` (`string`) &mdash; - к какому типу приводить полученный массив
    - `$insertValues` (`bool`) &mdash; - вставлять значения, а не плейсхолдеры
- Возвращает `array` value.

### `createAllPostgresArrayPlaceholders()` <a name="createAllPostgresArrayPlaceholders"></a>

Перестроить запрос и набор данных, если данные содержат значения в виде массиво

#### Сигнатура

- **public static** method.
- Может принимать следующий параметр(ы):
    - `$sql` (`string`) &mdash; - текст SQL-запроса
    - `$data` (`array`) &mdash; - массив данных для выполения запроса
- Возвращает `array` value.

### `getSQLParams()` <a name="getSQLParams"></a>

Получить из SQL-запроса все параметры

#### Сигнатура

- **public static** method.
- Может принимать следующий параметр(ы):
    - `$sql` (`string`) &mdash; - текст запроса
- Возвращает `array` value.

### `removeExcessSQLArgs()` <a name="removeExcessSQLArgs"></a>

Почистить параметры SQL-запроса от неиспользуемых в запросе

#### Сигнатура

- **public static** method.
- Может принимать следующий параметр(ы):
    - `$sql` (`string`) &mdash; - текст запроса
    - `$args` (`array`) &mdash; - параметры запроса
- Возвращает `array` value.

