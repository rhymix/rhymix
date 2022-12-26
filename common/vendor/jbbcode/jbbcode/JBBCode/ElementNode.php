<?php

namespace JBBCode;

require_once 'Node.php';

/**
 * An element within the tree. Consists of a tag name which defines the type of the
 * element and any number of Node children. It also contains a CodeDefinition matching
 * the tag name of the element.
 *
 * @author jbowens
 */
class ElementNode extends Node
{
    /** @var string The tagname of this element, for i.e. "b" in [b]bold[/b] */
    protected $tagName;

    /** @var string[] The attributes, if any, of this element node */
    protected $attribute;

    /** @var Node[] The child nodes contained within this element */
    protected $children;

    /** @var CodeDefinition The code definition that defines this element's behavior */
    protected $codeDefinition;

    /** @var integer How deeply this node is nested */
    protected $nestDepth;

    /**
     * Constructs the element node
     */
    public function __construct()
    {
        $this->children = array();
        $this->nestDepth = 0;
    }

    public function accept(NodeVisitor $nodeVisitor)
    {
        $nodeVisitor->visitElementNode($this);
    }

    /**
     * Gets the CodeDefinition that defines this element.
     *
     * @return CodeDefinition this element's code definition
     */
    public function getCodeDefinition()
    {
        return $this->codeDefinition;
    }

    /**
     * Sets the CodeDefinition that defines this element.
     *
     * @param CodeDefinition $codeDef the code definition that defines this element node
     */
    public function setCodeDefinition(CodeDefinition $codeDef)
    {
        $this->codeDefinition = $codeDef;
        $this->setTagName($codeDef->getTagName());
    }

    /**
     * Returns the tag name of this element.
     *
     * @return string the element's tag name
     */
    public function getTagName()
    {
        return $this->tagName;
    }

    /**
     * Returns the attribute (used as the option in bbcode definitions) of this element.
     *
     * @return array the attributes of this element
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Returns all the children of this element.
     *
     * @return Node[] an array of this node's child nodes
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * (non-PHPdoc)
     * @see JBBCode.Node::getAsText()
     *
     * Returns the element as text (not including any bbcode markup)
     *
     * @return string the plain text representation of this node
     */
    public function getAsText()
    {
        if ($this->codeDefinition) {
            return $this->codeDefinition->asText($this);
        } else {
            $s = "";
            foreach ($this->getChildren() as $child) {
                $s .= $child->getAsText();
            }
            return $s;
        }
    }

    /**
     * (non-PHPdoc)
     * @see JBBCode.Node::getAsBBCode()
     *
     * Returns the element as bbcode (with all unclosed tags closed)
     *
     * @return string the bbcode representation of this element
     */
    public function getAsBBCode()
    {
        $str = "[".$this->tagName;
        if (!empty($this->attribute)) {
            if (isset($this->attribute[$this->tagName])) {
                $str .= "=".$this->attribute[$this->tagName];
            }

            foreach ($this->attribute as $key => $value) {
                if ($key == $this->tagName) {
                    continue;
                } else {
                    $str .= " ".$key."=" . $value;
                }
            }
        }
        $str .= "]";
        foreach ($this->getChildren() as $child) {
            $str .= $child->getAsBBCode();
        }
        $str .= "[/".$this->tagName."]";

        return $str;
    }

    /**
     * (non-PHPdoc)
     * @see JBBCode.Node::getAsHTML()
     *
     * Returns the element as html with all replacements made
     *
     * @return string the html representation of this node
     */
    public function getAsHTML()
    {
        if ($this->codeDefinition) {
            return $this->codeDefinition->asHtml($this);
        } else {
            return "";
        }
    }

    /**
     * Adds a child to this node's content. A child may be a TextNode, or
     * another ElementNode... or anything else that may extend the
     * abstract Node class.
     *
     * @param Node $child the node to add as a child
     */
    public function addChild(Node $child)
    {
        $this->children[] = $child;
        $child->setParent($this);
    }

    /**
     * Removes a child from this node's content.
     *
     * @param Node $child the child node to remove
     */
    public function removeChild(Node $child)
    {
        foreach ($this->children as $key => $value) {
            if ($value === $child) {
                unset($this->children[$key]);
            }
        }
    }

    /**
     * Sets the tag name of this element node.
     *
     * @param string $tagName the element's new tag name
     */
    public function setTagName($tagName)
    {
        $this->tagName = $tagName;
    }

    /**
     * Sets the attribute (option) of this element node.
     *
     * @param string[] $attribute the attribute(s) of this element node
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * Traverses the parse tree upwards, going from parent to parent, until it finds a
     * parent who has the given tag name. Returns the parent with the matching tag name
     * if it exists, otherwise returns null.
     *
     * @param string $str the tag name to search for
     *
     * @return ElementNode|null the closest parent with the given tag name
     */
    public function closestParentOfType($str)
    {
        $str = strtolower($str);
        $currentEl = $this;

        while (strtolower($currentEl->getTagName()) != $str && $currentEl->hasParent()) {
            $currentEl = $currentEl->getParent();
        }

        if (strtolower($currentEl->getTagName()) != $str) {
            return null;
        } else {
            return $currentEl;
        }
    }
}
