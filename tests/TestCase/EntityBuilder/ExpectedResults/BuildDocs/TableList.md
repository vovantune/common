## TestTableOne
description govno
### Поля:
* int `id` comment1
* int `col_enum`
* \Cake\I18n\Time `col_time` = 'CURRENT_TIMESTAMP' asdasd
* string `oldField`
* string `notExists`

## TestTableTwo
description qweqwe
### Поля:
* int `id`
* int `table_one_fk` blabla
* string `col_text` = NULL
* int `fieldAlias` blabla (алиас поля table_one_fk)
* string `virtualField`
### Связи:
* TestTableOne `$TestTableOne` TestTableOne.table_one_fk => TestTableTwo.id

