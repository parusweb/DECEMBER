<?php
/**
 * Fasteners Addon Module - Admin Part
 * 
 * Административная настройка автоматического добавления крепежа к пиломатериалам:
 * - ACF поля для категорий пиломатериалов (БЕЗ листовых!)
 * - Выбор товара крепежа для автодобавления
 * - Автоматический расчёт по площади из калькулятора
 * - Отображение в корзине и заказе
 * 
 * @package ParusWeb_Functions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 2: ПРОВЕРКА КАТЕГОРИЙ ДЛЯ КРЕПЕЖА
// ============================================================================

/**
 * Получение категорий пиломатериалов (БЕЗ листовых)
 */
function parusweb_get_timber_categories() {
    // Только пиломатериалы (87-93), БЕЗ листовых (190, 191, 127, 94)
    return range(87, 93);
}

/**
 * Проверка - является ли товар пиломатериалом (НЕ листовым)
 */
function parusweb_is_timber_product($product_id) {
    $timber_categories = parusweb_get_timber_categories();
    
    $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
    if (is_wp_error($product_categories) || empty($product_categories)) {
        return false;
    }
    
    foreach ($product_categories as $cat_id) {
        // Прямое совпадение
        if (in_array($cat_id, $timber_categories)) {
            return true;
        }
        
        // Проверяем является ли родителем
        foreach ($timber_categories as $timber_cat_id) {
            if (cat_is_ancestor_of($timber_cat_id, $cat_id)) {
                return true;
            }
        }
        
        // Проверяем является ли дочерней категорией (обратная проверка)
        foreach ($timber_categories as $timber_cat_id) {
            if (cat_is_ancestor_of($cat_id, $timber_cat_id)) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Получение доступных вариантов крепежа для товара
 * 
 * @param int $product_id ID товара
 * @return array|false Массив вариантов крепежа или false
 */
function parusweb_get_available_fasteners_for_product($product_id) {
    // Проверяем что это пиломатериал (не листовой)
    $is_timber = parusweb_is_timber_product($product_id);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("FASTENER DEBUG: Product {$product_id} is_timber = " . ($is_timber ? 'YES' : 'NO'));
    }
    
    if (!$is_timber) {
        return false;
    }
    
    $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'all']);
    if (is_wp_error($product_categories) || empty($product_categories)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("FASTENER DEBUG: No categories found for product {$product_id}");
        }
        return false;
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $cat_ids = array_map(function($c) { return $c->term_id . ' (' . $c->name . ')'; }, $product_categories);
        error_log("FASTENER DEBUG: Product categories: " . implode(', ', $cat_ids));
    }
    
    // Сортируем категории от более конкретных к общим
    usort($product_categories, function($a, $b) {
        $depth_a = count(get_ancestors($a->term_id, 'product_cat'));
        $depth_b = count(get_ancestors($b->term_id, 'product_cat'));
        return $depth_b - $depth_a;
    });
    
    // Ищем настройки в категориях (приоритет у более конкретных)
    foreach ($product_categories as $category) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("FASTENER DEBUG: Checking category {$category->term_id} ({$category->name})");
        }
        
        // Проверяем разные варианты получения поля
        $fasteners = get_field('available_fasteners', 'product_cat_' . $category->term_id);
        

        if ($fasteners && is_array($fasteners) && count($fasteners) > 0) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("FASTENER DEBUG: Found " . count($fasteners) . " fasteners in category {$category->term_id}");
            }
            
            return [
                'category_id' => $category->term_id,
                'category_name' => $category->name,
                'fasteners' => $fasteners,
            ];
        }
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("FASTENER DEBUG: No fasteners found in any category");
    }
    
    return false;
}

// ============================================================================
// БЛОК 3: АВТОМАТИЧЕСКОЕ ДОБАВЛЕНИЕ КРЕПЕЖА В КОРЗИНУ
// ============================================================================

/**
 * Автоматическое добавление крепежа при добавлении пиломатериала
 */
