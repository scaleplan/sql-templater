# SqlTemplater

Класс хэлперов для SQL-шаблонизации.

Включает в себя несколько предопределенных директив, которые можно включать в SQL-запросы для упрощения:

- #### [fields]
 
Вместо такой части SQL-запроса вставляет строка ключей данных, пришедших на вход, т. е. если у нас есть запрос:

```sql
INSERT INTO
  user
 ([fields])
VALUES 
  ...
``` 

И данные:

```php
$data = [
    'name' => 'Ivan'
    'surname' => 'Fuckov'
];
```

то после обработки запрос примет вид:

```sql
INSERT INTO
  user
 (name, 
  surname)
VALUES 
  ...
``` 

Это полезно, если мы не знаем точно какой набор данных придет на вход.

- #### [fields:not(...)]

Действие аналогично **[fields]** + внутри *:not(...)* (вместо точек) можно указать список невключаемых полей через запятую, например:

```sql
INSERT INTO
  user
 ([fields:not(surname)])
VALUES 
  ...
``` 

преобразуется в:

```sql
INSERT INTO
  user
 (name)
VALUES 
  ...
``` 

при тех же данных.

- #### [expression]

Аналогична [fields] только заполняет другую часть запроса, например:

Запрос:

```sql
INSERT INTO
  user
 ([fields])
VALUES 
  [expression]
``` 

и данные:

```php
$data = [
    'name' => 'Ivan'
    'surname' => 'Fuckov'
];
```

Результатот будет:

```sql
INSERT INTO
  user
 (name, 
    surname)
VALUES 
 (:name,
  :surname)
``` 

Если строчек во входных данных несколько:

```php
$data = [
    [
        'name' => 'Ivan'
        'surname' => 'Fuckov'
    ],
    [
        'name' => 'Vasiliy'
        'surname' => 'Chekhov'
    ]
];
```

в результате будет:

```sql
INSERT INTO
  user
 (name, 
  surname)
VALUES 
 (:name0,
  :surname0),
 (:name1,
   :surname1)
``` 

и данные примут вид:

```php
$data = [
    'name0' => 'Ivan'
    'surname0' => 'Fuckov'
    'name1' => 'Vasiliy'
    'surname1' => 'Chekhov'
];
```

- #### [expression:not(...)]

Думаю тут все понятно - подстановка плейсхолдеров - всех кроме тех что внутри *not(...)*

<br>

Стоит отметить, что если среди значений параметров есть массив, то это тоже может корректно обрабатыватся:

Запрос:

```sql
INSERT INTO
  user
 ([fields])
VALUES 
  [expression]
``` 

Параметры:

```php
$data = [
    'name' => 'Ivan'
    'surname' => 'Fuckov',
    'phone_numbers' => [
        '+7905555555',
        '+7904444444'
    ]
];
```

Результат:

```sql
INSERT INTO
  user
 (name, 
  surname)
VALUES 
 (:name,
  :surname,
  ARRAY[:phone_numbers0, phone_numbers1])
``` 

Это преобразование можно отключать передачей параметра шаблонизации *$convertArrays* со значением *false*.

<br>

Кроме того, есть возможность использовать опциональные части запроса, применяющиеся только при наличие используемых в них параметров, например:

```sql
UPDATE
  *
FROM
  user
[WHERE 
  group = :group]
```

Если переданы данные:

```php
$data = [
    'group' => 'admins'
];
```

то результирующий запрос будет:

```sql
UPDATE
  *
FROM
  user
WHERE 
  group = :group
```

а если параметр *group* передан, то условие будет полностью выброшено, и запрос вернет всех пользователей.

<br>

[Документация класса](docs_ru)
