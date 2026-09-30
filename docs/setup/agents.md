# Свои агенты: planner и scout

## Роли

| Агент | Файл | mode | Что можно | Что нельзя |
|---|---|---|---|---|
| planner | `.kilo/agents/planner.md` | primary | читать код и доки; писать только в `docs/plan/**`; поручать поиск субагенту (`task` → scout) | `edit` везде, кроме `docs/plan/**`; `bash` целиком |
| scout | `.kilo/agents/scout.md` | subagent | читать и искать по коду (`read`, `grep`, `glob`) | `edit` и `bash` целиком; предлагать исправления |

planner строит план до кода, scout по его поручению в отдельной сессии находит места в коде и возвращает список «файл, строка, что там».

## Вызов scout из planner

Сессия planner, модель MiniMax M3, промпт: `@scout найди все места, где читается пробег (mileage)`.
planner вызвал scout через `task`; scout отработал только `grep`/`read`/`glob`, ничего не менял.

Что вернул scout (строки сверены с кодом):

| Файл | Строка | Что там |
|---|---|---|
| `frontend/index.html` | 31 | поле формы `name="mileage"`, значение по умолчанию 84000 |
| `frontend/app.js` | 8 | `mileage` в `NUMERIC_FIELDS` — приводится к числу в payload |
| `backend/src/Domain/ApplicationValidator.php` | 43 | чтение из payload: `(int) ($payload['mileage'] ?? -1)` |
| `backend/src/Domain/ApplicationValidator.php` | 44–45 | проверка диапазона `0..max_mileage_km` и текст ошибки |
| `backend/src/Domain/ApplicationValidator.php` | 78 | нормализованный `mileage` в результате валидации |
| `backend/config/rules.php` | 23 | порог `'max_mileage_km' => 500000` |
| `backend/src/Repository/ApplicationRepository.php` | 38–39, 45 | INSERT в `vehicles.mileage_km` из `$input['mileage']` |
| `backend/src/Repository/ApplicationRepository.php` | 68 | SELECT `v.mileage_km` в `find()` |
| `db/schema.sql` | 22 | колонка `vehicles.mileage_km INT UNSIGNED NOT NULL` |
| `db/seed.sql` | 31 | `mileage_km` в seed-данных `vehicles` |
| `tests/Unit/ApplicationValidatorTest.php` | 34 | фикстура `mileage: 84000` |
| `tests/Unit/AssessmentServiceTest.php` | 38 | фикстура `mileage: 96000` |

Вывод scout: в расчёте решения (`DecisionEngine`, `LtvCalculator`, `AssessmentService`) пробег не участвует — только валидация и сохранение в БД.

## ★ planner без @scout

Промпт: `найди все места, где читается пробег (mileage)` — без упоминания scout.
planner сам вызвал scout через `task` («выполню через scout-агента, чтобы получить точные пути и строки»); результат совпал с ручным вызовом.

## ★ planner против встроенного plan (1.4.3, тот же промпт, GLM 5.3)

1. Структура совпала (4 раздела, границы 399999 / 400000 / 400001 и пустой пробег, вопросы заказчику): plan прочитал уже готовый `docs/plan/plan_MILEAGE.md` и взял его за основу, так что это не сравнение «с нуля».
2. Права: plan попытался править `docs/plan/plan_MILEAGE.md` и получил отказ (ему можно только `.kilo/plans/`), свой вариант положил туда; planner пишет прямо в `docs/plan/`, куда просит задача.
3. По содержанию plan поймал ошибку planner: риск 5 утверждал, что забытую правку `AppFactory:37` ловит `AssessmentServiceTest`, а тест собирает сервис сам — planner по замечанию исправил риск; ещё plan заметил, что докблок `DecisionEngine` пишет `LTV <= approve_max`, а код сравнивает строго `<`.
