<?php


namespace Tests;

class FiltersCompiledTest extends InitViewTest
{
    public function testFilterCompiled()
    {
        // string
        $this->assertEquals(
            $this->parse('{{ \'chaine\'|lenght }}'),
            '<?php echo $this->escape($this->filter(\'lenght\', \'chaine\')); ?>'
        );
        //array
        $this->assertEquals(
            $this->parse('{{{ [1,2,3,4]|lenght }}}'),
            '<?php echo $this->filter(\'lenght\', [1,2,3,4]); ?>'
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
        // filter exists upper
        $this->assertEquals(
            $this->parse('{{{ \'nom\'|upper }}}'),
            '<?php echo strtoupper(\'nom\'); ?>'
        );

    }
}
