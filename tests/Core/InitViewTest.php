<?php
namespace Tests\Core;

use Cocoon\View\Compiler\Parser;
use Cocoon\View\Engine;
use Cocoon\View\Template;
use PHPUnit\Framework\TestCase;

session_start();

abstract class InitViewTest extends TestCase
{
    protected $view;
    protected $parser;
    protected $engine;
    protected $config = [
        'template_path' => __DIR__ . DIRECTORY_SEPARATOR . '../Twide',
        'template_php_path' => __DIR__ . DIRECTORY_SEPARATOR . '../Temp',
        'extension' => '.tpl.php'
    ];

    protected function setUp() :void
    {
        parent::setUp();
        $this->engine = Engine::create($this->config);
        $this->view = new Template($this->config);
        $this->parser = new Parser($this->view);
    }

    public function parse($content, $data = [])
    {
        $this->parser->setData($data);
        return $this->parser->parse($content);
    }

}