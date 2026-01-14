<?php

namespace Rhymix\Framework;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * This class can be extended to implement PSR-14 events.
 *
 * PSR-14 events are identified not by their name and position,
 * but by their type (class name, including the namespace).
 * Event handlers (listeners, subscribers) can subscribe to these events
 * by specifying the class name of the event they want.
 *
 * This means that events are no longer tied to specific points in the code,
 * as the old names and positions implied, but can be dispatched
 * and handled anywhere in the application as long as the correct data
 * structure is used.
 */
#[\AllowDynamicProperties]
abstract class AbstractEvent implements StoppableEventInterface
{
	/**
	 * This flag indicates whether the propagation of this event is stopped.
	 */
	protected bool $_propagation_stopped = false;

	/**
	 * This attribute provides a standardized way to communicate an error message.
	 */
	protected string $_error_message;

	/**
	 * This attribute stores the 'position' attribute of the event,
	 * which allows using the same event both before and after a certain action.
	 */
	protected string $_position;

	/**
	 * The constructor sets the position.
	 *
	 * @param string $position
	 */
	public function __construct(string $position)
	{
		$this->_position = $position;
	}

	/**
	 * Stop the propagation of this event.
	 *
	 * @return void
	 */
	public function stopPropagation(): void
	{
		$this->_propagation_stopped = true;
	}

	/**
	 * Is propagation of this event stopped?
	 *
	 * @return bool
	 */
	public function isPropagationStopped(): bool
	{
		return $this->_propagation_stopped;
	}

	/**
	 * Set the error message.
	 *
	 * @param string $message
	 * @return void
	 */
	public function setErrorMessage(string $message): void
	{
		$this->_error_message = $message;
	}

	/**
	 * Get the error message.
	 *
	 * @return string
	 */
	public function getErrorMessage(): string
	{
		return isset($this->_error_message) ? $this->_error_message : '';
	}

	/**
	 * Set the position.
	 *
	 * @param string $position
	 * @return void
	 */
	public function setPosition(string $position): void
	{
		$this->_position = $position;
	}

	/**
	 * Get the position.
	 *
	 * @return string
	 */
	public function getPosition(): string
	{
		return isset($this->_position) ? $this->_position : '';
	}
}
