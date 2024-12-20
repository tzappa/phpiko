# Counters

Counters diagram:

![Counters Diagram](Counters%20diagram.png)

```php
<?php
// Create an instance of the counter service
$counters = new \Clear\Counters\Service(new \Clear\Counters\DatabaseProvider($pdoConnection));

// Increment views for post (defined by $postId) count by one and get the new value
$views = $counters->inc($postId . ' views');
// To get the current counter value (without incrementing it)
$comments = $counters->get($postId . ' comments');
// To set a value for example for initial setup
$counters->set($postId, 123);

```
