<?php

namespace Cocoon\View;

use Cocoon\View\Compiler\Compiler;
use Cocoon\View\Features\TemplateComponentTrait;
use Cocoon\View\TemplateException;
use Cocoon\View\FunctionsTemplate;

/**
 * Class Template
 * Compile les templates .tpl en php template système
 */
class Template
{
    /**
     * Instance de FileTemplate
     *
     * @var object Cocoon\View\FileTemplate
     */
    protected $file;
    /**
     * Instance de FunctionsTemplate
     *
     * @var object Cocoon\View\FunctionsTemplate
     */
    protected $function;
    /**
     * Liste des nouvelles directives
     *
     * @var array
     */
    protected $custumDirectives = [];
    /**
     * Listes des composants if custume directives
     *
     * @var array
     */
    protected $conditions = [];

    use TemplateComponentTrait;
    /**
     * Initialise la class
     *
     * @param array $config
     */
    // TODO: variable data a initialiser
    public function __construct($config)
    {
        $this->file = new FileTemplate($config);
        $this->function = new FunctionsTemplate();
    }

    /**
     * Fonction de compilation des templates .tpl
     *
     * @param string $__template
     * @return void
     */
    public function compile($__template)
    {
        $content = $this->getCompiler()->compile($this->file->read($__template));
        $this->file->put($__template, $content);
    }
    /**
     * Compilation et mise a jour des templates .tpl
     *
     * @param string $__template
     * @param array $data
     * @return string
     */
    protected function tokenize($__template, $data = [])
    {
        if (! is_file($this->file->getPathTemplate($__template))) {
            throw new \Cocoon\View\TemplateException('le template '. $__template . ' est introuvable');
        }
        $this->data = $data;
        //TODO: a voir
        $this->data['__default__'] = null;
        ob_start();
        if (! $this->file->existsAndIsExpired($__template)) {
            $this->compile($__template);
        }
        extract($this->data);
        include $this->file->getPathTemplateCache($__template);

        $str = ob_get_clean();

        if ($this->layoutName != null) {
            ob_start();
            if (!$this->file->existsAndIsExpired($this->layoutName)) {
                $this->compile($this->layoutName);
            }
            $this->data = array_merge($this->data, $this->layoutData);
            extract($this->data);
            include $this->file->getPathTemplateCache($this->layoutName);

            $str = ob_get_clean();
        }
        return $str;
    }

    /**
     * Affiche le template
     *
     * @param string $__template
     * @param array $data
     * @return string
     */
    public function render($__template, $data = [])
    {
        return $this->tokenize($__template, $data);
    }

    /**
     * Initialise le compiler des templates
     *
     * @return Compiler Cocoon\View\Compiler\Compiler
     */
    protected function getCompiler() : Compiler
    {
        return new Compiler($this);
    }
    /**
     * Retourne une instance de Functiontemplate
     *
     * @return object
     */
    public function getFunction()
    {
        return $this->function;
    }
    /**
     * Execute une fonction dans le template {{ :asset:'path/to/asset' }}
     *
     * @param string $function
     * @param array ...$args
     * @return void
     */
    public function func($function, ...$args)
    {
        return $this->getFunction()->getFunction($function, ...$args);
    }
    /**
     * Execute un filtre sur une donnée du template
     *
     * @param string $filter
     * @param array ...$args
     * @return void
     */
    public function filter($filter, ...$args)
    {
        return $this->getFunction()->getFilter($filter, ...$args);
    }
    /**
     * Ajoute une nouvelle directive pour les templates
     *
     * @param string $name
     * @param callable $callback
     * @return void
     */
    public function addDirective($name, $callback)
    {
        $this->custumDirectives['@' . $name] = $callback;
    }
    /**
     * Retourne les directives initialisées
     *
     * @return array
     */
    public function getCustumDirectives() :array
    {
        return $this->custumDirectives;
    }
    /**
     * Retourne le résultat d'une condition
     *
     * @param string $name
     * @param array ...$parameters
     * @return void
     */
    public function check($name, ...$parameters)
    {
        return call_user_func($this->conditions[$name], ...$parameters);
    }
    /**
     * Ajoute une diretive de condition if directive
     *
     * @param string $name
     * @param callable $callback
     * @return void
     */
    public function setCondition($name, $callback)
    {
        $this->conditions[$name] = $callback;
    }
    /**
     * Retourne les données envoyées au template
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
    /**
     * Suppréssion sections et stacks
     */
    public function __destruct()
    {
        $this->flushSections();
    }
}
