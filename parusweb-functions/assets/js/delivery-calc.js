/**
 * ParusWeb - Delivery Calculator
 * 
 * Калькулятор доставки с интеграцией Яндекс.Карт
 * Этот файл должен находиться в теме: /js/delivery-calc.js
 * 
 * Основная логика в modules/features/delivery-calculator.php
 * 
 * @version 1.3
 */

(function($) {
    'use strict';
    
    // Проверка наличия настроек
    if (typeof deliveryVars === 'undefined') {
        return;
    }
    
    // Ждем загрузки Яндекс.Карт
    function initDelivery() {
        if (typeof ymaps === 'undefined') {
            setTimeout(initDelivery, 500);
            return;
        }
        
        ymaps.ready(function() {
            // Инициализация карты и калькулятора
            // Основная логика должна быть в PHP-модуле
        });
    }
    
    $(document).ready(function() {
        initDelivery();
    });
    
})(jQuery);
