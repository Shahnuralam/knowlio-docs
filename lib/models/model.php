<?php
/**
 * Base model: a small Active Record layer over $wpdb.
 *
 * Responsibilities:
 *  - chainable query building (`where`, `join`, `order_by`, `set_limit`, ...)
 *  - hydration of rows into typed model instances
 *  - mass-assignment whitelisting (`allowed_params`) separate from the
 *    persistence whitelist (`params_to_save`)
 *  - declarative validations (`properties_to_validate`)
 *  - lifecycle callbacks (`set_defaults`, `before_save`, `before_create`)
 *
 * Every identifier that reaches SQL is validated against a strict pattern and
 * back-quoted; every value goes through `$wpdb->prepare()`.
 *
 * A note on the phpcs:ignore comments below. All queries in this file run
 * against the plugin's own tables, which have no WordPress API to go through,
 * so WordPress.DB.DirectDatabaseQuery is suppressed by design rather than by
 * oversight. Table names are interpolated because MySQL cannot bind an
 * identifier as a placeholder; they come from constants built in
 * KnowlioDocs::define_constants() and never from a request.
 *
 * @package KnowlioDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KnowlioModel
 */
#[AllowDynamicProperties]
class KnowlioModel {

	/**
	 * WordPress database handle.
	 *
	 * @var wpdb
	 */
	protected $db;

	/**
	 * Fully qualified table name.
	 *
	 * @var string
	 */
	public $table_name = '';

	/**
	 * Human readable property names used in validation messages.
	 *
	 * @var array
	 */
	public $nice_names = array();

	/**
	 * Collected validation/persistence errors.
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Pending WHERE conditions.
	 *
	 * @var array
	 */
	protected $conditions = array();

	/**
	 * Pending JOIN clauses.
	 *
	 * @var array
	 */
	protected $joins = array();

	/**
	 * Pending SELECT columns.
	 *
	 * @var array
	 */
	protected $select_args = array();

	/**
	 * Pending ORDER BY.
	 *
	 * @var string
	 */
	protected $order_args = '';

	/**
	 * Pending GROUP BY.
	 *
	 * @var string
	 */
	protected $group_args = '';

	/**
	 * Pending LIMIT.
	 *
	 * @var int|false
	 */
	protected $limit = false;

	/**
	 * Pending OFFSET.
	 *
	 * @var int|false
	 */
	protected $offset = false;

	/**
	 * Last query executed, for debugging.
	 *
	 * @var string
	 */
	public $last_query = '';

	/**
	 * Operators accepted in a condition key.
	 *
	 * @var array
	 */
	protected static $allowed_operators = array( '=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN' );

	/**
	 * Constructor.
	 *
	 * @param int|false $id Optional record id to load.
	 */
	public function __construct( $id = false ) {
		global $wpdb;

		$this->db = $wpdb;

		if ( $id ) {
			$this->load_by_id( $id );
		}
	}

	/**
	 * Expose `get_x()` methods as a read-only `$model->x` property.
	 *
	 * @param string $property Property name.
	 *
	 * @return mixed
	 */
	public function __get( $property ) {
		$method = 'get_' . $property;

		if ( method_exists( $this, $method ) ) {
			return $this->$method();
		}

		return null;
	}

	/* --------------------------------------------------------------------- */
	/* Identifier + value safety                                             */
	/* --------------------------------------------------------------------- */

	/**
	 * Validate and back-quote a column reference (`col` or `table.col`).
	 *
	 * @param string $column Column reference.
	 *
	 * @return string|false Quoted identifier, or false when invalid.
	 */
	protected function quote_identifier( string $column ) {
		$column = trim( $column );

		if ( ! preg_match( '/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)?$/', $column ) ) {
			return false;
		}

		$parts = array_map(
			static function ( $part ) {
				return '`' . $part . '`';
			},
			explode( '.', $column )
		);

		return implode( '.', $parts );
	}

