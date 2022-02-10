<?php

namespace Cocoon\View\Compiler\Features;

trait CompileOther
{
    public function compileSet($args)
    {
        preg_match('/([a-zA-Z0-9_]+)\s(=)\s([^\t\r\n}]+)/', $args, $matches);
        return '<?php ' . $this->resolveExpression($matches[1]) . ' ' . $matches[2] . ' ' .
            $this->resolveExpression(trim($matches[3])) . '; ?>';
    }
}
