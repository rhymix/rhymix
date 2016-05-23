<?php
/**
 * This file declare the Swist_Transport_AWSTransport class.
 *
 * @package Swift
 * @subpackage Transport
 * @author Julien Muetton <julien_muetton@carpe-hora.com>
 * @copyright (c) Carpe Hora SARL 2011
 * @since 2011-10-07
 */

/**
 * the base class for aws transport
 */
abstract class Swift_Transport_AWSTransport implements Swift_Transport
{
  /** The event dispatcher from the plugin API */
  protected $_eventDispatcher;

  /**
   * Constructor.
   */
  public function __construct(Swift_Events_EventDispatcher $eventDispatcher)
  {
    $this->_eventDispatcher = $eventDispatcher;
  }
} // END OF Swist_Transport_AWSTransport

// now register dependancies

Swift_DependencyContainer::getInstance()

  -> register('transport.aws')
  -> withDependencies(array('transport.eventdispatcher'))
;
