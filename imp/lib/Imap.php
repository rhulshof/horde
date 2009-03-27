<?php
/**
 * The IMP_Imap:: class provides common functions for interaction with the
 * IMAP/POP3 server via the Horde_Imap_Client:: library.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Imap
{
    /**
     * The Horde_Imap_Client object.
     *
     * @var Horde_Imap_Client
     */
    public $ob = null;

    /**
     * The Horde_Imap_Client_Utils object.
     *
     * @var Horde_Imap_Client_Utils
     */
    public $utils = null;

    /**
     * Is connection read-only?
     *
     * @var array
     */
    protected $_readonly = array();

    /**
     * Namespace cache.
     *
     * @var array
     */
    protected $_nscache = array();

    /**
     * Default namespace.
     *
     * @var array
     */
    protected $_nsdefault;

    /**
     * Constructor.
     */
    function __construct()
    {
        /* Register the logging callback. */
        Horde_Imap_Client_Exception::$logCallback = array($this, 'logException');

        /* Rebuild the Horde_Imap_Client object. */
        $this->_loadImapObject();

        $this->utils = new Horde_Imap_Client_Utils();
    }

    /**
     * Save the Horde_Imap_Client object on session shutdown.
     */
    function __destruct()
    {
        /* Only need to serialize object once a second. When we do serialize,
         * make sure we login in order to ensure we have done the necessary
         * initialization. */
        if ($this->ob &&
            isset($_SESSION['imp']) &&
            !isset($_SESSION['imp']['imap_ob'])) {
            $this->ob->login();
            $_SESSION['imp']['imap_ob'] = serialize($this->ob);
        }
    }

    /**
     * Loads the server configuration from servers.php.
     *
     * @param string $server  Returns this labeled entry only.
     *
     * @return mixed  If $server is set, then return this entry, or return the
     *                entire servers array. Returns false on error.
     */
    static public function loadServerConfig($server = null)
    {
        $servers = Horde::loadConfiguration('servers.php', 'servers', 'imp');
        if (is_a($servers, 'PEAR_Error')) {
            Horde::logMessage($servers, __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }

        if (is_null($server)) {
            return $servers;
        }

        /* Check for the existence of the server in the config file. */
        if (empty($servers[$server]) || !is_array($servers[$server])) {
            $entry = sprintf('Invalid server key "%s" from client [%s]', $server, $_SERVER['REMOTE_ADDR']);
            Horde::logMessage($entry, __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }

        return $servers[$server];
    }

    /**
     * Loads the Horde_Imap_Client object from serialized session data and
     * loads the object into the global $imp_imap variable.
     *
     * @return boolean  True on success, false on error.
     */
    protected function _loadImapObject()
    {
        if (!is_null($this->ob)) {
            return true;
        }

        if (!isset($_SESSION['imp']['imap_ob'])) {
            return false;
        }

        Horde_Imap_Client::$encryptKey = IMP::getAuthKey();

        $old_error = error_reporting(0);
        $this->ob = unserialize($_SESSION['imp']['imap_ob']);
        error_reporting($old_error);

        if (empty($this->ob)) {
            // @todo How to handle bad unserialize?
            // @todo Log message
            return false;
        }

        $this->_postcreate($_SESSION['imp']['protocol']);

        return true;
    }

    /**
     * Create a new Horde_Imap_Client object.
     *
     * @param string $username  The username to authenticate with.
     * @param string $password  The password to authenticate with.
     * @param string $key       Create a new object using this server key.
     * @param boolean $global   If true, treate the created object as the IMP
     *                          global IMAP object.
     *
     * @return boolean  The object on success, false on error.
     */
    public function createImapObject($username, $password, $key,
                                     $global = true)
    {
        if ($global && !is_null($this->ob)) {
            return $GLOBALS['imp_imap'];
        }

        if (($server = $this->loadServerConfig($key)) === false) {
            return false;
        }

        $protocol = isset($server['protocol']) ? strtolower($server['protocol']) : 'imap';

        $imap_config = array(
            'debug' => isset($server['debug']) ? $server['debug'] : null,
            'hostspec' => isset($server['hostspec']) ? $server['hostspec'] : null,
            'password' => $password,
            'port' => isset($server['port']) ? $server['port'] : null,
            'secure' => isset($server['secure']) ? $server['secure'] : false,
            'statuscache' => true,
            'timeout' => !empty($server['timeout']) ? $server['timeout'] : 10,
            'username' => $username,
        );

        /* Initialize caching. */
        if (!empty($server['cache'])) {
            $c = $server['cache'];
            $driver = $GLOBALS['conf']['cache']['driver'];
            if ($driver != 'none') {
                $imap_config['cache'] = array(
                    'comparator' => empty($c['comparator']) ? false : $c['comparator'],
                    'compress' => empty($c['compress']) ? false : $c['compress'],
                    'driver' => $driver,
                    'driver_params' => Horde::getDriverConfig('cache', $driver),
                    'id' => empty($c['id']) ? false : $c['id'],
                    'lang' => empty($c['lang']) ? false : $c['lang'],
                    'lifetime' => empty($c['lifetime']) ? false : $c['lifetime'],
                    'slicesize' => empty($c['slicesize']) ? false : $c['slicesize'],
                );
            }
        }

        try {
            $ob = Horde_Imap_Client::getInstance(($protocol == 'imap') ? 'Socket' : 'Socket_Pop3', $imap_config);
        } catch (Horde_Imap_Client_Exception $e) {
            return false;
        }

        if ($global) {
            $this->ob = $ob;
            $this->_postcreate($protocol);
        }

        return $ob;
    }

    /**
     * Alter some IMP settings once we load/create the global object.
     *
     * @param string $protocol  The protocol used to connect.
     */
    protected function _postcreate($protocol)
    {
        global $conf, $prefs;

        switch ($protocol) {
        case 'pop':
            /* Turn some options off if we are working with POP3. */
            $conf['user']['allow_folders'] = false;
            $prefs->setValue('save_sent_mail', false);
            $prefs->setLocked('save_sent_mail', true);
            $prefs->setLocked('sent_mail_folder', true);
            $prefs->setLocked('drafts_folder', true);
            $prefs->setLocked('trash_folder', true);
            break;
        }
    }

    /**
     * Is the given mailbox read-only?
     *
     * @param string $mailbox  The mailbox to check.
     *
     * @return boolean  Is the mailbox read-only?
     */
    public function isReadOnly($mailbox)
    {
        if (!isset($this->_readonly[$mailbox])) {
            /* These tests work on both regular and search mailboxes. */
            $res = !empty($GLOBALS['conf']['hooks']['mbox_readonly']) &&
                Horde::callHook('_imp_hook_mbox_readonly', array($mailbox), 'imp');

            /* This check can only be done for regular IMAP mailboxes. */
            // TODO: POP3 also?
            if (!$res &&
                ($_SESSION['imp']['protocol'] == 'imap') &&
                !$GLOBALS['imp_search']->isSearchMbox($mailbox)) {
                try {
                    $status = $this->ob->status($mailbox, Horde_Imap_Client::STATUS_UIDNOTSTICKY);
                    $res = $status['uidnotsticky'];
                } catch (Horde_Imap_Client_Exception $e) {}
            }

            $this->_readonly[$mailbox] = $res;
        }

        return $this->_readonly[$mailbox];
    }

    /**
     * Logs an exception from Horde_Imap_Client.
     *
     * @param object Horde_Imap_Client_Exception $e  The exception object.
     */
    public function logException($e)
    {
        Horde::logMessage($e, $e->getFile(), $e->getLine(), PEAR_LOG_ERR);
    }

    /**
     * Get the namespace list.
     *
     * @return array  An array of namespace information.
     */
    public function getNamespaceList()
    {
        try {
            return $GLOBALS['imp_imap']->ob->getNamespaces(!empty($_SESSION['imp']['imap_ext']['namespace']) ? $_SESSION['imp']['imap_ext']['namespace'] : array());
        } catch (Horde_Imap_Client_Exception $e) {
            // @todo Error handling
            return array();
        }
    }

    /**
     * Get namespace info for a full folder path.
     *
     * @param string $mailbox  The folder path. If empty, will return info
     *                         on the default namespace (i.e. the first
     *                         personal namespace).
     * @param boolean $empty   If true and no matching namespace is found,
     *                         return the empty namespace, if it exists.
     *
     * @return mixed  The namespace info for the folder path or null if the
     *                path doesn't exist.
     */
    public function getNamespace($mailbox = null, $empty = true)
    {
        if ($_SESSION['imp']['protocol'] == 'pop') {
            return null;
        }

        $ns = $this->getNamespaceList();

        if ($mailbox === null) {
            reset($ns);
            $mailbox = key($ns);
        }

        $key = (int)$empty;
        if (isset($this->_nscache[$key][$mailbox])) {
            return $this->_nscache[$key][$mailbox];
        }

        foreach ($ns as $key => $val) {
            $mbx = $mailbox . $val['delimiter'];
            if (!empty($key) && (strpos($mbx, $key) === 0)) {
                $this->_nscache[$key][$mailbox] = $val;
                return $val;
            }
        }

        $this->_nscache[$key][$mailbox] = ($empty && isset($ns[''])) ? $ns[''] : null;

        return $this->_nscache[$key][$mailbox];
    }

    /**
     * Get the default personal namespace.
     *
     * @return mixed  The default personal namespace info.
     */
    public function defaultNamespace()
    {
        if ($_SESSION['imp']['protocol'] == 'pop') {
            return null;
        }

        if (!isset($this->_nsdefault)) {
            $this->_nsdefault = null;
            foreach ($this->getNamespaceList() as $val) {
                if ($val['type'] == 'personal') {
                    $this->_nsdefault = $val;
                    break;
                }
            }
        }

        return $this->_nsdefault;
    }
}
