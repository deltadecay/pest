# Pest - a simple php test framework

Pest is a simple testing framework heavily inspired by jest, but does not attempt to cover all functionality.

# Example

```php
require_once("pest/pest.php");
use function \pest\test;
use function \pest\expect;

test("null", function() {
    $n = null;
    expect($n)->toBeNull();
    expect($n)->not()->toBeSet();
    expect($n)->toBeUnset();
    expect($n)->not()->toBeTruthy();
    expect($n)->toBeFalsy();
});
```
When run, this outputs:
```text
 o PASS null
```


## Running the tests

Check the test files in **[tests](tests/)** to see more examples on how it is used.

```shell
$ php tests/pest.test.php
```

outputs status for each test whether it PASS or FAIL.

```text
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

# Requirements

Developed and tested with php 5.6, 8.2 and 8.4.
