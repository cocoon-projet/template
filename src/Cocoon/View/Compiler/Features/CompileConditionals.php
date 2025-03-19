<?php

namespace Cocoon\View\Compiler\Features;

use Cocoon\View\TemplateException;

/**
 * Trait pour la compilation des structures conditionnelles dans les templates
 *
 * Ce trait gère la compilation des directives conditionnelles en code PHP.
 * Il supporte les structures suivantes :
 *
 * 1. Conditions classiques :
 *    ```
 *    @if(condition)
 *      // code
 *    @elseif(autre_condition)
 *      // code
 *    @else
 *      // code
 *    @endif
 *    ```
 *
 * 2. Tests de variables :
 *    ```
 *    @isset(variable)
 *      // code si la variable existe
 *    @endisset
 *
 *    @empty(tableau)
 *      // code si le tableau est vide
 *    @endempty
 *    ```
 *
 * 3. Structure switch :
 *    ```
 *    @switch(status)
 *      @case('actif')
 *        // code
 *        @break
 *      @case('inactif')
 *        // code
 *        @break
 *      @default
 *        // code par défaut
 *    @endswitch
 *    ```
 *
 * 4. Contrôle de boucle :
 *    ```
 *    @foreach(items as item)
 *      @if(condition)
 *        @continue
 *      @endif
 *      @if(autre_condition)
 *        @break
 *      @endif
 *    @endforeach
 *    ```
 *
 * @package Cocoon\View\Compiler\Features
 */
trait CompileConditionals
{
    /**
     * Compile la directive @if
     *
     * Supporte les opérateurs de comparaison (==, !=, <, >, <=, >=)
     * et les opérateurs logiques (and, or).
     *
     * Exemples :
     * ```
     * @if(age >= 18)
     * @if(role == 'admin' and status == 'actif')
     * @if(user.isAdmin())
     * @if(users|length > 0)
     * ```
     *
     * @param string $args Expression conditionnelle
     * @return string Code PHP généré
     * @throws TemplateException Si l'expression est vide ou invalide
     */
    public function compileIf(string $args): string
    {
        $args = trim($args);
        if ($args === '') {
            throw new TemplateException(
                'Expression manquante pour @if. Exemple : @if(age >= 18)'
            );
        }

        try {
            $condition = $this->parseConditionals($args);
            return sprintf("<?php if (%s): ?>\n", trim($condition));
        } catch (TemplateException $e) {
            throw new TemplateException(
                sprintf('Erreur dans la condition @if(%s) : %s', $args, $e->getMessage())
            );
        }
    }

    /**
     * Compile la directive @elseif
     *
     * Supporte la même syntaxe que @if.
     *
     * Exemples :
     * ```
     * @elseif(age >= 13)
     * @elseif(role == 'editor')
     * ```
     *
     * @param string $args Expression conditionnelle
     * @return string Code PHP généré
     * @throws TemplateException Si l'expression est vide ou invalide
     */
    public function compileElseif(string $args): string
    {
        $args = trim($args);
        if ($args === '') {
            throw new TemplateException(
                'Expression manquante pour @elseif. Exemple : @elseif(role == "editor")'
            );
        }

        try {
            $condition = $this->parseConditionals($args);
            return sprintf("<?php elseif (%s): ?>\n", trim($condition));
        } catch (TemplateException $e) {
            throw new TemplateException(
                sprintf('Erreur dans la condition @elseif(%s) : %s', $args, $e->getMessage())
            );
        }
    }

    /**
     * Compile la directive @else
     *
     * @return string Code PHP généré
     */
    public function compileElse(): string
    {
        return "<?php else: ?>\n";
    }

    /**
     * Compile la directive @endif
     *
     * @return string Code PHP généré
     */
    public function compileEndif(): string
    {
        return "<?php endif; ?>\n";
    }

    /**
     * Compile la directive @endforelse
     *
     * @return string Code PHP généré
     */
    public function compileEndforelse(): string
    {
        return "<?php endif; ?>\n";
    }

    /**
     * Compile la directive @endisset
     *
     * @return string Code PHP généré
     */
    public function compileEndisset(): string
    {
        return "<?php endif; ?>\n";
    }

    /**
     * Compile la directive @endempty
     *
     * @return string Code PHP généré
     */
    public function compileEndempty(): string
    {
        return "<?php endif; ?>\n";
    }

    /**
     * Compile la directive @empty pour @forelse
     *
     * @return string Code PHP généré
     */
    public function compileIfempty(): string
    {
        return "<?php endforeach; else: ?>\n";
    }

    /**
     * Compile la directive @continue
     *
     * @return string Code PHP généré
     */
    public function compileContinue(): string
    {
        return "<?php continue; ?>\n";
    }

    /**
     * Compile la directive @break
     *
     * @return string Code PHP généré
     */
    public function compileBreak(): string
    {
        return "<?php break; ?>\n";
    }

