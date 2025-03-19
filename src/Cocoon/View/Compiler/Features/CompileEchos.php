<?php

namespace Cocoon\View\Compiler\Features;

use Cocoon\View\TemplateException;

/**
 * Trait pour la compilation des expressions d'affichage dans les templates
 *
 * Ce trait gère la compilation des expressions entre {{ }} et {{{ }}} en code PHP.
 * Il prend en charge les fonctionnalités suivantes :
 *
 * 1. Expressions échappées ({{ }}) :
 *    - Variables simples : {{ name }}
 *    - Appels de fonctions : {{ uppercase(name) }}
 *    - Filtres : {{ name|upper|trim }}
 *    - Expressions complexes : {{ user.profile.name|upper }}
 *
 * 2. Expressions non échappées ({{{ }}}) :
 *    - HTML brut : {{{ html_content }}}
 *    - Résultat de fonctions : {{{ render_template() }}}
 *
 * Exemples d'utilisation :
 * ```
 * {{ name }}                    -> <?php echo $this->escape($name); ?>
 * {{ user.name|upper }}        -> <?php echo $this->escape($this->filter('upper', $user->name)); ?>
 * {{{ html_content }}}         -> <?php echo $html_content; ?>
 * {{ format(name, age) }}      -> <?php echo $this->escape($this->func('format', $name, $age)); ?>
 * ```
 *
 * @package Cocoon\View\Compiler\Features
 */
trait CompileEchos
{
    /**
     * Compile les expressions échappées {{ var }}
     *
     * Cette méthode :
     * 1. Vérifie que l'expression n'est pas vide
     * 2. Détecte si c'est un appel de fonction ou une expression avec filtres
     * 3. Compile l'expression en code PHP sécurisé
     * 4. Ajoute l'échappement automatique avec $this->escape()
     *
     * @param string $code Expression à compiler (contenu entre {{ }})
     * @return string Code PHP généré
     * @throws TemplateException Si l'expression est vide ou invalide
     */
    public function compileEcho(string $code): string
    {
        $code = trim($code);
        if ($code === '') {
            throw new TemplateException(
                'Expression vide dans {{ }}. Utilisez une variable, une fonction ou une expression.'
            );
        }

        try {
            // Vérifie si c'est un appel de fonction sans filtre
            if ($this->isFunctionCall($code)) {
                $expression = $this->compileFunctionCall($code);
            } else {
                $expression = $this->compileFilteredExpression($code);
            }

            return sprintf('<?php echo $this->escape(%s); ?>', $expression);
        } catch (TemplateException $e) {
            throw new TemplateException(
                sprintf('Erreur dans l\'expression {{ %s }} : %s', $code, $e->getMessage())
            );
        }
    }

    /**
     * Compile les expressions non échappées {{{ var }}}
     *
     * Cette méthode est similaire à compileEcho() mais n'ajoute pas
     * d'échappement automatique. À utiliser avec précaution car le contenu
     * ne sera pas protégé contre les attaques XSS.
     *
     * @param string $code Expression à compiler (contenu entre {{{ }}})
     * @return string Code PHP généré
     * @throws TemplateException Si l'expression est vide ou invalide
     */
    public function compileNoEscape(string $code): string
    {
        $code = trim($code);
        if ($code === '') {
            throw new TemplateException(
                'Expression vide dans {{{ }}}. Utilisez une variable, une fonction ou une expression.'
            );
        }

        try {
            // Vérifie si c'est un appel de fonction sans filtre
            if ($this->isFunctionCall($code)) {
                $expression = $this->compileFunctionCall($code);
            } else {
                $expression = $this->compileFilteredExpression($code);
            }

            return sprintf('<?php echo %s; ?>', $expression);
        } catch (TemplateException $e) {
            throw new TemplateException(
                sprintf('Erreur dans l\'expression {{{ %s }}} : %s', $code, $e->getMessage())
            );
        }
    }

    /**
     * Vérifie si l'expression est un appel de fonction sans filtre
     *
     * @param string $code Expression à vérifier
     * @return bool true si c'est un appel de fonction simple
     */
    private function isFunctionCall(string $code): bool
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*\s*\(.*\)$/', $code) === 1
            && !str_contains($code, '|');
    }

    /**
     * Compile un appel de fonction en code PHP
     *
     * Transforme un appel de fonction du template en appel PHP valide.
     * Exemple : format(name, age) -> $this->func('format', $name, $age)
     *
     * @param string $code Expression de fonction à compiler
     * @return string Code PHP généré
     * @throws TemplateException Si la syntaxe de la fonction est invalide
     */
    private function compileFunctionCall(string $code): string
    {
        if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/', $code, $matches)) {
            throw new TemplateException(
                sprintf('Syntaxe de fonction invalide : %s', $code)
            );
        }

        $function = $matches[1];
        $arguments = trim($matches[2]);

        // Vérifie que le nom de la fonction est valide
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $function)) {
            throw new TemplateException(
                sprintf('Nom de fonction invalide : %s', $function)
            );
        }

        // Compile les arguments
        $compiledArgs = $this->compileArguments($arguments);

        return sprintf(
            '$this->func(%s, %s)',
            var_export($function, true),
            $compiledArgs
        );
    }

    /**
     * Compile une expression avec filtres en code PHP
     *
     * Transforme une expression avec filtres en appels PHP chaînés.
     * Exemple : name|upper|trim -> $this->filter('trim', $this->filter('upper', $name))
     *
     * @param string $code Expression avec filtres à compiler
     * @return string Code PHP généré
     * @throws TemplateException Si la syntaxe des filtres est invalide
     */
    private function compileFilteredExpression(string $code): string
    {
        $parts = explode('|', $code);
        $variable = array_shift($parts);
        
        if (empty($parts)) {
            return $this->resolveExpression($variable);
        }

        $expression = $this->resolveExpression($variable);

        foreach ($parts as $filter) {
            $filter = trim($filter);
            
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*(?:\((.*)\))?$/', $filter, $matches)) {
                $filterName = $matches[1];
                $filterArgs = isset($matches[2]) ? trim($matches[2]) : '';

                if (!isset($this->filters[$filterName])) {
                    throw new TemplateException(
                        sprintf('Filtre inconnu : %s', $filterName)
                    );
                }

                $args = $filterArgs !== ''
                    ? $this->compileArguments($filterArgs)
                    : '';

                $expression = sprintf(
                    '$this->filter(%s, %s%s)',
                    var_export($filterName, true),
                    $expression,
                    $args !== '' ? ', ' . $args : ''
                );
            } else {
                throw new TemplateException(
                    sprintf('Syntaxe de filtre invalide : %s', $filter)
                );
            }
        }

        return $expression;
    }

    /**
     * Compile une liste d'arguments en code PHP
     *
     * @param string $arguments Liste d'arguments séparés par des virgules
     * @return string Arguments compilés en PHP
     * @throws TemplateException Si un argument est invalide
     */
    private function compileArguments(string $arguments): string
    {
        if (trim($arguments) === '') {
            return '';
        }

        $args = array_map('trim', explode(',', $arguments));
        $compiled = [];

        foreach ($args as $arg) {
            try {
                $compiled[] = $this->resolveExpression($arg);
            } catch (TemplateException $e) {
                throw new TemplateException(
                    sprintf('Argument invalide : %s', $e->getMessage())
                );
            }
        }

        return implode(', ', $compiled);
    }
}
