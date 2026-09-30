# Судейский отчёт по MILEAGE

Проверены спецификация, intent, план, реализация и юнит-тесты. Ссылки ниже имеют вид `файл:строка`; выводы о ходе работы, для которых в рабочем дереве нет артефакта, явно отмечены как непроверяемые по файлам.

## 1. Покрытие REQ

| Требование | Тест(ы), проверяющие требование | Основание |
| --- | --- | --- |
| REQ-MILEAGE-01 | `DecisionEngineTest::testSendsToReviewWhenMileageAboveThreshold`; `AssessmentServiceTest::testSendsToReviewWithZeroLimitWhenMileageAboveThreshold`; `AssessmentServiceTest::testSendsToReviewAtMaxValidMileage` | Первые два проверяют `400001 -> review` и лимит `0` (`tests/Unit/DecisionEngineTest.php:54-57`, `tests/Unit/AssessmentServiceTest.php:81-87`); третий — срабатывание правила на `500000` (`tests/Unit/AssessmentServiceTest.php:105-110`). |
| REQ-MILEAGE-02 | `DecisionEngineTest::testKeepsApproveWhenMileageBelowReviewThreshold`; `DecisionEngineTest::testKeepsApproveAtExactMileageThreshold`; `AssessmentServiceTest::testKeepsApproveAtExactMileageThreshold`; `AssessmentServiceTest::testKeepsApproveWhenMileageIsZero` | Проверены значения `399999`, `400000` и `0`, каждое с результатом `approve` (`tests/Unit/DecisionEngineTest.php:44-52`, `tests/Unit/AssessmentServiceTest.php:89-95`, `tests/Unit/AssessmentServiceTest.php:113-118`). |
| REQ-MILEAGE-03 | `DecisionEngineTest::testKeepsReviewWhenMileageAboveThresholdAndLtvInReviewZone` | При LTV `72.3` и пробеге `400001` остаётся `review` (`tests/Unit/DecisionEngineTest.php:59-62`). |
| REQ-MILEAGE-04 | `DecisionEngineTest::testKeepsRejectWhenMileageAboveThresholdAndLtvAboveReviewMax`; `AssessmentServiceTest::testKeepsRejectWhenMileageAboveThresholdAndLtvHigh` | При пробеге `400001` и LTV `95.0` проверяется `reject`; сервисный тест дополнительно проверяет лимит `0` (`tests/Unit/DecisionEngineTest.php:64-67`, `tests/Unit/AssessmentServiceTest.php:97-103`). |
| REQ-MILEAGE-05 | `ApplicationValidatorTest::testRejectsMissingMileage`; `ApplicationValidatorTest::testRejectsNullMileage`; `AssessmentServiceTest::testThrowsWhenMileageIsMissing` | Первые два проверяют `ValidationException`, ключ и текст ошибки для отсутствующего/null пробега (`tests/Unit/ApplicationValidatorTest.php:83-104`); сервисный тест подтверждает, что для отсутствующего ключа `assess()` завершается исключением (`tests/Unit/AssessmentServiceTest.php:120-125`). Отдельного теста, который наблюдает отсутствие вызова расчёта решения для `null`, нет. |
| REQ-MILEAGE-06 | `DecisionEngineTest::testDecidesByLtv`, строки провайдера `ltvValues`: «сразу под порогом approve», «ровно на пороге approve», «верхняя граница серой зоны», «сразу за верхней границей»; также `AssessmentServiceTest::testApprovesLowLtvAndSetsLimitToRequestedAmount`, `::testSendsMiddleLtvToReviewWithZeroLimit`, `::testRejectsHighLtv` | Провайдер задаёт `59.9 -> approve`, `60.0 -> review`, `85.0 -> review`, `85.01 -> reject` (`tests/Unit/DecisionEngineTest.php:23-40`). Сервисные регрессии покрывают все три зоны LTV (`tests/Unit/AssessmentServiceTest.php:53-79`). |
| REQ-MILEAGE-07 | `AssessmentServiceTest::testSendsToReviewAtMaxValidMileage`; `ApplicationValidatorTest::testRejectsMileageAboveValidationMax`; `AssessmentServiceTest::testKeepsApproveWhenMileageIsZero` | Проверены допустимые границы `0` и `500000` и отклонение `500001` (`tests/Unit/AssessmentServiceTest.php:105-118`, `tests/Unit/ApplicationValidatorTest.php:107-115`). |
| REQ-MILEAGE-08 | `AssessmentServiceTest::testSendsToReviewWithZeroLimitWhenMileageAboveThreshold`; `::testKeepsApproveAtExactMileageThreshold`; `::testKeepsRejectWhenMileageAboveThresholdAndLtvHigh`; `::testSendsToReviewAtMaxValidMileage` | Проверяются лимиты `0`, сумма заявки, `0`, `0` соответственно (`tests/Unit/AssessmentServiceTest.php:81-110`). |

