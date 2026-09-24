# kilo_hello

готов

1) По README и корневым файлам — учебный сервис предварительной оценки заявки на заём под ПТС: принимает заявку (VIN, год, пробег, оценочная стоимость, сумма, срок), считает LTV и возвращает решение `approve` / `review` / `reject`; данные только синтетические.
2) В Makefile: `up`, `down`, `ps`, `logs`, `install`, `test` (PHPUnit), `lint` (php -l по `backend/` и `tests/`), `seed`, `help`; отдельных команд запуска и проверки в docker-compose.yml не нашёл — только описания сервисов `backend` (php -S 0.0.0.0:8080) и `db` (mysql:8.0) с healthcheck.
3) Решение `approve` / `review` / `reject` считается в папке `backend/src/Domain/` (класс `DecisionEngine.php`).

модель: MiniMax M3