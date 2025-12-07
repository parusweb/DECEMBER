/**
 * ParusWeb Functions - Utilities
 * 
 * Вспомогательные функции для работы с калькуляторами
 * 
 * @package ParusWeb_Functions
 * @version 2.0.0
 */

(function($) {
    'use strict';
    
    window.ParusWebUtils = {
        
        /**
         * Форматирование цены
         */
        formatPrice: function(price, decimals) {
            decimals = decimals || 2;
            price = parseFloat(price) || 0;
            return price.toFixed(decimals).replace('.', ',') + ' ₽';
        },
        
        /**
         * Парсинг числа из строки
         */
        parseNumber: function(value) {
            if (typeof value === 'number') return value;
            value = String(value).replace(/\s/g, '').replace(',', '.');
            return parseFloat(value) || 0;
        },
        
        /**
         * Склонение русских слов
         */
        pluralize: function(number, forms) {
            number = Math.abs(number) % 100;
            var n1 = number % 10;
            
            if (number > 10 && number < 20) return forms[2];
            if (n1 > 1 && n1 < 5) return forms[1];
            if (n1 === 1) return forms[0];
            
            return forms[2];
        },
        
        /**
         * Debounce функция
         */
        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this;
                var args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }
        
    };
    
})(jQuery);
