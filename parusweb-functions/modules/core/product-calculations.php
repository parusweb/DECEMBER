<?php
if (!defined('ABSPATH')) exit;

//
// Универсальный набор разделителей размеров
//
define('DIM_SYM', '\/×\*xхX');


//
// =============================
// EXTRACT AREA
// =============================
//
function extract_area_with_qty($title, $product_id = null) {
    
    // Нормализуем строку
    $t = mb_strtolower($title, 'UTF-8');
    
    // ========================================================================
    // ПРИОРИТЕТ 0A: Формат "4,48 М2/УПАК" или "4.48 М2/УПАК"
    // САМЫЙ ВЫСОКИЙ ПРИОРИТЕТ!
    // ========================================================================
    if (preg_match('/(\d+[.,]\d+)\s*м(?:2|²)\s*\/\s*упак/u', $t, $matches)) {
        $num = str_replace(',', '.', $matches[1]);
        return floatval($num);
    }
    
    // ========================================================================
    // ПРИОРИТЕТ 0B: Формат "СУП-4,56М2" или "СУП-4.56М2"
    // ========================================================================
    if (preg_match('/суп\s*[:-]?\s*(\d+(?:[.,-]\d+)?)\s*м(?:2|²)/u', $t, $matches)) {
        $num = str_replace([',', '-'], '.', $matches[1]);
        return floatval($num);
    }

    // ПРИОРИТЕТ 1: Формат 140/28 ... 3 метра
    if (preg_match('/(\d{2,3})[' . DIM_SYM . '](\d{1,2}).*?(\d+(?:[.,]\d+)?)\s*метр/u', $t, $m)) {
        $width_mm     = intval($m[1]);
        $thickness_mm = intval($m[2]);
        $length_m     = floatval(str_replace(',', '.', $m[3]));
        return round(($width_mm / 1000) * $length_m, 3);
    }

    // Формат 115(110)/14 ... 3 метра
    if (preg_match('/(\d{2,3})\(\d+\)[' . DIM_SYM . '](\d{1,2}).*?(\d+(?:[.,]\d+)?)\s*метр/u', $t, $m)) {
        $width_mm     = intval($m[1]);
        $thickness_mm = intval($m[2]);
        $length_m     = floatval(str_replace(',', '.', $m[3]));
        return round(($width_mm / 1000) * $length_m, 3);
    }

    // ПРИОРИТЕТ 2: Прямая площадь S = 2.24 м2
    if (preg_match('/\bS?\s*[:=]?\s*(\d+[.,]\d+)\s*м2/u', $t, $m)) {
        return floatval(str_replace(',', '.', $m[1]));
    }

    // ПРИОРИТЕТ 3: ACF
    if ($product_id && function_exists('get_field')) {
        $w = get_field('pa_shirina',  $product_id);
        $l = get_field('pa_dlina',    $product_id);
        if ($w && $l) {
            preg_match('/(\d+[.,]?\d*)/', $w, $wm);
            preg_match('/(\d+[.,]?\d*)/', $l, $lm);
            if (!empty($wm[1]) && !empty($lm[1])) {
                $width_mm  = floatval(str_replace(',', '.', $wm[1]));
                $length_mm = floatval(str_replace(',', '.', $lm[1]));
                if ($length_mm < 50) $length_mm *= 1000;
                return round(($width_mm/1000)*($length_mm/1000), 3);
            }
        }
    }

    // ПРИОРИТЕТ 4: Атрибуты WC
    if ($product_id) {
        $product = wc_get_product($product_id);
        if ($product) {
            $w = $product->get_attribute('pa_shirina');
            $l = $product->get_attribute('pa_dlina');
            preg_match('/(\d+[.,]?\d*)/', $w, $wm);
            preg_match('/(\d+[.,]?\d*)/', $l, $lm);
            if (!empty($wm[1]) && !empty($lm[1])) {
                $width_mm  = floatval(str_replace(',', '.', $wm[1]));
                $length_mm = floatval(str_replace(',', '.', $lm[1]));
                if ($length_mm < 50) $length_mm *= 1000;
                return round(($width_mm/1000)*($length_mm/1000), 3);
            }
        }
    }

    // ПРИОРИТЕТ 5: WooCommerce shipping (Д×Ш×В)
    if ($product_id) {
        $product = wc_get_product($product_id);
        if ($product) {
            $width_cm  = floatval($product->get_width());
            $length_cm = floatval($product->get_length());
            if ($width_cm > 0 && $length_cm > 0) {
                $width_m  = $width_cm  / 100;
                $length_m = $length_cm / 100;
                return round($width_m * $length_m, 3);
            }
        }
    }

    // ПРИОРИТЕТ 6: Старые 3-х мерные
    if (preg_match('/(\d+(?:[.,]\d+)?)\s*[' . DIM_SYM . ']\s*(\d+(?:[.,]\d+)?)\s*[' . DIM_SYM . ']\s*(\d+(?:[.,]\d+)?)/u', $t, $m)) {
        $dims = [floatval(str_replace(',', '.', $m[1])),
                 floatval(str_replace(',', '.', $m[2])),
                 floatval(str_replace(',', '.', $m[3]))];
        sort($dims);
        $width_mm  = $dims[1];
        $length_mm = $dims[2];
        if ($length_mm < 50) $length_mm *= 1000;
        return round(($width_mm/1000)*($length_mm/1000), 3);
    }

    return null;
}



