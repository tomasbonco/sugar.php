<?php

# Sugar.php
# Author: Tomáš Bončo
# Licence: MIT
# GitHub: https://github.com/tomasbonco/sugar_php

# Ver: 0.3
# Date: 9.7.2014


function describe( $desc, $callback )
{
	global $__sugar;


	# Validation

	if ( empty( $desc ) || ! is_string( $desc ) || strlen( trim( $desc )) == 0 ) throw new Exception( 'First parameter of it() is required and must be a string.' );
	if ( empty( $callback ) || ! is_callable( $callback) ) throw new Exception( 'Second parameter of it() is required and must be callable.' );


	# Create sugar, if it's not yet

	if ( empty( $__sugar ) )
	{
		$__sugar = new stdClass();
	}

	if ( empty( $__sugar->tests) )
	{
		$__sugar->tests = array();
	}


	# Save the test to global variable $__sugar, so sugar() can run it

	$__sugar->tests[ $desc ] = $callback;

}


function it( $desc, $callback )
{
	global $__sugar;


	# Validation

	if ( empty( $desc ) || ! is_string( $desc ) || strlen( trim( $desc )) == 0 ) throw new Exception( 'First parameter of it() is required and must be a string.' );
	if ( empty( $callback ) || ! is_callable( $callback) ) throw new Exception( 'Second parameter of it() is required and must be callable.' );

	if ( empty( $__sugar->tests )) throw new Exception( 'It() must be called inside describe().');


	# Apply before_each

	$before_each = $__sugar->before_each;
	$after_each = $__sugar->after_each;

	$before_each();


	# Run the test, save the result to $reporter

	$report = new stdClass;

	try
	{
		$callback();
		$report->status = 'OK';
	}

	catch ( Exception $e)
	{
		$report->status = 'FAIL';
		$report->message = $e->getMessage();
		$report->stack = array();

		$stack = $e->getTrace();
		
		array_walk( $stack, function( $item ) use( & $report )
		{
			if ( ! empty( $item['file'] ) && ! empty( $item['function'] ) && ! empty( $item['line'] ) )
			{
				if ( $item['file'] != __FILE__ )
				{
					$report->stack[] = sprintf( "%s at %s:%s", $item['function'], $item['file'], $item['line'] );
				}
			}
		});
	}


	# Save the result

	$__sugar->current_block[ $desc ] = $report;


	# Apply after_each

	$after_each();
}


function a( $value )
{
	global $__sugar;
	return new $__sugar->class( $value );
}


function before_each( $callback )
{
	global $__sugar;
	$__sugar->before_each = $callback;
}


function after_each( $callback )
{
	global $__sugar;
	$__sugar->after_each = $callback;
}


function sugar( $filter = NULL, $reporter = 'sugar_default_reporter', $unit_class = 'Sugar_unit_test' )
{
	global  $__sugar;


	# Validation

	if ( ! empty( $filter ) && ! is_array( $filter )) throw new Exception( 'First parameter ($filter) of Sugar must be an array (or null)!' );
	if ( ! empty( $output ) && ! is_bool( $output )) throw new Exception( 'Second parameter ($output) of Sugar must be bool!' );

	if ( empty( $__sugar ) ) return FALSE;
	if ( empty( $reporter ) ) $reporter = 'sugar_default_reporter';

	$__sugar->class = $unit_class;

	$tests = $__sugar->tests;


	# Filter the tests when $filter parameter exists

	if ( ! empty( $filter ) )
	{
		array_walk( $tests, function( &$value, $key ) use( $filter ) { if ( ! in_array( $key, $filter )) $value = NULL; });
		$tests = array_filter( $tests );
	}


	# Run the tests - this will run all the it()s

	foreach ( $tests as $test => $callback )
	{

		# Reset before_each and after_each

		$__sugar->before_each = function(){};
		$__sugar->after_each = function(){};

		$__sugar->results[ $test ] = array();
		$__sugar->current_block = & $__sugar->results[ $test ];

		$callback();

		# Reset before_each and after_each

		$__sugar->before_each = function(){};
		$__sugar->after_each = function(){};
	}


	# Calling reporter

	if ( ! empty( $reporter ) && ( is_string( $reporter ) || is_array( $reporter )))
	{
		if ( is_string( $reporter ) ) $reporter = array( $reporter );

		foreach( $reporter as $r )
		{
			if ( ! is_callable( $r ) ) throw new Exception( sprintf( 'Reporter must callable, "%s" is not.', $r ));
			$r( $__sugar->results );
		}
	}

	return $__sugar->results;
}


