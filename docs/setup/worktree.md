# Юнит-тесты: tests/Unit/

- `LtvCalculatorTest.php` — считает LTV в процентах (включая >100%) и бросает `InvalidArgumentException` при нулевой стоимости или неположительной сумме.
- `AssessmentServiceTest.php` — сквозной сценарий `AssessmentService`: LTV 50% → `approve` с лимитом равным запрошенной сумме, 75% → `review`, 95% → `reject`, оба с нулевым лимитом.
- `DecisionEngineTest.php` — решение по LTV по порогам из конфига: ≤60% `approve`, 60–85% `review`, >85% `reject` (проверяет границы 85.0/85.01).
- `VinValidatorTest.php` — формат VIN: 17 символов, регистронезависимость, запрещённые буквы I/O/Q, спецсимволы и пустая строка.
- `ApplicationValidatorTest.php` — валидация заявки: принимает корректные данные и нормализует VIN в верхний регистр, отклоняет год из будущего и сумму ниже минимума, собирает все ошибки разом.

Рабочая папка: `/Users/baglayradik/Documents/Projects/carmoney-lab-baglayradik/.kilo/worktrees/1-2-3`
Ветка: `1-2-3`
