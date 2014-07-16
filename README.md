Sockets
=======

Framework around socket streams using reactphp for the eventloop

Work in progress...

Async server is working. Very volatile API

Example usage:
```php
$loop = \React\EventLoop\Factory::create();

$context = [
    'options' => [
        'ssl' => [
            'local_cert' => 'cert.pem',
            'allow_self_signed' => true,
            'verify_peer' => false
        ],
    ],
];

$server = new Server($loop, $context);
$server->setProtocol(new Https());

$server->on("connecting", function(Connection $resource) {
    echo "CONNECTING " . $resource->getHandle()  . PHP_EOL;
});

$server->on("connected", function(Connection $resource) {
    echo "CONNECTED " . $resource->getHandle()  . PHP_EOL;
});

$server->on("data", function(HttpRequest $message, Connection $resource) {
    echo "PROTOCOL: " . $resource->protocol() . PHP_EOL;
    echo "URL: ". $message->getUrl() . PHP_EOL;
    $response = new HttpResponse(200, null, "Hello World");
    $resource->write($response);
    $resource->close();
});

$server->on("disconnect", function(Connection $resource) {
    echo "DISCONNECTED " . $resource->getHandle()  . PHP_EOL;
});

$server->on("close", function(Connection $resource){
//    echo "close" . PHP_EOL;
});

$server->on("end", function(Connection $resource){
//    echo "end" . PHP_EOL;
});

$server->listen(8080);
$loop->run();
```

Acknowledgements: laravel framework and reactphp
