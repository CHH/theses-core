<?php

namespace theses;

use PHPCR\SessionInterface;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPCR\Util\NodeHelper;
use iter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PostRepository implements \IteratorAggregate
{
    const PERMALINK_DATE_TITLE = 1;
    const PERMALINK_TITLE_ONLY = 2;

    protected $session;
    protected $factory;
    protected $dispatcher;

    function __construct(
        SessionInterface $session,
        callable $postFactory,
        EventDispatcherInterface $dispatcher
    )
    {
        $this->session = $session;
        $this->factory = $postFactory;
        $this->dispatcher = $dispatcher;
    }

    function create(array $attributes = [])
    {
        return call_user_func($this->factory, $attributes);
    }

    function find($id)
    {
        return $this->createFromNode($this->session->getNodeByIdentifier($id));
    }

    function findBySlug($slug)
    {
        $node = $this->session->getNode("/theses/posts/$slug");
        return $this->createFromNode($node);
    }

    function findByPermalink($permalink)
    {
        $route = $this->session->getNode("/theses/routes/$permalink");
        $node = $route->getProperty('node')->getNode();

        return $this->createFromNode($node);
    }

    function findAll()
    {
        $posts = NodeHelper::createPath($this->session, '/theses/posts');

        return iter\map(function($node) {
            return $this->createFromNode($node);
        }, $posts);
    }

    function findAllPublished()
    {
        $workspace = $this->session->getWorkspace();
        $queryManager = $workspace->getQueryManager();
        $sql = 'SELECT * FROM [nt:unstructured] AS post WHERE (ISDESCENDANTNODE(post, [/theses/posts]) AND post.[publishedAt] IS NOT NULL) ORDER BY publishedAt DESC';
        $query = $queryManager->createQuery($sql, 'JCR-SQL2');

        foreach ($query->execute() as $path => $row) {
            yield $this->createFromNode($row->getNode());
        }
    }

    function publish(Post $post)
    {
    }

    function unpublish(Post $post)
    {
    }

    /**
     * Renders the post content to HTML
     *
     * @return string
     */
    function render(Post $post)
    {
        $event = new event\ConvertPostEvent($post);
        $html = $this->dispatcher->dispatch(Events::POST_CONVERT, $event)->getContent();

        return $html;
    }

    function getIterator()
    {
        return $this->findAllPublished();
    }

    function insert(Post $post)
    {
        $this->dispatcher->dispatch(Events::POST_BEFORE_INSERT, new event\PostEvent($post));

        $posts = NodeHelper::createPath($this->session, '/theses/posts');

        $post->modify([
            'slug' => (new \Cocur\Slugify\Slugify)->slugify($post->getTitle())
        ]);

        $node = $posts->addNode($post->getSlug(), 'nt:unstructured');

        $node->addMixin('mix:referenceable');
        $node->addMixin('mix:created');
        $node->addMixin('mix:lastModified');

        $node->setProperty('jcr:createdBy', 'Christoph');
        $node->setProperty('title', $post->getTitle());
        $node->setProperty('content', $post->getContent());

        $this->session->save();

        $this->updateUserProperties($node, $post->getCustom());

        $post->modify([
            'id' => $node->getPropertyValue('jcr:uuid'),
            'createdAt' => $node->getPropertyValue('jcr:created'),
            'lastModified' => $node->getPropertyValue('jcr:lastModified'),
        ]);

        $this->dispatcher->dispatch(Events::POST_INSERT, new event\PostEvent($post));
    }

    function update(Post $post)
    {
        $this->dispatcher->dispatch(Events::POST_BEFORE_SAVE, new event\PostEvent($post));

        $node = $this->session->getNodeByIdentifier($post->getId());

        if ($post->getSlug() !== $node->getName()) {
            $node->rename($post->getSlug());
        }

        $node->setProperty('publishedAt', $post->getPublishedAt());
        $node->setProperty('title', $post->getTitle());
        $node->setProperty('content', $post->getContent());

        $this->updateUserProperties($node, $post->getCustom());

        $this->session->save();

        if ($post->getPublishedAt() !== null) {
            $this->createPermalink($post);
        }

        $this->dispatcher->dispatch(Events::POST_AFTER_SAVE, new event\PostEvent($post));
    }

    function regeneratePermalinks()
    {
        foreach ($this->findAll() as $post) {
            $this->createPermalink($post);
        }
    }

    private function updateUserProperties(NodeInterface $node, array $customProperties)
    {
        $custom = NodeHelper::createPath($this->session, $node->getPath().'/user_properties');

        $i = 0;
        $existingProperties = iterator_to_array($custom->getNodes(), false);

        foreach ($customProperties as $propertyName => $value) {
            if (isset($existingProperties[$i])) {
                $property = $existingProperties[$i];

                if (empty($propertyName)) {
                    $property->remove();
                    continue;
                }

                if ($property->getName() !== $propertyName) {
                    $property->rename($propertyName);
                }
            } else {
                $property = $custom->addNode($propertyName);
            }

            $property->setProperty('value', $value);
            $i++;
        }

        $this->session->save();
    }

    private function createPermalink(Post $post)
    {
        $permalink = ltrim($post->getUrl(), '/');

        $route = NodeHelper::createPath($this->session, "/theses/routes/$permalink");
        $route->setProperty('node', $post->getId(), PropertyType::WEAKREFERENCE);

        $this->session->save();
    }

    private function createFromNode(NodeInterface $node)
    {
        $attributes = $node->getPropertiesValues();
        $attributes['createdAt'] = $attributes['jcr:created'];
        $attributes['lastModified'] = $attributes['jcr:lastModified'];
        $attributes['slug'] = $node->getName();
        $attributes['id'] = $node->getIdentifier();
        $attributes['userProperties'] = $this->getCustomPostProperties($node);

        return $this->create($attributes);
    }

    private function getCustomPostProperties(NodeInterface $postNode)
    {
        try {
            $parent = $postNode->getNode('user_properties');
        } catch (\Exception $e) {
            return [];
        }

        $nodes = $parent->getNodes();

        $propertiesArray = [];

        foreach ($nodes as $node) {
            $propertiesArray[$node->getName()] = $node->getPropertyValue('value');
        }

        return $propertiesArray;
    }
}
