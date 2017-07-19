# Task Checker

[![Build status](https://travis-ci.org/codedokode/task-checker.svg?branch=master)](https://travis-ci.org/codedokode/task-checker)

Библиотека для написания тестов для проверки решений задач на PHP. Пока на этапе проектирования и демо-версии. 

Пример теста можно увидеть в [cli/run-test/scenario.php](./cli/run-test/scenario.php), в той же папке находятся примеры программ, которые проверяет этот тест.

## Установка 

Необходимо выполнить команду `composer install`. 

## Запуск 

Для демонстрации работы библиотеки используется скрипт [cli/run-test.php](./cli/run-test.php), которому надо передать в аргументах файл сценария проверки и файл с проверяемой программой. Результаты проверки выводятся в консоль. Используется PHP 5.4+. Пример команды: 

```
php cli/run-test.php cli/run-test/scenario.php cli/run-test/program1.php
```

Программа выводит в консоль текст в кодировке utf-8. Если под Windows этот текст не отображается, его можно сохранить в файл (дописав `> result.txt` к команде) и просмотреть файл редактором. 

## Юнит-тесты

В библиотеке есть несколько юнит-тестов, тестирующих внутренние компоненты библиотеки. Для их запуска необходимо использовать phpunit (проверено на phpunit 5.7), выполнив команду: 

```
php phpunit.phar .
```

## Разработка

В файле [draft/syntax-ideas.php](./draft/syntax-ideas.php) находятся разные идеи по ситаксису сценариев проверки.

Хорошо бы делать все запуски проверяемой программы асинхронными, чтобы их можно было выполнять параллельно или группировать (сейчас так и сделано).

К сожалению, в коде наблюдается нехватка комментариев. Принцип работы можно понять по скрипту [cli/run-test.php](./cli/run-test.php). Создается объект-тестировщик, ему передается сценарий проверки, проверяемая программа, проводится проверка. На выходе получается отчет о проверке в объекте Report, который затем печатается.

Используется немного запутанная система DI, которая позволяет подключать модули внутри сценария проверки с помощью конструкций вида `$this->moduleName`, например, `$this->assert` создает объект класса `Module\Assert`. Идея взята из фреймворка Codeception.

Общая структура кода: 

```
src/
    Codebot/       # Заглушка, выполняющая код программы с помощью eval
    Errors/        # Объекты ошибок, обнаруживаемые тестами. Используются
                     для построения отчета о проверке
    Module/        # Модули, которые будут доступны внутри сценария 
                        проверки
    Reporter/      # Классы для сборки и вывода отчета о проверке
            Report.php    # Класс для сборки отчета. Отчет состоит из 
                              шагов, каждый из которых представлен отдельным 
                              объектом Step\Step. Шаги могут быть вложены 
                              друг в друга.
    Step/          # Классы, описывающие шаги теста
    Test/          # Классы, описывающие объект-тестировщик, проверяющий 
                      программу
    TextReader/    # Разбирает используемые в сценарии проверки выражения
    TextScanner/   # Классы для обработки и замены фрагментов кода на PHP, 
                        используются для подстановки значений переменных 
                        в программу.
    Util/
    ModuleFactory.php    # Реализует DI в объекте-тестировщике
```

## Сценарии проверки задач

Сценарии проверки хранятся в папке [scenarios](./scenarios). Структура примерно такая: 

```
scenarios/
        hello-world/          - идентификатор задачи (hello-world)
                  tester.php  - скрипт проверки решения
                  /fail/      - примеры неправильных решений
                      example1.php 
                      example2.php 
                  /pass/      - примеры правильных решений
                      example1.php
                      example2.php
```

Специальный тест (ScenariosIntegrationTest) тестирует все задачи и все варианты решений к ним, проверяя, что правильные решения проходят тест, а неправильные - нет.
