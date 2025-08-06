# Sportsgateway

## Рабочий процесс
- Ветка `main` заблокирована от любого вмешательства, и содержит финальный резултат.
- Вся разработка ведется в ветке `dev`.
- При разработке, наследуемся от ветки `dev`, например `feature-notify`, а после выполнения задачи делаем _merge_ `dev` < `feature-notify`.
- После того как мы делаем _push_ или _merge_ в ветку `dev`, содержимое папки `src` пушится в ветку `main`.

## Развертка окружения
- Установить [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- Склонировать репозиторий
- Открыть папку в IDE
- Выполнить сборка/установку контейнеров:
```bash
docker-compose up -d 
```

## Комманды
- Запустить работу докер контейнеров
```bash
docker-compose up -d 
```

- Завершить работу докер контейнеров
```bash
docker-compose down -v
```

- Подключиться к командной строке php который содержит
  - Composer
  - XDebug
  - Redis
  - Memcached
```bash
docker-compose exec php-fpm bash
```