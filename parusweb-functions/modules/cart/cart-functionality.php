<?php
/**
 * ============================================================================
 * МОДУЛЬ: ФУНКЦИОНАЛ КОРЗИНЫ
 * ============================================================================
 * 
 * Добавление данных калькуляторов в корзину:
 * - Калькулятор площади
 * - Калькулятор размеров
 * - Калькулятор множителя
 * - Калькулятор погонных метров
 * - Калькулятор квадратных метров
 * - Калькулятор реечных перегородок
 * - Обычные покупки без калькулятора
 * - Покупки из карточек товаров
 * 
 * @package ParusWeb_Functions
 * @subpackage Cart
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// БЛОК 0: ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

/**
 * Проверка - является ли товар крепежом
 * 
 * @param int $product_id ID товара
 * @return bool true если товар из категории крепежа
 */
function parusweb_is_fastener_product($product_id) {
    // Категории крепежа (ID: 77 - основная категория крепежа)
    // Добавьте сюда все ID категорий крепежа, которые используются
    $fastener_categories = [77, 299, 300, 80, 123];
    
    $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
    
    if (is_wp_error($product_categories) || empty($product_categories)) {
        return false;
    }
    
    // Проверяем прямое совпадение
    foreach ($product_categories as $cat_id) {
        if (in_array($cat_id, $fastener_categories)) {
            return true;
        }
        
        // Проверяем родительские категории
        $ancestors = get_ancestors($cat_id, 'product_cat');
        foreach ($ancestors as $ancestor_id) {
            if (in_array($ancestor_id, $fastener_categories)) {
                return true;
            }
        }
    }
    
    return false;
}

// ============================================================================
// БЛОК 1: ДОБАВЛЕНИЕ ДАННЫХ КАЛЬКУЛЯТОРОВ В КОРЗИНУ
// ============================================================================

add_filter('woocommerce_add_cart_item_data', 'parusweb_add_calculator_data_to_cart', 10, 3);

