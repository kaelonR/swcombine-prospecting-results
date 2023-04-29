<?php
namespace SWCPR\Controllers;

use Twig\Environment as Twig;

class HomeController {
    private readonly Twig $twig;

    public function __construct(Twig $twig) {
        $this->twig = $twig;
    }

    public function index(): void {
        echo $this->twig->render('home/index.twig');
    }
}