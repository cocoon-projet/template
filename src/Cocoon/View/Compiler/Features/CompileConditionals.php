<?php

namespace Cocoon\View\Compiler\Features;

trait CompileConditionals
{
    /**
     * Compile le tag @if(name == 'Doe')
     *
     * @param string $args
     * @return void
     */
    public function compileIf($args)
    {
        $args = $this->parseConditionnals($args);
        return '<?php if (' . trim($args) . '): ?>';
    }
        /**
     * Compile le tag @elseif(test == 1)
     *
     * @param string $args
     * @return void
     */
    public function compileElseif($args)
    {
        $args = $this->parseConditionnals($args);
        return '<?php elseif (' . trim($args) . '): ?>';
    }
    /**
     * Compile le tag @endif
     *
     * @param string $args
     * @return void
     */
    public function compileEndif()
    {
        return "<?php endif; ?>";
    }
     /**
     * Compile le tgg @else
     *
     * @param string $args
     * @return void
     */
    public function compileElse()
    {
        return "<?php else: ?>\n";
    }
    /**
     * Compile le tag @endforelse
     *
     * @param string $args
     * @return void
     */
    public function compileEndforelse()
    {
        return "<?php endif; ?>\n";
    }
    /**
     * Compile le tag @endisset
     *
     * @param string $args
     * @return void
     */
    public function compileEndisset()
    {
        return "<?php endif; ?>\n";
    }
    /**
     * Compile le tag @endempty
     *
     * @param string $args
     * @return void
     */
    public function compileEndempty()
    {
        return "<?php endif; ?>\n";
    }
    /**
     * Compile le tag @empty
     *
     * @param string $args
     * @return void
     */
    public function compileIfempty()
    {
        return "<?php endforeach; else: ?>\n";
    }
    /**
     * Compile le tag @continue
     *
     * @param string $args
     * @return void
     */
    public function compileContinue()
    {
        return "<?php continue; ?>\n";
    }
    /**
     * Compile le tag @break
     *
     * @param string $args
     * @return void
     */
    public function compileBreak()
    {
        return "<?php break; ?>\n";
    }
    /**
     * Compile le tag @isset(var)
     *
     * @param string $args
     * @return void
     */
    public function compileIsset($args)
    {
        return '<?php if (isset(' . $this->resolveExpression(trim($args)) . ')): ?>';
    }
    /**
     * Compile le tag @empty
     *
     * @param string $args
     * @return void
     */
    public function compileEmpty($args)
    {
        return '<?php if (empty(' . $this->resolveExpression(trim($args)) . ')): ?>';
    }
    /**
     * Compile le tag @switch
     *
     * @param string $args
     * @return void
     */
    public function compileSwitch($args)
    {
        return '<?php switch(' . $this->resolveExpression(trim($args)) . '): ?>';
    }
    /**
     * Compile le tag @case(args)
     *
     * @param string $args
     * @return void
     */
    public function compileCase($args)
    {
        return '<?php case (' . $this->resolveExpression(trim($args)) . '): ?>';
    }
    /**
     * Compile le tag @default
     *
     * @param string $args
     * @return void
     */
    public function compileDefault()
    {
        return '<?php default: ?>';
    }
    /**
     * Compile le tag @endswitch
     *
     * @return void
     */
    public function compileEndswitch()
    {
        return '<?php endswitch; ?>';
    }
}
