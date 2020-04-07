<?php

namespace JBBCode;

/**
 * A node within the document tree.
 *
 * Known subclasses: TextNode, ElementNode
 *
 * @author jbowens
 */
abstract class Node
{
    /** @var Node Pointer to the parent node of this node */
    protected $parent;

    /**
     * Returns this node's immediate parent.
     *
     * @return Node the node's parent
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Determines if this node has a parent.
     *
     * @return boolean true if this node has a parent, false otherwise
     */
    public function hasParent()
    {
        return $this->parent != null;
    }

    /**
     * Returns true if this is a text node. Returns false otherwise.
     * (Overridden by TextNode to return true)
     *
     * @return boolean true if this node is a text node
     */
    public function isTextNode()
    {
        return false;
    }

    /**
     * Accepts the given NodeVisitor. This is part of an implementation
     * of the Visitor pattern.
     *
     * @param NodeVisitor $nodeVisitor the NodeVisitor traversing the graph
     */
    abstract public function accept(NodeVisitor $nodeVisitor);

    /**
     * Returns this node as text (without any bbcode markup)
     *
     * @return string the plain text representation of this node
     */
    abstract public function getAsText();

    /**
     * Returns this node as bbcode
     *
     * @return string the bbcode representation of this node
     */
    abstract public function getAsBBCode();

    /**
     * Returns this node as HTML
     *
     * @return string the html representation of this node
     */
    abstract public function getAsHTML();

    /**
     * Sets this node's parent to be the given node.
     *
     * @param Node $parent the node to set as this node's parent
     */
    public function setParent(Node $parent)
    {
        $this->parent = $parent;
    }
}
