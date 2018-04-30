# BitFrame\Message\DiactorosResponseEmitter

Zend Diactoros wrapper class to emit http response as a middleware.

### Installation

See [installation docs](https://www.bitframephp.com/middleware/response-emitter/zend-diactoros) for instructions on installing and using this middleware.

### Usage Example

```
use \BitFrame\Message\DiactorosResponseEmitter;

require 'vendor/autoload.php';

$app = new \BitFrame\Application;

$app->run([
    /* The response emitter must be the first middleware so that it
     * emits the response from all the middleware that follow. */
    DiactorosResponseEmitter::class
]);
```

### Tests

To execute the test suite, you will need [PHPUnit](https://phpunit.de/).

### Contributing

* File issues at https://github.com/designcise/bitframe-diactoros/issues
* Issue patches to https://github.com/designcise/bitframe-diactoros/pulls

### Documentation

Documentation is available at:

* https://www.bitframephp.com/middleware/response-emitter/zend-diactoros/

### License

Please see [License File](LICENSE.md) for licensing information.