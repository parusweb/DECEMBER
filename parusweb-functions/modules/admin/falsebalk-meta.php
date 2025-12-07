<?php
/**
 * Falsebalk Meta Fields
 * 
 * Метаполя для фальшбалок (категория 266):
 * - Настройка форм сечения (Г, П, О-образные)
 * - Доступные размеры (ширина, высота, длина) для каждой формы
 * 
 * @package ParusWeb_Functions
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: МЕТАПОЛЯ ФОРМ ФАЛЬШБАЛОК
// ============================================================================

/**
 * Добавление метабокса для настройки форм фальшбалок
 */
if (!function_exists('parusweb_add_falsebalk_shapes_fields')) {
function parusweb_add_falsebalk_shapes_fields() {
    global $post;

    // Проверяем, что товар относится к категории фальшбалок (266)
    if (!has_term(266, 'product_cat', $post->ID)) {
        return;
    }

    // Получаем сохраненные настройки
    $shapes_data = get_post_meta($post->ID, '_falsebalk_shapes_data', true);
    if (!is_array($shapes_data)) $shapes_data = [];

    $shapes = [
        'g' => 'Г-образная',
        'p' => 'П-образная',
        'o' => 'О-образная'
    ];

    $shape_icons = [
        'g' => '<svg width="32" height="32" viewBox="0 0 60 60"><rect x="5" y="5" width="10" height="50" fill="#666"/><rect x="5" y="45" width="50" height="10" fill="#666"/></svg>',
        'p' => '<svg width="32" height="32" viewBox="0 0 60 60"><rect x="5" y="5" width="10" height="50" fill="#666"/><rect x="45" y="5" width="10" height="50" fill="#666"/><rect x="5" y="5" width="50" height="10" fill="#666"/></svg>',
        'o' => '<svg width="32" height="32" viewBox="0 0 60 60"><rect x="5" y="5" width="50" height="50" fill="none" stroke="#666" stroke-width="10"/></svg>'
    ];

    ?>
    <div class="options_group" style="clear: both;">

        <p class="form-field" style="padding-left: 12px;">
            <label style="font-weight: 600;">Настройки фальшбалок</label>
        </p>

        <?php foreach ($shapes as $shape_key => $shape_label): ?>
            <?php
            $shape_info = $shapes_data[$shape_key] ?? [];
            $enabled = !empty($shape_info['enabled']);
            ?>

            <div class="form-field" style="padding: 0 12px;">
                <div style="padding: 15px; background: #f9f9f9; border:1px solid #ddd; border-radius:4px;">

                    <!-- ЗАГОЛОВОК — ВСЕГДА ВИДИМ -->
                    <div style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;">
                        <div style="display:flex; align-items:center; gap:10px; cursor:pointer;">

                            <input type="hidden"
                                   name="_shape_<?php echo $shape_key; ?>_enabled"
                                   value="0">

                            <input type="checkbox"
                                   name="_shape_<?php echo $shape_key; ?>_enabled"
                                   value="1"
                                   <?php checked($enabled); ?>
                                   class="falsebalk-shape-toggle"
                                   data-shape="<?php echo $shape_key; ?>">

                            <?php echo $shape_icons[$shape_key]; ?>

                            <strong style="flex:1;"><?php echo $shape_label; ?></strong>

                            <span class="falsebalk-status-<?php echo $shape_key; ?>"
                                  style="font-weight:600; <?php echo $enabled ? 'color:#46b450' : 'color:#dc3545'; ?>">
                                <?php echo $enabled ? '✓ Активна' : '✗ Не активна'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- ЭТОТ БЛОК МОЖНО СКРЫВАТЬ -->
                    <div class="falsebalk-shape-fields"
                         data-shape="<?php echo $shape_key; ?>"
                         style="<?php echo $enabled ? '' : 'display:none;'; ?>">

                        <table class="form-table" style="margin:0;">
                            <tbody>

                            <tr>
                                <th style="width:20%; padding:8px 0;">Ширина (мм)</th>
                                <td style="padding:8px 0;">
                                    <span style="font-size:11px; color:#666;">мин:</span>
                                    <input type="number"
                                           name="_shape_<?php echo $shape_key; ?>_width_min"
                                           value="<?php echo esc_attr($shape_info['width_min'] ?? ''); ?>"
                                           style="width:80px; margin:0 8px 0 4px;">
                                    <span style="font-size:11px; color:#666;">макс:</span>
                                    <input type="number"
                                           name="_shape_<?php echo $shape_key; ?>_width_max"
                                           value="<?php echo esc_attr($shape_info['width_max'] ?? ''); ?>"
                                           style="width:80px; margin:0 8px 0 4px;">
                                    <span style="font-size:11px; color:#666;">шаг:</span>
                                    <input type="number"
                                           name="_shape_<?php echo $shape_key; ?>_width_step"
                                           value="<?php echo esc_attr($shape_info['width_step'] ?? ''); ?>"
                                           style="width:60px; margin-left:4px;">
                                </td>
                            </tr>

                            <tr>
                                <th style="width:20%; padding:8px 0;">Высота <?php echo $shape_key==='p'?'1':''; ?> (мм)</th>
                                <td style="padding:8px 0;">
                                    <span style="font-size:11px; color:#666;">мин:</span>
                                    <input type="number"
                                           name="_shape_<?php echo $shape_key; ?>_height_min"
                                           value="<?php echo esc_attr($shape_info['height_min'] ?? ''); ?>"
                                           style="width:80px; margin:0 8px 0 4px;">
                                    <span style="font-size:11px; color:#666;">макс:</span>
                                    <input type="number"
                                           name="_shape_<?php echo $shape_key; ?>_height_max"
                                           value="<?php echo esc_attr($shape_info['height_max'] ?? ''); ?>"
                                           style="width:80px; margin:0 8px 0 4px;">
                                    <span style="font-size:11px; color:#666;">шаг:</span>
                                    <input type="number"
                                           name="_shape_<?php echo $shape_key; ?>_height_step"
                                           value="<?php echo esc_attr($shape_info['height_step'] ?? ''); ?>"
                                           style="width:60px; margin-left:4px;">
                                </td>
                            </tr>

                            <?php if ($shape_key === 'p'): ?>
                                <tr>
                                    <th style="width:20%; padding:8px 0;">Высота 2 (мм)</th>
                                    <td style="padding:8px 0;">
                                        <span style="font-size:11px; color:#666;">мин:</span>
                                        <input type="number"
                                               name="_shape_<?php echo $shape_key; ?>_height2_min"
                                               value="<?php echo esc_attr($shape_info['height2_min'] ?? ''); ?>"
                                               style="width:80px; margin:0 8px 0 4px;">
                                        <span style="font-size:11px; color:#666;">макс:</span>
                                        <input type="number"
                                               name="_shape_<?php echo $shape_key; ?>_height2_max"
                                               value="<?php echo esc_attr($shape_info['height2_max'] ?? ''); ?>"
                                               style="width:80px; margin:0 8px 0 4px;">
                                        <span style="font-size:11px; color:#666;">шаг:</span>
                                        <input type="number"
                                               name="_shape_<?php echo $shape_key; ?>_height2_step"
                                               value="<?php echo esc_attr($shape_info['height2_step'] ?? ''); ?>"
                                               style="width:60px; margin-left:4px;">
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <tr>
                                <th style="width:20%; padding:8px 0;">Длина (м)</th>
                                <td style="padding:8px 0;">
                                    <span style="font-size:11px; color:#666;">мин:</span>
                                    <input type="number"
                                           name="_shape_<?php echo $shape_key; ?>_length_min"
                                           step="0.01"
                                           value="<?php echo esc_attr($shape_info['length_min'] ?? ''); ?>"
                                           style="width:80px; margin:0 8px 0 4px;">
                                    <span style="font-size:11px; color:#666;">макс:</span>
                                    <input type="number"
                                           name="_shape_<?php echo $shape_key; ?>_length_max"
                                           step="0.01"
                                           value="<?php echo esc_attr($shape_info['length_max'] ?? ''); ?>"
                                           style="width:80px; margin:0 8px 0 4px;">
                                    <span style="font-size:11px; color:#666;">шаг:</span>
                                    <input type="number"
                                           name="_shape_<?php echo $shape_key; ?>_length_step"
                                           step="0.01"
                                           value="<?php echo esc_attr($shape_info['length_step'] ?? ''); ?>"
                                           style="width:60px; margin-left:4px;">
                                </td>
                            </tr>

                            </tbody>
                        </table>

                    </div>
                </div>
            </div>

        <?php endforeach; ?>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {

        $('.falsebalk-shape-toggle').on('change', function() {
            var shape = $(this).data('shape');
            var $fields = $('.falsebalk-shape-fields[data-shape="' + shape + '"]');
            var $status = $('.falsebalk-status-' + shape);

            if ($(this).is(':checked')) {
                $fields.slideDown(200);
                $status.text('✓ Активна').css('color', '#46b450');
            } else {
                $fields.slideUp(200);
                $status.text('✗ Не активна').css('color', '#dc3545');
            }
        });

    });
    </script>
    <?php
}}
add_action('woocommerce_product_options_pricing', 'parusweb_add_falsebalk_shapes_fields');