function parusweb_auto_add_fastener_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    // Проверяем выбрал ли покупатель крепеж
    if (!isset($_POST['selected_fastener']) || empty($_POST['selected_fastener'])) {
        return;
    }
    
    $selected_fastener_id = intval($_POST['selected_fastener']);
    
    // Получаем настройки крепежа для товара
    $fasteners_config = parusweb_get_available_fasteners_for_product($product_id);
    
    if (!$fasteners_config) {
        return;
    }
    
    // Проверяем что выбранный крепеж есть в списке доступных
    $is_valid = false;
    foreach ($fasteners_config['fasteners'] as $fastener) {
        if (intval($fastener['fastener_product']) === $selected_fastener_id) {
            $is_valid = true;
            break;
        }
    }
    
    if (!$is_valid) {
        return;
    }
    
    // Проверяем есть ли данные калькулятора площади и ширины
    $area = 0;
    $board_width = 0;
    
    // Из калькулятора размеров
    if (isset($cart_item_data['_dimension_calc_data'])) {
        $dim_data = json_decode($cart_item_data['_dimension_calc_data'], true);
        if (isset($dim_data['area'])) {
            $area = floatval($dim_data['area']);
        }
        if (isset($dim_data['width'])) {
            $board_width = floatval($dim_data['width']);
        }
    }
    
    // Из калькулятора с множителем
    if (!$area && isset($cart_item_data['_multiplier_calc_data'])) {
        $mult_data = json_decode($cart_item_data['_multiplier_calc_data'], true);
        if (isset($mult_data['area'])) {
            $area = floatval($mult_data['area']);
        }
        if (isset($mult_data['width'])) {
            $board_width = floatval($mult_data['width']);
        }
    }
    
    // Из калькулятора квадратных метров
    if (!$area && isset($cart_item_data['_square_meter_calc_data'])) {
        $sq_data = json_decode($cart_item_data['_square_meter_calc_data'], true);
        if (isset($sq_data['area'])) {
            $area = floatval($sq_data['area']);
        }
        if (isset($sq_data['width'])) {
            $board_width = floatval($sq_data['width']);
        }
    }
    
    // Если площадь или ширина не найдены, не добавляем крепеж
    if ($area <= 0 || $board_width <= 0) {
        return;
    }
    
    // Рассчитываем количество крепежа
    $fastener_product_id = $selected_fastener_id;
    
    // Получаем таблицу расчёта крепежа
    if (function_exists('calculate_fasteners_per_sqm') && function_exists('get_planken_fastener_table')) {
        $table = get_planken_fastener_table();
        $fasteners_per_sqm = calculate_fasteners_per_sqm($board_width, $table);
        $total_fasteners = ceil($area * $fasteners_per_sqm);
        
        // Получаем товар крепежа для расчёта упаковок
        $fastener_product = wc_get_product($fastener_product_id);
        if (!$fastener_product) {
            return;
        }
        
        $fastener_base_price = floatval($fastener_product->get_price());
        
        // Рассчитываем количество упаковок (если крепеж продаётся упаковками)
        $package_qty = parusweb_extract_package_quantity($fastener_product->get_name());
        
        $packages_needed = 1;
        if ($package_qty > 0) {
            $packages_needed = ceil($total_fasteners / $package_qty);
        } else {
            // Если количество в упаковке не указано, добавляем штуками
            $packages_needed = $total_fasteners;
        }
        
// Добавляем крепеж в корзину
$fastener_cart_data = [
    '_auto_added_fastener' => true,
    '_parent_product_id' => $product_id,
    '_parent_cart_key' => $cart_item_key,
    '_fastener_calc_details' => json_encode([
        'area' => $area,
        'board_width' => $board_width,
        'fasteners_per_sqm' => $fasteners_per_sqm,
        'total_fasteners' => $total_fasteners,
        'package_qty' => $package_qty,
        'packages_needed' => $packages_needed,
    ]),
];

// ==============================
// КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ START
// ==============================
// Сохраняем данные покраски основного товара
$saved_painting = [];
$painting_keys = [
    'painting_service_key',
    'painting_service_id', 
    'painting_service_name',
    'painting_service_total_cost',
    'painting_service_cost',
    'painting_service_price_per_m2',
    'painting_service_area'
];

foreach ($painting_keys as $key) {
    if (isset($_POST[$key])) {
        $saved_painting[$key] = $_POST[$key];
        unset($_POST[$key]); // Временно удаляем
    }
}
// ==============================
// КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ END
// ==============================

$fastener_categories = [77, 299, 300, 80, 123]; // Крепеж
$lkm_categories = []; // ЛКМ

WC()->cart->add_to_cart($fastener_product_id, $packages_needed, 0, [], $fastener_cart_data);

// Восстанавливаем данные покраски для основного товара
foreach ($saved_painting as $key => $value) {
    $_POST[$key] = $value;
}
    }
}
add_action('woocommerce_add_to_cart', 'parusweb_auto_add_fastener_to_cart', 10, 6);

/**
 * Извлечение количества из названия упаковки крепежа
 * Ищет паттерны типа "100 шт", "500шт" и т.д.
 * 
 * @param string $product_name Название товара
 * @return int Количество штук или 0
 */
function parusweb_extract_package_quantity($product_name) {
    // Паттерны: "100 шт", "100шт", "100 ШТ", "(100шт)"
    if (preg_match('/(\d+)\s*шт/ui', $product_name, $matches)) {
        return intval($matches[1]);
    }
    
    return 0;
}

