# MK Data Factory / Stream

Note, the following should be added to the parent application's config/autoload/local.php config 
file, in order to cover MongoDB access cerdentials and base URLs for making use of the Stream API:

~~~~
'mkdf-stream' => [
        'user'      => 'username',
        'pass'      => 'password',
        'server-url'    => 'http://server-url',
        'public-url'    => 'http://public-url',
    ],
~~~~



