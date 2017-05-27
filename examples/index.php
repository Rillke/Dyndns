<?php

require 'vendor/autoload.php';

$dyndns = new Dyndns\Server();

// Configuration
$dyndns
  ->setConfig('hostsFile', __DIR__ . '/../conf/dyndns.hosts') // hosts database
  ->setConfig('userFile', __DIR__ . '/../conf/dyndns.user')   // user database
  ->setConfig('debug', true)  // enable debugging
  ->setConfig('debugFile', '/tmp/dyndns.log') // debug file
  ->setConfig('tinydns.updateDir','/tmp/ddns_updates') // directory containing scheduled DNS updates
;

$dyndns->init();
