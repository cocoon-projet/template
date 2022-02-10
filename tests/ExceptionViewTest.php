<?php


namespace Tests;

use Cocoon\View\Engine;
use Cocoon\View\TemplateException;

class ExceptionViewTest extends InitViewTest
{
    public function testIsNotEmptyConfigFileEngine()
    {
        $this->expectException(TemplateException::class);
        $this->expectExceptionMessage('Le paramètre $config de type array n\'est pas renseigné');
        $engine = Engine::create();

    }
    public function testTemplateNotExist()
    {
        $this->expectException(TemplateException::class);
        $this->expectExceptionMessage('le template none est introuvable');

        $this->engine->render('none');
    }
}
