<?php

namespace Cocoon\View\Compiler\Features;

use Cocoon\View\TemplateException;

/**
 * Trait pour la compilation des directives d'inclusion de templates
 *
 * Ce trait gère la compilation des directives permettant d'inclure d'autres templates
 * de manière dynamique. Il supporte les fonctionnalités suivantes :
 *
 * 1. Inclusion simple (@include) :
 *    ```
 *    @include('header')
 *    @include('partials.menu')
 *    ```
 *
 * 2. Inclusion avec données (@include with) :
 *    ```
 *    @include('user/profile' with user)
 *    @include('form/input' with ['type' => 'text', 'name' => 'email'])
 *    @include('stats' with ['count' => users|length, 'title' => 'Utilisateurs'])
 *    ```
 *
 * 3. Inclusion répétée (@each) :
 *    ```
 *    @each('user/card', users, 'user')
 *
 *    @each('comment', comments, 'comment', 'comments/empty')
 *
 *    @each('product/item', products, 'product', 'raw|Aucun produit disponible')
 *    ```
 *
 * Sécurité :
 * - Les chemins des templates sont échappés pour éviter les inclusions malveillantes
 * - Les données passées sont validées pour éviter les injections
 * - Les templates inclus héritent du contexte de sécurité du template parent
 *
 * @package Cocoon\View\Compiler\Features
 */
trait CompileIncludes
{
    /**
     * Compile la directive @include
     *
     * Cette directive permet d'inclure un autre template avec ou sans données
     * supplémentaires. Le template inclus a accès à toutes les variables du
     * template parent, plus les données additionnelles spécifiées.
     *
     * Syntaxes supportées :
     * ```
     * @include('template')
     * @include('dir/template')
     * @include('template' with data)
     * @include('template' with ['key' => value])
     * @include(variable)
     * @include(getTemplate())
     * ```
     *
     * @param string $args Arguments de la directive
     * @return string Code PHP généré
     * @throws TemplateException Si la syntaxe est invalide ou les arguments sont manquants
     */
    public function compileInclude(string $args): string
    {
        $args = trim($args);
        if ($args === '') {
            throw new TemplateException(
                'Arguments manquants pour @include. Exemple : @include(\'header\')'
            );
        }

        try {
            // Inclusion avec données additionnelles
            if (str_contains($args, ' with ')) {
                return $this->compileIncludeWithData($args);
            }

            // Inclusion simple
            $template = $this->resolveExpression($args);
            return sprintf("<?php \$this->insert(%s); ?>\n", $template);
        } catch (TemplateException $e) {
            throw new TemplateException(
                sprintf('Erreur dans @include(%s) : %s', $args, $e->getMessage())
            );
        }
    }

    /**
     * Compile une inclusion de template avec données additionnelles
     *
     * @param string $args Arguments complets de la directive
     * @return string Code PHP généré
     * @throws TemplateException Si la syntaxe est invalide
     */
    private function compileIncludeWithData(string $args): string
    {
        if (!preg_match('/^(.+?)\s+with\s+([^\t\r\n}]+)$/', $args, $matches)) {
            throw new TemplateException(
                'Syntaxe invalide pour @include with. Format attendu : @include(\'template\' with data)'
            );
        }

        $template = $this->resolveExpression(trim($matches[1]));
        $data = $this->resolveExpression(trim($matches[2]));

        return sprintf("<?php \$this->insert(%s, %s); ?>\n", $template, $data);
    }

    /**
     * Compile la directive @each
     *
     * Cette directive permet d'inclure un template pour chaque élément d'une collection.
     * Elle offre également la possibilité de spécifier un template ou un message
     * à afficher lorsque la collection est vide.
     *
     * Syntaxe : @each(template, items, itemName[, empty])
     * - template : Chemin du template à inclure pour chaque élément
     * - items : Collection à itérer
     * - itemName : Nom de la variable pour chaque élément
     * - empty (optionnel) : Template ou message pour le cas vide
     *
     * Exemples :
     * ```
     * @each('user/card', $users, 'user')
     * @each('comment', $post->comments, 'comment', 'comments/empty')
     * @each('product', $products, 'product', 'raw|Aucun produit')
     * ```
     *
     * @param string $args Arguments de la directive
     * @return string Code PHP généré
     * @throws TemplateException Si la syntaxe est invalide ou les arguments sont manquants
     */
    public function compileEach(string $args): string
    {
        $args = trim($args);
        if ($args === '') {
            throw new TemplateException(
                'Arguments manquants pour @each. Format : @each(\'template\', items, \'itemName\'[, \'empty\'])'
            );
        }

        try {
            $arguments = $this->parseEachArguments($args);
            return $this->generateEachCode(...$arguments);
        } catch (TemplateException $e) {
            throw new TemplateException(
                sprintf('Erreur dans @each(%s) : %s', $args, $e->getMessage())
            );
        }
    }

    /**
     * Parse les arguments de la directive @each
     *
     * @param string $args Arguments bruts de la directive
     * @return array{string, string, string, string|null} [template, items, itemName, empty]
     * @throws TemplateException Si les arguments sont invalides
     */
    private function parseEachArguments(string $args): array
    {
        $arguments = array_map('trim', explode(',', $args));
        
        if (count($arguments) < 3) {
            throw new TemplateException(
                'Nombre incorrect d\'arguments pour @each. Minimum requis : template, items, itemName'
            );
        }

        $template = $this->resolveExpression($arguments[0]);
        $items = $this->resolveExpression($arguments[1]);
        $itemName = trim($arguments[2], '\'');
        
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $itemName)) {
            throw new TemplateException(
                sprintf('Nom de variable invalide pour @each : %s', $itemName)
            );
        }

        $empty = isset($arguments[3]) ? trim($arguments[3]) : null;

        return [$template, $items, $itemName, $empty];
    }

    /**
     * Génère le code PHP pour la directive @each
     *
     * @param string $template Template à inclure
     * @param string $items Collection à itérer
     * @param string $itemName Nom de la variable pour chaque élément
     * @param string|null $empty Template ou message pour le cas vide
     * @return string Code PHP généré
     */
    private function generateEachCode(
        string $template,
        string $items,
        string $itemName,
        ?string $empty
    ): string {
        $code = '';
        $indent = 0;

        // Gestion du cas vide
        if ($empty !== null) {
            $code .= sprintf("<?php if (empty(%s)): ?>\n", $items);
            $indent = 4;

            // Texte brut ou template pour le cas vide
            if (str_starts_with(trim($empty, '\''), 'raw|')) {
                $text = explode('|', trim($empty, '\''), 2)[1];
                $code .= str_repeat(' ', $indent) . htmlspecialchars($text) . "\n";
            } else {
                $code .= sprintf(
                    "%s<?php \$this->insert(%s); ?>\n",
                    str_repeat(' ', $indent),
                    $this->resolveExpression($empty)
                );
            }

            $code .= "<?php else: ?>\n";
        }

        // Boucle principale
        $code .= str_repeat(' ', $indent);
        $code .= sprintf("<?php foreach (%s as \$%s): ?>\n", $items, $itemName);
        
        // Inclusion du template pour chaque élément
        $code .= str_repeat(' ', $indent + 4);
        $code .= sprintf(
            "<?php \$this->insert(%s, ['%s' => \$%s]); ?>\n",
            $template,
            $itemName,
            $itemName
        );

        // Fermeture des blocs
        $code .= str_repeat(' ', $indent) . "<?php endforeach; ?>\n";
        if ($empty !== null) {
            $code .= "<?php endif; ?>\n";
        }

        return $code;
    }
}