function sugar_default_reporter( $reports )
{
	foreach ( $reports as $desc => $results )
	{
		printf( '<div class="describe"> <h2> %s </h2> <div class="its">', $desc );

		foreach ( $results as $it => $report )
		{
			printf( "<div class='it'><span class='test-name'>%s</span> ................... <span class='test-result %s'>%s</span> \n<br> <div class='error'><div class='error-message'> <strong>%s</strong> </div><div class='error-trace'> %s </div></div>",
				$it, strtolower( $report->status ), $report->status, ! empty( $report->message ) ? $report->message : '', empty( $report->stack ) ? '' : join( "<br>\n", $report->stack ));
		}

		printf( '</div> </div>');
	}
}

class Sugar_unit_test
{
	var $value;
	var $mode = 'equal'; 	// equal, gt, gte, lt, lte
	var $negation = FALSE;
	var $throw = FALSE;
	var $start_time = NULL;


	var $should;
	var $be;
	var $to;
	var $equal;
	var $equals;
	var $have;


	function Sugar_unit_test( $value )
	{
		$this->value = $value;
		$this->start_time = microtime( TRUE );

		$this->should =
		$this->be =
		$this->to =
		$this->equal =
		$this->equals =
		$this->have =
		$this;
	}


	function with()
	{
		$params = func_get_args();

		if ( empty( $params )) $params = array();

		ob_start();

		try
		{
			$this->value = call_user_func_array( $this->value, $params );
		}

		catch( Exception $e )
		{
			$this->throw = TRUE;
			$this->value = $e->getMessage();
		}

		$this->_output = ob_get_contents();

		ob_end_clean();

		return $this;
	}


	/* Linking words */

	function should()
	{
		return $this;
	}

	function have()
	{
		return $this;
	}



	/* Comparators */

	function be( $value = NULL )
	{
		## a(5).to.be( 5 )
		## a(5).should.be.equal( 5 )

		if ( empty( $value ) )
		{
			return $this->should();
		}

		else
		{
			return $this->equal( $value );
		}
	}


	function to( $value = NULL )
	{
		# a(5).to.be.equal( 5 )
		# a(5).should.equals.to(5)

		if ( empty( $value ) )
		{
			return $this->should();
		}

		else
		{
			return $this->_mode( $value );
		}
	}


	function equal( $expected = NULL )
	{
		$this->mode = 'equal';

		if ( ! empty( $expected ) )
		{
			return $this->_mode( $expected );
		}

		else
		{
			return $this;
		}
	}


	function equals( $expected = NULL )
	{
		return $this->equal( $expected );
	}


	function not_equal( $expected = NULL )
	{
		$this->not();
		return $this->equal( $expected );
	}


	function not_equals( $expected = NULL )
	{
		return $this->not_equal( $expected);
	}


	function not( $expected = NULL )
	{
		# a( 5 ).should.not.be.equals.to( 3 )
		# a( 5 ).should.be.not( 3 )

		if ( empty( $expected ) )
		{
			$this->negation = ! $this->negation;
			return $this;
		}

		else
		{
			return $this->not_equal();
		}
	}


	function exactly( $expected )
	{
		if ( $this->mode == 'equal' && $this->_can_continue( $this->value === $expected ))
		{
			return $this;
		}

		else
		{
			$this->_throw( $this->value, 'to be exactly', $expected );
		}
	}


	/* Numbers */

	function greater( $expected = NULL )
	{
		$this->mode = 'gt';

		if ( ! empty( $expected ))
		{
			return $this->than( $expected );
		}

		else
		{
			return $this;
		}
	}


