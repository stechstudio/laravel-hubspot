# HubSpot CRM SDK for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/stechstudio/laravel-hubspot.svg?style=flat-square)](https://packagist.org/packages/stechstudio/laravel-hubspot)

[//]: # ([![Total Downloads]&#40;https://img.shields.io/packagist/dt/stechstudio/laravel-hubspot.svg?style=flat-square&#41;]&#40;https://packagist.org/packages/stechstudio/laravel-hubspot&#41;)

Interact with HubSpot's CRM with an enjoyable, Eloquent-like developer experience.

- Familiar Eloquent CRUD methods `create`, `find`, `update`, and `delete`
- Associated objects work like relations: `Deal::find(555)->notes` and `Contact::find(789)->notes()->create(...)`
- Retrieving lists of objects feels like a query builder: `Company::where('state','NC')->orderBy('custom_property')->paginate(20)`
- Cursors provide a seamless way to loop through all records: `foreach(Contact::cursor() AS $contact) { ... }`

> **Note**
> Only the CRM API is currently implemented.  

## Installation

### 1) Install the package via composer:

```bash
composer require stechstudio/laravel-hubspot
```

### 2) Configure HubSpot

[Create a private HubSpot app](https://developers.hubspot.com/docs/api/private-apps#create-a-private-app) and give it appropriate scopes for what you want to do with this SDK. 

Copy the provided access token, and add to your Laravel `.env` file:

```bash
HUBSPOT_ACCESS_TOKEN=XXX-XXX-XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
```

## Usage

### Individual CRUD actions

#### Retrieving

You may retrieve single object records using the `find` method on any object class and providing the ID.

```php
$contact = Contact::find(123);
```

#### Creating

To create a new object record, use the `create` method and provide an array of properties.

```php
$contact = Contact::create([
    'firstname' => 'Test',
    'lastname' => 'User',
    'email' => 'testuser@example.com'
]);
```

Alternatively you can create the class first, provide properties one at a time, and then `save`.

```php
$contact = new Contact;
$contact->firstname = 'Test';
$contact->lastname = 'User';
$contact->email = 'testuser@example.com';
$contact->save();
```

#### Updating

Once you have retrieved or created an object, update with the `update` method and provide an array of properties to change.

```php
Contact::find(123)->update([
    'email' => 'newemail@example.com'
]);
```

You can also change properties individually and then `save`.

```php
$contact = Contact::find(123);
$contact->email = 'newemail@example.com';
$contact->save();
```

#### Deleting

This will "archive" the object in HubSpot.

```php
Contact::find(123)->delete();
```

### Retrieving multiple objects

Fetching a collection of objects from an API is different from querying a database directly in that you will always be limited in how many items you can fetch at once. You can't ask HubSpot to return ALL your contacts if you have thousands.

This package provides three different ways of fetching these results.

#### Paginating

Similar to a traditional database paginated result, you can paginate through a HubSpot collection of objects. 
You will receive a `LengthAwarePaginator` just like with Eloquent, which means you can generate links in your UI just like you are used to. 

```php
$contacts = Contact::paginate(20);
```

By default, this `paginate` method will look at the `page` query parameter. You can customize the query parameter key by passing a string as the second argument.

#### Cursor iteration

You can use the `cursor` method to iterate over the entire collection of objects. 
This uses [lazy collections](https://laravel.com/docs/9.x/collections#lazy-collections) and [generators](https://www.php.net/manual/en/language.generators.overview.php) 
to seamlessly fetch chunks of records from the API as needed, hydrating objects when needed, and providing smooth iteration over a limitless number of objects.

```php
// This will iterate over ALL your contacts!
foreach(Contact::cursor() AS $contact) {
    echo $contact->id . "<br>";
}
```

> **Warning**
> API rate limiting can be an obstacle when using this approach. Be careful about iterating over huge datasets very quickly, as this will still require quite a few API calls in the background.

#### Manually fetching chunks

Of course, you can grab collections of records with your own manual pagination or chunking logic. 
Use the `take` and `after` methods to specify what you want to grab, and then `get`.

```php
// This will get 100 contact records, starting at 501
$contacts = Contact::take(100)->after(500)->get();

// This will get the default 50 records, starting at the first one
$contacts = Contact::get();
```

### Searching and filtering

When retrieving multiple objects, you will frequently want to filter and search these results.
You can use a fluent interface to build up a query before retrieving the results. 

```php
Contact::wher
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
