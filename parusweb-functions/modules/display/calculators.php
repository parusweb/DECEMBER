<?php
/**
 * ============================================================================
 * МОДУЛЬ: ОТОБРАЖЕНИЕ КАЛЬКУЛЯТОРОВ
 * ============================================================================
 * 
 * Вывод всех типов калькуляторов на странице товара:
 * - Калькулятор площади
 * - Калькулятор размеров
 * - Калькулятор с множителем
 * - Калькулятор погонных метров
 * - Калькулятор квадратных метров
 * - Калькулятор фальшбалок
 * - Калькулятор категории 271
 * 
 * @package ParusWeb_Functions
 * @subpackage Display
 * @version 2.0.3
 */

if (!defined('ABSPATH')) exit;

add_action('woocommerce_after_add_to_cart_button', 'parusweb_render_calculators');

if (!function_exists('has_term_or_parent')) {
    function has_term_or_parent($parent_term_id, $taxonomy, $product_id) {
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

function parusweb_render_calculators() {
    if (!is_product()) return;
    
    global $product;
    $product_id = $product->get_id();
    
    $is_target = is_in_target_categories($product_id);
    $is_multiplier = is_in_multiplier_categories($product_id);
    $is_square_meter = is_square_meter_category($product_id);
    $is_running_meter = is_running_meter_category($product_id);
    $is_partition_slat = is_partition_slat_category($product_id);
    $is_category_271 = has_term(271, 'product_cat', $product_id);

    if (!function_exists('has_term_or_parent_197')) {
        function has_term_or_parent_197($parent_term_id, $taxonomy, $product_id) {
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
    
    $is_dpk_mpk = has_term_or_parent_197(197, 'product_cat', $product_id);
    if ($is_dpk_mpk && !$is_target) {
        $is_target = true;
    }

    // УДАЛЕНА НЕПРАВИЛЬНАЯ ЛОГИКА (строки 83-86 из старого файла)
    // Теперь проверка категории происходит внутри каждого блока калькулятора

    $has_calc_settings = (get_post_meta($product_id, '_calc_width_min', true) && get_post_meta($product_id, '_calc_length_min', true));
    if (!$is_target && !$is_multiplier && !$has_calc_settings) {
        return;
    }
    
    $title = $product->get_name();
    $pack_area = extract_area_with_qty($title, $product_id);
    $dims = null;
    if (!$pack_area) {
        $dims = extract_dimensions_from_title($title, $product_id);
    }
    $painting_services = get_available_painting_services_by_material($product_id);
    $price_multiplier = get_price_multiplier($product_id);
    
    if ($is_dpk_mpk) {
        $painting_services = array();
    }
    
    $is_dpk_for_js = $is_dpk_mpk;
    
    $calc_settings = null;
    if ($is_multiplier || (get_post_meta($product_id, '_calc_width_min', true) && get_post_meta($product_id, '_calc_length_min', true))) {
        $calc_settings = [
            'width_min' => floatval(get_post_meta($product_id, '_calc_width_min', true)),
            'width_max' => floatval(get_post_meta($product_id, '_calc_width_max', true)),
            'width_step' => floatval(get_post_meta($product_id, '_calc_width_step', true)) ?: 100,
            'length_min' => floatval(get_post_meta($product_id, '_calc_length_min', true)),
            'length_max' => floatval(get_post_meta($product_id, '_calc_length_max', true)),
            'length_step' => floatval(get_post_meta($product_id, '_calc_length_step', true)) ?: 0.01,
        ];
        
        $has_fastener_calc = false;
        $fastener_config = get_post_meta($product_id, '_fastener_config', true);
        if (!empty($fastener_config['enabled'])) {
            $has_fastener_calc = true;
        }

        if ($calc_settings['width_min'] == 0 && $calc_settings['width_max'] == 0) {
            $attr_shirina = $product->get_attribute('shirina');
            if ($attr_shirina) {
                $shirina_value = floatval(preg_replace('/[^0-9.]/', '', $attr_shirina));
                if ($shirina_value > 0) {
                    $calc_settings['width_min'] = $shirina_value;
                    $calc_settings['width_max'] = $shirina_value;
                    $calc_settings['width_step'] = 1;
                }
            }
            
            if ($calc_settings['width_min'] == 0) {
                $wc_width = floatval($product->get_width());
                if ($wc_width > 0) {
                    $calc_settings['width_min'] = $wc_width * 10;
                    $calc_settings['width_max'] = $wc_width * 10;
                    $calc_settings['width_step'] = 1;
                }
            }
        }

        if ($calc_settings['length_min'] == 0 && $calc_settings['length_max'] == 0) {
            $attr_dlina = $product->get_attribute('dlina');
            if ($attr_dlina) {
                $dlina_value = floatval(preg_replace('/[^0-9.]/', '', $attr_dlina));
                if ($dlina_value > 0) {
                    if ($dlina_value > 100) {
                        $dlina_value = $dlina_value / 1000;
                    }
                    $calc_settings['length_min'] = $dlina_value;
                    $calc_settings['length_max'] = $dlina_value;
                    $calc_settings['length_step'] = 0.01;
                }
            }
            
            if ($calc_settings['length_min'] == 0) {
                $wc_length = floatval($product->get_length());
                if ($wc_length > 0) {
                    $calc_settings['length_min'] = $wc_length / 100;
                    $calc_settings['length_max'] = $wc_length / 100;
                    $calc_settings['length_step'] = 0.01;
                }
            }
        }
    }
    
    $leaf_ids = array_merge([190], [191, 127, 94]);
    $is_leaf_category = has_term($leaf_ids, 'product_cat', $product_id);
    $unit_text = $is_leaf_category ? 'лист' : 'упаковку';
    $unit_forms = $is_leaf_category ? ['лист', 'листа', 'листов'] : ['упаковка', 'упаковки', 'упаковок'];
    
    $category_271_settings = null;
    if ($is_category_271) {
        $term_id = 271;
        $category_271_settings = [
            'width_min' => floatval(get_term_meta($term_id, 'category_271_width_min', true)),
            'width_max' => floatval(get_term_meta($term_id, 'category_271_width_max', true)),
            'width_step' => floatval(get_term_meta($term_id, 'category_271_width_step', true)) ?: 10,
            'length' => 3.0,
            'thickness' => 40
        ];
        
        $product_width_min = floatval(get_post_meta($product_id, '_calc_width_min', true));
        if ($product_width_min > 0) {
            $category_271_settings['width_min'] = $product_width_min;
            $category_271_settings['width_max'] = floatval(get_post_meta($product_id, '_calc_width_max', true));
            $category_271_settings['width_step'] = floatval(get_post_meta($product_id, '_calc_width_step', true)) ?: 10;
        }
    }
    
    $show_falsebalk_calculator = false;
    $shapes_data = array();
    
    if ($is_square_meter || $is_running_meter) {
        $is_falsebalk = has_term(266, 'product_cat', $product_id);
        if ($is_falsebalk) {
            $shapes_data = get_post_meta($product_id, '_falsebalk_shapes_data', true);
            if (!is_array($shapes_data)) {
                $shapes_data = array();
            }
            
            foreach ($shapes_data as $shape_key => $shape_info) {
                if (is_array($shape_info) && !empty($shape_info['enabled'])) {
                    $has_width = !empty($shape_info['width_min']) || !empty($shape_info['width_max']);
                    $has_height = !empty($shape_info['height_min']) || !empty($shape_info['height_max']);
                    $has_length = !empty($shape_info['length_min']) || !empty($shape_info['length_max']);
                    $has_old_format = !empty($shape_info['widths']) || !empty($shape_info['heights']) || !empty($shape_info['lengths']);
                    
                    if ($has_width || $has_height || $has_length || $has_old_format) {
                        $show_falsebalk_calculator = true;
                        break;
                    }
                }
            }
        }
    }
    
    ?>
    <script>
const isSquareMeter = <?php echo $is_square_meter ? 'true' : 'false'; ?>;
const isRunningMeter = <?php echo $is_running_meter ? 'true' : 'false'; ?>;
const isCategory271 = <?php echo $is_category_271 ? 'true' : 'false'; ?>;
const category271Settings = <?php echo $category_271_settings ? json_encode($category_271_settings) : 'null'; ?>;
const paintingServices = <?php echo json_encode($painting_services); ?>;
const priceMultiplier = <?php echo $price_multiplier; ?>;
const isMultiplierCategory = <?php echo $is_multiplier ? 'true' : 'false'; ?>;
const calcSettings = <?php echo $calc_settings ? json_encode($calc_settings) : 'null'; ?>;
const hasFastenerCalc = <?php echo isset($has_fastener_calc) && $has_fastener_calc ? 'true' : 'false'; ?>;
const fastenerConfig = <?php echo isset($fastener_config) && !empty($fastener_config) ? json_encode($fastener_config) : 'null'; ?>;
const showFalsebalkaCalculator = <?php echo $show_falsebalk_calculator ? 'true' : 'false'; ?>;
const shapesData = <?php echo !empty($shapes_data) ? json_encode($shapes_data) : '{}'; ?>;

let form = null;
let quantityInput = null;
let isAutoUpdate = false;
let paintingBlock = null;

function getRussianPlural(n, forms) {
    n = Math.abs(n);
    n %= 100;
    if (n > 10 && n < 20) return forms[2];
    n %= 10;
    if (n === 1) return forms[0];
    if (n >= 2 && n <= 4) return forms[1];
    return forms[2];
}

function removeHiddenFields(prefix) {
    if (!form) return;
    const fields = form.querySelectorAll(`input[name^="${prefix}"]`);
    fields.forEach(field => field.remove());
}

function createHiddenField(name, value) {
    if (!form) return null;
    let field = form.querySelector(`input[name="${name}"]`);
    if (!field) {
        field = document.createElement('input');
        field.type = 'hidden';
        field.name = name;
        form.appendChild(field);
    }
    field.value = value;
    return field;
}

function addHiddenField(name, value) {
    return createHiddenField(name, value);
}

let currentPaintingServiceCost = 0;
let currentPaintingServiceArea = 0;

function updatePaintingServiceCost(totalArea = null) {
    const paintingBlockEl = document.getElementById('painting-services-block');
    if (!paintingBlockEl) return 0;
    
    const serviceSelect = document.getElementById('painting_service_select');
    if (!serviceSelect) return 0;
    
    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
    const paintingResult = document.getElementById('painting-service-result');
    
    if (!selectedOption || !selectedOption.value) {
        if (paintingResult) paintingResult.innerHTML = '';
        removeHiddenFields('painting_service_');
        currentPaintingServiceCost = 0;
        currentPaintingServiceArea = 0;
        return 0;
    }
    
    const serviceKey = selectedOption.value;
    const servicePrice = parseFloat(selectedOption.dataset.price);
    
    if (!totalArea) {
        currentPaintingServiceArea = 0;
        if (paintingResult) paintingResult.innerHTML = `Выбрана услуга: ${paintingServices[serviceKey].name}`;
        return 0;
    }
    
    currentPaintingServiceArea = totalArea;
    const totalPaintingCost = totalArea * servicePrice;
    currentPaintingServiceCost = totalPaintingCost;
    
    if (paintingResult) {
        paintingResult.innerHTML = `${paintingServices[serviceKey].name}: ${totalPaintingCost.toFixed(2)} ₽ (${totalArea.toFixed(3)} м² × ${servicePrice} ₽/м²)`;
    }
    
    createHiddenField('painting_service_key', serviceKey);
    createHiddenField('painting_service_name', paintingServices[serviceKey].name);
    createHiddenField('painting_service_price_per_m2', servicePrice);
    createHiddenField('painting_service_area', totalArea.toFixed(3));
    createHiddenField('painting_service_total_cost', totalPaintingCost.toFixed(2));
    
    return totalPaintingCost;
}

function getPaintingServiceData() {
    const paintingBlockEl = document.getElementById('painting-services-block');
    if (!paintingBlockEl) return null;
    
    const serviceSelect = document.getElementById('painting_service_select');
    if (!serviceSelect) return null;
    
    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
    if (!selectedOption || !selectedOption.value) return null;
    
    const serviceKey = selectedOption.value;
    const service = paintingServices[serviceKey];
    if (!service) return null;
    
    const pricePerSqm = service.price;
    const totalCost = pricePerSqm * currentPaintingServiceArea;
    
    return {
        id: serviceKey,
        name: service.name,
        name_with_color: service.name,
        price_per_sqm: pricePerSqm,
        area: currentPaintingServiceArea,
        total_cost: totalCost
    };
}

document.addEventListener('DOMContentLoaded', function() {
    form = document.querySelector('form.cart') || 
           document.querySelector('form[action*="add-to-cart"]') ||
           document.querySelector('.single_add_to_cart_button').closest('form');
    quantityInput = document.querySelector('input[name="quantity"]') ||
                   document.querySelector('.qty') ||
                   document.querySelector('.input-text.qty');
    
    if (!form) return;

    const resultBlock = document.createElement('div');
    resultBlock.id = 'custom-calc-block';
    resultBlock.className = 'calc-result-container';
    resultBlock.style.marginTop = '20px';
    resultBlock.style.marginBottom = '20px';
    form.insertAdjacentElement('afterend', resultBlock);

    const productCatIds = <?php echo json_encode(wp_get_post_terms($product->get_id(), 'product_cat', array('fields'=>'ids'))); ?>;
    if (productCatIds.includes(273)) {
        const shapePrices = {
            round: <?php echo floatval(get_post_meta($product->get_id(), '_shape_price_round', true) ?: 0); ?>,
            triangle: <?php echo floatval(get_post_meta($product->get_id(), '_shape_price_triangle', true) ?: 0); ?>,
            flat: <?php echo floatval(get_post_meta($product->get_id(), '_shape_price_flat', true) ?: 0); ?>
        };

        let selectedShape = 'round';

        const shapeBlock = document.createElement('div');
        shapeBlock.id = 'shtaketnik-shape-icons';
        shapeBlock.style.marginTop = '15px';
        
        shapeBlock.innerHTML = `
        <p style="margin-bottom: 10px; font-weight: 600;">Форма верхнего спила:</p>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <label class="shape-option">
                <input type="radio" name="shape_type" value="round" checked style="display:none">
                <svg width="60" height="60" viewBox="0 0 100 100">
                    <path d="M0,50 A50,50 0 0,1 100,50 L100,100 L0,100 Z" fill="#8bc34a"/>
                </svg>
                <div style="font-size:11px">Полукруг</div>
            </label>
            <label class="shape-option">
                <input type="radio" name="shape_type" value="triangle" style="display:none">
                <svg width="60" height="60" viewBox="0 0 100 100">
                    <polygon points="50,0 100,100 0,100" fill="#8bc34a"/>
                </svg>
                <div style="font-size:11px">Треугольник</div>
            </label>
            <label class="shape-option">
                <input type="radio" name="shape_type" value="flat" style="display:none">
                <svg width="60" height="60" viewBox="0 0 100 100">
                    <rect width="100" height="100" fill="#8bc34a"/>
                </svg>
                <div style="font-size:11px">Прямой спил</div>
            </label>
        </div>
        `;
        resultBlock.appendChild(shapeBlock);

        const shapeInputs = shapeBlock.querySelectorAll('input[name="shape_type"]');
        shapeInputs[0].closest('label').classList.add('active-shape');
        
        shapeInputs.forEach(input => {
            const label = input.closest('label');
            label.addEventListener('click', () => {
                shapeInputs.forEach(i => i.closest('label').classList.remove('active-shape'));
                label.classList.add('active-shape');
                input.checked = true;
                selectedShape = input.value;

                createHiddenField('selected_shape_type', selectedShape);
                createHiddenField('selected_shape_price', shapePrices[selectedShape]);

                if (typeof updateMultiplierCalc === 'function') {
                    updateMultiplierCalc();
                }
            });
        });

        const style = document.createElement('style');
        style.textContent = `
            #shtaketnik-shape-icons label {
                border:2px solid #ddd;
                border-radius:8px;
                padding:5px;
                transition:all 0.2s ease;
                cursor:pointer;
                text-align:center;
            }
            #shtaketnik-shape-icons label:hover {
                transform:scale(1.05);
                border-color:#8bc34a;
            }
            #shtaketnik-shape-icons label.active-shape {
                border-color:#8bc34a;
                box-shadow:0 0 6px #8bc34a;
            }
        `;
        document.head.appendChild(style);
    }

    function createPaintingServicesBlock(currentCategoryId) {
        if (Object.keys(paintingServices).length === 0) return null;

        const paintingBlock = document.createElement('div');
        paintingBlock.id = 'painting-services-block';

        let optionsHTML = '<option value="" selected>Без покраски</option>';
        Object.entries(paintingServices).forEach(([key, service]) => {
            let optionText = service.name;
            if (currentCategoryId < 265 || currentCategoryId > 271) {
                optionText += ` (+${service.price} ₽/м²)`;
            }
            optionsHTML += `<option value="${key}" data-price="${service.price}">${optionText}</option>`;
        });

        paintingBlock.innerHTML = `
            <br><h4>Услуги покраски</h4>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 10px;">
                    Выберите услугу покраски:
                    <select id="painting_service_select" style="margin-left: 10px; padding: 5px; width: 100%; background: #fff">
                        ${optionsHTML}
                    </select>
                </label>
                <div id="painting-service-result" style="display:none;"></div>
            </div>
            <div id="paint-schemes-root"></div>
        `;
        return paintingBlock;
    }

    paintingBlock = createPaintingServicesBlock();
<?php 
$actual_pack_price = floatval($product->get_price());
$cubic_categories = [87, 310, 88, 90, 91, 92, 93];
$is_cubic_meter_cat = has_term($cubic_categories, 'product_cat',$product_id);
if ($is_cubic_meter_cat && function_exists('calculate_cubic_package_price')) {
    $cubic_calc = calculate_cubic_package_price($product_id, $actual_pack_price);
    if ($cubic_calc) {
        $actual_pack_price = $cubic_calc['price_per_pack'];
    }
}

// ============================================================================
// КАЛЬКУЛЯТОР ПЛОЩАДИ - для товаров с известной площадью упаковки
// НЕ показывается для пиломатериалов (87-93, 310) с calc_settings или dims
// ============================================================================
$timber_categories_check = array_merge([87, 310], range(88, 93));
$is_timber_check = has_term($timber_categories_check, 'product_cat', $product_id);
$has_timber_calc = ($calc_settings || $dims) && $is_timber_check;
$show_area_calc = ($pack_area && $is_target && !$has_timber_calc);

if($show_area_calc): ?>
    const areaCalc = document.createElement('div');
    areaCalc.id = 'calc-area';
    
    const isDpkMpk = <?php echo isset($is_dpk_for_js) && $is_dpk_for_js ? 'true' : 'false'; ?>;
    const unitTextDisplay = isDpkMpk ? 'штуки' : <?php echo json_encode($unit_text); ?>.replace('упаковку', 'упаковки').replace('лист', 'листа');
    const unitTextSingle = isDpkMpk ? 'шт.' : <?php echo json_encode($unit_text); ?>;
    
    areaCalc.innerHTML = `
        <br><h4>Расчет количества по площади</h4>
        <div style="margin-bottom: 10px;">
            Площадь ${unitTextDisplay}: <strong>${<?php echo $pack_area; ?>.toFixed(3)} м²</strong><br>
            Цена за ${unitTextSingle}: <strong>${(<?php echo $actual_pack_price; ?>).toFixed(2)} ₽</strong>
        </div>
        <label>Введите нужную площадь, м²:
            <input type="number" min="<?php echo $pack_area; ?>" step="0.1" id="calc_area_input" placeholder="1" style="width:100px; margin-left:10px;">
        </label>
        <div id="calc_area_result" style="margin-top:10px;"></div>
    `;
    resultBlock.appendChild(areaCalc);

    if (paintingBlock) {
        areaCalc.appendChild(paintingBlock);
    }

    const areaInput = document.getElementById('calc_area_input');
    const areaResult = document.getElementById('calc_area_result');
    const basePriceM2 = <?php echo floatval($product->get_price()); ?>;
    const packArea = <?php echo $pack_area; ?>;
    const unitForms = isDpkMpk ? ['шт.', 'шт.', 'шт.'] : <?php echo json_encode($unit_forms); ?>;

    function updateAreaCalc() {
        const area = parseFloat(areaInput.value);
        
        if (!area || area <= 0) {
            areaResult.innerHTML = '';
            removeHiddenFields('custom_area_');
            updatePaintingServiceCost(0);
            return;
        }

        const packs = Math.ceil(area / packArea);
        const totalPrice = packs * <?php echo $actual_pack_price; ?>;
        const totalArea = packs * packArea;
        const plural = getRussianPlural(packs, unitForms);
        
        const paintingCost = updatePaintingServiceCost(totalArea);
        const grandTotal = totalPrice + paintingCost;

        let html = `Нужная площадь: <b>${area.toFixed(2)} м²</b><br>`;
        html += `Необходимо: <b>${packs} ${plural}</b><br>`;
        html += `Стоимость материала: <b>${totalPrice.toFixed(2)} ₽</b><br>`;
        if (paintingCost > 0) {
            html += `Стоимость покраски: <b>${paintingCost.toFixed(2)} ₽</b><br>`;
            html += `<strong>Итого с покраской: <b>${grandTotal.toFixed(2)} ₽</b></strong>`;
        } else {
            html += `<strong>Итого: <b>${totalPrice.toFixed(2)} ₽</b></strong>`;
        }
        
        areaResult.innerHTML = html;

        createHiddenField('custom_area_packs', packs);
        createHiddenField('custom_area_area_value', area.toFixed(2));
        createHiddenField('custom_area_total_price', totalPrice.toFixed(2));
        createHiddenField('custom_area_grand_total', grandTotal.toFixed(2));

        if (quantityInput) {
            isAutoUpdate = true;
            quantityInput.value = packs;
            quantityInput.dispatchEvent(new Event('change', { bubbles: true }));
            setTimeout(() => { isAutoUpdate = false; }, 100);
        }
    }
    
    areaInput.addEventListener('input', updateAreaCalc);
    
    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            if (!isAutoUpdate && areaInput.value) {
                areaInput.value = '';
                updateAreaCalc();
            }
        });
        
        quantityInput.addEventListener('change', function() {
            if (!isAutoUpdate) {
                const packs = parseInt(this.value);
                if (packs > 0) {
                    const area = packs * packArea;
                    areaInput.value = area.toFixed(2);
                    updateAreaCalc();
                }
            }
        });
    }
<?php endif; ?>

<?php
// ============================================================================
// КАЛЬКУЛЯТОР РАЗМЕРОВ - для пиломатериалов (кубические метры)
// Категории: 87 (родительская) + 88-93, 310
// Базовая цена: ₽/м³, пересчитывается в ₽/м² с учетом толщины
// ============================================================================
$timber_categories = array_merge([87, 310], range(88, 93));
$is_timber = has_term($timber_categories, 'product_cat', $product_id);

// Показываем только если есть настройки калькулятора ИЛИ извлечены размеры
$show_timber_calc = $is_timber && ($calc_settings || $dims);

if($show_timber_calc): 
    
    // Получаем текущую цену товара (может быть уже пересчитанной за упаковку)
    $current_price = floatval($product->get_price());
    
    // Для пиломатериалов нужна БАЗОВАЯ цена за м³
    // Если в названии есть размеры упаковки, пересчитываем обратно
    $pack_volume = 0;
    
    // Пытаемся извлечь размеры упаковки из названия для обратного пересчета
    // Паттерн: "120x500x3000" или "120*500*3000" (ширина×высота×длина в мм)
    if (preg_match('/(\d+)\s*[×\*хx\/]\s*(\d+)\s*[×\*хx\/]\s*(\d+)/ui', $title, $matches)) {
        $dims_pack = array_map('floatval', [$matches[1], $matches[2], $matches[3]]);
        sort($dims_pack);
        
        $thickness_pack = $dims_pack[0]; // мм
        $width_pack = $dims_pack[1];     // мм
        $length_pack = $dims_pack[2];    // мм
        
        // Объем 1 доски в м³
        $pack_volume = ($thickness_pack / 1000) * ($width_pack / 1000) * ($length_pack / 1000);
    }
    
    // Если нашли объем упаковки, пересчитываем базовую цену за м³
    if ($pack_volume > 0) {
        $base_price_cubic = $current_price / $pack_volume;
    } else {
        // Иначе считаем что цена уже за м³
        $base_price_cubic = $current_price;
    }
    
    $base_price_for_calc = $base_price_cubic;
    
    // Ищем толщину в массиве $dims или атрибутах
    $thickness = 0;
    
    // Вариант 1: Толщина в $dims
    if ($dims && isset($dims['thickness'])) {
        $thickness = floatval($dims['thickness']);
    } elseif ($dims && isset($dims['tolshina'])) {
        $thickness = floatval($dims['tolshina']);
    } elseif ($dims && isset($dims['tolshhina'])) {
        // Внимание: две буквы h - это не опечатка, так называется атрибут pa_tolshhina
        $thickness = floatval($dims['tolshhina']);
    }
    
    // Вариант 2: Толщина в атрибуте товара
    if ($thickness == 0) {
        $attr_thickness = $product->get_attribute('tolshhina');
        if ($attr_thickness) {
            $thickness = floatval(preg_replace('/[^0-9.]/', '', $attr_thickness));
        }
    }
    
    // Вариант 3: Извлекаем толщину из названия товара
    // Паттерн: "20/120" или "20*120" или "20x120" - первое число это толщина
    if ($thickness == 0) {
        $title_lower = mb_strtolower($title, 'UTF-8');
        // Ищем паттерны: 20/120, 20*120, 20x120, 20×120
        if (preg_match('/(\d+)\s*[\/\*x×]\s*(\d+)/u', $title_lower, $matches)) {
            $first_num = floatval($matches[1]);
            $second_num = floatval($matches[2]);
            // Толщина обычно меньшее число
            $thickness = min($first_num, $second_num);
        }
    }
    
    // Вариант 4: Толщина из calc_settings (если там только одно значение)
    if ($thickness == 0 && $calc_settings && $calc_settings['width_min'] == $calc_settings['width_max']) {
        $thickness = $calc_settings['width_min'];
    }
    
    // Пересчитываем цену из ₽/м³ в ₽/м²
    // Формула: цена_за_м² = цена_за_м³ × толщина_в_метрах
    if ($thickness > 0) {
        $thickness_m = $thickness / 1000.0;
        $base_price_for_calc = $base_price_for_calc * $thickness_m;
    }
    
    // Определяем источник данных для селектов
    $use_calc_settings = ($calc_settings && $calc_settings['width_min'] > 0 && $calc_settings['length_min'] > 0);
    
    // Проверяем ширину - одно значение или диапазон
    $width_is_fixed = false;
    if ($use_calc_settings) {
        $width_is_fixed = ($calc_settings['width_min'] == $calc_settings['width_max']);
    } elseif ($dims && isset($dims['widths']) && count($dims['widths']) == 1) {
        $width_is_fixed = true;
    }
?>
    const dimCalc = document.createElement('div');
    dimCalc.id = 'calc-dim';
    
    let dimHTML = '<br><h4>Калькулятор стоимости</h4><div style="display:flex;gap:20px;flex-wrap:wrap;align-items: center;">';
    
    <?php if ($use_calc_settings): ?>
        <?php if ($width_is_fixed): ?>
            // Ширина фиксированная - показываем как текст
            dimHTML += '<div style="padding:8px 12px; background:#f0f4ff; border-radius:4px;">';
            dimHTML += '<span style="color:#666;">Ширина: <strong><?php echo $calc_settings['width_min']; ?> мм</strong></span>';
            dimHTML += '</div>';
            dimHTML += '<input type="hidden" id="custom_width" value="<?php echo $calc_settings['width_min']; ?>">';
        <?php else: ?>
            // Ширина - селект из calc_settings
            dimHTML += '<label>Ширина (мм): <select id="custom_width" style="background:#fff;margin-left:10px;">';
            dimHTML += '<option value="">Выберите...</option>';
            <?php for($w = $calc_settings['width_min']; $w <= $calc_settings['width_max']; $w += $calc_settings['width_step']): ?>
                dimHTML += '<option value="<?php echo $w; ?>"><?php echo $w; ?></option>';
            <?php endfor; ?>
            dimHTML += '</select></label>';
        <?php endif; ?>
        
        // Длина из calc_settings (в метрах)
        dimHTML += '<label>Длина (м): <select id="custom_length" style="background:#fff;margin-left:10px;">';
        dimHTML += '<option value="">Выберите...</option>';
        <?php 
        for($l = $calc_settings['length_min']; $l <= $calc_settings['length_max']; $l += $calc_settings['length_step']): 
        ?>
            dimHTML += '<option value="<?php echo number_format($l, 2, '.', ''); ?>"><?php echo number_format($l, 2); ?></option>';
        <?php endfor; ?>
        dimHTML += '</select></label>';
        
    <?php else: ?>
        <?php if ($width_is_fixed): ?>
            // Ширина фиксированная из $dims
            dimHTML += '<div style="padding:8px 12px; background:#f0f4ff; border-radius:4px;">';
            dimHTML += '<span style="color:#666;">Ширина: <strong><?php echo $dims['widths'][0]; ?> мм</strong></span>';
            dimHTML += '</div>';
            dimHTML += '<input type="hidden" id="custom_width" value="<?php echo $dims['widths'][0]; ?>">';
        <?php else: ?>
            // Ширина - селект из $dims
            dimHTML += '<label>Ширина (мм): <select id="custom_width" style="background:#fff;margin-left:10px;">';
            <?php foreach($dims['widths'] as $w): ?>
                dimHTML += '<option value="<?php echo esc_js($w); ?>"><?php echo esc_html($w); ?></option>';
            <?php endforeach; ?>
            dimHTML += '</select></label>';
        <?php endif; ?>
        
        // Длина из $dims (переводим в метры)
        dimHTML += '<label>Длина (м): <select id="custom_length" style="background:#fff;margin-left:10px;">';
        dimHTML += '<option value="">Выберите...</option>';
        <?php 
        for($l = $dims['length_min']; $l <= $dims['length_max']; $l += 100): 
            $length_m = $l / 1000;
        ?>
            dimHTML += '<option value="<?php echo esc_js($length_m); ?>"><?php echo esc_html(number_format($length_m, 2)); ?></option>';
        <?php endfor; ?>
        dimHTML += '</select></label>';
    <?php endif; ?>
    
    dimHTML += '</div><div id="calc_dim_result" style="margin-top:10px; font-size:1.3em"></div>';
    dimCalc.innerHTML = dimHTML;
    resultBlock.appendChild(dimCalc);

    if (paintingBlock && !document.getElementById('calc-area')) {
        dimCalc.appendChild(paintingBlock);
    }

    const widthEl = document.getElementById('custom_width');
    const lengthEl = document.getElementById('custom_length');
    const dimResult = document.getElementById('calc_dim_result');
    
    // Цена за м² (уже пересчитана с учетом толщины)
    const basePriceDim = <?php echo $base_price_for_calc; ?>;
    const widthIsFixed = <?php echo $width_is_fixed ? 'true' : 'false'; ?>;
    let dimInitialized = false;

    function updateDimCalc(userInteraction = false) {
        const width_mm = parseFloat(widthEl.value);
        const length_m = parseFloat(lengthEl.value);
        
        if (!width_mm || !length_m || width_mm <= 0 || length_m <= 0) {
            dimResult.innerHTML = '';
            removeHiddenFields('custom_dim_');
            updatePaintingServiceCost(0);
            return;
        }
        
        const width_m = width_mm / 1000;
        
        // Площадь = ширина_м × длина_м
        const area = width_m * length_m;
        
        // Цена = площадь × цена_за_м²
        const total = area * basePriceDim;
        
        const paintingCost = updatePaintingServiceCost(area);
        const grandTotal = total + paintingCost;

        let html = `Площадь 1 шт: <b>${area.toFixed(3)} м²</b><br>`;
        html += `Цена за 1 шт: <b>${total.toFixed(2)} ₽</b><br>`;
        if (paintingCost > 0) {
            html += `Стоимость покраски: <b>${paintingCost.toFixed(2)} ₽</b><br>`;
            html += `<br><strong>Итого: <b>${grandTotal.toFixed(2)} ₽</b></strong>`;
        } else {
            html += `<br><strong>Итого: <b>${total.toFixed(2)} ₽</b></strong>`;
        }

        dimResult.innerHTML = html;

        if (userInteraction) {
            // Сохраняем выбранные значения
            createHiddenField('custom_width_val', width_mm);
            createHiddenField('custom_length_val', length_m * 1000); // сохраняем в мм для совместимости
            createHiddenField('custom_dim_area', area.toFixed(3));
            createHiddenField('custom_dim_price', total.toFixed(2));
            createHiddenField('custom_dim_grand_total', grandTotal.toFixed(2));

            if (quantityInput) {
                isAutoUpdate = true;
                quantityInput.value = 1;
                quantityInput.dispatchEvent(new Event('change', { bubbles: true }));
                setTimeout(() => { isAutoUpdate = false; }, 100);
            }
        } else if (!dimInitialized) {
            dimInitialized = true;
        }
    }

    if (!widthIsFixed && widthEl) {
        widthEl.addEventListener('change', () => updateDimCalc(true));
    }
    lengthEl.addEventListener('change', () => updateDimCalc(true));
    
    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            if (!isAutoUpdate && form.querySelector('input[name="custom_width_val"]')) {
                // Сбрасываем все поля при ручном изменении количества
                removeHiddenFields('custom_');
                removeHiddenFields('painting_service_');
                if (!widthIsFixed) widthEl.selectedIndex = 0;
                lengthEl.selectedIndex = 0;
                const paintingSelect = document.getElementById('painting_service_select');
                if (paintingSelect) paintingSelect.selectedIndex = 0;
                updateDimCalc(false);
            }
        });
    }
    
    // Первоначальная инициализация - выбираем минимальные значения
    // Для ширины (если не фиксированная)
    if (!widthIsFixed && widthEl && widthEl.options.length > 1) {
        widthEl.selectedIndex = 1; // Первое значение после "Выберите..."
    }
    
    // Для длины - всегда выбираем первое значение (минимальное)
    if (lengthEl && lengthEl.options.length > 1) {
        lengthEl.selectedIndex = 1; // Первое значение после "Выберите..."
    }
    
    // Выполняем первоначальный расчет с минимальными значениями
    updateDimCalc(false);
<?php endif; ?>

    <?php 
    // ============================================================================
    // ФАСКА - для категорий 268, 270
    // ============================================================================
    $product_cats = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'ids'));
    $show_faska = false;
    $faska_types = array();

    if ($product_cats && !is_wp_error($product_cats)) {
        foreach ($product_cats as $cat_id) {
            if (in_array($cat_id, array(268, 270))) {
                $show_faska = true;
                $faska_types = get_term_meta($cat_id, 'faska_types', true);
                if ($faska_types) break;
            }
        }
    }
    ?>

    <?php 
    // ============================================================================
    // КАЛЬКУЛЯТОР С МНОЖИТЕЛЕМ - для столярных изделий (265-271, 273)
    // НЕ показывается для пиломатериалов ($is_target)
    // ============================================================================
    if($is_multiplier && !$show_falsebalk_calculator && !$is_category_271 && !$is_target): ?>
    const multiplierCalc = document.createElement('div');
    multiplierCalc.id = 'calc-multiplier';

    let calcHTML = '<br><h4>Калькулятор стоимости</h4>';
    if (priceMultiplier !== 1) {
        calcHTML =``;
    }
    calcHTML += '<div style="display:flex;gap:20px;flex-wrap:wrap;align-items: center;">';

    if (calcSettings && calcSettings.width_min > 0 && calcSettings.width_max > 0) {
        calcHTML += `<label>Ширина (мм): 
            <select id="mult_width" style="background:#fff;margin-left:10px;">
                <option value="">Выберите...</option>`;
        for (let w = calcSettings.width_min; w <= calcSettings.width_max; w += calcSettings.width_step) {
            calcHTML += `<option value="${w}">${w}</option>`;
        }
        calcHTML += `</select></label>`;
    } else {
        calcHTML += `<label>Ширина (мм): 
            <input type="number" id="mult_width" min="100" step="100" placeholder="100" style="width:100px; margin-left:10px;background:#fff;">
        </label>`;
    }

    if (calcSettings && calcSettings.length_min > 0 && calcSettings.length_max > 0) {
        calcHTML += `<label>Длина (м): 
            <select id="mult_length" min="0.01" step="0.01" style="margin-left:10px;background:#fff;">
                <option value="">Выберите...</option>`;
        for (let l = calcSettings.length_min; l <= calcSettings.length_max; l += calcSettings.length_step) {
            calcHTML += `<option value="${l.toFixed(2)}">${l.toFixed(2)}</option>`;
        }
        calcHTML += `</select></label>`;
    } else {
        calcHTML += `<label>Длина (м): 
            <input type="number" id="mult_length" min="0.01" step="0.01" placeholder="0.01" style="width:100px; margin-left:10px;">
        </label>`;
    }

    calcHTML += `<label style="display:none">Количество (шт): <span id="mult_quantity_display" style="display:none">1</span></label>`;
    calcHTML += '</div>';

    <?php if ($show_faska && !empty($faska_types)): ?>
    calcHTML += `<div id="faska_selection" style="margin-top: 10px; display: none;">
        <h5>Выберите тип фаски:</h4>
        <div id="faska_grid" style="display: grid; grid-template-columns: repeat(4, 1fr); grid-template-rows: repeat(2, 1fr); gap: 10px; margin-top: 10px;">
            <?php foreach ($faska_types as $index => $faska): 
                if (!empty($faska['name'])): ?>
            <label class="faska-option" style="cursor: pointer; text-align: center; padding: 8px; border: 2px solid #ddd; border-radius: 8px; transition: all 0.3s; aspect-ratio: 1;">
                <input type="radio" name="faska_type" value="<?php echo esc_attr($faska['name']); ?>" data-index="<?php echo $index; ?>" data-image="<?php echo esc_url($faska['image']); ?>" style="display: none;">
                <?php if (!empty($faska['image'])): ?>
                <img src="<?php echo esc_url($faska['image']); ?>" alt="<?php echo esc_attr($faska['name']); ?>" style="width: 100%; object-fit: contain; margin-bottom: 3px;">
                <?php endif; ?>
                <div style="font-size: 11px; line-height: 1.2;"><?php echo esc_html($faska['name']); ?></div>
            </label>
            <?php endif; 
            endforeach; ?>
        </div>
        <div id="faska_selected" style="display: none; margin-top: 20px; text-align: center; padding: 10px; border: 2px solid rgb(76, 175, 80); border-radius: 8px; background: #f9f9f9;">
            <p style="margin-bottom: 10px;">Выбранная фаска: <span id="faska_selected_name"></span></p>
            <img id="faska_selected_image" src="" alt="" style="height: auto; max-height: 250px; object-fit: contain;">
            <div style="margin-top: 10px;">
                <button type="button" id="change_faska_btn" style="padding: 8px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer;">Изменить выбор</button>
            </div>
        </div>
    </div>`;

    document.head.insertAdjacentHTML('beforeend', `
    <style>
    #faska_selection .faska-option:has(input:checked) {
        border-color: #0073aa !important;
        background-color: #f0f8ff;
        box-shadow: 0 0 8px rgba(0,115,170,0.4);
    }
    #faska_selection .faska-option:hover {
        border-color: #0073aa;
        transform: scale(1.05);
    }
    #change_faska_btn:hover {
        background: #005a87 !important;
    }
    @media (max-width: 768px) {
        #faska_grid {
            grid-template-columns: repeat(3, 1fr) !important;
            grid-template-rows: repeat(3, 1fr) !important;
        }
    }
    @media (max-width: 480px) {
        #faska_grid {
            grid-template-columns: repeat(2, 1fr) !important;
            grid-template-rows: repeat(4, 1fr) !important;
        }
        #faska_selected_image {
            max-width: 200px !important;
        }
    }
    </style>
    `);
    <?php endif; ?>

    calcHTML += '<div id="calc_mult_result" style="margin-top:10px; font-size:1.3em"></div>';
    multiplierCalc.innerHTML = calcHTML;
    resultBlock.appendChild(multiplierCalc);

    if (paintingBlock) {
        multiplierCalc.appendChild(paintingBlock);
    }

    <?php
    $show_thickness_categories = [268, 270, 271];
    $show_thickness = has_term($show_thickness_categories, 'product_cat', $product_id);
    ?>
    const multWidthEl = document.getElementById('mult_width');
    const multLengthEl = document.getElementById('mult_length');
    const multQuantityDisplay = document.getElementById('mult_quantity_display');
    const multResult = document.getElementById('calc_mult_result');
    const basePriceMult = <?php echo floatval($product->get_price()); ?>;

    function updateMultiplierCalc() {
        const widthValue = parseFloat(multWidthEl && multWidthEl.value);
        const lengthValue = parseFloat(multLengthEl && multLengthEl.value);

        const quantity = (quantityInput && !isNaN(parseInt(quantityInput.value))) ? parseInt(quantityInput.value) : 1;
        multQuantityDisplay.textContent = quantity;

        <?php if ($show_faska): ?>
        const faskaSelection = document.getElementById('faska_selection');
        if (faskaSelection) {
            if (widthValue > 0 && lengthValue > 0) {
                faskaSelection.style.display = 'block';
            } else {
                faskaSelection.style.display = 'none';
                const faskaInputs = document.querySelectorAll('input[name="faska_type"]');
                faskaInputs.forEach(input => input.checked = false);
                const grid = document.getElementById('faska_grid');
                if (grid) grid.style.display = 'grid';
                const sel = document.getElementById('faska_selected');
                if (sel) sel.style.display = 'none';
            }
        }
        <?php endif; ?>

        if (!widthValue || widthValue <= 0 || !lengthValue || lengthValue <= 0) {
            multResult.innerHTML = '';
            removeHiddenFields('custom_mult_');
            removeHiddenFields('selected_shape_');
            updatePaintingServiceCost(0);
            return;
        }

        const width_m = widthValue / 1000;
        const length_m = lengthValue;

        const areaPerItem = width_m * length_m;
        const totalArea = areaPerItem * quantity;
        const pricePerItem = areaPerItem * basePriceMult * priceMultiplier;
        const materialPrice = pricePerItem * quantity;

        const paintingCost = updatePaintingServiceCost(totalArea);

        let shapeExtraTotal = 0;
        const selectedShapeInput = document.querySelector('input[name="shape_type"]:checked');
        if (selectedShapeInput) {
            const shapeKey = selectedShapeInput.value;
            const shapePricesLocal = {
                round: <?php echo floatval(get_post_meta($product->get_id(), '_shape_price_round', true) ?: 0); ?>,
                triangle: <?php echo floatval(get_post_meta($product->get_id(), '_shape_price_triangle', true) ?: 0); ?>,
                flat: <?php echo floatval(get_post_meta($product->get_id(), '_shape_price_flat', true) ?: 0); ?>
            };
            const shapePrice = parseFloat(shapePricesLocal[shapeKey]) || 0;
            shapeExtraTotal = shapePrice * quantity;

            createHiddenField('selected_shape_type', shapeKey);
            createHiddenField('selected_shape_price_per_item', shapePrice.toFixed(2));
            createHiddenField('selected_shape_price_total', shapeExtraTotal.toFixed(2));
        } else {
            removeHiddenFields('selected_shape_');
        }

        const grandTotal = materialPrice + paintingCost + shapeExtraTotal;
        
        const showThickness = <?php echo $show_thickness ? 'true' : 'false'; ?>;

        let html = `Площадь 1 шт: <b>${areaPerItem.toFixed(3)} м²</b><br>`;
        if (showThickness) { html += `Толщина: <b>40мм</b><br>`; }
        html += `Цена за 1 шт: <b>${pricePerItem.toFixed(2)} ₽</b><br>`;
        html += `<br>`;

        if (paintingCost > 0) {
            // html += `Стоимость покраски: <b>${paintingCost.toFixed(2)} ₽</b><br>`;
        }

        if (shapeExtraTotal > 0) {
            // html += `Стоимость верхнего спила: <b>${shapeExtraTotal.toFixed(2)} ₽</b><br>`;
        }

        html += `<strong>Итого: <b>${grandTotal.toFixed(2)} ₽</b></strong>`;

        multResult.innerHTML = html;

        createHiddenField('custom_mult_width', widthValue);
        createHiddenField('custom_mult_length', lengthValue);
        createHiddenField('custom_mult_quantity', quantity);
        createHiddenField('custom_mult_area_per_item', areaPerItem.toFixed(3));
        createHiddenField('custom_mult_total_area', totalArea.toFixed(3));
        createHiddenField('custom_mult_multiplier', priceMultiplier);
        createHiddenField('custom_mult_price', materialPrice.toFixed(2));
        createHiddenField('custom_mult_grand_total', grandTotal.toFixed(2));

        <?php if ($show_faska): ?>
        const selectedFaska = document.querySelector('input[name="faska_type"]:checked');
        if (selectedFaska) {
            createHiddenField('selected_faska_type', selectedFaska.value);
        } else {
            removeHiddenFields('selected_faska_');
        }
        <?php endif; ?>
    }

    multWidthEl.addEventListener('change', updateMultiplierCalc);
    multLengthEl.addEventListener('change', updateMultiplierCalc);

    <?php if ($show_faska): ?>
    setTimeout(function() {
        const faskaInputs = document.querySelectorAll('input[name="faska_type"]');
        const faskaGrid = document.getElementById('faska_grid');
        const faskaSelected = document.getElementById('faska_selected');
        const faskaSelectedName = document.getElementById('faska_selected_name');
        const faskaSelectedImage = document.getElementById('faska_selected_image');
        const changeFaskaBtn = document.getElementById('change_faska_btn');
        
        faskaInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.checked) {
                    faskaGrid.style.display = 'none';
                    faskaSelected.style.display = 'block';
                    
                    faskaSelectedName.textContent = this.value;
                    faskaSelectedImage.src = this.dataset.image;
                    faskaSelectedImage.alt = this.value;
                    
                    // Сохраняем изображение в скрытое поле
                    createHiddenField('selected_faska_image', this.dataset.image);
                }
                updateMultiplierCalc();
            });
        });
        
        if (changeFaskaBtn) {
            changeFaskaBtn.addEventListener('click', function() {
                faskaGrid.style.display = 'grid';
                faskaSelected.style.display = 'none';
            });
        }
    }, 100);
    <?php endif; ?>

    if (quantityInput) {
        quantityInput.addEventListener('change', function() {
            if (!isAutoUpdate && multWidthEl.value && multLengthEl.value) {
                updateMultiplierCalc();
            }
        });

        quantityInput.addEventListener('input', function() {
            if (!isAutoUpdate) {
                const mainQty = parseInt(this.value);
                if (mainQty > 0 && multWidthEl.value && multLengthEl.value) {
                    updateMultiplierCalc();
                }
            }
        });
    }
    <?php endif; ?>

    if (paintingBlock) {
        const serviceSelect = document.getElementById('painting_service_select');
        if (serviceSelect) {
            serviceSelect.addEventListener('change', function() {
                const areaInput = document.getElementById('calc_area_input');
                const widthEl = document.getElementById('custom_width');
                const lengthEl = document.getElementById('custom_length');
                const multWidthEl = document.getElementById('mult_width');
                const multLengthEl = document.getElementById('mult_length');

                if (areaInput && areaInput.value) {
                    updateAreaCalc();
                    return;
                }

                if (widthEl && lengthEl) {
                    const width = parseFloat(widthEl.value);
                    const length = parseFloat(lengthEl.value);
                    if (width > 0 && length > 0) {
                        updateDimCalc(true);
                        return;
                    }
                }

                if (multWidthEl && multLengthEl) {
                    const width = parseFloat(multWidthEl.value);
                    const length = parseFloat(multLengthEl.value);
                    if (width > 0 && length > 0) {
                        updateMultiplierCalc();
                        return;
                    }
                }

                const rmWidthEl = document.getElementById('rm_width');
                const rmLengthEl = document.getElementById('rm_length');
                if (rmLengthEl && rmLengthEl.value) {
                    updateRunningMeterCalc();
                    return;
                }

                if (typeof packArea !== 'undefined' && packArea > 0) {
                    if (areaInput) {
                        areaInput.value = packArea.toFixed(2);
                        updateAreaCalc();
                    } else if (widthEl && lengthEl) {
                        updateDimCalc(true);
                    }
                }

                updatePaintingServiceCost(0);
            });
        }
    }

    <?php 
    // ============================================================================
    // КАЛЬКУЛЯТОР ПОГОННЫХ МЕТРОВ
    // ============================================================================
    if($is_running_meter): ?>
    <?php if ($show_falsebalk_calculator): ?>
    if (resultBlock) {
        resultBlock.innerHTML = '';
    }
    <?php endif; ?>
    
    const runningMeterCalc = document.createElement('div');
    runningMeterCalc.id = 'calc-running-meter';

    let rmCalcHTML = '<br><h4>Калькулятор стоимости</h4>';

    <?php if ($show_falsebalk_calculator): ?>
    <?php 
    $shape_icons = [
        'g' => '<svg width="60" height="60" viewBox="0 0 60 60"><rect x="5" y="5" width="10" height="50" fill="#000"/><rect x="5" y="45" width="50" height="10" fill="#000"/></svg>',
        'p' => '<svg width="60" height="60" viewBox="0 0 60 60"><rect x="5" y="5" width="10" height="50" fill="#000"/><rect x="45" y="5" width="10" height="50" fill="#000"/><rect x="5" y="5" width="50" height="10" fill="#000"/></svg>',
        'o' => '<svg width="60" height="60" viewBox="0 0 60 60"><rect x="5" y="5" width="50" height="50" fill="none" stroke="#000" stroke-width="10"/></svg>'
    ];
    
    $shape_labels = [
        'g' => 'Г-образная',
        'p' => 'П-образная',
        'o' => 'О-образная'
    ];
    
    $shapes_buttons_html = '';
    foreach ($shapes_data as $shape_key => $shape_info):
        if (is_array($shape_info) && !empty($shape_info['enabled'])):
            $shape_label = isset($shape_labels[$shape_key]) ? $shape_labels[$shape_key] : ucfirst($shape_key);
            $shapes_buttons_html .= '<label class="shape-tile" data-shape="' . esc_attr($shape_key) . '" style="cursor:pointer; border:2px solid #ccc; border-radius:10px; padding:10px; background:#fff; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; transition:all .2s; min-width:100px;">';
            $shapes_buttons_html .= '<input type="radio" name="falsebalk_shape" value="' . esc_attr($shape_key) . '" style="display:none;">';
            $shapes_buttons_html .= '<div>' . $shape_icons[$shape_key] . '</div>';
            $shapes_buttons_html .= '<span style="font-size:12px; color:#666; text-align:center;">' . esc_html($shape_label) . '</span>';
            $shapes_buttons_html .= '</label>';
        endif;
    endforeach;
    ?>

    rmCalcHTML += '<div style="margin-bottom:20px; border:2px solid #e0e0e0; padding:15px; border-radius:8px; background:#f9f9f9;">';
    rmCalcHTML += '<label style="display:block; margin-bottom:15px; font-weight:600; font-size:1.1em;">Шаг 1: Выберите форму сечения фальшбалки</label>';
    rmCalcHTML += '<div style="display:flex; gap:15px; flex-wrap:wrap;">';
    rmCalcHTML += <?php echo json_encode($shapes_buttons_html); ?>;
    rmCalcHTML += '</div></div>';

    rmCalcHTML += '<div id="falsebalk_params" style="display:none; margin-bottom:20px; border:2px solid #e0e0e0; padding:15px; border-radius:8px; background:#f9f9f9;">';
    rmCalcHTML += '<label style="display:block; margin-bottom:15px; font-weight:600; font-size:1.1em;">Шаг 2: Выберите размеры</label>';
    rmCalcHTML += '<div style="display:flex; gap:20px; flex-wrap:wrap; align-items:center;">';

    rmCalcHTML += `<label style="display:flex; flex-direction:column; gap:5px;">
        <span style="font-weight:500;">Ширина (мм):</span>
        <select id="rm_width" style="background:#fff; padding:8px 12px; border:1px solid #ddd; border-radius:4px; min-width:150px;">
            <option value="">Сначала выберите форму</option>
        </select>
    </label>`;

    rmCalcHTML += `<div id="height_container" style="display:contents"></div>`;

    rmCalcHTML += `<label style="display:flex; flex-direction:column; gap:5px;">
        <span style="font-weight:500;">Длина (м):</span>
        <select id="rm_length" style="background:#fff; padding:8px 12px; border:1px solid #ddd; border-radius:4px; min-width:150px;">
            <option value="">Сначала выберите форму</option>
        </select>
    </label>`;

    rmCalcHTML += `<label style="display:none; flex-direction:column; gap:5px;">
        <span style="font-weight:500;">Количество (шт):</span>
        <span id="rm_quantity_display" style="font-weight:600; font-size:1.1em;">1</span>
    </label>`;

    rmCalcHTML += '</div></div>';
    rmCalcHTML += '<div id="calc_rm_result" style="margin-top:15px;"></div>';

    <?php else: ?>
    rmCalcHTML += '<div style="display:flex;gap:20px;flex-wrap:wrap;align-items: center;">';

    if (calcSettings && calcSettings.width_min > 0 && calcSettings.width_max > 0) {
        rmCalcHTML += `<label>Ширина (мм): 
            <select id="rm_width" style="background:#fff;margin-left:10px;">
                <option value="">Выберите...</option>`;
        for (let w = calcSettings.width_min; w <= calcSettings.width_max; w += calcSettings.width_step) {
            rmCalcHTML += `<option value="${w}">${w}</option>`;
        }
        rmCalcHTML += `</select></label>`;
    } else {
        rmCalcHTML += `<label>Ширина (мм): 
            <input type="number" id="rm_width" min="1" step="100" placeholder="100" style="width:100px; margin-left:10px;background:#fff">
        </label>`;
    }

    if (calcSettings && calcSettings.length_min > 0 && calcSettings.length_max > 0) {
        rmCalcHTML += `<label>Длина (м): 
            <select id="rm_length" style="background:#fff;margin-left:10px;">
                <option value="">Выберите...</option>`;
        for (let l = calcSettings.length_min; l <= calcSettings.length_max; l += calcSettings.length_step) {
            rmCalcHTML += `<option value="${l.toFixed(2)}">${l.toFixed(2)}</option>`;
        }
        rmCalcHTML += `</select></label>`;
    } else {
        rmCalcHTML += `<label>Длина (пог. м): 
            <input type="number" id="rm_length" min="0.1" step="0.1" placeholder="2.0" style="width:100px; margin-left:10px;background:#fff">
        </label>`;
    }

    rmCalcHTML += `<label style="display:none">Количество (шт): <span id="rm_quantity_display" style="margin-left:10px; font-weight:600;">1</span></label>`;
    rmCalcHTML += '</div>';
    rmCalcHTML += '<div id="calc_rm_result" style="margin-top:10px;"></div>';
    <?php endif; ?>

    runningMeterCalc.innerHTML = rmCalcHTML;
    if (resultBlock) {
        resultBlock.appendChild(runningMeterCalc);
    }

    if (paintingBlock) {
        runningMeterCalc.appendChild(paintingBlock);
    }

    const rmWidthEl = document.getElementById('rm_width');
    const rmLengthEl = document.getElementById('rm_length');
    const rmQuantityDisplay = document.getElementById('rm_quantity_display');
    const rmResult = document.getElementById('calc_rm_result');
    const basePriceRM = <?php echo floatval($product->get_price()); ?>;

    <?php if ($show_falsebalk_calculator): ?>
    setTimeout(function() {
        function generateOptions(min, max, step, isLength = false) {
            const options = ['<option value="">Выберите...</option>'];
            if (!min || !max || !step || min > max) return options.join('');
            
            const stepsCount = Math.round((max - min) / step);
            for (let i = 0; i <= stepsCount; i++) {
                const value = parseFloat((min + (i * step)).toFixed(2));
                const displayValue = isLength ? value.toFixed(2) : Math.round(value);
                options.push(`<option value="${value}">${displayValue}</option>`);
            }
            return options.join('');
        }

        function parseOldFormat(data) {
            if (typeof data === 'string' && data.includes(',')) {
                const values = data.split(',').map(v => v.trim()).filter(v => v);
                return values.map(v => `<option value="${v}">${v}</option>`).join('');
            }
            return null;
        }

        const falsebalkaParams = document.getElementById('falsebalk_params');
        const heightContainer = document.getElementById('height_container');

        function updateDimensions(selectedShape) {
            const shapeData = shapesData[selectedShape];
            if (!shapeData || !shapeData.enabled) {
                return;
            }
            
            falsebalkaParams.style.display = 'block';
            
            const oldWidthFormat = parseOldFormat(shapeData.widths);
            if (oldWidthFormat) {
                rmWidthEl.innerHTML = '<option value="">Выберите...</option>' + oldWidthFormat;
            } else {
                rmWidthEl.innerHTML = generateOptions(shapeData.width_min, shapeData.width_max, shapeData.width_step, false);
            }
            
            heightContainer.innerHTML = '';
            if (selectedShape === 'p') {
                let height1Options, height2Options;
                const oldHeight1Format = parseOldFormat(shapeData.heights);
                
                if (oldHeight1Format) {
                    height1Options = '<option value="">Выберите...</option>' + oldHeight1Format;
                    height2Options = '<option value="">Выберите...</option>' + oldHeight1Format;
                } else {
                    height1Options = generateOptions(shapeData.height1_min, shapeData.height1_max, shapeData.height1_step, false);
                    height2Options = generateOptions(shapeData.height2_min, shapeData.height2_max, shapeData.height2_step, false);
                }
                
                heightContainer.innerHTML = `
                    <label style="display:flex; flex-direction:column; gap:5px;">
                        <span style="font-weight:500;">Высота 1 (мм):</span>
                        <select id="rm_height1" style="background:#fff; padding:8px 12px; border:1px solid #ddd; border-radius:4px; min-width:150px;">
                            ${height1Options}
                        </select>
                    </label>
                    <label style="display:flex; flex-direction:column; gap:5px;">
                        <span style="font-weight:500;">Высота 2 (мм):</span>
                        <select id="rm_height2" style="background:#fff; padding:8px 12px; border:1px solid #ddd; border-radius:4px; min-width:150px;">
                            ${height2Options}
                        </select>
                    </label>
                `;
                
                document.getElementById('rm_height1').addEventListener('change', updateRunningMeterCalc);
                document.getElementById('rm_height2').addEventListener('change', updateRunningMeterCalc);
            } else {
                const oldHeightFormat = parseOldFormat(shapeData.heights);
                let heightOptions = oldHeightFormat ? '<option value="">Выберите...</option>' + oldHeightFormat : 
                                   generateOptions(shapeData.height_min, shapeData.height_max, shapeData.height_step, false);
                
                heightContainer.innerHTML = `
                    <label style="display:flex; flex-direction:column; gap:5px;">
                        <span style="font-weight:500;">Высота (мм):</span>
                        <select id="rm_height" style="background:#fff; padding:8px 12px; border:1px solid #ddd; border-radius:4px; min-width:150px;">
                            ${heightOptions}
                        </select>
                    </label>
                `;
                
                document.getElementById('rm_height').addEventListener('change', updateRunningMeterCalc);
            }
            
            const oldLengthFormat = parseOldFormat(shapeData.lengths);
            if (oldLengthFormat) {
                rmLengthEl.innerHTML = '<option value="">Выберите...</option>' + oldLengthFormat;
            } else {
                rmLengthEl.innerHTML = generateOptions(shapeData.length_min, shapeData.length_max, shapeData.length_step, true);
            }
            
            document.getElementById('calc_rm_result').innerHTML = '';
            removeHiddenFields('custom_rm_');
        }

        document.querySelectorAll('.shape-tile').forEach(tile => {
            tile.addEventListener('click', function() {
                document.querySelectorAll('.shape-tile').forEach(t => {
                    t.style.borderColor = '#ccc';
                    t.style.boxShadow = 'none';
                });
                
                this.style.borderColor = '#3aa655';
                this.style.boxShadow = '0 0 0 3px rgba(58,166,85,0.3)';
                
                const radio = this.querySelector('input[name="falsebalk_shape"]');
                if (radio) {
                    radio.checked = true;
                    updateDimensions(radio.value);
                }
            });
            
            tile.addEventListener('mouseenter', function() {
                const radio = this.querySelector('input[name="falsebalk_shape"]');
                if (!radio || !radio.checked) {
                    this.style.borderColor = '#2c5cc5';
                    this.style.transform = 'scale(1.02)';
                }
            });
            
            tile.addEventListener('mouseleave', function() {
                const radio = this.querySelector('input[name="falsebalk_shape"]');
                if (!radio || !radio.checked) {
                    this.style.borderColor = '#ccc';
                    this.style.transform = 'scale(1)';
                }
            });
        });
    }, 200);

    <?php else: ?>
    <?php endif; ?>

    function updateRunningMeterCalc() {
        <?php if ($show_falsebalk_calculator): ?>
        const selectedShape = document.querySelector('input[name="falsebalk_shape"]:checked');
        if (!selectedShape) {
            rmResult.innerHTML = '<span style="color: #999;">⬆️ Выберите форму сечения фальшбалки</span>';
            return;
        }
        
        const widthValue = rmWidthEl ? parseFloat(rmWidthEl.value) : 0;
        const lengthValue = parseFloat(rmLengthEl.value);
        
        let heightValue = 0;
        let height2Value = 0;
        
        if (selectedShape.value === 'p') {
            const height1El = document.getElementById('rm_height1');
            const height2El = document.getElementById('rm_height2');
            heightValue = height1El ? parseFloat(height1El.value) : 0;
            height2Value = height2El ? parseFloat(height2El.value) : 0;
        } else {
            const heightEl = document.getElementById('rm_height');
            heightValue = heightEl ? parseFloat(heightEl.value) : 0;
        }
        <?php else: ?>
        const widthValue = rmWidthEl ? parseFloat(rmWidthEl.value) : 0;
        const lengthValue = parseFloat(rmLengthEl.value);
        <?php endif; ?>

        const quantity = (quantityInput && !isNaN(parseInt(quantityInput.value))) ? parseInt(quantityInput.value) : 1;
        rmQuantityDisplay.textContent = quantity;

        if (!lengthValue || lengthValue <= 0) {
            rmResult.innerHTML = '';
            removeHiddenFields('custom_rm_');
            updatePaintingServiceCost(0);
            return;
        }

        const totalLength = lengthValue * quantity;

        let paintingAreaPerItem = 0;
        if (widthValue > 0) {
            const width_m = widthValue / 1000;
            const height_m = (typeof heightValue !== 'undefined' ? heightValue : 0) / 1000;
            const height2_m = (typeof height2Value !== 'undefined' ? height2Value : 0) / 1000;

            <?php if ($show_falsebalk_calculator): ?>
            const shapeKey = selectedShape.value;
            if (shapeKey === 'g') {
                paintingAreaPerItem = (width_m + height_m) * lengthValue;
            } else if (shapeKey === 'p') {
                paintingAreaPerItem = (width_m + height_m + height2_m) * lengthValue;
            } else if (shapeKey === 'o') {
                paintingAreaPerItem = 2 * (width_m + height_m) * lengthValue;
            } else {
                paintingAreaPerItem = width_m * lengthValue;
            }
            <?php else: ?>
            paintingAreaPerItem = width_m * lengthValue;
            <?php endif; ?>
        }

        const totalPaintingArea = paintingAreaPerItem * quantity;
        const materialPricePerItem = paintingAreaPerItem * basePriceRM * priceMultiplier;
        const materialPrice = materialPricePerItem * quantity;
        const paintingCost = updatePaintingServiceCost(totalPaintingArea);
        const grandTotal = materialPrice + paintingCost;

        let html = `Площадь 1 шт: <b>${paintingAreaPerItem.toFixed(3)} м²</b><br>`;
        
        if (paintingCost > 0) {
            html += `<br><strong style="font-size: 1.2em;">Итого с покраской: <b>${grandTotal.toFixed(2)} ₽</b></strong>`;
        } else {
            html += `<br><strong style="font-size: 1.2em;">Итого: <b>${materialPrice.toFixed(2)} ₽</b></strong>`;
        }

        rmResult.innerHTML = html;

        <?php if ($show_falsebalk_calculator): ?>
        createHiddenField('custom_rm_shape', selectedShape.value);
        createHiddenField('custom_rm_width', widthValue || 0);
        createHiddenField('custom_rm_height', heightValue || 0);
        if (selectedShape.value === 'p' && height2Value > 0) {
            createHiddenField('custom_rm_height2', height2Value);
        }
        <?php else: ?>
        createHiddenField('custom_rm_width', widthValue || 0);
        <?php endif; ?>
        
        createHiddenField('custom_rm_length', lengthValue);
        createHiddenField('custom_rm_quantity', quantity);
        createHiddenField('custom_rm_total_length', totalLength.toFixed(2));
        createHiddenField('custom_rm_painting_area', totalPaintingArea.toFixed(3));
        createHiddenField('custom_rm_multiplier', priceMultiplier);
        createHiddenField('custom_rm_price', materialPrice.toFixed(2));
        createHiddenField('custom_rm_grand_total', grandTotal.toFixed(2));
    }

    if (rmWidthEl) rmWidthEl.addEventListener('change', updateRunningMeterCalc);
    if (rmLengthEl) rmLengthEl.addEventListener('change', updateRunningMeterCalc);

    const runningMeterCalcBlock = document.getElementById('calc-running-meter');
    if (runningMeterCalcBlock) {
        const paintingSelectRM = runningMeterCalcBlock.querySelector('#painting_service_select');
        if (paintingSelectRM) {
            paintingSelectRM.addEventListener('change', function() {
                if (rmLengthEl && rmLengthEl.value) {
                    updateRunningMeterCalc();
                }
            });
        }
    }

    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            if (!isAutoUpdate && rmLengthEl && rmLengthEl.value) {
                updateRunningMeterCalc();
            }
        });
        
        quantityInput.addEventListener('change', function() {
            if (!isAutoUpdate && rmLengthEl && rmLengthEl.value) {
                updateRunningMeterCalc();
            }
        });
    }
    <?php endif; ?>

});

