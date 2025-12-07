<?php
/**
 * ============================================================================
 * МОДУЛЬ: КАЛЬКУЛЯТОР КУБОМЕТРОВ → УПАКОВКА + М²
 * ============================================================================
 * 
 * Калькулятор для пиломатериалов с базовой ценой в кубометрах (м³).
 * Используется для категории 90 (имитация бруса).
 * 
 * Логика:
 * - В АДМИНКЕ: цена задается за 1 м³
 * - НА ФРОНТЕ: выводится цена за упаковку и за м² (как у других пиломатериалов)
 * - Извлекаются размеры из названия товара ИЛИ из атрибутов WooCommerce
 * 
 * @package ParusWeb_Functions
 * @subpackage Display
 * @version 1.3.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// ПРОВЕРКА КАТЕГОРИИ
// ============================================================================

/**
 * Проверка - относится ли товар к категории с расчетом по кубометрам
 * Категории : пм и листовые
 */
if (!function_exists('is_cubic_meter_category')) {
    function is_cubic_meter_category($product_id) {
    // Все категории пиломатериалов и листовых для расчета в кубометрах
    $cubic_categories = [87, 88, 89, 90, 91, 92, 93] . [190, 191, 127, 94];
    return has_term($cubic_categories, 'product_cat', $product_id);
    }
}

// ============================================================================
// ИЗВЛЕЧЕНИЕ ДАННЫХ ИЗ ТОВАРА
// ============================================================================

/**
 * Извлечь параметры для расчета кубометров из атрибутов WooCommerce
 * 
 * @param int $product_id ID товара
 * @return array|false Массив с параметрами или false
 */
function extract_cubic_params_from_attributes($product_id) {
    $product = wc_get_product($product_id);
    if (!$product) return false;
    
    $result = [
        'width' => 0,
        'thickness' => 0,
        'length' => 0,
        'qty_in_pack' => 1,
    ];
    
    // Получаем атрибуты - пробуем ВСЕ варианты
    $shirina = null;
    $tolshhina = null;
    $dlina = null;
    
    // ВАРИАНТ 1: Через get_attribute (без префикса)
    $shirina = $product->get_attribute('shirina');
    $tolshhina = $product->get_attribute('tolshina');
    $dlina = $product->get_attribute('dlina');
    
    // ВАРИАНТ 2: Если не нашли - пробуем с префиксом pa_
    if (!$shirina) {
        $shirina = $product->get_attribute('pa_shirina');
    }
    if (!$tolshhina) {
        $tolshhina = $product->get_attribute('pa_tolshhina');
    }
    if (!$dlina) {
        $dlina = $product->get_attribute('pa_dlina');
    }
    
    // ВАРИАНТ 3: Через wp_get_post_terms (без префикса)
    if (!$shirina) {
        $terms = wp_get_post_terms($product_id, 'shirina');
        if (!empty($terms) && !is_wp_error($terms)) {
            $shirina = $terms[0]->name;
        }
    }
    
    if (!$tolshhina) {
        $terms = wp_get_post_terms($product_id, 'tolshina');
        if (!empty($terms) && !is_wp_error($terms)) {
            $tolshhina = $terms[0]->name;
        }
    }
    
    if (!$dlina) {
        $terms = wp_get_post_terms($product_id, 'dlina');
        if (!empty($terms) && !is_wp_error($terms)) {
            $dlina = $terms[0]->name;
        }
    }
    
    // ВАРИАНТ 4: Через wp_get_post_terms (с префиксом pa_)
    if (!$shirina) {
        $terms = wp_get_post_terms($product_id, 'pa_shirina');
        if (!empty($terms) && !is_wp_error($terms)) {
            $shirina = $terms[0]->name;
        }
    }
    
    if (!$tolshhina) {
        $terms = wp_get_post_terms($product_id, 'pa_tolshhina');
        if (!empty($terms) && !is_wp_error($terms)) {
            $tolshhina = $terms[0]->name;
        }
    }
    
    if (!$dlina) {
        $terms = wp_get_post_terms($product_id, 'pa_dlina');
        if (!empty($terms) && !is_wp_error($terms)) {
            $dlina = $terms[0]->name;
        }
    }
    
    // ВАРИАНТ 5: Через get_attributes() и прямой доступ
    if (!$shirina || !$tolshhina || !$dlina) {
        $attributes = $product->get_attributes();
        foreach ($attributes as $attr_name => $attribute) {
            $name_clean = str_replace('pa_', '', $attr_name);
            
            if (($name_clean === 'shirina' || $attr_name === 'shirina') && !$shirina) {
                if ($attribute->is_taxonomy()) {
                    $terms = wp_get_post_terms($product_id, $attr_name);
                    if (!empty($terms) && !is_wp_error($terms)) {
                        $shirina = $terms[0]->name;
                    }
                } else {
                    $shirina = $attribute->get_options()[0] ?? null;
                }
            }
            
            if (($name_clean === 'tolshhina' || $attr_name === 'tolshhina') && !$tolshhina) {
                if ($attribute->is_taxonomy()) {
                    $terms = wp_get_post_terms($product_id, $attr_name);
                    if (!empty($terms) && !is_wp_error($terms)) {
                        $tolshhina = $terms[0]->name;
                    }
                } else {
                    $tolshhina = $attribute->get_options()[0] ?? null;
                }
            }
            
            if (($name_clean === 'dlina' || $attr_name === 'dlina') && !$dlina) {
                if ($attribute->is_taxonomy()) {
                    $terms = wp_get_post_terms($product_id, $attr_name);
                    if (!empty($terms) && !is_wp_error($terms)) {
                        $dlina = $terms[0]->name;
                    }
                } else {
                    $dlina = $attribute->get_options()[0] ?? null;
                }
            }
        }
    }
    
    // Извлекаем числовые значения из полученных строк
    if ($shirina) {
        preg_match('/(\d+(?:[.,]\d+)?)/', $shirina, $match);
        if (!empty($match[1])) {
            $result['width'] = floatval(str_replace(',', '.', $match[1]));
        }
    }
    
    if ($tolshhina) {
        preg_match('/(\d+(?:[.,]\d+)?)/', $tolshhina, $match);
        if (!empty($match[1])) {
            $result['thickness'] = floatval(str_replace(',', '.', $match[1]));
        }
    }
    
    if ($dlina) {
        preg_match('/(\d+(?:[.,]\d+)?)/', $dlina, $match);
        if (!empty($match[1])) {
            $length_value = floatval(str_replace(',', '.', $match[1]));
            
            // Определяем единицы измерения
            if (preg_match('/м|m/ui', $dlina) || $length_value < 50) {
                // Длина в метрах - конвертируем в мм
                $result['length'] = $length_value * 1000;
            } else {
                // Длина уже в мм
                $result['length'] = $length_value;
            }
        }
    }
    
    // Извлекаем количество из названия товара
    $title = $product->get_name();
    if (preg_match('/(\d+)\s*шт/ui', $title, $match)) {
        $result['qty_in_pack'] = intval($match[1]);
    }
    
    // Проверяем что все параметры извлечены
    if ($result['width'] > 0 && $result['thickness'] > 0 && $result['length'] > 0) {
        return $result;
    }
    
    return false;
}

