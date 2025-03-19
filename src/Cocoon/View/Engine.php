<?php

namespace Cocoon\View;

use Cocoon\View\Template;
use Cocoon\View\TemplateException;
use Cocoon\View;

/**
 * Moteur principal du système de template Twide.
 *
 * Cette classe gère le rendu des templates, l'ajout de fonctions personnalisées,
 * de filtres et de directives. Elle combine la syntaxe de Twig avec celle de Laravel Blade.
 *
 * @package Cocoon\View
 * @author Cocoon Project
 */
class Engine
{
    /**
     * Instance du gestionnaire de template
     *
     * @var Template
     */
    private $template = null;

    /**
     * Données partagées avec tous les templates
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    private array $globals = ['app' => []];

    /**
     * Initialise une nouvelle instance du moteur de template
     *
     * @param array<string, mixed> $config Configuration du moteur (chemins des vues, cache, etc.)
     * @throws TemplateException Si la configuration n'est pas fournie
     */
    public function __construct(array $config = [])
    {
        if (count($config) == 0) {
            throw new TemplateException('Le paramètre $config de type array n\'est pas renseigné');
        }
        $this->template = new Template($config);
        $this->globals['app'] = ['query' => $_GET, 'session' => $_SESSION , 'input' => $_POST];
        $this->with($this->globals);
    }
    /**
     * Crée une nouvelle instance du moteur de template
     *
     * @param array<string, mixed> $config Configuration du moteur
     * @return self Instance du moteur de template
     */
    public static function create(array $config = [])
    {
        return new Engine($config);
    }
    /**
     * Effectue le rendu d'un template avec les données fournies
     *
     * @param string $__template Chemin du template à rendre
     * @param array<string, mixed> $__data Données spécifiques pour ce rendu
     * @return string Le contenu HTML généré
     */
    public function render($__template, $__data = []) :string
    {
        $__data = array_merge($this->data, $__data);
        return $this->template->render($__template, $__data);
    }
    /**
     * Ajoute des données partagées pour tous les templates
     *
     * @param string|array<string, mixed> $data Nom de la variable ou tableau de données
     * @param mixed $value Valeur de la variable (si $data est une chaîne)
     * @return self Pour le chaînage des méthodes
     */
    public function with($data, $value = '')
    {
        if (is_array($data)) {
            $this->data = array_merge($this->data, $data);
        } else {
            $this->data[$data] = $value;
        }
        return $this;
    }
    /**
     * Ajoute un filtre personnalisé pour les variables dans les templates
     *
     * @param string $name Nom du filtre
     * @param callable|string $callback Fonction de callback du filtre
     * @return self Pour le chaînage des méthodes
     */
    public function addFilter($name, $callback)
    {
        $this->template->getFunction()->addFilter($name, $callback);
        return $this;
    }
    /**
     * Ajoute une fonction personnalisée utilisable dans les templates
     *
     * @param string $name Nom de la fonction
     * @param callable $callback Fonction de callback
     * @return self Pour le chaînage des méthodes
     */
    public function addFunction($name, callable $callback)
    {
        $this->template->getFunction()->addFunction($name, $callback);
        return $this;
    }
    /**
     * Ajoute une directive personnalisée pour étendre la syntaxe du template
     *
     * @param string $name Nom de la directive (sans le @ préfixe)
     * @param callable $callback Fonction qui génère le code PHP
     * @return self Pour le chaînage des méthodes
     */
    public function directive($name, callable $callback)
    {
        $this->template->addDirective($name, $callback);
        return $this;
    }
    /**
     * Ajoute une directive conditionnelle personnalisée
     *
     * Cette méthode crée automatiquement les directives @nomCondition, @elsenomCondition et @endnomCondition
     *
     * @param string $name Nom de la condition
     * @param callable $callback Fonction qui évalue la condition
     * @return self Pour le chaînage des méthodes
     */
    public function if($name, callable $callback)
    {
        $this->template->setCondition($name, $callback);
        
        // Directive principale (@if...)
        $this->directive($name, function ($expression) use ($name) {
            $expression = $this->parseExpression($expression);
            if (empty($expression)) {
                return "<?php if (\$this->check('{$name}')): ?>";
            }
            return "<?php if (\$this->check('{$name}', {$expression})): ?>";
        });

        // Directive else (@else...)
        $this->directive('else' . $name, function ($expression) use ($name) {
            $expression = $this->parseExpression($expression);
            if (empty($expression)) {
                return "<?php elseif (\$this->check('{$name}')): ?>";
            }
            return "<?php elseif (\$this->check('{$name}', {$expression})): ?>";
        });

        // Directive de fin (@end...)
        $this->directive('end' . $name, function () {
            return '<?php endif; ?>';
        });

        return $this;
    }

    /**
     * Parse une expression de condition pour la rendre évaluable
     *
     * @param string|null $expression L'expression à parser
     * @return string L'expression parsée
     */
    private function parseExpression($expression)
    {
        if ($expression === null) {
            return '';
        }

        // Remplace les variables par leur notation PHP
        $expression = preg_replace('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b(?!\()/', '$' . '$1', $expression);

        return $expression;
    }
    /**
     * Ajoute une extension au moteur de template
     *
     * @param AbstractExtension $extension Instance de l'extension à ajouter
     * @return self Pour le chaînage des méthodes
     */
    public function addExtension(AbstractExtension $extension)
    {
        // Enregistre les variables globales
        $this->with($extension->getWiths());

        // Enregistre les filtres
        foreach ($extension->getFilters() as $name => $callback) {
            $this->addFilter($name, $callback);
        }

        // Enregistre les fonctions
        foreach ($extension->getFunctions() as $name => $callback) {
            $this->addFunction($name, $callback);
        }

        // Enregistre les directives
        foreach ($extension->getDirectives() as $name => $callback) {
            $this->directive($name, $callback);
        }

        // Enregistre les conditions
        foreach ($extension->getIfs() as $name => $callback) {
            $this->if($name, $callback);
        }

        return $this;
    }

    public function addGlobal(string $name, $value): void
    {
        $this->globals['app'][$name] = $value;
        $this->with($this->globals);
    }

    public function getGlobals(): array
    {
        return $this->globals;
    }
}