<?php 
// ============================================================================
// КАЛЬКУЛЯТОР КАТЕГОРИИ 271
// ============================================================================
if($is_category_271 && $category_271_settings): ?>
document.addEventListener('DOMContentLoaded', function() {
    const cat271Container = document.querySelector('form.cart') || document.querySelector('.summary');
    if (!cat271Container) return;
    
    const cat271Calc = document.createElement('div');
    cat271Calc.id = 'calc-category-271';
    
    let calcHTML = '<br><h4>Калькулятор стоимости</h4>';
    calcHTML += '<div style="display:flex; gap:20px; flex-wrap:wrap; align-items:center; margin-bottom: 15px;">';
    
    calcHTML += '<label style="display:flex; flex-direction:column; gap:5px;">';
    calcHTML += '<span style="font-weight:500;">Ширина (мм):</span>';
    calcHTML += '<select id="cat271_width" style="background:#fff; padding:8px 12px; border:1px solid #ddd; border-radius:4px; min-width:150px;">';
    calcHTML += '<option value="">Выберите...</option>';
    
    const widthMin = category271Settings.width_min;
    const widthMax = category271Settings.width_max;
    const widthStep = category271Settings.width_step;
    
    if (widthMin > 0 && widthMax > 0 && widthStep > 0) {
        const stepsCount = Math.round((widthMax - widthMin) / widthStep) + 1;
        for (let i = 0; i < stepsCount; i++) {
            const value = Math.round(widthMin + (i * widthStep));
            calcHTML += '<option value="' + value + '">' + value + ' мм</option>';
        }
    }
    
    calcHTML += '</select></label>';
    
    calcHTML += '<div style="display:flex; gap:15px; align-items:center; padding:10px 15px; background:#f0f4ff; border-radius:8px;">';
    calcHTML += '<span style="color:#666;">Длина: <strong>3.0 м</strong></span>';
    calcHTML += '<span style="color:#666;">Толщина: <strong>40 мм</strong></span>';
    calcHTML += '</div>';
    
    calcHTML += '<label style="display:none">Количество (шт): <span id="cat271_quantity_display" style="margin-left:10px; font-weight:600;">1</span></label>';
    calcHTML += '</div>';
    calcHTML += '<div id="calc_cat271_result" style="margin-top:10px;"></div>';
    
    cat271Calc.innerHTML = calcHTML;
    cat271Container.appendChild(cat271Calc);
    
    if (Object.keys(paintingServices).length > 0) {
        const cat271PaintingBlock = document.createElement('div');
        cat271PaintingBlock.id = 'painting-services-block';
        
        let optionsHTML = '<option value="" selected>Без покраски</option>';
        Object.entries(paintingServices).forEach(([key, service]) => {
            optionsHTML += `<option value="${key}" data-price="${service.price}">${service.name} (+${service.price} ₽/м²)</option>`;
        });
        
        cat271PaintingBlock.innerHTML = `
            <br><h4>Услуги покраски</h4>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 10px;">
                    Выберите услугу покраски:
                    <select id="painting_service_select" style="margin-left: 10px; padding: 5px; width: 100%; background: #fff">
                        ${optionsHTML}
                    </select>
                </label>
                <div id="painting-service-result" style="display:none;"></div>
            </div>
            <div id="paint-schemes-root"></div>
        `;
        
        cat271Calc.appendChild(cat271PaintingBlock);
    }
    
    const widthSelect = document.getElementById('cat271_width');
    const resultDiv = document.getElementById('calc_cat271_result');
    const quantityDisplay = document.getElementById('cat271_quantity_display');
    const basePrice = <?php echo floatval($product->get_price()); ?>;
    const fixedLength = 3.0;
    const fixedThickness = 40;
    
    const mainQuantityInput = document.querySelector('input[name="quantity"]') || 
                             document.querySelector('.qty') || 
                             document.querySelector('.input-text.qty');
    
    function updateCategory271Calc() {
        const width = parseFloat(widthSelect.value);
        const quantity = (mainQuantityInput && !isNaN(parseInt(mainQuantityInput.value))) ? parseInt(mainQuantityInput.value) : 1;
        
        if (quantityDisplay) {
            quantityDisplay.textContent = quantity;
        }
        
        if (!width) {
            resultDiv.innerHTML = '';
            removeHiddenFields('custom_cat271_');
            updatePaintingServiceCost(0);
            return;
        }
        
        const area = (width / 1000) * fixedLength;
        const totalArea = area * quantity;
        const totalPrice = basePrice * area * priceMultiplier * quantity;
        
        updatePaintingServiceCost(totalArea);
        
        const paintingService = getPaintingServiceData();
        const grandTotal = paintingService ? (totalPrice + paintingService.total_cost) : totalPrice;
        
        let resultText = '<div style="padding:15px; margin-top:10px;">';
        resultText += '<div style="font-size:1.2em; font-weight:600; padding-top:10px; border-top:1px solid #ddd;">Стоимость: ' + totalPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
        
        if (paintingService) {
            resultText += '<div style="font-size:1.05em; margin-top:8px; padding-top:8px; border-top:1px solid #ddd;">+ ' + paintingService.name_with_color + ': ' + paintingService.total_cost.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
            resultText += '<div style="font-size:1.3em; font-weight:700; margin-top:8px;">Итого: ' + grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
        }
        
        resultText += '</div>';
        resultDiv.innerHTML = resultText;
        
        removeHiddenFields('custom_cat271_');
        addHiddenField('custom_cat271_width', width);
        addHiddenField('custom_cat271_length', fixedLength);
        addHiddenField('custom_cat271_thickness', fixedThickness);
        addHiddenField('custom_cat271_area', area);
        addHiddenField('custom_cat271_price', totalPrice);
        addHiddenField('custom_cat271_grand_total', grandTotal);
        addHiddenField('custom_cat271_quantity', quantity);
        
        if (paintingService) {
            addHiddenField('painting_service_id', paintingService.id);
            addHiddenField('painting_service_name', paintingService.name_with_color);
            addHiddenField('painting_service_cost', paintingService.total_cost);
            addHiddenField('painting_service_area', totalArea);
        }
    }
    
    widthSelect.addEventListener('change', updateCategory271Calc);
    
    if (mainQuantityInput) {
        mainQuantityInput.addEventListener('change', function() {
            if (widthSelect.value) {
                updateCategory271Calc();
            }
        });
        
        mainQuantityInput.addEventListener('input', function() {
            if (widthSelect.value) {
                updateCategory271Calc();
            }
        });
    }
    
    const paintingSelect271 = document.getElementById('painting_service_select');
    if (paintingSelect271) {
        paintingSelect271.addEventListener('change', function() {
            if (widthSelect.value) {
                updateCategory271Calc();
            }
        });
    }
});
<?php endif; ?>
    </script>
    <?php
}