	/**
	 * Split a condition key such as `price >=` into column and operator.
	 *
	 * @param string $key Condition key.
	 *
	 * @return array|false `[column, operator]`, or false when invalid.
	 */
	protected function parse_condition_key( string $key ) {
		$key = trim( preg_replace( '/\s+/', ' ', $key ) );

		$operator = '=';
		$column   = $key;

		if ( false !== strpos( $key, ' ' ) ) {
			$parts     = explode( ' ', $key );
			$candidate = strtoupper( implode( ' ', array_slice( $parts, 1 ) ) );

			if ( in_array( $candidate, self::$allowed_operators, true ) ) {
				$operator = $candidate;
				$column   = $parts[0];
			} else {
				return false;
			}
		}

		$quoted = $this->quote_identifier( $column );

		if ( false === $quoted ) {
			return false;
		}

		return array( $quoted, $operator );
	}

	/* --------------------------------------------------------------------- */
	/* Query building                                                        */
	/* --------------------------------------------------------------------- */

	/**
	 * Add WHERE conditions.
	 *
	 * Keys may carry an operator: `['status' => 'draft', 'price >=' => 10]`.
	 *
	 * @param array $conditions Conditions.
	 *
	 * @return $this
	 */
	public function where( array $conditions ) {
		if ( ! empty( $conditions ) ) {
			$this->conditions = array_merge( $this->conditions, $conditions );
		}

		return $this;
	}

	/**
	 * Add a `column IN (...)` condition.
	 *
	 * @param string $column Column.
	 * @param array  $values Values.
	 *
	 * @return $this
	 */
	public function where_in( string $column, array $values ) {
		$this->conditions[ $column . ' IN' ] = $values;

		return $this;
	}

	/**
	 * Add a `column NOT IN (...)` condition.
	 *
	 * @param string $column Column.
	 * @param array  $values Values.
	 *
	 * @return $this
	 */
	public function where_not_in( string $column, array $values ) {
		$this->conditions[ $column . ' NOT IN' ] = $values;

		return $this;
	}

	/**
	 * Add a JOIN.
	 *
	 * @param string $table   Table to join.
	 * @param array  $on_args `joined_column => other_qualified_column`.
	 * @param string $type    `left`, `right` or ''.
	 *
	 * @return $this
	 */
	public function join( string $table, array $on_args, string $type = '' ) {
		$this->joins[] = array(
			'table' => $table,
			'on'    => $on_args,
			'type'  => in_array( strtolower( $type ), array( 'left', 'right', 'inner' ), true ) ? strtoupper( $type ) : '',
		);

		return $this;
	}

	/**
	 * Restrict the selected columns.
	 *
	 * @param string|array $select_args Columns.
	 *
	 * @return $this
	 */
	public function select( $select_args ) {
		foreach ( (array) $select_args as $arg ) {
			$this->select_args[] = $arg;
		}

		return $this;
	}

	/**
	 * Add ORDER BY. Accepts `column asc`, `column desc` or bare `column`.
	 *
	 * @param string $order_args Order clause.
	 *
	 * @return $this
	 */
	public function order_by( string $order_args ) {
		$clean = array();

		foreach ( explode( ',', $order_args ) as $piece ) {
			$piece = trim( preg_replace( '/\s+/', ' ', $piece ) );
			if ( '' === $piece ) {
				continue;
			}

			$parts     = explode( ' ', $piece );
			$column    = $this->quote_identifier( $parts[0] );
			$direction = isset( $parts[1] ) && in_array( strtoupper( $parts[1] ), array( 'ASC', 'DESC' ), true )
				? strtoupper( $parts[1] )
				: 'ASC';

			if ( false === $column ) {
				continue;
			}

			$clean[] = $column . ' ' . $direction;
		}

		if ( $clean ) {
			$this->order_args = $this->order_args
				? $this->order_args . ', ' . implode( ', ', $clean )
				: implode( ', ', $clean );
		}

		return $this;
	}

	/**
	 * Add GROUP BY.
	 *
	 * @param string $group_args Column.
	 *
	 * @return $this
	 */
	public function group_by( string $group_args ) {
		$column = $this->quote_identifier( $group_args );

		if ( false !== $column ) {
			$this->group_args = $this->group_args ? $this->group_args . ', ' . $column : $column;
		}

		return $this;
	}

