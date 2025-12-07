<?php
/**
 * ============================================================================
 * МОДУЛЬ: МЕТАПОЛЯ ТОВАРОВ (АДМИНКА)
 * ============================================================================
 * 
 * Настройки калькуляторов для товаров:
 * - Множитель цены
 * - Настройки калькулятора размеров (min/max/step)
 * - Настройки крепежа
 * - Цены форм верха штакетника
 * 
 * ВАЖНО: Метаполя фальшбалок вынесены в отдельный файл falsebalk-meta.php
 * 
 * @package ParusWeb_Functions
 * @subpackage Admin
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// БЛОК 1: МНОЖИТЕЛЬ ЦЕНЫ
// ============================================================================

add_action('woocommerce_product_options_pricing', 'parusweb_add_price_multiplier_field');

function parusweb_add_price_multiplier_field() {
    echo '<div class="options_group">';
    
    woocommerce_wp_text_input([
        'id' => '_price_multiplier',
        'label' => 'Множитель цены',
        'desc_tip' => true,
        'description' => 'Множитель для расчета итоговой цены (например, 1.5). Если не задан, используется множитель категории.',
        'type' => 'number',
        'custom_attributes' => [
            'step' => '0.01',
            'min' => '0'
        ]
    ]);
    
    echo '</div>';
}

// ============================================================================
// БЛОК 2: НАСТРОЙКИ КАЛЬКУЛЯТОРА РАЗМЕРОВ
// ============================================================================

add_action('woocommerce_product_options_general_product_data', 'parusweb_add_calculator_settings');

function parusweb_add_calculator_settings() {
    global $post;
    
    echo '<div class="options_group show_if_simple show_if_variable">';
    echo '<h4 style="padding-left: 12px; color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-bottom: 15px;">📐 Настройки калькулятора размеров</h4>';
    
    woocommerce_wp_text_input([
        'id' => '_calc_width_min',
        'label' => 'Ширина мин. (мм)',
        'desc_tip' => true,
        'description' => 'Минимальная ширина для калькулятора',
        'type' => 'number',
        'custom_attributes' => ['step' => '1', 'min' => '0']
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_calc_width_max',
        'label' => 'Ширина макс. (мм)',
        'desc_tip' => true,
        'description' => 'Максимальная ширина для калькулятора',
        'type' => 'number',
        'custom_attributes' => ['step' => '1', 'min' => '0']
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_calc_width_step',
        'label' => 'Шаг ширины (мм)',
        'desc_tip' => true,
        'description' => 'Шаг изменения ширины (по умолчанию 100)',
        'placeholder' => '100',
        'type' => 'number',
        'custom_attributes' => ['step' => '1', 'min' => '1']
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_calc_length_min',
        'label' => 'Длина мин. (м)',
        'desc_tip' => true,
        'description' => 'Минимальная длина для калькулятора',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0.01']
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_calc_length_max',
        'label' => 'Длина макс. (м)',
        'desc_tip' => true,
        'description' => 'Максимальная длина для калькулятора',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0.01']
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_calc_length_step',
        'label' => 'Шаг длины (м)',
        'desc_tip' => true,
        'description' => 'Шаг изменения длины (по умолчанию 0.01)',
        'placeholder' => '0.01',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0.01']
    ]);
    
    echo '</div>';
}

// ============================================================================
// БЛОК 3: НАСТРОЙКИ КАЛЬКУЛЯТОРА КРЕПЕЖА
// ============================================================================

add_action('woocommerce_product_options_general_product_data', 'parusweb_add_fastener_calculator_settings');

function parusweb_add_fastener_calculator_settings() {
    global $post;
    
    $fastener_config = get_post_meta($post->ID, '_fastener_config', true);
    if (!is_array($fastener_config)) {
        $fastener_config = [];
    }
    
    echo '<div class="options_group show_if_simple show_if_variable">';
    echo '<h4 style="padding-left: 12px; color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-bottom: 15px;">🔩 Настройки калькулятора крепежа</h4>';
    
    woocommerce_wp_checkbox([
        'id' => '_fastener_enabled',
        'label' => 'Включить калькулятор крепежа',
        'description' => 'Показывать калькулятор автоматического расчета крепежа',
        'value' => !empty($fastener_config['enabled']) ? 'yes' : 'no'
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_fastener_coefficient',
        'label' => 'Коэффициент расчета',
        'desc_tip' => true,
        'description' => 'Коэффициент для расчета количества крепежа (по умолчанию 2.7)',
        'placeholder' => '2.7',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.1', 'min' => '0.1'],
        'value' => isset($fastener_config['coefficient']) ? $fastener_config['coefficient'] : '2.7'
    ]);
    
    echo '</div>';
}

// ============================================================================
// БЛОК 4: ЦЕНЫ ФОРМ ВЕРХА ШТАКЕТНИКА
// ============================================================================

add_action('woocommerce_product_options_pricing', 'parusweb_add_shtaketnik_shape_prices');

function parusweb_add_shtaketnik_shape_prices() {
    global $post;
    
    if (!has_term(273, 'product_cat', $post->ID)) {
        return;
    }
    
    echo '<div class="options_group">';
    echo '<h4 style="padding-left: 12px; color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-bottom: 15px;">🔺 Цены за форму верха штакетника</h4>';
    
    woocommerce_wp_text_input([
        'id' => '_shape_price_round',
        'label' => 'Цена "Полукруг" (₽)',
        'desc_tip' => true,
        'description' => 'Дополнительная цена за полукруглую форму верха',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0']
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_shape_price_triangle',
        'label' => 'Цена "Треугольник" (₽)',
        'desc_tip' => true,
        'description' => 'Дополнительная цена за треугольную форму верха',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0']
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_shape_price_flat',
        'label' => 'Цена "Прямая" (₽)',
        'desc_tip' => true,
        'description' => 'Дополнительная цена за прямую форму верха',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0']
    ]);
    
    echo '</div>';
}

// ============================================================================
// БЛОК 5: СОХРАНЕНИЕ МЕТАПОЛЕЙ
// ============================================================================

add_action('woocommerce_process_product_meta', 'parusweb_save_product_meta');

function parusweb_save_product_meta($post_id) {
    
    // Множитель цены
    $multiplier = isset($_POST['_price_multiplier']) ? 
        sanitize_text_field($_POST['_price_multiplier']) : '';
    update_post_meta($post_id, '_price_multiplier', $multiplier);
    
    // Настройки калькулятора
    $calc_fields = [
        '_calc_width_min', '_calc_width_max', '_calc_width_step',
        '_calc_length_min', '_calc_length_max', '_calc_length_step'
    ];
    
    foreach ($calc_fields as $field) {
        $value = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
        update_post_meta($post_id, $field, $value);
    }
    
    // Настройки крепежа
    $fastener_config = [];
    if (isset($_POST['_fastener_enabled']) && $_POST['_fastener_enabled'] === 'yes') {
        $fastener_config['enabled'] = true;
    }
    if (isset($_POST['_fastener_coefficient'])) {
        $fastener_config['coefficient'] = floatval($_POST['_fastener_coefficient']) ?: 2.7;
    }
    update_post_meta($post_id, '_fastener_config', $fastener_config);
    
    // Цены форм верха штакетника
    $shape_prices = [
        '_shape_price_round',
        '_shape_price_triangle',
        '_shape_price_flat'
    ];
    
    foreach ($shape_prices as $field) {
        $value = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
        update_post_meta($post_id, $field, $value);
    }
}

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================