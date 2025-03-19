<?php

namespace Cocoon\View\Compiler\Features;

use Cocoon\View\TemplateException;

/**
 * Trait pour la compilation des structures de boucle dans les templates
 *
 * Ce trait gère la compilation des directives de boucle en code PHP.
 * Il supporte les structures suivantes :
 *
 * 1. Boucle foreach :
 *    ```
 *    @foreach(users as user)
 *      {{ user.name }}
 *    @endforeach
 *
 *    @foreach(users as key => user)
 *      #{{ key }}: {{ user.name }}
 *    @endforeach
 *
 *    @foreach(users as user if user.active)
 *      {{ user.name }} (actif)
 *    @endforeach
 *    ```
 *
 * 2. Boucle forelse (foreach avec cas vide) :
 *    ```
 *    @forelse(users as user)
 *      {{ user.name }}
 *    @empty
 *      Aucun utilisateur
 *    @endforelse
 *    ```
 *
 * 3. Boucle for simplifiée :
 *    ```
 *    @for(i in 0 count 10)
 *      Item #{{ i }}
 *    @endfor
 *
 *    @for(j in 1 count items)
 *      Page {{ j }}
 *    @endfor
 *    ```
 *
 * 4. Boucle while :
 *    ```
 *    @while(hasNextPage())
 *      {{ loadNextPage() }}
 *    @endwhile
 *    ```
 *
 * @package Cocoon\View\Compiler\Features
 */
trait CompileLoops
{
    /**
     * Indique si une condition if est utilisée dans une boucle foreach
     *
     * Cette propriété est utilisée pour gérer correctement la fermeture
     * des blocs PHP quand un @foreach contient une condition.
     *
     * @var bool
     */
    protected bool $ifForeach = false;

    /**
     * Compile la directive @foreach
     *
     * Cette directive permet d'itérer sur une collection avec trois syntaxes possibles :
     * 1. Itération simple : @foreach(users as user)
     * 2. Avec clé : @foreach(users as key => value)
     * 3. Avec condition : @foreach(users as user if user.active)
     *
     * Exemples :
     * ```
     * @foreach(users as user)
     * @foreach(users as key => user)
     * @foreach(users|filter('active') as user)
     * @foreach(users as user if user.age >= 18)
     * ```
     *
     * @param string $args Arguments de la directive
     * @return string Code PHP généré
     * @throws TemplateException Si la syntaxe est invalide ou les arguments sont manquants
     */
    public function compileForeach(string $args): string
    {
        $args = trim($args);
        if ($args === '') {
            throw new TemplateException(
                'Arguments manquants pour @foreach. Exemple : @foreach(users as user)'
            );
        }

        try {
            // Gestion de la condition if
            if (str_contains($args, ' if ')) {
                [$foreachPart, $ifPart] = $this->splitForeachIf($args);
                $this->ifForeach = true;
                $foreachCode = $this->compileForeachPart($foreachPart);
                $ifCode = $this->compileIf($ifPart);
                return $foreachCode . $ifCode;
            }

            return $this->compileForeachPart($args);
        } catch (TemplateException $e) {
            throw new TemplateException(
                sprintf('Erreur dans @foreach(%s) : %s', $args, $e->getMessage())
            );
        }
    }

    /**
     * Sépare les parties foreach et if d'une directive @foreach avec condition
     *
     * @param string $args Arguments complets de la directive
     * @return array{0: string, 1: string} Tableau contenant [partie_foreach, partie_if]
     * @throws TemplateException Si la syntaxe est invalide
     */
    private function splitForeachIf(string $args): array
    {
        $parts = preg_split("/\s+if\s+/", $args, 2);
        if (!is_array($parts) || count($parts) !== 2) {
            throw new TemplateException(
                'Syntaxe invalide pour @foreach avec if. Format attendu : collection as item if condition'
            );
        }

        return [trim($parts[0]), trim($parts[1])];
    }

    /**
     * Compile la partie foreach d'une directive @foreach
     *
     * @param string $args Arguments de la partie foreach
     * @return string Code PHP généré
     * @throws TemplateException Si la syntaxe est invalide
     */
    private function compileForeachPart(string $args): string
    {
        $foreachArgs = $this->parseForeachArgs($args);
        return sprintf("<?php foreach(%s): ?>\n", $foreachArgs);
    }

    /**
     * Parse les arguments d'une directive @foreach
     *
     * Cette méthode analyse la syntaxe "collection as item" ou "collection as key => value"
     * et génère le code PHP correspondant.
     *
     * @param string $args Arguments de la directive
     * @return string Expression PHP générée
     * @throws TemplateException Si la syntaxe est invalide
     */
    protected function parseForeachArgs(string $args): string
    {
        if (!preg_match('/^(.+?)\s+as\s+(\$?\w+)(?:\s*=>\s*(\$?\w+))?$/', $args, $matches)) {
            throw new TemplateException(
                'Syntaxe invalide pour @foreach. Formats attendus :' . PHP_EOL .
                '- collection as item' . PHP_EOL .
                '- collection as key => value'
            );
        }

        try {
            $collection = $this->expressionFilters(trim($matches[1]));
            
            // Gestion de la variable de valeur
            $value = $this->ensureDollarPrefix($matches[2]);
            
            // Gestion de la clé si présente
            if (isset($matches[3])) {
                $key = $value;
                $value = $this->ensureDollarPrefix($matches[3]);
                return sprintf('%s as %s => %s', $collection, $key, $value);
            }

            return sprintf('%s as %s', $collection, $value);
        } catch (TemplateException $e) {
            throw new TemplateException(
                sprintf('Erreur dans les arguments du foreach : %s', $e->getMessage())
            );
        }
    }

