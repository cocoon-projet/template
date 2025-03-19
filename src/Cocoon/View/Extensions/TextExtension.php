<?php

namespace Cocoon\View\Extensions;

use Cocoon\View\AbstractExtension;
use Carbon\Carbon;

/**
 * Extension pour la manipulation de texte et de dates
 */
class TextExtension extends AbstractExtension
{
    /**
     * Définit les variables globales à injecter dans tous les templates
     */
    public function getWiths(): array
    {
        return [
            'text' => [
                'lorem' => 'Lorem ipsum dolor sit amet',
                'placeholder' => 'Texte par défaut'
            ]
        ];
    }

    /**
     * Définit les filtres personnalisés pour la manipulation de texte
     */
    public function getFilters(): array
    {
        return [
            'excerpt' => function ($text, $length = 100) {
                if (strlen($text) <= $length) {
                    return $text;
                }
                return substr($text, 0, $length) . '...';
            },
            'slug' => function ($text) {
                $text = preg_replace('~[^\pL\d]+~u', '-', $text);
                $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
                $text = preg_replace('~[^-\w]+~', '', $text);
                $text = trim($text, '-');
                $text = preg_replace('~-+~', '-', $text);
                $text = strtolower($text);
                return $text;
            },
            'wordcount' => function ($text) {
                return str_word_count($text);
            },
            'escape_attr' => function ($text) {
                return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        ];
    }

    /**
     * Définit les fonctions personnalisées pour la manipulation de texte
     */
    public function getFunctions(): array
    {
        return [
            'str_starts_with' => function ($haystack, $needle) {
                return str_starts_with($haystack, $needle);
            },
            'str_ends_with' => function ($haystack, $needle) {
                return str_ends_with($haystack, $needle);
            },
            'str_contains' => function ($haystack, $needle) {
                return str_contains($haystack, $needle);
            },
            'str_replace' => function ($subject, $search, $replace) {
                return str_replace($search, $replace, $subject);
            }
        ];
    }

    /**
     * Définit les directives personnalisées pour le texte
     */
    public function getDirectives(): array
    {
        return [];
    }

    /**
     * Définit les conditions personnalisées
     */
    public function getIfs(): array
    {
        return [
            'empty_text' => function ($value): bool {
                return empty(trim($value));
            },
            'contains' => function (string $haystack, string $needle): bool {
                return str_contains($haystack, $needle);
            }
        ];
    }
}
