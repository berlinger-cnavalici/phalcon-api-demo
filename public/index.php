<?php
/**
 * This is based on the micro application approach
 * it can use Controllers as Handlers as well
 *      https://docs.phalconphp.com/en/3.0.0/reference/micro.html#using-controllers-as-handlers
 *
 */
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Micro\Collection as MicroCollection;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Sqlite;
use Phalcon\Http\Response;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;

use Responses\JsonErrorResponse;


define('APP_PATH', __DIR__ . "/../");

$loader = new Loader();
$loader->registerNamespaces([
    "API" => APP_PATH . "/models/API/",
    "Responses" => APP_PATH . "/lib/Responses/",
    "Controllers" => APP_PATH . "/controllers/",
]);
$loader->register();


/**
 * Include composer autoloader
 */
require APP_PATH . "/vendor/autoload.php";

$di = new FactoryDefault();
$di->set(
    "db",
    function () {
        return new Sqlite([
            "dbname" => APP_PATH . "api.sqlite"
        ]);
    }
);

// JWT generic generation token function
$generateToken = function() {
    $token = (new Builder())
        ->setIssuer('http://products.host')     // Configures the issuer (iss claim)
        ->setAudience('http://products.host')   // Configures the audience (aud claim)
        ->setId('4f1g23a12aa', true)            // Configures the id (jti claim), replicating as a header item
        ->setIssuedAt(time())                   // Configures the time that the token was issue (iat claim)
        ->setNotBefore(time())             // Configures the time that the token can be used (nbf claim)
        ->setExpiration(time() + 3600)          // Configures the expiration time of the token (nbf claim)
        ->set('uid', 1)                         // Configures a new claim, called "uid"
        ->getToken();                           // Retrieves the generated token


    $token->getHeaders(); // Retrieves the token headers
    $token->getClaims(); // Retrieves the token claims

//            echo $token->getHeader('jti'); // will print "4f1g23a12aa"
//            echo $token->getClaim('iss'); // will print "http://example.com"
//            echo $token->getClaim('uid'); // will print "1"
    echo "\nUse this token instead:\n";
    echo $token; // The string representation of the object is a JWT string (pretty easy, right?)
};

# ROUTING PART
$app = new Micro($di);

$app->before(
    // new SomeMiddleware() implements MiddlewareInterface -> public function call
    function () use ($app, $generateToken) {
        $authToken = $app->request->getHeader('Authorization');

        try {
            $token = (new Parser())->parse($authToken); // Parses from a string
            $token->getHeaders(); // Retrieves the token header
            $token->getClaims(); // Retrieves the token claims
        } catch (Exception $e) {
            echo "MALFORMED OR MISSING TOKEN\n";
            $generateToken();
            return false;
        }

        $data = new ValidationData(); // It will use the current time to validate (iat, nbf and exp)
        $data->setIssuer('http://products.host');
        $data->setAudience('http://products.host');
        $data->setId('4f1g23a12aa');

        if (!$token->validate($data)) {
            // Return false stops the normal execution
            echo "Failed authorization.\n";

            return false;
        }

        return true;
    }
);

$app->get(
    "/api/products",
    function() use ($app) {
        // example of phql usage in relation with modelsManager
        $phql = "SELECT * FROM API\\Products";

        $products = $app->modelsManager->executeQuery($phql);

        // OR
        // $products = API\Products::find();

        return json_encode($products);
    }
);

$app->get(
    "/api/products/{id:[0-9]+}",
    function($id) {
        $product = API\Products::findFirst($id);

        return json_encode($product);
    }
);

$app->post(
    "/api/products",
    function() use ($app) {
        $input = $app->request->getJsonRawBody();

        $newProduct = new API\Products((array)$input);
        $response = new Response();

//        try-catch block is replaced by a more general approach $app->error....(see below)
//        try {
        if ($newProduct->create() === true) {
            $response->setStatusCode(201);

            $response->setJsonContent([
                "status"   => "OK",
                "messages" => $newProduct->getId(),
            ]);
        } else {
//                foreach ($newProduct->getMessages() as $message) {
//                    $errors[] = $message->getMessage();
//                }

            $response->setJsonContent([
                "status"   => "ERROR",
                "messages" => $newProduct->getErrorsAsArray()
            ]);
        }
//        } catch (Exception $e) {
//            $response = new JsonErrorResponse($e->getMessage());
//        }

        return $response;
    }
);

$app->delete(
    "/api/products/{id:[0-9]+}",
    function($id) {
        $response = new Response();
        $product = API\Products::findFirst($id);

        if ($product && $product->delete() === true) {
            $response->setJsonContent([
                "status"   => "OK",
                "messages" => "Your product was deleted",
            ]);
        } else {
            $response->setJsonContent([
                "status"   => "NOT FOUND",
                "messages" => "Product with id: $id was not found."
            ]);
        }

        return $response;
    }
);

$app->error(
    function ($exception) {
        return new JsonErrorResponse($exception->getMessage());
    }
);

// $app->mount(new ApiRoutes()); // file with grouped routes

$app->handle();