<?php

use STS\HubSpot\Api\Association as ApiAssociation;
use STS\HubSpot\Api\Builder as ApiBuilder;
use STS\HubSpot\Api\Collection;
use STS\HubSpot\Api\Model as AbstractApiModel;

beforeEach(function () {
    $this->builder = $this->createMock(ApiBuilder::class);
    $this->sourceModel = $this->createMock(AbstractApiModel::class);
    $this->targetModel = $this->createMock(AbstractApiModel::class);
    $this->association = new ApiAssociation(source: $this->sourceModel, target: $this->targetModel);
});

test('sourceBuilder returns builder from sourceModel', function () {
    $this->sourceModel->expects($this->once())->method('builder')->willReturn($this->builder);
    $this->targetModel->expects($this->never())->method('builder');
    $this->association->sourceBuilder();
});

test('builder returns builder from targetModel', function () {
    $this->sourceModel->expects($this->never())->method('builder');
    $this->targetModel->expects($this->once())->method('builder')->willReturn($this->builder);
    $this->association->builder();
});

test('get doesnt call load when collection set', function () {
    $testCollection = new Collection(['test' => $this->getName()]);
    $this->targetModel->expects($this->never())->method('builder');
    (new ReflectionProperty($this->association, 'collection'))->setValue($this->association, $testCollection);
    $this->assertSame($testCollection, $this->association->get());
});

test('load calls target builders findMany and returns collection', function () {
    $testCollection = new Collection(['test' => $this->getName()]);
    $ids = [123, 345, 567];
    $this->targetModel->method('builder')->willReturn($this->builder);
    $this->builder->expects($this->once())->method('findMany')->with($ids)->willReturn($testCollection);

    (new ReflectionProperty($this->association, 'ids'))->setValue($this->association, $ids);
    $this->assertSame($testCollection, $this->association->load());
});

test('get calls load when collection isnt set', function () {
    $ids = [123, 345, 567];
    $testCollection = new Collection(['test' => $this->getName()]);
    (new ReflectionProperty($this->association, 'ids'))->setValue($this->association, $ids);
    $this->targetModel->method('builder')->willReturn($this->builder);
    $this->builder->expects($this->once())->method('findMany')->with($ids)->willReturn($testCollection);
    $this->assertSame($testCollection, $this->association->get());
});

test('attach gets id from model when targetId is model', function () {
    $testId = random_int(100, 999);
    $anotherModel = $this->createMock(AbstractApiModel::class);
    $anotherModel->expects($this->once())->method('__get')->with('id')->willReturn($testId);
    $this->sourceModel->expects($this->once())->method('builder')->willReturn($this->builder);
    $this->builder->expects($this->once())->method('associate')->with($this->targetModel, $testId);

    $this->association->attach($anotherModel);
});

test('attach uses passed targetId', function () {
    $testId = random_int(100, 999);
    $this->sourceModel->expects($this->once())->method('builder')->willReturn($this->builder);
    $this->builder->expects($this->once())->method('associate')->with($this->targetModel, $testId);

    $this->association->attach($testId);
});
