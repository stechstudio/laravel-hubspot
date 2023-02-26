<?php

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use STS\HubSpot\Api\Association;
use STS\HubSpot\Api\Builder as ApiBuilder;
use STS\HubSpot\Api\Collection;
use STS\HubSpot\Api\Model as AbstractApiModel;
use STS\HubSpot\Api\PropertyDefinition;
use STS\HubSpot\Crm\Property;

beforeEach(function () {
    $this->builder = $this->createMock(ApiBuilder::class);
    $this->app->instance(ApiBuilder::class, $this->builder);
});

test('new model fills properties on creation', function () {
    $testValue = ['test_attribute' => sha1(random_bytes(11))];

    (new class($testValue) extends AbstractApiModel {
        public function fill(array $properties): static
        {
            Assert::assertArrayHasKey('test_attribute', $properties);

            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            Assert::assertSame('__construct', $trace['function']);
            Assert::assertSame(AbstractApiModel::class, $trace['class']);
            return $this;
        }
    });
});

test('new model does not call fill when empty params', function () {
    new class([]) extends AbstractApiModel {
        public function fill(array $properties): static
        {
            Assert::fail('fill was called from __construct when empty params');
        }
    };
    $this->addToAssertionCount(1);
});

test('fill does not fill hubspot types', function (string $type) {
    $baseData = ['test_name' => $this->getName()];
    $model = (new class extends AbstractApiModel {
    })->fill([$type => sha1(random_bytes(11)), ...$baseData]);

    $properties = new ReflectionProperty($model, 'properties');
    expect($properties->getValue($model))->toBe($baseData);
})->with('SdkTypes-both');

test('update calls fill & save', function () {
    $testValue = ['test_attribute' => sha1(random_bytes(11))];

    (new class extends AbstractApiModel {
        public function fill(array $properties): static
        {
            Assert::assertArrayHasKey('test_attribute', $properties);
            $this->assertBacktraceIsUpdate();
            return $this;
        }

        public function save(): static
        {
            $this->assertBacktraceIsUpdate();
            return $this;
        }

        protected function assertBacktraceIsUpdate(): void
        {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2];
            dump($trace);
            Assert::assertSame('update', $trace['function']);
            Assert::assertSame(AbstractApiModel::class, $trace['class']);
        }

    })->update($testValue);
});

test('setting value on model sets property key to value', function () {
    $model = new class extends AbstractApiModel {
    };
    $attributes = new ReflectionProperty($model, 'properties');

    $propName = sha1(random_bytes(11));
    $propValue = sha1(random_bytes(11));

    $model->$propName = $propValue;

    expect($attributes->getValue($model))
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey($propName, $propValue);
});

test('setting hubspot types on model does not set value', function (string $propName) {
    $model = new class extends AbstractApiModel {
    };
    $attributes = new ReflectionProperty($model, 'properties');

    $propValue = sha1(random_bytes(11));

    $model->$propName = $propValue;

    expect($attributes->getValue($model))
        ->toBeArray()
        ->toBeEmpty();
})
    ->with('SdkTypes-both');

test('builder returns api builder', function () {
    $model = new class extends AbstractApiModel {
    };

    $this->builder
        ->expects($this->once())
        ->method('for')
        ->with($model)
        ->willReturnSelf();

    expect($model->builder())
        ->toBe($this->builder);
});

test('static query returns api builder', function () {
    $model = new class extends AbstractApiModel {
    };

    $this->builder
        ->expects($this->once())
        ->method('for')
        ->with($this->callback(
            function ($param) {
                $this->assertInstanceOf(AbstractApiModel::class, $param);
                return true;
            }
        ))
        ->willReturnSelf();

    expect($model::query())
        ->toBe($this->builder);
});

