<img src="http://i.imgur.com/6MVGm2A.png">

## What?
Too often, information is collected from users but is never applied to your application to understand what type of users are doing what on your site.

Too often, great content may be shared on your site, but your means of determining what is popular or relevant to your users is a challenge.

Enter Shadow, an analytics engine for developers that integrates seamlessly into your current system.

If Google Analytics is an external stats tracker, consider Shadow to be an internal one.

## Idea
Shadow is used within your system to keep track of "objects" meta data and users relations to them. An "object" represents anything in your application. For example, an "object" can be a Post, Image, or even a Comment. Imagine if everytime a user visited a post, you tracked the users gender. You'd be able to query that data later to say that "X% of the people that read this post are Male". That is what meta data in Shadow can be used for.

By default, meta data is tracked as a **count**, meaning every time you track the data, a counter will increment. If you prefer to set the value to a string instead of a count, you can.

Relations are the users connection with an object. If you wanted to add a like button for pictures in your app, your object type might be "picture" and your operation would be "unary". Want to incorporate up/downvotes into your comments? With Shadow, all that is only a few lines of code.



## Use Cases?
* Impression Tracking
* User Demographic Analyzation
* Database Sessions
* IP Blocking
* Popularity
* Likes or Favorites
* Upvoting + Downvoting
* Star Ratings

## Requirements
* MySQL with PDO Support
* PHP 5.3+

## Terms

#### Meta Tracking
* **Simple Operation**: A simple operation is when data is tracked on an objects attribute. For example, "impressions" or "session".
* **Complex Operations**: A Complex operation is when data is tracked on an objects sub-attributes. For example, "gender/male". It is an attribute or property in which there can be multiple values for (i.e. male or female).

#### Relation Tracking
* **Unary Operation**: This is an item that can only have one other state, such as a single upvote or a "like". Great for giving posts a fun element of competition.
* **Binary Operaions**: This is an item that can be voted up, voted down. Great for comments or content. 
* **Multary Operations**: This is an item that can have a variable rating, for instance a star system 1-5 or a movie rating 1-10.


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
meta( $key, $value=false )
```
* `$key`:  String - A key (as in key/value) to track data as (record data as. i.e. "impressions" or "404s")
* `$value`: String - If you do not need to keep **count** of a properties occurence, and rather set static data, you may pass a string as the second aprameter

```php 
item( $itemID, $timestamp = false )
```
* `$itemID`: String - A unique identifier for the object you would like to track. i.e. (setting 5 as the `itemID` with "post" set as the `itemType` infers that we are referencing Post ID #5). Int ID's will work best in this field.
* `$timestamp`: Int - If tracking the `relation` of an object, the objects timestamp must be passed as an Int to accurately determine the Popularity of the object

```php 
relation( $type, $user = false, $value = false )
```
* `$type`: String - Defines the relation type. Accepted values are `"unary"`, `"binary"`, or `"multary"`
* `$user`: Int - The ID of the user that will be relating to our object
* `$value`: Varies - The `value` parameter takes in a Boolean or an Int. More information about when certain types of values are to be used can be found below.

```php 
track()
```
* Calling `track` records the data we've compiled from the functions above.

```php 
get( $start = false, $amount=false )
```
* Calling `get` retrieves the data we've compiled from the functions above.
* `$limit`: Int - Limit the number of items returned
* `get(4)`: Will return only 4 objects
* `get(5,4)`: Will return only 4 objects starting from the 5th index

# Usage

## Meta Operations

### Simple Operations
Simple operations are for tracking a single attribute of an object.

If the second parameter of `meta` is a string, it will be set as the value rather than keep count.

**Tracking**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->meta( "impressions" )
       ->track();
```

In this example, we are tracking "impressions" on Post ID #5. Everytime this is called, a count of impressions will be incremented.

**Getting**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->meta( "impressions" )
       ->get();
```

Change the `track` function to the `get` function and in this example, the count of impressions will be returned.

Sample Return (if `track` was called 180 times)
```php
180
```

### Complex Operations
Complex operations are for keeping a count of a multiple sub-attributes of an object. To define an attribute as a complex one, simply add a slash in the nave with it's respective value following after the key.

**Tracking**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->meta( "gender/male" )
       ->track();
```

