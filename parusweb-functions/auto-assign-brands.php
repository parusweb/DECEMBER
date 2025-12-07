<?php
/**
 * ============================================================================
 * СКРИПТ: АВТОМАТИЧЕСКОЕ ОПРЕДЕЛЕНИЕ БРЕНДОВ
 * ============================================================================
 * 
 * Определяет бренд по названию товара и:
 * 1. Связывает товар с таксономией "Бренды" (product_brand или brands)
 * 2. Устанавливает атрибут pa_proizvoditel
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * 1. Загрузите в /wp-content/plugins/parusweb-functions/
 * 2. Откройте: https://krasivydom-spb.ru/wp-content/plugins/parusweb-functions/auto-assign-brands.php
 * 3. УДАЛИТЕ скрипт после использования!
 */

// Загрузка WordPress
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    die('WordPress not found');
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== АВТОМАТИЧЕСКОЕ ОПРЕДЕЛЕНИЕ БРЕНДОВ ===\n";
echo "Время: " . date('Y-m-d H:i:s') . "\n\n";

// ============================================================================
// КОНФИГУРАЦИЯ
// ============================================================================

// Определите названия брендов и их вариации
$brands_config = [
    'СтройКрепп' => [
        'patterns' => ['/СтройКрепп/ui', '/StrojKrepp/ui', '/Стройкрепп/ui'],
        'canonical_name' => 'СтройКрепп',
        'slug' => 'stroikrepp'
    ],
    'Гвозdeck' => [
        'patterns' => ['/Гвозdeck/ui', '/Gvozdeck/ui', '/гвоздeck/ui'],
        'canonical_name' => 'Гвозdeck',
        'slug' => 'gvozdeck'
    ],
    'ЛесФикс' => [
        'patterns' => ['/ЛесФикс/ui', '/LesFix/ui', '/Лесфикс/ui'],
        'canonical_name' => 'ЛесФикс',
        'slug' => 'lesfix'
    ],
    'Директ' => [
        'patterns' => ['/Директ/ui', '/Direct/ui'],
        'canonical_name' => 'Директ',
        'slug' => 'direct'
    ],
    // Добавьте другие бренды здесь
];

// Проверка названия таксономии брендов
$brand_taxonomies = ['product_brand', 'brands', 'pa_brands'];
$brand_taxonomy = null;

foreach ($brand_taxonomies as $tax) {
    if (taxonomy_exists($tax)) {
        $brand_taxonomy = $tax;
        echo "✓ Найдена таксономия брендов: $tax\n\n";
        break;
    }
}

if (!$brand_taxonomy) {
    die("❌ ОШИБКА: Таксономия брендов не найдена!\nПроверьте: product_brand, brands, pa_brands\n");
}

// Проверка атрибута pa_proizvoditel
if (!taxonomy_exists('pa_proizvoditel')) {
    die("❌ ОШИБКА: Атрибут pa_proizvoditel не существует!\nСоздайте атрибут 'proizvoditel' в WooCommerce → Атрибуты\n");
}

echo "✓ Атрибут pa_proizvoditel найден\n\n";

// ============================================================================
// ПОЛУЧЕНИЕ ТОВАРОВ БЕЗ БРЕНДА
// ============================================================================

$args = [
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'publish',
];

$products = get_posts($args);

echo "Найдено товаров: " . count($products) . "\n";
echo "Начало обработки...\n\n";
echo str_repeat('=', 80) . "\n\n";

// ============================================================================
// ОБРАБОТКА ТОВАРОВ
// ============================================================================

$stats = [
    'total' => 0,
    'processed' => 0,
    'skipped_has_brand' => 0,
    'skipped_no_match' => 0,
    'errors' => 0
];

