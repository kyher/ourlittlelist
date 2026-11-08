<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A review action was refused. The message is the plain-language reason shown to the user (FR-021).
 */
final class ReviewActionException extends RuntimeException {}
