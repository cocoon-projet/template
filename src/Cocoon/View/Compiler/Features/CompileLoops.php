<?php

namespace Cocoon\View\Compiler\Features;

trait CompileLoops
{
    /**
     * Une condition if est utilisée d'une boucle foreach
     *
     * @var boolean
     */
    protected $ifForeach = false;
    /**
     * Compile le tag  @foreach(users  user)
     *
     * @param string $args
     * @return void
     */
    public function compileForeach($args)
    {
        // TODO: exporter dans parse
        $foreach = '';
        if (strpos($args, 'if')) {
            $keys = preg_split("/(if)/", $args);
            $this->ifForeach = true;
            $return = explode(' ', trim($keys[0]));
            $if = $this->compileIf(trim($keys[1]));
        } else {
            $return = explode(' ', trim($args));
            $if = '';
        }
        if (count($return) == 2) {
            $php = $this->expressionFilters(trim($return[0])) . ' as $' . trim($return[1]);
        } else {
            $php = $this->expressionFilters(trim($return[0])) . ' as $' . trim($return[1]) . ' => $' . trim($return[2]);
        }
        $foreach .= '<?php foreach(' . $php . '): ?>'. PHP_EOL;
        $foreach .= $if;
        return $foreach;
    }
    /**
     * Compile le tag  @endforeach
     *
     * @param string $args
     * @return void
     */
    public function compileEndforeach()
    {
        if ($this->ifForeach) {
            $this->ifForeach = false;
            return "<?php endif; endforeach; ?>\n";
        }
        return "<?php endforeach; ?>\n";
    }
     /**
     * Compile le tag @forelse(users user)
     *
     * @param string $args
     * @return string
     */
    public function compileForelse($args)
    {
        $code = "<?php" . PHP_EOL;
        $return = explode(' ', $args);
        if (count($return) == 2) {
            $php = $this->expressionFilters(trim($return[0])) . ' as $' . trim($return[1]);
        } else {
            $php = $this->expressionFilters(trim($return[0])) . ' as $' . trim($return[1]) . ' => $' . trim($return[2]);
        }
        $code .= "if (count(" . trim('$'. $return[0]) . ") != 0):\n";
        $code .= str_repeat(' ', 4) . "foreach({$php}):\n?>";
        return $code;
    }
    /**
     * Complile le tag @for(i in 0 count 10)
     *
     * @param string $args
     * @return void
     */
    public function compileFor($args)
    {
        $keys = preg_split("/(in|count)/", $args);
        $i = trim($keys[0]);
        $number = trim($keys[2]);
        if (is_numeric($number)) {
            $count = $number;
        } else {
            $count = count($this->data[$number]);
        }
        $args = '$' . $i . ' = ' . trim($keys[1]) . '; $' . $i . ' < ' . $count . '; $' . $i . '++';
        return '<?php for(' . $args . '): ?>';
    }
    /**
     * Compile le tag @while(args)
     *
     * @param string $args
     * @return void
     */
    public function compileWhile($args)
    {
        return '<?php while(' . $args . '): ?>';
    }
    /**
     * Compile le tag @endfor
     *
     * @param string $args
     * @return void
     */
    public function compileEndfor()
    {
        return "<?php endfor; ?>\n";
    }
    /**
     * Compile le tag @endwhile
     *
     * @param string $args
     * @return void
     */
    public function compileEndwhile()
    {
        return "<?php endwhile; ?>\n";
    }
}
