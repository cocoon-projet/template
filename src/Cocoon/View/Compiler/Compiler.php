<?php

namespace Cocoon\View\Compiler;

use Cocoon\View\Template;

/**
 * Compilateur principal du moteur de template Twide
 *
 * Cette classe est responsable de :
 * - La compilation des templates .tpl en fichiers PHP
 * - La gestion du parser qui analyse la syntaxe
 * - La transformation des expressions Twide en code PHP
 *
 * @package Cocoon\View\Compiler
 * @author Cocoon Project
 */
class Compiler
{
    /**
     * Instance du parser de template
     *
     * @var Parser
     */
    private Parser $parser;

    /**
     * Initialise le compilateur avec une instance de Template
     *
     * @param Template $template Instance du moteur de template
     */
    public function __construct(Template $template)
    {
        $this->setParser(new Parser($template));
    }

    /**
     * Compile le code template en PHP
     *
     * @param string $template_code Code source du template à compiler
     * @return string Code PHP compilé
     */
    public function compile(string $template_code): string
    {
        return $this->getParser()->parse($template_code);
    }

    /**
     * Définit l'instance du parser à utiliser
     *
     * @param Parser $parser Instance du parser
     * @return void
     */
    public function setParser(Parser $parser): void
    {
        $this->parser = $parser;
    }

    /**
     * Retourne l'instance du parser utilisé
     *
     * @return Parser Instance du parser
     */
    public function getParser(): Parser
    {
        return $this->parser;
    }
}