//
// =============================
// EXTRACT DIMENSIONS
// =============================
//
function extract_dimensions_from_title($title, $product_id = null) {

    // ПРИОРИТЕТ 1: 140/28 ... 3 метра
    if (preg_match('/(\d{2,3})[' . DIM_SYM . '](\d{1,2}).*?(\d+(?:[.,]\d+)?)\s*метр/ui', $title, $m)) {

        return [
            'width'     => intval($m[1]),
            'thickness' => intval($m[2]),
            'length'    => floatval(str_replace(',', '.', $m[3])) * 1000
        ];
    }

    // 115(110)/14 ... 3 метра
    if (preg_match('/(\d{2,3})\(\d+\)[' . DIM_SYM . '](\d{1,2}).*?(\d+(?:[.,]\d+)?)\s*метр/ui', $title, $m)) {

        return [
            'width'     => intval($m[1]),
            'thickness' => intval($m[2]),
            'length'    => floatval(str_replace(',', '.', $m[3])) * 1000
        ];
    }

    // ПРИОРИТЕТ 2: ACF
    if ($product_id && function_exists('get_field')) {

        $w = get_field('pa_shirina',   $product_id);
        $l = get_field('pa_dlina',     $product_id);
        $t = get_field('pa_tolshhina', $product_id);

        if ($w && $l && $t) {

            preg_match('/(\d+[.,]?\d*)/', $w, $wm);
            preg_match('/(\d+[.,]?\d*)/', $l, $lm);
            preg_match('/(\d+[.,]?\d*)/', $t, $tm);

            if (!empty($wm[1]) && !empty($lm[1]) && !empty($tm[1])) {

                $width_mm  = floatval(str_replace(',', '.', $wm[1]));
                $length_mm = floatval(str_replace(',', '.', $lm[1]));
                $thickness = floatval(str_replace(',', '.', $tm[1]));

                if ($length_mm < 50) $length_mm *= 1000;

                return [
                    'width'     => $width_mm,
                    'thickness' => $thickness,
                    'length'    => $length_mm
                ];
            }
        }
    }

    // ПРИОРИТЕТ 3: WC атрибуты
    if ($product_id) {

        $product = wc_get_product($product_id);
        if ($product) {

            $w = $product->get_attribute('pa_shirina');
            $l = $product->get_attribute('pa_dlina');
            $t = $product->get_attribute('pa_tolshhina');

            preg_match('/(\d+[.,]?\d*)/', $w, $wm);
            preg_match('/(\d+[.,]?\d*)/', $l, $lm);
            preg_match('/(\d+[.,]?\d*)/', $t, $tm);

            if (!empty($wm[1]) && !empty($lm[1]) && !empty($tm[1])) {

                $width_mm  = floatval($wm[1]);
                $length_mm = floatval($lm[1]);
                $thickness = floatval($tm[1]);

                if ($length_mm < 50) $length_mm *= 1000;

                return [
                    'width'     => $width_mm,
                    'thickness' => $thickness,
                    'length'    => $length_mm
                ];
            }
        }
    }

    // ПРИОРИТЕТ 4: WooCommerce shipping
    if ($product_id) {

        $product = wc_get_product($product_id);
        if ($product) {

            $width_cm  = floatval($product->get_width());
            $height_cm = floatval($product->get_height());
            $length_cm = floatval($product->get_length());

            if ($width_cm > 0 && $height_cm > 0 && $length_cm > 0) {

                return [
                    'width'     => $width_cm  * 10,   // cm → mm
                    'thickness' => $height_cm * 10,
                    'length'    => $length_cm * 10
                ];
            }
        }
    }

    return null;
}



//
// =============================
// PRICE MULTIPLIER
// =============================
//
function get_price_multiplier($product_id) {

    $product_multiplier = get_post_meta($product_id, '_price_multiplier', true);
    if (is_numeric($product_multiplier)) {
        return floatval($product_multiplier);
    }

    $cats = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
    if (!is_wp_error($cats)) {
        foreach ($cats as $cat) {
            $cat_m = get_term_meta($cat, 'category_price_multiplier', true);
            if (is_numeric($cat_m)) {
                return floatval($cat_m);
            }
        }
    }

    return 1.0;
}
