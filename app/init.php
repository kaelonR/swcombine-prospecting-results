<?php
use Twig\Loader\FilesystemLoader;
use Twig\Environment as Twig;

require_once '/composer/vendor/autoload.php';

//PHP-DI
$containerBuilder = new DI\ContainerBuilder();
$containerBuilder->addDefinitions([
    PDO::class => DI\factory(fn() => new PDO('mysql:dbname=swcombine-prospecting-results;host=database', 'swcdemo', 'swcdemo', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]))
]);


//Twig
$twigLoader = new FilesystemLoader('/app/Views');
$twig = new Twig($twigLoader, [
    'cache' => false,
    'autoescape' => 'html'
]);
$containerBuilder->addDefinitions([
    Twig::class => $twig,
]);

//PHP-DI wrap-up
$container = $containerBuilder->build();
$GLOBALS['container'] = $container;

function runRoute(string $controller, string $method, ?array $vars = null): void {
    if(!isset($vars))
        $vars = [];

    $container = $GLOBALS['container'];
    $controllerInstance = $container->get($controller);
    $controllerInstance->{$method}(...$vars);
}