<?php
/**
 * ============================================================================
 * МОДУЛЬ: НАСТРОЙКИ URL И REWRITE
 * ============================================================================
 * 
 * Управление структурой URL:
 * - Удаление /category/ только для рубрики news
 * 
 * @package ParusWeb_Functions
 * @subpackage Core
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// КАСТОМНОЕ ПРАВИЛО ТОЛЬКО ДЛЯ РУБРИКИ NEWS
// ============================================================================

add_action('init', 'parusweb_custom_news_rewrite');
function parusweb_custom_news_rewrite() {
    add_rewrite_rule(
        '^news/?$',
        'index.php?category_name=news',
        'top'
    );
    add_rewrite_rule(
        '^news/page/?([0-9]{1,})/?$',
        'index.php?category_name=news&paged=$matches[1]',
        'top'
    );
}

// Изменяем URL только для рубрики news
add_filter('term_link', 'parusweb_news_category_link', 10, 3);
function parusweb_news_category_link($termlink, $term, $taxonomy) {
    if ($taxonomy === 'category' && $term->slug === 'news') {
        $termlink = home_url('/news/');
    }
    return $termlink;
}

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================