    /**
     * Compile la directive @isset
     *
     * Vérifie si une variable est définie et non null.
     *
     * Exemples :
     * ```
     * @isset(user)
     * @isset(user.name)
     * @isset(users[0])
     * ```
     *
     * @param string $args Variable à tester
     * @return string Code PHP généré
     * @throws TemplateException Si l'expression est vide ou invalide
     */
    public function compileIsset(string $args): string
    {
        $args = trim($args);
        if ($args === '') {
            throw new TemplateException(
                'Expression manquante pour @isset. Exemple : @isset(user.name)'
            );
        }

        try {
            $expression = $this->resolveExpression($args);
            return sprintf("<?php if (isset(%s)): ?>\n", $expression);
        } catch (TemplateException $e) {
            throw new TemplateException(
                sprintf('Erreur dans @isset(%s) : %s', $args, $e->getMessage())
            );
        }
    }

    /**
     * Compile la directive @empty
     *
     * Vérifie si une variable est vide (empty en PHP).
     * Une variable est considérée vide si elle :
     * - N'existe pas
     * - Est égale à "" (chaîne vide)
     * - Est égale à 0 (zéro)
     * - Est égale à null
     * - Est un tableau vide
     *
     * Exemples :
     * ```
     * @empty(users)
     * @empty(message)
     * @empty(results|filter('active'))
     * ```
     *
     * @param string $args Variable à tester
     * @return string Code PHP généré
     * @throws TemplateException Si l'expression est vide ou invalide
     */
    public function compileEmpty(string $args): string
    {
        $args = trim($args);
        if ($args === '') {
            throw new TemplateException(
                'Expression manquante pour @empty. Exemple : @empty(users)'
            );
        }

        try {
            $expression = $this->resolveExpression($args);
            return sprintf("<?php if (empty(%s)): ?>\n", $expression);
        } catch (TemplateException $e) {
            throw new TemplateException(
                sprintf('Erreur dans @empty(%s) : %s', $args, $e->getMessage())
            );
        }
    }

    /**
     * Compile la directive @switch
     *
     * Exemples :
     * ```
     * @switch(status)
     * @switch(user.role)
     * @switch(type|default('unknown'))
     * ```
     *
     * @param string $args Variable à tester
     * @return string Code PHP généré
     * @throws TemplateException Si l'expression est vide ou invalide
     */
    public function compileSwitch(string $args): string
    {
        $args = trim($args);
        if ($args === '') {
            throw new TemplateException(
                'Expression manquante pour @switch. Exemple : @switch(status)'
            );
        }

        try {
            $expression = $this->resolveExpression($args);
            return sprintf("<?php switch(%s): ?>\n", $expression);
        } catch (TemplateException $e) {
            throw new TemplateException(
                sprintf('Erreur dans @switch(%s) : %s', $args, $e->getMessage())
            );
        }
    }

    /**
     * Compile la directive @case
     *
     * Exemples :
     * ```
     * @case('active')
     * @case(1)
     * @case(STATUS_PENDING)
     * ```
     *
     * @param string $args Valeur du case
     * @return string Code PHP généré
     * @throws TemplateException Si l'expression est vide ou invalide
     */
    public function compileCase(string $args): string
    {
        $args = trim($args);
        if ($args === '') {
            throw new TemplateException(
                'Expression manquante pour @case. Exemple : @case("active")'
            );
        }

        try {
            $expression = $this->resolveExpression($args);
            return sprintf("<?php case %s: ?>\n", $expression);
        } catch (TemplateException $e) {
            throw new TemplateException(
                sprintf('Erreur dans @case(%s) : %s', $args, $e->getMessage())
            );
        }
    }

    /**
     * Compile la directive @default
     *
     * @return string Code PHP généré
     */
    public function compileDefault(): string
    {
        return "<?php default: ?>\n";
    }

    /**
     * Compile la directive @endswitch
     *
     * @return string Code PHP généré
     */
    public function compileEndswitch(): string
    {
        return "<?php endswitch; ?>\n";
    }

    /**
     * Parse une expression conditionnelle en code PHP
     *
     * Cette méthode gère :
     * - Les opérateurs de comparaison (==, !=, <, >, <=, >=)
     * - Les opérateurs logiques (and, or)
     * - Les expressions complexes avec parenthèses
     * - Les appels de méthodes et accès aux propriétés
     *
     * @param string $expression Expression à parser
     * @return string Code PHP généré
     * @throws TemplateException Si l'expression est invalide
     */
    private function parseConditionals(string $expression): string
    {
        // Sépare l'expression sur les opérateurs logiques
        $parts = preg_split('/(and|or)/', $expression, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            throw new TemplateException('Expression conditionnelle invalide');
        }

        $result = '';
        foreach ($parts as $part) {
            $part = trim($part);
            
            // Opérateurs logiques
            if ($part === 'and' || $part === 'or') {
                $result .= " $part ";
                continue;
            }

            // Comparaisons
            if (preg_match('/^(.+?)\s*(==|!=|<|>|<=|>=|===|!==)\s*(.+)$/', $part, $matches)) {
                $left = $this->resolveExpression(trim($matches[1]));
                $operator = $matches[2];
                $right = $this->resolveExpression(trim($matches[3]));
                $result .= "$left $operator $right";
            } else {
                // Expression simple
                $result .= $this->resolveExpression($part);
            }
        }

        return $result;
    }
}
