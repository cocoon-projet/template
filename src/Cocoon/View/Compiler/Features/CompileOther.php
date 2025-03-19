<?php

namespace Cocoon\View\Compiler\Features;

use Cocoon\View\TemplateException;

/**
 * Trait pour la compilation des directives diverses
 *
 * Ce trait gère la compilation des directives qui ne rentrent pas dans les autres catégories :
 * - @set : Définition de variables dans le template
 *
 * @package Cocoon\View\Compiler\Features
 */
trait CompileOther
{
    /**
     * Compile la directive @set
     *
     * Permet de définir une variable dans le template.
     * La syntaxe doit être : @set variable = valeur
     *
     * Exemples :
     * - @set title = 'Mon titre'
     * - @set count = items.length
     * - @set total = price * quantity
     *
     * @param string $args Expression d'assignation
     * @return string Code PHP généré
     * @throws TemplateException Si la syntaxe est invalide
     */
    public function compileSet(string $args): string
    {
        if (empty(trim($args))) {
            throw new TemplateException('Expression manquante pour @set');
        }

        if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*(=)\s*([^\t\r\n}]+)$/', trim($args), $matches)) {
            throw new TemplateException('Syntaxe invalide pour @set. Format attendu : @set variable = valeur');
        }

        return sprintf(
            '<?php %s %s %s; ?>' . PHP_EOL,
            $this->resolveExpression($matches[1]),
            $matches[2],
            $this->resolveExpression(trim($matches[3]))
        );
    }
}
