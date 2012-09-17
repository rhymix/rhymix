<?php
try
{
	return self::_getRemoteResource($url, $body, $timeout, $method, $content_type, $headers, $cookies, $post_data);
}
catch(Exception $e)
{
	return NULL;
}
