<?php
/**
 * ============================================================================
 * МОДУЛЬ: ФОРМАТИРОВАНИЕ ЦЕН
 * ============================================================================
 * 
 * Изменение отображения цен в зависимости от типа товара.
 * 
 * @package ParusWeb_Functions
 * @subpackage Display
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// ФИЛЬТР ОТОБРАЖЕНИЯ ЦЕН
// ============================================================================

add_filter('woocommerce_get_price_html', 'parusweb_format_product_price', 20, 2);

function parusweb_format_product_price($price, $product) {
    $product_id = $product->get_id();
    
    // Сначала проверяем категорию ДПК и МПК (197)
    if (!function_exists('has_term_or_parent_dpk')) {
        function has_term_or_parent_dpk($parent_term_id, $taxonomy, $product_id) {
            if (has_term($parent_term_id, $taxonomy, $product_id)) {
                return true;
            }
            $terms = wp_get_post_terms($product_id, $taxonomy, array('fields' => 'ids'));
            if (is_wp_error($terms) || empty($terms)) {
                return false;
            }
            foreach ($terms as $term_id) {
                if (term_is_ancestor_of($parent_term_id, $term_id, $taxonomy)) {
                    return true;
                }
            }
            return false;
        }
    }
    
    $is_dpk_mpk = has_term_or_parent_dpk(197, 'product_cat', $product_id);
    if ($is_dpk_mpk) {
        return format_dpk_mpk_price($product, $product_id);
    }
    
    // Проверяем целевые категории
    if (!is_in_target_categories($product_id)) {
        return $price;
    }
    
    $base_price_m2 = floatval($product->get_regular_price() ?: $product->get_price());
    $type = parusweb_get_product_type($product_id);
    
    // Категории для скрытия базовой цены
    $hide_base_price_categories = range(265, 271);
    $should_hide_base_price = has_term($hide_base_price_categories, 'product_cat', $product_id);
    
    // Проверка на пиломатериалы (категории 87-93, 310)
$timber_categories = array_merge(
    [87, 310], 
    range(88, 93),
    [190, 191, 127, 94]  // ← ДОБАВИТЬ
);
$is_timber = has_term($timber_categories, 'product_cat', $product_id);

if ($is_timber && function_exists('format_cubic_meter_price')) {
    return format_cubic_meter_price($product_id, $base_price_m2); 
}
    
    // Форматирование в зависимости от типа
    switch ($type) {
         case 'cubic_meter_pack':
        return format_cubic_meter_price($product_id, $base_price_m2);
        
        case 'partition_slat':
            return format_partition_slat_price($product_id, $base_price_m2, $should_hide_base_price);
            
        case 'running_meter':
            return format_running_meter_price($product_id, $base_price_m2, $should_hide_base_price);
            
        case 'square_meter':
            return format_square_meter_price($product_id, $base_price_m2, $should_hide_base_price);
            
        case 'multiplier':
            return format_multiplier_price($product_id, $base_price_m2, $should_hide_base_price);
            
        case 'target':
            return format_target_price($product, $product_id, $base_price_m2);
            
        case 'liter':
            return format_liter_price($price);
            
        default:
            return $price;
    }
}

// ============================================================================
// ФОРМАТИРОВАНИЕ ПО ТИПАМ
// ============================================================================

function format_dpk_mpk_price($product, $product_id) {
    $price_per_m2 = floatval($product->get_regular_price() ?: $product->get_price());
    
    // Получаем площадь одной доски из названия
    $title = $product->get_name();
    $board_area = extract_area_with_qty($title, $product_id);
    
    if (!$board_area || $board_area <= 0) {
        // Если не удалось извлечь площадь, показываем просто цену за м²
        if (is_product()) {
            return '<span style="font-size:1.3em;"><strong>' . wc_price($price_per_m2) . '</strong> за м²</span>';
        } else {
            return '<span style="font-size:1.1em;"><strong>' . wc_price($price_per_m2) . '</strong> за м²</span>';
        }
    }
    
    // Рассчитываем цену за штуку
    $price_per_piece = $price_per_m2 * $board_area;
    
    if (is_product()) {
        // На странице товара: показываем цену за штуку крупно + цену за м² мелко
        return '<span style="font-size:1.3em;"><strong>' . wc_price($price_per_piece) . '</strong> за шт.</span><br>' .
               '<span style="font-size:0.9em !important; color:#666;">(' . wc_price($price_per_m2) . ' за м²)</span>';
    } else {
        // В архиве/категории: компактный вариант
        return '<span style="font-size:1.1em;"><strong>' . wc_price($price_per_piece) . '</strong> за шт.</span><br>' .
               '<span style="font-size:0.85em !important; color:#666;">(' . wc_price($price_per_m2) . ' за м²)</span>';
    }
}

function format_timber_price($product, $product_id, $base_price_m2) {
    $title = $product->get_name();
    $current_price = floatval($product->get_price());
    
    // ПРОВЕРЯЕМ: есть ли площадь упаковки в названии
    $pack_area = extract_area_with_qty($title, $product_id);
    
    // ПРОВЕРЯЕМ: есть ли настройки калькулятора размеров
    $has_calculator = (
        get_post_meta($product_id, '_calc_width_min', true) || 
        get_post_meta($product_id, '_calc_length_min', true)
    );
    
    // ========================================================================
    // ВАРИАНТ 1: ТОВАР С УПАКОВКОЙ (есть pack_area, нет калькулятора)
    // Показываем: "XXX ₽ за м²" + "YYY ₽ за упаковку"
    // ========================================================================
    if ($pack_area && !$has_calculator) {
        // Извлекаем размеры для расчета базовой цены за м³
        $thickness = 0;
        $width = 0;
        $length = 0;
        $pieces_in_pack = 1;
        
        // Парсим размеры: "120(114)/15" или "120×15×4000"
        if (preg_match('/(\d+)\s*(?:\(\d+\))?\s*[\/]\s*(\d+)/ui', $title, $matches)) {
            $width = floatval($matches[1]);
            $thickness = floatval($matches[2]);
        } elseif (preg_match('/(\d+)\s*[×\*хx]\s*(\d+)\s*[×\*хx]\s*(\d+)/ui', $title, $matches)) {
            $dims = array_map('floatval', [$matches[1], $matches[2], $matches[3]]);
            sort($dims);
            $thickness = $dims[0];
            $width = $dims[1];
            $length = $dims[2];
        }
        
        // Длина из названия: "4 МЕТРА"
        if (!$length && preg_match('/(\d+(?:\.\d+)?)\s*(?:метра|метров|м\b)/ui', $title, $matches)) {
            $length = floatval($matches[1]) * 1000;
        }
        
        // Количество штук: "10 ШТ"
        if (preg_match('/(\d+)\s*шт/ui', $title, $matches)) {
            $pieces_in_pack = intval($matches[1]);
        }
        
        // Fallback значения
        if (!$thickness) $thickness = 20;
        if (!$width) $width = 120;
        if (!$length) $length = 3000;
        
        // Объем 1 доски
        $volume_per_piece = ($thickness / 1000) * ($width / 1000) * ($length / 1000);
        $pack_volume = $volume_per_piece * $pieces_in_pack;
        
        // Базовая цена за м³
        if ($pack_volume > 0) {
            $base_price_cubic = $current_price / $pack_volume;
        } else {
            $base_price_cubic = $current_price;
        }
        
        // Цена за м²
        $base_price_per_m2 = $base_price_cubic * ($thickness / 1000);
        
        // Цена за упаковку (уже есть в $current_price)
        $price_per_pack = $current_price;
        
        if (is_product()) {
            return wc_price($base_price_per_m2) . '<span style="font-size:1.3em; font-weight:600">&nbsp;за м<sup>2</sup></span><br>' .
                   '<span style="font-size:1.3em;"><strong>' . wc_price($price_per_pack) . '</strong> за упаковку</span>';
        } else {
            return wc_price($base_price_per_m2) . '<span style="font-size:0.9em; font-weight:600">&nbsp;за м<sup>2</sup></span><br>' .
                   '<span style="font-size:1.1em;"><strong>' . wc_price($price_per_pack) . '</strong> за упаковку</span>';
        }
    }
    
    // ========================================================================
    // ВАРИАНТ 2: ТОВАР С КАЛЬКУЛЯТОРОМ РАЗМЕРОВ (нет pack_area, есть калькулятор)
    // Показываем: "XXX ₽ за м²" + "YYY ₽ за шт. (от Z м²)"
    // ========================================================================
    else {
        // Извлекаем размеры
        $thickness = 0;
        $width = 0;
        $length = 0;
        
        if (preg_match('/(\d+)\s*(?:\(\d+\))?\s*[\/]\s*(\d+)/ui', $title, $matches)) {
            $width = floatval($matches[1]);
            $thickness = floatval($matches[2]);
        } elseif (preg_match('/(\d+)\s*[×\*хx]\s*(\d+)\s*[×\*хx]\s*(\d+)/ui', $title, $matches)) {
            $dims = array_map('floatval', [$matches[1], $matches[2], $matches[3]]);
            sort($dims);
            $thickness = $dims[0];
            $width = $dims[1];
            $length = $dims[2];
        }
        
        if (!$length && preg_match('/(\d+(?:\.\d+)?)\s*(?:метра|метров|м\b)/ui', $title, $matches)) {
            $length = floatval($matches[1]) * 1000;
        }
        
        if (!$thickness) $thickness = 20;
        if (!$width) $width = 120;
        if (!$length) $length = 3000;
        
        // Объем 1 доски
        $volume_per_piece = ($thickness / 1000) * ($width / 1000) * ($length / 1000);
        
        // Базовая цена за м³
        if ($volume_per_piece > 0) {
            $base_price_cubic = $current_price / $volume_per_piece;
        } else {
            $base_price_cubic = $current_price;
        }
        
        // Цена за м²
        $base_price_per_m2 = $base_price_cubic * ($thickness / 1000);
        
        // Минимальная цена из calc_settings или размеров товара
        $min_width = floatval(get_post_meta($product_id, '_calc_width_min', true));
        $min_length = floatval(get_post_meta($product_id, '_calc_length_min', true));
        
        if (!$min_width) $min_width = $width;
        if (!$min_length) $min_length = 0.5;
        
        $min_area = ($min_width / 1000) * $min_length;
        $min_price = $base_price_per_m2 * $min_area;
        
        if (is_product()) {
            return wc_price($base_price_per_m2) . '<span style="font-size:1.3em; font-weight:600">&nbsp;за м<sup>2</sup></span><br>' .
                   '<span style="font-size:1.1em;">' . wc_price($min_price) . ' за шт. (от ' . number_format($min_area, 3) . ' м²)</span>';
        } else {
            return wc_price($base_price_per_m2) . '<span style="font-size:0.9em; font-weight:600">&nbsp;за м<sup>2</sup></span><br>' .
                   '<span style="font-size:0.85em;">' . wc_price($min_price) . ' шт.</span>';
        }
    }
}

function format_partition_slat_price($product_id, $base_price_per_m2, $hide_base) {
    $min_price = calculate_min_price_partition_slat($product_id, $base_price_per_m2);
    
    if (is_product()) {
        if ($hide_base) {
            return '<span style="font-size:1.1em;">' . wc_price($min_price) . ' за шт.</span>';
        }
        return wc_price($base_price_per_m2) . '<span style="font-size:1.3em; font-weight:600">&nbsp;за м<sup>2</sup></span><br>' .
               '<span style="font-size:1.1em;">' . wc_price($min_price) . ' за шт.</span>';
    } else {
        if ($hide_base) {
            return '<span style="font-size:0.85em;">' . wc_price($min_price) . ' шт.</span>';
        }
        return wc_price($base_price_per_m2) . '<span style="font-size:0.9em; font-weight:600">&nbsp;за м<sup>2</sup></span><br>' .
               '<span style="font-size:0.85em;">' . wc_price($min_price) . ' шт.</span>';
    }
}

function format_running_meter_price($product_id, $base_price_per_m, $hide_base) {
    $min_price = calculate_min_price_running_meter($product_id, $base_price_per_m);
    $min_length = floatval(get_post_meta($product_id, '_calc_length_min', true)) ?: 1;
    
    if (is_product()) {
        if ($hide_base) {
            return '<span style="font-size:1.1em;">' . wc_price($min_price) . ' за шт.</span>';
        }
        return wc_price($base_price_per_m) . '<span style="font-size:1.3em; font-weight:600">&nbsp;за м<sup>2</sup></span><br>' .
               '<span style="font-size:1.1em;">' . wc_price($min_price) . ' за шт.</span>';
    } else {
        if ($hide_base) {
            return '<span style="font-size:0.85em;">' . wc_price($min_price) . ' шт.</span>';
        }
        return wc_price($base_price_per_m) . '<span style="font-size:0.9em; font-weight:600">&nbsp;за м<sup>2</sup></span><br>' .
               '<span style="font-size:0.85em;">' . wc_price($min_price) . ' шт.</span>';
    }
}

function format_square_meter_price($product_id, $base_price_per_m2, $hide_base) {
    $min_price = calculate_min_price_square_meter($product_id, $base_price_per_m2);
    $min_area = ($min_price / $base_price_per_m2) / get_price_multiplier($product_id);
    
    if (is_product()) {
        if ($hide_base) {
            return '<span style="font-size:1.1em;">' . wc_price($min_price) . ' за шт. (' . number_format($min_area, 2) . ' м²)</span>';
        }
        return wc_price($base_price_per_m2) . '<span style="font-size:1.3em; font-weight:600">&nbsp;за м<sup>2</sup></span><br>' .
               '<span style="font-size:1.1em;">' . wc_price($min_price) . ' за шт. (' . number_format($min_area, 2) . ' м²)</span>';
    } else {
        if ($hide_base) {
            return '<span style="font-size:0.85em;">' . wc_price($min_price) . ' шт.</span>';
        }
        return wc_price($base_price_per_m2) . '<span style="font-size:0.9em; font-weight:600">&nbsp;за м<sup>2</sup></span><br>' .
               '<span style="font-size:0.85em;">' . wc_price($min_price) . ' шт.</span>';
    }
}

function format_multiplier_price($product_id, $base_price_per_m2, $hide_base) {
    $min_price = calculate_min_price_multiplier($product_id, $base_price_per_m2);
    $multiplier = get_price_multiplier($product_id);
    $min_area = ($min_price / $base_price_per_m2) / $multiplier;
    
    if (is_product()) {
        if ($hide_base) {
            return '<span style="font-size:1.1em;">' . wc_price($min_price) . ' за шт. (' . number_format($min_area, 3) . ' м²)</span>';
        }
        return wc_price($base_price_per_m2) . '<span style="font-size:1.3em; font-weight:600">&nbsp;за м<sup>2</sup></span><br>' .
               '<span style="font-size:1.1em;">' . wc_price($min_price) . ' за шт. (' . number_format($min_area, 3) . ' м²)</span>';
    } else {
        if ($hide_base) {
            return '<span style="font-size:0.85em;">' . wc_price($min_price) . ' шт.</span>';
        }
        return wc_price($base_price_per_m2) . '<span style="font-size:0.9em; font-weight:600">&nbsp;за м<sup>2</sup></span><br>' .
               '<span style="font-size:0.85em;">' . wc_price($min_price) . ' шт.</span>';
    }
}

function format_target_price($product, $product_id, $base_price_m2) {
    $pack_area = extract_area_with_qty($product->get_name(), $product_id);
    $is_leaf = is_leaf_category($product_id);
    
    if (!$pack_area) {
        return wc_price($base_price_m2) . '<span style="font-size:0.9em; font-weight:600">&nbsp;за м<sup>2</sup></span>';
    }
    
    $price_per_pack = $base_price_m2 * $pack_area;
    $unit_text = $is_leaf ? 'лист' : 'упаковку';
    
    if (is_product()) {
        return wc_price($base_price_m2) . '<span style="font-size:1.3em; font-weight:600">&nbsp;за м<sup>2</sup></span><br>' .
               '<span style="font-size:1.3em;"><strong>' . wc_price($price_per_pack) . '</strong> за 1 ' . $unit_text . '</span>';
    } else {
        return wc_price($base_price_m2) . '<span style="font-size:0.9em; font-weight:600">&nbsp;за м<sup>2</sup></span><br>' .
               '<span style="font-size:1.1em;"><strong>' . wc_price($price_per_pack) . '</strong> за ' . $unit_text . '</span>';
    }
}

function format_liter_price($price) {
    if (strpos($price, 'за литр') !== false) {
        return $price;
    }
    
    if (preg_match('/(.*)<\/span>(.*)$/i', $price, $matches)) {
        return $matches[1] . '/литр</span>' . $matches[2];
    }
    
    return $price . ' за литр';
}
// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================