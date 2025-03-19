<?php

namespace Tests\Core;

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

    public function testWithArrayData()
    {
        $this->engine->with([
            'name' => 'John',
            'age' => 30
        ]);
        $result = $this->engine->render('witharray');
        $this->assertEquals('John is 30', trim($result));
    }

    public function testAddFilterEngine()
    {
        $this->engine->addFilter('maj', function ($expression) {
            return ucwords($expression);
        });
        $result = $this->engine->render('filter');
        $this->assertEquals('Franck', trim($result));
    }

    public function testMultipleFilters()
    {
        $this->engine->addFilter('maj', function ($str) {
            return strtoupper($str);
        });
        $result = $this->engine->render('multifilter');
        $this->assertEquals('FRANCK', trim($result));
    }

    public function testAddFunctionEngine()
    {
        $this->engine->addFunction('asset', function ($expression) {
            return $expression . ' la fonction asset';
        });
        $result = $this->engine->render('function');
        $this->assertEquals('Bonjour la fonction asset', trim($result));
    }

    public function testFunctionWithMultipleArguments()
    {
        $this->engine->addFunction('format', function ($greeting, $name, $age) {
            return sprintf("%s %s, you are %d", $greeting, $name, $age);
        });
        $this->engine->with([
            'name' => 'John',
            'age' => 30
        ]);
        $result = $this->engine->render('formatfunction');
        $this->assertEquals('Hello John, you are 30', trim($result));
    }

    public function testAddDirectiveEngine()
    {
        $this->engine->directive('hello', function () {
            return "<?php echo 'Hello Franck'; ?>";
        });
        $result = $this->engine->render('directive');
        $this->assertEquals('Hello Franck', trim($result));
    }

    public function testDirectiveWithParameters()
    {
        $this->engine->directive('greet', function ($expression) {
            return "<?php echo 'Hello ' . $expression; ?>";
        });
        $result = $this->engine->render('paramsdirective', ['name' => 'John']);
        $this->assertEquals('Hello John', trim($result));
    }

    public function testAddIfDirectiveEngine()
    {
        $this->engine->if('member', function ($user) {
            return $user === 'franck';
        });
        $result = $this->engine->render('ifdirective', ['nom' => 'franck']);
        $this->assertSame('oui', trim($result));
    }

    public function testIfDirectiveWithElse()
    {
        $this->engine->if('admin', function ($role) {
            return $role === 'admin';
        });
        $result = $this->engine->render('ifdirectiveelse', ['role' => 'user']);
        $this->assertSame('not admin', trim($result));
    }

    public function testNestedSections()
    {
        $result = $this->engine->render('nested');
        $this->assertStringContainsString('parent content', $result);
        $this->assertStringContainsString('child content', $result);
    }

    public function testComponentRendering()
    {
        $result = $this->engine->render('withcomponent');
        $this->assertStringContainsString('alert-success', $result);
        $this->assertStringContainsString('Operation successful', $result);
    }

    public function testGetData()
    {
        $data = ['name' => 'John', 'age' => 30];
        $this->engine->with($data);
        $result = $this->engine->render('witharray');
        $this->assertEquals('John is 30', trim($result));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_SESSION = [];
    }
}