// ============================================================================
// БЛОК 4: УДАЛЕНИЕ КРЕПЕЖА ПРИ УДАЛЕНИИ ОСНОВНОГО ТОВАРА
// ============================================================================

/**
 * Автоматическое удаление крепежа при удалении основного товара из корзины
 */
function parusweb_remove_fastener_with_parent($cart_item_key, $cart) {
    $cart_item = $cart->get_cart_item($cart_item_key);
    
    if (!$cart_item) {
        return;
    }
    
    // Ищем связанный крепеж
    foreach ($cart->get_cart() as $item_key => $item) {
        if (isset($item['_parent_cart_key']) && $item['_parent_cart_key'] === $cart_item_key) {
            $cart->remove_cart_item($item_key);
        }
    }
}
add_action('woocommerce_remove_cart_item', 'parusweb_remove_fastener_with_parent', 10, 2);

/**
 * Обновление количества крепежа при изменении количества основного товара
 */
function parusweb_update_fastener_quantity($cart_item_key, $quantity, $old_quantity, $cart) {
    return;
}
add_action('woocommerce_after_cart_item_quantity_update', 'parusweb_update_fastener_quantity', 10, 4);

// ============================================================================
// БЛОК 5: ОТОБРАЖЕНИЕ В КОРЗИНЕ
// ============================================================================

/**
 * Отображение информации о крепеже в корзине
 */
function parusweb_display_fastener_addon_in_cart($item_data, $cart_item) {
    if (isset($cart_item['_auto_added_fastener']) && $cart_item['_auto_added_fastener']) {
        $item_data[] = [
            'key' => 'Автоматически добавлен',
            'value' => 'Крепёж для основного товара',
        ];
        
        if (isset($cart_item['_fastener_calc_details'])) {
            $details = json_decode($cart_item['_fastener_calc_details'], true);
            
            if ($details) {
                $item_data[] = [
                    'key' => 'Расчёт',
                    'value' => sprintf(
                        'Площадь: %.2f м², ширина доски: %d мм, норма: %d шт/м²',
                        $details['area'],
                        $details['board_width'],
                        $details['fasteners_per_sqm']
                    ),
                ];
                
                if ($details['package_qty'] > 0) {
                    $item_data[] = [
                        'key' => 'Упаковок',
                        'value' => sprintf(
                            '%d упак. (всего %d шт, по %d шт/упак)',
                            $details['packages_needed'],
                            $details['total_fasteners'],
                            $details['package_qty']
                        ),
                    ];
                }
            }
        }
    }
    
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'parusweb_display_fastener_addon_in_cart', 10, 2);

// ============================================================================
// БЛОК 6: СОХРАНЕНИЕ В ЗАКАЗ
// ============================================================================

/**
 * Сохранение данных крепежа в мета-данные заказа
 */
function parusweb_save_fastener_addon_to_order($item, $cart_item_key, $values, $order) {
    if (isset($values['_auto_added_fastener'])) {
        $item->add_meta_data('_auto_added_fastener', true, true);
    }
    
    if (isset($values['_parent_product_id'])) {
        $item->add_meta_data('_parent_product_id', $values['_parent_product_id'], true);
    }
    
    if (isset($values['_fastener_calc_details'])) {
        $item->add_meta_data('_fastener_calc_details', $values['_fastener_calc_details'], true);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'parusweb_save_fastener_addon_to_order', 10, 4);

/**
 * Отображение в админке заказа
 */
function parusweb_display_fastener_addon_in_admin_order($item_id, $item, $product) {
    $is_auto_added = $item->get_meta('_auto_added_fastener');
    
    if ($is_auto_added) {
        echo '<div class="fastener-addon-info" style="margin-top:10px; padding:10px; background:#e8f5e9; border-left:3px solid #4caf50;">';
        echo '<strong>Крепёж добавлен</strong><br>';
        
        $details_json = $item->get_meta('_fastener_calc_details');
        if ($details_json) {
            $details = json_decode($details_json, true);
            
            if ($details) {
                echo 'Площадь: ' . $details['area'] . ' м²<br>';
                echo 'Ширина доски: ' . $details['board_width'] . ' мм<br>';
                echo 'Норма расхода: ' . $details['fasteners_per_sqm'] . ' шт/м²<br>';
                echo 'Всего крепежа: ' . $details['total_fasteners'] . ' шт<br>';
                
                if ($details['package_qty'] > 0) {
                    echo 'Упаковок: ' . $details['packages_needed'] . ' шт (по ' . $details['package_qty'] . ' шт/упак)';
                }
            }
        }
        
        echo '</div>';
    }
}
add_action('woocommerce_after_order_itemmeta', 'parusweb_display_fastener_addon_in_admin_order', 10, 3);

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================