# ⚡ Быстрый старт

## 🚀 Развертывание за 5 минут

### 1. Выберите хостинг
- **InfinityFree** (рекомендуется) - https://infinityfree.com/
- **Hostinger** - https://hostiman.ru/
- **Render.com** - https://render.com/

### 2. Загрузите файлы
1. Скачайте все файлы проекта
2. Загрузите их на хостинг через FTP или панель управления

### 3. Настройте базу данных
1. Создайте PostgreSQL базу данных
2. Выполните SQL из `database.sql`
3. Обновите `config.php` с вашими данными

### 4. Настройте Redis
**Вариант A:** Внешний Redis
- Зарегистрируйтесь на https://upstash.com/
- Получите URL подключения
- Обновите настройки в `config.php`

**Вариант B:** Без Redis
- Используйте файловую блокировку (встроено в код)

### 5. Запустите настройку
Откройте в браузере: `http://your-domain.com/setup.php`

### 6. Готово!
Откройте: `http://your-domain.com/index.html`

## 📋 Минимальные требования

- PHP 7.4+
- PostgreSQL 12+ (или MySQL с адаптацией)
- Redis (опционально)
- Веб-сервер (Apache/Nginx)

## 🔧 Быстрая настройка config.php

```php
// Замените на ваши данные
define('DB_HOST', 'your-db-host.com');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// Для внешнего Redis
define('REDIS_HOST', 'your-redis-host.com');
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', 'your_redis_password');
```

## 🧪 Тестирование

1. **Проверка настройки:** `setup.php`
2. **Тест производительности:** `test_performance.php`
3. **Основной интерфейс:** `index.html`

## 📞 Поддержка

- Подробная документация: `README.md`
- Инструкции по развертыванию: `DEPLOYMENT.md`
- При проблемах запустите `setup.php` для диагностики

## ⚡ Оптимизация для бесплатных хостингов

```php
// В config.php уменьшите количество итераций
define('DEFAULT_ITERATIONS', 100); // Вместо 1000
```

## 🎯 Что получится

✅ Система управления заказами с PostgreSQL  
✅ Защита от повторного запуска через Redis  
✅ Массовая генерация заказов  
✅ Статистика в реальном времени  
✅ Современный веб-интерфейс  
✅ Оптимизированная производительность
