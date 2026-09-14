<?php
/**
 * Activation hook — schema create + version stamp.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System;

use HR_Healthcare\Sample_System\Data\Schema;

/**
 * Runs on `register_activation_hook`. Idempotent: safe to re-activate.
 */
final class Activation {

	/**
	 * Create tables and stamp the schema version.
	 */
	public static function activate(): void {
		Schema::create();

		// Ensure rewrite/capability state is fresh after activation.
		flush_rewrite_rules( false );
	}
}