In this example, we are tracking the users "gender" (specifically, "male") on Post ID #5. If you have the users information, it may be beneficial to record the users meta information and associate it with on object so you can see what types of people are doing what on your website.

**Getting single sub-attribute**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->meta( "gender/male" )
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
       ->meta( "gender" )
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

**Tracking Strings**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->meta( "foo", "bar" )
       ->track();
```
Passing a string as the second parameter to `meta` will set the data as you'd expect, and will be able to be retrieved as a string.


## Relation Operations
Relation Operations are used to track the relationship between a user and an object in a social setting.

### Unary Operations
When a user on <a href="https://www.facebook.com/" target="_blank">Facebook</a> "Likes" a post, the form a Unary relationship with that post, **a relationship in which there can only be one other state than the default state**. The user can either "Like" the post or not.

**Tracking**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->relation( "unary", 80, true )
       ->track();
```

The `relation` function takes in three parameters, a relation type, the user ID, and the value for the relation.

Unary Relations may only have a value of `True` or `False`. If `True`, a relation will be formed if one does not exist. If `False`, the relation will be destroyed.

In the example above, this will store a relation in our database that User #80 "Likes" Post #5.


**Getting Users Relation to Unary Object**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->relation( "unary", 80 )
       ->get();
```

Change the `track` function to the `get` function, and omit the third parameter `$value` from the `relation` function and the users relation to the object will be returned.

Sample Return (if the User #80 "Liked" Post #5)
```php
true
```

**Getting Unary Objects Social Value**
```php
$shadow->type( "post" )
       ->item( 5 )
       ->relation( "unary" )
       ->get();
```

If you now omit the second parameter `$userID` from the `relation` function ange `get` the data, an int will be returned representing the number of "Likes" Post #5 has.

Sample Return (15 likes)
```php
15
```

**Getting Popular Unary Objects**
```php
$shadow->type( "post" )
       ->relation( "unary" )
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

### Binary Operations
When a user "upvotes" or "downvotes" a submission on <a href="http://reddit.com" target="-blank">Reddit</a>, the user forms a Binary relationship with that submission, **a relationship in which there can only be one of two states other than the default state**. The user can either "Upvote" or "Downvote" the post. In the following examples, we will use "Comments" as a `$postType`.

**Tracking**
```php
$shadow->type( "comment" )
       ->item( 10 )
       ->relation( "binary", 80, true )
       ->track();
```

The `relation` function's first parameter is now "binary", and it's value is `True`.

Binary Relations Values will only accept `True`, `False`, or `null`. If `True`, a "positive" relation will be formed between the user and the object. If `False`, a "negative" relation will be formed. If `null`, any type of relation (if any) will be destroyed.

In the example above, this will store a relation in our database that User #80 "Upvoted" Comment #10. If the third parameter in `relation` were `false`, User #80 would have "Downvoted" Comment #10;


**Getting Users Relation to Binary Object**
```php
$shadow->type( "comment" )
       ->item( 10 )
       ->relation( "binary", 80 )
       ->get();
```

Change the `track` function to the `get` function, and omit the third parameter `$value` from the `relation` function and the users relation to the object will be returned.

