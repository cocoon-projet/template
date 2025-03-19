<?php

namespace Tests\Extensions;

use PHPUnit\Framework\TestCase;
use Cocoon\View\Extensions\TextExtension;

class TextExtensionTest extends TestCase
{
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new TextExtension();
    }

    public function testExcerptFilter()
    {
        $filters = $this->extension->getFilters();
        $this->assertArrayHasKey('excerpt', $filters);

        $text = "Ceci est un texte très long qui doit être tronqué à une certaine longueur";
        $result = $filters['excerpt']($text, 20);
        $this->assertEquals("Ceci est un texte tr...", $result);

        $text = "Texte court";
        $result = $filters['excerpt']($text, 20);
        $this->assertEquals("Texte court", $result);
    }

    public function testSlugFilter()
    {
        $filters = $this->extension->getFilters();
        $this->assertArrayHasKey('slug', $filters);

        $text = "Ceci est un titre avec des caractères spéciaux !@#$%^&*()";
        $result = $filters['slug']($text);
        $this->assertEquals("ceci-est-un-titre-avec-des-caracteres-speciaux", $result);
    }

    public function testWordcountFilter()
    {
        $filters = $this->extension->getFilters();
        $this->assertArrayHasKey('wordcount', $filters);

        $text = "Ceci est un texte avec sept mots";
        $result = $filters['wordcount']($text);
        $this->assertEquals(7, $result);
    }

    public function testEscapeAttrFilter()
    {
        $filters = $this->extension->getFilters();
        $this->assertArrayHasKey('escape_attr', $filters);

        $text = 'Ceci est un "texte" avec des caractères <spéciaux>';
        $result = $filters['escape_attr']($text);
        $this->assertEquals('Ceci est un &quot;texte&quot; avec des caractères &lt;spéciaux&gt;', $result);
    }

    public function testStrStartsWithFunction()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('str_starts_with', $functions);

        $result = $functions['str_starts_with']("Ceci est un texte", "Ceci");
        $this->assertTrue($result);

        $result = $functions['str_starts_with']("Ceci est un texte", "texte");
        $this->assertFalse($result);
    }

    public function testStrEndsWithFunction()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('str_ends_with', $functions);

        $result = $functions['str_ends_with']("Ceci est un texte", "texte");
        $this->assertTrue($result);

        $result = $functions['str_ends_with']("Ceci est un texte", "Ceci");
        $this->assertFalse($result);
    }

    public function testStrContainsFunction()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('str_contains', $functions);

        $result = $functions['str_contains']("Ceci est un test", "est");
        $this->assertTrue($result);

        $result = $functions['str_contains']("Ceci est un test", "xyz");
        $this->assertFalse($result);

        $result = $functions['str_contains']("Test", "");
        $this->assertTrue($result);
    }

    public function testStrReplaceFunction()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('str_replace', $functions);

        $result = $functions['str_replace']("Ceci est un texte", "texte", "test");
        $this->assertEquals("Ceci est un test", $result);

        $result = $functions['str_replace']("Test", "", "");
        $this->assertEquals("Test", $result);
    }

    public function testEmptyTextIf()
    {
        $ifs = $this->extension->getIfs();
        $this->assertArrayHasKey('empty_text', $ifs);

        $result = $ifs['empty_text']("");
        $this->assertTrue($result);

        $result = $ifs['empty_text']("   ");
        $this->assertTrue($result);

        $result = $ifs['empty_text']("Texte");
        $this->assertFalse($result);
    }

    public function testContainsIf()
    {
        $ifs = $this->extension->getIfs();
        $this->assertArrayHasKey('contains', $ifs);

        $result = $ifs['contains']("Ceci est un test", "est");
        $this->assertTrue($result);

        $result = $ifs['contains']("Ceci est un test", "xyz");
        $this->assertFalse($result);
    }
} 