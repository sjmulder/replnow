#!/usr/bin/php
<?php

class DebugSoapClient extends SoapClient
{
    function __doRequest($request, $location, $action, $version, $one_way = null)
    {
        if ($one_way === null) $one_way = 1;
        printf("\nRequest:\n");
        print_r($request);
        return parent::__doRequest($request, $location, $action, $version, $one_way);
    }
}

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

define('VERSION',       '0.2');
define('APP_ID',        'replnow');
define('DESCRIPTION',   'A REPL for the RightNow query language');
define('WSDL_FORMAT',   'https://%s/cgi-bin/%s.cfg/services/soap?wsdl=typed');
define('QUIT_COMMANDS', 'q quit exit stop');
define('WSSE_NS',       'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
define('RIGHTNOW_NS',   'urn:messages.ws.rightnow.com/v1');

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

$cmdline->addOption('verbose', array(
    'short_name'  => '-v',
    'long_name'   => '--verbose',
    'description' => 'print requests and responses',
    'action'      => 'StoreTrue'
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
$verbose   = $options['verbose'];

while (empty($host))      $host      = readline('host:      ');
while (empty($interface)) $interface = readline('interface: ');
while (empty($username))  $username  = readline('username:  ');

if (function_exists('ncurses_noecho')) ncurses_noecho();
while (empty($password))  $password  = readline('password:  ');
if (function_exists('ncurses_echo')) ncurses_echo();

$wsdl_url = sprintf(WSDL_FORMAT, urlencode($host), urlencode($interface));

try {
    $clientClass = $verbose ? 'DebugSoapClient' : 'SoapClient';
    $client = new $clientClass($wsdl_url, array('trace' => 1));
} catch (SoapFault $e) {
    fprintf(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$security_xml = '
<hns1:Security xmlns:hns1="' . WSSE_NS . '" SOAP-ENV:mustUnderstand="1">
    <hns1:UsernameToken>
        <hns1:Username>' . $username . '</hns1:Username>
        <hns1:Password>' . $password . '</hns1:Password>
    </hns1:UsernameToken>
</hns1:Security>';

$client_info_xml = '
<hns2:ClientInfoHeader xmlns:hns2="' . RIGHTNOW_NS . '" SOAP-ENV:mustUnderstand="0">
    <hns2:AppID>' . APP_ID . '</hns2:AppID>
</hns2:ClientInfoHeader>';

$client->__setSoapHeaders(array(
    new SoapHeader(WSSE_NS, 'Security', new SoapVar($security_xml, XSD_ANYXML), true),
    new SoapHeader(WSSE_NS, 'ClientInfoHeader', new SoapVar($client_info_xml, XSD_ANYXML), false)
));

$skipNewline = false;
while (true) {
    if ($skipNewline) {
        $skipNewline = false;
    } else {
        printf("\n");
    }

    $cmd = readline('> ');
    $cmd = trim($cmd);
    if (empty($cmd)) {
        $skipNewline = true;
        continue;
    }
    readline_add_history($cmd);

    if (in_array($cmd, explode(' ', QUIT_COMMANDS))) {
        printf("Bye!\n");
        exit(0);
    }

    try {
        $result = $client->QueryCSV(new QueryMsg($cmd));
    } catch (SoapFault $e) {
        if ($verbose) {
            printf("\nError:\n");
            print_r($client->__getLastResponse());
            printf("\n");
        } else {
            printf("%s\n", $e->faultstring);
        }
        continue;
    }

    if ($verbose) {
        printf("\nResult:\n");
        print_r($result);
    }

    if (!isset($result->CSVTableSet)) {
        printf("No table set returned.\n");
        continue;
    }
    $tableSet = $result->CSVTableSet;
    if (empty($tableSet->CSVTables)) {
        printf("No tables returned.\n");
        continue;
    }

    $tables = $tableSet->CSVTables;
    $firstTable = true;
    foreach ($tables as $table) {
        printf("\n[%s]\n", $table->Name);
        if (empty($table->Columns)) {
            printf("(no columns)\n");
            continue;
        }
        printf("%s\n", $table->Columns);
        if (empty($table->Rows->Row)) {
            printf("(no rows)\n");
            continue;
        }
        $rows = $table->Rows->Row;
        if (!is_array($rows)) {
            $rows = array($rows);
        }
        foreach ($rows as $row) {
            printf("%s\n", $row);
        }
    }
}

