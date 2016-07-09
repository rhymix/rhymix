<?php

namespace Rhymix\Framework;

/**
 * The session class.
 */
class Session
{
	/**
	 * Properties for internal use only.
	 */
	protected static $_started = false;
	protected static $_must_create = false;
	protected static $_must_refresh = false;
	
	/**
	 * Get a session variable.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public static function get($key)
	{
		
	}
	
	/**
	 * Set a session variable.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public static function set($key, $value)
	{
		
	}
	
	/**
	 * Start the session.
	 * 
	 * This method is called automatically at Rhymix startup.
	 * There is usually no need to call it manually.
	 *
	 * @return bool
	 */
	public static function start()
	{
		
	}
	
	/**
	 * Refresh the session.
	 * 
	 * This method can be used to invalidate old session cookies.
	 * It is called automatically when someone logs in or out.
	 *
	 * @return bool
	 */
	public static function refresh()
	{
		
	}
	
	/**
	 * Close the session and write its data.
	 * 
	 * This method is called automatically at the end of a request, but you can
	 * call it sooner if you don't plan to write any more data to the session.
	 * 
	 * @return bool
	 */
	public static function close()
	{
		
	}
	
	/**
	 * Destroy the session.
	 * 
	 * This method deletes all data associated with the current session.
	 * 
	 * @return bool
	 */
	public static function destroy()
	{
		
	}
	
	/**
	 * Log in.
	 *
	 * This method accepts either an integer or a member object.
	 * It returns true on success and false on failure.
	 * 
	 * @param int $member_srl
	 * @param bool $is_admin
	 * @return bool
	 */
	public static function login($member_srl, $is_admin = false)
	{
		
	}
	
	/**
	 * Log out.
	 *
	 * This method returns true on success and false on failure.
	 *
	 * @return bool
	 */
	public static function logout()
	{
		
	}
	
	/**
	 * Check if a member has logged in with this session.
	 * 
	 * This method returns true or false, not 'Y' or 'N'.
	 *
	 * @return bool
	 */
	public static function isMember()
	{
		
	}
	
	/**
	 * Check if an administrator is logged in with this session.
	 * 
	 * This method returns true or false, not 'Y' or 'N'.
	 *
	 * @return bool
	 */
	public static function isAdmin()
	{
		
	}
	
	/**
	 * Check if the current session is trusted.
	 *
	 * This can be useful if you want to force a password check before granting
	 * access to certain pages. The duration of trust can be set by calling
	 * the Session::setTrusted() method.
	 * 
	 * @return bool
	 */
	public static function isTrusted()
	{
		
	}
	
	/**
	 * Get the member_srl of the currently logged in member.
	 * 
	 * This method returns an integer, or false if nobody is logged in.
	 *
	 * @return int|false
	 */
	public static function getMemberSrl()
	{
		
	}
	
	/**
	 * Get information about the currently logged in member.
	 * 
	 * This method returns an object, or false if nobody is logged in.
	 *
	 * @return object|false
	 */
	public static function getMemberInfo()
	{
		
	}
	
	/**
	 * Get the current user's preferred language.
	 * 
	 * If the current user does not have a preferred language, this method
	 * will return the default language.
	 *
	 * @return string
	 */
	public static function getLanguage()
	{
		
	}
	
	/**
	 * Set the current user's preferred language.
	 * 
	 * @param string $language
	 * @return bool
	 */
	public static function setLanguage($language)
	{
		
	}
	
	/**
	 * Get the current user's preferred time zone.
	 * 
	 * If the current user does not have a preferred time zone, this method
	 * will return the default time zone for display.
	 *
	 * @return string
	 */
	public static function getTimezone()
	{
		
	}
	
	/**
	 * Set the current user's preferred time zone.
	 * 
	 * @param string $timezone
	 * @return bool
	 */
	public static function setTimezone($timezone)
	{
		
	}
	
	/**
	 * Mark the current session as trusted for a given duration.
	 * 
	 * See isTrusted() for description.
	 * 
	 * @param int $duration (optional, default is 300 seconds)
	 * @return bool
	 */
	public static function setTrusted($duration = 300)
	{
		
	}
	
	/**
	 * Create a token that can only be verified in the same session.
	 * 
	 * This can be used to create CSRF tokens, etc.
	 * If you specify a key, the same key must be used to verify the token.
	 * 
	 * @param string $key (optional)
	 * @return string
	 */
	public static function createToken($key = null)
	{
		
	}
	
	/**
	 * Verify a token.
	 * 
	 * This method returns true if the token is valid, and false otherwise.
	 * 
	 * @param string $token
	 * @param string $key (optional)
	 * @return bool
	 */
	public static function verifyToken($token, $key = null)
	{
		
	}
	
	/**
	 * Invalidate a token so that it cannot be verified.
	 * 
	 * @param string $token
	 * @param string $key (optional)
	 * @return bool
	 */
	public static function invalidateToken($token)
	{
		
	}
	
	/**
	 * Encrypt data so that it can only be decrypted in the same session.
	 * 
	 * Arrays and objects can also be encrypted. (They will be serialized.)
	 * Resources and the boolean false value will not be preserved.
	 * 
	 * @param mixed $plaintext
	 * @return string
	 */
	public static function encrypt($plaintext)
	{
		
	}
	
	/**
	 * Decrypt data that was encrypted in the same session.
	 * 
	 * This method returns the decrypted data, or false on failure.
	 * All users of this method must be designed to handle failures safely.
	 * 
	 * @param string $ciphertext
	 * @return mixed
	 */
	public static function decrypt($ciphertext)
	{
		
	}
}
