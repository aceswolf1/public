<?php
/**
 * Excel/CSV importer — idempotent diff-sync into the three tables.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Data;

use HR_Healthcare\Sample_System\Support\ImageMap;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/**
 * Parse → normalize → extract properties/combinations → diff-sync.
 *
 * Pre-commit returns a summary (including per-group URL-less skips) so the
 * admin UI can confirm before {@see commit()} applies writes.
 */
final class Importer {

	/**
	 * Known typo corrections applied during normalize.
	 *
	 * @var array<string, string>
	 */
	private const TYPO_MAP = array(
		'Stanadard'           => 'Standard',
		'Without Drainae Bag' => 'Without Drainage Bag',
	);

	/**
	 * Column aliases for non-prop metadata headers.
	 *
	 * @var array<string, string>
	 */
	private const META_ALIASES = array(
		'sku #'         => 'sku',
		'sku'           => 'sku',
		'product name'  => 'product_name',
		'hcpcs code'    => 'hcpcs',
		'hcpcs'         => 'hcpcs',
		'sample-amount' => 'sample_amount',
		'sample amount' => 'sample_amount',
		'sample_amount' => 'sample_amount',
		'product-line'  => 'product_line',
		'product line'  => 'product_line',
		'product_line'  => 'product_line',
		'webpage-link'  => 'webpage_link',
		'webpage link'  => 'webpage_link',
		'webpage_link'  => 'webpage_link',
	);

	/**
	 * Optional ImageMap for post-commit rebuild.
	 *
	 * @var ImageMap|null
	 */
	private ?ImageMap $image_map;

	/**
	 * Constructor.
	 *
	 * @param ImageMap|null $image_map Optional map to rebuild after commit.
	 */
	public function __construct( ?ImageMap $image_map = null ) {
		$this->image_map = $image_map;
	}

	/**
	 * Parse a spreadsheet into a pre-commit diff summary (no DB writes).
	 *
	 * @param string $path Absolute path to .xlsx / .csv.
	 * @return array{
	 *   ok: bool,
	 *   error?: string,
	 *   groups: array{added:int,updated:int,removed:int,unchanged:int},
	 *   options: array{added:int,updated:int,removed:int,unchanged:int},
	 *   skus: array{added:int,updated:int,removed:int,unchanged:int},
	 *   skipped: list<array{start_row:int,end_row:int,products:list<string>,reason:string}>,
	 *   incoming_slugs: list<string>,
	 *   plan: array<string, mixed>
	 * }
	 */
	public function preview( string $path ): array {
		try {
			$parsed = $this->parse_file( $path );
		} catch ( Throwable $e ) {
			return array(
				'ok'             => false,
				'error'          => $e->getMessage(),
				'groups'         => $this->empty_counts(),
				'options'        => $this->empty_counts(),
				'skus'           => $this->empty_counts(),
				'skipped'        => array(),
				'incoming_slugs' => array(),
				'plan'           => array(),
			);
		}

		$plan = $this->build_plan( $parsed['groups'] );

		return array(
			'ok'             => true,
			'groups'         => $plan['counts']['groups'],
			'options'        => $plan['counts']['options'],
			'skus'           => $plan['counts']['skus'],
			'skipped'        => $parsed['skipped'],
			'incoming_slugs' => array_keys( $parsed['groups'] ),
			'plan'           => $plan,
		);
	}

	/**
	 * Apply a previously computed plan (or re-parse + commit from path).
	 *
	 * @param string                    $path Absolute spreadsheet path.
	 * @param array<string, mixed>|null $plan Optional plan from {@see preview()}.
	 * @return array<string, mixed> Same shape as preview, plus `committed` bool.
	 */
	public function commit( string $path, ?array $plan = null ): array {
		if ( null === $plan ) {
			$preview = $this->preview( $path );

			if ( ! $preview['ok'] ) {
				return $preview + array( 'committed' => false );
			}

			$plan = $preview['plan'];
			$base = $preview;
		} else {
			$base = array(
				'ok'             => true,
				'groups'         => $plan['counts']['groups'] ?? $this->empty_counts(),
				'options'        => $plan['counts']['options'] ?? $this->empty_counts(),
				'skus'           => $plan['counts']['skus'] ?? $this->empty_counts(),
				'skipped'        => $plan['skipped'] ?? array(),
				'incoming_slugs' => $plan['incoming_slugs'] ?? array(),
				'plan'           => $plan,
			);
		}

		$this->apply_plan( $plan );

		if ( $this->image_map instanceof ImageMap ) {
			$this->image_map->rebuild();
		}

		$base['committed'] = true;

		return $base;
	}

