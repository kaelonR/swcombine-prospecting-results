<?php
namespace SWCPR\Controllers;

class PlanetController extends UIControllerBase {
    public function index(): void {
        $this->render('planets/index.twig');
    }

    public function addPlanet(): void {
        $this->render('planets/add-planet.twig');
    }
}