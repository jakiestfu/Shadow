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
