<?php

namespace Cocoon\View;

use Cocoon\View\AbstractExtension;

// use Cocoon\View\Extensions\DateExtension;
//use Cocoon\View\Extensions\TextExtension;
//use Cocoon\View\Extensions\ArrayExtension;

class Twide
{
    private static ?Engine $instance = null;

    public static function init(array $config = []): Engine
    {
        if (self::$instance === null) {
            self::$instance = new Engine($config);
        }
        
        return self::$instance;
    }

    public static function render(string $template, array $data = []): string
    {
        return self::getInstance()->render($template, $data);
    }

    public static function addExtension(AbstractExtension $extension): void
    {
        self::getInstance()->addExtension($extension);
    }

    public static function addGlobal(string $name, $value): void
    {
        self::getInstance()->addGlobal($name, $value);
    }

    public static function with($data, $value = ''): Engine
    {
        return self::getInstance()->with($data, $value);
    }

    public static function addFilter(string $name, $callback): Engine
    {
        return self::getInstance()->addFilter($name, $callback);
    }

    public static function addFunction(string $name, callable $callback): Engine
    {
        return self::getInstance()->addFunction($name, $callback);
    }

    public static function directive(string $name, callable $callback): Engine
    {
        return self::getInstance()->directive($name, $callback);
    }

    public static function if(string $name, callable $callback): Engine
    {
        return self::getInstance()->if($name, $callback);
    }

    public static function create(array $config = []): Engine
    {
        return Engine::create($config);
    }

    public static function getGlobals(): array
    {
        return self::getInstance()->getGlobals();
    }

    public static function getInstance(): Engine
    {
        if (self::$instance === null) {
            self::init();
        }
        return self::$instance;
    }
}
