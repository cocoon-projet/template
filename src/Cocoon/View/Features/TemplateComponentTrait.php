<?php

namespace Cocoon\View\Features;

use Cocoon\View\TemplateException;

/**
 * Trait pour la gestion des composants de template
 *
 * Ce trait fournit les fonctionnalités essentielles pour :
 * 1. La gestion des sections (@section/@endsection)
 * 2. La gestion des layouts (@layout)
 * 3. L'inclusion de templates (@include)
 * 4. La gestion des piles de contenu (@push/@endpush)
 *
 * Fonctionnalités principales :
 * ```
 * // Définition d'une section
 * @section('content')
 *   <h1>Contenu</h1>
 * @endsection
 *
 * // Utilisation d'un layout
 * @layout('layouts.main', ['title' => 'Page d\'accueil'])
 *
 * // Empilage de contenu
 * @push('scripts')
 *   <script src="app.js"></script>
 * @endpush
 * ```
 *
 * @package Cocoon\View\Features
 */
trait TemplateComponentTrait
{
    /**
     * Données pour le template courant
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Nom du layout à utiliser
     *
     * @var string|null
     */
    protected ?string $layoutName = null;

    /**
     * Données à passer au layout
     *
     * @var array<string, mixed>
     */
    protected array $layoutData = [];

    /**
     * Liste des sections définies
     *
     * @var array<string, string>
     */
    protected array $sections = [];

    /**
     * Nom de la section en cours de définition
     *
     * @var string|null
     */
    protected ?string $sectionName = null;

    /**
     * Contenu des piles (stacks)
     *
     * @var array<string, array<string>>
     */
    protected array $pushes = [];

    /**
     * Pile des noms de stack en cours
     *
     * @var array<string>
     */
    protected array $stack = [];

    /**
     * Initialise une section dans le template
     *
     * Une section permet de définir une partie du contenu qui sera
     * injectée dans un layout. Si des données sont fournies, la section
     * est automatiquement fermée après leur insertion.
     *
     * @param string $section_name Nom unique de la section
     * @param string|null $data Contenu optionnel de la section
     * @throws TemplateException Si une section est déjà en cours de définition
     */
    public function section(string $section_name, ?string $data = null): void
    {
        if ($this->sectionName !== null) {
            throw new TemplateException(
                sprintf('Une section "%s" est déjà en cours de définition', $this->sectionName)
            );
        }

        $this->sectionName = $section_name;
        ob_start();

        if ($data !== null) {
            echo $data;
            $this->endSection();
        }
    }

    /**
     * Termine la définition de la section en cours
     *
     * @throws TemplateException Si aucune section n'est en cours de définition
     */
    public function endSection(): void
    {
        if ($this->sectionName === null) {
            throw new TemplateException(
                'Impossible de terminer une section : aucune section n\'est en cours de définition'
            );
        }

        $this->sections[$this->sectionName] = ob_get_clean();
        $this->sectionName = null;
    }

    /**
     * Récupère le contenu d'une section
     *
     * @param string $section_name Nom de la section
     * @return string Contenu de la section
     * @throws TemplateException Si la section n'existe pas
     */
    public function getSection(string $section_name): string
    {
        if (!isset($this->sections[$section_name])) {
            throw new TemplateException(
                sprintf('La section "%s" n\'existe pas', $section_name)
            );
        }
        return $this->sections[$section_name];
    }

    /**
     * Démarre l'empilage de contenu dans un stack
     *
     * Les stacks permettent d'accumuler du contenu à différents endroits
     * du template et de le restituer ailleurs (typiquement dans le layout).
     *
     * @param string $stack Nom du stack
     * @param string $content Contenu optionnel à empiler directement
     */
    public function push(string $stack, string $content = ''): void
    {
        if ($content === '') {
            $this->stack[] = $stack;
            ob_start();
        } else {
            $this->stack[] = $stack;
            $this->pushes[$stack][] = $content;
            $this->endPush();
        }
    }

    /**
     * Termine l'empilage du contenu en cours
     *
     * @throws TemplateException Si aucun stack n'est en cours
     */
    public function endPush(): void
    {
        if (empty($this->stack)) {
            throw new TemplateException(
                'Impossible de terminer l\'empilage : aucun stack n\'est en cours'
            );
        }

        $stackName = array_pop($this->stack);
        if (!isset($this->pushes[$stackName])) {
            $this->pushes[$stackName] = [];
        }
        $this->pushes[$stackName][] = ob_get_clean() . PHP_EOL;
    }

    /**
     * Récupère le contenu empilé dans un stack
     *
     * @param string $name Nom du stack
     * @param string $default Contenu par défaut si le stack est vide
     * @return string Contenu concaténé du stack
     */
    public function getStack(string $name, string $default = ''): string
    {
        if (!isset($this->pushes[$name])) {
            return $default;
        }

        return implode('', $this->pushes[$name]);
    }

    /**
     * Vide toutes les sections et stacks
     */
    public function flushSections(): void
    {
        $this->sections = [];
        $this->pushes = [];
        $this->stack = [];
    }

    /**
     * Définit le layout à utiliser pour le template
     *
     * @param string $layout_name Nom du layout
     * @param array<string, mixed> $layout_data Données à passer au layout
     */
    public function layout(string $layout_name, array $layout_data = []): void
    {
        $this->layoutName = $layout_name;
        $this->layoutData = $layout_data;
    }

    /**
     * Inclut un sous-template
     *
     * Cette méthode :
     * 1. Compile le template s'il n'existe pas ou s'il a été modifié
     * 2. Extrait les variables dans la portée locale
     * 3. Inclut le template compilé
     *
     * @param string $__template Chemin du template à inclure
     * @param array<string, mixed> $data Variables à passer au template
     */
    public function insert(string $__template, array $data = []): void
    {
        extract($data);

        if (!$this->file->existsAndIsExpired($__template)) {
            $content_inc = $this->file->read($__template);
            $content = $this->getCompiler()->compile($content_inc);
            $this->file->put($__template, $content);
        }

        include $this->file->getPathTemplateCache($__template);
    }

    /**
     * Échappe une chaîne pour l'affichage HTML
     *
     * @param mixed $string Valeur à échapper
     * @return string Chaîne échappée
     */
    public function escape(mixed $string): string
    {
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }
}
