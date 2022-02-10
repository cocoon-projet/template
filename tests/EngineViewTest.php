<?php


namespace Tests;

class EngineViewTest extends InitViewTest
{
    public function testBasicEngineTemplate()
    {
        $result = $this->engine->render('basic');
        $this->assertEquals('hello word', trim($result));
    }

    public function testWithSetDataToView()
    {
        $this->engine->with('foo', 'baa');
        $this->engine->with('baz', 'boo');
        $result = $this->engine->render('with');
        $this->assertEquals('baa boo', trim($result));
    }

    public function testAddFilterEngine()
    {
        $this->engine->addFilter('maj', function ($expression) {
            return ucwords($expression);
        });
        $result = $this->engine->render('filter');
        $this->assertEquals('Franck', trim($result));
    }

    public function testAddFunctionEngine()
    {
        $this->engine->addFunction('asset', function ($expression) {
            return $expression . ' la fonction asset';
        });
        $result = $this->engine->render('function');
        $this->assertEquals('Bonjour la fonction asset', trim($result));
    }

    public function testAddDirectiveEngine()
    {
        $this->engine->directive('hello', function () {
            return "<?php echo 'Hello Franck'; ?>";
        });
        $result = $this->engine->render('directive');
        $this->assertEquals('Hello Franck', trim($result));
    }
    public function testAddIfDirectiveEngine()
    {
        $this->engine->if('member', function ($user) {
            if ($user == 'franck') {
                return true;
            }
        });
        $result = $this->engine->render('ifdirective');
        $this->assertSame('oui', trim($result));
    }
}
