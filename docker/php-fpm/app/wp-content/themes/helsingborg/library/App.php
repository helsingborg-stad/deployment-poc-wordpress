<?php
namespace Helsingborg;

class App
{
    public function __construct()
    {
        new \Helsingborg\Theme\AdminMenu();
        new \Helsingborg\Theme\Seo();
    }
}