	/**
	 * Parse + normalize + extract without touching the DB.
	 *
	 * @param string $path Spreadsheet path.
	 * @return array{groups: array<string, array<string, mixed>>, skipped: list<array<string, mixed>>}
	 * @throws \InvalidArgumentException When the path is unreadable.
	 * @throws \RuntimeException         When the sheet is empty or missing required columns.
	 */
	public function parse_file( string $path ): array {
		if ( ! is_readable( $path ) ) {
			throw new \InvalidArgumentException( 'Spreadsheet is not readable.' );
		}

		$spreadsheet = IOFactory::load( $path );
		$sheet       = $spreadsheet->getActiveSheet();
		$rows        = $sheet->toArray( null, true, true, false );

		if ( array() === $rows ) {
			throw new \RuntimeException( 'Spreadsheet is empty.' );
		}

		$header_row = array_shift( $rows );
		$columns    = $this->map_headers( is_array( $header_row ) ? $header_row : array() );

		if ( ! isset( $columns['meta']['webpage_link'] ) ) {
			throw new \RuntimeException( 'Missing required webpage-link column.' );
		}

		if ( ! isset( $columns['meta']['sku'] ) ) {
			throw new \RuntimeException( 'Missing required Sku # column.' );
		}

		$raw_groups = $this->segment_groups( $rows, $columns );
		$skipped    = array();
		$groups     = array();

		foreach ( $raw_groups as $raw ) {
			$slug = $this->slug_from_url( (string) $raw['url'] );

			if ( '' === $slug ) {
				$skipped[] = array(
					'start_row' => $raw['start_row'],
					'end_row'   => $raw['end_row'],
					'products'  => $raw['product_names'],
					'reason'    => 'no webpage-link',
				);
				continue;
			}

			$extracted = $this->extract_group( $raw['rows'], $columns, $slug, (string) $raw['url'] );

			if ( null === $extracted ) {
				continue;
			}

			$groups[ $slug ] = $extracted;
		}

		return array(
			'groups'  => $groups,
			'skipped' => $skipped,
		);
	}

	/**
	 * Map header cells to prop/meta/label column indexes.
	 *
	 * @param array<int, mixed> $header Header row.
	 * @return array{
	 *   props: list<array{index:int,key:string,label:string}>,
	 *   labels: array<string, int>,
	 *   meta: array<string, int>
	 * }
	 */
	private function map_headers( array $header ): array {
		$props  = array();
		$labels = array();
		$meta   = array();

		foreach ( $header as $index => $raw ) {
			$name = $this->normalize_header( (string) $raw );

			if ( '' === $name ) {
				continue;
			}

			if ( str_starts_with( $name, 'prop_' ) ) {
				$key     = substr( $name, 5 );
				$props[] = array(
					'index' => (int) $index,
					'key'   => $key,
					'label' => $this->humanize_axis( $key ),
				);
				continue;
			}

			if ( str_starts_with( $name, 'label_' ) ) {
				$labels[ substr( $name, 6 ) ] = (int) $index;
				continue;
			}

			$alias = self::META_ALIASES[ $name ] ?? null;

			if ( null !== $alias ) {
				$meta[ $alias ] = (int) $index;
			}
		}

		return array(
			'props'  => $props,
			'labels' => $labels,
			'meta'   => $meta,
		);
	}

