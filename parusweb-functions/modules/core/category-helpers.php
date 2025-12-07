<?php
/**
 * ============================================================================
 * МОДУЛЬ: ПРОВЕРКА КАТЕГОРИЙ (КРИТИЧЕСКИЙ)
 * ============================================================================
 * 
 * @package ParusWeb_Functions
 * @subpackage Core
 * @version 2.1.0 - ДОБАВЛЕНЫ КАТЕГОРИИ КРЕПЕЖА
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// ПРОВЕРКА КАТЕГОРИЙ ТОВАРОВ
// ============================================================================

/**
 * Проверка принадлежности к целевым категориям
 * ИСПРАВЛЕНО: Добавлены категории крепежа для обработки покраски!
 */
function is_in_target_categories($product_id) {
    $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
    if (is_wp_error($product_categories) || empty($product_categories)) return false;
    

    $target_categories = array_merge(
        range(87, 93),       // Пиломатериалы
        [190, 191, 127, 94], // Листовые
        range(265, 271)     // Столярные изделия
        //[77, 299, 300, 80, 123] // КРЕПЕЖ - добавлено для обработки покраски!
    );
    
    foreach ($product_categories as $cat_id) {
        if (in_array($cat_id, $target_categories)) return true;
        foreach ($target_categories as $target_cat_id) {
            if (cat_is_ancestor_of($target_cat_id, $cat_id)) return true;
        }
    }
    return false;
}

/**
 * Проверка категорий для покраски
 */
function is_in_painting_categories($product_id) {
    // Используем is_in_target_categories как базу
    return is_in_target_categories($product_id);
}

/**
 * Проверка категорий столярки (с множителем)
 */
function is_in_multiplier_categories($product_id) {
    $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
    if (is_wp_error($product_categories) || empty($product_categories)) return false;
    
    $target_categories = [265, 266, 267, 268, 270, 271, 273];
    
    foreach ($product_categories as $cat_id) {
        if (in_array($cat_id, $target_categories)) return true;
        foreach ($target_categories as $target_cat_id) {
            if (cat_is_ancestor_of($target_cat_id, $cat_id)) return true;
        }
    }
    return false;
}

/**
 * Проверка категорий за квадратные метры
 */
function is_square_meter_category($product_id) {
    $square_meter_cat_ids = array(142, 137, 135, 136, 266);
    return has_term($square_meter_cat_ids, 'product_cat', $product_id);
}

/**
 * Проверка категорий за погонные метры
 */
function is_running_meter_category($product_id) {
    $running_meter_cats = [266]; // Фальшбалки
    return has_term($running_meter_cats, 'product_cat', $product_id);
}

/**
 * Проверка категории реечных перегородок
 */
function is_partition_slat_category($product_id) {
    return has_term(271, 'product_cat', $product_id);
}

/**
 * Проверка категорий ЛКМ (за литр)
 */
function is_in_liter_categories($product_id) {
    $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
    if (is_wp_error($product_categories) || empty($product_categories)) return false;
    
    $target_categories = range(81, 86);
    
    foreach ($product_categories as $cat_id) {
        if (in_array($cat_id, $target_categories)) return true;
        foreach ($target_categories as $target_cat_id) {
            if (cat_is_ancestor_of($target_cat_id, $cat_id)) return true;
        }
    }
    return false;
}

/**
 * Универсальная функция проверки принадлежности к категории (с учетом иерархии)
 */
function product_in_category($product_id, $category_id) {
    $terms = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
    if (is_wp_error($terms) || empty($terms)) return false;
    
    if (in_array($category_id, $terms)) return true;
    
    foreach ($terms as $term_id) {
        if (term_is_ancestor_of($category_id, $term_id, 'product_cat')) {
            return true;
        }
    }
    
    return false;
}

// ============================================================================
// ОПРЕДЕЛЕНИЕ ТИПОВ ТОВАРОВ
// ============================================================================

/**
 * Определить тип товара по категориям
 */
function parusweb_get_product_type($product_id) {
    if (is_cubic_meter_category($product_id)) return 'cubic_meter_pack';
    if (is_partition_slat_category($product_id)) return 'partition_slat';
    if (is_running_meter_category($product_id)) return 'running_meter';
    if (is_square_meter_category($product_id)) return 'square_meter';
    if (is_in_multiplier_categories($product_id)) return 'multiplier';
    if (is_in_liter_categories($product_id)) return 'liter';
    if (is_in_target_categories($product_id)) return 'target';
    return false;
}

/**
 * Получить название единицы измерения товара
 */
function parusweb_get_product_unit($product_id) {
    $type = parusweb_get_product_type($product_id);
    
    $units = [
        'partition_slat' => 'шт',
        'running_meter' => 'шт',
        'square_meter' => 'м²',
        'multiplier' => 'шт',
        'liter' => 'л',
        'target' => 'упак'
    ];
    
    return $units[$type] ?? 'шт';
}

/**
 * Проверка - является ли товар листовым материалом
 */
function is_leaf_category($product_id) {
    $leaf_parent_id = 190;
    $leaf_children = [191, 127, 94];
    $leaf_ids = array_merge([$leaf_parent_id], $leaf_children);
    
    return has_term($leaf_ids, 'product_cat', $product_id);
}

/**
 * Получить тип единицы (лист/упаковка) для отображения
 */
function get_unit_type_label($product_id) {
    return is_leaf_category($product_id) ? 'лист' : 'упаковка';
}

/**
 * Получить формы склонения единицы
 */
function get_unit_forms($product_id) {
    return is_leaf_category($product_id) 
        ? ['лист', 'листа', 'листов'] 
        : ['упаковка', 'упаковки', 'упаковок'];
}

// ============================================================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

/**
 * Склонение русских существительных
 */
function get_russian_plural_for_cart($n, $forms) {
    $n = abs($n);
    $n %= 100;
    if ($n > 10 && $n < 20) return $forms[2];
    $n %= 10;
    if ($n === 1) return $forms[0];
    if ($n >= 2 && $n <= 4) return $forms[1];
    return $forms[2];
}


/**
 * Добавить стандартную пагинацию WooCommerce
 */
function parusweb_add_woocommerce_pagination() {
    if (is_shop() || is_product_category() || is_product_tag()) {
        woocommerce_pagination();
    }
}
add_action('woocommerce_after_shop_loop', 'parusweb_add_woocommerce_pagination', 10);


// Товаров на странице
function parusweb_products_per_page($per_page) {
    return 12; 
}
add_filter('loop_shop_per_page', 'parusweb_products_per_page', 20);