<?php

namespace Forge;

/**
 * User authorization library. Handles user login and logout, as well as secure
 * password hashing.
 *
 * @package    SuperFan
 * @category   Auth
 * @author     Zach Jenkins <zach@superfanu.com>
 * @copyright  (c) 2017 SuperFan, Inc.
 */
abstract class Auth
{
    // Auth instances
    protected static $_instance;

    /**
     * Singleton pattern
     *
     * @return Auth
     */
    public static function instance()
    {
        if( ! isset( self::$_instance ) )
        {
            // Load the configuration for this type
            $config = Foundation::$config->load( 'auth' );

            if( ! $type = $config->get( 'driver' ) )
            {
                $type = 'file';
            }

            // Set the session class name
            $class = 'Auth_' . ucfirst( $type );

            // Create a new session instance
            self::$_instance = new $class( $config );
        }

        return self::$_instance;
    }

    protected $_session;

    protected $_config;

    /**
     * Loads Session and configuration options.
     *
     * @param   array  $config  Config Options
     * @return  void
     */
    public function __construct( $config = array() )
    {
        // Save the config in the object
        $this->_config = $config;

        $this->_session = Session::instance( $this->_config['session_type'] );
    }

    abstract protected function _login( $username, $password, $remember );

    abstract public function password( $username );

    abstract public function check_password( $password );

    /**
     * Gets the currently logged in user from the session.
     * Returns NULL if no user is currently logged in.
     *
     * @param   mixed  $default  Default value to return if the user is currently not logged in.
     * @return  mixed
     */
    public function get_user( $default = NULL )
    {
        return $this->_session->get( $this->_config['session_key'], $default );
    }

    /**
     * Attempt to log in a user by using an ORM object and plain-text password.
     *
     * @param   string   $username  Username to log in
     * @param   string   $password  Password to check against
     * @param   boolean  $remember  Enable autologin
     * @return  boolean
     */
    public function login( $username, $password, $remember = FALSE )
    {
        if( empty( $password ) )
        {
            return FALSE;
        }

        return $this->_login( $username, $password, $remember );
    }

    /**
     * Log out a user by removing the related session variables.
     *
     * @param   boolean  $destroy     Completely destroy the session
     * @param   boolean  $logout_all  Remove all tokens for user
     * @return  boolean
     */
    public function logout( $destroy = FALSE, $logout_all = FALSE )
    {
        if( $destroy === TRUE )
        {
            // Destroy the session completely
            $this->_session->destroy();
        }
        else
        {
            // Remove the user from the session
            $this->_session->delete( $this->_config['session_key'] );

            // Regenerate session_id
            $this->_session->regenerate();
        }

        // Double check
        return ! $this->logged_in();
    }

    /**
     * Check if there is an active session. Optionally allows checking for a
     * specific role.
     *
     * @param   string  $role  role name
     * @return  mixed
     */
    public function logged_in( $role = NULL )
    {
        return ( $this->get_user() !== NULL );
    }

    /**
     * Perform a hmac hash, using the configured method.
     *
     * @param   string  $str  string to hash
     * @return  string
     */
    public function hash( $str )
    {
        if( ! $this->_config['hash_key'] )
        {
            throw new Foundation_Exception( 'A valid hash key must be set in your auth config.' );
        }

        return hash_hmac( $this->_config['hash_method'], $str, $this->_config['hash_key'] );
    }

    protected function complete_login( $user )
    {
        // Regenerate session_id
        $this->_session->regenerate();

        // Store username in session
        $this->_session->set( $this->_config['session_key'], $user );

        return TRUE;
    }

}
