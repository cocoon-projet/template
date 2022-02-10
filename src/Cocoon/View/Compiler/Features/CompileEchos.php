<?php

namespace Cocoon\View\Compiler\Features;

use Cocoon\View\TemplateException;

trait CompileEchos
{
 
    /**
     * Compile les tags échappés {{ name }}
     *
     * @param string $code
     * @return void
     */
    public function compileEcho($code)
    {
        // le code est une fonction
        if (preg_match('/([a-zA-Z]+)\s*\((.*)\)/', $code, $matches) && !strpos($code, '|')) {
            $expression = $this->expressionFunctions($matches);
        } else {
            $expression = $this->expressionFilters($code);
        }
        return '<?php echo $this->escape(' . $expression . '); ?>';
    }
    /**
     * Compile les tags qui ne sont pas échappés {{{ name }}}
     *
     * @param string $code
     * @return void
     */
    public function compileNoEscape($code)
    {
        // le code est une fonction
        if (preg_match('/([a-zA-Z]+)^[\.]\s*\((.*)\)/', $code, $matches) && !strpos($code, '|')) {
            $expression = $this->expressionFunctions($code);
        } else {
            $expression = $this->expressionFilters($code);
        }
        return '<?php echo ' . $expression . '; ?>';
    }
}
