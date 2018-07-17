# SqlTemplater

A helper class for the SQL standardization.

Includes several predefined directives that can be included in SQL queries for simplicity:

- ####[fields]
 
Instead of this part of the SQL query inserts a string of data keys that came to the input, i.e. if we have a query:

```
INSERT INTO
  user
 ([fields])
VALUES 
  ...
```

And data:

```
$data = [
    'name' = > 'Ivan'
    'surname' = > 'Fuckov'
];
```

then after processing the request will take the form:

```
INSERT INTO
  user
 (name, 
  surname)
VALUES 
  ...
```

This is useful if we do not know exactly which set of data will come to the entrance.

- #### [fields:not(...)]

The action is similar to <b> [fields]</b> + inside <i>: not(...) </i> (instead of points) you can specify a comma separated list of non-include fields, for example:

```
INSERT INTO
  user
 ([fields:not (sur)])
VALUES 
  ...
```

converted to:

```
INSERT INTO
  user
 (name)
VALUES 
  ...
```

with the same data.

- ####[expression]

Similar to <i > [fields]</i> only fills in another part of the query, for example:

Request:

```
INSERT INTO
  user
 ([fields])
VALUES 
  [expression]
```

and data:

```
$data = [
    'name' = > 'Ivan'
    'surname' = > 'Fuckov'
];
```

The result will be:

```
INSERT INTO
  user
 (name, 
    surname)
VALUES 
 (:name,
  : surname)
```

If there are multiple lines in the input:

```
$data = [
    [
        'name' = > 'Ivan'
        'surname' = > 'Fuckov'
    ],
    [
        'name' = > 'Vasiliy'
        'surname' = > 'Chekhov'
    ]
];
```

the result will be:

```
INSERT INTO
  user
 (name, 
  surname)
VALUES 
 (: name0,
  : surname0),
 (: name1,
   : surname1)
```

and the data will look like:

```
$data = [
    'name0' = > 'Ivan'
    'surname0' = > 'Fuckov'
    'name1' = > 'Vasiliy'
    'surname1' = > 'Chekhov'
];
```

- #### [expression:not(...)]

I think here everything is clear - the substitution of placeholders - all except those inside <i>not(...) </i>

<br>

It should be noted that if among the parameter values there is an array, it can also be correctly processed:

Request:

```
INSERT INTO
  user
 ([fields])
VALUES 
  [expression]
```

Characteristic:

```
$data = [
    'name' = > 'Ivan'
    'surname' = > 'Fuckov',
    'phone_numbers' => [
        '+7905555555',
        '+7904444444'
    ]
];
```

Result:

```
INSERT INTO
  user
 (name, 
  surname)
VALUES 
 (:name,
  : surname,
  ARRAY [: phone_numbers0, phone_numbers1])
```

This conversion can be disabled by passing the template parameter <I>$convertArrays</I> with the value <I>false</I>.

<br>

In addition, it is possible to use the optional parts of the query, which are used only if the parameters used in them, for example:

```
UPDATE
  *
FROM
  user
[WHERE 
  group =: group]
```

If data is transferred:

```
$data = [
    'group' = > 'admins'
];
```

then the resulting query will be:

```
UPDATE
  *
FROM
  user
WHERE 
  group =: group
```

and if the <I>group</i> parameter is passed, the condition will be completely thrown out and the query will return all users.

<br>

[Class documentation](docs_en)
