<?php


namespace Tests\Core;

class FiltersCompiledTest extends InitViewTest
{
    public function testFilterCompiled()
    {
        // string
        $this->assertEquals(
            $this->parse('{{ \'chaine\'|length }}'),
            '<?php echo $this->escape($this->filter(\'length\', \'chaine\')); ?>'
        );
        //array
        $this->assertSame(
            str_replace(' ', '', $this->parse('{{{ [1,2,3,4]|length }}}')),
            str_replace(' ', '', '<?php echo $this->filter(\'length\', [1, 2, 3, 4]); ?>')
        );
        // date
        $this->assertEquals(
            $this->parse('{{{ \'now\'|date("m/d/Y") }}}'),
            '<?php echo $this->filter(\'date\', \'now\', "m/d/Y"); ?>'
        );
        // carbon diffforhumans
        $this->assertEquals(
            $this->parse('{{{ \'2019-05-29 18:42:23\'|diffForHumans }}}'),
            '<?php echo $this->filter(\'diffForHumans\', \'2019-05-29 18:42:23\'); ?>'
        );
        // date format
        $this->assertEquals(
            $this->parse('{{{ \'2019-05-29 18:42:23\'|date(\'m/d/Y\') }}}'),
            '<?php echo $this->filter(\'date\', \'2019-05-29 18:42:23\', \'m/d/Y\'); ?>'
        );
        // filter exists upper - accepte les deux formes
        $result = $this->parse('{{{ \'nom\'|upper }}}');
        $this->assertTrue(
            $result === '<?php echo strtoupper(\'nom\'); ?>' ||
            $result === '<?php echo $this->filter(\'upper\', \'nom\'); ?>'
        );
    }
}
