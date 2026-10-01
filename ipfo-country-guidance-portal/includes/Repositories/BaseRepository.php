<?php
declare( strict_types = 1 );

namespace IPFO\Repositories;

use IPFO\Database\Schema;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class BaseRepository {

	protected wpdb $db;
	protected string $table;

	public function __construct() {
		global $wpdb;
		$this->db    = $wpdb;
		$this->table = Schema::table( $this->table_name() );
	}

	abstract protected function table_name(): string;

	protected function now(): string {
		return current_time( 'mysql' );
	}

	/**
	 * Hash an IP address for privacy-conscious logging (never store raw IPs).
	 */
	public static function hash_ip( string $ip ): string {
		return hash( 'sha256', $ip . wp_salt( 'auth' ) );
	}
}
