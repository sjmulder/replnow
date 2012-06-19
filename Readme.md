# REPLnow

REPLnow is an interactive command line tool for running ROQL queries. ROQL is the query language used by RightNow. 

## Usage

    $ ./replnow.php --help
    A REPL for the RightNow query language
    Usage:
      ./replnow.php [options]
    Options:
      -H HOST, --host=HOST                 hostname to connect to (e.g.
                                           foo.custhelp.com)
      -i INTERFACE, --interface=INTERFACE  interface name (e.g. foo)
      -u USER, --user=USER                 authenticate as USER
      -p PASSWORD, --password=PASSWORD     authenticate with PASSWORD
      -v, --verbose                        print requests and responses
      -h, --help                           show this help message and exit
      --version                            show the program version and exit    

## Exampe

    $ ./replnow.php -H example.custhelp.com -i foo
    REPLnow 0.2

    > select * from ServiceCategory limit 10
    
    [ServiceCategory]
    ID,CreatedTime,UpdatedTime,DisplayOrder,Name,Parent
    9,,,43,General,
    16,,,2,-,9
    17,,,3,-,9
    55,,,3,About Foo,
    60,,,4,My Account,
    61,,,5,Profile Settings,
    74,,,6,Sharing,
    86,,,8,Searching,
    89,,,7,Sending Private Messages,
    94,,,10,Uploading Pictures,

    > q
    Bye!

 Written bij Sijmen Mulder, licenced under the 3-clause BSD licence.