Sugar.php
=========

Sugar is simple, Mocha(+Chai)-like testing framework for PHP (>5.4).


API
===

### describe( $desc, $callback )
Name group of tests.
```php
describe( '#htmlspecialchars', function()
{
	it ('should replace correct characters', function()
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
```

### it( $desc, $callback )
Name tests, and also reports results of tests. it() contains a() (can contain more then one). Code example is above.

### a( $value )
**Must be inside it().** Initializes value. When trying to test objects, use array form and .with():
```php
a(5)->should->be(5);
a( array( $my_object, 'my_method') )->with()->should->ok();
```

### before_each( $callback )
Put this inside describe() and callback will be applied before each it().
```php
describe( '#create', function()
{
	before_each( function()
	{
		$this->db->truncate( 'table' ); // truncate table for each set of tests
	});

	it ('should add new entry', function()
	{
		// ...
	});

	it ('should add new entry with some option', function()
	{
		// ...
	});
});
```

### after_each( $callback )
Similar to before_each(), but applies after each it().

### with([ $param, $param ... ])
When a() is set to function or object, with() runs it with specified parameters.
```php
a( 'htmlspecialchars' )->with('>')->should->be('&gt;');
```

### should()
Linking word, does nothing.


### be( [$expected] )
Without parameter is used as linking word and does nothing. With parameter it's alias to equal().
```php
a( 'Hello' )->should->be()->equal( 'Hello' );
a( 'Hello' )->should->be( 'Hello' );
```

### not( [$expected] )
Without parameter sets result to opposite value. With parameter it is alias to not_equal().
```php
a( 5 )->should->not->be->equals->to( 6 );
a( 5 )->should->be->not( 6 );
```

### to( [$expected] )
Without parameter is used as linking word. With parameter it evaluate sentence.
```php
a( TRUE )->to()->be( TRUE );
a( 6 )->should->equals->to( 6 );
```

### equal( [$expected] )
Without parameter it is used as linking word and does (almost) nothing. With parameter it evaluate sentence (==).
```php
a( 7 )->should->not()->be->equal( 8 );
a( 8 )->should->be->equal()->to( 8 );
```

### equals( [$expected] )
Alias to equal()

### not_equal( [$expeted] )
Without parameter it is alias to not(). With parameter it evaluate sentence with opposite result.
```php
a( 5 )->should->not_equal( 6 );
a( 5 )->should->not_equal->to( 6 );
```

### not_equals()
Alias to not_equal()

### exactly( [$expected] )
It is similar to equal, but it compares using ===.
```php
a(TRUE)->should->not()->be->exactly( 1 );
```

### greater( [$expected] )
It sets sentence to comparing mode. Without parameter it works as linking word. With parameter it evaluate sentence.
```php
a( 3 )->should->be->greater( 10 );
a( 3 )->should->be->greater->then( 10 );
```

### lower( [$expected] )
It sets sentence to comparing mode. Without parameter it works as linking word. With parameter it evaluate sentence.
```php
a( 3 )->should->be->lower( 10 );
a( 3 )->should->be->lower->then( 10 );
```

### or_equal( [$expected] )
After greater() and lower(), it allows to even compare values. Without parameter it works as linking word. With parameter it evaluate sentence.
```php
a( 8 )->should->be->greater()->or_equal( 9 );
a( 8 )->should->be->greater()->or_equal()->to( 8 );
```

### or_equals( [$expected] )
Alias to or_equal()

### gt( [$expected] )
Alias (abbreviation) to greater()

### lt( [$expected] )
Alias (abbreviation) to lower()

### gte( [$expected] )
Alias (abbreviation) for calling greater()->or_equals_to()

### lte()
Alias (abbreviation) for calling lower()->or_equals_to()

### output( [$expected] )
Works with output of function. Without parameter it rewrites return value of function with it's output. With parameter it only evaluates sencence.
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

### outputs( [$expected] )
Alias to output()

### display( [$expected] )
Alias to output()

### blank()
Evaluates sentence and asks if return value is empty (after using output/outputs/display with parameter it asks for its output).
```php
a( 'htmlspecialchars' )->with( '<' )->should->not()->be->blank();
```

### not_blank()
Opposite to blank().

### true()
Evaluate sentence and check if it's output is equal ( === ) to TRUE.
```php
a( 5 ).should.be.true();
```

### ok()
Evaluate sentence and check if it's output is equal ( == ) to TRUE.
```php
a( 5 ).should.be.ok();
```

### false()
Evaluate sentence and check if it's output is equal ( === ) to FALSE.
```php
a( 5 ).should.be.ok();
```

### then( [$expected] )
Evaluates sentence. Useful after greater() and lower().
```php
a( 5 )->should->be->lower()->then( 7 );
```

### thrown()
Evaluates sentence and checks if function thrown error.
```php
a( array( $windows, 'throw_some_random_error') )->should->throw();
```

### fail()
Alias to thrown().

### also()
This is not recommended, but possible. You can combine multiple tests into one:
```php
a( 5 )->to->be->greater->then(3)->also()->lower->then( 7 );
```


## Linking words
Those words does nothing: `should`, `be`, `to`, `equal`, `equals`. They do not have brackets.
Usage:
```php
a( 5 )->should->be->equal->to( 5 ); // 'to' is not used as linking word in this context
```