	function lower( $expected = NULL )
	{
		$this->mode = 'lt';

		if ( ! empty( $expected ))
		{
			return $this->than( $expected );
		}

		else
		{
			return $this;
		}
	}


	function or_equals( $expected = NULL )
	{
		if ( $this->mode == 'lt' || $this->mode == 'gt' )
		{
			$this->mode .= 'e';
		}

		if ( $expected )
		{
			return $this->than( $expected );	
		}

		else
		{
			return $this;
		}
	}


	function or_equal( $expected = NULL )
	{
		$this->or_equals( $expected );
	}


	function gt( $expected = NULL )
	{
		return $this->greater( $expected );
	}


	function gte( $expected = NULL )
	{
		$this->greater();
		return $this->or_equals( $expected );
	}


	function lt( $expected = NULL )
	{
		return $this->lower( $expected );
	}


	function lte( $expected = NULL )
	{
		$this->lower();
		return $this->or_equals( $expected );
	}


	function than( $expected )
	{
		return $this->_mode( $expected );
	}


	/* Bool */

	function true()
	{
		$this->exactly( TRUE );
	}


	function ok()
	{
		$this->equal( TRUE );
	}


	function false()
	{
		$this->exactly( FALSE );
	}


	/* String */

	function contain( $search, $offset = 0, $length = 0 )
	{
		if ( ! is_array( $this->value ) && ! is_string( $this->value )) $this->_throw( gettype( $this->value ), 'to be', 'array or string' );

		if ( is_string( $this->value ) )
		{
			if ( empty( $length )) $length = strlen( $search ) - $offset;

			$result = ( substr_count( $this->value, $search, $offset, $length ) > 0 );
		}

		else
		{
			$result = TRUE;

			foreach( $search as $value )
			{
				if ( in_array( $value, $this->value ) == FALSE )
				{
					$result = FALSE;
					break;
				}
			}
		}

		if ( $this->_can_continue( $result ))
		{
			return $this;
		}

		else
		{
			$this->_throw( $this->value, 'to contain', $search );
		}
		
	}

	function contains( $search, $offset = 0, $length = 0 )
	{
		return $this->contain( $search, $offset, $length );
	}


	function length( $expected )
	{
		if ( ! is_array( $this->value ) && ! is_string( $this->value )) $this->_throw( gettype( $this->value ), 'to be', 'array or string' );

		if (  $this->_can_continue( ( is_string( $this->value ) && strlen( $this->value ) == $expected ) || ( is_array( $this->value ) && count( $this->value ) == $expected ) ))
		{
			return $this;
		}

		else
		{
			$this->_throw( strlen( $this->value ), 'to be', $expected );
		}
	}


	function in( $expected )
	{
		if ( ! is_array( $expected)) throw new Exception( 'Incorrectly written test: in() accepts an array as a parameter' );

		if (  $this->_can_continue( in_array( $this->value, $expected ) ))
		{
			return $this;
		}

		else
		{
			$this->_throw( $this->value, 'to be one of', $expected );
		}
	}


	function match( $expected )
	{
		if ( $this->_can_continue( preg_match( $expected, $this->value ) ) )
		{
			return $this;
		}

		else
		{
			$this->_throw( $this->value, 'to match', $expected );
		}
	}


	/* Array */

	function keys( $expected )
	{
		if ( ! is_array( $this->value )) $this->_throw( gettype( $this->value ), 'to be', 'array' );
		if ( ! is_array( $expected)) throw new Exception( 'Incorrectly written test: keys() accepts an array as a parameter' );

		$flag = TRUE;

		foreach( $expected as $key )
		{
			if ( array_key_exists( $key, $this->value ) == FALSE )
			{
				$flag = FALSE;
				break;
			}
		}

		if ( $this->_can_continue( $flag ) )
		{
			return $this;
		}

		else
		{
			$this->_throw( $this->value, 'to have keys', $expected );
		}
	}