### REQ без теста

Нет: для каждого REQ-MILEAGE-01—08 есть хотя бы один юнит-тест из таблицы. Частичное ограничение: отсутствие вычисления решения при `mileage: null` не наблюдается отдельно; тестируется только исключение валидатора (`tests/Unit/ApplicationValidatorTest.php:96-104`).

## 2. Покрытие AC

| AC | Тест (класс::метод или строка провайдера) | Основание |
| --- | --- | --- |
| AC-MILEAGE-01 | `DecisionEngineTest::testSendsToReviewWhenMileageAboveThreshold`; `AssessmentServiceTest::testSendsToReviewWithZeroLimitWhenMileageAboveThreshold` | `400001`, LTV `50.0`, `review`, лимит `0` (`tests/Unit/DecisionEngineTest.php:54-57`, `tests/Unit/AssessmentServiceTest.php:81-87`). |
| AC-MILEAGE-02 | `DecisionEngineTest::testKeepsApproveAtExactMileageThreshold`; `AssessmentServiceTest::testKeepsApproveAtExactMileageThreshold` | `400000`, LTV `50.0`, `approve`, лимит суммы (`tests/Unit/DecisionEngineTest.php:49-52`, `tests/Unit/AssessmentServiceTest.php:89-95`). |
| AC-MILEAGE-03 | `DecisionEngineTest::testKeepsApproveWhenMileageBelowReviewThreshold` | `399999`, LTV `50.0`, `approve` (`tests/Unit/DecisionEngineTest.php:44-47`). |
| AC-MILEAGE-04 | `DecisionEngineTest::testKeepsReviewWhenMileageAboveThresholdAndLtvInReviewZone` | `400001`, LTV `72.3`, `review` (`tests/Unit/DecisionEngineTest.php:59-62`). |
| AC-MILEAGE-05 | `DecisionEngineTest::testKeepsRejectWhenMileageAboveThresholdAndLtvAboveReviewMax`; `AssessmentServiceTest::testKeepsRejectWhenMileageAboveThresholdAndLtvHigh` | `400001`, LTV `95.0`, `reject`, а сервисный тест — ещё и лимит `0` (`tests/Unit/DecisionEngineTest.php:64-67`, `tests/Unit/AssessmentServiceTest.php:97-103`). |
| AC-MILEAGE-06 | `ApplicationValidatorTest::testRejectsMissingMileage`; `AssessmentServiceTest::testThrowsWhenMileageIsMissing` | Первый проверяет ключ и текст ошибки, второй — исключение при `assess()` (`tests/Unit/ApplicationValidatorTest.php:83-93`, `tests/Unit/AssessmentServiceTest.php:120-125`). |
| AC-MILEAGE-07 | `ApplicationValidatorTest::testRejectsNullMileage` | Проверяет `ValidationException`, ключ и текст для `null` (`tests/Unit/ApplicationValidatorTest.php:96-104`). Невызов расчёта решения этим тестом не наблюдается. |
| AC-MILEAGE-08 | `DecisionEngineTest::testDecidesByLtv`, провайдер `ltvValues`, строка «сразу под порогом approve» | `59.9`, пробег `96000`, `approve` (`tests/Unit/DecisionEngineTest.php:23-26`, `tests/Unit/DecisionEngineTest.php:35`). |
| AC-MILEAGE-09 | `DecisionEngineTest::testDecidesByLtv`, провайдер `ltvValues`, строка «ровно на пороге approve» | `60.0`, пробег `96000`, `review` (`tests/Unit/DecisionEngineTest.php:23-26`, `tests/Unit/DecisionEngineTest.php:36`). |
| AC-MILEAGE-10 | `DecisionEngineTest::testDecidesByLtv`, провайдер `ltvValues`, строка «верхняя граница серой зоны» | `85.0`, пробег `96000`, `review` (`tests/Unit/DecisionEngineTest.php:23-26`, `tests/Unit/DecisionEngineTest.php:38`). |
| AC-MILEAGE-11 | `DecisionEngineTest::testDecidesByLtv`, провайдер `ltvValues`, строка «сразу за верхней границей» | `85.01`, пробег `96000`, `reject` (`tests/Unit/DecisionEngineTest.php:23-26`, `tests/Unit/DecisionEngineTest.php:39`). |
| AC-MILEAGE-12 | `AssessmentServiceTest::testSendsToReviewAtMaxValidMileage` | `500000`, LTV `50.0`, `review`, лимит `0` (`tests/Unit/AssessmentServiceTest.php:105-110`). |
| AC-MILEAGE-13 | `ApplicationValidatorTest::testRejectsMileageAboveValidationMax` | `500001` даёт `ValidationException` с ключом `mileage` (`tests/Unit/ApplicationValidatorTest.php:107-115`). Текст ошибки и отсутствие вызова решения не наблюдаются этим тестом. |
| AC-MILEAGE-14 | `AssessmentServiceTest::testKeepsApproveWhenMileageIsZero` | `0`, LTV `50.0`, `approve` (`tests/Unit/AssessmentServiceTest.php:113-118`). |