test('magic get value on model calls getFromProperties when property set', function () {
    $propName = sha1(random_bytes(11));
    $propValue = sha1(random_bytes(11));

    $model = (new class extends AbstractApiModel {
        protected string $type = 'deals';
        private string $expectedKey;

        public function setExpectedKey(string $expectedKey)
        {
            $this->expectedKey = $expectedKey;
            return $this;
        }

        public function getFromProperties($key): mixed
        {
            Assert::assertSame($this->expectedKey, $key);
            return $this->properties[$key];
        }
    })->setExpectedKey($propName);

    $this->builder->method('for')->willReturnSelf();

    $model->__set($propName, $propValue);

    expect($model->__get($propName))->toBe($propValue);
});

test('magic get value on model calls getAssociations when HubSpot::isType', function (string $type) {
    $this->builder->method('for')->willReturnSelf();
    $testReturn = new Collection(['test' => $this->getName(), 'type' => $type]);

    $model = (new class extends AbstractApiModel {
        private string $expectedType;
        private Collection $testReturn;

        public function getAssociations($type): Collection
        {
            Assert::assertSame($this->expectedType, $type);
            return $this->testReturn;
        }

        public function setTestExpectations(string $expectedType, Collection $testReturn): self
        {
            $this->expectedType = $expectedType;
            $this->testReturn = $testReturn;
            return $this;
        }
    })->setTestExpectations($type, $testReturn);

    expect($model->__get($type))->toBe($testReturn);

})->with('SdkTypes');
test('magic get value on model calls getAssociations when HubSpot::isType singular', function (string $singularType) {
    $this->builder->method('for')->willReturnSelf();
    $testReturn = new Collection(['test' => $this->getName(), 'type' => $singularType]);
    $model = (new class extends AbstractApiModel {
        private string $expectedType;
        private Collection $testReturn;

        public function getAssociations($type): Collection
        {
            Assert::assertSame(Str::plural($this->expectedType), $type);
            return $this->testReturn;
        }

        public function setTestExpectations(string $expectedType, Collection $testReturn): self
        {
            $this->expectedType = $expectedType;
            $this->testReturn = $testReturn;
            return $this;
        }
    })->setTestExpectations($singularType, $testReturn);

    expect($model->__get($singularType))
        ->toBeString()
        ->toBe($testReturn->first());

})->with('SdkTypes-singular');

test('magic get definitions on model calls builder for values', function () {
    $this->builder->method('for')->willReturnSelf();
    $mockPropertyDefinition = $this->createMock(PropertyDefinition::class);
    $this->builder
        ->expects($this->once())
        ->method('definitions')
        ->willReturn($mockPropertyDefinition);

    $testCollection = new Collection(['a' => 'b']);
    $mockPropertyDefinition
        ->expects($this->once())
        ->method('get')
        ->willReturn($testCollection);

    $model = new class extends AbstractApiModel {
    };

    expect($model->__get('definitions'))->toBe($testCollection);
});
test('magic get gets payload if exists', function () {
    $this->builder->method('for')->willReturnSelf();
    $propName = sha1(random_bytes(11));

    $model = (new class extends AbstractApiModel {
        private string $expectedKey;

        public function setTestExpectations(string $key, string $value): self
        {
            $this->expectedKey = $key;
            $this->payload[$key] = $value;
            return $this;
        }

        public function getFromPayload($key, $default = null): mixed
        {
            Assert::assertSame($this->expectedKey, $key);
            Assert::assertNull($default);
            return parent::getFromPayload($key, $default);
        }
    })->setTestExpectations($propName, $this->getName());

    expect($model->__get($propName))
        ->toBeString()
        ->toBe($this->getName());
});

test('magic get returns null if nothing found', function () {
    $model = new class extends AbstractApiModel {
    };
    expect($model->__get(sha1(random_bytes(11))))->toBeNull();
});

test('type returns internal type', function () {
    $this->builder->method('for')->willReturnSelf();
    $model = new class extends AbstractApiModel {
    };
    $type = sha1(random_bytes(11));
    $property = new ReflectionProperty($model, 'type');
    $property->setValue($model, $type);
    expect($model->type())->toBe($type);
});