	function only( $expected )
	{
		if ( ! is_array( $this->value )) $this->_throw( gettype( $this->value ), 'to be', 'array' );
		if ( ! is_array( $expected )) throw new Exception( 'Incorrectly written test: only() accepts an array as a parameter' );

		$flag = TRUE;

		if ( count( $this->value ) != count( $expected ) )
		{
			$flag = FALSE;
		}

		else
		{
			foreach( $expected as $key )
			{
				if ( array_key_exists( $key, $this->value ) == FALSE )
				{
					$flag = FALSE;
					break;
				}
			}
		}

		if ( $this->_can_continue( $flag ) )
		{
			return $this;
		}

		else
		{
			$this->_throw( $this->value, 'to have only keys', $expected );
		}
	}


	/* Object */

	function properties( $expected )
	{
		if ( ! is_object( $this->value )) $this->_throw( gettype( $this->value ), 'to be', 'object' );
		if ( ! is_array( $expected )) throw new Exception( 'Incorrectly written test: only() accepts an array as a parameter' );

		$flag = TRUE;

		foreach( $expected as $key )
		{
			if ( property_exists( $this->value, $key ) == FALSE )
			{
				$flag = FALSE;
				break;
			}
		}

		if ( $this->_can_continue( $flag ) )
		{
			return $this;
		}

		else
		{
			$this->_throw( $this->value, '(' . join( ', ', array_keys( get_object_vars( $this->value ))) . ') to have properties', $expected );
		}
	}


	function methods( $expected )
	{
		if ( ! is_object( $this->value )) $this->_throw( gettype( $this->value ), 'to be', 'object' );
		if ( ! is_array( $expected )) throw new Exception( 'Incorrectly written test: only() accepts an array as a parameter' );

		$flag = TRUE;

		foreach( $expected as $key )
		{
			if ( method_exists( $this->value, $key ) == FALSE )
			{
				$flag = FALSE;
				break;
			}
		}

		if ( $this->_can_continue( $flag ) )
		{
			return $this;
		}

		else
		{
			$this->_throw( $this->value, '(may be inaccurate: ' . join( ', ', get_class_methods( get_class( $this->value ))) . ') to have methods', $expected );
		}
	}


	function subclass_of( $expected )
	{
		if ( ! is_object( $this->value )) $this->_throw( gettype( $this->value ), 'to be', 'object' );

		if ( $this->_can_continue( is_subclass_of( $this->value, $expected ) ))
		{
			return $this;
		}

		else
		{
			$this->_throw( $this->value, 'to be subclass of', $expected );
		}
	}


	/* Time */

	function exceed( $expected )
	{
		$time = round( microtime( TRUE ) - $this->start_time );

		if ( $this->_can_continue( $expected < $time ) )
		{
			return $this;
		}

		else
		{
			$this->_throw( $time . 'ms', 'to exceed', $expected .'ms' );
		}
	}


	/* Output */

	function outputs( $expected = NULL )
	{
		## THIS MAY REWRITE RETURN VALUE

		$value = $this->value;
		$this->value = $this->_output;

		if ( ! empty( $expected ) )
		{
			$return = $this->equal( $expected );
			$this->value = $value;

			return $return;
		}

		return $this;
	}


	function output( $expected = NULL )
	{
		return $this->outputs( $expected );
	}


	function display( $expected = NULL )
	{
		return $this->outputs( $expected );
	}


	/* Empty? */

	function blank()
	{
		if ( $this->_can_continue( empty( $this->value )))
		{
			return $this;
		}

		else
		{
			$this->_throw( $this->value, 'to be', 'Empty' );
		}
	}


	function not_blank()
	{
		$this->not();
		$this->empty();
	}


	function thrown()
	{
		if ( $this->_can_continue( $this->throw ))
		{
			return $this;
		}

		else
		{
			$this->_throw( $this->value, 'to throw', 'Exception' );
		}
	}


	function fail()
	{
		$this->thrown();
	}


	/* Data types */

	function _array()
	{
		if ( $this->_can_continue( is_array( $this->value )))
		{
			return $this;
		}

		else
		{
			$this->_throw( gettype( $this->value), 'to be', 'array' );
		}
	}


