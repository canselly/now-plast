<?php
/**
 * Veritabanı şeması. MySQL ve SQLite için aynı tanımlar kullanılır;
 * sürücüye özel kısımlar {PK} ve {ENGINE} yer tutucularıyla değiştirilir.
 */
function schema_statements(string $driver): array
{
    $sql = [
        "CREATE TABLE IF NOT EXISTS settings (
            skey VARCHAR(100) NOT NULL PRIMARY KEY,
            svalue TEXT NULL
        ){ENGINE}",

        "CREATE TABLE IF NOT EXISTS users (
            id {PK},
            username VARCHAR(60) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(120) NULL,
            last_login DATETIME NULL,
            created_at DATETIME NULL
        ){ENGINE}",

        "CREATE TABLE IF NOT EXISTS categories (
            id {PK},
            parent_id INT NULL,
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL UNIQUE,
            summary VARCHAR(500) NULL,
            description TEXT NULL,
            image VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            meta_title VARCHAR(190) NULL,
            meta_description VARCHAR(300) NULL
        ){ENGINE}",

        "CREATE TABLE IF NOT EXISTS sectors (
            id {PK},
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL UNIQUE,
            icon VARCHAR(40) NULL,
            summary VARCHAR(500) NULL,
            description TEXT NULL,
            image VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            meta_title VARCHAR(190) NULL,
            meta_description VARCHAR(300) NULL
        ){ENGINE}",

        "CREATE TABLE IF NOT EXISTS products (
            id {PK},
            category_id INT NULL,
            code VARCHAR(60) NULL,
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL UNIQUE,
            summary VARCHAR(500) NULL,
            description TEXT NULL,
            volume_ml DECIMAL(12,2) NULL,
            height_mm DECIMAL(10,2) NULL,
            top_diameter_mm DECIMAL(10,2) NULL,
            bottom_diameter_mm DECIMAL(10,2) NULL,
            width_mm DECIMAL(10,2) NULL,
            length_mm DECIMAL(10,2) NULL,
            weight_g DECIMAL(10,2) NULL,
            material VARCHAR(60) NULL,
            decoration VARCHAR(60) NULL,
            lid VARCHAR(120) NULL,
            packaging VARCHAR(120) NULL,
            extra_specs TEXT NULL,
            image VARCHAR(255) NULL,
            datasheet VARCHAR(255) NULL,
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            is_new TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            meta_title VARCHAR(190) NULL,
            meta_description VARCHAR(300) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ){ENGINE}",

        "CREATE TABLE IF NOT EXISTS product_images (
            id {PK},
            product_id INT NOT NULL,
            path VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0
        ){ENGINE}",

        "CREATE TABLE IF NOT EXISTS product_sectors (
            product_id INT NOT NULL,
            sector_id INT NOT NULL,
            PRIMARY KEY (product_id, sector_id)
        ){ENGINE}",

        "CREATE TABLE IF NOT EXISTS pages (
            id {PK},
            title VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL UNIQUE,
            summary VARCHAR(500) NULL,
            content TEXT NULL,
            image VARCHAR(255) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            meta_title VARCHAR(190) NULL,
            meta_description VARCHAR(300) NULL,
            updated_at DATETIME NULL
        ){ENGINE}",

        "CREATE TABLE IF NOT EXISTS menu_items (
            id {PK},
            location VARCHAR(20) NOT NULL DEFAULT 'header',
            parent_id INT NULL,
            title VARCHAR(120) NOT NULL,
            link_type VARCHAR(20) NOT NULL DEFAULT 'url',
            link_value VARCHAR(255) NULL,
            auto_children VARCHAR(20) NULL,
            new_tab TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0
        ){ENGINE}",

        "CREATE TABLE IF NOT EXISTS sliders (
            id {PK},
            title VARCHAR(190) NULL,
            subtitle VARCHAR(500) NULL,
            eyebrow VARCHAR(120) NULL,
            button_text VARCHAR(60) NULL,
            button_link VARCHAR(255) NULL,
            image VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ){ENGINE}",

        "CREATE TABLE IF NOT EXISTS news (
            id {PK},
            title VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL UNIQUE,
            summary VARCHAR(500) NULL,
            content TEXT NULL,
            image VARCHAR(255) NULL,
            published_at DATE NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            meta_title VARCHAR(190) NULL,
            meta_description VARCHAR(300) NULL
        ){ENGINE}",

        "CREATE TABLE IF NOT EXISTS certificates (
            id {PK},
            title VARCHAR(190) NOT NULL,
            image VARCHAR(255) NULL,
            file VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ){ENGINE}",

        "CREATE TABLE IF NOT EXISTS clients (
            id {PK},
            name VARCHAR(190) NOT NULL,
            logo VARCHAR(255) NULL,
            url VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ){ENGINE}",

        "CREATE TABLE IF NOT EXISTS messages (
            id {PK},
            type VARCHAR(20) NOT NULL DEFAULT 'contact',
            name VARCHAR(120) NOT NULL,
            company VARCHAR(190) NULL,
            email VARCHAR(190) NULL,
            phone VARCHAR(40) NULL,
            subject VARCHAR(190) NULL,
            quantity VARCHAR(60) NULL,
            message TEXT NULL,
            product_id INT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            ip VARCHAR(45) NULL,
            created_at DATETIME NULL
        ){ENGINE}",
    ];

    $pk = $driver === 'mysql' ? 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $engine = $driver === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    return array_map(fn($s) => str_replace(['{PK}', '{ENGINE}'], [$pk, $engine], $s), $sql);
}

function schema_indexes(): array
{
    return [
        'CREATE INDEX idx_categories_parent ON categories (parent_id)',
        'CREATE INDEX idx_products_category ON products (category_id)',
        'CREATE INDEX idx_products_active ON products (is_active, sort_order)',
        'CREATE INDEX idx_product_images_product ON product_images (product_id)',
        'CREATE INDEX idx_product_sectors_sector ON product_sectors (sector_id)',
        'CREATE INDEX idx_menu_items_loc ON menu_items (location, parent_id)',
        'CREATE INDEX idx_messages_created ON messages (created_at)',
    ];
}
