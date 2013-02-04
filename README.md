<img src="http://i.imgur.com/6MVGm2A.png">

## What?
Too often, information is collected from users but is never applied to your application to understand what type of users are doing what on your site.

Too often, great content may be shared on your site, but your means of determining what is popular or relevant to your users is a challenge.

Enter Shadow, an analytics engine for developers that integrates seamlessly into your current system.

If Google Analytics is an external stats tracker, consider Shadow to be an internal one.

## Idea
Shadow is used within your system to keep track of "objects" attributes and users relations to them. An "object" represents anything in your application. For example, an "object" can be a Post, Image, or even a Comment. Imagine if everytime a user visited a post, you tracked the users gender. You'd be able to query that data later to say that "X% of the people that read this post are Male". That is what Attributes in Shadow are used for.

Relations are the users connection with an object. If you wanted to add a like button for pictures in your app, your object type might be "picture" and your operation would be "unary". Want to incorporate up/downvotes into your comments? With Shadow, all that is only a few lines of code.

## Use Cases?
* Impression Tracking
* User Demographic Analyzation
* Popularity
* Likes or Favorites
* Upvoting + Downvoting
* Star Ratings

## Requirements
* MySQL with PDO Support
* PHP 5.3+

## Terms

#### Meta Tracking
* **Simple Operation**: A simple operation would be a incrementing the count of an "impression" or number of "likes".
* **Complex Operations**: A complex property may be something like gender, a property in which there can be multiple values for (i.e. male or female).

#### Relation Tracking
* **Unary Operation**: This is an item that can only have one other state, such as a single upvote or a "like". Great for giving posts a fun element of competition.
* **Binary Operaions**: This is an item that can be voted up, voted down. Great for comments or content. Computes the Lower bound of Wilson score confidence interval for a Bernoulli parameter for Popularity
* **Multary Operations**: This is an item that can have a variable rating, for instance a star system 1-5 or a movie rating 1-10. Computes the Bayesian Average for Popularity


#The Code

##Instantiation
Require Shadow in your script, and initialize it. The only paramater of Shadow is a String. It represents your application namespace as to avoid object collision within your database.

```php
require( 'src/Shadow.php' );

$shadow = new Shadow( 'MyAppName' );
```

## Public Functions
```php 
type( $itemType ) 
```
* `$itemType`: String - The type of item you will be recording data for, i.e. "Post", "Comment", "Picture", etc.

```php 
attribute( $key )
```
* `$key`:  String - A key (as in key/value) to record data as. i.e. "impressions" or "cumComments"

```php 
item( $itemID, $timestamp = false )
```
* `$itemID`: String - A unique identifier for the object you would like to track. i.e. (setting 5 as the `itemID` with "post" set as the `itemType` infers that we are referencing Post ID#5)
* `$timestamp`: Int - If tracking the `relation` of an object, the objects timestamp must be passed as an Int to accurately determine the Popularity of the object

```php 
relation( $type, $user = false, $value = false )
```
* `$type`: String - Defines the relation type. Accepted values are `"unary"`, `"binary"`, or `"multary"`
* `$user`: Int - The ID of the user that will be relating to our object
* `#value`: Varies - The `value` parameter takes in a Boolean or an Int. More information about when certain types of values are to be used can be found below.

```php 
track()
```
* Calling `track` records the data we've compiled from the functions above.

```php 
get( $limt = false )
```
* Calling `get` retrieves the data we've compiled from the functions above.
* `$limit`: Int - Limit the number of items returned

# Usage

## Meta Operations

### Simple Operations
Simple operations are for keeping a count of a single attribute of an object.

**Tracking**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->attribute( "impressions" )
       ->track();
```

In this example, we are tracking "impressions" on Post ID #5. Everytime this is called, a count of impressions will be incremented.

**Getting**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->attribute( "impressions" )
       ->get();
```

Change the `track` function to the `get` function and in this example, the count of impressions will be returned.

Sample Return (if `track` was called 180 times)
```php
180
```

### Complex Operations
Complex operations are for keeping a count of a multiple sub-attributes of an object.

**Tracking**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->attribute( "gender/male" )
       ->track();
```

In this example, we are tracking the users "gender" (specifically, "male") on Post ID #5. If you have the users information, it may be beneficial to record the users meta information and associate it with on object so you can see what types of people are doing what on your website.

**Getting single sub-attribute**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->attribute( "gender/male" )
       ->get();
```

Change the `track` function to the `get` function and in this example, the count of males will be returned.

Sample Return
```php
7
```

**Getting all sub-attributes**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->attribute( "gender" )
       ->get();
```

Remove the specified sub-attribute from your attribute function and all of the sub-attributes for "gender" will be returned:

Sample Return
```php
Array (
    [male] => 7,
    [female] => 20
)
```

In the above example, we can use the tracked information to deduce that there were significantly more females viewing Post ID #5 than there were males.


## Relation Operations
Relation Operations are used to track the relationship between a user and an object in a social setting.

### Unary Operations
When a user on Facebook "Likes" a post, the form a Unary relationship with that post, **a relationship in which there can only be one other state than the default state**. The user can either "Like" the post or not.

**Tracking**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->social( "unary", 80, true )
       ->track();
```

The `social` function takes in three parameters, a relation type, the user ID, and the value for the relation.

Unary Relations may only have a value of `True` or `False`. If `True`, a relation will be formed if one does not exist. If `False`, the relation will be destroyed.

In the example above, this will store a relation in our database that User #80 "Likes" Post #5.


**Getting Users Relation to Unary Object**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->social( "unary", 80 )
       ->get();
```

Change the `track` function to the `get` function, and omit the third parameter `$value` from the `social` function and the users relation to the object will be returned.

Sample Return (if the User #80 "Liked" Post #5)
```php
true
```

**Getting Unary Objects Social Value**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->social( "unary" )
       ->get();
```

If you now omit the second parameter `$userID` from the `social` function ange `get` the data, an int will be returned representing the number of "Likes" Post #5 has.

Sample Return (15 likes)
```php
15
```

**Getting Popular Unary Objects**
```php
$shadow->type( "post" )
       ->social( "unary" )
       ->get();
```

If you now omit the `item` function (which specifies a specific object), a list of the popular `$itemType`'s ("post" in this case) will be returned.

Sample Return: 3 "Posts", stored in the `objects` array. `id` represents the Posts ID, `count` is the "number of likes", and `rank` is the Social Rank of objects of type "post" that have a Unary relation
```php
Array
(
    [count] => 3
    [type] => post
    [objects] => Array
        (
            [0] => Array
                (
                    [id] => 4
                    [count] => 20
                    [rank] => 1.69941166289984
                )

            [1] => Array
                (
                    [id] => 7
                    [count] => 38
                    [rank] => 1.37037037037037
                )

            [2] => Array
                (
                    [id] => 1
                    [count] => 8
                    [rank] => 0.875
                )

        )

)
```
