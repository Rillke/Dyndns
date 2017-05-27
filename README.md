# Dyndns: a simple DynDNS server in PHP

This script takes the same parameters as the original dyndns.org server does. It can update a Tiny DNS server via [Dyndns-config](https://github.com/Rillke/Dyndns-config).

As it uses the same syntax as the original DynDNS.org servers do, a dynamic DNS server equipped with this script can be used with DynDNS compatible clients without having to modify anything on the client side.


### Features

This script handles DNS updates on the url

    https://yourdomain.tld/?hostname=<domain>&myip=<ipaddr>

For security HTTP basic auth is used. You can create multiple users and assign host names for each user.


### DNS-Server Installation

C.f. https://github.com/Rillke/Dyndns-config

### PHP script configuration

The PHP script is called by the DynDNS client, it validates the input and calls "nsupdate" to 
finally update the DNS with the new data. Its configuration is rather simple, the user database is
implemented as text file "dyndns.user" with each line containing

    <user>:<password>

Where the password is crypt'ed like in Apache's htpasswd files. Use -d parameter to select the CRYPT encryption.

    htpasswd -cB conf/dyndns.user user1

Hosts are assigned to users in using the file  "dyndns.hosts":

    <host>:<user>(,<user>,<user>,...)

(So users can update multiple hosts, and a host can be updated by multiple users).


### Installation via Composer

    # Install Composer
    curl -sS https://getcomposer.org/installer | php

    # Add Dyndns as a dependency
    php composer.phar require nicokaiser/dyndns:*

Then you can create a simple `index.php` with the configuration:

```php
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
```


### Usage

Authentication in URL:

    https://username:password@yourdomain.tld/?hostname=yourhostname&myip=ipaddress


Raw HTTP GET Request:

    GET /?hostname=yourhostname&myip=ipaddress HTTP/1.0 
    Host: yourdomain.tld 
    Authorization: Basic base-64-authorization 
    User-Agent: Company - Device - Version Number

Fragment base-64-authorization should be represented by Base 64 encoded username:password string.


### Implemented fields

- `hostname` Comma separated list of hostnames that you wish to update (up to 20 hostnames per request). This is a required field. Example: `hostname=dynhost1.yourdomain.tld,dynhost2.yourdomain.tld`
- `myip` IP address to set for the update. Defaults to the best IP address the server can determine.


### Return Codes

- `good` The update was successful, and the hostname is now updated.
- `badauth` The username and password pair do not match a real user.
- `notfqdn` The hostname specified is not a fully-qualified domain name (not in the form hostname.dyndns.org or domain.com).
- `nohost` The hostname specified does not exist in this user account (or is not in the service specified in the system parameter)
- `badagent` The user agent was not sent or HTTP method is not permitted (we recommend use of GET request method).
- `dnserr` DNS error encountered
- `911` There is a problem or scheduled maintenance on our side.


### Contributors

- @afrimberger (IPv6 support)


### License

MIT
