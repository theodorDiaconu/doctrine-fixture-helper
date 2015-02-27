Doctrine Fixture Helper
==============================================

The main idea of this is to decouple your DataFixture classes to avoid spaghetti,
and write elegant code by using a bit of functional PHP.

```
composer require theodordiaconu/doctrine-fixture-helper dev-master
```

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
        $this->iterator(Configuration::USERS, function($index) {
            $user = new User();
            $user->setFirstname($this->faker->firstname());
            $user->setLastname($this->faker->lastname());
            $user->setJob($this->faker->randomElement(Configuration::$jobTitles))
            
            return $user; // you must return the object.
        }, 'user'); // note: user is our reference name, the script will create user-1, user-2, user-3 accordingly.
    }
    
    public function getOrder()
    {
        return 1;
    }
}
```

Now let's create 3 blogs for each user

```
    // in LoadBlogPostData.php
    public function doLoad()
    {
        $this->iterator('user', function(User $user) {
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
                $comment = new Comment($this->getRandomObject('user');
                $comment->setPost($post);
                
                return $comment;
            }, 'comment-for-'.$post->getId())
        });
    }
    
    // update getOrder
```

As you can see we have written this with very few lines of code. And the sky is the limit. This will help you create very complex

You can also make use of other helper methods:

```
$this->getObjects('user') // will return all users
$this->getReference('user-1') // will return user-1
$this->container->get('my_service') // will return the service
```


Take a look at faker helper methods: https://github.com/fzaninotto/Faker

