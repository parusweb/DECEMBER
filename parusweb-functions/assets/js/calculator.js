/**
 * ParusWeb Functions - Calculator Main Script
 * 
 * Основной файл калькуляторов. Логика находится в PHP-модулях (inline scripts).
 * Этот файл служит для регистрации и базовой инициализации.
 * 
 * @package ParusWeb_Functions
 * @version 2.0.0
 */

(function() {
    'use strict';
    
    // Объект для хранения данных калькуляторов
    window.ParusWebCalculators = window.ParusWebCalculators || {};
    
    // Готовность калькулятора
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof paruswebCalculator !== 'undefined') {
            window.ParusWebCalculators.config = paruswebCalculator;
            
            // Тр иггер события готовности
            var event = new CustomEvent('parusweb:calculator:ready', {
                detail: paruswebCalculator
            });
            document.dispatchEvent(event);
        }
    });
    
})();