// ============================================================================
// БЛОК 2: СОХРАНЕНИЕ ДАННЫХ ФОРМ ФАЛЬШБАЛОК
// ============================================================================

/**
 * Сохранение метаполей форм фальшбалок
 */
if (!function_exists('parusweb_save_falsebalk_shapes')) {
function parusweb_save_falsebalk_shapes($post_id) {
    // Проверки безопасности
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    // Проверяем, что товар относится к категории фальшбалок
    if (!has_term(266, 'product_cat', $post_id)) {
        return;
    }
    
    $shapes = ['g', 'p', 'o'];
    $shapes_data = [];
    
    foreach ($shapes as $shape) {
        $enabled = isset($_POST["_shape_{$shape}_enabled"]) ? 1 : 0;
        
        $shapes_data[$shape] = [
            'enabled' => $enabled,
            'width_min' => isset($_POST["_shape_{$shape}_width_min"]) ? intval($_POST["_shape_{$shape}_width_min"]) : 0,
            'width_max' => isset($_POST["_shape_{$shape}_width_max"]) ? intval($_POST["_shape_{$shape}_width_max"]) : 0,
            'width_step' => isset($_POST["_shape_{$shape}_width_step"]) ? intval($_POST["_shape_{$shape}_width_step"]) : 10,
            'height_min' => isset($_POST["_shape_{$shape}_height_min"]) ? intval($_POST["_shape_{$shape}_height_min"]) : 0,
            'height_max' => isset($_POST["_shape_{$shape}_height_max"]) ? intval($_POST["_shape_{$shape}_height_max"]) : 0,
            'height_step' => isset($_POST["_shape_{$shape}_height_step"]) ? intval($_POST["_shape_{$shape}_height_step"]) : 10,
            'length_min' => isset($_POST["_shape_{$shape}_length_min"]) ? floatval($_POST["_shape_{$shape}_length_min"]) : 1,
            'length_max' => isset($_POST["_shape_{$shape}_length_max"]) ? floatval($_POST["_shape_{$shape}_length_max"]) : 6,
            'length_step' => isset($_POST["_shape_{$shape}_length_step"]) ? floatval($_POST["_shape_{$shape}_length_step"]) : 0.01,
        ];
        
        // Дополнительные поля для П-образной формы
        if ($shape === 'p') {
            $shapes_data[$shape]['height2_min'] = isset($_POST["_shape_{$shape}_height2_min"]) ? intval($_POST["_shape_{$shape}_height2_min"]) : 0;
            $shapes_data[$shape]['height2_max'] = isset($_POST["_shape_{$shape}_height2_max"]) ? intval($_POST["_shape_{$shape}_height2_max"]) : 0;
            $shapes_data[$shape]['height2_step'] = isset($_POST["_shape_{$shape}_height2_step"]) ? intval($_POST["_shape_{$shape}_height2_step"]) : 10;
        }
    }
    
    update_post_meta($post_id, '_falsebalk_shapes_data', $shapes_data);
}
}
add_action('woocommerce_process_product_meta', 'parusweb_save_falsebalk_shapes');

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================