foreach ($products as $product_post) {
    $stats['total']++;
    $product_id = $product_post->ID;
    $product_name = $product_post->post_title;
    
    // Проверка: есть ли уже бренд в таксономии?
    $existing_brands = wp_get_post_terms($product_id, $brand_taxonomy, ['fields' => 'ids']);
    
    // Проверка: есть ли уже атрибут производитель?
    $existing_producer = wp_get_post_terms($product_id, 'pa_proizvoditel', ['fields' => 'ids']);
    
    // Пропускаем если УЖЕ есть бренд И атрибут
    if (!empty($existing_brands) && !empty($existing_producer)) {
        $stats['skipped_has_brand']++;
        continue;
    }
    
    // Поиск бренда в названии
    $found_brand = null;
    $matched_config = null;
    
    foreach ($brands_config as $brand_key => $config) {
        foreach ($config['patterns'] as $pattern) {
            if (preg_match($pattern, $product_name)) {
                $found_brand = $brand_key;
                $matched_config = $config;
                break 2;
            }
        }
    }
    
    // Если бренд не найден
    if (!$found_brand) {
        $stats['skipped_no_match']++;
        continue;
    }
    
    echo "Товар #$product_id: $product_name\n";
    echo "→ Найден бренд: $found_brand\n";
    
    // ========================================================================
    // 1. ДОБАВЛЕНИЕ В ТАКСОНОМИЮ БРЕНДОВ
    // ========================================================================
    
    $brand_term = null;
    
    // Проверяем существует ли термин
    $existing_term = get_term_by('slug', $matched_config['slug'], $brand_taxonomy);
    
    if ($existing_term) {
        $brand_term = $existing_term;
        echo "  ✓ Термин существует: {$brand_term->name} (ID: {$brand_term->term_id})\n";
    } else {
        // Создаем новый термин
        $new_term = wp_insert_term(
            $matched_config['canonical_name'],
            $brand_taxonomy,
            ['slug' => $matched_config['slug']]
        );
        
        if (is_wp_error($new_term)) {
            echo "  ❌ Ошибка создания термина: " . $new_term->get_error_message() . "\n";
            $stats['errors']++;
            continue;
        }
        
        $brand_term = get_term($new_term['term_id'], $brand_taxonomy);
        echo "  ✓ Создан термин: {$brand_term->name} (ID: {$brand_term->term_id})\n";
    }
    
    // Связываем товар с брендом (только если еще не связан)
    if (empty($existing_brands)) {
        $result = wp_set_object_terms($product_id, $brand_term->term_id, $brand_taxonomy, false);
        
        if (is_wp_error($result)) {
            echo "  ❌ Ошибка привязки к таксономии: " . $result->get_error_message() . "\n";
            $stats['errors']++;
            continue;
        }
        
        echo "  ✓ Товар связан с таксономией '$brand_taxonomy'\n";
    } else {
        echo "  ⊘ Таксономия уже установлена (пропуск)\n";
    }
    
    // ========================================================================
    // 2. УСТАНОВКА АТРИБУТА pa_proizvoditel
    // ========================================================================
    
    if (empty($existing_producer)) {
        // Проверяем существует ли значение атрибута
        $producer_term = get_term_by('slug', $matched_config['slug'], 'pa_proizvoditel');
        
        if (!$producer_term) {
            // Создаем значение атрибута
            $new_producer_term = wp_insert_term(
                $matched_config['canonical_name'],
                'pa_proizvoditel',
                ['slug' => $matched_config['slug']]
            );
            
            if (is_wp_error($new_producer_term)) {
                echo "  ❌ Ошибка создания значения атрибута: " . $new_producer_term->get_error_message() . "\n";
                $stats['errors']++;
                continue;
            }
            
            $producer_term = get_term($new_producer_term['term_id'], 'pa_proizvoditel');
            echo "  ✓ Создано значение атрибута: {$producer_term->name}\n";
        } else {
            echo "  ✓ Значение атрибута существует: {$producer_term->name}\n";
        }
        
        // Связываем товар с атрибутом
        $result = wp_set_object_terms($product_id, $producer_term->term_id, 'pa_proizvoditel', false);
        
        if (is_wp_error($result)) {
            echo "  ❌ Ошибка установки атрибута: " . $result->get_error_message() . "\n";
            $stats['errors']++;
            continue;
        }
        
        // ВАЖНО: Обновляем мета-данные товара для отображения атрибута
        $product_attributes = get_post_meta($product_id, '_product_attributes', true);
        
        if (!is_array($product_attributes)) {
            $product_attributes = [];
        }
        
        $product_attributes['pa_proizvoditel'] = [
            'name' => 'pa_proizvoditel',
            'value' => '',
            'position' => count($product_attributes),
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 1
        ];
        
        update_post_meta($product_id, '_product_attributes', $product_attributes);
        
        echo "  ✓ Атрибут pa_proizvoditel установлен\n";
    } else {
        echo "  ⊘ Атрибут уже установлен (пропуск)\n";
    }
    
    $stats['processed']++;
    echo "  ✅ ГОТОВО\n\n";
}

// ============================================================================
// СТАТИСТИКА
// ============================================================================

echo str_repeat('=', 80) . "\n\n";
echo "=== СТАТИСТИКА ===\n\n";
echo "Всего товаров: {$stats['total']}\n";
echo "Обработано: {$stats['processed']}\n";
echo "Пропущено (уже есть бренд): {$stats['skipped_has_brand']}\n";
echo "Пропущено (бренд не найден): {$stats['skipped_no_match']}\n";
echo "Ошибок: {$stats['errors']}\n\n";

if ($stats['skipped_no_match'] > 0) {
    echo "⚠️ У некоторых товаров бренд не определен!\n";
    echo "Проверьте названия товаров или добавьте бренды в \$brands_config\n\n";
}

echo "=== ГОТОВО ===\n\n";
echo "⚠️ УДАЛИТЕ ЭТОТ СКРИПТ!\n";
