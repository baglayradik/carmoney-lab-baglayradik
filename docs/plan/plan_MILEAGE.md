# План MILEAGE: пробег больше 400 000 км -> решение review

ID задачи: `MILEAGE`. Дата: 2026-09-30. Основа: `docs/spec/spec_MILEAGE.md`
(далее — спека): поведение берётся из её требований REQ-MILEAGE-01–08 и
критериев приёмки AC-MILEAGE-01–14. Спека опирается на
`docs/intent/intent_MILEAGE.md` и ответы заказчика из
`docs/intent/grill_MILEAGE.md`: все вопросы, которые этот план раньше держал
открытыми, закрыты заказчиком («Открытых вопросов нет»). Единственный
открытый вопрос intent — бизнес-обоснование правила и порога 400 000
(intent §4 п. 5, спека §4 п. 5) — адресован заказчику и на требования и этот
план не влияет.

Правило из постановки: «пробег авто не больше 400 000 км, иначе решение
review». Трактовки закреплены спекой: ровно 400 000 — допустимый пробег,
решение по LTV, `review` — с 400 001, сравнение строгое `>`
(REQ-MILEAGE-02); правило только понижает `approve` до `review`, `review`
не переопределяется, `reject` по LTV сохраняется (REQ-MILEAGE-01, -03, -04);
отсутствующий/null пробег — ошибка валидации (в API — HTTP 422), валидатор
не трогаем (REQ-MILEAGE-05).

