# Микроразметка для описания заданий

Описания заданий в учебнике размечены с помощью микроразметки, что 
позволяет автоматически создать их список с помощью разбора HTML кода.

Микроразметка основана на спецификации [microformats2](http://microformats.org/wiki/microformats-2). В качестве корневого класса используется `h-php-problem`.

Пример разметки, который содержит все возможные элементы (эти элементы 
соответствуют полям класса `Problem`): 

```html
<!-- Идентификатор задачи указывается в поле id с префиксом 'problem-' -->
<div class="h-php-problem" id="problem-three-numbers">
    <h1 class="p-name">Задание 1</h1>

    <div class="e-description">
        <p>Дано 3 разных числа ($a, $b и $c). Определите, какое 
            из них самое большое и самое маленькое.</p>
        <p>Выведите эти 2 числа.</p>
    </div>

    <p>За основу можно взять 
        <a href="http://example.com/code-sample-1" 
            class="u-code-sample-url">образец кода</a>:
    </p>

    <pre class="p-code-sample">&lt;?php 
        $a = 1;
        $b = 2;
        $c = 3;
        echo "Самое большое число: \n";
        echo "Самое маленькое число: \n";
    </pre>

    <h2>Подсказки:</h2>

    <ul>
        <li class="e-hint">Можно использовать функцию min()</li>
        <li class="e-hint">Можно использовать функцию max()</li>
    </ul>

    <h2>Примеры:</h2>

    <p class="e-example">Для чисел 1, 2, 3 наибольшее и наименьшее будут 1 и 3</p>
    <p class="e-example">Для чисел 5, 6, 7 наибольшее и наименьшее будут 5 и 7</p>
</div>
```

Обязательными являются только `id`, `p-name` и `e-description`.

Класс [Parser](../src/ProblemParser/Parser.php) используется для разбора
микроразметки. Он возвращает объекты класса `Problem` для каждой найденной
задачи. 

Скрипт [../cli/test-parse-html.php](../cli/test-parse-html.php) может использоваться для тестирования парсинга.