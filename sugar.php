<?php

# Sugar.php
# Author: Tomáš Bončo
# Licence: MIT
# GitHub: https://github.com/tomasbonco/sugar_php

# Ver: 0.1
# Date: 28.6.2014


function describe( $desc, $callback )
{
	global $before_each, $after_each;

	$before_each = function(){};
	$after_each = function(){};

	printf( '<div class="describe"> <h2> %s </h2> <div class="its">', $desc );

	$callback();

	printf( '</div> </div>');

	$before_each = function(){};
	$after_each = function(){};
}

function it( $desc, $callback )
{
	global $before_each, $after_each;

	$before_each();

	$status = 'OK';
	$error = NULL;

	try
	{
		$callback();
	}

	catch ( Exception $e)
	{
		$status = 'FAIL';
		$stack = $e->getTrace();

		$stack_string = '';
		array_walk( $stack, function( $item ) use( &$stack_string )
		{
			if ( $item['file'] != __FILE__ )
			$stack_string .= sprintf( "%s at %s:%s\n<br>", $item['function'], $item['file'], $item['line'] );
		});

		$error = sprintf( "<p class='error'> <div class='error message'><strong>%s</strong></div> <div class='trace'>%s</div> </p>\n", $e->getMessage(), $stack_string);
	}

	printf( "<div class='it'><span class='test_name'>%s</span> ................... <span class='test_result %s'>%s</span> \n<br> %s </div>", $desc, strtolower( $status ), $status, $error );

	$after_each();
}

function a( $value )
{
	return new Unit_test( $value );
}

function before_each( $callback )
{
	global $before_each;
	$before_each = $callback;
}

function after_each( $callback )
{
	global $after_each;
	$after_each = $callback;
}

class Unit_test
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


	function Unit_test( $value )
	{
		$this->value = $value;

		$this->should =
		$this->be =
		$this->to =
		$this->equal =
		$this->equals =
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
			$this->value = $e->message;
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


	/* comparators */

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


	function blank()
	{
		if ( ( ! $this->negation && empty( $this->value ) ) || ( $this->negation && ! empty( $this->value ) ) )
		{
			return $this;
		}

		else
		{
			throw new Exception( sprintf( 'Failed: Expected empty, [%s] given.', $this->value ) );
		}
	}


	function not_blank()
	{
		$this->not();
		$this->empty();
	}


	function then( $expected )
	{
		return $this->_mode( $expected );
	}


	function thrown()
	{
		if ( ( ! $this->negation && $this->throw ) || ( $this->negation && ! $this->throw ))
		{
			return $this;
		}

		else
		{
			throw new Exception( sprintf( 'Failed: Exception should be thrown, instead it has value [%s].', $this->value ) );
		}
	}


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

	function exactly( $expected )
	{
		if ( $this->mode == 'equal' && ( ( ! $this->negation && $this->value === $expected ) || ( $this->negation && ! ( $this->value === $expected )) ) )
		{
			return $this;
		}

		else
		{
			throw new Exception( sprintf( 'Failed: Expected [%s] not to be [%s].', $this->value, $expected ) );
		}
	}


	/* Special */

	function also()
	{
		$this->mode = '';
		$this->negation = FALSE;

		return $this;
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
			if ( $this->negation )
			{
				throw new Exception( sprintf( 'Failed: Expected [%s] not to be [%s].', $this->value, $expected ) );
			}

			else
			{
				throw new Exception( sprintf( 'Failed: Expected [%s] to be [%s].', $this->value, $expected ) ); 
			}
		}
	}
}