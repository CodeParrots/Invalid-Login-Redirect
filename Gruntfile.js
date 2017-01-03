'use strict';

module.exports = function( grunt ) {

	var pkg = grunt.file.readJSON( 'package.json' );

	grunt.initConfig( {

		pkg: pkg,

		uglify: {
			dist: {
				files: {
					'lib/js/ilr-admin.min.js': [
						'lib/js/ilr-admin.js'
					],
					'modules/partials/js/ilr-notifications.min.js': [
						'modules/partials/js/ilr-notifications.js'
					],
					'modules/partials/js/jquery-growl.min.js': [
						'modules/partials/js/jquery-growl.js'
					],
				}
			}
		},

		postcss: {
			options: {
				map: true,
				processors: [
					require( 'autoprefixer-core' ) ( {
						browsers: [ 'last 2 versions' ]
					} )
				]
			},
			dist: {
				src: [ 'lib/css/*.css' ]
			}
		},

		cssmin: {
			target: {
				files: [
					{
						'lib/css/ilr-admin.min.css':
						[
							'lib/css/ilr-admin.css'
						],
					},
					{
						'lib/css/ilr-styles.min.css':
						[
							'lib/css/ilr-styles.css'
						],
					},
					{
						'lib/css/ilr-admin-rtl.min.css':
						[
							'lib/css/ilr-admin-rtl.css'
						],
					},
					{
						'lib/css/ilr-styles-rtl.min.css':
						[
							'lib/css/ilr-styles-rtl.css'
						],
					},
					{
						'modules/partials/css/ilr-notifications.min.css':
						[
							'modules/partials/css/ilr-notifications.css'
						],
					}
				]
			}
		},

		usebanner: {
			taskName: {
				options: {
					position: 'top',
					replace: true,
					banner: '/*\n'+
						' * @Plugin <%= pkg.title %>\n' +
						' * @Author <%= pkg.author %>\n'+
						' * @Site <%= pkg.site %>\n'+
						' * @Version <%= pkg.version %>\n' +
						' * @Build <%= grunt.template.today("mm-dd-yyyy") %>\n'+
						' */',
					linebreak: true
				},
				files: {
					src: [
						'lib/css/ilr-admin.min.css',
						'lib/css/ilr-admin-rtl.min.css',
						'lib/css/ilr-styles.min.css',
						'lib/css/ilr-styles-rtl.min.css',
						'lib/js/ilr-admin.min.js',
					]
				}
			}
		},

		// watch our project for changes
		watch: {
			admin_css: { // admin css
				files: [ 'lib/css/*.css', 'modules/partials/css/*.css' ],
				tasks: [ 'cssjanus', 'cssmin', 'usebanner' ],
				options: {
					spawn: false,
					event: ['all']
				},
			},
			admin_js: { // admin css
				files: [ 'lib/js/*.js', 'modules/partials/js/*.js' ],
				tasks: [ 'uglify', 'usebanner' ],
				options: {
					spawn: false,
					event: ['all']
				},
			},
		},

		cssjanus: {
			theme: {
				options: {
					swapLtrRtlInUrl: false
				},
				files: [
					{
						src: 'lib/css/ilr-admin.css',
						dest: 'lib/css/ilr-admin-rtl.css'
					},
					{
						src: 'lib/css/ilr-styles.css',
						dest: 'lib/css/ilr-styles-rtl.css'
					}
				]
			}
		},

		makepot: {
			target: {
				options: {
					domainPath: 'i18n/',
					include: [ '.+\.php' ],
					exclude: [ 'node_modules/' ],
					potComments: 'Copyright (c) {year} Code Parrots. All Rights Reserved.',
					potHeaders: {
						'x-poedit-keywordslist': true
					},
					processPot: function( pot, options ) {
						pot.headers['report-msgid-bugs-to'] = pkg.bugs.url;
						return pot;
					},
					type: 'wp-plugin',
					updatePoFiles: true
				}
			}
		},

		po2mo: {
			files: {
				src: 'languages/*.po',
				expand: true
			}
		},

		replace: {
			base_file: {
				src: [ 'invalid-login-redirect.php' ],
				overwrite: true,
				replacements: [
					{
						from: /Version: (.*)/,
						to: "Version: <%= pkg.version %>"
					},
					{
						from: /this->version = (.*)/,
						to: "this->version = '<%= pkg.version %>';"
					}
				]
			},
			readme_txt: {
				src: [ 'readme.txt' ],
				overwrite: true,
				replacements: [{
					from: /Stable tag: (.*)/,
					to: "Stable tag: <%= pkg.version %>"
				}]
			},
			readme_md: {
				src: [ 'README.md' ],
				overwrite: true,
				replacements: [
					{
						from: /# Invalid Login Redirect - (.*)/,
						to: "# Invalid Login Redirect - <%= pkg.version %>"
					},
					{
						from: /\*\*Stable tag:\*\*        (.*)/,
						to: "\**Stable tag:**        <%= pkg.version %> <br />"
					}
				]
			}
		}

	} );

	// load tasks
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-banner' );
	grunt.loadNpmTasks( 'grunt-postcss' ); // CSS autoprefixer plugin (cross-browser auto pre-fixes)
	grunt.loadNpmTasks( 'grunt-cssjanus' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-po2mo' );
	grunt.loadNpmTasks( 'grunt-text-replace' );
	grunt.loadNpmTasks( 'grunt-wp-deploy' );

	grunt.registerTask( 'default', [
		'cssjanus',
		'uglify',
		'postcss',
		'cssmin',
		'usebanner'
	] );

	grunt.registerTask( 'update-pot', [
		'makepot'
	] );

	grunt.registerTask( 'update-mo', [
		'po2mo'
	] );

	grunt.registerTask( 'update-translations', [
		'makepot',
		'po2mo'
	] );

	grunt.registerTask( 'bump-version', [
		'replace',
	] );

};
