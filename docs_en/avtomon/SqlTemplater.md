<small> avtomon </small>

SqlTemplater
============

Class of methods for SQL-template

Description
-----------

Class SqlTemplater

Signature
---------

- ** class**.

Constants
---------

class sets the following constants:

- [FIELDS_LABEL`](#FIELDS_LABEL) &mdash; Template of the list of available query fields
  - [`EXPRESSION_LABEL`](#EXPRESSION_LABEL) &mdash; Template of available query expressions
- ['OPTIONAL_TEMPLATE`](#OPTIONAL_TEMPLATE) &mdash; Regular search of the query parts

Methods
-------

Class methods class:

  - [`parseConditions()`](#parseConditions) &mdash; Update the conditions
  - [`parseOptional()`](#parseOptional) &mdash; Update optional parts of the query
  - [`createExpression()`](#createExpression) &mdash; Collect data for insertion or changes
  - [`parseExpressions()`](#parseExpressions) &mdash; Parse part of expression
  - [`parseFields()`](#parseFields) &mdash; Part of fields
  - [`sql()`](#sql) &mdash; Analysis of the SQL-template
  - [`createPrepareFields()`](#createPrepareFields) &mdash; Formation of a row of placeholders for SQL queries
  - [`createSelectString()`](#createSelectString) &mdash; Forming a list of fields for SQL queries
  - [`createPostgresArrayPlaceholders()`](#createPostgresArrayPlaceholders) &mdash; Generate a Postgres array from a PHP array
  - [`replacePostgresArray()`](#replacePostgresArray) &mdash; Replace the PHP array with the Postgres array query parameters
  - [`createAllPostgresArrayPlaceholders()`](#createAllPostgresArrayPlaceholders) &mdash; Rebuild the query and data set if the data contains values ​​in the form of an array
  - [`getSQLParams()`](#getSQLParams) &mdash; Get all the parameters from the SQL query
  - [`removeExcessSQLArgs()`](#removeExcessSQLArgs) &mdash; Clean the SQL query parameters from those that are not used in the query

### `parseConditions()`<a name="parseConditions"> </a>

Update the conditions

#### Signature

- ** public static** method.
- It can take the following parameter (s):
  - `$sql`(`string`) - the text of the request
  - `$data`(`array`) - request parameters
Returns the `array`value.

### `parseOptional()`<a name="parseOptional"> </a>

Update optional parts of the query

#### Signature

- ** public static** method.
- It can take the following parameter (s):
  - `$sql`(`string`) - the text of the request
  - `$data`(`array`) - request parameters
Returns the `array`value.

### `createExpression()`<a name="createExpression"> </a>

Collect data for insertion or changes

#### Signature

- ** public static** method.
- It can take the following parameter (s):
  - `$sql`(`string`) - the text of the request
  - `$data`(`array`) - request parameters
  - `$pos`(`int`) - insertion position
Returns `string`value.

### `parseExpressions()`<a name="parseExpressions"> </a>

Parse part of expression

#### Signature

- ** public static** method.
- It can take the following parameter (s):
  - `$sql`(`string`) - the SQL query template
  - `$data`(`array`) - data for query execution
Returns the `array`value.

### `parseFields()`<a name="parseFields"> </a>

Part of fields

#### Signature

- ** public static** method.
- It can take the following parameter (s):
  - `$sql`(`string`) - the SQL query template
  - `$data`(`array`) - data for query execution
Returns the `array`value.

### `sql()`<a name="sql"> </a>

Analysis of the SQL-template

#### Signature

- ** public static** method.
- It can take the following parameter (s):
  - `$sql`(`string`) - the SQL query template
  - `$data`(`array`) - data for query execution
  - `$convertArrays`(`bool`) - to convert arrays of PHP to PostgreSQL arrays
Returns the `array`value.

### `createPrepareFields()`<a name="createPrepareFields"> </a>

Formation of a row of placeholders for SQL queries

#### Signature

- ** public static** method.
- It can take the following parameter (s):
  - `$data`(`array`) - data array
  - `$type`(`string`) - the type of the row of placeholders
Returns `string`value.

### `createSelectString()`<a name="createSelectString"> </a>

Forming a list of fields for SQL queries

#### Signature

- ** public static** method.
- It can take the following parameter (s):
  - `$data`(`array`) - data array
Returns `string`value.

### `createPostgresArrayPlaceholders()`<a name="createPostgresArrayPlaceholders"> </a>

Generate a Postgres array from a PHP array

#### Signature

- ** public static** method.
- It can take the following parameter (s):
  - `$array`(`array`) - PHP array
  - `$fieldName`(`string`) - the prefix of the names of placeholders
  - `$castType`(`string`) - to what type should the resulting array
  - `$insertValues`(`bool`) - insert values, not placeholders
Returns the `array`value.

### `replacePostgresArray()`<a name="replacePostgresArray"> </a>

Replace the PHP array with the Postgres array query parameters

#### Signature

- ** public static** method.
- It can take the following parameter (s):
  - `$sql`(`string`) - the text of the request
  - `$record`(`array`) - query parameters
  - `$fieldName`(`string`) - what field is replaced
  - `$castType`(`string`) - to what type should the resulting array
  - `$insertValues`(`bool`) - insert values, not placeholders
Returns the `array`value.

### `createAllPostgresArrayPlaceholders()`<a name="createAllPostgresArrayPlaceholders"> </a>

Rebuild the query and data set if the data contains values ​​in the form of an array

#### Signature

- ** public static** method.
- It can take the following parameter (s):
  - `$sql`(`string`) - the text of the SQL query
  - `$data`(`array`) - data array for query execution
Returns the `array`value.

### `getSQLParams()`<a name="getSQLParams"> </a>

Get all the parameters from the SQL query

#### Signature

- ** public static** method.
- It can take the following parameter (s):
  - `$sql`(`string`) - the text of the request
Returns the `array`value.

### `removeExcessSQLArgs()`<a name="removeExcessSQLArgs"> </a>

Clean the SQL query parameters from those that are not used in the query

#### Signature

- ** public static** method.
- It can take the following parameter (s):
  - `$sql`(`string`) - the text of the request
  - `$args`(`array`) - request parameters
Returns the `array`value.

