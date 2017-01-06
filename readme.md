tueena-lib/service-locator
==========================
Two classes to realize dependency injection for php 7.

Features
--------
* Very tiny: Two classes and one interface (< 200 lines of code).
* Tet driven developed. Code coverage: 100%.
* `ServiceLocator` is immutable.
* Every class can be registered as service (no interface required).
* Services can be registered by class name, by interface name or by a factory method (closure).
* Services that are required by a service in the constructor are injected automatically.
* Already registered services cannot be overwritten.
* `Injector` is a static class (it does not contain any state).
* With the injector you can build classes, call methods, static methods, functions, closures and 
invoke classes.

Usage
-----
This package comes with a class to register services (the `ServiceLocator`) and a static class to inject services into
constructors, methods, static methods, functions, closures and invoke methods (the `Injector`).

```php
<?php

use tueenaLib\serviceLocator;

// The ServiceLocator is immutable. So the register() method will return a new instance of
// the ServiceLocator on each call.
$serviceLocator = (new ServiceLocator)
  // Define a concrete class, that implements the interface of a service.
  // The constructor of the class may require other services as parameters.
  // They will be injected automatically, if they are registered.
  ->register(IConfiguration::class, Configuration::class)
  // Or define a factory function. The factory function may require other
  // services as well.
  ->register(ISomeApi::class, function (IConfiguration $configuration) { return new SomeApi($configuration->getApiKey()); })
  // First parameter may also be an abstract or concrete class.
  // The second parameter can be omitted, if the first parameter is a
  // concrete class.
  ->register(MyMailer::class);

// The ServiceLocator provides two more functions:
if ($serviceLocator->has(MyMailer::class))
	$myMailer = $serviceLocator->get(MyMailer::class);
// The get() method throws an exception, if the service is not registered.

// The second class of the package is the static Injector class.
// Use it to inject services into all kind of callables...

$myObject = Injector::invokeConstructor($serviceLocator, MyClass:class);
$result = Injector::invokeMethod($serviceLocator, $anObject, 'aMethod');
$result = Injector::invokeStaticMethod($serviceLocator, MyClass:class), 'aMStaticethod';
$result = Injector::invokeInvokeMethod($serviceLocator, $anObject);
$result = Injector::invokeFunction($serviceLocator, 'namespace\\myFunction');
$result = Injector::invokeClosure($serviceLocator, function (MyMailer $mailer) { $mailer->sendSomeMessage(); });
```

Best practices
--------------
* If you register the services by interfaces, then you can replace concrete
 implementations and it's very easy to mock those services in your unit tests.
* Passing around the `ServiceLocator` will hide dependencies. You only see the 
 `ServiceLocator` as dependency in the code, but don't know, what services are really 
 required. Of course you can register the `ServiceLocator` instance as service itself,
 but I would not recommend it. Try to move each call to a method of the `Injector` 
 close to the front controller. Typically you would use it within some kind of router 
 to map a http request or cli command to a controller or something like this (if you 
 don't want to hard code the creation of the controller). After that point, there should 
 be no need to access the `ServiceLocator` anymore.

License
-------
MIT

Requirements
------------
php >= 7.0.0

Installation
------------
If you use `Composer`:
```
composer require tueena-lib/service-locator
```
Otherwise just download the two classes and the interface and use it.

Contact
-------
Bastian Fenske <bastian.fenske@tueena.org>
