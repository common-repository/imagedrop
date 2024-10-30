/*global module:false*/
module.exports = function (grunt) {

    grunt.initConfig({
        uglify: {
            options: {
                sourceMap: 'js/admin-script.map.js',
                sourceMappingURL: 'admin-script.map.js'
            },
            dist: {
                src: 'js/admin-script.js',
                dest: 'js/admin-script.min.js'
            }
        },
        jshint: {
            options: {
                curly: true,
                eqeqeq: true,
                immed: true,
                latedef: true,
                newcap: true,
                noarg: true,
                sub: true,
                undef: true,
                unused: true,
                boss: true,
                eqnull: true,
                browser: true,
                globals: {
                    jQuery: true,
                    id_data: true,
                    loading_template: true,
                    ajaxurl: true,
                    tinymce: true
                }
            },
            gruntfile: {
                src: 'Gruntfile.js'
            },
            src: {
                src: ['js/admin-script.js']
            }
        },
        watch: {
            js: {
                files: ['js/src/admin-script.js'],
                tasks: ['concat', 'uglify']
            }
        }
    });

    // These plugins provide necessary tasks.
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-watch');

    // Default task.
    grunt.registerTask('default', ['jshint']);
    grunt.registerTask('build', ['jshint', 'uglify']);
};
