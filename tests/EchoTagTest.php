<?php


namespace Tests;

class Person
{
    protected $name;

    public function __construct($name = 'default')
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}

class Profile
{
    public $statut;

    public function __construct($statut = 'default')
    {
        $this->statut = $statut;
    }
}

class EchoTagTest extends InitViewTest
{
    public function testEchoCompiled()
    {
        // escaped
        $this->assertEquals($this->parse('{{ nom }}'), '<?php echo $this->escape($nom); ?>');
        // unescaped
        $this->assertEquals($this->parse('{{{ nom }}}'), '<?php echo $nom; ?>');
        // sans espace
        $this->assertEquals($this->parse('{{nom}}'), '<?php echo $this->escape($nom); ?>');
        // array
        $data['person'] = ['name' => 'john'];
        $this->assertEquals(
            '<?php echo $this->escape($person[\'name\']); ?>',
            $this->parse('{{ person[\'name\'] }}', $data)
        );
        // object public method
        $data['person'] = new Person('john');
        $this->assertEquals(
            $this->parse('{{ person.name }}', $data),
            '<?php echo $this->escape($person->getName()); ?>'
        );
        // object public variable
        $data['profile'] = new Profile('member');
        $this->assertEquals(
            $this->parse('{{ profile.statut }}', $data),
            '<?php echo $this->escape($profile->statut); ?>'
        );
        // vue js variable
        $this->assertEquals($this->parse('@{{ nom }}'), '{{ nom }}');
    }
}
