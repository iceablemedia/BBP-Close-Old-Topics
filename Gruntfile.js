module.exports = function(grunt) {

  require('load-grunt-tasks')(grunt, {scope: 'devDependencies'});

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    makepot: {
      target: {
        options: {
          domainPath: '/languages/',
          potFilename: 'bbp-close-old-topics.pot',
          type: 'wp-plugin'
        }
      }
    },

    watch: {
        pot: {
            files: ['*.php', '**/*.php', '**/**/*.php'],
            tasks: ['makepot'],
            options: {
              interrupt: true,
            },
          }
    },

});

grunt.registerTask('default', ['makepot']);

};