Sample Return (if the User #80 "Downvoted" Comment #10)
```php
false
```
Sample Return (if the User #80 "Upvoted" Comment #10)
```php
true
```
Sample Return (if the User #80 has no relation with Comment #10)
```php
null
```


**Getting Binary Objects Social Value**
```php
$shadow->type( "comment" )
       ->item( 10 )
       ->relation( "binary" )
       ->get();
```

If you now omit the second parameter `$userID` from the `relation` function ange `get` the data, an array of positive and negative votes will be shown for Comment # 10

Sample Return (9 "Upvotes" and 3 "Downvotes")
```php
Array
(
    [positive] => 9,
    [negative] => 3
)
```

**Getting Popular Binary Objects**
```php
$shadow->type( "comment" )
       ->relation( "binary" )
       ->get();
```

If you now omit the `item` function (which specifies a specific object), a list of the popular `$itemType`'s ("comment" in this case) will be returned.

Sample Return: 3 "Comments", stored in the `objects` array. `id` represents the Posts ID, `positive` is the number of "upvotes", `negative` is the number of "downvotes" and `rank` is the Social Rank of objects of type "comment" that have a Binary relation
```php
Array
(
    [count] => 3
    [type] => comment
    [objects] => Array
        (
            [0] => Array
                (
                    [id] => object-idfs
                    [positive] => 14
                    [negative] => 3
                    [rank] => 0.366543291473893
                )

            [1] => Array
                (
                    [id] => object-id
                    [positive] => 14
                    [negative] => 7
                    [rank] => 0.2845286548008661
                )
                
            [3] => Array
                (
                    [id] => object-idfs
                    [positive] => 21
                    [negative] => 94
                    [rank] => 0.1965433291273893
                )
        )
)
```


### Multary Operations
When a user rates a movie on <a href="http://www.imdb.com/" target="_blank">IMDB</a>, the user forms a Multary relationship with that movie, **a relationship in which there can be more than two states other than the default state**. The user can "rate" a movie as 3, 7, or even 20. In the following examples, we will use "Movies" as a `$postType`.

**Tracking**
```php
$shadow->type( "movie" )
       ->item( 15 )
       ->relation( "multary", 80, 3 )
       ->track();
```

The `relation` function's first parameter is now "multary", and it's value is `3`.

Multary Relations Values will only accept an `Int`, `false`, or `null`. If the value is an `Int`, a relation will be formed between the user and object with the `Int` as the value for the "rating". If `false` or `null`, any type of relation (if any) will be destroyed.

In the example above, this will store a relation in our database that User #80 "rated" Movie #15 as 3.

**Getting Users Relation to Multary Object**
```php
$shadow->type( "movie" )
       ->item( 15 )
       ->relation( "multary", 80 )
       ->get();
```

Change the `track` function to the `get` function, and omit the third parameter `$value` from the `relation` function and the users relation to the object will be returned.

Sample Return (if the User #80 "voted" a 3 on Movie #15)
```php
3
```
Sample Return (if the User #80 has no relation with Movie #15)
```php
null
```


**Getting Multary Objects Social Value**
```php
$shadow->type( "movie" )
       ->item( 15 )
       ->relation( "multary" )
       ->get();
```

If you now omit the second parameter `$userID` from the `relation` function and `get` the data, an int will be returned representing the ratings Movie #15 has

Sample Return (81 "votes" totaling up to 253 with an average vote of 3)
```php
Array
(
    [num_votes] => 81
    [total_votes_count] => 253
    [avg_vote] => 0.3201
)
```

**Getting Popular Multary Objects**
```php
$shadow->type( "comment" )
       ->relation( "multary" )
       ->get();
```

If you now omit the `item` function (which specifies a specific object), a list of the popular `$itemType`'s ("movie" in this case) will be returned.

Sample Return: 3 "Movies", stored in the `objects` array. `id` represents the Movies ID, `num_votes` is the number of "votes" casted, `total_votes_count` is the sum of all votes, `avg_vote` is the average vote across "movie" objects, and `rank` is the Social Rank of objects of type "movie" that have a Multary relation
```php

Array
(
    [count] => 3
    [type] => movie
    [0] => Array
        (
            [id] => 8
            [num_votes] => 13
            [total_votes_count] => 40
            [avg_vote] => 0.325
            [rank] => 0.316240517139895
        )

    [1] => Array
        (
            [id] => 4
            [num_votes] => 4
            [total_votes_count] => 13
            [avg_vote] => 0.30769230769231
            [rank] => 0.307698224688894
        )

    [2] => Array
        (
            [id] => 6
            [num_votes] => 23
            [total_votes_count] => 75
            [avg_vote] => 0.30666666666667
            [rank] => 0.307045870537496
        )
)
```

## Popularity Algorithms

### Unary Algorithm: Custom
```
Popularity = (likes – 1) / (time_in_hrs + 2)^1.5
```
This algorithm is derived from Y Combinator's Hacker News.
<hr />
### Binary Algorithm: <a href="http://www.evanmiller.org/how-not-to-sort-by-average-rating.html" target="_blank">Lower bound of Wilson Score Confidence Interval for a Bernoulli Parameter</a>
<img src="http://i.imgur.com/1ePMCtY.png">
<hr />
### Multary Algorithm: <a href="http://en.wikipedia.org/wiki/Bayesian_average" target="_blank">Bayesian Average</a>
Determines the Bayesian Average, the mean of a population with data from the populations being used as a way to minimize deviations/randomness