### AC без теста

Нет AC без тестового соответствия. Однако полная формулировка AC-07 («решение не рассчитывается») и часть формулировки AC-13 (текст ошибки и отсутствие расчёта) не проверяются наблюдаемыми ассерциями: указанные тесты проверяют только исключение и/или ключ ошибки (`tests/Unit/ApplicationValidatorTest.php:96-115`).

### Тесты, не проверяющие ни один REQ/AC MILEAGE

- `LtvCalculatorTest::*`: проверяют формулу LTV и её собственные ошибочные входы, а не правила пробега, LTV-решения или валидации MILEAGE (`tests/Unit/LtvCalculatorTest.php:21-55`).
- `VinValidatorTest::testValidatesVinFormat`: проверяет формат VIN, которого в REQ/AC MILEAGE нет (`tests/Unit/VinValidatorTest.php:20-38`).
- `ApplicationValidatorTest::testAcceptsValidApplicationAndNormalisesVin`, `::testRejectsYearInTheFuture`, `::testRejectsAmountBelowMinimum`, `::testCollectsAllErrorsAtOnce`: это нормализация VIN либо ограничения года/суммы/прочих полей, не MILEAGE (`tests/Unit/ApplicationValidatorTest.php:41-80`).
- Строки провайдера `ltvValues` «низкий LTV», «середина зелёной зоны», «серая зона», «высокий LTV» — дополнительные проверки внутренних зон LTV, но не отдельные AC с заданными граничными числами (`tests/Unit/DecisionEngineTest.php:33-40`). Они всё же относятся к общему REQ-MILEAGE-06, поэтому не являются тестами вне REQ.

## 3. Требования спеки, которых нет в intent

Нет. Спецификация прямо утверждает отсутствие требований сверх intent (`docs/spec/spec_MILEAGE.md:51-54`), а REQ-01—08 последовательно соответствуют constraints intent: правило и приоритеты (`docs/intent/intent_MILEAGE.md:26-29`), отсутствующий/null пробег (`docs/intent/intent_MILEAGE.md:30-32`), LTV (`docs/intent/intent_MILEAGE.md:33-36`), диапазон пробега (`docs/intent/intent_MILEAGE.md:37-40`) и лимит (`docs/intent/intent_MILEAGE.md:41-42`).

**Итог: «каждый REQ покрыт тестом, лишних требований нет» — да:** каждый REQ имеет тестовое покрытие; спека не добавляет требований сверх intent. Оговорка: несколько деталей AC о том, что решение *не рассчитывается*, не подтверждены отдельным наблюдаемым assertion (`tests/Unit/ApplicationValidatorTest.php:96-115`).

