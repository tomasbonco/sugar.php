<?php

# Sugar.php
# Author: Tomáš Bončo
# Licence: MIT
# GitHub: https://github.com/tomasbonco/sugar_php

# Ver: 0.2
# Date: 28.6.2014


function describe( $desc, $callback )
{
	global $__sugar;

	if ( empty( $desc ) || ! is_string( $desc ) || strlen( trim( $desc )) == 0 ) throw new Exception( 'First parameter of it() is required and must be string.' );
	if ( empty( $callback ) || ! is_callable( $callback) ) throw new Exception( 'Second parameter of it() is required and must be callable.' );

	if ( empty( $__sugar ) )
	{
		$__sugar = new stdClass();
	}

	if ( empty( $__sugar->tests) )
	{
		$__sugar->tests = array();
	}

	$__sugar->tests[ $desc ] = $callback;

}

function it( $desc, $callback )
{
	global $__sugar;

	if ( empty( $desc ) || ! is_string( $desc ) || strlen( trim( $desc )) == 0 ) throw new Exception( 'First parameter of it() is required and must be string.' );
	if ( empty( $callback ) || ! is_callable( $callback) ) throw new Exception( 'Second parameter of it() is required and must be callable.' );

	if ( empty( $__sugar->tests )) throw new Exception( 'It() must be called inside describe().');

	$before_each = $__sugar->before_each; 	# Funny fact: PHP can't do $__sugar->before_each(), because before_each wasn't defined as a method
	$after_each = $__sugar->after_each;

	$before_each();

	$report = new stdClass;

	try
	{
		$callback();
		$report->status = 'OK';
	}

	catch ( Exception $e)
	{
		$report->status = 'FAIL';
		$report->stack = array();
		
		array_walk( $e->getTrace(), function( $item )
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

	$__sugar->current_block[ $desc ] = $report;

	$after_each();
}

function a( $value )
{
	return new Sugar_unit_test( $value );
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

function sugar( $filter = NULL, $output = TRUE, $reporter = 'sugar_report' )
{
	global  $__sugar;

	if ( ! empty( $filter ) && ! is_array( $filter )) throw new Exception( 'First parameter ($filter) of Sugar must be an array (or null)!' );
	if ( ! empty( $output ) && ! is_bool( $output )) throw new Exception( 'Second parameter ($output) of Sugar must be bool!' );




	if ( empty( $__sugar ) )
	{
		return FALSE;
	}


	$tests = $__sugar->tests;

	if ( ! empty( $filter ) )
	{
		array_walk( $tests, function( &$value, $key ) use( $filter ) { if ( ! in_array( $key, $filter )) $value = NULL; });
		$tests = array_filter( $tests );
	}

	foreach ( $tests as $test => $callback )
	{
		$__sugar->before_each = function(){ echo 'aa'; };
		$__sugar->after_each = function(){};

		$__sugar->results[ $test ] = array();
		$__sugar->current_block = & $__sugar->results[ $test ];

		$callback();

		$__sugar->before_each = function(){};
		$__sugar->after_each = function(){};
	}

	if ( ! empty( $reporter ))
	{
		if ( ! is_callable( $reporter ) ) throw new Exception( sprintf( 'Reporter must callable, "%s" is not.', $reporter ));
		$reporter();
	}

	return $__sugar->results;
}


function sugar_report()
{
	global $__sugar;

	foreach ( $__sugar->results as $desc => $results )
	{
		printf( '<div class="describe"> <h2> %s </h2> <div class="its">', $desc );

		foreach ( $results as $it => $report )
		{
			printf( "<div class='it'><span class='test-name'>%s</span> ................... <span class='test-result %s'>%s</span> \n<br> %s </div>", $it, strtolower( $report->status ), $report->status, empty( $report->stack ) ? '' : $report->stack );
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


	var $should;
	var $be;
	var $to;
	var $equal;
	var $equals;


	function Sugar_unit_test( $value )
	{
		$this->value = $value;
		$this->start = microtime( TRUE );

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
			return $this->then( $expected );	
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


	function then( $expected )
	{
		return $this->_mode( $expected );
	}


	/* Bool */

	function fail()
	{
		$this->thrown();
	}


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

	function contain( $search, $offset, $length )
	{
		if ( is_string( $this->value ) )
		{
			$result = ( substr_count( $this->value, $search, $offset, $limit ) > 0 );
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

	function length( $expected )
	{
		if (  _can_continue( ( is_string( $this->value ) && strlen( $this->value ) == $expected ) || ( is_array( $this->value ) && count( $this->value ) == $expected ) ))
		{
			return $this;
		}

		else
		{
			$this->_throw( strlen( $this->value ), 'to be', $expected );
		}
	}


	/* Array */

	function keys( $expected )
	{
		$flag = TRUE;

		foreach( $expected as $key )
		{
			if ( array_key_exists( $key, $this->value ) == FALSE )
			{
				$flag = FALSE;
				break;
			}
		}

		if ( _can_continue( $flag ) )
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

		if ( _can_continue( $flag ) )
		{
			return $this;
		}

		else
		{
			$this->_throw( $this->value, 'to have only keys', $expected );
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
			$this->_throw( $this->value, 'to thrown', 'Exception' );
		}
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


	function _mode( $expected )
	{
		if ( ( $this->mode == 'equal' && ( ( ! $this->negation && $this->value == $expected ) || ( $this->negation && ! ( $this->value == $expected )) ))
		  || ( $this->mode == 'gt' 	&& ( ( ! $this->negation && $this->value > $expected ) 	|| ( $this->negation && ! ( $this->value > $expected )) ))
		  || ( $this->mode == 'lt' 	&& ( ( ! $this->negation && $this->value < $expected ) 	|| ( $this->negation && ! ( $this->value < $expected )) ))
		  || ( $this->mode == 'gte' 	&& ( ( ! $this->negation && $this->value >= $expected ) || ( $this->negation && ! ( $this->value >= $expected )) ))
		  || ( $this->mode == 'lte' 	&& ( ( ! $this->negation && $this->value <= $expected ) || ( $this->negation && ! ( $this->value <= $expected )) )) )
		{
			return $this;
		}

		else
		{
			$this->_throw( $this->value, 'to be equal', $expected );
		}
	}

	function _throw( $expected, $sentence, $given )
	{
		throw new Exception( sprintf(
			'Failed: Excepted [%s] %s%s [%s].',
			is_array( $expected ) ? ('[' . join( ', ', array_keys( $expected )) . ']') : $expected,
			$this->negation ? 'not ' : '' ,
			$sentence,
			is_array( $given ) ? ('[' . join( ', ', array_keys( $given )) . ']') : $expected ));
	}

	function _can_continue( $value )
	{
		return ( ( ! $this->negation && $value ) || ( $this->negation && ! $value ) );
	}
}