/**
 * Извлечь параметры для расчета кубометров из названия товара
 * 
 * Формат названия: "Имитация бруса 140×20×6000 мм, 10 шт/упак"
 * Извлекаемые данные:
 * - width (ширина) в мм
 * - thickness (толщина) в мм
 * - length (длина) в мм
 * - qty_in_pack (количество в упаковке)
 * 
 * @param string $title Название товара
 * @return array|false Массив с параметрами или false
 */
function extract_cubic_params_from_title($title) {
    // Очищаем строку
    $title = mb_strtolower($title, 'UTF-8');
    $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $title = str_replace("\xC2\xA0", ' ', $title); // Неразрывный пробел
    
    $result = [
        'width' => 0,
        'thickness' => 0,
        'length' => 0,
        'qty_in_pack' => 1,
    ];
    
    // Паттерн 1: "145(140)/18" - специальный формат
    // 145(140) = ширина, 18 = толщина
    if (preg_match('/(\d+)\s*\(\s*\d+\s*\)\s*\/\s*(\d+)/u', $title, $matches)) {
        $result['width'] = intval($matches[1]);
        $result['thickness'] = intval($matches[2]);
    }
    // Паттерн 2: "140×20×6000" или "140x20x6000" (три размера)
    elseif (preg_match('/(\d+)\s*[×xх*]\s*(\d+)\s*[×xх*]\s*(\d+)/ui', $title, $matches)) {
        $dim1 = intval($matches[1]);
        $dim2 = intval($matches[2]);
        $dim3 = intval($matches[3]);
        
        // Сортируем размеры: наибольший, средний, наименьший
        $sizes = [$dim1, $dim2, $dim3];
        rsort($sizes);
        
        // Определяем какой размер что означает:
        if ($sizes[0] > 1000) {
            // Первый размер - длина в мм
            $result['length'] = $sizes[0];
            $result['width'] = $sizes[1];
            $result['thickness'] = $sizes[2];
        } else {
            $result['width'] = $sizes[1];
            $result['thickness'] = $sizes[2];
            $result['length'] = $sizes[0];
        }
    }
    // Паттерн 3: "140×20" (два размера)
    elseif (preg_match('/(\d+)\s*[×xх*]\s*(\d+)(?!\d)/ui', $title, $matches)) {
        $dim1 = intval($matches[1]);
        $dim2 = intval($matches[2]);
        
        if ($dim1 > $dim2) {
            $result['width'] = $dim1;
            $result['thickness'] = $dim2;
        } else {
            $result['width'] = $dim2;
            $result['thickness'] = $dim1;
        }
    }
    
    // Извлекаем длину из текста (если не извлечена ранее)
    if ($result['length'] == 0) {
        // "3 метра", "3 м", "3м"
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:метра|метров|м\b)/ui', $title, $matches)) {
            $length_m = floatval(str_replace(',', '.', $matches[1]));
            $result['length'] = $length_m * 1000;
        }
        // "6000", "6000мм"
        elseif (preg_match('/(\d{4,5})\s*(?:мм)?/ui', $title, $matches)) {
            $result['length'] = intval($matches[1]);
        }
    }
    
    // Извлекаем количество в упаковке
    if (preg_match('/(\d+)\s*шт/ui', $title, $matches)) {
        $result['qty_in_pack'] = intval($matches[1]);
    }
    
    // Валидация: все размеры должны быть больше 0
    if ($result['width'] <= 0 || $result['thickness'] <= 0 || $result['length'] <= 0) {
        return false;
    }
    
    return $result;
}

