<?php
use Doubleedesign\BasePlugin\CPTIndexHandler;
use function Spies\{stub_function, get_spy_for, match_array, match_pattern, any, expect_spy};

describe('CPT Index custom post type', function() {
	beforeEach(function() {
		do_action('init');

		// Mock functions that are called within the class methods so we don't get "undefined function" errors.
		// These can be overridden with spies/return values in individual tests as needed.
		stub_function('get_posts');
		stub_function('wp_insert_post');
		stub_function('get_post_types');
		stub_function('get_post_meta');
	});

	describe('post type registration', function() {

		it('is registered on WP init', function() {
			$spy = get_spy_for('register_post_type');
			new CPTIndexHandler();

			do_action('init');

			expect($spy)->was_called_with('cpt_index', any())->toBeTrue();
		});

		it('should not be available directly on the front-end by default', function() {
			$spy = get_spy_for('register_post_type');
			$instance = new CPTIndexHandler();
			$instance->register_cpt_index_type();

			expect($spy)->was_called_with('cpt_index', match_array([
				'publicly_queryable' => false,
			]))->toBeTrue();
		});
	});

	describe('indexable post types', function() {
		it('fetches publicly queryable post types by default', function() {
			$spy = get_spy_for('get_post_types');
			$instance = new CPTIndexHandler();
			$instance->get_indexable_post_types();

			expect($spy)->was_called_with(match_array(['publicly_queryable' => true]), any())->toBeTrue();
		});

		it('returns post type objects for indexable CPTs', function() {
			$mock_post_type = (object)[
				'name'               => 'book',
				'labels'             => (object)['archives' => 'Books'],
				'publicly_queryable' => true,
			];
			stub_function('get_post_types')->when_called->will_return(['book' => $mock_post_type]);

			$instance = new CPTIndexHandler();
			$result = $instance->get_indexable_post_types();

			expect($result)->toEqual(['book' => $mock_post_type]);
		});

		it('returns an empty array if no post types are indexable', function() {
			stub_function('get_post_types')->when_called->will_return([]);

			$instance = new CPTIndexHandler();
			$result = $instance->get_indexable_post_types();

			expect($result)->toEqual([]);
		});

		describe('theme/plugin filtering of indexable post types', function() {

			it('filters out a specified post type', function() {
				$mock_post_types = array(
					'book'  => [
						'name'               => 'book',
						'labels'             => (object)['archives' => 'Books'],
						'publicly_queryable' => true,
					],
					'movie' => [
						'name'               => 'movie',
						'labels'             => (object)['archives' => 'Movies'],
						'publicly_queryable' => true,
					]
				);
				stub_function('get_post_types')->when_called->will_return($mock_post_types);

				add_filter('doublee_indexable_custom_post_types', function($include) {
					return array_filter($include, fn($cpt) => $cpt !== 'movie');
				});

				$instance = new CPTIndexHandler();
				$result = $instance->get_indexable_post_types();

				expect($result)->toEqual(['book' => $mock_post_types['book']]);
			});

			it('adds a specified post type', function() {
				$mock_post_types_initial = array(
					'book'  => [
						'name'               => 'book',
						'labels'             => (object)['archives' => 'Books'],
						'publicly_queryable' => true,
					],
					'movie' => [
						'name'               => 'movie',
						'labels'             => (object)['archives' => 'Movies'],
						'publicly_queryable' => true,
					]
				);
				stub_function('get_post_types')->when_called->will_return($mock_post_types_initial);

				$mock_post_type_additional = (object)[
					'name'               => 'album',
					'labels'             => (object)['archives' => 'Albums'],
					'publicly_queryable' => true,
				];

				stub_function('get_post_type_object')->when_called->with('album')->will_return($mock_post_type_additional);

				add_filter('doublee_indexable_custom_post_types', function($include) {
					return array_merge($include, ['album']);
				});

				$instance = new CPTIndexHandler();
				$result = $instance->get_indexable_post_types();

				expect($result)->toEqual([
					'book'  => $mock_post_types_initial['book'],
					'movie' => $mock_post_types_initial['movie'],
					'album' => $mock_post_type_additional,
				]);
			});

			it('logs a warning if a provided post type does not exist', function() {
				$logSpy = get_spy_for('error_log');

				$mock_post_types_initial = array(
					'book' => [
						'name'               => 'book',
						'labels'             => (object)['archives' => 'Books'],
						'publicly_queryable' => true,
					]
				);
				stub_function('get_post_types')->when_called->will_return($mock_post_types_initial);

				stub_function('get_post_type_object')->when_called->with('nonexistent_cpt')->will_return(null);
				add_filter('doublee_indexable_custom_post_types', function($include) {
					return array_merge($include, ['nonexistent_cpt']);
				});

				$instance = new CPTIndexHandler();
				$instance->get_indexable_post_types();

				// This format provides more useful failure feedback than expect($spy)->was_called_with(...)->toBeTrue()
				expect_spy($logSpy)->to_have_been_called()
					->with(match_pattern("/post type 'nonexistent_cpt' does not exist when trying to include it as indexable/"))
					->verify();

				// ...but we also need another assertion or else Pest/Spies/whatever will say this test has no assertions
				expect(true)->toBeTrue();
			});

			it('continues and returns the expected array if a provided post type does not exist', function() {
				$mock_post_types_initial = array(
					'book' => [
						'name'               => 'book',
						'labels'             => (object)['archives' => 'Books'],
						'publicly_queryable' => true,
					]
				);
				stub_function('get_post_types')->when_called->will_return($mock_post_types_initial);

				stub_function('get_post_type_object')->when_called->with('nonexistent_cpt')->will_return(null);
				add_filter('doublee_indexable_custom_post_types', function($include) {
					return array_merge($include, ['nonexistent_cpt']);
				});

				$instance = new CPTIndexHandler();
				$result = $instance->get_indexable_post_types();

				expect($result)->toEqual([
					'book' => $mock_post_types_initial['book'],
				]);
			});

			it('handles adding multiple post types', function() {
				$mock_post_types_initial = array(
					'book' => [
						'name'               => 'book',
						'labels'             => (object)['archives' => 'Books'],
						'publicly_queryable' => true,
					]
				);
				stub_function('get_post_types')->when_called->will_return($mock_post_types_initial);

				$mock_post_types_custom = [
					'album' => (object)[
						'name'               => 'album',
						'labels'             => (object)['archives' => 'Albums'],
						'publicly_queryable' => true,
					],
					'movie' => (object)[
						'name'               => 'movie',
						'labels'             => (object)['archives' => 'Movies'],
						'publicly_queryable' => true,
					],
				];
				stub_function('get_post_type_object')->when_called->with('album')->will_return($mock_post_types_custom['album']);
				stub_function('get_post_type_object')->when_called->with('movie')->will_return($mock_post_types_custom['movie']);

				add_filter('doublee_indexable_custom_post_types', function($include) {
					return array_merge($include, ['album', 'movie']);
				});

				$instance = new CPTIndexHandler();
				$result = $instance->get_indexable_post_types();
				expect($result)->toEqual([
					'book'  => $mock_post_types_initial['book'],
					'album' => $mock_post_types_custom['album'],
					'movie' => $mock_post_types_custom['movie'],
				]);
			});

			it('handles both adding and removing a post type', function() {
				$mock_post_types_initial = array(
					'book'  => [
						'name'               => 'book',
						'labels'             => (object)['archives' => 'Books'],
						'publicly_queryable' => true,
					],
					'movie' => [
						'name'               => 'movie',
						'labels'             => (object)['archives' => 'Movies'],
						'publicly_queryable' => true,
					]
				);
				stub_function('get_post_types')->when_called->will_return($mock_post_types_initial);

				$mock_post_type_additional = (object)[
					'name'               => 'album',
					'labels'             => (object)['archives' => 'Albums'],
					'publicly_queryable' => true,
				];

				stub_function('get_post_type_object')->when_called->with('album')->will_return($mock_post_type_additional);
				add_filter('doublee_indexable_custom_post_types', function($include) {
					$include = array_filter($include, fn($cpt) => $cpt !== 'movie');
					return array_merge($include, ['album']);
				});

				$instance = new CPTIndexHandler();

				$result = $instance->get_indexable_post_types();
				expect($result)->toEqual([
					'book'  => $mock_post_types_initial['book'],
					'album' => $mock_post_type_additional,
				]);
			});

		});
	});

	describe('index creation', function() {
		it('creates an index for an indexable CPT', function() {
			$spy = get_spy_for('wp_insert_post');

			$mock_post_types = array(
				'book' => (object)[
					'name'               => 'Books',
					'labels'             => (object)['archives' => 'Books'],
					'publicly_queryable' => true,
				]
			);

			stub_function('get_post_types')->when_called->will_return($mock_post_types);
			stub_function('get_posts')->when_called->will_return([]); // No existing indexes

			$instance = new CPTIndexHandler();
			$instance->create_cpt_indexes();

			// NOTE: Spies has a bad time if we try to assert on more than one call using the below array expectation,
			// so let's just check one here. We can assert on multiple calls in another test using $spy->get_called_functions().
			expect_spy($spy)->to_have_been_called()
				->with(match_array([
					'post_title'  => 'Books',
					'post_name'   => 'books',
					'post_type'   => 'cpt_index',
					'post_status' => 'publish',
					'meta_input'  => array(
						'indexed_post_type' => 'Books',
					),
				]))->verify();

			expect($spy)->was_called_times(1)->toBeTrue();
		});

		it('creates indexes for multiple indexable CPTs', function() {
			$spy = get_spy_for('wp_insert_post');

			$mock_post_types = array(
				'book'  => (object)[
					'name'               => 'Books',
					'labels'             => (object)['archives' => 'Books'],
					'publicly_queryable' => true,
				],
				'movie' => (object)[
					'name'               => 'Movies',
					'labels'             => (object)['archives' => 'Movies'],
					'publicly_queryable' => true,
				]
			);

			stub_function('get_post_types')->when_called->will_return($mock_post_types);
			stub_function('get_posts')->when_called->will_return([]); // No existing indexes

			$instance = new CPTIndexHandler();
			$instance->create_cpt_indexes();

			expect($spy)->was_called_times(2)->toBeTrue();

			$calls = $spy->get_called_functions();
			expect($calls[0]->get_args()[0])->toEqual([
				'post_title'  => 'Books',
				'post_name'   => 'books',
				'post_type'   => 'cpt_index',
				'post_status' => 'publish',
				'meta_input'  => array(
					'indexed_post_type' => 'Books',
				),
			]);
			expect($calls[1]->get_args()[0])->toEqual([
				'post_title'  => 'Movies',
				'post_name'   => 'movies',
				'post_type'   => 'cpt_index',
				'post_status' => 'publish',
				'meta_input'  => array(
					'indexed_post_type' => 'Movies',
				),
			]);
		});

		it('does not create an index if one already exists', function() {
			$spy = get_spy_for('wp_insert_post');

			$mock_post_types = array(
				'book' => (object)[
					'name'               => 'Books',
					'labels'             => (object)['archives' => 'Books'],
					'publicly_queryable' => true,
				]
			);

			stub_function('get_post_types')->when_called->will_return($mock_post_types);

			// Existing index
			stub_function('get_posts')->when_called->will_return([
				(object)['ID' => 123, 'post_type' => 'cpt_index', 'post_name' => 'books']
			]);
			stub_function('get_post_meta')->when_called->with(123, 'indexed_post_type', true)->will_return('book');

			$instance = new CPTIndexHandler();
			$instance->create_cpt_indexes();

			expect($spy)->was_called()->toBeFalse();
		});

		it('handles one pre-existing and one not', function() {
			$spy = get_spy_for('wp_insert_post');

			$mock_post_types = array(
				'book'  => (object)[
					'name'               => 'Books',
					'labels'             => (object)['archives' => 'Books'],
					'publicly_queryable' => true,
				],
				'movie' => (object)[
					'name'               => 'Musicals',
					'labels'             => (object)['archives' => 'Musicals'],
					'publicly_queryable' => true,
				]
			);

			stub_function('get_post_types')->when_called->will_return($mock_post_types);

			// Existing index for 'book' only
			stub_function('get_posts')->when_called->will_return([
				(object)['ID' => 123, 'post_type' => 'cpt_index', 'post_name' => 'books']
			]);
			stub_function('get_post_meta')->when_called->with(123, 'indexed_post_type', true)->will_return('book');

			$instance = new CPTIndexHandler();
			$instance->create_cpt_indexes();

			$calls = $spy->get_called_functions();
			expect($spy)->was_called_times(1)->toBeTrue();
			expect($calls[0]->get_args()[0]['post_name'])->toEqual('musicals');
		});

		it('ignores the index post title being different to anything about the CPT', function() {
			$spy = get_spy_for('wp_insert_post');

			$mock_post_types = array(
				'book' => (object)[
					'name'               => 'Books',
					'labels'             => (object)['archives' => 'Books'],
					'publicly_queryable' => true,
				]
			);

			stub_function('get_post_types')->when_called->will_return($mock_post_types);
			stub_function('get_posts')->when_called->will_return([
				(object)['ID' => 123, 'post_type' => 'cpt_index', 'post_name' => 'books', 'post_title' => 'My Book Index']
			]);
			stub_function('get_post_meta')->when_called->with(123, 'indexed_post_type', true)->will_return('book');

			$instance = new CPTIndexHandler();
			$instance->delete_cpt_indexes();

			// Should not insert a new index
			expect($spy)->was_called()->toBeFalse();
		});
	});

	describe('index deletion', function() {
		it('deletes an index for a CPT specified as non-indexable', function() {
			$spy = get_spy_for('wp_delete_post');

			$mock_post_types = array(
				'book' => (object)[
					'name'               => 'Books',
					'labels'             => (object)['archives' => 'Books'],
					'publicly_queryable' => true,
				]
			);

			stub_function('get_post_types')->when_called->will_return($mock_post_types);
			stub_function('get_posts')->when_called->will_return([
				(object)['ID' => 123, 'post_type' => 'cpt_index', 'post_name' => 'books']
			]);
			stub_function('get_post_meta')->when_called->with(123, 'indexed_post_type', true)->will_return('book');

			add_filter('doublee_indexable_custom_post_types', function($include) {
				return array_filter($include, fn($cpt) => $cpt !== 'book');
			});

			$instance = new CPTIndexHandler();
			$instance->delete_cpt_indexes();

			expect($spy)->was_called_with(123, true)->toBeTrue();
		});

		it('does not attempt to delete an index if the CPT is indexable', function() {
			$spy = get_spy_for('wp_delete_post');

			$mock_post_types = array(
				'book' => (object)[
					'name'               => 'Books',
					'labels'             => (object)['archives' => 'Books'],
					'publicly_queryable' => true,
				]
			);

			stub_function('get_post_types')->when_called->will_return($mock_post_types);
			stub_function('get_posts')->when_called->will_return([
				(object)['ID' => 123, 'post_type' => 'cpt_index', 'post_name' => 'books']
			]);
			stub_function('get_post_meta')->when_called->with(123, 'indexed_post_type', true)->will_return('book');

			$instance = new CPTIndexHandler();
			$instance->delete_cpt_indexes();

			expect($spy)->was_called()->toBeFalse();
		});

		it('only deletes the unwanted one if one is indexable and one is not', function() {
			$spy = get_spy_for('wp_delete_post');

			$mock_post_types = array(
				'book'  => (object)[
					'name'               => 'Books',
					'labels'             => (object)['archives' => 'Books'],
					'publicly_queryable' => true,
				],
				'movie' => (object)[
					'name'               => 'Movies',
					'labels'             => (object)['archives' => 'Movies'],
					'publicly_queryable' => true,
				]
			);

			stub_function('get_post_types')->when_called->will_return($mock_post_types);
			stub_function('get_posts')->when_called->will_return([
				(object)['ID' => 123, 'post_type' => 'cpt_index', 'post_name' => 'books'],
				(object)['ID' => 456, 'post_type' => 'cpt_index', 'post_name' => 'movies'],
			]);
			stub_function('get_post_meta')->when_called->with(123, 'indexed_post_type', true)->will_return('book');
			stub_function('get_post_meta')->when_called->with(456, 'indexed_post_type', true)->will_return('movie');

			add_filter('doublee_indexable_custom_post_types', function($include) {
				return array_filter($include, fn($cpt) => $cpt !== 'movie');
			});

			$instance = new CPTIndexHandler();
			$instance->delete_cpt_indexes();

			expect($spy)->was_called_times(1)->toBeTrue();
			expect($spy)->was_called_with(456, true)->toBeTrue();
		});

		it('deletes an index if the CPT it was for no longer exists', function() {
			$spy = get_spy_for('wp_delete_post');

			stub_function('get_post_types')->when_called->will_return([]);
			stub_function('get_posts')->when_called->will_return([
				(object)['ID' => 123, 'post_type' => 'cpt_index', 'post_name' => 'movies']
			]);
			stub_function('get_post_meta')->when_called->with(123, 'indexed_post_type', true)->will_return('movie');

			$instance = new CPTIndexHandler();
			$instance->delete_cpt_indexes();

			expect($spy)->was_called_with(123, true)->toBeTrue();
		});

		it('ignores the index post title being different to anything about the CPT', function() {
			$spy = get_spy_for('wp_delete_post');

			stub_function('get_post_types')->when_called->will_return([]); // CPT doesn't exist anymore
			stub_function('get_posts')->when_called->will_return([
				(object)['ID' => 123, 'post_type' => 'cpt_index', 'post_name' => 'books', 'post_title' => 'My Book Index']
			]);
			stub_function('get_post_meta')->when_called->with(123, 'indexed_post_type', true)->will_return('book');

			$instance = new CPTIndexHandler();
			$instance->delete_cpt_indexes();

			expect($spy)->was_called()->toBeTrue();
		});
	});
});
