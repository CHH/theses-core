<?php

namespace theses;

use PHPCR\SessionInterface;
use PHPCR\NodeInterface;
use iter;

class PostRepository implements \IteratorAggregate
{
    protected $session;

    function __construct(SessionInterface $session)
    {
        $this->session = $session;
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

    function getIterator()
    {
        $posts = iter\map(function($node) {
            return $this->createFromNode($node);
        }, $this->session->getNode('/posts'));

        return $posts;
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

        try {
            $custom = $node->getNode('user_properties');
        } catch (\Exception $e) {
            $custom = $node->addNode('user_properties');
        }

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
    }

    private function createFromNode(NodeInterface $node)
    {
        $post = new Post();
        $post->id = $node->getIdentifier();
        $post->userProperties = $this->getCustomPostProperties($node);
        $post->title = $node->getPropertyValueWithDefault('title', '');
        $post->content = $node->getPropertyValueWithDefault('content', '');
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