	/**
	 * Segment rows into forward-filled URL groups (blank rows = separators).
	 *
	 * @param array<int, array<int, mixed>> $rows    Data rows (header already removed).
	 * @param array<string, mixed>          $columns Column map.
	 * @return list<array{url:string,start_row:int,end_row:int,rows:list<array<string,mixed>>,product_names:list<string>}>
	 */
	private function segment_groups( array $rows, array $columns ): array {
		$link_idx = (int) $columns['meta']['webpage_link'];
		$name_idx = $columns['meta']['product_name'] ?? null;
		$groups   = array();
		$current  = null;

		foreach ( $rows as $offset => $row ) {
			$excel_row = $offset + 2; // header is row 1.

			if ( $this->is_blank_row( $row ) ) {
				if ( null !== $current ) {
					$groups[] = $current;
					$current  = null;
				}
				continue;
			}

			$link_raw = isset( $row[ $link_idx ] ) ? trim( (string) $row[ $link_idx ] ) : '';

			if ( '' !== $link_raw ) {
				if ( null !== $current ) {
					$groups[] = $current;
				}

				$current = array(
					'url'           => $link_raw,
					'start_row'     => $excel_row,
					'end_row'       => $excel_row,
					'rows'          => array(),
					'product_names' => array(),
				);
			} elseif ( null === $current ) {
				// URL-less group start (placeholder case).
				$current = array(
					'url'           => '',
					'start_row'     => $excel_row,
					'end_row'       => $excel_row,
					'rows'          => array(),
					'product_names' => array(),
				);
			}

			$normalized_row     = $this->normalize_row( $row, $columns );
			$current['rows'][]  = $normalized_row;
			$current['end_row'] = $excel_row;

			$name = $normalized_row['product_name'] ?? '';

			if ( '' !== $name && count( $current['product_names'] ) < 5 ) {
				$current['product_names'][] = $name;
			}

			unset( $name_idx ); // reserved for future use / clarity.
		}

		if ( null !== $current ) {
			$groups[] = $current;
		}

		return $groups;
	}

	/**
	 * Normalize one data row into typed fields + prop values.
	 *
	 * @param array<int, mixed>    $row     Raw cells.
	 * @param array<string, mixed> $columns Column map.
	 * @return array<string, mixed>
	 */
	private function normalize_row( array $row, array $columns ): array {
		$out = array(
			'props'  => array(),
			'labels' => array(),
		);

		foreach ( $columns['meta'] as $key => $index ) {
			$out[ $key ] = $this->clean_cell( $row[ $index ] ?? null );
		}

		foreach ( $columns['props'] as $prop ) {
			$raw = $this->clean_cell( $row[ $prop['index'] ] ?? null );

			if ( null === $raw ) {
				$out['props'][ $prop['key'] ] = null;
				continue;
			}

			$out['props'][ $prop['key'] ] = $raw;
		}

		foreach ( $columns['labels'] as $axis => $index ) {
			$label = $this->clean_cell( $row[ $index ] ?? null );

			if ( null !== $label ) {
				$out['labels'][ $axis ] = $label;
			}
		}

		return $out;
	}

