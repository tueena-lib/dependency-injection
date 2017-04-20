tueena-lib/dependency-injection
===============================
This package provides two classes to realize inversion of control and dependency injection for php 7
applications.

You register services to a service locator by telling the service locator how to identify the service
(a class name or better an interface name) and how to build the service instance. You can pass the
name of the concrete class here or a closure, that will return the service instance. Other services
that are required by the constructor or factory method of the service are injected automatically.

All services are only build on demand and only once.

The second class of the library is a static `Injector` class. It provides methods to inject
services into constructors, methods, static methods, closures, functions and invoke methods.

Features and design decisions
-----------------------------
* The library is very small: Two classes and one interface (< 200 lines of code). So you 
can easily understand the whole thing and also copy it into your source code to adapt it to
your needs or get rid of the dependency without blowing something up.
* Test driven developed. Code coverage: 100%.
* `ServiceLocator` is immutable.
* Every class can be registered as service (no interface or base class required, of course).
* Services are identified by a class name or interface name.
* Services implementations are defined by class name or a factory method (closure).
* Services that are required by a service constructor are injected automatically.
* Also required services are injected into the factory methods.
* Services are build on demand.
* Only one service instance for each registered service per service locator.
* Already registered services cannot be overwritten.
* Trying to get a service from the `ServiceLocator`, that has not been registered will
throw an exception.
* Requires explicit service registration: No "autowiring".
* No annotation support, no injection through setters or interfaces.
* `Injector` is a static class (it does not contain any state).
* With the injector you can build classes, call methods, static methods, functions, closures and 
invoke classes.

Usage
-----
Register services to the `ServiceLocator`:

```php
<?php

use tueenaLib\dependencyInjection\ServiceLocator;

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

// The ServiceLocator provides two more methods, but you probably will never use them.
// Use the injector instead.
if ($serviceLocator->has(MyMailer::class))
	$myMailer = $serviceLocator->get(MyMailer::class);
// The get() method throws an exception, if the service is not registered.
```

Use the injector to inject services into all kind of callables.

```php
use tueenaLib\dependencyInjection\Injector;

$myObject = Injector::invokeConstructor($serviceLocator, MyClass::class);
$result = Injector::invokeMethod($serviceLocator, $anObject, 'aMethod');
$result = Injector::invokeStaticMethod($serviceLocator, MyClass::class, 'aStaticMethod');
$result = Injector::invokeInvokeMethod($serviceLocator, $anObject);
$result = Injector::invokeFunction($serviceLocator, 'namespace\\myFunction');
$result = Injector::invokeClosure($serviceLocator, function (MyMailer $mailer) { $mailer->sendSomeMessage(); });
```

In practice, you will not have to deal with the `ServiceLocator` or the `Injector` very much in your
application. An example:

```php
use tueenaLib\dependencyInjection\ServiceLocator;
use tueenaLib\dependencyInjection\Injector;

// Define the services.
$serviceLocator = (new ServiceLocator)
    ->register(IConfiguration::class, function () { return include __DIR__ . '/../configuration/local.php'; })
    ->register(IRequest::class, function () { return new Request($_GET, $_POST, ...); }
    ->register(IRouter::class, Router::class)
    ->register(ILowLevelMailer::class, LowLevelMailer::class)
    ->register(
        IApplicationMailer::class,
        function (IConfiguration $configuration, ILowLevelMailer $lowLevelMailer) {
            $companyEMailAddress = $configuration->getCompanyEMailAddress();
            $signature = $configuration->getEMailSignature();
            return new ApplicationMailer($companyEMailAddress, $signature, $lowLevelMailer);
        }
    )
    ->register(IDatabase::class, function (IConfiguration $configuration) { return new Database($configuration->getDsn()); })
    ->register(IPayment::class, Payment::class)
    ->register(IPaymentApi::class, PaymentApi::class)
    ->register(IPaymentStorage::class, PaymentStorage::class)
    ->register(IUserRepository::class, UserRepository::class)
    ->register(ISession::class, Session::class);

// The routing.
$router = $serviceLocator->get(Router::class);
$request = $servcieLocator->get(Reuqest::class);
list ($controllerClassName, $controllerMethodName, $controllerParameters) = $router->resolve($request);

// Build the controller.
$controller = Injector::invokeConstructor($serviceLocator, $controllerClassName);

// Call the controller method with the parameters of the route.
$controllerResult = call_user_func_array([$controller, $controllerMethodName], $controllerParameters);

// Beyond this point, you will not use the $serviceLocator or Injector anymore.
// A contoller method for example would require an IApplicatiationMailer and an IPayment service in
// the constructor. The Payment service would require the IPaymentApi and the IPaymentStorage service.
// The PaymentStorage class would require the IDatabase service and so on. But this all is resolved
// automatically beyond this point.
```

Best practices
--------------
* If you register the services by interfaces, then you can replace concrete
 implementations and it's very easy to mock those services in your unit tests.
* Passing around the `ServiceLocator` will obfuscate dependencies. You only see the 
 `ServiceLocator` as dependency in the method signatures, but not the services 
 that are really required. Of course you can register the `ServiceLocator` instance
 as service itself, but I would not recommend it. Try to move each call to a method
 of the `Injector` close to the front controller (see the example above).
* We don't support injection through setters, properties (with annotations) or
 interfaces (that provides an extra method for the injection), because this would cause
 a separation of the object creation and the injection. This leads to an invalid
 object state between this two steps. This is not a problem when you use the DI
 framework, that supports this, because the framework will do the two steps before
 returning the instance. But it binds you to that framework or requires extra
 documentation how to use that class (first create it, then inject the required
 services).
* If you would need for example two instances of a database service, create a wrapper
 or subclass for each database.

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
composer require tueena-lib/dependency-injector
```
Otherwise just download the two classes and the interface and use it.

Contact
-------
Bastian Fenske <bastian.fenske@tueena.org>
