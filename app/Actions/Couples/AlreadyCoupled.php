<?php

namespace App\Actions\Couples;

use LogicException;

/**
 * @internal Thrown inside AcceptCoupleRequest's transaction to roll it back when either user
 * is no longer single.
 */
final class AlreadyCoupled extends LogicException {}
