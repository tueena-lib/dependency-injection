tueena-lib/dependency-injection
===============================

This package provides two classes to realize dependency injection for php 7 applications.

You register services to a service locator by telling the service locator the interface name that
identifies the service within the service locator and either the name of the class that will be
instantiated to create the service or a factory method (a closure) that will return the service 
instance.

Other services that are required by the constructor or factory method of the service are injected automatically.

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
* Services are identified by an interface name.
* Services implementations are defined by class name or a factory method (closure).
* Services that are required by a service constructor or factory are injected automatically.
* Services are build on demand.
* Only one service instance for each registered service per service locator.
* Already registered services cannot be overwritten.
* Trying to get a service from the `ServiceLocator`, that has not been registered will
throw an exception.
* Requires explicit service registration: No "autowiring".
* By design no support for injection by annotations, through setters or interfaces.
* `Injector` is a static class (it does not contain any state).
* With the injector you can build classes, call methods, static methods, functions, closures and 
invoke classes.

Usage
-----
Register services to the `ServiceLocator`:

```php
use tueenaLib\dependencyInjection\ServiceLocator;

// The ServiceLocator is immutable. So the register*() methods will return new instances of
// the ServiceLocator on each call.
$serviceLocator = (new ServiceLocator)
  // Define a concrete class.
  // The constructor of the class may require other services as parameters.
  // They will be injected automatically, if they are registered.
  ->registerClass(IConfiguration::class, Configuration::class)
  // Or define a factory function. The factory function may require other
  // services as well.
  ->registerFactory(ISomeApi::class, function (IConfiguration $configuration) { return new SomeApi($configuration->getApiKey()); })
;

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

In practice, you will not have to deal with the `ServiceLocator` or the `Injector` very much
in your application. In fact, you don't want to.

Here an example use case: Let's say you have all the application agnostic business logic in 
classes in a namespace myApp\core. Now you have several applications that uses that core:
A REST API, an administration tool, some command line tools. Let's say, the REST API 
requires an `OrderInteractor`. It manages order entities and requires an object, that knows
how to persist orders somewhere.

You could write a script `core/init.php`, that returns a service locator:

```php
return function () {

	$serviceLocator = (new ServiceLocator)
		->registerFactory(IConfiguration::class, function () { return new Configuration(__DIR__ . '/configuration/...'); })
		->registerClass(IMySqlConnection::class, MySqlConnection::class)

		->registerClass(IOrderStorage::class, OrderMySqlStorage::class)
		->registerClass(IOrderInteractor::class, OrderInteractor::class)
	;
	return $serviceLocator;
};
```

You'll import that into your application:

```php
// applications/restApi/init.php

$coreInitializer = include __DIR__ . '/../core/init.php';
$coreServiceLocator = $coreInitializer();

// Add application specific services.
$applicationServiceLocator = $coreServiceLocator
	->registerClass(IWebSecurityPolicy::class, WebSecurityPolicy::class)
;

// some kind of routing...
$router = new Router;
$request = new Request($_GET, ...);
$controllerClassName = $router->resolveRequest();

$controller = Injector::invokeConstructor($applicationServiceLocator, $controllerClassName);
$result = $controller->execute($request);
```

As you can see, you're not going to deal with database connection within the applications.
But, you can use the database anyways to store application specific data.

Now your REST API controller could look like this:

```php
public function __construct(IWebSecurityPolicy $securityPolicy, IOrderInteractor $orderInteractor)
{
	$this->securityPolicy = $securityPolicy;
	$this->orderInteractor = $orderInteractor;
}

public function execute(HttpRequest $httpRequest)
{
	if ($this->securityPolicy->isIpAddressBlacklistedToOrder($request->getIpAddress()))
		...
	$processNewOrderRequest = self::createProcessNewOrderRequestFromHttpRequest($httpRequest);
	$this->orderInteractor->processNewOrder($processNewOrderRequest);
}
```

The `OrderInteractor` could look something like that:

```php
public function __construct(IOrderStorage $storage)
{
	$this->storage = $storage;
}

public function processNewOrder(ProcessNewOrderRequest $request)
{
	$order = self::createOrderFromRequest($request);
	$this->storage->saveNewOrder($order);
	// ...
}
```

As you can see, all the stuff that binds your code to tueena-lib is placed in one
file per module. In a bootstrap file at the entry point of the module or the front
controller. All the other files and classes are absolutely independent of tueena-lib. 
No annotations, no calls to `Injector`, no global service locator instance.

Best practices and notes
------------------------
* Keep in mind, that each call to `Injector` and each usage of the `ServiceLocator`
  will bind your software to this library. You want to avoid that. Better don't pass
  around the service locator. Define the services at one place (per module) within or
  close to the front controller (etc.). Also use the `Injector` only within the
  front controller or somewhere there. Don't spread that around.
* Also passing the service locator around will obfuscate the dependencies. You only see
  `IServiceLocator` as dependency in the method signatures, but not the services that 
  are really required.
* Think about the disadvantages of this "magical" dependency injection. Maybe you
  can wire up your application with manual dependency injection with factories to
  not have to create all the objects and database connections and api connections at
  every application request. Why do we need to use this library to pass the
  configuration into the database connection object in the example above? It's less code,
  of course. But is it better code?
* Expect problems, when you register services to the service locator after services have
  already been build. The already build instances are not copied into the new service
  locator instance returned by the `register*` methods.

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
