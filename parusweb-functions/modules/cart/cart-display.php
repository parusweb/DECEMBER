<?php
if (!defined('ABSPATH')) exit;

add_filter('woocommerce_get_item_data', 'parusweb_display_calculator_data_in_cart', 999, 2);

function parusweb_display_calculator_data_in_cart($item_data, $cart_item) {
    
    $fastener_categories = [77, 299, 300, 80, 123];
    $product = $cart_item['data'];
    $product_id = $product->get_id();
    $is_fastener = false;
    foreach ($fastener_categories as $cat_id) {
        if (has_term($cat_id, 'product_cat', $product_id)) {
            $is_fastener = true;
            break;
        }
    }
    
    if (isset($cart_item['custom_area_calc'])) {
        $area_calc = $cart_item['custom_area_calc'];
        $is_leaf_category = $area_calc['is_leaf'];
        $unit_forms = $is_leaf_category ? ['лист', 'листа', 'листов'] : ['упаковка', 'упаковки', 'упаковок'];
        
        $plural = ($area_calc['packs'] % 10 === 1 && $area_calc['packs'] % 100 !== 11) ? $unit_forms[0] :
                  (($area_calc['packs'] % 10 >= 2 && $area_calc['packs'] % 10 <= 4 && ($area_calc['packs'] % 100 < 10 || $area_calc['packs'] % 100 >= 20)) ? $unit_forms[1] : $unit_forms[2]);
        
        $item_data[] = [
            'name' => '',
            'value' => $area_calc['area'] . ' м² (' . $area_calc['packs'] . ' ' . $plural . ')'
        ];
        
        $item_data[] = [
            'name' => '',
            'value' => 'Стоимость материала: ' . number_format($area_calc['total_price'], 0, '', ' ') . ' ₽'
        ];
        
        if (!$is_fastener && isset($area_calc['painting_service']) && $area_calc['painting_service']) {
            $painting = $area_calc['painting_service'];
            $item_data[] = [
                'name' => '',
                'value' => 'Стоимость покраски: ' . number_format($painting['total_cost'], 0, '', ' ') . ' ₽'
            ];
        }
    }
    
    if (isset($cart_item['custom_dimensions'])) {
        $dims = $cart_item['custom_dimensions'];
        
        $item_data[] = [
            'name' => 'Размеры',
            'value' => $dims['width'] . ' мм × ' . $dims['length'] . ' мм'
        ];
        
        $item_data[] = [
            'name' => '',
            'value' => 'Стоимость материала: ' . number_format($dims['price'], 0, '', ' ') . ' ₽'
        ];
        
        if (!$is_fastener && isset($dims['painting_service']) && $dims['painting_service']) {
            $painting = $dims['painting_service'];
            $item_data[] = [
                'name' => '',
                'value' => 'Стоимость покраски: ' . number_format($painting['total_cost'], 0, '', ' ') . ' ₽'
            ];
        }
    }
    
    if (isset($cart_item['custom_multiplier_calc'])) {
        $mult_calc = $cart_item['custom_multiplier_calc'];
        
        $item_data[] = [
            'name' => 'Параметры',
            'value' => 'Ширина: ' . $mult_calc['width'] . ' мм, Длина: ' . $mult_calc['length'] . ' м'
        ];
        
        if (isset($mult_calc['faska_type'])) {
            $item_data[] = [
                'name' => 'Фаска',
                'value' => $mult_calc['faska_type']
            ];
        }
        
        $item_data[] = [
            'name' => '',
            'value' => 'Стоимость материала: ' . number_format($mult_calc['price'], 0, '', ' ') . ' ₽'
        ];
        
        if (!$is_fastener && isset($mult_calc['painting_service']) && $mult_calc['painting_service']) {
            $painting = $mult_calc['painting_service'];
            $item_data[] = [
                'name' => '',
                'value' => 'Стоимость покраски: ' . number_format($painting['total_cost'], 0, '', ' ') . ' ₽'
            ];
        }
    }
    
    if (isset($cart_item['custom_running_meter_calc'])) {
        $rm_calc = $cart_item['custom_running_meter_calc'];
        
        $display_text = '';
        
        if (isset($rm_calc['shape_label'])) {
            $display_text .= 'Форма сечения: ' . $rm_calc['shape_label'];
            
            if ($rm_calc['width'] > 0) {
                $display_text .= '<br>Ширина ' . $rm_calc['width'] . ' мм';
            }
            if (isset($rm_calc['height']) && $rm_calc['height'] > 0) {
                $display_text .= ', Высота ' . $rm_calc['height'] . ' мм';
            }
            if (isset($rm_calc['height2']) && $rm_calc['height2'] > 0) {
                $display_text .= ', Высота 2: ' . $rm_calc['height2'] . ' мм';
            }
            $display_text .= ', Длина ' . $rm_calc['length'] . ' м';
        } else {
            if ($rm_calc['width'] > 0) {
                $display_text .= 'Ширина: ' . $rm_calc['width'] . ' мм, ';
            }
            $display_text .= 'Длина: ' . $rm_calc['length'] . ' м';
        }
        
        $item_data[] = [
            'name' => '',
            'value' => $display_text
        ];
        
        if (isset($rm_calc['faska_type'])) {
            $item_data[] = [
                'name' => 'Фаска',
                'value' => $rm_calc['faska_type']
            ];
        }
        
        $item_data[] = [
            'name' => '',
            'value' => 'Стоимость материала: ' . number_format($rm_calc['price'], 0, '', ' ') . ' ₽'
        ];
        
        if (!$is_fastener && isset($rm_calc['painting_service']) && $rm_calc['painting_service']) {
            $painting = $rm_calc['painting_service'];
            $item_data[] = [
                'name' => '',
                'value' => 'Стоимость покраски: ' . number_format($painting['total_cost'], 0, '', ' ') . ' ₽'
            ];
        }
    }
    
    if (isset($cart_item['custom_square_meter_calc'])) {
        $sq_calc = $cart_item['custom_square_meter_calc'];
        
        $item_data[] = [
            'name' => 'Параметры',
            'value' => 'Ширина: ' . $sq_calc['width'] . ' мм, Длина: ' . $sq_calc['length'] . ' м'
        ];
        
        $item_data[] = [
            'name' => '',
            'value' => 'Стоимость материала: ' . number_format($sq_calc['price'], 0, '', ' ') . ' ₽'
        ];
        
        if (!$is_fastener && isset($sq_calc['painting_service']) && $sq_calc['painting_service']) {
            $painting = $sq_calc['painting_service'];
            $item_data[] = [
                'name' => '',
                'value' => 'Стоимость покраски: ' . number_format($painting['total_cost'], 0, '', ' ') . ' ₽'
            ];
        }
    }
    
    if (isset($cart_item['custom_partition_slat_calc'])) {
        $part_calc = $cart_item['custom_partition_slat_calc'];
        
        $item_data[] = [
            'name' => 'Параметры',
            'value' => 'Ширина: ' . $part_calc['width'] . ' мм, Длина: ' . $part_calc['length'] . ' м, Толщина: ' . $part_calc['thickness'] . ' мм'
        ];
        
        $item_data[] = [
            'name' => '',
            'value' => 'Стоимость материала: ' . number_format($part_calc['total_price'], 0, '', ' ') . ' ₽'
        ];
        
        if (!$is_fastener && isset($part_calc['painting_service']) && $part_calc['painting_service']) {
            $painting = $part_calc['painting_service'];
            $item_data[] = [
                'name' => '',
                'value' => 'Стоимость покраски: ' . number_format($painting['total_cost'], 0, '', ' ') . ' ₽'
            ];
        }
    }
    
    if (isset($cart_item['card_pack_purchase'])) {
        $pack_data = $cart_item['card_pack_purchase'];
        
        $display_text = 'Площадь: ' . $pack_data['area'] . ' м²<br>';
        $display_text .= 'Стоимость материала: ' . number_format($pack_data['total_price'], 0, '', ' ') . ' ₽';
        
        if (!$is_fastener && isset($pack_data['painting_service']) && $pack_data['painting_service']) {
            $painting = $pack_data['painting_service'];
            $display_text .= '<br>Стоимость покраски: ' . number_format($painting['total_cost'], 0, '', ' ') . ' ₽';
        }
        
        $item_data[] = [
            'name' => 'В корзине ' . $pack_data['unit_type'],
            'value' => $display_text
        ];
    }
    
    if (isset($cart_item['standard_pack_purchase'])) {
        $pack_data = $cart_item['standard_pack_purchase'];
        
        $display_text = 'Площадь: ' . $pack_data['area'] . ' м²<br>';
        $display_text .= 'Стоимость материала: ' . number_format($pack_data['total_price'], 0, '', ' ') . ' ₽';
        
        if (!$is_fastener && isset($pack_data['painting_service']) && $pack_data['painting_service']) {
            $painting = $pack_data['painting_service'];
            $display_text .= '<br>Стоимость покраски: ' . number_format($painting['total_cost'], 0, '', ' ') . ' ₽';
        }
        
        $item_data[] = [
            'name' => 'В корзине ' . $pack_data['unit_type'],
            'value' => $display_text
        ];
    }
    
    return $item_data;
}