	/**
	 * Extract properties + combinations for one accepted group.
	 *
	 * @param list<array<string, mixed>> $rows    Normalized rows.
	 * @param array<string, mixed>       $columns Column map.
	 * @param string                     $slug    Group slug.
	 * @param string                     $url     Page URL.
	 * @return array<string, mixed>|null
	 */
	private function extract_group( array $rows, array $columns, string $slug, string $url ): ?array {
		if ( array() === $rows ) {
			return null;
		}

		$product_line = '';

		foreach ( $rows as $row ) {
			if ( ! empty( $row['product_line'] ) ) {
				$product_line = (string) $row['product_line'];
				break;
			}
		}

		$properties = array();
		$prop_sort  = 0;

		foreach ( $columns['props'] as $prop ) {
			$axis  = $prop['key'];
			$seen  = array();
			$order = array();

			foreach ( $rows as $row ) {
				$raw = $row['props'][ $axis ] ?? null;

				if ( null === $raw || '' === $raw ) {
					continue;
				}

				$value_key = $this->slugify_value( $raw );

				if ( isset( $seen[ $value_key ] ) ) {
					continue;
				}

				$label_override     = $row['labels'][ $axis ] ?? null;
				$seen[ $value_key ] = array(
					'key'   => $value_key,
					'label' => is_string( $label_override ) && '' !== $label_override ? $label_override : $raw,
				);
				$order[]            = $value_key;
			}

			if ( array() === $order ) {
				++$prop_sort;
				continue;
			}

			$selectable = count( $order ) > 1;
			$values     = array();

			foreach ( $order as $value_sort => $value_key ) {
				$values[] = array(
					'key'        => $seen[ $value_key ]['key'],
					'label'      => $seen[ $value_key ]['label'],
					'value_sort' => $value_sort,
				);
			}

			$properties[] = array(
				'key'        => $axis,
				'label'      => $prop['label'],
				'selectable' => $selectable,
				'prop_sort'  => $prop_sort,
				'values'     => $values,
			);

			++$prop_sort;
		}

		$selectable_axes = array();

		foreach ( $properties as $property ) {
			if ( $property['selectable'] ) {
				$selectable_axes[] = $property['key'];
			}
		}

		$combinations = array();

		foreach ( $rows as $row ) {
			$sku = isset( $row['sku'] ) ? (string) $row['sku'] : '';

			if ( '' === $sku ) {
				continue;
			}

			$options = array();

			foreach ( $selectable_axes as $axis ) {
				$raw = $row['props'][ $axis ] ?? null;

				if ( null === $raw || '' === $raw ) {
					continue 2; // incomplete selectable combo — skip row.
				}

				$options[ $axis ] = $this->slugify_value( $raw );
			}

			// Identity = combo; later duplicate rows overwrite earlier (sheet order).
			$key                  = Skus::encode_combo( $options );
			$combinations[ $key ] = array(
				'options'       => $options,
				'sku'           => $sku,
				'product_name'  => (string) ( $row['product_name'] ?? '' ),
				'hcpcs'         => (string) ( $row['hcpcs'] ?? '' ),
				'sample_amount' => (string) ( $row['sample_amount'] ?? '' ),
			);
		}

		return array(
			'slug'         => $slug,
			'page_url'     => $url,
			'product_line' => $product_line,
			'properties'   => $properties,
			'combinations' => array_values( $combinations ),
		);
	}

	/**
	 * Build an in-memory diff plan against current tables.
	 *
	 * @param array<string, array<string, mixed>> $incoming Incoming groups keyed by slug.
	 * @return array<string, mixed>
	 */
	private function build_plan( array $incoming ): array {
		$existing_groups = Groups::keyed_by_slug();

		$plan = array(
			'groups'         => array(
				'insert' => array(),
				'update' => array(),
				'delete' => array(),
			),
			'options'        => array(
				'insert' => array(),
				'update' => array(),
				'delete' => array(),
			),
			'skus'           => array(
				'insert' => array(),
				'update' => array(),
				'delete' => array(),
			),
			'counts'         => array(
				'groups'  => $this->empty_counts(),
				'options' => $this->empty_counts(),
				'skus'    => $this->empty_counts(),
			),
			'incoming_slugs' => array_keys( $incoming ),
		);

		foreach ( $incoming as $slug => $group ) {
			if ( ! isset( $existing_groups[ $slug ] ) ) {
				$plan['groups']['insert'][] = $group;
				++$plan['counts']['groups']['added'];

				foreach ( $this->flatten_options( $group ) as $option ) {
					$plan['options']['insert'][] = array(
						'slug'   => $slug,
						'option' => $option,
					);
					++$plan['counts']['options']['added'];
				}

				foreach ( $group['combinations'] as $combo ) {
					$plan['skus']['insert'][] = array(
						'slug' => $slug,
						'sku'  => $combo,
					);
					++$plan['counts']['skus']['added'];
				}

				continue;
			}

			$existing = $existing_groups[ $slug ];
			$group_id = (int) $existing['id'];
			$changed  = (string) $existing['page_url'] !== (string) $group['page_url']
				|| (string) $existing['product_line'] !== (string) $group['product_line'];

			if ( $changed ) {
				$plan['groups']['update'][] = array(
					'id'   => $group_id,
					'data' => array(
						'page_url'     => $group['page_url'],
						'product_line' => $group['product_line'],
					),
				);
				++$plan['counts']['groups']['updated'];
			} else {
				++$plan['counts']['groups']['unchanged'];
			}

			$this->diff_options( $plan, $slug, $group_id, $group );
			$this->diff_skus( $plan, $slug, $group_id, $group );
		}

		foreach ( $existing_groups as $slug => $existing ) {
			if ( isset( $incoming[ $slug ] ) ) {
				continue;
			}

			$group_id = (int) $existing['id'];

			$plan['groups']['delete'][] = $group_id;
			++$plan['counts']['groups']['removed'];

			foreach ( Options::for_group( $group_id ) as $row ) {
				$plan['options']['delete'][] = (int) $row['id'];
				++$plan['counts']['options']['removed'];
			}

			foreach ( Skus::for_group( $group_id ) as $row ) {
				$plan['skus']['delete'][] = (int) $row['id'];
				++$plan['counts']['skus']['removed'];
			}
		}

		return $plan;
	}

