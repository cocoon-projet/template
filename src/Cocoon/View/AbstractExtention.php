<?php

namespace Cocoon\View;

abstract class AbstractExtention
{
    public function getWiths()
    {
        return [];
    }

    public function getFilters()
    {
        return [];
    }

    public function getFunctions()
    {
        return [];
    }

    public function getDirectives()
    {
        return [];
    }

    public function getIfs()
    {
        return [];
    }
}
