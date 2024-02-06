# Pest - a simple php test framework

Pest is a simple testing framework heavily inspired by jest, but does not attempt to cover all functionality.

This has been tested with php 5.6 and 8.2.

Running the tests in **test.php**
```sh
$ php test.php
```

outputs status for each test whether it PASS or FAIL.

```sh
 o PASS equality
 o PASS add two ints
 o PASS null
 o PASS zero
 o PASS testing floats may not always be ==
 o PASS testing floats with tolerance
 o PASS instanceof
 o PASS match with regexp
 o PASS throws
 o PASS the shopping list has milk on it
 o PASS check persons in list
 o PASS mock function
```
