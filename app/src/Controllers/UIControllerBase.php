<?php
namespace SWCPR\Controllers;

use Twig\Environment as Twig;

abstract class UIControllerBase {
    private readonly Twig $twig;

    public function __construct(Twig $twig) {
        $this->twig = $twig;
    }

    protected function render(string $templateName): void {
        echo $this->twig->render($templateName);
    }
}