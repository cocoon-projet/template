<?php

namespace Cocoon\View\Compiler\Features;

trait CompileIncludes
{
    /**
     * Compile le tag @include('file')
     *
     * @param string $args
     * @return void
     */
    public function compileInclude($args)
    {
        if (strpos($args, ' with ')) {
            preg_match('#(.*?) with ([^\t\r\n}]+)#', $args, $matches);
            $return =  '<?php $this->insert(' . $matches[1] . ', ' .
                $this->resolveExpression($matches[2]) . '); ?>' . PHP_EOL;
            return $return;
        }
        return '<?php $this->insert(' . $args . '); ?>';
    }
    /**
     * Compile le tag @each($args)
     *
     * @param string $args
     * @return void
     */
    public function compileEach($args)
    {
        $arg = explode(',', $args);
        $arg_two = trim($arg[2]);
        $arg_two = '$' . trim($arg_two, '\'');
        if (! isset($arg[3])) {
            $php = '<?php foreach(' . trim($arg[1]) . ' as ' . $arg_two. '): ?>';
            $php .= "\n";
            $php .= str_repeat(' ', 4) . '<?php $this->insert(' . trim($arg[0])
            . ', [' . trim($arg[2]) . ' => ' . $arg_two . ']); ?>' . PHP_EOL;
            $php .= "\n";
            $php .= '<?php endforeach; ?>';
            return $php;
        }
        $arg_tree = trim($arg[3]);
        $arg_tree = trim($arg_tree, '\'');
        if (substr($arg_tree, 0, 4) == 'raw|') {
            $text = explode('|', $arg_tree);
            $return = $text[1];
        } else {
            $return = '<?php $this->insert(' . trim($arg[3]) . '); ?>';
        }
        $php = '<?php if(count(' . trim($arg[1]) . ') == 0): ?>';
        $php .= "\n";
        $php .= str_repeat(' ', 4) . $return;
        $php .= "\n";
        $php .= '<?php else: ?>';
        $php .= "\n";
        $php .= str_repeat(' ', 4) . '<?php foreach(' . trim($arg[1]) . ' as ' . $arg_two . '): ?>';
        $php .= "\n";
        $php .= str_repeat(' ', 8) . '<?php $this->insert(' . trim($arg[0]) . ', ['
        . trim($arg[2]) . ' => ' . $arg_two . ']); ?>'. PHP_EOL;
        $php .= "\n";
        $php .= str_repeat(' ', 4) . '<?php endforeach; ?>';
        $php .= "\n";
        $php .= '<?php endif; ?>';
        return $php;
    }
}