/**
 * Получить параметры товара для расчета кубометров
 * 
 * ПРИОРИТЕТ:
 * 1. Извлечение из названия товара
 * 2. Извлечение из атрибутов WooCommerce (shirina, tolshina, dlina)
 * 
 * @param int $product_id ID товара
 * @return array|false Параметры или false
 */
function get_cubic_product_params($product_id) {
    $product = wc_get_product($product_id);
    if (!$product) return false;
    
    $title = $product->get_name();
    
    // ПРИОРИТЕТ 1: Пытаемся извлечь из названия
    $params = extract_cubic_params_from_title($title);
    
    if ($params !== false) {
        return $params;
    }
    
    // ПРИОРИТЕТ 2: Если не получилось из названия - берем из атрибутов
    $params = extract_cubic_params_from_attributes($product_id);
    
    if ($params !== false) {
        return $params;
    }
    
    return false;
}

// ============================================================================
// РАСЧЕТНЫЕ ФУНКЦИИ
// ============================================================================

/**
 * Рассчитать объем одной доски в кубометрах
 * 
 * @param array $params Параметры: width, thickness, length (все в мм)
 * @return float Объем в м³
 */
function calculate_board_volume($params) {
    $width_m = $params['width'] / 1000;
    $thickness_m = $params['thickness'] / 1000;
    $length_m = $params['length'] / 1000;
    
    return $width_m * $thickness_m * $length_m;
}

/**
 * Рассчитать площадь одной доски в м²
 * 
 * @param array $params Параметры: width, length (в мм)
 * @return float Площадь в м²
 */
function calculate_board_area($params) {
    $width_m = $params['width'] / 1000;
    $length_m = $params['length'] / 1000;
    
    return $width_m * $length_m;
}

/**
 * Рассчитать цену за м² исходя из цены за м³ и толщины
 * 
 * @param float $price_per_m3 Цена за кубометр
 * @param int $thickness_mm Толщина в мм
 * @return float Цена за м²
 */
function calculate_price_per_m2_from_m3($price_per_m3, $thickness_mm) {
    $thickness_m = $thickness_mm / 1000;
    return $price_per_m3 * $thickness_m;
}

/**
 * Рассчитать полную цену упаковки
 * 
 * @param int $product_id ID товара
 * @param float $base_price_per_m3 Базовая цена за м³
 * @return array|false Массив с расчетами или false
 */
