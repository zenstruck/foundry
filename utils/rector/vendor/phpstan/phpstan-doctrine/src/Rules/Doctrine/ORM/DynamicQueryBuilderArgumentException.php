<?php declare(strict_types = 1);

namespace PHPStan\Rules\Doctrine\ORM;

use Exception;

class DynamicQueryBuilderArgumentException extends Exception
{

	/** @api */
	public function __construct()
	{
		parent::__construct();
	}

}
