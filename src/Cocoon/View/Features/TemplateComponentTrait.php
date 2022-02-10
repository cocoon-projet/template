<?php

namespace Cocoon\View\Features;

use Cocoon\View\TemplateException;

trait TemplateComponentTrait
{
    /**
     * listes des données pour le template
     *
     * @var array
     */
    protected $data = [];
    /**
     * Nom du layout
     *
     * @var [type]
     */
    protected $layoutName = null;
    /**
     * Données pour le layout
     *
     * @var array
     */
    protected $layoutData = [];
    /**
     * Liste des section pour les templates
     *
     * @var array
     */
    protected $sections = [];
    /**
     * Nom d'une section
     *
     * @var string
     */
    protected $sectionName;
    /**
     * Stack content
     *
     * @var array
     */
    protected $pushes = [];
    /**
     * Stack nom
     *
     * @var array
     */
    protected $stack = [];
    /**
     * Initialise une section pour le template
     *
     * @param string $section_name
     * @param array $data
     * @return void
     */
    public function section($section_name, $data = null)
    {
        if ($this->sectionName) {
            throw new TemplateException('une section est déjà initialisée');
        }

        $this->sectionName = $section_name;

        ob_start();

        if ($data != null) {
            echo $data;
            $this->endSection();
        }
    }
    /**
     * Stop la section définit pour le template
     *
     * @return void
     */
    public function endSection()
    {
        if (!$this->sectionName) {
            throw new TemplateException('vous devez initialiser une section avant de la stopper.');
        }

        if (!isset($this->sections[$this->sectionName])) {
            $this->sections[$this->sectionName] = '';
        }

        $this->sections[$this->sectionName] =  ob_get_clean();
        $this->sectionName = null;
    }
    /**
     * Retourne une section définit dans le layout
     *
     * @param string $section_name
     * @return void
     */
    public function getSection($section_name)
    {
        if (!isset($this->sections[$section_name])) {
            throw new TemplateException('La section n\éxiste pas');
        }
        return $this->sections[$section_name];
    }
    /**
     * Démarre une push section
     *
     * @param string $name
     * @return void
     */
    public function push($stack, $content = '')
    {
        ob_start();
        if ($content == '') {
             $this->stack[] = $stack;
        } else {
            $this->stack[] = $stack;
            $this->pushes[$stack][] = $content;
            $this->endPush();
        }
    }
    /**
     * Stop une push section
     *
     * @return void
     */
    public function endPush()
    {
        if (empty($this->stack)) {
            throw new TemplateException('Impossible de stopper le stack si il n\'est pas démarré');
        }
        $this->pushes[array_pop($this->stack)][] = ob_get_clean() . PHP_EOL;
    }
    /**
     * Retourne une push section
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    public function getStack($name, $default = '') :string
    {
        if (!isset($this->pushes[$name])) {
            return $default;
        }

        if (isset($this->pushes[$name])) {
            $content = implode($this->pushes[$name]);
        }

        return $content;
    }

    public function flushSections()
    {
        $this->sections = [];
        $this->pushes = [];
        $this->stack = [];
    }

    /**
     * Initialise un layout pour les templates
     *
     * @param string $name Nom du layout
     * @param array $data
     * @return void
     */
    public function layout($layout_name, array $layout_data = [])
    {
        $this->layoutName = $layout_name;
        $this->layoutData = $layout_data;
    }
    /**
     * Gestion des fichiers inclus dans le template: @include('alert')
     *
     * @param string $name nom du template
     * @param array $data
     * @return void
     */
    public function insert($__template, array $data = array())
    {
        extract($data);
        //extract($this->data);
        if (! $this->file->existsAndIsExpired($__template)) {
            $content_inc = $this->file->read($__template);
            $content = $this->getCompiler()->compile($content_inc);
            $this->file->put($__template, $content);
        }
        include $this->file->getPathTemplateCache($__template);
    }
    /**
     * Echappement des caractères spéciaux
     *
     * @param string $string
     * @return string
     */
    public function escape($string) :string
    {
        // TODO: flag a prevoir dans config
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
