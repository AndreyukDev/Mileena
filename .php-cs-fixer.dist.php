<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'var',
        'storage',
        'docker',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRules([
        // Используем самый актуальный стандарт вместо устаревшего PSR-12
        '@PER-CS' => true,

        // 1. Схлопываем лишние пустые строки (твоя боль)
        'no_extra_blank_lines' => [
            'tokens' => [
                'extra',           // Убирает 2+ пустые строки подряд
                'break',
                'continue',
                'curly_brace_block',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'throw',
                'use',
            ],
        ],

        // 2. Убираем пустые строки в начале и конце тел классов/методов
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,

        // 3. Красиво отделяем return и if одной строкой (но не тремя!)
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'try', 'if', 'foreach', 'while'],
        ],

        // 4. Убираем лишние пробелы в круглых скобках (ультра-плотность)
        'no_spaces_around_offset' => true,

        // 5. Автоматическое приведение к строгой типизации (как в Go)
        'void_return' => true,

        // Автоматически добавляет declare(strict_types=1); во все файлы
        'declare_strict_types' => true,

        // Массивы должны быть короткими []
        'array_syntax' => ['syntax' => 'short'],

        // Убирает лишние импорты (use), которые не используются
        'no_unused_imports' => true,

        // Добавляет запятую в конце длинных списков (удобно для Git diff)
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],

        // Приводит в порядок отступы и скобки в новых фишках PHP 8+
        'ordered_types' => ['null_adjustment' => 'always_last'],
        'phpdoc_to_comment' => false, // Чтобы не ломать аннотации, если используешь
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true);
