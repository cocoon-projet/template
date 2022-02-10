<?php

namespace Cocoon\View\Compiler;

use Cocoon\View\Template;

/**
 * Compiler Template Class
 */
class Compiler
{
    /**
     * Parser instance
     *
     * @var object
     */
    private $parser;

    /**
     * Constructeur initialise le compiler
     * @param Template $template
     */
    public function __construct(Template $template)
    {
        $this->setParser(new Parser($template));
    }
    /**
     * Compile le code tpl en template php système
     *
     * @param string $template_code
     * @return void
     */
    public function compile($template_code)
    {
        return $this->getParser()->parse($template_code);
    }
    /**
     * Inialise Template Parser
     *
     * @param Parser $parser
     * @return void
     */
    public function setParser(Parser $parser)
    {
        $this->parser = $parser;
    }
    /**
     * Retourne une instance de Template Parser
     *
     * @return void
     */
    public function getParser()
    {
        return $this->parser;
    }
}
