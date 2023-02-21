<?php

use Illuminate\Support\Str;
use STS\HubSpot\Sdk;

dataset('SdkTypes', function () {
    $sdk = new Sdk();
    $properties = new ReflectionProperty($sdk, 'models');
    foreach ($properties->getValue($sdk) as $key => $modelClass) {
        yield $key;
    }
});

dataset('SdkTypes-singular',  function () {
    $sdk = new Sdk();
    $properties = new ReflectionProperty($sdk, 'models');
    foreach ($properties->getValue($sdk) as $key => $modelClass) {
        yield Str::singular($key);
    }
});

dataset('SdkTypes-both',  function () {
    $sdk = new Sdk();
    $properties = new ReflectionProperty($sdk, 'models');
    foreach ($properties->getValue($sdk) as $key => $modelClass) {
        yield $key;
        yield Str::singular($key);
    }
});
