<?php

namespace Cocoon\View\Compiler\Features;

trait CompileLayoutsAndStacks
{
       /**
     * Compile le tag de layout @extends('layout')
     *
     * @param string $args
     * @return void
     */
    public function compileExtends($args)
    {
        return '<?php $this->layout(' . $args . '); ?>';
    }
    /**
     * Compile le tag @stop
     *
     * @param string $args
     * @return void
     */
    public function compileEndSection()
    {
        return '<?php $this->endSection(); ?>' . PHP_EOL;
    }
    /**
     * Compile le tag @section('ma_section')
     *
     * @param string $args
     * @return void
     */
    public function compileSection($args)
    {
        if (strpos($args, ' with ')) {
            preg_match('#(.*?) with ([^\t\r\n}]+)#', $args, $matches);
            $return =  '<?php $this->section(' . $matches[1] . ', ' .
                $this->resolveExpression($matches[2]) . '); ?>' . PHP_EOL;
            return $return;
        }
        return '<?php $this->section(' . $args . '); ?>';
    }
     /**
     * Compile le tag @push('scripts')
     *
     * @param string $args
     * @return void
     */
    public function compilePush($args)
    {
        if (strpos($args, ' with ')) {
            preg_match('#(.*?) with ([^\t\r\n}]+)#', $args, $matches);
            $return =  '<?php $this->push(' . $matches[1] . ', ' . $this->resolveExpression($matches[2]) . '); ?>';
            $return .= "\n";
            return $return;
        }
        return '<?php $this->push(' . $args . '); ?>';
    }
    /**
     * Compile le tag @endPush
     *
     * @param string $args
     * @return void
     */
    public function compileEndpush()
    {
        return '<?php $this->endPush(); ?>' . PHP_EOL;
    }
    /**
     * Compile le tag @yield('content)
     *
     * @param string $args
     * @return void
     */
    public function compileYield($args)
    {
        return  '<?php echo $this->getSection(' . $args . '); ?>';
    }
    /**
     * Compile le tag @stack('content)
     *
     * @param string $args
     * @return void
     */
    public function compileStack($args)
    {
        return  '<?php echo $this->getStack(' . $args . '); ?>';
    }
    /**
     * Compile le tag @javascript
     *
     * @param string $args
     * @return void
     */
    public function compileJavascript($args)
    {
        return "<script>\n" . array_shift($args) . "\n</script>";
    }
}