	/**
	 * Diff options for an existing group.
	 *
	 * @param array<string, mixed> $plan     Plan (by ref).
	 * @param string               $slug     Group slug.
	 * @param int                  $group_id Group ID.
	 * @param array<string, mixed> $group    Incoming group.
	 */
	private function diff_options( array &$plan, string $slug, int $group_id, array $group ): void {
		$existing = Options::keyed_for_group( $group_id );
		$incoming = array();

		foreach ( $this->flatten_options( $group ) as $option ) {
			$key              = Options::stable_key( $option['axis'], $option['value'] );
			$incoming[ $key ] = $option;
		}

		foreach ( $incoming as $key => $option ) {
			if ( ! isset( $existing[ $key ] ) ) {
				$plan['options']['insert'][] = array(
					'slug'     => $slug,
					'group_id' => $group_id,
					'option'   => $option,
				);
				++$plan['counts']['options']['added'];
				continue;
			}

			$row     = $existing[ $key ];
			$changed = (string) $row['label'] !== (string) $option['label']
				|| (string) $row['value_label'] !== (string) $option['value_label']
				|| (int) $row['selectable'] !== (int) $option['selectable']
				|| (int) $row['prop_sort'] !== (int) $option['prop_sort']
				|| (int) $row['value_sort'] !== (int) $option['value_sort'];

			if ( $changed ) {
				$plan['options']['update'][] = array(
					'id'   => (int) $row['id'],
					'data' => array(
						'label'       => $option['label'],
						'value_label' => $option['value_label'],
						'selectable'  => $option['selectable'],
						'prop_sort'   => $option['prop_sort'],
						'value_sort'  => $option['value_sort'],
					),
				);
				++$plan['counts']['options']['updated'];
			} else {
				++$plan['counts']['options']['unchanged'];
			}
		}

		foreach ( $existing as $key => $row ) {
			if ( isset( $incoming[ $key ] ) ) {
				continue;
			}

			$plan['options']['delete'][] = (int) $row['id'];
			++$plan['counts']['options']['removed'];
		}
	}

	/**
	 * Diff SKUs for an existing group.
	 *
	 * @param array<string, mixed> $plan     Plan (by ref).
	 * @param string               $slug     Group slug.
	 * @param int                  $group_id Group ID.
	 * @param array<string, mixed> $group    Incoming group.
	 */
	private function diff_skus( array &$plan, string $slug, int $group_id, array $group ): void {
		// Collapse DB duplicates (same combo_json) — keep first, queue extras for delete.
		$existing = array();

		foreach ( Skus::for_group( $group_id ) as $row ) {
			$key = Skus::encode_combo( Skus::decode_combo( (string) $row['combo_json'] ) );

			if ( isset( $existing[ $key ] ) ) {
				$plan['skus']['delete'][] = (int) $row['id'];
				++$plan['counts']['skus']['removed'];
				continue;
			}

			$existing[ $key ] = $row;
		}

		$incoming = array();

		foreach ( $group['combinations'] as $combo ) {
			$key              = Skus::encode_combo( $combo['options'] );
			$incoming[ $key ] = $combo;
		}

		foreach ( $incoming as $key => $combo ) {
			if ( ! isset( $existing[ $key ] ) ) {
				$plan['skus']['insert'][] = array(
					'slug'     => $slug,
					'group_id' => $group_id,
					'sku'      => $combo,
				);
				++$plan['counts']['skus']['added'];
				continue;
			}

			$row     = $existing[ $key ];
			$changed = (string) $row['sku'] !== (string) $combo['sku']
				|| (string) $row['product_name'] !== (string) $combo['product_name']
				|| (string) $row['hcpcs'] !== (string) $combo['hcpcs']
				|| (string) $row['sample_amount'] !== (string) $combo['sample_amount'];

			if ( $changed ) {
				$plan['skus']['update'][] = array(
					'id'   => (int) $row['id'],
					'data' => array(
						'sku'           => $combo['sku'],
						'product_name'  => $combo['product_name'],
						'hcpcs'         => $combo['hcpcs'],
						'sample_amount' => $combo['sample_amount'],
					),
				);
				++$plan['counts']['skus']['updated'];
			} else {
				++$plan['counts']['skus']['unchanged'];
			}
		}

		foreach ( $existing as $key => $row ) {
			if ( isset( $incoming[ $key ] ) ) {
				continue;
			}

			$plan['skus']['delete'][] = (int) $row['id'];
			++$plan['counts']['skus']['removed'];
		}
	}

