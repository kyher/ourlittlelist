<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A couple action was refused. The message is the plain-language reason shown to the user (FR-020).
 */
final class CoupleActionException extends RuntimeException {}