function parusweb_add_calculator_data_to_cart($cart_item_data, $product_id, $variation_id) {
    
    if (!is_in_target_categories($product_id)) {
        return $cart_item_data;
    }
    
    $product = wc_get_product($product_id);
    if (!$product) return $cart_item_data;
    
    $title = $product->get_name();
    $pack_area = extract_area_with_qty($title, $product_id);
    $base_price_m2 = floatval($product->get_regular_price() ?: $product->get_price());
    
    $leaf_parent_id = 190;
    $leaf_children = [191, 127, 94];
    $leaf_ids = array_merge([$leaf_parent_id], $leaf_children);
    $is_leaf_category = has_term($leaf_ids, 'product_cat', $product_id);
    
    // КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Не добавляем данные покраски для крепежа
    $painting_service = null;
    if (!parusweb_is_fastener_product($product_id)) {
        $painting_service = parusweb_get_painting_service_from_post();
    }
    
    $scheme_data = parusweb_get_scheme_data_from_post();
    if ($scheme_data) {
        $cart_item_data = array_merge($cart_item_data, $scheme_data);
    }
    
    if (!empty($_POST['custom_area_packs']) && !empty($_POST['custom_area_area_value'])) {
        $cart_item_data['custom_area_calc'] = [
            'packs' => intval($_POST['custom_area_packs']),
            'area' => floatval($_POST['custom_area_area_value']),
            'total_price' => floatval($_POST['custom_area_total_price']),
            'grand_total' => floatval($_POST['custom_area_grand_total'] ?? $_POST['custom_area_total_price']),
            'is_leaf' => $is_leaf_category,
            'painting_service' => $painting_service
        ];
        return $cart_item_data;
    }
    
    if (!empty($_POST['custom_width_val']) && !empty($_POST['custom_length_val'])) {
        $cart_item_data['custom_dimensions'] = [
            'width' => intval($_POST['custom_width_val']),
            'length'=> intval($_POST['custom_length_val']),
            'price'=> floatval($_POST['custom_dim_price']),
            'grand_total' => floatval($_POST['custom_dim_grand_total'] ?? $_POST['custom_dim_price']),
            'is_leaf' => $is_leaf_category,
            'painting_service' => $painting_service
        ];
        return $cart_item_data;
    }
    
    if (!empty($_POST['custom_multiplier']) && !empty($_POST['custom_mult_width']) && !empty($_POST['custom_mult_length'])) {
        $width = intval($_POST['custom_mult_width']);
        $length = floatval($_POST['custom_mult_length']);
        $area_per_item = ($width / 1000) * $length;
        
        $cart_item_data['custom_multiplier_calc'] = [
            'multiplier' => intval($_POST['custom_multiplier']),
            'width' => $width,
            'length' => $length,
            'area_per_item' => $area_per_item,
            'total_area' => $area_per_item * intval($_POST['custom_multiplier']),
            'total_price' => floatval($_POST['custom_mult_price']),
            'grand_total' => floatval($_POST['custom_mult_grand_total'] ?? $_POST['custom_mult_price']),
            'is_leaf' => $is_leaf_category,
            'painting_service' => $painting_service
        ];
        return $cart_item_data;
    }
    
    if (!empty($_POST['custom_rm_length'])) {
        $cart_item_data['custom_running_meter_calc'] = [
            'length' => floatval($_POST['custom_rm_length']),
            'price_per_meter' => floatval($_POST['custom_rm_price_per_meter'] ?? $base_price_m2),
            'total_price' => floatval($_POST['custom_rm_price']),
            'grand_total' => floatval($_POST['custom_rm_grand_total'] ?? $_POST['custom_rm_price']),
            'painting_service' => $painting_service
        ];
        
        if (!empty($_POST['custom_rm_shape'])) {
            $cart_item_data['custom_running_meter_calc']['shape'] = sanitize_text_field($_POST['custom_rm_shape']);
            $cart_item_data['custom_running_meter_calc']['shape_label'] = sanitize_text_field($_POST['custom_rm_shape_label'] ?? '');
        }
        
        if (!empty($_POST['custom_rm_faska'])) {
            $cart_item_data['custom_running_meter_calc']['faska'] = sanitize_text_field($_POST['custom_rm_faska']);
            $cart_item_data['custom_running_meter_calc']['faska_label'] = sanitize_text_field($_POST['custom_rm_faska_label'] ?? '');
        }
        
        return $cart_item_data;
    }
    
    if (!empty($_POST['custom_sq_width']) && !empty($_POST['custom_sq_area'])) {
        $cart_item_data['custom_square_meter_calc'] = [
            'width' => intval($_POST['custom_sq_width']),
            'area' => floatval($_POST['custom_sq_area']),
            'total_price' => floatval($_POST['custom_sq_price']),
            'grand_total' => floatval($_POST['custom_sq_grand_total'] ?? $_POST['custom_sq_price']),
            'painting_service' => $painting_service
        ];
        return $cart_item_data;
    }
    
    if (!empty($_POST['custom_part_width']) && !empty($_POST['custom_part_length']) && !empty($_POST['custom_part_thickness'])) {
        $cart_item_data['custom_partition_slat_calc'] = [
            'width' => intval($_POST['custom_part_width']),
            'length' => floatval($_POST['custom_part_length']),
            'thickness' => intval($_POST['custom_part_thickness']),
            'volume' => floatval($_POST['custom_part_volume']),
            'total_price' => floatval($_POST['custom_part_price']),
            'painting_service' => $painting_service
        ];
        return $cart_item_data;
    }
    
    if (!empty($_POST['card_pack_purchase'])) {
        $cart_item_data['card_pack_purchase'] = [
            'area' => $pack_area,
            'price_per_m2' => $base_price_m2,
            'total_price' => $base_price_m2 * $pack_area,
            'is_leaf' => $is_leaf_category,
            'unit_type' => $is_leaf_category ? 'лист' : 'упаковка',
            'painting_service' => $painting_service
        ];
        return $cart_item_data;
    }
    
if ($pack_area > 0) {
    // ИСПРАВЛЕНИЕ ДЛЯ КАТЕГОРИИ 90: Используем правильную цену
    $cubic_categories = [87, 310, 88, 90, 91, 92, 93];
$is_cubic_meter_cat = has_term($cubic_categories, 'product_cat', $product_id_for_check);
    $total_price = $base_price_m2 * $pack_area;
    
    if ($is_cubic_meter_cat && function_exists('calculate_cubic_package_price')) {
        $cubic_calc = calculate_cubic_package_price($product_id, $base_price_m2);
        if ($cubic_calc) {
            $total_price = $cubic_calc['price_per_pack'];
            $base_price_m2 = $cubic_calc['price_per_m2'];
        }
    }
    
    $cart_item_data['standard_pack_purchase'] = [
        'area' => $pack_area,
        'price_per_m2' => $base_price_m2,
        'total_price' => $total_price,
        'is_leaf' => $is_leaf_category,
        'unit_type' => $is_leaf_category ? 'лист' : 'упаковка',
        'painting_service' => $painting_service
    ];
}
    
    return $cart_item_data;
}

