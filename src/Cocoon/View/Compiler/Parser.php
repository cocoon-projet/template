<?php

namespace Cocoon\View\Compiler;

use ReflectionProperty;
use Cocoon\View\Template;
use InvalidArgumentException;
use Cocoon\View\TemplateException;
use Cocoon\View\Compiler\Features\CompileEchos;
use Cocoon\View\Compiler\Features\CompileLoops;
use Cocoon\View\Compiler\Features\CompileOther;
use Cocoon\View\Compiler\Features\CompileIncludes;
use Cocoon\View\Compiler\Features\CompileConditionals;
use Cocoon\View\Compiler\Features\CompileLayoutsAndStacks;

/**
 * Parseur du moteur de template Twide
 *
 * Cette classe est responsable de :
 * - L'analyse et la transformation des expressions {{ }} en code PHP
 * - La compilation des directives @directive en code PHP
 * - La gestion des filtres et des fonctions dans les templates
 * - Le support des layouts et des sections
 * - La gestion des boucles et des conditions
 *
 * Syntaxe supportée :
 * - Variables : {{ variable }}, {{{ html_variable }}}
 * - Filtres : {{ variable|upper }}, {{ variable|substr:0,1 }}
 * - Directives : @if, @foreach, @section, etc.
 * - Commentaires : {* commentaire *}
 * - JavaScript : @script ... @endscript
 *
 * @package Cocoon\View\Compiler
 */
class Parser
{
    /**
     * Délimiteurs des commentaires dans les templates
     *
     * @var array<int, string>
     */
    protected array $comment = ['{*', '*}'];

    /**
     * Délimiteurs HTML qui remplacent les commentaires
     *
     * @var array<int, string>
     */
    protected array $replace = ['<!--', '-->'];

    /**
     * Code JavaScript extrait des templates
     *
     * @var array<int, string>
     */
    protected array $javascripts = [];

    /**
     * Liste des directives personnalisées
     *
     * @var array<string, callable>
     */
    protected array $customDirectives = [];

    /**
     * Liste des filtres disponibles
     *
     * @var array<string, callable|string>
     */
    protected array $filters = [];

    /**
     * Liste des fonctions disponibles
     *
     * @var array<string, callable>
     */
    protected array $functions = [];

    /**
     * Mots réservés du langage
     *
     * @var array<int, string>
     */
    protected array $reservedWords = [
        'true', 'false', 'null'
    ];

    /**
     * Données partagées avec le template
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    use
        CompileEchos,
        CompileConditionals,
        CompileIncludes,
        CompileLayoutsAndStacks,
        CompileLoops,
        CompileOther;

    /**
     * Initialise le parseur avec une instance de Template
     *
     * @param Template $template Instance du moteur de template
     */
    public function __construct(Template $template)
    {
        $this->customDirectives = $template->getCustomDirectives();
        $this->filters = $template->getFunction()->getFilters();
        $this->functions = $template->getFunction()->getFunctions();
        $this->data = $template->getData();
    }

    /**
     * Définit les données pour les tests unitaires
     *
     * @param array<string, mixed> $data Données de test
     */
    public function setData(array $data = []): void
    {
        $this->data = $data;
    }

    /**
     * Parse et compile un template en code PHP
     *
     * Cette méthode effectue les opérations suivantes :
     * 1. Normalise les fins de ligne
     * 2. Convertit les commentaires en commentaires HTML
     * 3. Extrait et stocke le code JavaScript
     * 4. Compile les expressions et directives en code PHP
     *
     * @param string $code Code source du template
     * @return string Code PHP compilé
     * @throws TemplateException Si une erreur de syntaxe est détectée
     */
    public function parse(string $code): string
    {
        if (empty($code)) {
            return '';
        }

        try {
            $content = str_replace(["\r\n", "\r"], "\n", $code);
            
            // Remplace les commentaires par des commentaires HTML
            $content = str_replace($this->comment, $this->replace, $content);
            
            // Extrait le code JavaScript
            if (preg_match_all('!@script(.*?)@endscript!s', $content, $matches)) {
                $this->javascripts = $matches[1];
                $content = preg_replace("!@script(.*?)@endscript!s", '@javascript(code)', $content);
            }
            
            // Parse les expressions et les directives
            $result = preg_replace_callback(
                '#(@?{{{?\s*([^\t\r\n}]+)\s*}?}})|(@[a-z]+\s*(?:\([^\t\r\n}]+\))?)#s',
                [$this, 'callback'],
                $content
            );

            if ($result === null) {
                throw new TemplateException('Erreur lors de la compilation du template');
            }
            
            return $result;
        } catch (InvalidArgumentException $e) {
            throw new TemplateException('Erreur de syntaxe : ' . $e->getMessage());
        }
    }

