# Sugar.php

Sugar is simple, Mocha(+Chai)-like testing framework for PHP (>5.4).

## Contents
- [Sugar.php](#sugarphp)
    - [Contents](#contents)
    - [API](#api)
        - [Core](#core)
            - [sugar( [$filter, $reporter, $unit_class] )](#sugar-filter-reporter-unitclass-)
            - [describe( $desc, $callback )](#describe-desc-callback-)
            - [it( $desc, $callback )](#it-desc-callback-)
            - [a( $value )](#a-value-)
            - [before_each( $callback )](#beforeeach-callback-)
            - [after_each( $callback )](#aftereach-callback-)
            - [with([ $param, $param ... ])](#with-param-param--)
        - [Comparators](#comparators)
            - [be( [$expected] )](#be-expected-)
            - [not( [$expected] )](#not-expected-)
            - [to( [$expected] )](#to-expected-)
            - [equal( [$expected] )](#equal-expected-)
            - [equals( [$expected] )](#equals-expected-)
            - [not_equal( [$expeted] )](#notequal-expeted-)
            - [not_equals()](#notequals)
            - [exactly( [$expected] )](#exactly-expected-)
        - [Number](#number)
            - [greater( [$expected] )](#greater-expected-)
            - [lower( [$expected] )](#lower-expected-)
            - [or_equal( [$expected] )](#orequal-expected-)
            - [or_equals( [$expected] )](#orequals-expected-)
            - [gt( [$expected] )](#gt-expected-)
            - [lt( [$expected] )](#lt-expected-)
            - [gte( [$expected] )](#gte-expected-)
            - [lte( [$expected] )](#lte-expected-)
            - [than( [$expected] )](#than-expected-)
        - [Bool](#bool)
            - [true()](#true)
            - [ok()](#ok)
            - [false()](#false)
        - [String](#string)
            - [contain( $substring, [$offset, $length] )](#contain-substring-offset-length-)
            - [length( $expected )](#length-expected-)
            - [in( $expected )](#in-expected-)
            - [match( $expected )](#match-expected-)
        - [Array](#array)
            - [keys( $expected )](#keys-expected-)
            - [only( $expected )](#only-expected-)
            - [contain( $expected )](#contain-expected-)
            - [length( $expected )](#length-expected-)
        - [Object](#object)
            - [properties( $expected )](#properties-expected-)
            - [methods( $expected )](#methods-expected-)
            - [subclass_of( $expected )](#subclassof-expected-)
        - [Time](#time)
            - [exceed( $expected )](#exceed-expected-)
        - [Output](#output)
            - [output( [$expected] )](#output-expected-)
            - [outputs( [$expected] )](#outputs-expected-)
            - [display( [$expected] )](#display-expected-)
        - [Exceptions and empty values](#exceptions-and-empty-values)
            - [blank()](#blank)
            - [not_blank()](#notblank)
            - [thrown()](#thrown)
            - [fail()](#fail)
        - [Special](#special)
            - [also()](#also)
    - [Linking words](#linking-words)
    - [Extending Sugar.php](#extending-sugarphp)
        - [Extending unit test's methods](#extending-unit-tests-methods)
        - [Changing reporter](#changing-reporter)

## API

### Core

#### sugar( [$filter, $reporter, $unit_class] )
Sugar() starts the execution of tests. You can specify which tests you would like to run in the first parameter, or leave it blank to run all the tests. Specify reporter in the second parameter or set it to `FALSE`, if you don't want to show HTML output. Sugar's default reporter is called `sugar_default_reporter`. You can set multiple reporters by passing an array. If you want to extend Sugar, you can extend Sugar's default class (`Sugar_unit_test`) that handles unit tests and passes the name of your newly created class as the third parameter.
```php
describe( '#create', function()
{
  it( 'should be successful', function()
  {
    a('success')->should->be->ok();
  });

  it( 'should not be successful', function()
  {
    a('success')->should->not()->be->ok();
  });

    // unit tests ...
});

describe( '#read', function()
{
    // unit tests ...
});

describe( '#update', function()
{
    // unit tests ...
});

describe( '#delete', function()
{
    // unit tests ...
});

// Note: These are alternatives of sugar() calling, do not call sugar() multiple times if you don't really need to.

sugar(); // will run all the tests and display the output
sugar( ['#create', '#read'] ); // will run only #create and #read unit tests
$result = sugar( NULL, FALSE ); // will run all the tests and return result as PHP array.
```

If we examine the $result, we will find this pattern:
```php
// return of print_r( $result );

Array
(
  [#create] => Array
  (
    [should be successful] => stdClass Object
    (
      [status] => OK
    )

    [should not be successful] => stdClass Object
    (
      [status] => FAIL
      [message] => Failed: Expected [success] not to be equal to [1].
      [stack] => Array
      (
        [0] => ok at my_test_script.php:117
        [1] => it at my_test_script.php:118
        [2] => sugar at my_test_script.php:121
      )
    )
  )
)
```


#### describe( $desc, $callback )
It names group of tests.
```php
describe( '#htmlspecialchars', function()
{
	it ('should replace chosen characters', function()
	{
		a('htmlspecialchars')->with('<')->should->be('&lt;');
		a('htmlspecialchars')->with('>')->should->be('&gt;');
		a('htmlspecialchars')->with('&')->should->be('&amp;');
		a('htmlspecialchars')->with('"')->should->be('&quot;');
		a('htmlspecialchars')->with('\'')->should->be('\'');
	});

	it ('should leave double quotes when flag ENT_NOQUOTES is set', function()
	{
		a('htmlspecialchars')->with('"', ENT_NOQUOTES)->should->be('"');
	});

	it ('should protect single quote when flag ENT_QUOTES is set', function()
	{
		a('htmlspecialchars')->with('\'', ENT_QUOTES )->should->be('&#039;');
	});

	// ...

});

sugar();
```

#### it( $desc, $callback )
Names tests, and also reports results of tests. it() contains a() (can contain more than one). Code example is above.

#### a( $value )
**Must be inside it().** Initializes value. When trying to test objects, use array form and .with():
```php
a(5)->should->be(5);
a( array( $my_object, 'my_method') )->with()->should->be->ok();
```

#### before_each( $callback )
Put this inside describe() and callback will be applied before each it().
```php
describe( '#create', function()
{
	before_each( function()
	{
		$this->db->truncate( 'table' ); // truncates table before each set of tests
	});

	it ('should add a new entry', function()
	{
		// ...
	});

	it ('should add a new entry with some option', function()
	{
		// ...
	});
});
```

#### after_each( $callback )
Similar to before_each(), but it will be applied after each it().

#### with([ $param, $param ... ])
When a() is set to function or object, with() runs it with specified parameters.
```php
a( 'htmlspecialchars' )->with('>')->should->be('&gt;');
```

---

### Comparators

#### be( [$expected] )
Without parameter it is used as linking word and does nothing. With parameter it's alias to equal().
```php
a( 'Hello' )->should->be()->equal( 'Hello' );
a( 'Hello' )->should->be( 'Hello' );
```

#### not( [$expected] )
Without parameter it sets result to opposite value. With parameter it is alias to not_equal().
```php
a( 5 )->should->not->be->equals->to( 6 );
a( 5 )->should->be->not( 6 );
```

#### to( [$expected] )
Without parameter it is used as linking word. With parameter it evaluates the test.
```php
a( TRUE )->to()->be( TRUE );
a( 6 )->should->equals->to( 6 );
```

#### equal( [$expected] )
Without parameter it is used as linking word and does (almost) nothing. With parameter it evaluates the test (==).
```php
a( 7 )->should->not()->be->equal( 8 );
a( 8 )->should->be->equal()->to( 8 );
```

#### equals( [$expected] )
Alias to equal()

#### not_equal( [$expeted] )
Without parameter it is alias to not(). With parameter it evaluates the test with the opposite result.
```php
a( 5 )->should->not_equal( 6 );
a( 5 )->should->not_equal->to( 6 );
```

#### not_equals()
Alias to not_equal()

#### exactly( [$expected] )
It is similar to equal, but it compares using ===.
```php
a(TRUE)->should->not()->be->exactly( 1 );
```

---

### Number

#### greater( [$expected] )
It sets sentence to comparing mode. Without parameter it works as linking word. With parameter it evaluates the test.
```php
a( 3 )->should->be->greater( 10 );
a( 3 )->should->be->greater->than( 10 );
```

#### lower( [$expected] )
It sets sentence to comparing mode. Without parameter it works as linking word. With parameter it evaluate sentence.
```php
a( 3 )->should->be->lower( 10 );
a( 3 )->should->be->lower->than( 10 );
```

#### or_equal( [$expected] )
After greater() and lower(), it allows to even compare the values. Without parameter it works as linking word. With parameter it evaluates the test.
```php
a( 8 )->should->be->greater()->or_equal( 9 );
a( 8 )->should->be->greater()->or_equal()->to( 8 );
```

#### or_equals( [$expected] )
Alias to or_equal()

#### gt( [$expected] )
Alias (abbreviation) to greater()

#### lt( [$expected] )
Alias (abbreviation) to lower()

#### gte( [$expected] )
Alias (abbreviation) for calling greater()->or_equals_to()

#### lte( [$expected] )
Alias (abbreviation) for calling lower()->or_equals_to()

#### than( [$expected] )
Evaluates the test. Useful after greater() and lower().
```php
a( 5 )->should->be->lower()->than( 7 );
```

---

### Bool

#### true()
Evaluates the test and checks if its output is equal ( === ) to TRUE.
```php
a( 5 )->should->be->true();
```

#### ok()
Evaluates the test and checks if its output is equal ( == ) to TRUE.
```php
a( 5 )->should->be->ok();
```

#### false()
Evaluates the test and checks if its output is equal ( === ) to FALSE.
```php
a( 5 )->should->be->ok();
```

---

### String

#### contain( $substring, [$offset, $length] )
Checks if string contains given substring. You can specify offset and length. Set length to 0 (default) for unlimited length. 
```php
a('Sugar is awesome')->should->contain( 'Sugar' );
a('Sugar is awesome')->should->not()->contain( 'Sugar', 1 );
```

#### length( $expected )
Checks if string has expected length. 
```php
a('Sugar')->should->have->length( 5 );
```

#### in( $expected )
Checks if value is in the array of expected values. **Note: $expected must be an array.**
```php
a('Sugar')->should->be->in( array( 'Sugar', 'is', 'awesome' ) );
a('Sugar')->should->not()->be->in( array( 'PHP', 'is', 'awesome' ) );
```

#### match( $expected )
Checks if value matches expected regex.
```php
a('Sugar is sweet.')->should->match( '/sweet/' );
```


---

### Array

#### keys( $expected )
Checks if given array has expected keys. **Note: $expected must be an array.**
```php
a( [ 'name' => 'Sugar.php', 'year' => '2014', 'licence' => 'MIT'] )->should->have->keys([ 'name', 'licence' ]);
```

#### only( $expected )
Checks if given array has only expected keys. **Note: $expected must be an array.**
```php
a( [ 'name' => 'Sugar.php', 'licence' => 'MIT'] )->should->have->only([ 'name', 'licence' ]);
a( [ 'name' => 'Sugar.php', 'licence' => 'MIT'] )->should->not()->have->only([ 'name']);
```

#### contain( $expected )
Checks if given array contains expected value. **Note: $expected must be an array.**
```php
a( ['sugar', 'is', 'awesome'] )->should->contain( ['Sugar'] );
```

#### length( $expected )
Checks if given array has expected length.
```php
a( ['sugar', 'is', 'awesome'] )->should->length( 3 );
```

---

### Object

#### properties( $expected )
Checks if given object has expected properties.
```php
$user = new StdClass;
$user->name = 'Alex';
$user->email = 'example@email.com';

a( $user )->should->have->properties( [ 'name', 'email' ] );
```

#### methods( $expected )
Checks if given object has expected methods.
```php
a( $user )->should->not()->have->methods( [ 'remove' ] );
```

#### subclass_of( $expected )
Checks if given object is subclass of given class.
```php
Class x{}
Class y extends x {}

$z = new y();

a( $z )->should->be->subclass_of( 'x' );
```

---

### Time

#### exceed( $expected )
Checks if given test haven't exceeded expected time in milliseconds.
```php
a( 'parse_xml' )->with( 'file.xml' )->should->not()->exceed( 50 );
```

---

### Output

#### output( [$expected] )
Works with output of function. Without parameter it rewrites return value of function with its output. With parameter it only evaluates the test.
```php
function hello_world() { echo 'Hello World'; }

determine ( '#hello_world', function()
{
	it ('should output Hello world', function()
	{
		a( 'hello_world' )->with()->should->output( 'Hello world' ); // Recommended
		a( 'hello_world' )->with()->output()->should->be( 'Hello world' );
	});
});
```

#### outputs( [$expected] )
Alias to output()

#### display( [$expected] )
Alias to output()

---

### Exceptions and empty values

#### blank()
Evaluates the test and asks if return value is empty (after using output/outputs/display with parameter it asks for its output).
```php
a( 'htmlspecialchars' )->with( '<' )->should->not()->be->blank();
```

#### not_blank()
Opposite to blank().

#### thrown()
Evaluates the test and checks if function throws an error.
```php
a( array( $windows, 'throw_some_random_error') )->should->have->thrown();
```

#### fail()
Alias to thrown().

---

### Special

#### also()
This is not recommended, but possible. You can combine multiple tests into one:
```php
a( 5 )->to->be->greater->than(3)->also()->lower->than( 7 );
```


## Linking words
These words do nothing: `should`, `be`, `to`, `equal`, `equals`, `have`. They do not need brackets. Some of them can have parameter and then they don't act like linking words.
Usage:
```php
a( 5 )->should->be->equal->to->false();
a( 5 )->should()->be()->equal()->to()->false(); // they can have brackets
a( 5 )->should->be( 5 ); // be is no longer linking word
```

## Extending Sugar.php

### Extending unit test's methods
You can extend default Sugar's functionality by creating your class that extends `Sugar_unit_test` and then passing its name as the third parameter to sugar().
```php
// In this example I'm going to add `is` as a linking word.

class My_Sugar extends Sugar_unit_test
{
  var $is;

  function My_Sugar( $value )
  {
    parent::__construct( $value );
    $this->is = $this;
  }

  function is()
  {
    return $this;
  }
}

describe( '#is', function()
{
  it('should be just a linking word', function()
  {
    a(5)->is->equal->to(5);
    a(5)->is()->equal->to(5);
  })
});

sugar( NULL, NULL, 'My_Sugar');
```

### Changing reporter
You can change reporter by creating a function and then passing its name as the second parameter of sugar().

```php
// In this example I'm going to create new reporter that shows only failed tests

function only_failed( $reports )
{
  foreach ( $reports as $desc => $results )
  {
    foreach ( $results as $it => $report )
    {
      if ( $report->status == 'FAIL' )
      {
        printf( '<div> %s: (<strong>%s</strong>) is failing -> %s </div>', $desc, $it, $report->message );
      }
    }
  }
}

sugar( NULL, 'only_failed' );
```