// ============================================================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

function parusweb_get_painting_service_from_post() {
    if (empty($_POST['painting_service_id'])) {
        return null;
    }
    
    $painting_service = [
        'id' => intval($_POST['painting_service_id']),
        'name' => sanitize_text_field($_POST['painting_service_name'] ?? ''),
        'price_per_m2' => floatval($_POST['painting_service_price_per_m2'] ?? 0),
        'area' => floatval($_POST['painting_service_area'] ?? 0),
        'total_cost' => floatval($_POST['painting_service_cost'] ?? 0)
    ];
    
    if (!empty($_POST['painting_service_color'])) {
        $painting_service['color'] = sanitize_text_field($_POST['painting_service_color']);
    }
    
    $painting_service['name_with_color'] = $painting_service['name'];
    if (!empty($painting_service['color'])) {
        $painting_service['name_with_color'] .= ' (' . $painting_service['color'] . ')';
    }
    
    return $painting_service;
}

function parusweb_get_scheme_data_from_post() {
    if (empty($_POST['pm_selected_scheme'])) {
        return null;
    }
    
    $scheme_data = [
        'pm_selected_scheme_id' => intval($_POST['pm_selected_scheme']),
        'pm_selected_scheme_name' => sanitize_text_field($_POST['pm_selected_scheme_name'] ?? ''),
    ];
    
    if (!empty($_POST['pm_selected_color'])) {
        $scheme_data['pm_selected_color'] = sanitize_text_field($_POST['pm_selected_color']);
    }
    
    if (!empty($_POST['pm_selected_color_image'])) {
        $scheme_data['pm_selected_color_image'] = esc_url_raw($_POST['pm_selected_color_image']);
    }
    
    if (!empty($_POST['pm_selected_color_filename'])) {
        $scheme_data['pm_selected_color_filename'] = sanitize_text_field($_POST['pm_selected_color_filename']);
    }
    
    return !empty($scheme_data['pm_selected_scheme_id']) ? $scheme_data : null;
}

// ============================================================================
// БЛОК 2: УСТАНОВКА ПРАВИЛЬНОГО КОЛИЧЕСТВА
// ============================================================================

add_filter('woocommerce_add_to_cart_quantity', 'parusweb_adjust_cart_quantity', 10, 2);

function parusweb_adjust_cart_quantity($quantity, $product_id) {
    if (!is_in_target_categories($product_id)) {
        return $quantity;
    }
    
    if (isset($_POST['custom_area_packs']) && !empty($_POST['custom_area_packs']) && 
        isset($_POST['custom_area_area_value']) && !empty($_POST['custom_area_area_value'])) {
        return intval($_POST['custom_area_packs']);
    }
    
    if (isset($_POST['custom_width_val']) && !empty($_POST['custom_width_val']) && 
        isset($_POST['custom_length_val']) && !empty($_POST['custom_length_val'])) {
        return 1;
    }
    
    return $quantity;
}

// ============================================================================
// БЛОК 3: КОРРЕКТИРОВКА ПОСЛЕ ДОБАВЛЕНИЯ
// ============================================================================

add_action('woocommerce_add_to_cart', 'parusweb_correct_cart_quantity', 10, 6);

function parusweb_correct_cart_quantity($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    if (!is_in_target_categories($product_id)) {
        return;
    }
    
    if (isset($cart_item_data['custom_area_calc'])) {
        $packs = intval($cart_item_data['custom_area_calc']['packs']);
        if ($packs > 0 && $quantity !== $packs) {
            WC()->cart->set_quantity($cart_item_key, $packs);
        }
    }
}

// ============================================================================
// БЛОК 4: ПЕРЕСЧЕТ ЦЕН В КОРЗИНЕ
// ============================================================================

add_action('woocommerce_before_calculate_totals', 'parusweb_recalculate_cart_prices');

