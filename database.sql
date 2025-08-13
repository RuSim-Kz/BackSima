-- Создание базы данных и таблиц для системы

-- Таблица категорий
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица продуктов
CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category_id INTEGER REFERENCES categories(id),
    stock_quantity INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица заказов
CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    product_id INTEGER REFERENCES products(id),
    quantity INTEGER NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    purchase_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    customer_email VARCHAR(255),
    status VARCHAR(50) DEFAULT 'completed'
);

-- Таблица статистики
CREATE TABLE IF NOT EXISTS statistics (
    id SERIAL PRIMARY KEY,
    category_id INTEGER REFERENCES categories(id),
    products_sold INTEGER DEFAULT 0,
    total_revenue DECIMAL(10,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Вставка начальных данных
INSERT INTO categories (name, description) VALUES 
('Электроника', 'Электронные устройства и гаджеты'),
('Одежда', 'Мужская и женская одежда'),
('Книги', 'Художественная и техническая литература'),
('Спорт', 'Спортивные товары и инвентарь'),
('Дом и сад', 'Товары для дома и сада');

INSERT INTO products (name, description, price, category_id, stock_quantity) VALUES 
('iPhone 15', 'Смартфон Apple iPhone 15', 89999.00, 1, 50),
('Samsung Galaxy S24', 'Смартфон Samsung Galaxy S24', 79999.00, 1, 45),
('Футболка мужская', 'Хлопковая футболка', 1500.00, 2, 100),
('Джинсы женские', 'Стильные джинсы', 3500.00, 2, 80),
('Война и мир', 'Роман Льва Толстого', 800.00, 3, 200),
('Python для начинающих', 'Учебник по Python', 1200.00, 3, 150),
('Футбольный мяч', 'Профессиональный футбольный мяч', 2500.00, 4, 60),
('Гантели 5кг', 'Пара гантелей по 5кг', 1800.00, 4, 40),
('Лампа настольная', 'LED лампа для рабочего стола', 1200.00, 5, 70),
('Горшок для цветов', 'Керамический горшок 20см', 500.00, 5, 90);

-- Создание индексов для оптимизации
CREATE INDEX IF NOT EXISTS idx_orders_purchase_time ON orders(purchase_time);
CREATE INDEX IF NOT EXISTS idx_orders_product_id ON orders(product_id);
CREATE INDEX IF NOT EXISTS idx_products_category_id ON products(category_id);