function calculate_cubic_package_price($product_id, $base_price_per_m3) {
    $params = get_cubic_product_params($product_id);
    
    if (!$params) {
        return false;
    }
    
    // Объем одной доски
    $volume_per_piece = calculate_board_volume($params);
    
    // Площадь одной доски
    $area_per_piece = calculate_board_area($params);
    
    // Цена за одну доску (через объем)
    $price_per_piece = $base_price_per_m3 * $volume_per_piece;
    
    // Цена за упаковку
    $price_per_pack = $price_per_piece * $params['qty_in_pack'];
    
    // Цена за м² (для вывода)
    $price_per_m2 = calculate_price_per_m2_from_m3($base_price_per_m3, $params['thickness']);
    
    // Общая площадь упаковки в м²
    $total_area = $area_per_piece * $params['qty_in_pack'];
    
    return [
        'params' => $params,
        'volume_per_piece' => $volume_per_piece,
        'area_per_piece' => $area_per_piece,
        'price_per_piece' => $price_per_piece,
        'price_per_pack' => $price_per_pack,
        'price_per_m2' => $price_per_m2,
        'total_area' => $total_area,
        'base_price_per_m3' => $base_price_per_m3,
    ];
}

// ============================================================================
// ФУНКЦИИ ФОРМАТИРОВАНИЯ ЦЕНЫ
// ============================================================================

/**
 * Отформатировать цену для категории с кубометрами
 * ВЫВОД: за упаковку + за м² (БЕЗ м³ на фронте!)
 * 
 * @param int $product_id ID товара
 * @param float $base_price_per_m3 Базовая цена за м³ (из админки)
 * @return string HTML с отформатированной ценой
 */
function format_cubic_meter_price($product_id, $base_price_per_m3) {
    $calc = calculate_cubic_package_price($product_id, $base_price_per_m3);
    
    if (!$calc) {
        $price_per_m2 = $base_price_per_m3 * 0.02;
        return '<span style="font-size:1.1em;"><strong>' . wc_price($price_per_m2) . '</strong> за м²</span>';
    }
    
    $qty = $calc['params']['qty_in_pack'];
    
    // ========================================================================
    // ПРОВЕРЯЕМ: есть ли у товара калькулятор размеров (calc_settings)
    // ========================================================================
    $has_calculator = (
        get_post_meta($product_id, '_calc_width_min', true) || 
        get_post_meta($product_id, '_calc_length_min', true)
    );
    
    // ========================================================================
    // ВАРИАНТ 1: ТОВАР С КАЛЬКУЛЯТОРОМ РАЗМЕРОВ
    // Показываем: "462 ₽ за шт. (от 0.060 м²)" + "(7700 ₽ за м²)"
    // ========================================================================
    if ($has_calculator) {
        // Получаем минимальные размеры из calc_settings
        $min_width = floatval(get_post_meta($product_id, '_calc_width_min', true));
        $min_length = floatval(get_post_meta($product_id, '_calc_length_min', true));
        
        // Если нет calc_settings, используем размеры из параметров товара
        if (!$min_width) {
            $min_width = $calc['params']['width'];
        }
        if (!$min_length) {
            $min_length = 0.5; // 0.5м по умолчанию
        }
        
        // Площадь минимального размера в м²
        $min_area = ($min_width / 1000) * $min_length;
        
        // Цена за минимальный размер
        $min_price = $calc['price_per_m2'] * $min_area;
        
        if (is_product()) {
            return sprintf(
                '<span style="font-size:1.3em;"><strong>%s</strong> за шт. (от %s м²)</span><br>' .
                '<span style="font-size:0.9em !important; color:#666;">(%s за м²)</span>',
                wc_price($min_price),
                number_format($min_area, 3),
                wc_price($calc['price_per_m2'])
            );
        } else {
            return sprintf(
                '<span style="font-size:1.1em;"><strong>%s</strong> шт.</span><br>' .
                '<span style="font-size:0.85em !important; color:#666;">(%s за м²)</span>',
                wc_price($min_price),
                wc_price($calc['price_per_m2'])
            );
        }
    }
    
    // ========================================================================
    // ВАРИАНТ 2: ТОВАР-УПАКОВКА БЕЗ КАЛЬКУЛЯТОРА
    // Показываем: "11306 ₽ за упаковку (8 шт)" + "(3249 ₽ за м²)"
    // ========================================================================
    else {
        if (is_product()) {
            return sprintf(
                '<span style="font-size:1.3em;"><strong>%s</strong> за упаковку (%d шт)</span><br>' .
                '<span style="font-size:0.9em !important; color:#666;">(%s за м²)</span>',
                wc_price($calc['price_per_pack']),
                $qty,
                wc_price($calc['price_per_m2'])
            );
        } else {
            return sprintf(
                '<span style="font-size:1.1em;"><strong>%s</strong> за упак.</span><span style="font-size:0.9em;clear:both">(%d шт)</span><br>' .
                '<span style="font-size:0.85em !important; color:#666;">(%s за м²)</span>',
                wc_price($calc['price_per_pack']),
                $qty,
                wc_price($calc['price_per_m2'])
            );
        }
    }
}

