# Searchable trait for Laravel's Eloquent models

This package adds search/filtering functionality to Eloquent models in Laravel 4/5.

You could also find those 2 packages useful:

- [Sortable](https://github.com/jedrzej/sortable) - Allows sorting your models using request parameters
- [Withable](https://github.com/jedrzej/withable) - Allows eager loading of relations using request parameters

## Composer install

Add the following line to `composer.json` file in your project:

    "jedrzej/searchable": "dev-master"
	
or run the following in the commandline in your project's root folder:	


    composer require "jedrzej/searchable" "dev-master"

## Setting up searchable models

In order to make an Eloquent model searchable, add the trait to the model and define a list of fields that the model can be filtered by.
You can either define a $searchable property or implement a getSearchableAttributes method if you want to execute some logic to define list of searchable fields.

    use Jedrzej\Searchable\SearchableTrait;
    
    class Post extends Eloquent
    {
        use SearchableTrait;

        // either a property holding a list of searchable fields...
        public $searchable = ['title', 'forum_id', 'user_id', 'created_at'];

        // ...or a method that returns a list of searchable fields
        public function getSearchableAttributes()
        {
            return ['title', 'forum_id', 'user_id', 'created_at'];
        }
    }

In order to make all fields searchable put an asterisk * in the list of searchable fields:

    public $searchable = ['*'];

## Searching moddels

`SearchableTrait` adds a `filtered()` scope to the model - you can pass it a query being an array of filter conditions:
 
    // return all posts with forum_id equal to $forum_id
    Post::filtered(['forum_id' => $forum_id])->get();
    
    // return all posts with with <operator> applied to forum_id
    Post::filtered(['forum_id' => <operator>])->get();
 
 or it will use `Input::all()` as default:
    
    // if you append ?forum_id=<operator> to the URL, you'll get all Posts with <operator> applied to forum_id
    Post::filtered()->get();

## Choosing query mode
The default query mode is to apply conjunction (```AND```) of all queries to searchable model. It can be changed to disjunction (```OR```)
by setting value of `mode` query paramter to `or`. If the `mode` query parameter is already in use, name returned by `getQueryMode` method
will be used.
 
## Building a query

The `SearchableTrait` supports the following operators:
    
### Comparison operators
Comparison operators allow filtering based on result of comparison of model's attribute and query value. They work for strings, numbers and dates. They have the following for:
    
    (<operator>)<value>

The following comparison operators are available:

* `gt` for `greater than` comparison
* `ge` for `greater than or equal` comparison
* `lt` for `less than` comparison, e.g
* `le` for `les than or equal` comparison

In order to filter posts from 2015 and newer, the following query should be used:

    ?created_at=(ge)2015-01-01
    
### Equals/In operators
Searchable trait allows filtering by exact value of an attribute or by a set of values, depending on the type of value passed as query parameter. 
If the value contains commas, the parameter is split on commas and used as array input for `IN` filtering, otherwise exact match is applied.
    
In order to filter posts from user with id 42, the following query should be used:

    ?user_id=42
    
In order to filter posts from forums with id 7 or 8, the following query should be used:

    ?forum_id=7,8
    
### Like operators
Like operators allow filtering using `LIKE` query. This operator is triggered if exact match operator is used, but value contains `%` sign as first or last character.

In order to filter posts that start with `How`, the following query should be used:

    ?title=How%

```Notice:``` percentage character is used to encode special characters in URLs, so when sending the request make sure the tools
you use properly ```encode the % character as %25```
    
### Negation operator
It is possible to get negated results of a query by prepending the operator with `!`.
    
Some examples:
    
    //filter posts from all forums except those with id 7 or 8
    ?forum_id=!7,8
    
    //filter posts older than 2015
    ?created_at=!(ge)2015
    
### Multiple constraints for single attribute
It is possible to apply multiple constraints for a single model's attribute. 
In order to achieve that provide an array of query filters instead of a single filter:

    // filter all posts from year 20** except 2013
    ?created_at[]=20%&created_at[]=!2013%