	/**
	 * Set LIMIT.
	 *
	 * @param int $limit Row limit.
	 *
	 * @return $this
	 */
	public function set_limit( int $limit ) {
		$this->limit = max( 0, $limit );

		return $this;
	}

	/**
	 * Set OFFSET.
	 *
	 * @param int $offset Row offset.
	 *
	 * @return $this
	 */
	public function set_offset( int $offset ) {
		$this->offset = max( 0, $offset );

		return $this;
	}

	/**
	 * Clear all pending query state.
	 *
	 * @return $this
	 */
	public function reset_conditions() {
		$this->conditions  = array();
		$this->joins       = array();
		$this->select_args = array();
		$this->order_args  = '';
		$this->group_args  = '';
		$this->limit       = false;
		$this->offset      = false;

		return $this;
	}

	/**
	 * Build the WHERE fragment and its bind values.
	 *
	 * @return array `[sql, values]`.
	 */
	protected function build_conditions_query(): array {
		$fragments = array();
		$values    = array();

		foreach ( $this->conditions as $key => $value ) {
			$parsed = $this->parse_condition_key( (string) $key );

			if ( false === $parsed ) {
				continue; // Unknown column or operator: drop it rather than risk it.
			}

			list( $column, $operator ) = $parsed;

			if ( in_array( $operator, array( 'IN', 'NOT IN' ), true ) ) {
				$value = array_values( array_filter( (array) $value, static function ( $item ) {
					return '' !== $item && ! is_null( $item );
				} ) );

				if ( empty( $value ) ) {
					// An empty IN () is a syntax error; make the condition impossible instead.
					$fragments[] = ( 'IN' === $operator ) ? '1 = 0' : '1 = 1';
					continue;
				}

				$placeholders = implode( ', ', array_fill( 0, count( $value ), '%s' ) );
				$fragments[]  = $column . ' ' . $operator . ' (' . $placeholders . ')';
				$values       = array_merge( $values, $value );
				continue;
			}

			if ( is_null( $value ) ) {
				$fragments[] = $column . ( '=' === $operator ? ' IS NULL' : ' IS NOT NULL' );
				continue;
			}

			$fragments[] = $column . ' ' . $operator . ' %s';
			$values[]    = $value;
		}

		return array(
			$fragments ? ' WHERE ' . implode( ' AND ', $fragments ) : '',
			$values,
		);
	}

	/**
	 * Build the JOIN fragment.
	 *
	 * @return string
	 */
	protected function build_join_query(): string {
		$sql = '';

		foreach ( $this->joins as $join ) {
			$table = $this->quote_identifier( $join['table'] );

			if ( false === $table ) {
				continue;
			}

			$on = array();

			foreach ( $join['on'] as $left => $right ) {
				$left_column  = $this->quote_identifier( $join['table'] . '.' . $left );
				$right_column = $this->quote_identifier( (string) $right );

				if ( false === $left_column || false === $right_column ) {
					continue;
				}

				$on[] = $left_column . ' = ' . $right_column;
			}

			if ( empty( $on ) ) {
				continue;
			}

			$sql .= ' ' . $join['type'] . ' JOIN ' . $table . ' ON ' . implode( ' AND ', $on );
		}

		return $sql;
	}

	/**
	 * Build the SELECT column list.
	 *
	 * @return string
	 */
	protected function build_select_args_string(): string {
		if ( empty( $this->select_args ) ) {
			return '*';
		}

		$columns = array();

		foreach ( $this->select_args as $arg ) {
			$quoted = $this->quote_identifier( (string) $arg );

			if ( false !== $quoted ) {
				$columns[] = $quoted;
			}
		}

		return $columns ? implode( ', ', $columns ) : '*';
	}