// ============================================================================
// МИНИМАЛЬНАЯ ЦЕНА (для сортировки и фильтров)
// ============================================================================

/**
 * Рассчитать минимальную цену для товаров категории 90
 * Используется в фильтрах и сортировке WooCommerce
 * 
 * @param int $product_id ID товара
 * @param float $base_price_per_m3 Базовая цена за м³
 * @return float Минимальная цена (цена упаковки)
 */
function calculate_cubic_min_price($product_id, $base_price_per_m3) {
    $calc = calculate_cubic_package_price($product_id, $base_price_per_m3);
    
    if (!$calc) {
        // Если не удалось рассчитать, возвращаем базовую цену
        return $base_price_per_m3;
    }
    
    // Возвращаем цену упаковки как минимальную
    return $calc['price_per_pack'];
}

// ============================================================================
// ВЫВОД ДИАГНОСТИЧЕСКОЙ ИНФОРМАЦИИ (ДЛЯ ТЕСТОВ)
// ============================================================================

/**
 * Вывести информацию о расчетах для тестирования
 * УПРОЩЕННАЯ ВЕРСИЯ
 * 
 * @param int $product_id ID товара
 */
function display_cubic_calculation_debug($product_id) {
    $product = wc_get_product($product_id);
    if (!$product) return;
    
    $base_price = floatval($product->get_regular_price() ?: $product->get_price());
    $title = $product->get_name();
    
    $params_from_title = extract_cubic_params_from_title($title);
    $params_from_attrs = extract_cubic_params_from_attributes($product_id);
    $calc = calculate_cubic_package_price($product_id, $base_price);
    
    echo '<div style="background: #f5f5f5; border: 1px solid #ddd; padding: 15px; margin: 15px 0; font-family: monospace; font-size: 12px;">';
    echo '<strong>ДИАГНОСТИКА (Категория 90)</strong><br><br>';
    
    echo '<strong>Название:</strong> ' . esc_html($title) . '<br><br>';
    
    echo '<strong>Из НАЗВАНИЯ:</strong> ';
    if ($params_from_title) {
        echo 'OK - ' . $params_from_title['width'] . 'x' . $params_from_title['thickness'] . 'x' . $params_from_title['length'] . ' мм, ' . $params_from_title['qty_in_pack'] . ' шт';
    } else {
        echo 'НЕТ';
    }
    echo '<br><br>';
    
    echo '<strong>Из АТРИБУТОВ:</strong> ';
    if ($params_from_attrs) {
        echo 'OK - ' . $params_from_attrs['width'] . 'x' . $params_from_attrs['thickness'] . 'x' . $params_from_attrs['length'] . ' мм, ' . $params_from_attrs['qty_in_pack'] . ' шт';
    } else {
        echo 'НЕТ';
    }
    echo '<br><br>';
    
    if (!$calc) {
        echo '<strong>ОШИБКА:</strong> Не удалось рассчитать. Проверьте название и атрибуты товара.';
        echo '</div>';
        return;
    }
    
    echo '<strong>ИСПОЛЬЗОВАНО:</strong> ' . $calc['params']['width'] . 'x' . $calc['params']['thickness'] . 'x' . $calc['params']['length'] . ' мм, ' . $calc['params']['qty_in_pack'] . ' шт<br><br>';
    
    echo '<strong>Базовая цена:</strong> ' . wc_price($calc['base_price_per_m3']) . ' за м³<br>';
    echo '<strong>Объем 1 доски:</strong> ' . number_format($calc['volume_per_piece'], 6, '.', '') . ' м³<br>';
    echo '<strong>Площадь упаковки:</strong> ' . number_format($calc['total_area'], 2, '.', '') . ' м²<br><br>';
    
    echo '<strong>РЕЗУЛЬТАТ:</strong><br>';
    echo 'Цена упаковки: ' . wc_price($calc['price_per_pack']) . ' (' . $calc['params']['qty_in_pack'] . ' шт)<br>';
    echo 'Цена за м²: ' . wc_price($calc['price_per_m2']);
    
    echo '</div>';
}

/**
 * Хук для вывода диагностики на странице товара
 */
function parusweb_display_cubic_debug() {
    if (!is_product()) return;
    
    global $product;
    $product_id = $product->get_id();
    
    if (!is_cubic_meter_category($product_id)) return;
    
    display_cubic_calculation_debug($product_id);
}
// Раскомментировать для показа диагностики:
// add_action('woocommerce_before_add_to_cart_form', 'parusweb_display_cubic_debug', 5);

// ============================================================================
// КОНЕЦ МОДУЛЯ
// ============================================================================