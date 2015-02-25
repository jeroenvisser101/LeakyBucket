# LeakyBucket [![Build Status](https://travis-ci.org/jeroenvisser101/LeakyBucket.svg?branch=master)](https://travis-ci.org/jeroenvisser101/LeakyBucket)
Leaky Bucket is an algorithm which works as follows:

1. There is a bucket.
1. The bucket has a defined leak and defined capacity.
1. The bucket leaks at a constant rate.
1. Overflows when full, will not add other drops to the bucket.

## Usage

### Basic usage
``` php
<?php

use LeakyBucket\LeakyBucket;
use LeakyBucket\Storage\RedisStorage;

// Define which storage to use
$storage = new RedisStorage();

// Define the bucket
$settings = [
    'capacity' => 10,
    'leak'     => 1
];

// Create the bucket
$bucket = new LeakyBucket('example-bucket', $storage, $settings);

// Fill the bucket
$bucket->fill();

// Check if it's full
if ($bucket->isFull()) {
    header('HTTP/1.1 429 Too Many Requests');
    exit '<!doctype html><html><body><h1>429 Too Many Requests</h1><p>You seem to be doing a lot of requests. You\'re now cooling down.</p></body></html>';
}

// ...
```

### Other functionality
You can also do great stuff with it through the methods that LeakyBucket provides.

``` php
// Get capacity information
$capacityTotal = $bucket->getCapacity();
$capacityLeft  = $bucket->getCapacityLeft();
$capacityUsed  = $bucket->getCapacityUsed();

// Get the drops/second that the bucket leaks
$leakPerSecond = $bucket->getLeak();

// Get the last timestamp from when the bucket was updated
$timestamp = $bucket->getLastTimestamp();

// Set additional data
$bucket->setData(
    [
        'timeout' => 3600
    ]
);

// Set additional data
$data = $bucket->getData();

// Update the bucket with the leaked drops
$bucket->leak();

// Remove excess drops
$bucket->overflow();

// Update the bucket's timestamp manually
$bucket->touch();

// Fill the bucket with one drop
$bucket->fill();

// Fill the bucket with 5 drops
$bucket->fill(5);

// Spill one drop from the bucket
$bucket->spill();

// Spill 5 drops from the bucket
$bucket->spill(5);

// Remove the bucket's content
$bucket->reset();

// Force save
$bucket->save();
```


## Contributing
You can contribute by forking the repo and creating pull requests. You can also create issues or feature requests.

#### Checklist
1. Your code complies with the [PSR-2](http://www.php-fig.org/psr/psr-2/) standards. (check using the [php-cs-checker](http://cs.sensiolabs.org/))
1. Your code is fully tested and PHPUnit tests are also supplied.
1. Your code passes in TravisCI build.
1. By contributing your code, you agree to license your contribution under the MIT license.

## Boring legal stuff
This project is licensed under the MIT license. `LICENSE` file can be found in this repository.
