<?php
/**
 * ============================================================================
 * МОДУЛЬ: НАСТРОЙКИ КАТЕГОРИИ 271 (АДМИНКА)
 * ============================================================================
 * 
 * Настройки для товаров категории 271:
 * - Ширина (от-до, шаг) - настраивается
 * - Длина - фиксированная 3 м
 * - Толщина - фиксированная 40 мм
 * 
 * @package ParusWeb_Functions
 * @subpackage Admin
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// НАСТРОЙКИ КАТЕГОРИИ 271 - МЕТАПОЛЯ
// ============================================================================

/**
 * Добавить поля настроек ширины для категории 271
 */
add_action('product_cat_edit_form_fields', 'parusweb_add_category_271_width_fields', 10, 1);

function parusweb_add_category_271_width_fields($term) {
    // Показываем только для категории 271
    if ($term->term_id != 271) {
        return;
    }
    
    $width_min = get_term_meta($term->term_id, 'category_271_width_min', true);
    $width_max = get_term_meta($term->term_id, 'category_271_width_max', true);
    $width_step = get_term_meta($term->term_id, 'category_271_width_step', true);
    ?>
    
    <tr class="form-field">
        <th scope="row">
            <label>Настройки ширины для калькулятора</label>
        </th>
        <td>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; border: 2px solid #ddd;">
                <p style="margin-top: 0;"><strong>Параметры по умолчанию для товаров этой категории:</strong></p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label><strong>Ширина мин. (мм)</strong></label><br>
                        <input type="number" 
                               name="category_271_width_min" 
                               value="<?php echo esc_attr($width_min); ?>" 
                               placeholder="100"
                               style="width: 100%;">
                    </div>
                    
                    <div>
                        <label><strong>Ширина макс. (мм)</strong></label><br>
                        <input type="number" 
                               name="category_271_width_max" 
                               value="<?php echo esc_attr($width_max); ?>" 
                               placeholder="300"
                               style="width: 100%;">
                    </div>
                    
                    <div>
                        <label><strong>Шаг (мм)</strong></label><br>
                        <input type="number" 
                               name="category_271_width_step" 
                               value="<?php echo esc_attr($width_step); ?>" 
                               placeholder="10"
                               style="width: 100%;">
                    </div>
                </div>
                
                <div style="background: #e0f7ff; padding: 10px; border-radius: 3px;">
                    <p style="margin: 0;"><strong>ℹ️ Фиксированные параметры:</strong></p>
                    <ul style="margin: 5px 0;">
                        <li><strong>Длина:</strong> 3 м (не изменяется)</li>
                        <li><strong>Толщина:</strong> 40 мм (не изменяется)</li>
                    </ul>
                </div>
                
                <p style="margin-bottom: 0; color: #666; font-size: 0.9em;">
                    Эти настройки будут применяться ко всем новым товарам категории 271. 
                    Для каждого товара можно задать индивидуальные значения.
                </p>
            </div>
        </td>
    </tr>
    <?php
}

/**
 * Сохранить настройки категории 271
 */
add_action('edited_product_cat', 'parusweb_save_category_271_width_fields', 10, 1);

function parusweb_save_category_271_width_fields($term_id) {
    if ($term_id != 271) {
        return;
    }
    
    if (isset($_POST['category_271_width_min'])) {
        update_term_meta($term_id, 'category_271_width_min', sanitize_text_field($_POST['category_271_width_min']));
    }
    
    if (isset($_POST['category_271_width_max'])) {
        update_term_meta($term_id, 'category_271_width_max', sanitize_text_field($_POST['category_271_width_max']));
    }
    
    if (isset($_POST['category_271_width_step'])) {
        update_term_meta($term_id, 'category_271_width_step', sanitize_text_field($_POST['category_271_width_step']));
    }
}

// ============================================================================
// НАСТРОЙКИ ТОВАРА КАТЕГОРИИ 271 - МЕТАПОЛЯ
// ============================================================================

/**
 * Добавить поля настроек ширины для товаров категории 271
 */
add_action('woocommerce_product_options_general_product_data', 'parusweb_add_product_271_width_fields');