	/**
	 * Apply a plan to the database.
	 *
	 * @param array<string, mixed> $plan Diff plan.
	 */
	private function apply_plan( array $plan ): void {
		$slug_to_id = array();

		foreach ( Groups::keyed_by_slug() as $slug => $row ) {
			$slug_to_id[ $slug ] = (int) $row['id'];
		}

		foreach ( $plan['groups']['insert'] as $group ) {
			$id                           = Groups::insert(
				array(
					'slug'         => $group['slug'],
					'page_url'     => $group['page_url'],
					'product_line' => $group['product_line'],
				)
			);
			$slug_to_id[ $group['slug'] ] = $id;

			foreach ( $this->flatten_options( $group ) as $option ) {
				Options::insert(
					array(
						'group_id'    => $id,
						'axis'        => $option['axis'],
						'label'       => $option['label'],
						'value'       => $option['value'],
						'value_label' => $option['value_label'],
						'selectable'  => $option['selectable'],
						'prop_sort'   => $option['prop_sort'],
						'value_sort'  => $option['value_sort'],
					)
				);
			}

			foreach ( $group['combinations'] as $combo ) {
				Skus::insert(
					array(
						'group_id'      => $id,
						'sku'           => $combo['sku'],
						'combo_json'    => $combo['options'],
						'product_name'  => $combo['product_name'],
						'hcpcs'         => $combo['hcpcs'],
						'sample_amount' => $combo['sample_amount'],
					)
				);
			}
		}

		foreach ( $plan['groups']['update'] as $item ) {
			Groups::update( (int) $item['id'], $item['data'] );
		}

		foreach ( $plan['options']['insert'] as $item ) {
			// New-group options already inserted above; only handle existing-group inserts.
			if ( ! isset( $item['group_id'] ) ) {
				continue;
			}

			$option = $item['option'];
			Options::insert(
				array(
					'group_id'    => (int) $item['group_id'],
					'axis'        => $option['axis'],
					'label'       => $option['label'],
					'value'       => $option['value'],
					'value_label' => $option['value_label'],
					'selectable'  => $option['selectable'],
					'prop_sort'   => $option['prop_sort'],
					'value_sort'  => $option['value_sort'],
				)
			);
		}

		foreach ( $plan['options']['update'] as $item ) {
			Options::update( (int) $item['id'], $item['data'] );
		}

		foreach ( $plan['options']['delete'] as $id ) {
			Options::delete( (int) $id );
		}

		foreach ( $plan['skus']['insert'] as $item ) {
			if ( ! isset( $item['group_id'] ) ) {
				continue;
			}

			$combo = $item['sku'];
			Skus::insert(
				array(
					'group_id'      => (int) $item['group_id'],
					'sku'           => $combo['sku'],
					'combo_json'    => $combo['options'],
					'product_name'  => $combo['product_name'],
					'hcpcs'         => $combo['hcpcs'],
					'sample_amount' => $combo['sample_amount'],
				)
			);
		}

		foreach ( $plan['skus']['update'] as $item ) {
			Skus::update( (int) $item['id'], $item['data'] );
		}

		foreach ( $plan['skus']['delete'] as $id ) {
			Skus::delete( (int) $id );
		}

		foreach ( $plan['groups']['delete'] as $id ) {
			Options::delete_for_group( (int) $id );
			Skus::delete_for_group( (int) $id );
			Groups::delete( (int) $id );
		}

		unset( $slug_to_id );
	}