    /**
     * Assure qu'une variable commence par $
     *
     * @param string $var Nom de la variable
     * @return string Variable avec préfixe $
     */
    private function ensureDollarPrefix(string $var): string
    {
        return str_starts_with($var, '$') ? $var : '$' . $var;
    }

    /**
     * Compile la directive @endforeach
     *
     * Gère correctement la fermeture des blocs PHP en tenant compte
     * de la présence éventuelle d'une condition if.
     *
     * @return string Code PHP généré
     */
    public function compileEndforeach(): string
    {
        if ($this->ifForeach) {
            $this->ifForeach = false;
            return "<?php endif; endforeach; ?>\n";
        }
        return "<?php endforeach; ?>\n";
    }

    /**
     * Compile la directive @forelse
     *
     * Cette directive combine un foreach avec une gestion du cas où
     * la collection est vide. Elle doit être utilisée avec @empty
     * et @endforelse.
     *
     * Exemples :
     * ```
     * @forelse(users as user)
     *   {{ user.name }}
     * @empty
     *   Aucun utilisateur trouvé
     * @endforelse
     * ```
     *
     * @param string $args Arguments de la directive
     * @return string Code PHP généré
     * @throws TemplateException Si la syntaxe est invalide
     */
    public function compileForelse(string $args): string
    {
        $args = trim($args);
        if ($args === '') {
            throw new TemplateException(
                'Arguments manquants pour @forelse. Exemple : @forelse(users as user)'
            );
        }

        try {
            $foreachArgs = $this->parseForeachArgs($args);
            $collection = preg_replace('/\s+as\s+.*$/', '', $foreachArgs);

            return sprintf(
                "<?php if (!empty(%s)): foreach(%s): ?>\n",
                $collection,
                $foreachArgs
            );
        } catch (TemplateException $e) {
            throw new TemplateException(
                sprintf('Erreur dans @forelse(%s) : %s', $args, $e->getMessage())
            );
        }
    }

    /**
     * Compile la directive @for
     *
     * Cette directive offre une syntaxe simplifiée pour les boucles for
     * avec un compteur numérique.
     *
     * Syntaxe : @for(variable in début count fin)
     * - variable : nom de la variable de boucle
     * - début : valeur de départ (nombre ou variable)
     * - fin : valeur de fin (nombre ou variable)
     *
     * Exemples :
     * ```
     * @for(i in 0 count 10)
     * @for(page in 1 count totalPages)
     * @for(j in startIndex count items|length)
     * ```
     *
     * @param string $args Arguments de la directive
     * @return string Code PHP généré
     * @throws TemplateException Si la syntaxe est invalide
     */
    public function compileFor(string $args): string
    {
        $args = trim($args);
        if ($args === '') {
            throw new TemplateException(
                'Arguments manquants pour @for. Exemple : @for(i in 0 count 10)'
            );
        }

        if (!preg_match('/^(\w+)\s+in\s+(\d+|\w+)\s+count\s+(\d+|\w+)$/', $args, $matches)) {
            throw new TemplateException(
                'Syntaxe invalide pour @for. Format attendu : variable in début count fin'
            );
        }

        try {
            $variable = $this->ensureDollarPrefix($matches[1]);
            $start = is_numeric($matches[2]) ? $matches[2] : $this->resolveExpression($matches[2]);
            $end = is_numeric($matches[3])
                ? $matches[3]
                : sprintf('count(%s)', $this->expressionFilters($matches[3]));

            return sprintf(
                "<?php for(%s = %s; %s < %s; %s++): ?>\n",
                $variable,
                $start,
                $variable,
                $end,
                $variable
            );
        } catch (TemplateException $e) {
            throw new TemplateException(
                sprintf('Erreur dans @for(%s) : %s', $args, $e->getMessage())
            );
        }
    }

    /**
     * Compile la directive @while
     *
     * Cette directive permet de créer une boucle while classique.
     * La condition peut être une expression simple ou complexe.
     *
     * Exemples :
     * ```
     * @while(hasNextPage())
     * @while(cursor < max)
     * @while(queue|length > 0)
     * ```
     *
     * @param string $args Condition de la boucle
     * @return string Code PHP généré
     * @throws TemplateException Si l'expression est vide ou invalide
     */
    public function compileWhile(string $args): string
    {
        $args = trim($args);
        if ($args === '') {
            throw new TemplateException(
                'Expression manquante pour @while. Exemple : @while(hasNextPage())'
            );
        }

        try {
            $condition = $this->parseConditionals($args);
            return sprintf("<?php while(%s): ?>\n", trim($condition));
        } catch (TemplateException $e) {
            throw new TemplateException(
                sprintf('Erreur dans @while(%s) : %s', $args, $e->getMessage())
            );
        }
    }

    /**
     * Compile la directive @endfor
     *
     * @return string Code PHP généré
     */
    public function compileEndfor(): string
    {
        return "<?php endfor; ?>\n";
    }

    /**
     * Compile la directive @endwhile
     *
     * @return string Code PHP généré
     */
    public function compileEndwhile(): string
    {
        return "<?php endwhile; ?>\n";
    }
}
