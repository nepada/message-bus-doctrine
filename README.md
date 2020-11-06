Message Bus Doctrine ORM Extension
==================================

[![Build Status](https://github.com/nepada/message-bus-doctrine/workflows/CI/badge.svg)](https://github.com/nepada/message-bus-doctrine/actions?query=workflow%3ACI+branch%3Amaster)
[![Coverage Status](https://coveralls.io/repos/github/nepada/message-bus-doctrine/badge.svg?branch=master)](https://coveralls.io/github/nepada/message-bus-doctrine?branch=master)
[![Downloads this Month](https://img.shields.io/packagist/dm/nepada/message-bus-doctrine.svg)](https://packagist.org/packages/nepada/message-bus-doctrine)
[![Latest stable](https://img.shields.io/packagist/v/nepada/message-bus-doctrine.svg)](https://packagist.org/packages/nepada/message-bus-doctrine)

Installation
------------

Via Composer:

```sh
$ composer require nepada/message-bus-doctrine
```


Usage
-----

### Recording domain events inside entities

Make your entities implement `Nepada\MessageBusDoctrine\Events\ContainsRecordedEvents` (e.g. by using `Nepada\MessageBusDoctrine\Events\PrivateEventRecorder` trait) and record domain events inside the entities. The events will be automatically collected and dispatched on flush.

### Transaction handling

`TransactionMiddleware` wraps the command handling into a database transaction. All changes made by lower layers are automatically flushed and commited, or rolled back on error.

It is highly recommended to use `PreventOuterTransactionMiddleware` to ensure there is no outer database transaction started outside of the message bus stack.
Not doing so might lead to unwanted behavior such as dispatching and processing events before the changes made in command handler were actually persisted.  

### Clearing entity manager

Use `ClearEntityManagerMiddleware` to clear entity manager before and/or after the message is handled.
Note: Entity manager will not be cleared when inside active transaction, i.e. the middleware order is important.


Credits
-------

Event recording idea and parts of its implementation are based on [simple-bus/doctrine-orm-bridge](https://github.com/SimpleBus/DoctrineORMBridge) by Matthias Noback, Cliff Odijk, Ruud Kamphuis.
