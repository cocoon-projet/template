<?php

namespace Cocoon\View;

use Cocoon\View\Compiler\Compiler;
use Cocoon\View\Features\TemplateComponentTrait;
use Cocoon\View\TemplateException;
use Cocoon\View\FunctionsTemplate;

/**
 * Classe principale de gestion des templates Twide
 *
 * Cette classe est responsable de :
 * - La compilation des templates .tpl en fichiers PHP
 * - La gestion du cache des templates
 * - L'exécution des directives personnalisées
 * - La gestion des layouts et des sections
 *
 * @package Cocoon\View
 * @author Cocoon Project
 */
class Template
{
    use TemplateComponentTrait;

    /**
     * Gestionnaire de fichiers templates
     *
     * @var FileTemplate
     */
    protected FileTemplate $file;

    /**
     * Gestionnaire des fonctions et filtres
     *
     * @var FunctionsTemplate
     */
    protected FunctionsTemplate $function;

    /**
     * Liste des directives personnalisées
     *
     * @var array<string, callable>
     */
    protected array $customDirectives = [];

    /**
     * Liste des conditions personnalisées pour les directives @if
     *
     * @var array<string, callable>
     */
    protected array $conditions = [];

    /**
     * Initialise une nouvelle instance du gestionnaire de templates
     *
     * @param array<string, mixed> $config Configuration (chemins des templates, cache, etc.)
     * @throws TemplateException Si la configuration est invalide
     */
    public function __construct(array $config)
    {
        if (empty($config)) {
            throw new TemplateException('La configuration du template est invalide');
        }
        $this->file = new FileTemplate($config);
        $this->function = new FunctionsTemplate();
    }

    /**
     * Compile un template en PHP
     *
     * @param string $__template Chemin du template à compiler
     * @throws TemplateException Si le template n'existe pas
     */
    public function compile(string $__template): void
    {
        $content = $this->getCompiler()->compile($this->file->read($__template));
        $this->file->put($__template, $content);
    }

    /**
     * Compile et exécute un template avec les données fournies
     *
     * @param string $__template Chemin du template
     * @param array<string, mixed> $data Variables à passer au template
     * @throws TemplateException Si le template n'existe pas
     * @return string Le contenu HTML généré
     */
    protected function tokenize(string $__template, array $data = []): string
    {
        if (!is_file($this->file->getPathTemplate($__template))) {
            throw new TemplateException(
                sprintf('Le template "%s" est introuvable', $__template)
            );
        }

        $this->data = $data;
        $this->data['__default__'] = null;

        ob_start();
        if (!$this->file->existsAndIsExpired($__template)) {
            $this->compile($__template);
        }

        extract($this->data, EXTR_SKIP);
        include $this->file->getPathTemplateCache($__template);
        $content = ob_get_clean();

        // Gestion du layout si défini
        if ($this->layoutName !== null) {
            $this->data = array_merge($this->data, $this->layoutData);
            ob_start();
            if (!$this->file->existsAndIsExpired($this->layoutName)) {
                $this->compile($this->layoutName);
            }
            extract($this->data, EXTR_SKIP);
            include $this->file->getPathTemplateCache($this->layoutName);
            $content = ob_get_clean();
        }

        return $content;
    }

    /**
     * Effectue le rendu d'un template
     *
     * @param string $__template Chemin du template
     * @param array<string, mixed> $data Variables à passer au template
     * @return string Le contenu HTML généré
     */
    public function render(string $__template, array $data = []): string
    {
        return $this->tokenize($__template, $data);
    }

    /**
     * Retourne une nouvelle instance du compilateur
     */
    protected function getCompiler(): Compiler
    {
        return new Compiler($this);
    }

    /**
     * Retourne le gestionnaire de fonctions et filtres
     */
    public function getFunction(): FunctionsTemplate
    {
        return $this->function;
    }

    /**
     * Exécute une fonction dans le template
     * Exemple: {{ :asset:'path/to/asset' }}
     *
     * @param string $function Nom de la fonction
     * @param mixed ...$args Arguments de la fonction
     * @return mixed Résultat de la fonction
     */
    public function func(string $function, mixed ...$args): mixed
    {
        return $this->getFunction()->getFunction($function, ...$args);
    }

    /**
     * Applique un filtre sur une donnée du template
     *
     * @param string $filter Nom du filtre
     * @param mixed ...$args Arguments du filtre
     * @return mixed Résultat du filtre
     */
    public function filter(string $filter, mixed ...$args): mixed
    {
        return $this->getFunction()->getFilter($filter, ...$args);
    }

    /**
     * Ajoute une directive personnalisée
     *
     * @param string $name Nom de la directive (sans @)
     * @param callable $callback Fonction de traitement
     */
    public function addDirective(string $name, callable $callback): void
    {
        $this->customDirectives['@' . $name] = $callback;
    }

    /**
     * Retourne toutes les directives personnalisées
     *
     * @return array<string, callable>
     */
    public function getCustomDirectives(): array
    {
        return $this->customDirectives;
    }

    /**
     * Évalue une condition personnalisée
     *
     * @param string $name Nom de la condition
     * @param mixed ...$parameters Paramètres de la condition
     * @return mixed Résultat de la condition
     * @throws TemplateException Si la condition n'existe pas
     */
    public function check(string $name, mixed ...$parameters): mixed
    {
        if (!isset($this->conditions[$name])) {
            throw new TemplateException(
                sprintf('La condition "%s" n\'existe pas', $name)
            );
        }
        return $this->conditions[$name](...$parameters);
    }

    /**
     * Ajoute une condition personnalisée pour les directives @if
     *
     * @param string $name Nom de la condition
     * @param callable $callback Fonction d'évaluation
     */
    public function setCondition(string $name, callable $callback): void
    {
        $this->conditions[$name] = $callback;
    }

    /**
     * Retourne les données partagées avec le template
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Nettoie les sections et les piles avant la destruction
     */
    public function __destruct()
    {
        $this->flushSections();
    }
}