add_filter('woocommerce_cart_item_price', 'parusweb_format_cart_item_price', 10, 3);

function parusweb_format_cart_item_price($price, $cart_item, $cart_item_key) {
    $product = $cart_item['data'];
    $product_id = $product->get_id();
    
    if (!is_in_target_categories($product_id)) {
        return $price;
    }
    
    if (isset($cart_item['card_pack_purchase']) || 
        isset($cart_item['custom_area_calc']) || 
        isset($cart_item['custom_dimensions']) ||
        isset($cart_item['custom_multiplier_calc']) ||
        isset($cart_item['custom_running_meter_calc']) ||
        isset($cart_item['custom_square_meter_calc']) ||
        isset($cart_item['custom_partition_slat_calc'])) {
        
        $current_price = floatval($product->get_price());
        $base_price_m2 = floatval($product->get_regular_price() ?: $product->get_price());
        
        $leaf_parent_id = 190;
        $leaf_children = [191, 127, 94];
        $leaf_ids = array_merge([$leaf_parent_id], $leaf_children);
        $is_leaf_category = has_term($leaf_ids, 'product_cat', $product_id);
        $unit_text = $is_leaf_category ? 'лист' : 'упаковку';
        
        return wc_price($current_price) . ' за ' . $unit_text . '<br>' .
               '<small style="color: #666;">' . wc_price($base_price_m2) . ' за м²</small>';
    }
    
    return $price;
}