    /**
     * Traite les expressions trouvées dans le template
     *
     * @param array<int, string> $matches Résultats de la regex
     * @return string Code PHP généré
     * @throws TemplateException Si l'expression n'est pas reconnue
     */
    protected function callback(array $matches): string
    {
        [$tag] = $matches;

        // Expression d'affichage {{ }} ou {{{ }}}
        if (preg_match('/(@?[{]+)\s*(.*?)\s*([}]+)$/', $tag, $code)) {
            return $this->parseEchoTags($code);
        }
        
        // Directive @directive
        if (preg_match('/@([a-z]+)\s*(?:\((.*)\))?/', $tag, $code)) {
            return $this->parseArobaseTags($code);
        }

        throw new TemplateException(sprintf(
            'Expression non reconnue : %s. Utilisez {{ var }} pour les variables ou @directive pour les directives.',
            $tag
        ));
    }

    /**
     * Parse les expressions d'affichage {{ }} et {{{ }}}
     *
     * @param array<int, string> $code Parties de l'expression
     * @return string Code PHP généré
     * @throws TemplateException Si la syntaxe est invalide
     */
    protected function parseEchoTags(array $code): string
    {
        if ($code[1] === '@{{' && $code[3] === '}}') {
            return '{{ ' . $code[2] . ' }}';
        }

        if ($code[1] === '{{' && $code[3] === '}}') {
            return $this->compileEcho($code[2]);
        }

        if ($code[1] === '{{{' && $code[3] === '}}}') {
            return $this->compileNoEscape($code[2]);
        }

        throw new TemplateException(
            'Syntaxe invalide pour l\'expression d\'affichage. Utilisez {{ var }} pour un affichage échappé ' .
            'ou {{{ var }}} pour un affichage non échappé.'
        );
    }

    /**
     * Parse les directives @directive
     *
     * @param array<int, string> $code Parties de la directive
     * @return string Code PHP généré
     * @throws TemplateException Si la directive n'existe pas ou est invalide
     */
    protected function parseArobaseTags(array $code): string
    {
        if (empty($code[1])) {
            throw new TemplateException('Directive invalide');
        }

        $directive = $code[1];
        $compileMethod = 'compile' . ucfirst($directive);
        $hasArguments = isset($code[2]);
        $arguments = $hasArguments ? $code[2] : null;

        // Cas spécial pour le JavaScript
        if ($compileMethod === 'compileJavascript') {
            return $this->$compileMethod($this->javascripts);
        }

        // Méthode de compilation interne
        if (method_exists($this, $compileMethod)) {
            return $hasArguments ? $this->$compileMethod($arguments) : $this->$compileMethod();
        }

        // Directive personnalisée
        $customDirective = '@' . $directive;
        if (isset($this->customDirectives[$customDirective])) {
            $handler = $this->customDirectives[$customDirective];
            return $hasArguments ? $handler($this->resolveExpression($arguments)) : $handler();
        }

        throw new TemplateException(sprintf(
            'La directive @%s n\'existe pas. Les directives disponibles sont : @if, @foreach, @section, etc.',
            $directive
        ));
    }

    /**
     * Résout une expression en code PHP valide
     *
     * Gère les types suivants :
     * - Booléens et null
     * - Nombres et expressions mathématiques
     * - Tableaux et accès aux indices
     * - Objets et accès aux propriétés
     * - Chaînes de caractères
     * - Variables simples
     *
     * @param string|null $code Expression à résoudre
     * @return string Code PHP généré
     * @throws TemplateException Si l'expression est invalide
     */
    protected function resolveExpression(?string $code): string
    {
        if ($code === null || trim($code) === '') {
            throw new TemplateException('Expression vide');
        }

        $code = trim($code);

        // Valeurs booléennes ou null
        if (in_array($code, $this->reservedWords, true)) {
            return $code;
        }

        // Tableaux littéraux
        if (preg_match('/^\[(.*)\]$/', $code, $matches)) {
            if (empty($matches[1])) {
                return '[]';
            }
            $items = array_map('trim', explode(',', $matches[1]));
            $resolvedItems = array_map(function ($item) {
                return $this->resolveExpression($item);
            }, $items);
            return '[' . implode(', ', $resolvedItems) . ']';
        }

        // Nombres et expressions mathématiques
        if (preg_match('/^-?\d+(?:\.\d+)?$/', $code)) {
            return $code;
        }

        // Accès aux indices de tableau
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\[(.+?)\]$/', $code, $matches)) {
            return '$' . $matches[1] . '[' . $this->resolveExpression($matches[2]) . ']';
        }

        // Accès aux propriétés d'objet ou tableau multidimensionnel
        if (str_contains($code, '.')) {
            return $this->expressionDataType($code);
        }

