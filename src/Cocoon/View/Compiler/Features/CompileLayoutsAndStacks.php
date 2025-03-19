<?php

namespace Cocoon\View\Compiler\Features;

use Cocoon\View\TemplateException;

/**
 * Trait pour la compilation des layouts et des piles
 *
 * Ce trait gère la compilation des directives liées aux layouts et aux piles :
 * - @extends : Héritage de layout
 * - @section/@endsection : Définition de sections
 * - @yield : Affichage de sections
 * - @push/@endpush : Empilage de contenu
 * - @stack : Affichage de piles
 *
 * @package Cocoon\View\Compiler\Features
 */
trait CompileLayoutsAndStacks
{
    /**
     * Compile la directive @extends
     *
     * Définit le layout parent du template.
     * Exemple : @extends('layouts.main')
     *
     * @param string $args Chemin du layout
     * @return string Code PHP généré
     * @throws TemplateException Si l'argument est vide
     */
    public function compileExtends(string $args): string
    {
        if (empty(trim($args))) {
            throw new TemplateException('Argument manquant pour @extends');
        }

        return '<?php $this->layout(' . trim($args) . '); ?>';
    }

    /**
     * Compile la directive @endsection
     *
     * Termine la définition d'une section.
     *
     * @return string Code PHP généré
     */
    public function compileEndSection(): string
    {
        return '<?php $this->endSection(); ?>' . PHP_EOL;
    }

    /**
     * Compile la directive @section
     *
     * Définit une section de contenu.
     * Exemples :
     * - @section('content')
     * - @section('sidebar' with ['menu' => $menu])
     *
     * @param string $args Arguments de la directive
     * @return string Code PHP généré
     * @throws TemplateException Si la syntaxe est invalide
     */
    public function compileSection(string $args): string
    {
        if (empty(trim($args))) {
            throw new TemplateException('Arguments manquants pour @section');
        }

        // Section avec données
        if (str_contains($args, ' with ')) {
            if (!preg_match('#(.*?) with ([^\t\r\n}]+)#', $args, $matches)) {
                throw new TemplateException('Syntaxe invalide pour @section with');
            }

            return sprintf(
                '<?php $this->section(%s, %s); ?>' . PHP_EOL,
                trim($matches[1]),
                $this->resolveExpression(trim($matches[2]))
            );
        }

        // Section simple
        return '<?php $this->section(' . trim($args) . '); ?>';
    }

    /**
     * Compile la directive @push
     *
     * Empile du contenu dans une pile nommée.
     * Exemples :
     * - @push('scripts')
     * - @push('styles' with ['media' => 'print'])
     *
     * @param string $args Arguments de la directive
     * @return string Code PHP généré
     * @throws TemplateException Si la syntaxe est invalide
     */
    public function compilePush(string $args): string
    {
        if (empty(trim($args))) {
            throw new TemplateException('Arguments manquants pour @push');
        }

        // Push avec données
        if (str_contains($args, ' with ')) {
            if (!preg_match('#(.*?) with ([^\t\r\n}]+)#', $args, $matches)) {
                throw new TemplateException('Syntaxe invalide pour @push with');
            }

            return sprintf(
                '<?php $this->push(%s, %s); ?>' . PHP_EOL,
                trim($matches[1]),
                $this->resolveExpression(trim($matches[2]))
            );
        }

        // Push simple
        return '<?php $this->push(' . trim($args) . '); ?>' . PHP_EOL;
    }

    /**
     * Compile la directive @endpush
     *
     * Termine l'empilage de contenu.
     *
     * @return string Code PHP généré
     */
    public function compileEndpush(): string
    {
        return '<?php $this->endPush(); ?>' . PHP_EOL;
    }

    /**
     * Compile la directive @yield
     *
     * Affiche le contenu d'une section.
     * Exemple : @yield('content')
     *
     * @param string $args Nom de la section
     * @return string Code PHP généré
     * @throws TemplateException Si l'argument est vide
     */
    public function compileYield(string $args): string
    {
        if (empty(trim($args))) {
            throw new TemplateException('Argument manquant pour @yield');
        }

        return '<?php echo $this->getSection(' . trim($args) . '); ?>';
    }

    /**
     * Compile la directive @stack
     *
     * Affiche le contenu d'une pile.
     * Exemple : @stack('scripts')
     *
     * @param string $args Nom de la pile
     * @return string Code PHP généré
     * @throws TemplateException Si l'argument est vide
     */
    public function compileStack(string $args): string
    {
        if (empty(trim($args))) {
            throw new TemplateException('Argument manquant pour @stack');
        }

        return '<?php echo $this->getStack(' . trim($args) . '); ?>';
    }

    /**
     * Compile la directive @javascript
     *
     * Encapsule du code JavaScript dans des balises script.
     * Le code est extrait du template et stocké dans un tableau.
     *
     * @param array<int, string> $args Code JavaScript
     * @return string Code HTML généré
     */
    public function compileJavascript(array $args): string
    {
        if (empty($args)) {
            return '';
        }

        $code = array_shift($args);
        return "<script>\n" . trim($code) . "\n</script>";
    }
}