test('endpoints returns internal endpoints', function () {
    $model = new class extends AbstractApiModel {
    };

    $property = new ReflectionProperty($model, 'endpoints');
    $method = new ReflectionMethod($model, 'endpoints');

    expect($method->invoke($model))
        ->toBe($property->getValue($model));
});

test('hasNamedScope returns correct value', function () {
    $model = new class extends AbstractApiModel {
        public function scopeTest()
        {
        }
    };

    expect($model->hasNamedScope('test'))
        ->toBeTrue()
        ->and($model->hasNamedScope($this->getName()))
        ->toBeFalse();
});

test('toArray returns payload', function () {
    $model = new class extends AbstractApiModel {
    };

    $payload = [
        'test' => $this->getName(),
        'rng' => sha1(random_bytes(11)),
    ];

    $property = new ReflectionProperty($model, 'payload');
    $property->setValue($model, $payload);

    expect($model->toArray())->toBe($payload);
});

test('callNamedScope calls Named Scope', function () {
    $model = new class extends AbstractApiModel {
        public function scopeTestScope(...$parameters)
        {
            foreach ($parameters as &$value) {
                $value++;
            }

            return $parameters;
        }
    };

    expect($model->callNamedScope('TestScope', range(1, 5)))
        ->toBeArray()
        ->toBe(range(2, 6));
});

test('__isset returns when property is set', function () {
    $model = new class extends AbstractApiModel {
    };
    $propName = sha1(random_bytes(11));
    $propValue = sha1(random_bytes(11));

    expect($model->__isset($propName))
        ->toBeFalse()
        ->and(isset($model->$propName))
        ->toBeFalse();

    $model->$propName = $propValue;

    expect($model->__isset($propName))
        ->toBeTrue()
        ->and(isset($model->$propName))
        ->toBeTrue();
});

test('cast casts based on type', function () {
    $model = new class extends AbstractApiModel {
    };
    $castMethod = new ReflectionMethod($model, 'cast');

    expect($castMethod->invoke($model, '123', 'int'))->toBeInt()
        ->and($castMethod->invoke($model, '2023-02-23', 'datetime'))->toBeInstanceOf(Carbon::class)
        ->and($castMethod->invoke($model, '123', 'array'))->toBeArray()
        ->and($castMethod->invoke($model, 123, 'string'))->toBeString();
});

test('cast invalid datetime throws exception', function () {
    $model = new class extends AbstractApiModel {
    };
    $castMethod = new ReflectionMethod($model, 'cast');
    $castMethod->invoke($model, 'abc', 'datetime');
})->throws(InvalidFormatException::class);

test('cast to string throws exception on array', function () {
    $model = new class extends AbstractApiModel {
    };
    $castMethod = new ReflectionMethod($model, 'cast');
    $castMethod->invoke($model, ['abc'], 'string');
})->throws(ErrorException::class, 'Array to string conversion');

test('cast to int does weird', function () {
    $model = new class extends AbstractApiModel {
    };
    $castMethod = new ReflectionMethod($model, 'cast');
    expect($castMethod->invoke($model, ['abc'], 'int'))->toBe(1)
        ->and($castMethod->invoke($model, '0x16', 'int'))->toBe(0);
});

test('getFromPayload returns casted properties', function () {
    $model = new class extends AbstractApiModel {
        private string|null $expectedType;

        protected function cast($value, $type = null): mixed
        {
            Assert::assertSame($this->expectedType, $type);
            return parent::cast($value, $type);
        }

        public function setExpected($key, $value = null, $type = null)
        {
            if ($type !== null) {
                $this->schema[$key] = $type;
                $this->expectedType = $type;
            } elseif (array_key_exists($key, $this->schema)) {
                $this->expectedType = $this->schema[$key];
            }

            if ($value !== null) {
                $this->payload[$key] = $value;
            }
            return $this;
        }
    };

    $idValue = random_int(11, 99);
    expect(
        $model->setExpected('id', value: (string)$idValue)->getFromPayload('id')
    )->toBe($idValue);
});

