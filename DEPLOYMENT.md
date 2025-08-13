# 🚀 Инструкции по развертыванию

## Выбор хостинга

### Рекомендуемые бесплатные хостинги:

1. **InfinityFree** (https://infinityfree.com/)
   - ✅ Бесплатный хостинг с PostgreSQL
   - ✅ Поддержка PHP 8.0
   - ⚠️ Активация через 72 часа
   - ⚠️ Ограниченные ресурсы

2. **Hostinger** (https://hostiman.ru/)
   - ✅ Бесплатный хостинг
   - ✅ Быстрая активация
   - ⚠️ Требует подтверждение паспортом
   - ⚠️ Ограниченная поддержка PostgreSQL

3. **000webhost** (https://000webhost.com/)
   - ✅ Бесплатный хостинг от Hostinger
   - ✅ Простая регистрация
   - ⚠️ Ограниченные ресурсы

4. **Render.com** (https://render.com/)
   - ✅ Бесплатный хостинг с PostgreSQL
   - ✅ Современная платформа
   - ⚠️ Ограничения на бесплатном плане

## Пошаговое развертывание

### Шаг 1: Подготовка файлов

1. Создайте архив всех файлов проекта:
```bash
zip -r order-system.zip . -x "*.git*" "*.DS_Store*"
```

2. Файлы для загрузки:
   - `index.html` - главная страница
   - `alpha.php` - скрипт генерации заказов
   - `beta.php` - скрипт массового запуска
   - `gamma.php` - скрипт статистики
   - `config.php` - конфигурация
   - `database.sql` - структура БД
   - `setup.php` - автоматическая настройка
   - `test_performance.php` - тест производительности
   - `.htaccess` - настройки сервера
   - `README.md` - документация

### Шаг 2: Настройка базы данных

#### Для хостингов с PostgreSQL:

1. Создайте базу данных PostgreSQL
2. Выполните SQL скрипт `database.sql`
3. Обновите настройки в `config.php`:
```php
define('DB_HOST', 'your_host');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_PORT', '5432');
```

#### Для хостингов без PostgreSQL:

Используйте MySQL версию (требует адаптации кода):
```sql
-- Адаптированный SQL для MySQL
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Шаг 3: Настройка Redis

#### Вариант 1: Встроенный Redis
Если хостинг поддерживает Redis:
```php
define('REDIS_HOST', 'localhost');
define('REDIS_PORT', 6379);
```

#### Вариант 2: Внешний Redis
Используйте бесплатные Redis хостинги:
- Redis Cloud (https://redis.com/try-free/)
- Upstash Redis (https://upstash.com/)

#### Вариант 3: Без Redis
Адаптируйте код для работы без Redis:
```php
// Простая файловая блокировка
function acquireLock($lockFile) {
    $fp = fopen($lockFile, 'w+');
    if (flock($fp, LOCK_EX | LOCK_NB)) {
        return $fp;
    }
    return false;
}
```

### Шаг 4: Загрузка на хостинг

1. **Через FTP/SFTP:**
   - Подключитесь к хостингу через FileZilla
   - Загрузите все файлы в корневую директорию
   - Установите права доступа 644 для файлов, 755 для директорий

2. **Через панель управления:**
   - Войдите в панель управления хостингом
   - Откройте файловый менеджер
   - Загрузите файлы через веб-интерфейс

### Шаг 5: Настройка и тестирование

1. Откройте `setup.php` в браузере для автоматической настройки
2. Проверьте работу всех компонентов
3. Запустите `test_performance.php` для тестирования производительности

## Специфичные инструкции по хостингам

### InfinityFree

1. **Регистрация:**
   - Зарегистрируйтесь на infinityfree.com
   - Дождитесь активации (72 часа)
   - Создайте сайт

2. **База данных:**
   - В панели управления создайте MySQL базу данных
   - Адаптируйте `database.sql` для MySQL
   - Обновите `config.php` для MySQL

3. **Redis:**
   - Используйте внешний Redis хостинг
   - Или адаптируйте код для работы без Redis

### Hostinger

1. **Регистрация:**
   - Зарегистрируйтесь на hostiman.ru
   - Подтвердите личность паспортом
   - Активируйте хостинг

2. **Настройка:**
   - Создайте базу данных PostgreSQL
   - Настройте домен
   - Загрузите файлы

### Render.com

1. **Создание проекта:**
   - Зарегистрируйтесь на render.com
   - Создайте новый Web Service
   - Подключите GitHub репозиторий

2. **Настройка переменных окружения:**
```bash
DB_HOST=your_postgres_host
DB_NAME=your_database
DB_USER=your_username
DB_PASS=your_password
REDIS_URL=your_redis_url
```

3. **Деплой:**
   - Render автоматически развернет приложение
   - Настройте PostgreSQL и Redis сервисы

## Оптимизация для бесплатных хостингов

### Ограничения ресурсов:
- **CPU:** Ограниченное время выполнения
- **RAM:** 128-512 MB
- **Диск:** 1-10 GB
- **Трафик:** 1-10 GB/месяц

### Рекомендации по оптимизации:

1. **Уменьшите количество итераций:**
```php
define('DEFAULT_ITERATIONS', 100); // Вместо 1000
```

2. **Оптимизируйте запросы:**
```php
// Добавьте LIMIT в запросы
SELECT * FROM orders ORDER BY purchase_time DESC LIMIT 100;
```

3. **Кэширование:**
```php
// Используйте файловое кэширование
$cacheFile = 'cache/stats.json';
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 60) {
    return json_decode(file_get_contents($cacheFile), true);
}
```

4. **Сжатие ответов:**
```php
// Включите сжатие в .htaccess
AddOutputFilterByType DEFLATE application/json
```

## Мониторинг и поддержка

### Логирование:
```php
// Добавьте в config.php
ini_set('log_errors', 1);
ini_set('error_log', 'logs/error.log');
```

### Мониторинг производительности:
- Регулярно запускайте `test_performance.php`
- Следите за временем выполнения запросов
- Мониторьте использование памяти

### Резервное копирование:
```bash
# Экспорт базы данных
pg_dump -h host -U user -d database > backup.sql

# Импорт
psql -h host -U user -d database < backup.sql
```

## Устранение неполадок

### Частые проблемы:

1. **Ошибка подключения к БД:**
   - Проверьте настройки в `config.php`
   - Убедитесь, что БД создана
   - Проверьте права доступа пользователя

2. **Медленная работа:**
   - Уменьшите количество итераций
   - Оптимизируйте SQL запросы
   - Используйте кэширование

3. **Ошибки Redis:**
   - Проверьте подключение к Redis
   - Используйте альтернативную блокировку
   - Проверьте настройки Redis

4. **Ограничения хостинга:**
   - Следите за лимитами ресурсов
   - Оптимизируйте код
   - Используйте более мощный хостинг при необходимости

## Ссылки и ресурсы

- **Документация PHP:** https://www.php.net/docs.php
- **PostgreSQL документация:** https://www.postgresql.org/docs/
- **Redis документация:** https://redis.io/documentation
- **cURL документация:** https://curl.se/libcurl/

## Поддержка

При возникновении проблем:
1. Проверьте логи ошибок
2. Запустите `setup.php` для диагностики
3. Обратитесь в поддержку хостинга
4. Создайте Issue в репозитории проекта
