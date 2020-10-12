# Loading static content

Let's do a simple blog.

## Setup

### Create the model

Create a simple class that describe your model:

```php
<?php

namespace App\Model;

class Article {
    public string $title;
    public string $slug;
    public string $content;
    public \DateTimeInterface $created;
    public \DateTimeInterface $lastModified;
}
```

### Register content provider

Register your model in `config/packages/content.yaml`:

```yaml
content:
  providers:
    App\Model\Article: '%kernel.project_dir%/content/articles'
```

### Write your first content

Write your first article in `content/articles/how-to-train-your-dragon.md`:

```markdown
---
title: "How to train your dragon"
---

# This is Berk

It's twelve days north of Hopeless and a few degrees south of Freezing to Death. It's located solidly on the Meridian of Misery. My village. In a word, sturdy. It's been here for seven generations, but every single building is new. We've got hunting, fishing, and a charming view of the sunsets. The only problems are the pests. Most places have mice or mosquitoes. We have... dragons.
````

_Note: by default, the content of the source file are mapped on the `content` property and the name of the file is mapped on the `slug` property._

Check out all [supported formats](supported-formats.md).

## Usage

### Listing content

In your controller (or service):

```php
<?php

namespace App\Controller;

use App\Model\Article;
use Content\ContentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/blog")
 */
class BlogController extends AbstractController
{
    /**
     * @Route("/", name="blog")
     */
    public function index(ContentManager $contentManager)
    {
        return $this->render(
            'blog/index.html.twig',
            ['articles' => $contentManager->getContents(Article::class)]
        );
    }
```

_Note: contents of the same type can very well be writen in different formats._

### Fetching a specific content

The ContentManager uses slugs to identify your content. The `slug` argument must exactly matche the static file name in your content directory.

Example: `$contentManager->getContent(Article::class, 'how-to-train-your-dragon');` will fetch the `content/articles/how-to-train-your-dragon.md` article.

```php
<?php

namespace App\Controller;

use App\Model\Article;
use Content\ContentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/blog")
 */
class BlogController extends AbstractController
{
    // ...

    /**
     * @Route("/{slug}", name="article")
     */
    public function article(ContentManager $contentManager, string $slug)
    {
        return $this->render(
            'blog/article.html.twig',
            ['article' => $contentManager->getContent(Article::class, $slug)]
        );
    }
```

### Sorting contents

The `getContents` method have a second parameters `$sortBy` that allows sorting the content list.

It accepts:

#### A property name (string)

Alphabetically sorted categories:

```php
$categories = $contentManager->getContents(Category::class, 'title');
```

#### An array of properties and sorting mode

All articles sorted by descending `date` (most recent first) and then by ascending `title` (Alphabetically):

```php
$latestArticles = $contentManager->getContents(Category::class, ['date' => false, 'title' => true]);
```

#### A custom callable supported by the PHP [usort](https://www.php.net/manual/fr/function.usort.php) function

```php
$tasks = $contentManager->getContents(Task::class, fn($a, $b) => $a->priority <=> $b->priority);
```

### Filtering contents

The `getContents` method have a third parameters `$filterBy` that allows filtering the content list.

It accepts:

#### An array of properties and values

```php
$articles = $contentManager->getContents(Article::class, null, ['category' => 'symfony']);
```

When passing multiple requirements, all must be met:

```php
$myDrafts = $this->manager->getContents(Article::class, null, ['author' => 'ogizanagi', 'draft' => true]);
```

#### A property name (string)

```php
// Equivalent to ['active' => true]
$users = $contentManager->getContents(User::class, 'active');
```

#### A custom callable supported by the PHP [usort](https://www.php.net/manual/fr/function.usort.php) function

```php
$tagedMobileArticles = $this->manager->getContents(
    Article::class,
    null,
    fn (Article $article): bool => in_array('mobile', $article->tags)
);
```

## Advanced usage and extension

### Register a custom denormalizer

For more control over your model denormalization, you can register your own Denormalizer.
Simply, create a service that implements Symfony's `DenormalizerInterface` and supports your model:

```php
<?php

namespace App\Content\Denormalizer;

use App\Model\Article;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ArticleDenormalizer implements DenormalizerInterface
{
    /**
     * Must return true for supported models.
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_a($type, Article::class, true);
    }

    /**
     * Instanciate your model from the denormalized data array.
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return new Article(
            $data['title'],
            $data['slug'],
            $data['content'],
            new \DateTimeImmutable($data['date']),
            new \DateTimeImmutable($data['lastModified'])
        );
    }
}
```

_Note: Using autowiring, denormalizers are automaticaly registered in Symfony Serializer._