	/**
	 * Flatten group properties into option rows.
	 *
	 * @param array<string, mixed> $group Incoming group.
	 * @return list<array<string, mixed>>
	 */
	private function flatten_options( array $group ): array {
		$out = array();

		foreach ( $group['properties'] as $property ) {
			foreach ( $property['values'] as $value ) {
				$out[] = array(
					'axis'        => $property['key'],
					'label'       => $property['label'],
					'value'       => $value['key'],
					'value_label' => $value['label'],
					'selectable'  => $property['selectable'] ? 1 : 0,
					'prop_sort'   => (int) $property['prop_sort'],
					'value_sort'  => (int) $value['value_sort'],
				);
			}
		}

		return $out;
	}

	/**
	 * Empty added/updated/removed/unchanged counters.
	 *
	 * @return array{added:int,updated:int,removed:int,unchanged:int}
	 */
	private function empty_counts(): array {
		return array(
			'added'     => 0,
			'updated'   => 0,
			'removed'   => 0,
			'unchanged' => 0,
		);
	}

	/**
	 * Whether a raw spreadsheet row is entirely blank.
	 *
	 * @param array<int, mixed> $row Cells.
	 */
	private function is_blank_row( array $row ): bool {
		foreach ( $row as $cell ) {
			if ( null !== $cell && '' !== trim( (string) $cell ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Trim, collapse whitespace, apply typo map; treat `-` / blank as null.
	 *
	 * @param mixed $value Raw cell.
	 */
	private function clean_cell( mixed $value ): ?string {
		if ( null === $value ) {
			return null;
		}

		$string = trim( (string) $value );
		$string = preg_replace( '/\s+/u', ' ', $string ) ?? $string;

		if ( '' === $string || '-' === $string ) {
			return null;
		}

		foreach ( self::TYPO_MAP as $from => $to ) {
			$string = str_replace( $from, $to, $string );
		}

		return $string;
	}

	/**
	 * Normalize a header to a comparable key.
	 *
	 * @param string $header Raw header.
	 */
	private function normalize_header( string $header ): string {
		$header = strtolower( trim( $header ) );
		$header = preg_replace( '/\s+/u', ' ', $header ) ?? $header;

		return $header;
	}

	/**
	 * Derive a URL slug from a webpage-link cell.
	 *
	 * @param string $url Raw URL (or placeholder text).
	 */
	private function slug_from_url( string $url ): string {
		$url = trim( $url );

		if ( '' === $url ) {
			return '';
		}

		// Explicit non-URL placeholders (e.g. "Page still needs to be made").
		if ( ! preg_match( '#^https?://#i', $url ) ) {
			return '';
		}

		$path = wp_parse_url( $url, PHP_URL_PATH );

		if ( ! is_string( $path ) || '' === $path || '/' === $path ) {
			return '';
		}

		$slug = sanitize_title( basename( untrailingslashit( $path ) ) );

		return $slug;
	}

	/**
	 * Slugify a property value into a stable machine key.
	 *
	 * @param string $raw Display/raw value.
	 */
	private function slugify_value( string $raw ): string {
		$slug = sanitize_title( $raw );

		if ( '' !== $slug ) {
			return $slug;
		}

		// sanitize_title can empty strings that are only symbols; fall back.
		$fallback = strtolower( preg_replace( '/[^a-zA-Z0-9]+/', '_', $raw ) ?? '' );
		$fallback = trim( $fallback, '_' );

		return '' !== $fallback ? $fallback : 'value';
	}

	/**
	 * Human label from a prop_ axis key.
	 *
	 * @param string $axis Machine axis (e.g. dimension_length).
	 */
	private function humanize_axis( string $axis ): string {
		$label = str_replace( '_', ' ', $axis );
		$label = ucwords( $label );

		return $label;
	}
}
