<?php

namespace Rhymix\Framework\Responses;

/**
 * This interface signals that the response requires late rendering,
 * i.e. after all addons and event handlers have been executed.
 *
 * This is used for potentially memory-intensive responses
 * like file downloads and streaming content.
 */
interface LateRenderingResponse
{

}