add_filter('woocommerce_cart_item_subtotal', 'parusweb_format_cart_item_subtotal', 10, 3);

function parusweb_format_cart_item_subtotal($subtotal, $cart_item, $cart_item_key) {
    $product = $cart_item['data'];
    $product_id = $product->get_id();
    
    if (!is_in_target_categories($product_id)) {
        return $subtotal;
    }
    
    if (isset($cart_item['card_pack_purchase']) || 
        isset($cart_item['custom_area_calc']) || 
        isset($cart_item['custom_dimensions']) ||
        isset($cart_item['custom_multiplier_calc']) ||
        isset($cart_item['custom_running_meter_calc']) ||
        isset($cart_item['custom_square_meter_calc']) ||
        isset($cart_item['custom_partition_slat_calc'])) {
        
        $quantity = $cart_item['quantity'];
        $total = floatval($product->get_price()) * $quantity;
        
        $leaf_parent_id = 190;
        $leaf_children = [191, 127, 94];
        $leaf_ids = array_merge([$leaf_parent_id], $leaf_children);
        $is_leaf_category = has_term($leaf_ids, 'product_cat', $product_id);
        $unit_forms = $is_leaf_category ? ['лист', 'листа', 'листов'] : ['упаковка', 'упаковки', 'упаковок'];
        
        $plural = parusweb_get_russian_plural($quantity, $unit_forms);
        
        return '<strong>' . wc_price($total) . '</strong><br>' .
               '<small style="color: #666;">' . $quantity . ' ' . $plural . '</small>';
    }
    
    return $subtotal;
}