        // Chaînes de caractères
        if (preg_match('/^([\'"])(.*?)\1$/', $code)) {
            return $code;
        }

        // Variables simples
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $code)) {
            return '$' . $code;
        }

        throw new TemplateException(sprintf('Expression invalide : %s', $code));
    }

    /**
     * Résout une expression contenant des accès à des propriétés d'objet ou de tableau
     *
     * Cette méthode gère :
     * - L'accès aux propriétés publiques d'objets
     * - L'appel de méthodes d'objets
     * - L'accès aux indices de tableaux multidimensionnels
     * - La résolution des getters (méthode get*)
     *
     * Exemples :
     * - user.name -> $user->name
     * - user.getName() -> $user->getName()
     * - array.key -> $array['key']
     *
     * @param string $data Expression à résoudre (ex: "user.name")
     * @return string Code PHP généré
     * @throws TemplateException Si l'expression est invalide ou l'accès impossible
     */
    protected function expressionDataType(string $data): string
    {
        if (!str_contains($data, '.')) {
            throw new TemplateException('L\'expression doit contenir au moins un point (.)');
        }

        $parts = explode('.', $data);
        $variable = array_shift($parts);
        $expression = '$' . $variable;

        if (!isset($this->data[$variable])) {
            throw new TemplateException(sprintf(
                'La variable "%s" n\'est pas définie dans le contexte du template',
                $variable
            ));
        }

        $target = $this->data[$variable];

        if (is_object($target)) {
            foreach ($parts as $part) {
                // Appel de méthode avec arguments : user.format(arg1, arg2)
                if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/', $part, $matches)) {
                    $method = $matches[1];
                    $args = $this->resolveMethodArguments($matches[2]);
                    
                    if (!method_exists($target, $method)) {
                        throw new TemplateException(sprintf(
                            'La méthode "%s" n\'existe pas dans la classe %s',
                            $method,
                            get_class($target)
                        ));
                    }
                    
                    $expression .= sprintf('->%s(%s)', $method, $args);
                    continue;
                }

                // Propriété publique
                if (property_exists($target, $part)) {
                    try {
                        if ($this->propertyIsPublic($target, $part)) {
                            $expression .= '->' . $part;
                            continue;
                        }
                    } catch (\ReflectionException $e) {
                        throw new TemplateException(sprintf(
                            'Erreur lors de l\'accès à la propriété "%s" : %s',
                            $part,
                            $e->getMessage()
                        ));
                    }
                }

                // Méthode sans arguments
                if (method_exists($target, $part)) {
                    $expression .= '->' . $part . '()';
                    continue;
                }

                // Getter
                $getter = 'get' . ucfirst($part);
                if (method_exists($target, $getter)) {
                    $expression .= '->' . $getter . '()';
                    continue;
                }

                throw new TemplateException(sprintf(
                    'Impossible d\'accéder à "%s" : la propriété n\'est pas publique et aucun getter n\'existe',
                    $part
                ));
            }
        } elseif (is_array($target)) {
            foreach ($parts as $part) {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $part)) {
                    throw new TemplateException(sprintf(
                        'Clé de tableau invalide : "%s". Seuls les caractères alphanumériques et _ sont autorisés',
                        $part
                    ));
                }
                $expression .= sprintf('[%s]', var_export($part, true));
            }
        } else {
            throw new TemplateException(sprintf(
                'Impossible d\'accéder aux propriétés : %s n\'est ni un objet ni un tableau',
                $variable
            ));
        }

        return $expression;
    }

    /**
     * Résout les arguments d'une méthode en code PHP valide
     *
     * @param string $arguments Liste d'arguments séparés par des virgules
     * @return string Arguments résolus et formatés pour PHP
     * @throws TemplateException Si un argument est invalide
     */
    protected function resolveMethodArguments(string $arguments): string
    {
        if (trim($arguments) === '') {
            return '';
        }

        $args = array_map('trim', explode(',', $arguments));
        $resolved = [];

        foreach ($args as $arg) {
            try {
                $resolved[] = $this->resolveExpression($arg);
            } catch (TemplateException $e) {
                throw new TemplateException(sprintf(
                    'Argument invalide dans l\'appel de méthode : %s',
                    $e->getMessage()
                ));
            }
        }

        return implode(', ', $resolved);
    }

    /**
     * Vérifie si une propriété d'un objet est publique
     *
     * @param object $object Objet à vérifier
     * @param string $property Nom de la propriété
     * @return bool true si la propriété est publique
     * @throws \ReflectionException Si la propriété n'existe pas ou en cas d'erreur de réflexion
     */
    private function propertyIsPublic(object $object, string $property): bool
    {
        return (new ReflectionProperty($object, $property))->isPublic();
    }
}
