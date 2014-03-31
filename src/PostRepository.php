<?php

namespace theses;

use PHPCR\SessionInterface;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPCR\Util\NodeHelper;
use iter;

class PostRepository implements \IteratorAggregate
{
    protected $session;

    function __construct(SessionInterface $session, callable $postFactory)
    {
        $this->session = $session;
        $this->factory = $postFactory;
    }

    function create()
    {
        return call_user_func($this->factory);
    }

    function find($id)
    {
        return $this->createFromNode($this->session->getNodeByIdentifier($id));
    }

    function findBySlug($slug)
    {
        $node = $this->session->getNode("/posts/$slug");
        return $this->createFromNode($node);
    }

    function findByPermalink($permalink)
    {
        $route = $this->session->getNode("/routes/$permalink");
        $node = $route->getProperty('node')->getNode();

        return $this->createFromNode($node);
    }

    function findAll()
    {
        $posts = NodeHelper::createPath($this->session, '/posts');

        return iter\map(function($node) {
            return $this->createFromNode($node);
        }, $posts);
    }

    function findAllPublished()
    {
        $workspace = $this->session->getWorkspace();
        $queryManager = $workspace->getQueryManager();
        $sql = 'SELECT * FROM [nt:unstructured] AS post WHERE (ISDESCENDANTNODE(post, [/posts]) AND post.[publishedAt] IS NOT NULL) ORDER BY publishedAt DESC';
        $query = $queryManager->createQuery($sql, 'JCR-SQL2');

        foreach ($query->execute() as $path => $row) {
            yield $this->createFromNode($row->getNode());
        }
    }

    function getIterator()
    {
        return $this->findAllPublished();
    }

    function insert(Post $post)
    {
        $posts = NodeHelper::createPath($this->session, '/posts');

        $post->slug = (new \Cocur\Slugify\Slugify)->slugify($post->getTitle());

        $node = $posts->addNode($post->getSlug(), 'nt:unstructured');

        $node->addMixin('mix:referenceable');
        $node->addMixin('mix:created');
        $node->addMixin('mix:lastModified');

        $node->setProperty('jcr:createdBy', 'Christoph');
        $node->setProperty('title', $post->getTitle());
        $node->setProperty('content', $post->getRawContent());

        $this->session->save();

        $post->createdAt = $node->getPropertyValue('jcr:created');
        $post->id = $node->getPropertyValue('jcr:uuid');
    }

    function update(Post $post)
    {
        $node = $this->session->getNodeByIdentifier($post->id);

        if ($post->slug !== $node->getName()) {
            $node->rename($post->slug);
        }

        $node->setProperty('publishedAt', $post->publishedAt);
        $node->setProperty('title', $post->title);
        $node->setProperty('content', $post->content);

        $custom = NodeHelper::createPath($this->session, $node->getPath().'/user_properties');

        $customProperties = $post->userProperties;
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

        if ($post->publishedAt !== null) {
            $this->createPermalink($post);
        }
    }

    function regeneratePermalinks()
    {
        foreach ($this->findAll() as $post) {
            $this->createPermalink($post);
        }
    }

    private function createPermalink(Post $post)
    {
        $permalink = ltrim($post->getUrl(), '/');

        $route = NodeHelper::createPath($this->session, "/routes/$permalink");
        $route->setProperty('node', $post->getId(), PropertyType::WEAKREFERENCE);

        $this->session->save();
    }

    private function createFromNode(NodeInterface $node)
    {
        $post = $this->create();
        $post->id = $node->getIdentifier();
        $post->userProperties = $this->getCustomPostProperties($node);
        $post->title = $node->getPropertyValueWithDefault('title', '');
        $post->rawContent = $node->getPropertyValueWithDefault('content', '');
        $post->publishedAt = $node->getPropertyValueWithDefault('publishedAt', null);
        $post->createdAt = $node->getPropertyValueWithDefault('jcr:created', null);
        $post->lastModified = $node->getPropertyValueWithDefault('jcr:lastModified', null);
        $post->slug = $node->getName();

        return $post;
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
