<?php

namespace JBBCode;

require_once "CodeDefinition.php";

/**
 * Implements the builder pattern for the CodeDefinition class. A builder
 * is the recommended way of constructing CodeDefinition objects.
 *
 * @author jbowens
 */
class CodeDefinitionBuilder
{

    /** @var string */
    protected $tagName;
    /** @var boolean */
    protected $useOption = false;
    /** @var string */
    protected $replacementText;
    /** @var boolean */
    protected $parseContent = true;
    /** @var integer */
    protected $nestLimit = -1;
    /** @var array[string]InputValidator The input validators to run options through */
    protected $optionValidator = array();
    /** @var InputValidator */
    protected $bodyValidator = null;

    /**
     * Construct a CodeDefinitionBuilder.
     *
     * @param string $tagName  the tag name of the definition to build
     * @param string $replacementText  the replacement text of the definition to build
     */
    public function __construct($tagName, $replacementText)
    {
        $this->tagName = $tagName;
        $this->replacementText = $replacementText;
    }

    /**
     * Sets the tag name the CodeDefinition should be built with.
     *
     * @param string $tagName  the tag name for the new CodeDefinition
     * @return self
     */
    public function setTagName($tagName)
    {
        $this->tagName = $tagName;
        return $this;
    }

    /**
     * Sets the replacement text that the new CodeDefinition should be
     * built with.
     *
     * @param string $replacementText  the replacement text for the new CodeDefinition
     * @return self
     */
    public function setReplacementText($replacementText)
    {
        $this->replacementText = $replacementText;
        return $this;
    }

    /**
     * Set whether or not the built CodeDefinition should use the {option} bbcode
     * argument.
     *
     * @param boolean $option  true iff the definition includes an option
     * @return self
     */
    public function setUseOption($option)
    {
        $this->useOption = $option;
        return $this;
    }

    /**
     * Set whether or not the built CodeDefinition should allow its content
     * to be parsed and evaluated as bbcode.
     *
     * @param boolean $parseContent  true iff the content should be parsed
     * @return self
     */
    public function setParseContent($parseContent)
    {
        $this->parseContent = $parseContent;
        return $this;
    }

    /**
     * Sets the nest limit for this code definition.
     *
     * @param integer $limit a positive integer, or -1 if there is no limit.
     * @throws \InvalidArgumentException  if the nest limit is invalid
     * @return self
     */
    public function setNestLimit($limit)
    {
        if (!is_int($limit) || ($limit <= 0 && -1 != $limit)) {
            throw new \InvalidArgumentException("A nest limit must be a positive integer " .
                                               "or -1.");
        }
        $this->nestLimit = $limit;
        return $this;
    }

    /**
     * Sets the InputValidator that option arguments should be validated with.
     *
     * @param InputValidator $validator  the InputValidator instance to use
     * @return self
     */
    public function setOptionValidator(\JBBCode\InputValidator $validator, $option=null)
    {
        if (empty($option)) {
            $option = $this->tagName;
        }
        $this->optionValidator[$option] = $validator;
        return $this;
    }

    /**
     * Sets the InputValidator that body ({param}) text should be validated with.
     *
     * @param InputValidator $validator  the InputValidator instance to use
     * @return self
     */
    public function setBodyValidator(\JBBCode\InputValidator $validator)
    {
        $this->bodyValidator = $validator;
        return $this;
    }

    /**
     * Removes the attached option validator if one is attached.
     * @return self
     */
    public function removeOptionValidator()
    {
        $this->optionValidator = array();
        return $this;
    }

    /**
     * Removes the attached body validator if one is attached.
     * @return self
     */
    public function removeBodyValidator()
    {
        $this->bodyValidator = null;
        return $this;
    }

    /**
     * Builds a CodeDefinition with the current state of the builder.
     *
     * @return CodeDefinition a new CodeDefinition instance
     */
    public function build()
    {
        $definition = CodeDefinition::construct($this->tagName,
                                                $this->replacementText,
                                                $this->useOption,
                                                $this->parseContent,
                                                $this->nestLimit,
                                                $this->optionValidator,
                                                $this->bodyValidator);
        return $definition;
    }
}