test('delete calls builder delete and sets exists false', function () {
    $model = new class extends AbstractApiModel {
        public bool $exists = true;
    };

    $this->builder->method('for')->willReturnSelf();
    $this->builder
        ->expects($this->once())
        ->method('delete');

    expect($model->delete())
        ->toBeTrue()
        ->and($model->exists)
        ->toBeFalse();
});

test('method calls are forwarded to associations for hubspot types', function (string $type) {
    $model = (new class extends AbstractApiModel {

        private string $expectedType;
        public function associations($type): Association
        {
            Assert::assertSame($this->expectedType, $type);
            return parent::associations($type);
        }

        public function setExpected(string $expectedType): self
        {
            $this->expectedType = $expectedType;
            return $this;
        }

    })->setExpected($type);

    $model->$type();
})->with('SdkTypes');

test('static calls get forwarded', function() {
    $model = new class extends AbstractApiModel {
        protected function mylittletestfunction(string $testMessage)
        {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            Assert::assertSame('__callStatic', $trace['function']);
            Assert::assertSame(AbstractApiModel::class, $trace['class']);
            Assert::assertSame('this is a test', $testMessage);
        }
    };

    $model::mylittletestfunction('this is a test');
});

test('only calls getFromProperties for each item passed', function() {
    $model = new class extends AbstractApiModel {
        public const TESTVALUES = ['a' => 1, 'yes' => 2, 'b' => 3, 'no' => 4, 'k' => 5];

        public function getFromProperties($key): mixed
        {
            Assert::assertArrayHasKey($key, static::TESTVALUES);
            return static::TESTVALUES[$key];
        }
    };

    $this->assertSame($model::TESTVALUES, $model->only(array_keys($model::TESTVALUES)));
    $this->assertSame($model::TESTVALUES, $model->only(...array_keys($model::TESTVALUES)));
});

test('getFromProperties returns item when instance of Property', function() {
    $model = new class extends Property {
    };

    $this->builder->method('for')->willReturnSelf();
    $this->builder->expects($this->never())->method('definitions');

    $model->testProperty = sha1(random_bytes(11));
    $this->assertSame($model->testProperty, $model->getFromProperties('testProperty'));
});

test('getFromProperties returns item when definitions is missing key ', function() {
    $model = new class extends AbstractApiModel {
    };

    $propertyDefinition = $this->createMock(PropertyDefinition::class);
    $collectionMock = $this->createMock(Collection::class);
    $this->builder->method('for')->willReturnSelf();
    $this->builder->method('definitions')->willReturn($propertyDefinition);
    $propertyDefinition->method('get')->willReturn($collectionMock);
    $collectionMock
        ->expects($this->once())
        ->method('has')
        ->with('testProperty')
        ->willReturn(false);

    $sha1 = sha1(random_bytes(11));
    $model->testProperty = $sha1;
    $this->assertSame($sha1, $model->getFromProperties('testProperty'));
});

test('getFromProperties returns unserialized definition when definitions is matches key', function() {
    $model = new class extends AbstractApiModel {
    };

    $sha1 = sha1(random_bytes(11));
    $sha2 = sha1(random_bytes(11));
    $model->testProperty = $sha1;

    $propertyDefinition = $this->createMock(PropertyDefinition::class);
    $collectionMock = $this->createMock(Collection::class);
    $mockProperty = $this->createMock(Property::class);
    $this->builder->method('for')->willReturnSelf();
    $this->builder->method('definitions')->willReturn($propertyDefinition);
    $propertyDefinition->method('get')->willReturn($collectionMock);
    $collectionMock
        ->expects($this->once())
        ->method('has')
        ->with('testProperty')
        ->willReturn(true);
    $collectionMock->expects($this->once())
        ->method('get')
        ->with('testProperty')
        ->willReturn($mockProperty);

    $mockProperty->expects($this->once())
        ->method('unserialize')
        ->with($sha1)
        ->willReturn($sha2);

    $this->assertSame($sha2, $model->getFromProperties('testProperty'));
});