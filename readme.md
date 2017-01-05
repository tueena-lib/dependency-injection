tueenaLib/serviceLocator
========================
A tiny php service and class to realize dependency injection.

Requirements
------------
php >= 7.0.0

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
  // The constructor of the class may require other services as paraeters.
  // They will be injected automatically, if they are registered.
  ->register(IConfiguration::class, Configuration::class)
  // Or define a factory function. The factory function may require other
  // services as well.
  ->register(ISomeApi::class, function (IConfiguration $configuration) { return new SomeApi($configuration->getApiKey()); })
  // First parameter may also be an abstract or concrete class.
  // The second paraeter can be omittes, if the first parameter is a
  // concrete class.
  ->register(MyMailer::class);

// The ServiceLocator provides two more functions:
if ($serviceLocator->has(MyMailer::class))
	$myMailer = $serviceLocator->get(MyMailer::class);
// The get() method throws an exception, if the service is not registered.

// The second class of the package is the static Injector class.
// Use it to inject services into all kind of callables...

$myObject = Injector::injectConstructor($serviceLocator, MyClass:class);
$result = Injector::injectMethod($serviceLocator, $anObject, 'aMethod');
$result = Injector::injectStaticMethod($serviceLocator, MyClass:class), 'aMStaticethod';
$result = Injector::injectInvokeMethod($serviceLocator, $anObject);
$result = Injector::injectFunction($serviceLocator, 'namespace\\myFunction');
$result = Injector::injectClosure($serviceLocator, function (MyMailer $mailer) { $mailer->sendSomeMessage(); });
```

License
-------
MIT

Contact
-------
Bastian Fenske <bastian.fenske@tueena.org>