Источники: спека (основа); `docs/intent/grill_MILEAGE.md` (итоговый список
решений); `docs/setup/code_map.md` (раздел «Правило "пробег > 400 000
км -> review"»); код, сверенный по состоянию на 2026-09-30. Все места, где
читается пробег, найдены поиском по `mileage|max_mileage` и совпали с картой
кода; субагент scout не потребовался — карта кода уже содержит полный
разбор, места сверены с кодом построчно.

Ключевой факт из карты кода: сейчас пробег участвует только в валидации
(`ApplicationValidator`, диапазон 0..500 000) и в сохранении в БД; на решение
approve/review/reject он не влияет. `DecisionEngine::decide()` принимает только
`float $ltv`.

## 1. Файлы

Всё, чего нет в списке, при реализации трогать нельзя. В скобках — какой шаг
и какие REQ закрывает файл; покрытие AC — в разделе 3.

- `backend/config/rules.php` (строка 23, секция `vehicle`; шаг 1, REQ-MILEAGE-01) — добавить новый ключ `review_mileage_km => 400000` с комментарием «пробег выше — заявка уходит в review»; существующие значения не менять: `max_mileage_km` 500 000 и `ltv.*` остаются как есть (REQ-MILEAGE-06, REQ-MILEAGE-07).
- `backend/src/Domain/DecisionEngine.php` (строки 7–13 докблок, 23–28 конструктор, 30–41 `decide()`; шаг 2, REQ-MILEAGE-01–04, REQ-MILEAGE-06) — дополнить схему решений в докблоке веткой пробега и заодно поправить существующую неточность: докблок обещает approve при `LTV <= approve_max`, а код сравнивает строго `<` (строки 10 и 32) — привести к семантике REQ-MILEAGE-06; в конструктор добавить второй аргумент `int $reviewMileageKm` и поле; сменить сигнатуру на `decide(float $ltv, int $mileage): string` и добавить ветку пробега.
- `backend/src/Domain/AssessmentService.php` (строка 33; шаг 4, REQ-MILEAGE-01) — передать `$input['mileage']` вторым аргументом в `$this->decisionEngine->decide()`. Расчёт лимита (строка 39) не трогать (REQ-MILEAGE-08).
- `backend/src/AppFactory.php` (строка 37; шаг 3, REQ-MILEAGE-01) — прокинуть `$rules['vehicle']['review_mileage_km']` вторым аргументом в `new DecisionEngine(...)`.
- `tests/Unit/DecisionEngineTest.php` (строка 17 конструктор, 23 вызов `decide()`, 29–36 провайдер; раздел 3) — конструктор с порогом 400 000; второй аргумент (безопасный пробег 96 000) в строках существующего провайдера LTV и две новые строки 59.9 / 60.0; новые кейсы по пробегу.
- `tests/Unit/AssessmentServiceTest.php` (строка 27 конструктор, 33–43 хелпер `payload()`; раздел 3) — конструктор с порогом; параметр пробега в хелпере (по умолчанию 96 000); новые тесты сценария целиком.
- `tests/Unit/ApplicationValidatorTest.php` (раздел 3; REQ-MILEAGE-05, REQ-MILEAGE-07) — новые тесты на отсутствующий/null пробег и пробег выше `max_mileage_km`: фиксация существующего поведения, сам валидатор не меняется.

## 2. Шаги

1. `rules.php`: добавить `vehicle.review_mileage_km => 400000` (REQ-MILEAGE-01 — носитель порога). Поведение пока не меняется — порог никем не читается.
2. `DecisionEngine` (REQ-MILEAGE-01–04, REQ-MILEAGE-06): конструктор `__construct(array{approve_max:float,review_max:float} $thresholds, int $reviewMileageKm)`; в `decide(float $ltv, int $mileage): string` порядок проверки — сначала reject по LTV (LTV > `review_max` — REQ-MILEAGE-04), затем review (LTV >= `approve_max` ИЛИ пробег > `review_mileage_km` — REQ-MILEAGE-01, REQ-MILEAGE-03), иначе approve. Точная семантика LTV-границ сохраняется (REQ-MILEAGE-06): approve при LTV < `approve_max`, review до `review_max` включительно, reject при LTV > `review_max`; сравнение пробега — строгое `>` (REQ-MILEAGE-02: ровно 400 000 не срабатывает).
3. `AppFactory`: передать порог в конструктор `DecisionEngine` (строка 37, REQ-MILEAGE-01).
4. `AssessmentService`: передать `$input['mileage']` в `decide()` (строка 33, REQ-MILEAGE-01). Лимит (строка 39) и остальной код сервиса не меняются (REQ-MILEAGE-08).
5. Тесты — по разделу 3: закрывают AC-MILEAGE-01–14, в том числе REQ-MILEAGE-05, REQ-MILEAGE-07 и REQ-MILEAGE-08, которые кода не меняют и фиксируются только тестами.
6. Проверка: `make test`, затем `make lint`. Шаги 2–5 делаются одним коммитом: между сменой сигнатуры `decide()` и обновлением тестов набор красный (ArgumentCountError), это ожидаемо, не откатывать.

Покрытие требований (где закрывается каждый REQ):

- REQ-MILEAGE-01 — шаги 1–4 (порог в конфиге, ветка в `decide()`, передача пробега, прокидка); тесты: AC-01, AC-12.
- REQ-MILEAGE-02 — шаг 2 (строгое `>`); тесты: AC-02, AC-03, AC-14.
- REQ-MILEAGE-03 — шаг 2 (review-ветка «LTV ИЛИ пробег»); тест: AC-04.
- REQ-MILEAGE-04 — шаг 2 (reject по LTV — первой веткой); тест: AC-05.
- REQ-MILEAGE-05 — код не меняется (валидатор не трогаем, grill вопрос 2); фиксируется тестами AC-06, AC-07.
- REQ-MILEAGE-06 — шаг 2 (семантика LTV-границ) + регрессия провайдера `ltvValues`; тесты: AC-08, AC-09, AC-10, AC-11.
- REQ-MILEAGE-07 — код не меняется (`max_mileage_km` 500 000 остаётся, шаг 1 добавляет только новый ключ); тесты: AC-12, AC-13.
- REQ-MILEAGE-08 — код не меняется (`AssessmentService:39`); ассерты лимита в тестах AC-01, AC-02, AC-05, AC-12.

## 3. Тесты

Уровень — домен, юнит-тесты: спека §3 проверяет AC на уровне домена,
сквозная HTTP-проверка — вне задачи (спека §1 «Не входит», п. 5).
Существующие фикстуры (`mileage` 84 000 и 96 000) ниже порога — ожидания
старых тестов не меняются, кроме мест, где вызывается `decide()`: нужен
второй аргумент, безопасное значение — 96 000 (фикстура ниже порога,
спека §3).

Какой тест закрывает какой AC:

| AC | Тест(ы) |
| --- | --- |
| AC-MILEAGE-01 | `DecisionEngineTest::testSendsToReviewWhenMileageAboveThreshold` + `AssessmentServiceTest::testSendsToReviewWithZeroLimitWhenMileageAboveThreshold` (лимит 0) |
| AC-MILEAGE-02 | `DecisionEngineTest::testKeepsApproveAtExactMileageThreshold` + `AssessmentServiceTest::testKeepsApproveAtExactMileageThreshold` (лимит = запрошенной сумме) |
| AC-MILEAGE-03 | `DecisionEngineTest::testKeepsApproveWhenMileageBelowReviewThreshold` (399 999) |
| AC-MILEAGE-04 | `DecisionEngineTest::testKeepsReviewWhenMileageAboveThresholdAndLtvInReviewZone` |
| AC-MILEAGE-05 | `DecisionEngineTest::testKeepsRejectWhenMileageAboveThresholdAndLtvAboveReviewMax` + `AssessmentServiceTest::testKeepsRejectWhenMileageAboveThresholdAndLtvHigh` (лимит 0) |
| AC-MILEAGE-06 | `ApplicationValidatorTest::testRejectsMissingMileage` + `AssessmentServiceTest::testThrowsWhenMileageIsMissing` (решение не рассчитывается) |
| AC-MILEAGE-07 | `ApplicationValidatorTest::testRejectsNullMileage` |
| AC-MILEAGE-08 | `DecisionEngineTest`, провайдер `ltvValues`, новая строка 59.9 -> approve |
| AC-MILEAGE-09 | `DecisionEngineTest`, провайдер `ltvValues`, новая строка 60.0 -> review |
| AC-MILEAGE-10 | `DecisionEngineTest`, провайдер `ltvValues`, существующая строка 85.0 -> review |
| AC-MILEAGE-11 | `DecisionEngineTest`, провайдер `ltvValues`, существующая строка 85.01 -> reject |
| AC-MILEAGE-12 | `AssessmentServiceTest::testSendsToReviewAtMaxValidMileage` (500 000) |
| AC-MILEAGE-13 | `ApplicationValidatorTest::testRejectsMileageAboveValidationMax` (500 001) |
| AC-MILEAGE-14 | `AssessmentServiceTest::testKeepsApproveWhenMileageIsZero` (0) |

`tests/Unit/DecisionEngineTest.php` (LTV зелёной зоны — 50.0, если не сказано
иное):

- `testKeepsApproveWhenMileageBelowReviewThreshold` — пробег 399 999, LTV 50.0 -> approve (AC-03).
- `testKeepsApproveAtExactMileageThreshold` — пробег 400 000, LTV 50.0 -> approve (AC-02).
- `testSendsToReviewWhenMileageAboveThreshold` — пробег 400 001, LTV 50.0 -> review (AC-01).
- `testKeepsReviewWhenMileageAboveThresholdAndLtvInReviewZone` — пробег 400 001, LTV 72.3 -> review (AC-04).
- `testKeepsRejectWhenMileageAboveThresholdAndLtvAboveReviewMax` — пробег 400 001, LTV 95.0 -> reject (AC-05, REQ-04).
- Существующий провайдер `ltvValues` — каждой строке добавить второй аргумент (безопасный пробег 96 000), ожидания решений не меняются: регрессия LTV-семантики (REQ-06); существующие строки 85.0 -> review и 85.01 -> reject закрывают AC-10 и AC-11. Добавить две строки: 59.9 -> approve (AC-08) и 60.0 -> review (AC-09).

Граничные значения (каждое — отдельной строкой; источник — спека §3 «Числа
в AC»):

- 399 999 -> approve (AC-03)
- 400 000 -> approve (AC-02)
- 400 001 -> review (AC-01)
- 500 000 -> review: валидация проходит, правило срабатывает в верхней точке полосы 400 001–500 000 (AC-12)
- 500 001 -> ValidationException по полю `mileage` (AC-13)
- 0 -> approve (AC-14)
- LTV 59.9 -> approve (AC-08)
- LTV 60.0 -> review (AC-09)
- LTV 85.0 -> review (AC-10)
- LTV 85.01 -> reject (AC-11)
- 96 000 — безопасный пробег в провайдере LTV, фикстура ниже порога (AC-08–11)
- `mileage` отсутствует или равен null -> ValidationException с ошибкой `mileage` «Пробег от 0 до 500000 км», в API — HTTP 422, решение не считается (AC-06, AC-07; существующее поведение, не меняется)

`tests/Unit/AssessmentServiceTest.php` (хелпер `payload()` получает параметр
пробега, по умолчанию 96 000 — старые тесты не меняются):

- `testSendsToReviewWithZeroLimitWhenMileageAboveThreshold` — пробег 400 001, LTV 50.0 -> decision review, approved_limit 0 (раньше был approve) (AC-01, REQ-08).
- `testKeepsApproveAtExactMileageThreshold` — пробег 400 000, LTV 50.0 -> approve, approved_limit = запрошенной сумме (AC-02, REQ-08).
- `testKeepsRejectWhenMileageAboveThresholdAndLtvHigh` — пробег 400 001, LTV 95.0 -> reject, approved_limit 0 (AC-05, REQ-08).
- `testSendsToReviewAtMaxValidMileage` — пробег 500 000, LTV 50.0 -> review, approved_limit 0: верхняя точка полосы 400 001–500 000, валидация проходит (AC-12, REQ-07, REQ-08).
- `testKeepsApproveWhenMileageIsZero` — пробег 0, LTV 50.0 -> approve (AC-14).
- `testThrowsWhenMileageIsMissing` — payload без ключа `mileage` -> ValidationException, решение не рассчитывается (AC-06).

`tests/Unit/ApplicationValidatorTest.php` (код валидатора не меняется —
фиксация существующего поведения):

- `testRejectsMissingMileage` — в payload нет ключа `mileage` -> ValidationException, в `errors()` есть ключ `mileage` с текстом «Пробег от 0 до 500000 км» (AC-06).
- `testRejectsNullMileage` — `mileage => null` -> то же самое (`?? -1` относит null к отсутствию) (AC-07).
- `testRejectsMileageAboveValidationMax` — `mileage => 500001` -> ValidationException с той же ошибкой `mileage`: до расчёта решения не доходит, правило пробега не применяется (AC-13).

Существующее поведение, зафиксированное без нового теста: пустая строка `''`
в `mileage` сейчас нормализуется в 0 и проходит валидацию (`(int) '' === 0`);
решение при этом считается по LTV, так как 0 не больше 400 000 (производное
от REQ-MILEAGE-02, спека §1 «Не входит», п. 1). Кейс вне задачи, тест не
добавляем; фронт при пустом поле тоже отправит 0 (`Number('') === 0`, но поле
`required` со значением по умолчанию).

Ручная проверка (по желанию, после `make up`; сценарий AC-01):
`curl -X POST http://localhost:8080/api/ltv -d '{"vin":"XTA21099998765432","year":2019,"mileage":400001,"market_value":900000,"requested_amount":450000,"term_months":24}' -H 'Content-Type: application/json'` -> `decision: "review"`.

## 4. Риски

1. Существующая проверка `max_mileage_km` (500 000, `rules.php:23`, `ApplicationValidator:44–46`): новое правило работает только в полосе 400 001–500 000; пробег больше 500 000 в решение не доходит — это по-прежнему 422 валидации (REQ-MILEAGE-07, AC-13). Нельзя «выровнять» `max_mileage_km` до 400 000: изменится диапазон валидации и текст ошибки, это другая задача. Если порог `review_mileage_km` когда-нибудь поднимут выше 500 000, правило не сработает — валидация отсечёт раньше. Верх полосы закреплён тестом AC-12.
2. Смена сигнатуры `decide()`: смену чувствуют пять мест — вызовы `decide()` (`AssessmentService:33`, `DecisionEngineTest:23`) и вызовы конструктора `DecisionEngine` (`AppFactory:37` — риск 5, `DecisionEngineTest:17`, `AssessmentServiceTest:27`). Все обновляются одним коммитом, иначе ArgumentCountError. Красный `make test` между шагами 2 и 5 — ожидаемый, не признак бага.
3. Приоритет review/reject закреплён спекой (REQ-MILEAGE-04, grill вопрос 3): reject по LTV проверяется в `decide()` первым и правилом пробега не перекрывается. Смена приоритета («всегда review» вместо сохранения reject) возможна только новым решением заказчика — отдельная задача, в MILEAGE не предусматривается.
4. Заметное изменение поведения: заявки с пробегом 400 001–500 000 и низким LTV, которые раньше получали `approve` с лимитом = сумме, теперь уходят в `review` с `approved_limit = 0` (автоматически через `AssessmentService:39`, REQ-MILEAGE-08). Форма API-ответа не меняется — только значения; фронт и логи (`ApplicationController:36–42`) изменений кода не требуют.
5. Прокидка конфига: `DecisionEngine` теперь читает пороги из двух секций (`ltv` и `vehicle`). Забытая правка `AppFactory:37` даст ArgumentCountError — и юнит-тесты её не ловят: `AssessmentServiceTest:24–29` собирает граф сервиса сам (`:27` — `new DecisionEngine($rules['ltv'], порог)` после шага 5), минуя `AppFactory`; `tests/Feature` пуст, ни один тест не поднимает приложение, поэтому `make test` и `make lint` с такой дырой остаются зелёными (`make lint` — только `php -l`, нехватка аргумента — ошибка времени выполнения). Ошибка проявляется лишь на первом HTTP-запросе: `public/index.php:9` вызывает `AppFactory::create()`, сервис собирается до регистрации роутов (`AppFactory:30–39`), так что упадёт любой запрос, включая `GET /health` (500). Ловится ручной проверкой после `make up`: `curl http://localhost:8080/health`. Надёжное закрытие дыры — Feature-тест, собирающий приложение через `AppFactory`, но это ДЗ.1 и в план не входит (см. «Не входит»).
6. `rules.php` правится только решением человека (`docs/agent-rules.md`): в этой задаче человек прямо велел добавить порог — добавляем новый ключ; существующие значения (`max_mileage_km`, `ltv.*`, `amount.*`, `term.*`) не менять.
7. Данные: фикстуры тестов (84 000, 96 000) и максимум в `db/seed.sql` (296 000) ниже порога — регрессий в тестах и расхождений seed-заявок с новым правилом нет; `schema.sql`, `seed.sql`, репозиторий и фронтенд не трогаем.

### Не входит (в реализации не делать; спека §1 «Не входит»)

- расчёт лимита по `ltv_by_age` — отдельная задача LOAN-12 (спека §1 п. 4);
- изменение валидации пробега: диапазон 0–500 000, тексты ошибок, кейс пустой строки `''` -> 0 (спека §1 п. 1);
- пересчёт уже сохранённых решений — правило применяется только при расчёте по новой заявке (спека §1 п. 2);
- фронтенд, схема и данные БД, код сохранения заявок, форма API-ответа (спека §1 п. 3);
- HTTP Feature-тест уровня `tests/Feature` — отдельное ДЗ.1 (спека §1 п. 5); при желании туда кладётся POST `/api/ltv` с пробегом 400 001 -> review.
