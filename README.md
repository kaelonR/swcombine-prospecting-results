# SWC prospecting results
Little demo application made for the SWC Dev Challenge.

## Demo
You can try out the demo at TBA (not available online yet)

## Installing Locally
### Option 1 - Docker Desktop
If you wish to get the app running locally, the easiest way to do so is with Docker Desktop, as I made this repo with Docker in mind for easy portability, so that no configuration is needed.

To get it running on Docker Desktop, simply clone the repo, then run `docker-compose up --detach` in the terminal. Finally, go to http://localhost:80/init to initialize the database. After that, the app is ready to be used on http://localhost:80.

### Option 2 - Manual configuration
If you wish to manually set up this app, the requirements are as follows:
- PHP 8.3
- Composer
- Web Server with the ability to always serve app/public/index.php regardless of the URL of each request (e.g. Nginx or Apache, the Docker setup uses Nginx)
- MariaDB database server.

You'll need to configure the web server such that `/app/public` is the root directory (with requests that start with `/assets` being allowed access to the `/app/assets` folder), and such that regardless of the URL, the web server always loads up `app/public/index.php`. If you're using Nginx, you can use this repo's [nginx.conf file](https://github.com/kaelonR/swcombine-prospecting-results/blob/main/nginx.conf) as a reference for configuring your own Nginx server.

You'll need to set your database credentials in `app/init.php` line 10.

Run `composer install` and `composer dump-autoload` to download and install the packages used. You may need to [Install Composer](https://getcomposer.org/download/) if you don't have it installed on your machine yet.

Once done, make a GET request to `/init` (doing this in the browser is fine) to have the database initialized with a schema and tables ready to go. If you see the message 'Database initiated successfully' then everything is set up and you'll be able to use the app locally.

## App Architecture
The app is set up as an MVC (Model - View - Controller) app, with [PHP-DI](https://php-di.org/) used for Dependency Injection, with [Twig](https://twig.symfony.com/) as view rendering engine, and [nikic/fast-route](https://github.com/nikic/FastRoute) used for dynamic routing, rather than using a file structure-based routing pattern. Finally [rmccue/requests](https://github.com/WordPress/Requests) is used for easier sending of HTTP requests, rather than working with the quirky CURL API.

### Program Flow
The entrypoint of the app is `/app/public/index.php`. This file will first load `/app/init.php`, which in turn sets up Dependency Injection, configures PDO with the database credentials, and configures Twig for rendering views.
Once the app has been initialised a `runRoute` function is exposed, which can create an instance of a controller and run an arbitrary method with some parameters. The `app/public/index.php` file uses this to set up a dynamic router, supply it the possible URLs, and then uses `runRoute` to wire each route up to a method on a controller.

If the URL being requested is one that will render a view, a method on a controller extending `UIControllerBase` will be executed. This controller will then execute the `render` method, which tells twig which view to render. This view can be found in `app/views`.

If the URL starts with `/api`, then a method on a controller extending `ApiControllerBase` will be executed. This controller has three helper methods, `respondJson`, `respondStatusCode` and `respondWithError`. The API controllers will do input sanitation and validation, call the relevant database repositories or the `StarWarsCombineClient` when requesting data from the SWC webservices. All read/write operations are done through the API controllers.

## App Cache
When using the 'Import planet from SWC' feature, an `/app/cache` directory will be created, where data retrieved from the SWC webservices will be stored, as a way to cut down on the number of requests to SWC. Whenever the relevant file is found and determined to not be too old, the data will be grabbed from that file and no HTTP request will be sent. The cache stores three distinct pieces of data, with different expiration times:
- List of all systems in the game - cached for 24 hours. If `/app/cache/systems.txt` is older than 24 hours this list will be fetched again. This is also the slowest operation, taking ~15 seconds on average to retrieve from the webservices, so pulling this from cache is preferable.
- List of planets within a system - cached indefinitely
- Info for a specific planet - cached indefinitely

When you want to invalidate any of the cached data, simply delete the file. This may be necessary if you change any of the model classes.