	function _bool()
	{
		if ( $this->_can_continue( is_bool( $this->value )))
		{
			return $this;
		}

		else
		{
			$this->_throw( gettype( $this->value), 'to be', 'bool' );
		}
	}


	function _callable()
	{
		if ( $this->_can_continue( is_callable( $this->value )))
		{
			return $this;
		}

		else
		{
			$this->_throw( gettype( $this->value), 'to be', 'callable' );
		}
	}


	function _double()
	{
		if ( $this->_can_continue( is_double( $this->value )))
		{
			return $this;
		}

		else
		{
			$this->_throw( gettype( $this->value), 'to be', 'double' );
		}
	}


	function _float()
	{
		if ( $this->_can_continue( is_float( $this->value )))
		{
			return $this;
		}

		else
		{
			$this->_throw( gettype( $this->value), 'to be', 'float' );
		}
	}


	function _int()
	{
		if ( $this->_can_continue( is_int( $this->value )))
		{
			return $this;
		}

		else
		{
			$this->_throw( gettype( $this->value), 'to be', 'int' );
		}
	}


	function _object()
	{
		if ( $this->_can_continue( is_object( $this->value )))
		{
			return $this;
		}

		else
		{
			$this->_throw( gettype( $this->value), 'to be', 'object' );
		}
	}


	function _resource()
	{
		if ( $this->_can_continue( is_resource( $this->value )))
		{
			return $this;
		}

		else
		{
			$this->_throw( gettype( $this->value), 'to be', 'resource' );
		}
	}


	function _scalar()
	{
		if ( $this->_can_continue( is_scalar( $this->value )))
		{
			return $this;
		}

		else
		{
			$this->_throw( gettype( $this->value), 'to be', 'scalar' );
		}
	}


	function _number()
	{
		if ( $this->_can_continue( is_numeric( $this->value )))
		{
			return $this;
		}

		else
		{
			$this->_throw( gettype( $this->value), 'to be', 'numeric' );
		}
	}


	function _string()
	{
		if ( $this->_can_continue( is_string( $this->value )))
		{
			return $this;
		}

		else
		{
			$this->_throw( gettype( $this->value), 'to be', 'string' );
		}
	}


	/* Special */

	function also()
	{
		$this->mode = '';
		$this->negation = FALSE;

		return $this;
	}


	/* Comparing */

	function _mode( $expected )
	{
		if ( ( $this->mode == 'equal' && ( ( ! $this->negation && $this->value == $expected ) || ( $this->negation && ! ( $this->value == $expected )) ))
		  || ( $this->mode == 'gt' 	&& ( ( ! $this->negation && $this->value > $expected ) 	|| ( $this->negation && ! ( $this->value > $expected )) ))
		  || ( $this->mode == 'lt' 	&& ( ( ! $this->negation && $this->value < $expected ) 	|| ( $this->negation && ! ( $this->value < $expected )) ))
		  || ( $this->mode == 'gte' && ( ( ! $this->negation && $this->value >= $expected ) || ( $this->negation && ! ( $this->value >= $expected )) ))
		  || ( $this->mode == 'lte' && ( ( ! $this->negation && $this->value <= $expected ) || ( $this->negation && ! ( $this->value <= $expected )) )) )
		{
			return $this;
		}

		else
		{
			$this->_throw( $this->value, 'to be equal to', $expected );
		}
	}


	function _throw( $expected, $sentence, $given )
	{
		throw new Exception( sprintf( 'Failed: Expected [%s] %s%s [%s].', $this->_stringify( $expected, TRUE ), $this->negation ? 'not ' : '' , $sentence, $this->_stringify( $given ) ));
	}


	function _stringify( $value, $keys = FALSE )
	{
		switch ( gettype( $value ) )
		{
			case 'array':

				$value = sprintf( '[%s]', join( ', ', $keys ? array_keys( $value ) : array_values( $value ) ));
				break;

			case 'object':

				$value = 'Object of ' . get_class( $value );
				break;

			case 'resource':

				$value = 'Resource';
				break;
		}

		return $value;
	}


	function _can_continue( $value )
	{
		return ( ( ! $this->negation && $value ) || ( $this->negation && ! $value ) );
	}
}