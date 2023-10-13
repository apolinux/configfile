# Config File

Set or get configuration items from dictionary style array inside file.

The configuration is wrote in a file contains an array that must be returned. 
```
// file called config.php 
return [
   'item1' => 'value1' ,
   'item2' => [
     'item3 => 'value2',
    ],
  ..
 ];
```
Then the items inside array can be obtained using dot notation, for example, for item3:
 
$result = Config::get('config.item2.item3');
 
The first part of notation 'config' is the filename without .php extension.

## instalation 

```
composer require apolinux/configfile
```

### Example

In the current directory there is a php file called myconfig.php that have the following code:

```
// myconfig.php 

return [
  'item' => ['item2' => ['item3' => ['item4' => ['http://uno']]]],
  'list' => ['blablabla'],
  'other' => ['a' => 'b', 'c' => 'd', 'e' => 'f'],
  'alt' => ['alt1' => ['ttt ' => [1, 2, 3, 4, 5, 10000]]],
];

```

Then we get the items:

```
<?php 

Config::init(__DIR__) ;

echo Config::get('myconfig.item.item2.item3.item4');
// it shows 'http://uno' 

echo Config::get('myconfig.list');
// shows 'blablabla' 

echo Config::set('myconfig.list','otherlist');
// set only in memory 'otherlist' in 'list' key from myconfig.php file
```

### Methods 

#### init($dir_base) 

defines the base directory of config files 


#### get($item, $default=null)

obtains value from key defined in $item. If there is no value found return $default. If $default is null, throws an exception

#### set($item, $value)

Put a value in key $item but only in memory. The file config remains untouched.

#### getToUserPwd($item, $default=null)

Group key and value in same text. Convert an indexed array like [ 'a' => 'b' ] to form 'a:b'.


#### getReplaced($item, $default=null)

Get item like get() method, but replaces wildards inside value returned 
enclosed with '%' like '%item%' with the value obtained in 
another get operation using this wildcard.

Example:
If Config::get('config.testwc') returns '%init%/some'
then getReplaced search for the value of Config::get('config.init') and replaces it in '%init%'
