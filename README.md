PHP Tomita Parser wrapper
=========================

Небольшой враппер для удобной работы с Томита Парсер от Яндекса.

Пример
------

```php

$p = new Tomita\TomitaParser('/home/user/tomita-parser', '/home/user/config.proto');
$result = $p->run('Text to parse');
```

Важно
-----

- config.proto не должен содержать дескрипторов File (ввод/вывод осуществляется через STDIN/STDOUT)
- Формат вывода — xml


За пример спасибо автору [poor-python-yandex-tomita-parser](https://github.com/vas3k/poor-python-yandex-tomita-parser).
