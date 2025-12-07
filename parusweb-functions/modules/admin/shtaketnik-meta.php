<?php
/**
 * Shtaketnik Meta Fields
 * 
 * Метаполя для штакетника (категория 273):
 * - Цены за форму верха (полукруг, треугольник, прямой спил)
 * 
 * @package ParusWeb_Functions
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: МЕТАПОЛЯ ЦЕН ФОРМ ВЕРХА
// ============================================================================

/**
 * Добавление полей цен форм верха штакетника в раздел "Цены"
 */
 if (!function_exists('parusweb_add_shtaketnik_shape_prices')) {
function parusweb_add_shtaketnik_shape_prices() {
    global $post;
    
    // Проверяем, что товар относится к категории штакетника (273)
    if (!has_term(273, 'product_cat', $post->ID)) {
        return;
    }
    
    echo '<div class="options_group">';
    echo '<h4 style="padding-left: 12px; color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-bottom: 15px;">Цены за форму верха штакетника</h4>';
    
    // Поле: Цена за полукруглую форму
    woocommerce_wp_text_input([
        'id' => '_shape_price_round',
        'label' => 'Цена "Полукруг" (₽)',
        'desc_tip' => true,
        'description' => 'Дополнительная цена за полукруглую форму верха',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0'],
        'placeholder' => '0.00'
    ]);
    
    // Поле: Цена за треугольную форму
    woocommerce_wp_text_input([
        'id' => '_shape_price_triangle',
        'label' => 'Цена "Треугольник" (₽)',
        'desc_tip' => true,
        'description' => 'Дополнительная цена за треугольную форму верха',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0'],
        'placeholder' => '0.00'
    ]);
    
    // Поле: Цена за прямую форму
    woocommerce_wp_text_input([
        'id' => '_shape_price_flat',
        'label' => 'Цена "Прямой спил" (₽)',
        'desc_tip' => true,
        'description' => 'Дополнительная цена за прямую форму верха (обычно 0 или минимальная)',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0'],
        'placeholder' => '0.00'
    ]);
    
    echo '<p class="form-field" style="padding-left: 12px; color: #666; font-style: italic; margin-top: 10px;">';
    echo 'Эти цены добавляются к базовой стоимости товара при выборе соответствующей формы верха в калькуляторе.';
    echo '</p>';
    
    echo '</div>';
}
}
add_action('woocommerce_product_options_pricing', 'parusweb_add_shtaketnik_shape_prices');

// ============================================================================
// БЛОК 2: СОХРАНЕНИЕ МЕТАПОЛЕЙ
// ============================================================================

/**
 * Сохранение цен форм верха штакетника
 */
function parusweb_save_shtaketnik_shape_prices($post_id) {
    // Проверяем, что товар относится к категории штакетника
    if (!has_term(273, 'product_cat', $post_id)) {
        return;
    }
    
    $shape_price_fields = [
        '_shape_price_round',
        '_shape_price_triangle',
        '_shape_price_flat'
    ];
    
    foreach ($shape_price_fields as $field) {
        if (isset($_POST[$field])) {
            $value = sanitize_text_field($_POST[$field]);
            
            if ($value === '' || $value === '0') {
                delete_post_meta($post_id, $field);
            } else {
                update_post_meta($post_id, $field, $value);
            }
        }
    }
}
add_action('woocommerce_process_product_meta', 'parusweb_save_shtaketnik_shape_prices');

// ============================================================================
// БЛОК 3: ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

/**
 * Получение цены конкретной формы верха
 * 
 * @param int $product_id ID товара
 * @param string $shape_type Тип формы: 'round', 'triangle', 'flat'
 * @return float Цена формы
 */
function parusweb_get_shape_price($product_id, $shape_type) {
    $meta_key = '_shape_price_' . $shape_type;
    $price = get_post_meta($product_id, $meta_key, true);
    
    return floatval($price);
}

/**
 * Получение всех цен форм верха для товара
 * 
 * @param int $product_id ID товара
 * @return array Массив цен ['round' => 0, 'triangle' => 0, 'flat' => 0]
 */
function parusweb_get_all_shape_prices($product_id) {
    return [
        'round' => parusweb_get_shape_price($product_id, 'round'),
        'triangle' => parusweb_get_shape_price($product_id, 'triangle'),
        'flat' => parusweb_get_shape_price($product_id, 'flat')
    ];
}

/**
 * Проверка, настроены ли цены форм верха для товара
 * 
 * @param int $product_id ID товара
 * @return bool true если хотя бы одна цена задана
 */
function parusweb_has_shape_prices($product_id) {
    $prices = parusweb_get_all_shape_prices($product_id);
    
    foreach ($prices as $price) {
        if ($price > 0) {
            return true;
        }
    }
    
    return false;
}

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================
