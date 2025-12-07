<?php
/**
 * ============================================================================
 * ИНТЕГРАЦИЯ С JETENGINE ФИЛЬТРОМ
 * ============================================================================
 */

if (!defined('ABSPATH')) exit;

/**
 * ============================================================================
 * АВТОЗАПОЛНЕНИЕ META ПОЛЯ _display_price_m2
 * ============================================================================
 */

/**
 * Синхронизация цены за м² при сохранении товара пиломатериалов
 */
add_action('woocommerce_update_product', 'parusweb_sync_jetengine_price_m2', 10, 1);
add_action('woocommerce_new_product', 'parusweb_sync_jetengine_price_m2', 10, 1);

function parusweb_sync_jetengine_price_m2($product_id) {
    
    // Категории пиломатериалов и листовых
    $timber_categories = array_merge(
    [87, 310], 
    range(88, 93),
    [190, 191, 127, 94]  //листовые
);
    if (!has_term($timber_categories, 'product_cat', $product_id)) {
        return;
    }
    
    $product = wc_get_product($product_id);
    if (!$product) return;
    
    // Базовая цена (₽/м³)
    $price_per_m3 = floatval($product->get_regular_price());
    
    if ($price_per_m3 <= 0) return;
    
    // Получаем параметры
    if (!function_exists('get_cubic_product_params')) {
        error_log("Function get_cubic_product_params not found for product $product_id");
        return;
    }
    
    $params = get_cubic_product_params($product_id);
    
    if (!$params) {
        // Используем толщину по умолчанию 20мм
        $thickness = 20;
        error_log("Product $product_id: using default thickness 20mm");
    } else {
        $thickness = $params['thickness'];
    }
    
    // Пересчитываем в ₽/м²
    $thickness_m = $thickness / 1000;
    $price_per_m2 = $price_per_m3 * $thickness_m;
    
    // Сохраняем в meta поле для JetEngine
    update_post_meta($product_id, '_display_price_m2', round($price_per_m2, 2));
    
    error_log(sprintf(
        "Product %d: synced _display_price_m2 = %.2f (thickness=%dmm, price_m3=%.2f)",
        $product_id,
        $price_per_m2,
        $thickness,
        $price_per_m3
    ));
}

/**
 * ============================================================================
 * МАССОВАЯ СИНХРОНИЗАЦИЯ СУЩЕСТВУЮЩИХ ТОВАРОВ
 * ============================================================================
 */

function parusweb_bulk_sync_jetengine_prices() {
    
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
    $synced = 0;
    $errors = 0;
    
    foreach ($products as $post) {
        $result = parusweb_sync_jetengine_price_m2($post->ID);
        
        if ($result !== false) {
            $synced++;
        } else {
            $errors++;
        }
    }
    
    return [
        'synced' => $synced,
        'errors' => $errors,
        'total' => count($products)
    ];
}