Doctrine Fixture Helper
==============================================

The main idea of this is to decouple your DataFixture classes to avoid spaghetti,
and write elegant code by using a bit of functional PHP.

This works with both ODM and ORM of Doctrine.


Installation
==============================================

```
composer require theodordiaconu/doctrine-fixture-helper dev-master
```

Add the fixtures bundle to AppKernel.php:

```
new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
```

Usage
================================================
To understand how it works, let's take the following scenario:

- You want to generate a bunch of users
- You want each user to have a given number of blog posts
- Each blog posts can have a certain number of comments given by some other users in the system.


In your DataFixtures/ORM folder from your bundle, we recommend having a Configuration class:

```
class Configuration
{
    const USERS = 100;
    const BLOG_POSTS_PER_USER = 5;
    const COMMENTS_PER_BLOG_POST = 15;

    public static $jobTitles = [
        'Manager', 'Customer Relation', 'Web-Consultant', 'Web Architect', 'Loan Provider', 'Boss'
    ];
}
```


Let's begin with Users.

```
use TD\FixtureHelper\BaseFixture;

class LoadUserData extends BaseFixture
{
    public function doLoad()
    {
        $this->userService = $this->container->get('user_service');
        
        $this->iterator(Configuration::USERS, function($index) {
            $user = new User();
            $user->setFirstname($this->faker->firstname());
            $user->setLastname($this->faker->lastname());
            $user->setJob($this->faker->randomElement(Configuration::$jobTitles));
            
            $this->userService->doSomething($user);
            
            return $user; // you must return the object.
        }, 'user'); // note: user is our reference name, the script will create user-1, user-2, user-3 accordingly.
    }
    
    public function getOrder()
    {
        return 1;
    }
}
```

Now let's create some blog posts for each user

```
    // in LoadBlogPostData.php
    public function doLoad()
    {
        $this->iterator('user', function(User $user) { // this will iterate through all existing users
            $this->iterator(Configuration::BLOG_POSTS_PER_USER, function($index) use ($user) {
                $blog = new BlogPost($user);
                $blog->setTitle($this->faker->sentence());
                $blog->setText($this->faker->text());
                
                return $blog;
            }, 'post')
        });
    }
    // update getOrder
```

Now let's leave comments to the blog posts:

```
    // in LoadCommentData.php
    public function doLoad()
    {
        $this->iterator('post', function(BlogPost $post) {
            $this->iterator(Configuration::COMMENTS_PER_BLOG_POST, function($index) use ($post) {
                $comment = new Comment($this->getRandomObject('user'));
                $comment->setPost($post);
                
                return $comment;
            }, 'comment-for-'.$post->getId())
        });
    }
    
    // update getOrder
```

Conclusion
========================================
As you might have already noticed, if the iterator gets an integer as the first parameter, it iterates one by one until that limit is reached,
if it gets a string it assumes that you want to iterate through a collection of pre-existing object references.

As you can see we have written this with very few lines of code. And the sky is the limit. This will help you create very complex
and interconnected entities.



API
========================================

```
$this->container // container
$this->manager // ObjectManager
$this->faker // faker
$this->getObjects('user') // will return all users
$this->getReference('user-1') // will return user-1
$this->getRandomObject('user') // will return a random element from user collection
```


Take a look at faker helper methods: https://github.com/fzaninotto/Faker

