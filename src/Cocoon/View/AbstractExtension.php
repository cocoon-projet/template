<?php

namespace Cocoon\View;

/**
 * Classe abstraite pour créer des extensions du moteur de template
 *
 * Cette classe permet d'étendre les fonctionnalités du moteur de template en ajoutant :
 * - Des variables globales accessibles dans tous les templates
 * - Des filtres pour transformer les données (ex: truncate, slug, etc.)
 * - Des fonctions pour des opérations complexes (ex: format_date, format_number)
 * - Des directives pour générer du code PHP personnalisé
 * - Des conditions personnalisées pour les structures if/else
 *
 * Exemple d'utilisation :
 * ```php
 * class MyExtension extends AbstractExtention
 * {
 *     public function getWiths(): array
 *     {
 *         return [
 *             'config' => ['app_name' => 'Mon App']
 *         ];
 *     }
 *
 *     public function getFilters(): array
 *     {
 *         return [
 *             'upper' => 'strtoupper',
 *             'truncate' => function($text, $length = 100) {
 *                 return mb_substr($text, 0, $length) . '...';
 *             }
 *         ];
 *     }
 * }
 *
 * // Dans le template :
 * {{ config.app_name }}
 * {{ text|upper }}
 * {{ description|truncate(50) }}
 * ```
 *
 * @package Cocoon\View
 */
abstract class AbstractExtension
{
    /**
     * Définit les variables globales à injecter dans tous les templates
     *
     * Ces variables seront accessibles dans tous les templates sans avoir à les passer
     * explicitement. Utile pour des configurations, des données de session, etc.
     *
     * @return array<string, mixed> Tableau associatif des variables globales
     */
    public function getWiths(): array
    {
        return [];
    }

    /**
     * Définit les filtres personnalisés pour transformer les données
     *
     * Les filtres sont utilisés avec la syntaxe {{ variable|filtre(args) }}
     * Ils peuvent être :
     * - Une fonction PHP native (ex: 'trim', 'strtoupper')
     * - Une fonction anonyme avec des arguments
     *
     * @return array<string, callable|string> Tableau associatif nom => callback
     */
    public function getFilters(): array
    {
        return [];
    }

    /**
     * Définit les fonctions personnalisées utilisables dans les templates
     *
     * Les fonctions sont appelées avec la syntaxe {{ ma_fonction(args) }}
     * Elles permettent des opérations plus complexes que les filtres.
     *
     * @return array<string, callable> Tableau associatif nom => callback
     */
    public function getFunctions(): array
    {
        return [];
    }

    /**
     * Définit les directives personnalisées pour le template
     *
     * Les directives sont utilisées avec @ (ex: @ma_directive(args))
     * Elles génèrent du code PHP qui sera exécuté lors du rendu.
     *
     * @return array<string, callable> Tableau associatif nom => callback retournant du code PHP
     */
    public function getDirectives(): array
    {
        return [];
    }

    /**
     * Définit les conditions personnalisées pour les structures if/else
     *
     * Les conditions sont utilisées dans les @if (ex: @if(ma_condition(args)))
     * Elles doivent retourner un booléen.
     *
     * @return array<string, callable> Tableau associatif nom => callback retournant bool
     */
    public function getIfs(): array
    {
        return [];
    }
}
