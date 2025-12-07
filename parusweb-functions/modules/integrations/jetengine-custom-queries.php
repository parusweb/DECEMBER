<?php
/**
 * ============================================================================
 * КАСТОМНЫЕ QUERY ДЛЯ JETENGINE
 * ============================================================================
 * 
 */

if (!defined('ABSPATH')) exit;

/**
 * Регистрация кастомных Query для JetEngine
 */
add_action('jet-engine/query-builder/queries/register', function($manager) {
    
    // ========================================================================
    // QUERY 1: Только пиломатериалы
    // ========================================================================
    $manager->register_query('timber-products-only', [
        'label' => 'Товары: Пиломатериалы',
        'callback' => function($query_args, $query_obj) {
            
            $timber_categories = [87, 88, 89, 90, 91, 92, 93, 310];
            
            $args = [
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => isset($query_args['posts_per_page']) ? $query_args['posts_per_page'] : 12,
                'paged' => isset($query_args['paged']) ? $query_args['paged'] : 1,
                'tax_query' => [
                    [
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $timber_categories,
                        'operator' => 'IN'
                    ]
                ]
            ];
            
            $query = new WP_Query($args);
            return $query->posts;
        }
    ]);
    
    // ========================================================================
    // QUERY 2: Все КРОМЕ пиломатериалов
    // ========================================================================
    $manager->register_query('products-not-timber', [
        'label' => 'Товары: НЕ пиломатериалы',
        'callback' => function($query_args, $query_obj) {
            
            $timber_categories = [87, 88, 89, 90, 91, 92, 93, 310];
            
            $args = [
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => isset($query_args['posts_per_page']) ? $query_args['posts_per_page'] : 12,
                'paged' => isset($query_args['paged']) ? $query_args['paged'] : 1,
                'tax_query' => [
                    [
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $timber_categories,
                        'operator' => 'NOT IN'
                    ]
                ]
            ];
            
            $query = new WP_Query($args);
            return $query->posts;
        }
    ]);
});