<?php

use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use STS\HubSpot\Api\Builder as ApiBuilder;
use STS\HubSpot\Api\Collection;
use STS\HubSpot\Api\Model as AbstractApiModel;
use STS\HubSpot\Api\PropertyDefinition;

beforeEach(function () {
    $this->builder = $this->createMock(ApiBuilder::class);
    $this->app->instance(ApiBuilder::class, $this->builder);
});

test('new model fills properties on creation', function () {
    $testValue = ['test_attribute' => sha1(random_bytes(11))];

    $model = new class($testValue) extends AbstractApiModel {
    };
    $attributes = new ReflectionProperty($model, 'properties');

    expect($attributes->getValue($model))
        ->toBe($testValue);
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

test('magic get returns null if nothing found', function() {
   $model = new class extends AbstractApiModel{};
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