function parusweb_recalculate_cart_prices($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    foreach ($cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        
        if (isset($cart_item['custom_area_calc'])) {
            $area_calc = $cart_item['custom_area_calc'];
            $base_price_m2 = floatval($product->get_regular_price() ?: $product->get_price());
            $area = floatval($area_calc['area']);
            $total_price = $base_price_m2 * $area;
            
            if (isset($area_calc['painting_service']) && !empty($area_calc['painting_service'])) {
                $painting_price = floatval($area_calc['painting_service']['total_cost']);
                $total_price += $painting_price;
            }
            
            $product->set_price($total_price);
        }
        
        if (isset($cart_item['custom_dimensions'])) {
            $dims = $cart_item['custom_dimensions'];
            $price = floatval($dims['price']);
            
            if (isset($dims['painting_service']) && !empty($dims['painting_service'])) {
                $painting_price = floatval($dims['painting_service']['total_cost']);
                $price += $painting_price;
            }
            
            $product->set_price($price);
        }
        
        if (isset($cart_item['custom_multiplier_calc'])) {
            $mult_calc = $cart_item['custom_multiplier_calc'];
            $price = floatval($mult_calc['total_price']);
            
            if (isset($mult_calc['painting_service']) && !empty($mult_calc['painting_service'])) {
                $painting_price = floatval($mult_calc['painting_service']['total_cost']);
                $price += $painting_price;
            }
            
            $product->set_price($price);
        }
        
        if (isset($cart_item['custom_running_meter_calc'])) {
            $rm_calc = $cart_item['custom_running_meter_calc'];
            $price = floatval($rm_calc['total_price']);
            
            if (isset($rm_calc['painting_service']) && !empty($rm_calc['painting_service'])) {
                $painting_price = floatval($rm_calc['painting_service']['total_cost']);
                $price += $painting_price;
            }
            
            $product->set_price($price);
        }
        
        if (isset($cart_item['custom_square_meter_calc'])) {
            $sq_calc = $cart_item['custom_square_meter_calc'];
            $price = floatval($sq_calc['total_price']);
            
            if (isset($sq_calc['painting_service']) && !empty($sq_calc['painting_service'])) {
                $painting_price = floatval($sq_calc['painting_service']['total_cost']);
                $price += $painting_price;
            }
            
            $product->set_price($price);
        }
        
        if (isset($cart_item['custom_partition_slat_calc'])) {
            $part_calc = $cart_item['custom_partition_slat_calc'];
            $price = floatval($part_calc['total_price']);
            
            if (isset($part_calc['painting_service']) && !empty($part_calc['painting_service'])) {
                $painting_price = floatval($part_calc['painting_service']['total_cost']);
                $price += $painting_price;
            }
            
            $product->set_price($price);
        }
        
        if (isset($cart_item['card_pack_purchase'])) {
            $pack_data = $cart_item['card_pack_purchase'];
            $price = floatval($pack_data['total_price']);
            
            if (isset($pack_data['painting_service']) && !empty($pack_data['painting_service'])) {
                $painting_price = floatval($pack_data['painting_service']['total_cost']);
                $price += $painting_price;
            }
            
            $product->set_price($price);
        }
        
        if (isset($cart_item['standard_pack_purchase'])) {
            $pack_data = $cart_item['standard_pack_purchase'];
            $price = floatval($pack_data['total_price']);
            
            if (isset($pack_data['painting_service']) && !empty($pack_data['painting_service'])) {
                $painting_price = floatval($pack_data['painting_service']['total_cost']);
                $price += $painting_price;
            }
            
            $product->set_price($price);
        }
        
        if (isset($cart_item['tara'])) {
            $base_price = floatval($product->get_regular_price());
            $volume = floatval($cart_item['tara']);
            $price = $base_price * $volume;
            
            if ($volume >= 9) {
                $price *= 0.9;
            }
            
            $product->set_price($price);
        }
    }
}

// ============================================================================
// БЛОК 5: JAVASCRIPT ДЛЯ КАРТОЧЕК ТОВАРОВ
// ============================================================================

add_action('wp_footer', 'parusweb_card_purchase_script');

function parusweb_card_purchase_script() {
    if (!is_shop() && !is_product_category() && !is_product_tag()) {
        return;
    }
    
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const addToCartButtons = document.querySelectorAll('.add_to_cart_button:not(.product_type_variable)');
        
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                const productId = this.getAttribute('data-product_id');
                
                if (!productId) return;
                
                const formData = new FormData();
                formData.append('card_pack_purchase', '1');
                formData.append('product_id', productId);
                formData.append('quantity', this.getAttribute('data-quantity') || 1);
                
                const href = this.getAttribute('href');
                if (href && href.includes('add-to-cart=')) {
                    e.preventDefault();
                    
                    fetch(wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'), {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error('Error adding to cart:', data);
                            return;
                        }
                        
                        jQuery(document.body).trigger('added_to_cart', [data.fragments, data.cart_hash, button]);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            });
        });
    });
    </script>
    <?php
}

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================