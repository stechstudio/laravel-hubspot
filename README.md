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
use STS\HubSpot\Crm\Contact;

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

When retrieving multiple objects, you will frequently want to filter, search, and order these results.
You can use a fluent interface to build up a query before retrieving the results. 

#### Adding filters

Use the `where` method to add filters to your query.
You can use any of the supported operators for the second argument, see here for the full list: [https://developers.hubspot.com/docs/api/crm/search#filter-search-results](https://developers.hubspot.com/docs/api/crm/search#filter-search-results);

This package also provides friendly aliases for common operators `=`, `!=`, `>`, `>=`, `<`, `<=`, `exists`, `not exists`, `like`, and `not like`.

```php
Contact::where('lastname','!=','Smith')->get();
```

You can omit the operator argument and `=` will be used.

```php
Contact::where('email', 'johndoe@example.com')->get();
```

For the `BETWEEN` operator, provide the lower and upper bounds as a two-element tuple.

```php
Contact::where('days_to_close', 'BETWEEN', [30, 60])->get();
```

> **Note**
> All filters added are grouped as "AND" filters, and applied together. Optional "OR" grouping is not yet supported.

#### Searching common properties

HubSpot supports searching through certain object properties very easily. See here for details:

[https://developers.hubspot.com/docs/api/crm/search#search-default-searchable-properties](https://developers.hubspot.com/docs/api/crm/search#search-default-searchable-properties)

Specify a search parameter with the `search` method:

```php
Contact::search('1234')->get();
```

#### Ordering

You can order the results with any property. 

```php
Contact::orderBy('lastname')->get();
```

The default direction is `asc`, you can change this to `desc` if needed.

```php
Contact::orderBy('days_to_close', 'desc')->get();
```

### Associations

HubSpot associations are handled similar to Eloquent relationships. 

#### Dynamic properties

You can access associated objects using dynamic properties.

```php
foreach(Company::find(555)->contacts AS $contact) {
    echo $contact->email;
}
```

#### Association methods

If you need to add additional constraints, use the association method. You can add any of the filtering, searching, or ordering methods described above.

```php
Company::find(555)->contacts()
    ->where('days_to_close', 'BETWEEN', [30, 60])
    ->search('smith')
    ->get();
```

#### Eager loading association IDs

Normally, there are three HubSpot API calls to achieve the above result:

1. Fetch the company object
2. Retrieve all the contact IDs that are associated to this company
3. Query for contacts that match the IDs

Now we can eliminate the second API call by eager loading the associated contact IDs. 
This library always eager-loads the IDs for associated companies, contacts, deals, and tickets. It does not eager-load
IDs for engagements like emails and notes, since those association will tend to be much longer lists.

If you know in advance that you want to, say, retrieve the notes for a contact, you can specify this up front.

```php
// This will only be two API calls, not three
Contact::with('notes')->find(123)->notes;
```

#### Creating associated objects

You can create new records off of the association methods.

```php
Company::find(555)->contacts()->create([
    'firstname' => 'Test',
    'lastname' => 'User',
    'email' => 'testuser@example.com'
]);
```

This will create a new contact, associate it to the company, and return the new contact.

You can also associate existing objects using `attach`. This method accepts and ID or an object instance.

```php
Company::find(555)->attach(Contact::find(123));
```



## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
