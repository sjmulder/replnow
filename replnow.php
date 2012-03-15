#!/usr/bin/php
<?php

class QueryMsg
{
    public $Query;
    public $PageSize;

    function __construct($Query = null, $PageSize = null)
    {
        $this->Query    = $Query;
        $this->PageSize = $PageSize;
    }
}

require_once 'Console/CommandLine.php';

define('VERSION',       '0.1');
define('DECRIPTIOM',    'A REPL for the RightNow query language');
define('WSDL_FORMAT',   'https://%s/cgi-bin/%s.cfg/services/soap?wsdl=typed');
define('QUIT_COMMANDS', 'q quit exit stop');

$cmdline = new Console_CommandLine();
$cmdline->description = DESCRIPTION;
$cmdline->version     = VERSION;

$cmdline->addOption('host', array(
    'short_name'  => '-H',
    'long_name'   => '--host',
    'description' => 'hostname to connect to (e.g. foo.custhelp.com)',
    'help_name'   => 'HOST',
    'action'      => 'StoreString'
));

$cmdline->addOption('interface', array(
    'short_name'  => '-i',
    'long_name'   => '--interface',
    'description' => 'interface name (e.g. foo)',
    'help_name'   => 'INTERFACE',
    'action'      => 'StoreString'
));

$cmdline->addOption('username', array(
    'short_name'  => '-u',
    'long_name'   => '--user',
    'description' => 'authenticate as USER',
    'help_name'   => 'USER',
    'action'      => 'StoreString'
));

$cmdline->addOption('password', array(
    'short_name'  => '-p',
    'long_name'   => '--password',
    'description' => 'authenticate with PASSWORD',
    'help_name'   => 'PASSWORD',
    'action'      => 'StoreString'
));

try {
    $result = $cmdline->parse();
    $options = $result->options;
} catch (Console_CommandLine_Exception $e) {
    fprintf(STDERR, $e->getMessage() . "\n");
    exit(1);
}

printf('REPLnow ' . VERSION . "\n");

$host      = $options['host'];
$interface = $options['interface'];
$username  = $options['username'];
$password  = $options['password'];

while (empty($host))      $host      = readline('host:      ');
while (empty($interface)) $interface = readline('interface: ');
while (empty($username))  $username  = readline('username:  ');

if (function_exists('ncurses_noecho')) ncurses_noecho();
while (empty($password)) $password  = readline('password:  ');
if (function_exists('ncurses_echo')) ncurses_echo();

$wsdl_url = sprintf(WSDL_FORMAT, urlencode($host), urlencode($interface));

try {
    $client = new SoapClient($wsdl_url);
} catch (SoapFault $e) {
    fprintf(STDERR, $e->getMessage() . "\n");
    exit(1);
}

while (true) {
    printf("\n");
    $cmd = readline('> ');
    readline_add_history($cmd);

    if (in_array($cmd, explode(' ', QUIT_COMMANDS))) {
        printf("Bye!\n");
        exit(0);
    }

    try {
        $result = $client->QueryCSV(new QueryMsg($cmd));
        print_r($result);
    } catch (SoapFault $e) {
        printf($e->getMessage() . "\n");
    }
}

