# MK Data Factory / Stream

### v0.10.0 changes
Added functionality for registering multiple keys against a single dataset and being able to remove access of your own keys from a dataset.
Requires mkdf-keys v0.9.6

---

Note that the following should be added to the parent application's config/autoload/local.php config 
file, in order to cover MongoDB access credentials and base URLs for making use of the Stream API:


```php
'mkdf-stream' => [
        'user'      => 'username',
        'pass'      => 'password',
        'server-url'    => 'http://server-url',
        'public-url'    => 'http://public-url',
    ],
```



