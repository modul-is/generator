<?php

namespace ModulIS\Generator\Exception;

/**
 * The exception that is thrown when CMD command is missing table argument
 */
class MissingTableArgumentException extends \Exception
{}

/**
 * The exception that is thrown when more than 4 arguments are passed from CMD
 */
class OverlimitArgumentException extends \Exception
{}

/**
 * The exception that is thrown when table passed in CMD does not exist in DB
 */
class MissingTableException extends \Exception
{}

/**
 * The exception that is thrown when wrong type is provided
 */
class WrongTypeException extends \Exception
{}

/**
 * The exception that is thrown when presenter is needed but does not exist
 */
class MissingPresenterException extends \Exception
{}