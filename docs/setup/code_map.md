# Карта кода: расчёт решения approve / review / reject

## Участствующие файлы

- `backend/src/Domain/AssessmentService.php` — оркестратор: валидация -> LTV -> решение -> лимит.
- `backend/src/Domain/ApplicationValidator.php` — нормализация и валидация полей заявки.
- `backend/src/Domain/VinValidator.php` — проверка VIN (длина, алфавит, запрещённые символы).
- `backend/src/Domain/VehicleAge.php` — возраст авто в полных годах.
- `backend/src/Domain/LtvCalculator.php` — расчёт LTV.
- `backend/src/Domain/DecisionEngine.php` — маппинг LTV в решение.
- `backend/config/rules.php` — пороги и лимиты (vin, vehicle, amount, term, ltv).

## Порядок вызовов

`AssessmentService::assess($payload)`:

1. `ApplicationValidator::validate($payload)` — нормализует vin, year, mileage,
   market_value, requested_amount, term_months; при ошибках бросает
   `ValidationException`. Внутри: `VinValidator::isValid()`, `VehicleAge::inYears()`.
2. `LtvCalculator::calculate(requested_amount, market_value)` —
   `сумма / стоимость * 100`, округление до 2 знаков.
3. `DecisionEngine::decide($ltv)`:
   - LTV < `ltv.approve_max` (60.0) -> `approve`
   - 60 <= LTV <= `ltv.review_max` (85.0) -> `review`
   - LTV > 85.0 -> `reject`
4. Результат: `vehicle_age` через `VehicleAge::inYears()`, `approved_limit`
   равен запрошенной сумме при approve и нулю в остальных случаях.

## Правило «пробег > 400 000 км -> review»: куда встаёт

- Место: `DecisionEngine::decide()` — новая ветка сравнения пробега с порогом
  (до или после LTV-проверок), а в `AssessmentService::assess()` — передача
  пробега в `decide()`. Альтернатива без правки `DecisionEngine`: переопределить
  решение в `assess()` сразу после вызова `decide()`.
- Порог 400 000 по конвенции кладётся в `rules.php` (рядом с
  `vehicle.max_mileage_km`).

### Что уже есть

- Нормализованный `mileage` (int) в `$input` после
  `ApplicationValidator::validate()`; он же попадает в результат `assess()`.
- Конфиг `rules.php`, куда можно добавить порог.

### Чего не хватает

- Передачи `mileage` и порога в `DecisionEngine::decide()`: сейчас сигнатура
  принимает только `float $ltv`.
- Самой ветки сравнения в `decide()`.
- Порога 400 000 в `rules.php`.

## Что сейчас проверяется про пробег

Только валидация в `ApplicationValidator::validate()`: пробег — целое число
от 0 до `vehicle.max_mileage_km` (500 000 из rules.php), иначе ошибка `mileage`
в `ValidationException`. На решение (approve/review/reject) пробег сейчас
не влияет. Отдельного mileage-класса/функции нет. Больше про пробег в коде нет.
