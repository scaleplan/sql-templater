# SqlTemplater

Класс хэлперов для SQL-шаблонизации.

Включает в себя несколько предопределенных директив, которые можно включать в SQL-запросы для упрощения:

- ####  [fields]
 
Вместо такой части SQL-запроса вставляет строка ключей данных, пришедших на вход, т. е. если у нас есть запрос:

```
INSERT INTO
  user
 ([fields])
VALUES 
  ...
``` 

И данные:

```
$data = [
    'name' => 'Ivan'
    'surname' => 'Fuckov'
];
```

то после обработки запрос примет вид:

```
INSERT INTO
  user
 (name, 
  surname)
VALUES 
  ...
``` 

Это полезно, если мы не знаем точно какой набор данных придет на вход.

- #### [fields:not(...)]

Действие аналогично <b>[fields]</b> + внутри <i>:not(...)</i> (вместо точек) можно указать список невключаемых полей через запятую, например:

```
INSERT INTO
  user
 ([fields:not(surname)])
VALUES 
  ...
``` 

преобразуется в:

```
INSERT INTO
  user
 (name)
VALUES 
  ...
``` 

при тех же данных.

- ####  [expression]

Аналогична <i>[fields]</i> только заполняет другую часть запроса, например:

Запрос:

```
INSERT INTO
  user
 ([fields])
VALUES 
  [expression]
``` 

и данные:

```
$data = [
    'name' => 'Ivan'
    'surname' => 'Fuckov'
];
```

Результатот будет:

```
INSERT INTO
  user
 (name, 
    surname)
VALUES 
 (:name,
  :surname)
``` 

Если строчек во входных данных несколько:

```
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

```
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

```
$data = [
    'name0' => 'Ivan'
    'surname0' => 'Fuckov'
    'name1' => 'Vasiliy'
    'surname1' => 'Chekhov'
];
```

- #### [expression:not(...)]

Думаю тут все понятно - подстановка плейсхолдеров - всех кроме тех что внутри <i>not(...)</i>

<br>

Стоит отметить, что если среди значений параметров есть массив, то это тоже может корректно обрабатыватся:

Запрос:

```
INSERT INTO
  user
 ([fields])
VALUES 
  [expression]
``` 

Параметры:

```
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

```
INSERT INTO
  user
 (name, 
  surname)
VALUES 
 (:name,
  :surname,
  ARRAY[:phone_numbers0, phone_numbers1])
``` 

Это преобразование можно отключать передачей параметра шаблонизации <i>$convertArrays</i> со значением <i>false</i>.

<br>

Кроме того, есть возможность использовать опциональные части запроса, применяющиеся только при наличие используемых в них параметров, например:

```
UPDATE
  *
FROM
  user
[WHERE 
  group = :group]
```

Если переданы данные:

```
$data = [
    'group' => 'admins'
];
```

то результирующий запрос будет:

```
UPDATE
  *
FROM
  user
WHERE 
  group = :group
```

а если параметр <i>group</i> передан, то условие будет полностью выброшено, и запрос вернет всех пользователей.

<br>

[Документация класса](docs_ru)
