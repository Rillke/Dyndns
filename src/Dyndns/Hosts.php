<?php

namespace Dyndns;

/**
 * Host database.
 */
class Hosts
{
    /**
     * Filename of the hosts file (dyndns.hosts)
     * @var string
     */
    private $hostsFile;

    /**
     * Host/Users array:  'hostname' => array ('user1', 'user2', ...)
     * @var array
     */
    private $hosts;

    /**
     * List of updates in the format 'hostname' => 'ip'
     * @var array
     */
    private $updates;

    /**
     * This is true if the status / user files were read
     * @var boolean
     */
    private $initialized;

    /**
     * Constructor.
     *
     * @param string $hostsFile
     */
    public function __construct($hostsFile)
    {
        $this->hostsFile = $hostsFile;
        $this->initialized = false;
        $this->updates = array();
    }

    /**
     * Adds an update to the list
     *
     * @param string $hostname
     * @param string $ip
     */
    public function update($hostname, $ip)
    {
        if (! $this->initialized) {
            $this->init();
        }

        $this->debug('Update: ' . $hostname . ':' . $ip);
        $this->updates[$hostname] = $ip;
        return true;
    }

    /**
     * Checks if the host belongs to the user
     *
     * @param string $user
     * @param string $hostname
     * @return boolean True if the user is allowed to update the host
     */
    function checkUserHost($user, $hostname)
    {
        if (! Helper::checkValidHost($hostname)) {
            $this->debug('Invalid host: ' . $hostname);
            return false;
        }

        if (! $this->initialized) {
            $this->init();
        }

        if (is_array($this->hosts)) {
            foreach ($this->hosts as $line) {
                if (preg_match("/^(.*?):(.*)/", $line, $matches)) {
                    if (Helper::compareHosts($matches[1], $hostname, '*') &&
                            in_array($user, explode(',', strtolower($matches[2])))) {
                        return true;
                    }
                }
            }
        }
        $this->debug('Host '.$hostname.' does not belong to user '.$user);
        return false;
    }

    /**
     * Write cached changes to the status file
     */
    public function flush()
    {
        return $this->updateBind();
    }

    /**
     * Initializes the user and status list from the file
     *
     * @access private
     */
    private function init()
    {
        if ($this->initialized) return;

        $this->readHostsFile();
        if (! is_array($this->hosts)) {
            $this->hosts = array();
        }

        $this->initialized = true;
    }

    function readHostsFile()
    {
        $lines = @file($this->hostsFile);
        if (is_array($lines)) {
            $this->hosts = $lines;
        } else {
            $this->debug('Empty hosts file: "' . $this->hostsFile . '"');
        }
    }

    /**
     * Sends DNS Updates to BIND server
     *
     * @access private
     */
    private function updateBind()
    {
        $updatedir = $this->getConfig('tinydns.updateDir');

        // sanitiy checks
        if (! is_dir($updatedir)) {
            $this->debug('WARNING: Creating DNS-Update directory at ' . $updatedir);
            if (! mkdir($updatedir)) {
               $this->debug('ERROR: Can\'t create DNS-Update directory for pickup by CRON.');
               return false;
            }
        }

        foreach ($this->updates as $host => $ip) {
           $recType = Helper::getRecordType($ip);
           
           if ($recType === FALSE) {
	      $this->debug('ERROR: unknown record type');
           }
           
           if (! Helper::hasIPChanged($host, $ip)) {
	      continue;
           }

           $target =  "$updatedir/$host";
           if (file_put_contents($target, $ip, LOCK_EX) !== strlen($ip)) {
              return false;
           }
        }

        return true;
    }

    private function getConfig($key)
    {
        return $GLOBALS['dyndns']->getConfig($key);
    }

    private function debug($message)
    {
        return $GLOBALS['dyndns']->debug($message);
    }
}