function parusweb_add_product_271_width_fields() {
    global $post;
    
    // Проверяем, принадлежит ли товар категории 271
    if (!has_term(271, 'product_cat', $post->ID)) {
        return;
    }
    
    // Получаем настройки категории как значения по умолчанию
    $terms = wp_get_post_terms($post->ID, 'product_cat', ['fields' => 'ids']);
    $category_width_min = '';
    $category_width_max = '';
    $category_width_step = '';
    
    if (!empty($terms) && in_array(271, $terms)) {
        $category_width_min = get_term_meta(271, 'category_271_width_min', true);
        $category_width_max = get_term_meta(271, 'category_271_width_max', true);
        $category_width_step = get_term_meta(271, 'category_271_width_step', true);
    }
    
    // Получаем значения товара
    $width_min = get_post_meta($post->ID, '_calc_width_min', true);
    $width_max = get_post_meta($post->ID, '_calc_width_max', true);
    $width_step = get_post_meta($post->ID, '_calc_width_step', true);
    
    ?>
    <div class="options_group">
        <h4 style="padding-left: 12px; color: #2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 10px; margin-bottom: 15px;">
            📐 Настройки калькулятора (Категория 271)
        </h4>
        
        <div style="padding: 0 12px; background: #f9f9f9; margin: 0 12px 20px; border-radius: 5px; padding: 15px;">
            <p style="margin-top: 0;"><strong>Настройки ширины для калькулятора:</strong></p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                <?php
                woocommerce_wp_text_input([
                    'id' => '_calc_width_min',
                    'label' => 'Ширина мин. (мм)',
                    'desc_tip' => true,
                    'description' => 'Минимальная ширина для калькулятора',
                    'type' => 'number',
                    'placeholder' => $category_width_min ?: '100',
                    'value' => $width_min,
                    'custom_attributes' => ['step' => '1', 'min' => '1']
                ]);
                
                woocommerce_wp_text_input([
                    'id' => '_calc_width_max',
                    'label' => 'Ширина макс. (мм)',
                    'desc_tip' => true,
                    'description' => 'Максимальная ширина для калькулятора',
                    'type' => 'number',
                    'placeholder' => $category_width_max ?: '300',
                    'value' => $width_max,
                    'custom_attributes' => ['step' => '1', 'min' => '1']
                ]);
                
                woocommerce_wp_text_input([
                    'id' => '_calc_width_step',
                    'label' => 'Шаг ширины (мм)',
                    'desc_tip' => true,
                    'description' => 'Шаг изменения ширины',
                    'type' => 'number',
                    'placeholder' => $category_width_step ?: '10',
                    'value' => $width_step,
                    'custom_attributes' => ['step' => '1', 'min' => '1']
                ]);
                ?>
            </div>
            
            <div style="background: #e0f7ff; padding: 10px; border-radius: 3px; margin-top: 15px;">
                <p style="margin: 5px 0 0 0;"><strong>ℹ️ Фиксированные параметры для категории 271:</strong></p>
                <ul style="margin: 5px 0;">
                    <li><strong>Длина:</strong> 3.0 м (не изменяется)</li>
                    <li><strong>Толщина:</strong> 40 мм (не изменяется)</li>
                </ul>
            </div>
            
            <?php if ($category_width_min || $category_width_max || $category_width_step): ?>
            <p style="margin-bottom: 0; color: #666; font-size: 0.9em; margin-top: 10px;">
                <strong>Значения из категории:</strong> 
                Ширина: <?php echo $category_width_min ?: '—'; ?> - <?php echo $category_width_max ?: '—'; ?> мм 
                (шаг <?php echo $category_width_step ?: '—'; ?> мм)
            </p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Сохранить настройки товара категории 271
 */
add_action('woocommerce_process_product_meta', 'parusweb_save_product_271_width_fields');

function parusweb_save_product_271_width_fields($post_id) {
    // Проверяем, принадлежит ли товар категории 271
    if (!has_term(271, 'product_cat', $post_id)) {
        return;
    }
    
    // Сохраняем ширину
    if (isset($_POST['_calc_width_min'])) {
        update_post_meta($post_id, '_calc_width_min', sanitize_text_field($_POST['_calc_width_min']));
    }
    
    if (isset($_POST['_calc_width_max'])) {
        update_post_meta($post_id, '_calc_width_max', sanitize_text_field($_POST['_calc_width_max']));
    }
    
    if (isset($_POST['_calc_width_step'])) {
        update_post_meta($post_id, '_calc_width_step', sanitize_text_field($_POST['_calc_width_step']));
    }
    
    // Автоматически устанавливаем фиксированные значения
    update_post_meta($post_id, '_calc_length_min', '3.0');
    update_post_meta($post_id, '_calc_length_max', '3.0');
    update_post_meta($post_id, '_calc_length_step', '0.01');
    
    // Сохраняем толщину в отдельное поле для отображения
    update_post_meta($post_id, '_fixed_thickness', '40');
}

// ============================================================================
// АВТОПРИМЕНЕНИЕ НАСТРОЕК КАТЕГОРИИ К НОВЫМ ТОВАРАМ
// ============================================================================

/**
 * Применить настройки категории 271 к новому товару
 */
add_action('woocommerce_new_product', 'parusweb_apply_category_271_defaults_to_new_product');

function parusweb_apply_category_271_defaults_to_new_product($product_id) {
    // Проверяем, принадлежит ли товар категории 271
    if (!has_term(271, 'product_cat', $product_id)) {
        return;
    }
    
    // Получаем настройки категории
    $category_width_min = get_term_meta(271, 'category_271_width_min', true);
    $category_width_max = get_term_meta(271, 'category_271_width_max', true);
    $category_width_step = get_term_meta(271, 'category_271_width_step', true);
    
    // Применяем настройки ширины
    if ($category_width_min) {
        update_post_meta($product_id, '_calc_width_min', $category_width_min);
    }
    
    if ($category_width_max) {
        update_post_meta($product_id, '_calc_width_max', $category_width_max);
    }
    
    if ($category_width_step) {
        update_post_meta($product_id, '_calc_width_step', $category_width_step);
    }
    
    // Фиксированные значения
    update_post_meta($product_id, '_calc_length_min', '3.0');
    update_post_meta($product_id, '_calc_length_max', '3.0');
    update_post_meta($product_id, '_calc_length_step', '0.01');
    update_post_meta($product_id, '_fixed_thickness', '40');
}