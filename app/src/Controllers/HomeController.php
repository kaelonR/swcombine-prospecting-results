<?php
namespace SWCPR\Controllers;

class HomeController extends UIControllerBase {
    public function index(): void {
        $this->render('home/index.twig');
    }
}