	/**
	 * Prepare a query only when there are values to bind.
	 *
	 * @param string $query  SQL with placeholders.
	 * @param array  $values Bind values.
	 *
	 * @return string
	 */
	protected function prepare( string $query, array $values = array() ): string {
		if ( empty( $values ) ) {
			return $query;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL
		return $this->db->prepare( $query, $values );
	}

	/**
	 * Assemble the full SELECT statement for the pending query.
	 *
	 * @return string
	 */
	protected function build_select_query(): string {
		list( $where_sql, $where_values ) = $this->build_conditions_query();

		$sql = 'SELECT ' . $this->build_select_args_string()
			. ' FROM ' . $this->table_name
			. $this->build_join_query()
			. $where_sql;

		if ( $this->group_args ) {
			$sql .= ' GROUP BY ' . $this->group_args;
		}

		if ( $this->order_args ) {
			$sql .= ' ORDER BY ' . $this->order_args;
		}

		$query = $this->prepare( $sql, $where_values );

		if ( false !== $this->limit ) {
			// phpcs:ignore WordPress.DB.PreparedSQL
			$query .= $this->db->prepare( ' LIMIT %d', $this->limit );

			if ( false !== $this->offset ) {
				// phpcs:ignore WordPress.DB.PreparedSQL
				$query .= $this->db->prepare( ' OFFSET %d', $this->offset );
			}
		}

		return $query;
	}

	/* --------------------------------------------------------------------- */
	/* Reading                                                               */
	/* --------------------------------------------------------------------- */

	/**
	 * Run the pending query and return raw rows.
	 *
	 * @param string $output_type Output constant.
	 *
	 * @return array
	 */
	public function get_results( $output_type = ARRAY_A ): array {
		$query            = $this->build_select_query();
		$this->last_query = $query;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$rows = $this->db->get_results( $query, $output_type );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Run the pending query and hydrate rows into model instances.
	 *
	 * Always returns a list unless `$single` is explicitly requested. Inferring
	 * "single" from `set_limit(1)` would silently change the return type of any
	 * caller that legitimately asks for one row of a list -- for example the
	 * `[knowlio_articles limit="1"]` shortcode.
	 *
	 * @param bool $single Return the first model (or null) instead of a list.
	 *
	 * @return static|static[]|null
	 */
	public function get_results_as_models( bool $single = false ) {
		$rows = $this->get_results( ARRAY_A );

		$this->reset_conditions();

		$models = array();

		foreach ( $rows as $row ) {
			$model = new static();
			$model->load_from_row_data( $row );

			/**
			 * Filters each hydrated model. Returning a falsy value drops the row.
			 *
			 * @since 1.0.0
			 * @hook knowlio_get_results_as_models
			 *
			 * @param KnowlioModel $model Hydrated model.
			 */
			$model = apply_filters( 'knowlio_get_results_as_models', $model );

			if ( $model ) {
				$models[] = $model;
			}
		}

		if ( $single ) {
			return $models[0] ?? null;
		}

		return $models;
	}

	/**
	 * Run the pending query and return the first matching model.
	 *
	 * @return static|null
	 */
	public function get_first() {
		return $this->set_limit( 1 )->get_results_as_models( true );
	}

	/**
	 * Count rows matching the pending conditions.
	 *
	 * @return int
	 */
	public function count(): int {
		list( $where_sql, $where_values ) = $this->build_conditions_query();

		$sql = 'SELECT COUNT(*) FROM ' . $this->table_name . $this->build_join_query() . $where_sql;

		$query            = $this->prepare( $sql, $where_values );
		$this->last_query = $query;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$count = (int) $this->db->get_var( $query );

		$this->reset_conditions();

		return $count;
	}

	/**
	 * Populate the model from a database row.
	 *
	 * @param array|object $row_data Row.
	 *
	 * @return $this
	 */
	public function load_from_row_data( $row_data ) {
		foreach ( (array) $row_data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}

		return $this;
	}

	/**
	 * Load a record by primary key.
	 *
	 * @param int $id Record id.
	 *
	 * @return $this|false
	 */
	public function load_by_id( $id ) {
		$id = absint( $id );

		if ( ! $id ) {
			return false;
		}

		$query = $this->db->prepare( 'SELECT * FROM ' . $this->table_name . ' WHERE id = %d LIMIT 1', $id ); // phpcs:ignore WordPress.DB.PreparedSQL

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$row = $this->db->get_row( $query, ARRAY_A );

		if ( ! $row ) {
			return false;
		}

		$this->load_from_row_data( $row );

		/**
		 * Filters a model right after it is loaded by id.
		 *
		 * @since 1.0.0
		 * @hook knowlio_model_loaded_by_id
		 *
		 * @param KnowlioModel $model Loaded model.
		 */
		return apply_filters( 'knowlio_model_loaded_by_id', $this );
	}

	/**
	 * Whether the model has been persisted.
	 *
	 * @return bool
	 */
	public function is_new_record(): bool {
		return empty( $this->id );
	}

	/**
	 * Whether the model exists in the database.
	 *
	 * @return bool
	 */
	public function exists(): bool {
		return ! $this->is_new_record();
	}

	/* --------------------------------------------------------------------- */
	/* Writing                                                               */
	/* --------------------------------------------------------------------- */

	/**
	 * Mass-assign data, filtered by the role's whitelist.
	 *
	 * @param array|object $data     Incoming data.
	 * @param string       $role     Assignment role (`admin`, `public`, ...).
	 * @param bool         $sanitize Whether to run the model's sanitizers.
	 *
	 * @return $this
	 */
	public function set_data( $data, string $role = 'admin', bool $sanitize = true ) {
		$data = (array) $this->prepare_data_before_it_is_set( (array) $data );

		// An id in the payload means "edit this record": load it before assigning.
		if ( ! empty( $data['id'] ) && is_numeric( $data['id'] ) && $this->is_new_record() ) {
			$this->load_by_id( $data['id'] );
		}

		foreach ( $this->get_allowed_params( $role ) as $param ) {
			if ( ! array_key_exists( $param, $data ) ) {
				continue;
			}

			$this->$param = $sanitize ? $this->sanitize_param( $param, $data[ $param ] ) : $data[ $param ];
		}

		/**
		 * Fires after data has been mass-assigned to a model.
		 *
		 * @since 1.0.0
		 * @hook knowlio_model_set_data
		 *
		 * @param KnowlioModel $model Model.
		 * @param array   $data  Assigned data.
		 */
		do_action( 'knowlio_model_set_data', $this, $data );

		$this->after_data_was_set( $data );

		return $this;
	}

	/**
	 * Hook for subclasses: transform the payload before assignment.
	 *
	 * @param array $data Payload.
	 *
	 * @return array
	 */
	public function prepare_data_before_it_is_set( array $data ): array {
		return $data;
	}

	/**
	 * Hook for subclasses: react after assignment.
	 *
	 * @param array $data Payload.
	 */
	public function after_data_was_set( array $data ) {} // phpcs:ignore

	/**
	 * Validate then insert or update.
	 *
	 * @param bool $skip_validation Persist without validating.
	 *
	 * @return bool
	 */
	public function save( bool $skip_validation = false ): bool {
		try {
			$this->set_defaults();
			$this->before_save();

			if ( ! $skip_validation && ! $this->validate() ) {
				return false;
			}

			if ( property_exists( $this, 'updated_at' ) ) {
				$this->updated_at = KnowlioUtilHelper::now_db_datetime();
			}

			if ( $this->is_new_record() ) {
				$this->before_create();

				if ( property_exists( $this, 'created_at' ) ) {
					$this->created_at = KnowlioUtilHelper::now_db_datetime();
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$result = $this->db->insert( $this->table_name, $this->get_params_to_save_with_values() );

				if ( false === $result ) {
					$this->add_database_error( 'insert_error' );

					return false;
				}

				$this->id = $this->db->insert_id;
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$result = $this->db->update(
					$this->table_name,
					$this->get_params_to_save_with_values(),
					array( 'id' => $this->id ),
					null,
					array( '%d' )
				);

				if ( false === $result ) {
					$this->add_database_error( 'update_error' );

					return false;
				}
			}

			$this->last_query = $this->db->last_query;

			/**
			 * Fires after a model is written to the database.
			 *
			 * @since 1.0.0
			 * @hook knowlio_model_save
			 *
			 * @param KnowlioModel $model Saved model.
			 */
			do_action( 'knowlio_model_save', $this );

			return true;
		} catch ( Exception $e ) {
			$this->add_error( 'save_exception', $e->getMessage() );

			return false;
		}
	}

	/**
	 * Update a subset of columns without a full save cycle.
	 *
	 * @param array $data     Column => value.
	 * @param bool  $sanitize Whether to sanitize.
	 *
	 * @return bool
	 */
	public function update_attributes( array $data, bool $sanitize = true ): bool {
		if ( $this->is_new_record() ) {
			return false;
		}

		$prepared = array();

		foreach ( $data as $key => $value ) {
			if ( ! property_exists( $this, $key ) || ! in_array( $key, $this->get_params_to_save(), true ) ) {
				continue;
			}

			$value            = $sanitize ? $this->sanitize_param( $key, $value ) : $value;
			$this->$key       = $value;
			$prepared[ $key ] = $value;
		}

		if ( empty( $prepared ) ) {
			return false;
		}

		if ( property_exists( $this, 'updated_at' ) ) {
			$this->updated_at         = KnowlioUtilHelper::now_db_datetime();
			$prepared['updated_at'] = $this->updated_at;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $this->db->update( $this->table_name, $prepared, array( 'id' => $this->id ), null, array( '%d' ) );

		if ( false === $result ) {
			$this->add_database_error( 'update_error' );

			return false;
		}

		return true;
	}

	/**
	 * Delete a record.
	 *
	 * @param int|false $id Record id; defaults to the loaded record.
	 *
	 * @return bool
	 */
	public function delete( $id = false ): bool {
		$id = $id ? absint( $id ) : absint( $this->id ?? 0 );

		if ( ! $id ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $this->db->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );

		if ( ! $result ) {
			return false;
		}

		/**
		 * Fires after a model row is deleted.
		 *
		 * @since 1.0.0
		 * @hook knowlio_model_deleted
		 *
		 * @param KnowlioModel $model Model instance.
		 * @param int     $id    Deleted id.
		 */
		do_action( 'knowlio_model_deleted', $this, $id );

		return true;
	}

	/**
	 * Build the column => value map actually written to the table.
	 *
	 * @param string $role Persistence role.
	 *
	 * @return array
	 */
	protected function get_params_to_save_with_values( string $role = 'admin' ): array {
		$values = array();

		foreach ( $this->get_params_to_save( $role ) as $param ) {
			if ( 'id' === $param ) {
				continue; // Never write the primary key.
			}

			if ( ! property_exists( $this, $param ) ) {
				continue;
			}

			$values[ $param ] = $this->$param;
		}

		if ( property_exists( $this, 'updated_at' ) && isset( $this->updated_at ) ) {
			$values['updated_at'] = $this->updated_at;
		}

		if ( property_exists( $this, 'created_at' ) && isset( $this->created_at ) ) {
			$values['created_at'] = $this->created_at;
		}

		return $values;
	}

	/* --------------------------------------------------------------------- */
	/* Subclass configuration                                                */
	/* --------------------------------------------------------------------- */

	/**
	 * Params accepted by `set_data()` for a role.
	 *
	 * @param string $role Role.
	 *
	 * @return array
	 */
	protected function allowed_params( string $role = 'admin' ): array {
		return array();
	}

	/**
	 * Params accepted by `set_data()`, filtered.
	 *
	 * @param string $role Role.
	 *
	 * @return array
	 */
	public function get_allowed_params( string $role = 'admin' ): array {
		/**
		 * Filters the mass-assignment whitelist.
		 *
		 * @since 1.0.0
		 * @hook knowlio_model_allowed_params
		 *
		 * @param array   $params Allowed param names.
		 * @param KnowlioModel $model  Model.
		 * @param string  $role   Role.
		 */
		return (array) apply_filters( 'knowlio_model_allowed_params', $this->allowed_params( $role ), $this, $role );
	}

	/**
	 * Columns persisted by `save()` for a role.
	 *
	 * @param string $role Role.
	 *
	 * @return array
	 */
	protected function params_to_save( string $role = 'admin' ): array {
		return array();
	}

	/**
	 * Columns persisted by `save()`, filtered.
	 *
	 * @param string $role Role.
	 *
	 * @return array
	 */
	public function get_params_to_save( string $role = 'admin' ): array {
		/**
		 * Filters the persistence whitelist. Addons that add a column to a core
		 * table append it here.
		 *
		 * @since 1.0.0
		 * @hook knowlio_model_params_to_save
		 *
		 * @param array   $params Column names.
		 * @param KnowlioModel $model  Model.
		 * @param string  $role   Role.
		 */
		return (array) apply_filters( 'knowlio_model_params_to_save', $this->params_to_save( $role ), $this, $role );
	}

	/**
	 * Property => sanitization rule map.
	 *
	 * @return array
	 */
	protected function params_to_sanitize(): array {
		return array();
	}

	/**
	 * Apply the configured sanitizer for one property.
	 *
	 * @param string $param_name Property.
	 * @param mixed  $value      Raw value.
	 *
	 * @return mixed
	 */
	protected function sanitize_param( string $param_name, $value ) {
		$rules = $this->params_to_sanitize();

		if ( isset( $rules[ $param_name ] ) ) {
			return KnowlioParamsHelper::sanitize_param( $value, $rules[ $param_name ] );
		}

		return is_string( $value ) ? sanitize_text_field( $value ) : $value;
	}

	/**
	 * Lifecycle hook: set defaults before validation.
	 */
	protected function set_defaults() {} // phpcs:ignore

	/**
	 * Lifecycle hook: run before every save.
	 */
	protected function before_save() {} // phpcs:ignore

	/**
	 * Lifecycle hook: run before the first insert only.
	 */
	protected function before_create() {} // phpcs:ignore

	/* --------------------------------------------------------------------- */
	/* Validation                                                            */
	/* --------------------------------------------------------------------- */

	/**
	 * Property => list of validation names.
	 *
	 * @return array
	 */
	protected function properties_to_validate(): array {
		return array();
	}

	/**
	 * Run all declared validations.
	 *
	 * @return bool
	 */
	public function validate(): bool {
		$this->errors = array();

		foreach ( $this->properties_to_validate() as $property => $validations ) {
			foreach ( (array) $validations as $validation ) {
				$method = 'validates_' . $validation;

				if ( ! method_exists( $this, $method ) ) {
					continue;
				}

				$result = $this->$method( $property );

				if ( is_wp_error( $result ) ) {
					$this->add_error( 'validation', $result->get_error_message() );
				}
			}
		}

		/**
		 * Fires during validation so addons can add their own rules.
		 *
		 * @since 1.0.0
		 * @hook knowlio_model_validate
		 *
		 * @param KnowlioModel $model Model being validated.
		 */
		do_action( 'knowlio_model_validate', $this );

		return ! $this->has_error();
	}

	/**
	 * Validation: the property must not be empty.
	 *
	 * @param string $property Property.
	 *
	 * @return true|WP_Error
	 */
	protected function validates_presence( string $property ) {
		$value = $this->$property ?? null;

		if ( ! is_null( $value ) && '' !== $value ) {
			return true;
		}

		/* translators: %s: field name. */
		return new WP_Error( $property, sprintf( __( '%s can not be blank', 'minidocs' ), $this->get_property_nice_name( $property ) ) );
	}

	/**
	 * Validation: the property must be a valid email.
	 *
	 * @param string $property Property.
	 *
	 * @return true|WP_Error
	 */
	protected function validates_email( string $property ) {
		$value = $this->$property ?? '';

		if ( empty( $value ) || is_email( $value ) ) {
			return true;
		}

		/* translators: %s: field name. */
		return new WP_Error( $property, sprintf( __( '%s is not a valid email', 'minidocs' ), $this->get_property_nice_name( $property ) ) );
	}

	/**
	 * Validation: the property must be numeric.
	 *
	 * @param string $property Property.
	 *
	 * @return true|WP_Error
	 */
	protected function validates_numericality( string $property ) {
		$value = $this->$property ?? '';

		if ( '' === $value || is_numeric( $value ) ) {
			return true;
		}

		/* translators: %s: field name. */
		return new WP_Error( $property, sprintf( __( '%s must be a number', 'minidocs' ), $this->get_property_nice_name( $property ) ) );
	}

	/**
	 * Validation: the property must be unique in the table.
	 *
	 * @param string $property Property.
	 *
	 * @return true|WP_Error
	 */
	protected function validates_uniqueness( string $property ) {
		$value = $this->$property ?? '';

		if ( '' === $value || is_null( $value ) ) {
			return true;
		}

		$column = $this->quote_identifier( $property );

		if ( false === $column ) {
			return true;
		}

		if ( $this->is_new_record() ) {
			$query = $this->db->prepare(
				'SELECT id FROM ' . $this->table_name . ' WHERE ' . $column . ' = %s LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL
				$value
			);
		} else {
			$query = $this->db->prepare(
				'SELECT id FROM ' . $this->table_name . ' WHERE ' . $column . ' = %s AND id != %d LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL
				$value,
				$this->id
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		if ( $this->db->get_var( $query ) ) {
			/* translators: %s: field name. */
			return new WP_Error( $property, sprintf( __( '%s has to be unique', 'minidocs' ), $this->get_property_nice_name( $property ) ) );
		}

		return true;
	}

	/**
	 * Validation: the property must be one of the allowed values.
	 *
	 * @param string $property Property.
	 *
	 * @return true|WP_Error
	 */
	protected function validates_inclusion( string $property ) {
		$allowed = $this->allowed_values_for( $property );

		if ( empty( $allowed ) || in_array( (string) ( $this->$property ?? '' ), array_map( 'strval', $allowed ), true ) ) {
			return true;
		}

		/* translators: %s: field name. */
		return new WP_Error( $property, sprintf( __( '%s has an unsupported value', 'minidocs' ), $this->get_property_nice_name( $property ) ) );
	}

	/**
	 * Allowed values for an `inclusion` validation.
	 *
	 * @param string $property Property.
	 *
	 * @return array
	 */
	protected function allowed_values_for( string $property ): array {
		return array();
	}

	/**
	 * Human readable name of a property.
	 *
	 * @param string $property Property.
	 *
	 * @return string
	 */
	protected function get_property_nice_name( string $property ): string {
		return $this->nice_names[ $property ] ?? ucwords( str_replace( '_', ' ', $property ) );
	}

	/* --------------------------------------------------------------------- */
	/* Errors                                                                */
	/* --------------------------------------------------------------------- */

	/**
	 * Record a failed write.
	 *
	 * The message from $wpdb is deliberately not used: it goes straight into a
	 * response the browser sees, and it carries table and column names, so a
	 * failed write would hand out a description of the schema. The real error
	 * is left in $wpdb->last_error for a developer to read.
	 *
	 * @param string $code Error code.
	 */
	protected function add_database_error( string $code ) {
		$this->add_error( $code, __( 'The record could not be saved. Please try again.', 'minidocs' ) );
	}

	/**
	 * Record an error.
	 *
	 * @param string $code    Error code.
	 * @param string $message Message.
	 */
	public function add_error( string $code, string $message ) {
		$this->errors[] = array(
			'code'    => $code,
			'message' => $message,
		);
	}

	/**
	 * Whether any error was recorded.
	 *
	 * @return bool
	 */
	public function has_error(): bool {
		return ! empty( $this->errors );
	}

	/**
	 * All error messages.
	 *
	 * @return array
	 */
	public function get_error_messages(): array {
		return wp_list_pluck( $this->errors, 'message' );
	}

	/**
	 * All errors.
	 *
	 * @return array
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/* --------------------------------------------------------------------- */
	/* Presentation helpers                                                  */
	/* --------------------------------------------------------------------- */

	/**
	 * Formatted creation date.
	 *
	 * @param string $default Fallback.
	 *
	 * @return string
	 */
	public function formatted_created_date( string $default = 'n/a' ): string {
		return KnowlioUtilHelper::format_datetime( $this->created_at ?? null, $default );
	}

	/**
	 * Formatted update date.
	 *
	 * @param string $default Fallback.
	 *
	 * @return string
	 */
	public function formatted_updated_date( string $default = 'n/a' ): string {
		return KnowlioUtilHelper::format_datetime( $this->updated_at ?? null, $default );
	}

	/**
	 * Plain array of the model's persisted columns, for JSON responses.
	 *
	 * @return array
	 */
	public function to_array(): array {
		$data = array( 'id' => $this->id ?? null );

		foreach ( $this->get_params_to_save() as $param ) {
			if ( property_exists( $this, $param ) ) {
				$data[ $param ] = $this->$param;
			}
		}

		return $data;
	}
}
