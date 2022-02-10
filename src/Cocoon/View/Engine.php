<?php

namespace Cocoon\View;

use Cocoon\View\Template;
use Cocoon\View\TemplateException;
use Cocoon\View;

/**
 * Class View\Engine
 */
class Engine
{
    /**
     * Template instance
     *
     * @var object Cocoon\View\Template
     */
    private $template = null;
    /**
     * Ajouter une donnée pour les templates.
     *
     * @var array
     */
    protected $data = [];
    /**
     * Registre des fonctions enregistrées
     *
     * @var array
     */
    public function __construct(array $config = [])
    {
        if (count($config) == 0) {
            throw new TemplateException('Le paramètre $config de type array n\'est pas renseigné');
        }
        $this->template = new Template($config);
        $globals['app'] = ['query' => $_GET, 'session' => $_SESSION , 'input' => $_POST];
        $this->with($globals);
    }
    /**
     * Creation de View\Engine
     *
     * @param array $config
     * @return object instance de View\Engine
     */
    public static function create(array $config = [])
    {
        return new Engine($config);
    }
    /**
     * Affiche le template
     *
     * @param string $__template
     * @param array $__data
     * @return string
     */
    public function render($__template, $__data = []) :string
    {
        $__data = array_merge($this->data, $__data);
        return $this->template->render($__template, $__data);
    }
    /**
     * Assigne une donnée pour le template
     *
     * @param string|array $data
     * @param string $value
     * @return void
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
     * Ajoute un filtre pour les varialbes de template les templates
     *
     * @param string $name
     * @param string|callable $callback
     * @return object
     */
    public function addFilter($name, $callback)
    {
        $this->template->getFunction()->addFilter($name, $callback);
        return $this;
    }
    /**
     * Ajoute une fonction pour les templates
     *
     * @param string $name
     * @param callable $callback
     * @return void
     */
    public function addFunction($name, callable $callback)
    {
        $this->template->getFunction()->addFunction($name, $callback);
        return $this;
    }
    /**
     * Ajoute une nouvelle directive pour les templates
     *
     * @param string $name
     * @param callable $callback
     * @return void
     */
    public function directive($name, callable $callback)
    {
        $this->template->addDirective($name, $callback);
        return $this;
    }
    /**
     * Ajoute une directive if pour les templates
     *
     * @param string $name
     * @param callable $callback
     * @return object Engine class
     */
    public function if($name, callable $callback)
    {
        $this->template->setCondition($name, $callback);
        $this->directive($name, function ($expression = null) use ($name) {
            if (null === $expression) {
                return "<?php if (\$this->check('{$name}')): ?>";
            } else {
                return "<?php if (\$this->check('{$name}', {$expression})): ?>";
            }
        })
        ->directive('else' . $name, function ($expression = null) use ($name) {
            if (null === $expression) {
                return "<?php elseif (\$this->check('{$name}')): ?>";
            } else {
                return "<?php elseif (\$this->check('{$name}', {$expression})): ?>";
            }
        })
        ->directive('end' . $name, function () {
            return '<?php endif; ?>';
        });
        return $this;
    }
    /**
     * Ajoute une extention pour le template
     *
     * @param object $extension
     * @return void
     */
    // TODO: finaliser cette fonction
    public function addExtension($extension)
    {
        $this->with($extension->getwiths());
    }
}