add_filter('woocommerce_widget_cart_item_quantity', 'parusweb_format_mini_cart_quantity', 10, 3);

function parusweb_format_mini_cart_quantity($quantity, $cart_item, $cart_item_key) {
    $product = $cart_item['data'];
    $product_id = $product->get_id();
    
    if (!is_in_target_categories($product_id)) {
        return $quantity;
    }
    
    if (isset($cart_item['card_pack_purchase']) || 
        isset($cart_item['custom_area_calc']) || 
        isset($cart_item['custom_dimensions']) ||
        isset($cart_item['custom_multiplier_calc']) ||
        isset($cart_item['custom_running_meter_calc']) ||
        isset($cart_item['custom_square_meter_calc']) ||
        isset($cart_item['custom_partition_slat_calc'])) {
        
        $qty = $cart_item['quantity'];
        $current_price = floatval($product->get_price());
        
        $leaf_parent_id = 190;
        $leaf_children = [191, 127, 94];
        $leaf_ids = array_merge([$leaf_parent_id], $leaf_children);
        $is_leaf_category = has_term($leaf_ids, 'product_cat', $product_id);
        $unit_forms = $is_leaf_category ? ['лист', 'листа', 'листов'] : ['упаковка', 'упаковки', 'упаковок'];
        
        $plural = parusweb_get_russian_plural($qty, $unit_forms);
        
        return '<span class="quantity">' . $qty . ' ' . $plural . ' × ' . wc_price($current_price) . '</span>';
    }
    
    return $quantity;
}

add_filter('woocommerce_get_item_data', 'parusweb_remove_price_from_service_name', 9999, 2);

function parusweb_remove_price_from_service_name($item_data, $cart_item) {
    foreach ($item_data as $key => &$data) {
        if (isset($data['value']) && is_string($data['value'])) {
            $data['value'] = preg_replace('/\s*—\s*[\d\s,.]+₽/', '', $data['value']);
            $data['value'] = preg_replace('/\s*\([\d\s,.]+м²\s*×\s*[\d\s,.]+₽\/м²\)/', '', $data['value']);
        }
        
        if (isset($data['name']) && trim($data['name']) === '' && isset($data['value']) && trim($data['value']) === '') {
            unset($item_data[$key]);
        }
    }
    
    return array_values($item_data);
}

add_filter('woocommerce_variation_option_name', 'parusweb_remove_empty_variation_name', 10);

function parusweb_remove_empty_variation_name($name) {
    $name = trim($name);
    if ($name === '' || $name === ':') {
        return '';
    }
    return $name;
}

add_filter('woocommerce_dropdown_variation_attribute_options_html', 'parusweb_clean_variation_dropdown', 200, 2);

function parusweb_clean_variation_dropdown($html, $args) {
    $html = preg_replace('/<option[^>]*value="[^"]*"[^>]*>\s*:\s*<\/option>/', '', $html);
    return $html;
}

add_filter('gettext', 'parusweb_replace_subtotal_text', 999, 3);

function parusweb_replace_subtotal_text($translated, $text, $domain) {
    if ($domain === 'woocommerce' && ($text === 'Subtotal' || $text === 'Подытог')) {
        return 'Стоимость';
    }
    return $translated;
}

function parusweb_get_russian_plural($number, $forms) {
    $cases = array(2, 0, 1, 1, 1, 2);
    return $forms[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}