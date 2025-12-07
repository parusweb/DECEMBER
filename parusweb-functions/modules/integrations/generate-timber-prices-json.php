<?php
/**
 * ============================================================================
 * ГЕНЕРАТОР JSON: Цены за м² для пиломатериалов
 * ============================================================================
 * 
 * Создает файл timber-prices.json с ценами за м² для всех пиломатериалов
 * Используется фильтром Bricks для корректного отображения диапазона цен
 * 
 * Формат JSON:
 * {
 *   "12345": {"price_m3": 180500, "price_m2": 3249, "thickness": 18},
 *   "12346": {"price_m3": 385000, "price_m2": 7700, "thickness": 20}
 * }
 */

if (!defined('ABSPATH')) {
    // Если запускается напрямую из CLI
    require_once('../../../wp-load.php');
}

function generate_timber_prices_json() {
    
    // Категории пиломатериалов
    $timber_categories = array_merge([87, 310], range(88, 93));
    
    $args = [
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'tax_query' => [
            [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $timber_categories,
            ]
        ]
    ];
    
    $products = get_posts($args);
    $prices = [];
    $processed = 0;
    $skipped = 0;
    
    foreach ($products as $post) {
        $product_id = $post->ID;
        $product = wc_get_product($product_id);
        
        if (!$product) {
            $skipped++;
            continue;
        }
        
        // Базовая цена (₽/м³)
        $price_per_m3 = floatval($product->get_regular_price());
        
        if ($price_per_m3 <= 0) {
            $skipped++;
            continue;
        }
        
        // Получаем параметры товара
        if (!function_exists('get_cubic_product_params')) {
            error_log("Function get_cubic_product_params not found!");
            return false;
        }
        
        $params = get_cubic_product_params($product_id);
        
        if (!$params) {
            // Если не удалось извлечь параметры, используем минимальную толщину
            $thickness = 20; // мм по умолчанию
            error_log("Product $product_id: using default thickness 20mm");
        } else {
            $thickness = $params['thickness'];
        }
        
        // Пересчитываем в ₽/м²
        $thickness_m = $thickness / 1000;
        $price_per_m2 = $price_per_m3 * $thickness_m;
        
        $prices[$product_id] = [
            'price_m3' => $price_per_m3,
            'price_m2' => round($price_per_m2, 2),
            'thickness' => $thickness,
            'title' => $product->get_name()
        ];
        
        $processed++;
    }
    
    // Путь к JSON файлу
    $upload_dir = wp_upload_dir();
    $json_file = $upload_dir['basedir'] . '/timber-prices.json';
    
    // Сохраняем JSON
    $json_data = [
        'generated_at' => current_time('mysql'),
        'total_products' => count($products),
        'processed' => $processed,
        'skipped' => $skipped,
        'prices' => $prices
    ];
    
    $result = file_put_contents($json_file, json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    if ($result === false) {
        error_log("Failed to write JSON file: $json_file");
        return false;
    }
    
    error_log("Generated timber prices JSON: $json_file");
    error_log("Processed: $processed, Skipped: $skipped");
    
    return [
        'file' => $json_file,
        'url' => $upload_dir['baseurl'] . '/timber-prices.json',
        'processed' => $processed,
        'skipped' => $skipped,
        'total' => count($products)
    ];
}

/**
 * ============================================================================
 * СПОСОБЫ ЗАПУСКА
 * ============================================================================
 */

// СПОСОБ 1: Через админку WordPress
// Добавьте в functions.php ВРЕМЕННО:
/*
add_action('admin_init', function() {
    if (current_user_can('administrator') && isset($_GET['generate_timber_json'])) {
        $result = generate_timber_prices_json();
        if ($result) {
            wp_die(sprintf(
                '<h2>JSON Generated Successfully!</h2>
                <p>File: %s</p>
                <p>URL: <a href="%s" target="_blank">%s</a></p>
                <p>Processed: %d, Skipped: %d, Total: %d</p>',
                $result['file'],
                $result['url'],
                $result['url'],
                $result['processed'],
                $result['skipped'],
                $result['total']
            ));
        } else {
            wp_die('ERROR: Failed to generate JSON');
        }
    }
});
*/
// Затем перейти: /wp-admin/?generate_timber_json=1

// СПОСОБ 2: WP-CLI
// wp eval-file generate-timber-prices-json.php

// СПОСОБ 3: Cron (автоматическая регенерация)
// add_action('parusweb_regenerate_timber_prices', 'generate_timber_prices_json');
// wp_schedule_event(time(), 'daily', 'parusweb_regenerate_timber_prices');

/**
 * ============================================================================
 * АВТОМАТИЧЕСКАЯ РЕГЕНЕРАЦИЯ при сохранении товара
 * ============================================================================
 */

// Регенерация JSON при обновлении товара из категории пиломатериалов
add_action('woocommerce_update_product', 'parusweb_maybe_regenerate_timber_json', 10, 1);
add_action('woocommerce_new_product', 'parusweb_maybe_regenerate_timber_json', 10, 1);

function parusweb_maybe_regenerate_timber_json($product_id) {
    
    $timber_categories = array_merge([87, 310], range(88, 93));
    
    if (!has_term($timber_categories, 'product_cat', $product_id)) {
        return;
    }
    
    // Используем transient чтобы не генерировать JSON многократно
    $transient_key = 'timber_json_regenerating';
    
    if (get_transient($transient_key)) {
        return; // Уже регенерируется
    }
    
    // Ставим лок на 5 минут
    set_transient($transient_key, true, 300);
    
    // Регенерируем JSON
    generate_timber_prices_json();
    
    error_log("Timber JSON regenerated after product $product_id update");
}

/**
 * ============================================================================
 * ПРИМЕР ВЫВОДА JSON
 * ============================================================================
 * 
 * {
 *   "generated_at": "2025-01-15 10:30:00",
 *   "total_products": 156,
 *   "processed": 150,
 *   "skipped": 6,
 *   "prices": {
 *     "12345": {
 *       "price_m3": 180500,
 *       "price_m2": 3249,
 *       "thickness": 18,
 *       "title": "Имитация бруса 145×18×3000, 8 шт"
 *     },
 *     "12346": {
 *       "price_m3": 385000,
 *       "price_m2": 7700,
 *       "thickness": 20,
 *       "title": "Планкен 20×120×500-3000мм"
 *     }
 *   }
 * }
 */