## 4. Где агент срезал угол

1. **Неточность, которую требовал исправить план, исправлена в нужном файле.** Раздел «Файлы» предписывает поправить именно докблок `backend/src/Domain/DecisionEngine.php`: он ссылается на «строки 7–13 докблок» и говорит привести его к семантике строгого `<` (`docs/plan/plan_MILEAGE.md:39`). Текущий докблок действительно содержит `LTV < approve_max -> approve` (`backend/src/Domain/DecisionEngine.php:7-15`), что соответствует коду: при `LTV >= approve_max` получается `review` (`backend/src/Domain/DecisionEngine.php:41-45`). План не требует менять отдельный LTV-комментарий в `rules.php`: для этого файла он требует добавить ключ `review_mileage_km` и не менять существующие `ltv.*` (`docs/plan/plan_MILEAGE.md:38`). Поэтому оставшийся комментарий `LTV <= approve_max -> approve` в конфиге неточен (`backend/config/rules.php:38-47`), но не является невыполнением данного пункта плана.

2. **Литерал порога в `AssessmentServiceTest` не нарушает план, но расходится с общей конвенцией AGENTS.** План для этого теста требует только «конструктор с порогом» и параметр пробега в хелпере; источник порога не задан (`docs/plan/plan_MILEAGE.md:43`). Формулировка «с порогом 400 000» также прямо используется планом для `DecisionEngineTest` (`docs/plan/plan_MILEAGE.md:42`), поэтому утверждать нарушение плана из-за `new DecisionEngine($rules['ltv'], 400000)` нельзя (`tests/Unit/AssessmentServiceTest.php:22-29`). Вместе с тем `400000` — бизнес-порог, вынесенный в `vehicle.review_mileage_km` (`backend/config/rules.php:20-26`), а общая конвенция требует не хардкодить бизнес-числа и брать пороги из `backend/config/rules.php` (`AGENTS.md:29-33`). Следовательно, это возможное нарушение конвенции AGENTS, не отклонение от плана. Production-код порог из конфигурации берёт (`backend/src/AppFactory.php:30-38`).

3. **Реализация по основным шагам плана выполнена, поэтому других файловых отклонений не найдено.** Добавлены порог и комментарий (`backend/config/rules.php:23-25`), изменены конструктор и сигнатура `decide()` (`backend/src/Domain/DecisionEngine.php:27-42`), передан пробег из сервиса (`backend/src/Domain/AssessmentService.php:30-39`) и конфигурационный порог из фабрики (`backend/src/AppFactory.php:30-38`). Набор тестов, перечисленный планом для AC-01—14, присутствует (`docs/plan/plan_MILEAGE.md:75-92`; `tests/Unit/DecisionEngineTest.php:44-67`; `tests/Unit/AssessmentServiceTest.php:81-125`; `tests/Unit/ApplicationValidatorTest.php:83-115`).

4. **Факты процесса из условия задания, не проверяемые по файлам.** В рабочем дереве нет промпта/лога запусков MiniMax M3, поэтому нельзя файловыми ссылками подтвердить два вызова запрещённого bash и последующее изменение прав. Этот факт учитывается как сообщённый в задании, но самостоятельно не верифицируется. Аналогично, по текущим файлам нельзя доказать содержание ошибочного отчёта о `Too few arguments to __construct()` или воспроизвести старую версию. Текущая сигнатура действительно требует два аргумента (`backend/src/Domain/DecisionEngine.php:27-35`), а актуальные тесты передают оба (`tests/Unit/DecisionEngineTest.php:17-20`, `tests/Unit/AssessmentServiceTest.php:25-29`); утверждение о поведении PHP на старом коде по одним текущим файлам не проверяется.

5. **Статусы CI и невозможность локального запуска также не подтверждаются рабочим деревом.** В файлах нет журнала CI для коммитов `9a2dcb6` и `5655924`, поэтому заявленные в задании результаты — failure для тестового коммита и success для кодового — нельзя подтвердить ссылкой `файл:строка`. План требовал выполнить `make test`, затем `make lint` (`docs/plan/plan_MILEAGE.md:52-53`); сам факт выполнения агентом по файлам не наблюдаем. Отчёт не утверждает, что эти команды запускались локально.
